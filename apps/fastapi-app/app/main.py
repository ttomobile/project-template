"""Application entrypoint for the FastAPI service."""

from fastapi import FastAPI

app = FastAPI(title="Project Template API", version="0.1.0")


@app.get("/health", summary="Health check", tags=["Health"])
def health_check() -> dict[str, str]:
    """Return a simple health-check payload."""
    return {"status": "ok"}
