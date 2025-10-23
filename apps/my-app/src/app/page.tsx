"use client";

import Link from "next/link";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";

type ProviderKey = "fastapi" | "goa";

type Session = {
  access_token: string;
  id_token: string;
  expires_in: number;
  scope: string;
  user: {
    sub: string;
    email: string;
    name?: string | null;
  };
};

type ProviderState = {
  session: Session | null;
  state: string | null;
  loading: boolean;
  error: string | null;
  startLogin: () => Promise<void>;
  refreshSession: () => Promise<void>;
  clearSession: () => void;
  baseUrl: string;
  title: string;
  description: string;
};

const sanitizeBaseUrl = (value: string): string => value.replace(/\/+$/, "");

const fastapiBase = sanitizeBaseUrl(
  process.env.NEXT_PUBLIC_FASTAPI_BASE_URL ?? "http://localhost:8001",
);
const goaBase = sanitizeBaseUrl(
  process.env.NEXT_PUBLIC_GOA_BASE_URL ?? "http://localhost:8080",
);

export default function Home(): JSX.Element {
  const fastapi = useOidcProvider("fastapi", fastapiBase, {
    title: "FastAPI client",
    description:
      "Python FastAPI service orchestrating the code flow using the Laravel provider.",
  });
  const goa = useOidcProvider("goa", goaBase, {
    title: "Goa client",
    description:
      "Goa-based Go service demonstrating the same login integration.",
  });

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">
      <header className="border-b border-slate-900/80 bg-slate-950/90 backdrop-blur">
        <div className="mx-auto flex max-w-5xl flex-col gap-4 px-6 py-10 sm:px-10">
          <span className="inline-flex w-fit rounded-full border border-slate-700 px-3 py-1 text-xs uppercase tracking-wide text-slate-400">
            Laravel × FastAPI × Goa × Next.js
          </span>
          <h1 className="text-3xl font-semibold sm:text-4xl">
            Laravel OpenID Connect Playground
          </h1>
          <p className="text-sm text-slate-400 sm:text-base">
            Launch the PKCE authorization code flow against the Laravel provider
            and inspect the resulting sessions managed by the FastAPI and Goa
            sample APIs.
          </p>
          <div className="flex flex-wrap items-center gap-3 text-xs text-slate-500">
            <Link
              className="underline-offset-4 hover:underline"
              href="/callback"
            >
              Callback handler
            </Link>
            <a
              className="underline-offset-4 hover:underline"
              href="https://openid.net/specs/openid-connect-core-1_0.html"
              target="_blank"
              rel="noreferrer noopener"
            >
              OpenID Connect core spec
            </a>
          </div>
        </div>
      </header>
      <main className="mx-auto flex max-w-5xl flex-col gap-8 px-6 py-12 sm:px-10">
        <section className="grid gap-6 lg:grid-cols-2">
          <ProviderCard {...fastapi} />
          <ProviderCard {...goa} />
        </section>
        <section className="rounded-2xl border border-slate-900 bg-slate-900/60 p-6">
          <h2 className="text-lg font-medium text-slate-100">
            How the flow works
          </h2>
          <ol className="mt-4 list-decimal space-y-2 pl-5 text-sm text-slate-300">
            <li>
              Click <strong>Start login</strong> to request an authorization URL
              from the chosen backend.
            </li>
            <li>
              After authenticating with the Laravel provider, you are returned
              to the Next.js callback page where the backend finalises the code
              exchange.
            </li>
            <li>
              Once redirected back here, use <strong>Fetch session</strong> to
              display the tokens and user profile saved by the API.
            </li>
          </ol>
          <p className="mt-4 text-xs text-slate-500">
            States are persisted in local storage so you can refresh this page
            without losing the session context. Use
            <strong> Clear state</strong> to remove the association.
          </p>
        </section>
      </main>
    </div>
  );
}

function useOidcProvider(
  provider: ProviderKey,
  baseUrl: string,
  meta: Pick<ProviderState, "title" | "description">,
): ProviderState {
  const storageKey = useMemo(() => `${provider}-state`, [provider]);
  const [session, setSession] = useState<Session | null>(null);
  const [state, setState] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const stateRef = useRef<string | null>(null);

  useEffect(() => {
    stateRef.current = state;
  }, [state]);

  const fetchSession = useCallback(
    async (targetState: string, signal?: AbortSignal) => {
      setLoading(true);
      try {
        const response = await fetch(`${baseUrl}/sessions/${targetState}`, {
          signal,
        });
        if (!response.ok) {
          if (response.status === 404) {
            setError("Session not found yet. Complete the login flow first.");
            setSession(null);
            return;
          }
          if (response.status === 401 || response.status === 403) {
            throw new Error("Unauthorized. Please re-run the login flow.");
          }
          throw new Error(`Failed to fetch session (${response.status})`);
        }
        const data: Session = await response.json();
        setSession(data);
        setError(null);
      } catch (err) {
        if ((err as DOMException | undefined)?.name === "AbortError") {
          return;
        }
        setError(err instanceof Error ? err.message : "Unexpected error");
      } finally {
        setLoading(false);
      }
    },
    [baseUrl],
  );

  const fetchSessionRef = useRef(fetchSession);
  useEffect(() => {
    fetchSessionRef.current = fetchSession;
  }, [fetchSession]);

  useEffect(() => {
    if (typeof window === "undefined") {
      return;
    }
    const storedState = window.localStorage.getItem(storageKey);
    if (!storedState) {
      return;
    }

    setState(storedState);
    const controller = new AbortController();
    void fetchSessionRef.current(storedState, controller.signal);

    return () => controller.abort();
  }, [storageKey]);

  const startLogin = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await fetch(`${baseUrl}/auth/start`, { method: "POST" });
      if (!response.ok) {
        throw new Error(
          `Failed to create authorization request (${response.status})`,
        );
      }
      const data = (await response.json()) as {
        auth_url: string;
        state: string;
      };
      if (typeof window !== "undefined") {
        window.localStorage.setItem(storageKey, data.state);
        setState(data.state);
        window.location.href = data.auth_url;
      }
    } catch (err) {
      setLoading(false);
      setError(err instanceof Error ? err.message : "Unexpected error");
    }
  }, [baseUrl, storageKey]);

  const refreshSession = useCallback(async () => {
    const currentState = stateRef.current;
    if (!currentState) {
      setError("Start the login flow to obtain a state first.");
      return;
    }
    await fetchSession(currentState);
  }, [fetchSession]);

  const clearSession = useCallback(() => {
    if (typeof window !== "undefined") {
      window.localStorage.removeItem(storageKey);
    }
    setSession(null);
    setState(null);
    setError(null);
  }, [storageKey]);

  return {
    session,
    state,
    loading,
    error,
    startLogin,
    refreshSession,
    clearSession,
    baseUrl,
    ...meta,
  };
}

function ProviderCard(state: ProviderState): JSX.Element {
  const {
    title,
    description,
    session,
    state: flowState,
    loading,
    error,
    startLogin,
    refreshSession,
    clearSession,
    baseUrl,
  } = state;

  return (
    <article className="flex h-full flex-col justify-between rounded-2xl border border-slate-900 bg-slate-900/70 p-6 shadow-lg shadow-slate-950/50">
      <div className="space-y-4">
        <header className="space-y-2">
          <h2 className="text-xl font-semibold text-slate-100">{title}</h2>
          <p className="text-sm text-slate-400">{description}</p>
          <p className="text-xs text-slate-500">
            API base:{" "}
            <span className="font-mono text-slate-300">{baseUrl}</span>
          </p>
          {flowState ? (
            <p className="text-xs text-emerald-400">
              Active state: {flowState}
            </p>
          ) : (
            <p className="text-xs text-slate-500">No active login state yet.</p>
          )}
        </header>
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            onClick={startLogin}
            className="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-medium text-emerald-950 transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-60"
            disabled={loading}
          >
            {loading ? "Processing…" : "Start login"}
          </button>
          <button
            type="button"
            onClick={refreshSession}
            className="rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 transition hover:border-emerald-400 hover:text-emerald-200 disabled:cursor-not-allowed disabled:opacity-60"
            disabled={!flowState || loading}
          >
            Fetch session
          </button>
          <button
            type="button"
            onClick={clearSession}
            className="rounded-lg border border-slate-800 px-3 py-2 text-xs font-medium text-slate-400 transition hover:border-red-500 hover:text-red-300 disabled:cursor-not-allowed disabled:opacity-60"
            disabled={!flowState}
          >
            Clear state
          </button>
        </div>
        {error && <p className="text-sm text-red-400">{error}</p>}
        {session && (
          <div className="space-y-3 rounded-xl border border-slate-800 bg-slate-950/70 p-4 text-sm text-slate-200">
            <div className="space-y-1">
              <p className="text-xs uppercase tracking-wide text-slate-500">
                User
              </p>
              <p>
                {session.user.name ?? session.user.email}{" "}
                <span className="text-slate-500">({session.user.sub})</span>
              </p>
              <p className="text-xs text-slate-500">Scope: {session.scope}</p>
            </div>
            <div className="space-y-1">
              <p className="text-xs uppercase tracking-wide text-slate-500">
                Access token
              </p>
              <code className="block break-all rounded bg-slate-900 px-2 py-1 text-xs text-emerald-300">
                {truncateToken(session.access_token)}
              </code>
            </div>
            <div className="space-y-1">
              <p className="text-xs uppercase tracking-wide text-slate-500">
                ID token
              </p>
              <code className="block break-all rounded bg-slate-900 px-2 py-1 text-xs text-indigo-300">
                {truncateToken(session.id_token)}
              </code>
            </div>
            <details className="group">
              <summary className="cursor-pointer text-xs text-slate-400 hover:text-slate-200">
                View raw session payload
              </summary>
              <pre className="mt-2 overflow-x-auto rounded bg-slate-900/80 p-3 text-xs text-slate-300">
                {JSON.stringify(session, null, 2)}
              </pre>
            </details>
          </div>
        )}
      </div>
    </article>
  );
}

function truncateToken(token: string): string {
  if (!token) {
    return "(empty)";
  }
  if (token.length <= 48) {
    return token;
  }
  return `${token.slice(0, 24)}…${token.slice(-12)}`;
}
