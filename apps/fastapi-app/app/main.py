"""Application entrypoint for the FastAPI OIDC demo client."""

from __future__ import annotations

import base64
import hashlib
import os
import secrets
import time
from typing import Any, Dict

import httpx
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field


class StartResponse(BaseModel):
    """Response returned when initiating the authorization flow."""

    auth_url: str = Field(description="Authorization URL including query parameters")
    state: str = Field(description="Opaque value that must match the callback state")


class CallbackPayload(BaseModel):
    """Payload received from the frontend after the provider redirects back."""

    code: str
    state: str


class UserInfo(BaseModel):
    """Subset of user information retrieved from the provider."""

    sub: str
    email: str
    name: str | None = None


class SessionResponse(BaseModel):
    """Tokens and user information issued after a successful login."""

    access_token: str
    id_token: str
    expires_in: int
    scope: str
    user: UserInfo


OIDC_PROVIDER_URL = os.getenv("OIDC_PROVIDER_URL", "http://localhost:8000").rstrip("/")
CLIENT_ID = os.getenv("FASTAPI_OIDC_CLIENT_ID", "fastapi-client")
CLIENT_SECRET = os.getenv("FASTAPI_OIDC_CLIENT_SECRET", "")
REDIRECT_URI = os.getenv("FASTAPI_OIDC_REDIRECT_URI", "http://localhost:3000/callback?source=fastapi")
REQUESTED_SCOPE = os.getenv("FASTAPI_OIDC_SCOPE", "openid profile email")
FRONTEND_ORIGIN = os.getenv("FASTAPI_FRONTEND_ORIGIN", "http://localhost:3000")

pending_logins: Dict[str, Dict[str, Any]] = {}
completed_sessions: Dict[str, SessionResponse] = {}
discovery_document: Dict[str, Any] = {}

app = FastAPI(title="FastAPI OIDC Client", version="0.2.0")
app.add_middleware(
    CORSMiddleware,
    allow_origins=[FRONTEND_ORIGIN, "http://localhost:3000", "http://127.0.0.1:3000"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.on_event("startup")
async def load_discovery() -> None:
    """Fetch the provider discovery document once on startup."""

    global discovery_document
    discovery_document = {}
    url = f"{OIDC_PROVIDER_URL}/.well-known/openid-configuration"
    try:
        async with httpx.AsyncClient(timeout=10.0) as client:
            response = await client.get(url)
            response.raise_for_status()
            discovery_document = response.json()
    except httpx.HTTPError:
        discovery_document = {}


@app.get("/health", summary="Health check", tags=["Health"])
async def health_check() -> dict[str, str]:
    """Return a simple health-check payload."""

    return {"status": "ok"}


@app.post("/auth/start", response_model=StartResponse, tags=["OIDC"])
async def start_authorization() -> StartResponse:
    """Generate an authorization URL using PKCE and return it to the caller."""

    state = secrets.token_urlsafe(16)
    code_verifier = secrets.token_urlsafe(64)
    code_challenge = _code_challenge(code_verifier)

    auth_endpoint = discovery_document.get("authorization_endpoint") or f"{OIDC_PROVIDER_URL}/oidc/authorize"
    params = httpx.QueryParams(
        {
            "response_type": "code",
            "client_id": CLIENT_ID,
            "redirect_uri": REDIRECT_URI,
            "scope": REQUESTED_SCOPE,
            "state": state,
            "code_challenge": code_challenge,
            "code_challenge_method": "S256",
        }
    )
    pending_logins[state] = {
        "code_verifier": code_verifier,
        "created_at": time.time(),
    }
    return StartResponse(auth_url=f"{auth_endpoint}?{params}", state=state)


@app.post("/auth/callback", response_model=SessionResponse, tags=["OIDC"])
async def complete_authorization(payload: CallbackPayload) -> SessionResponse:
    """Exchange the authorization code for tokens and persist the session."""

    pending = pending_logins.pop(payload.state, None)
    if pending is None:
        raise HTTPException(status_code=404, detail="Unknown or expired state")

    token_endpoint = discovery_document.get("token_endpoint") or f"{OIDC_PROVIDER_URL}/oidc/token"
    request_data = {
        "grant_type": "authorization_code",
        "code": payload.code,
        "redirect_uri": REDIRECT_URI,
        "client_id": CLIENT_ID,
        "code_verifier": pending["code_verifier"],
    }
    if CLIENT_SECRET:
        request_data["client_secret"] = CLIENT_SECRET

    async with httpx.AsyncClient(timeout=10.0) as client:
        response = await client.post(token_endpoint, data=request_data)
        try:
            response.raise_for_status()
        except httpx.HTTPStatusError as exc:
            raise HTTPException(status_code=exc.response.status_code, detail="Token exchange failed") from exc
        token_payload = response.json()

    session = SessionResponse(
        access_token=token_payload.get("access_token", ""),
        id_token=token_payload.get("id_token", ""),
        expires_in=int(token_payload.get("expires_in", 3600)),
        scope=token_payload.get("scope", REQUESTED_SCOPE),
        user=await _fetch_userinfo(token_payload.get("access_token", "")),
    )

    completed_sessions[payload.state] = session
    return session


@app.get("/sessions/{state}", response_model=SessionResponse, tags=["OIDC"])
async def get_session(state: str) -> SessionResponse:
    """Retrieve a previously completed session by state."""

    session = completed_sessions.get(state)
    if session is None:
        raise HTTPException(status_code=404, detail="Session not found")
    return session


def _code_challenge(code_verifier: str) -> str:
    digest = hashlib.sha256(code_verifier.encode("utf-8")).digest()
    return base64.urlsafe_b64encode(digest).rstrip(b"=").decode("ascii")


async def _fetch_userinfo(access_token: str) -> UserInfo:
    if not access_token:
        raise HTTPException(status_code=400, detail="Missing access token")

    endpoint = discovery_document.get("userinfo_endpoint") or f"{OIDC_PROVIDER_URL}/oidc/userinfo"
    async with httpx.AsyncClient(timeout=10.0) as client:
        response = await client.get(endpoint, headers={"Authorization": f"Bearer {access_token}"})
        try:
            response.raise_for_status()
        except httpx.HTTPStatusError as exc:
            raise HTTPException(status_code=exc.response.status_code, detail="Failed to fetch userinfo") from exc
        payload = response.json()

    return UserInfo(sub=payload.get("sub", ""), email=payload.get("email", ""), name=payload.get("name"))
