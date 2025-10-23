"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";

const fastapiBase =
  process.env.NEXT_PUBLIC_FASTAPI_BASE_URL ?? "http://localhost:8001";
const goaBase = process.env.NEXT_PUBLIC_GOA_BASE_URL ?? "http://localhost:8080";

type Source = "fastapi" | "goa";

type Status = {
  message: string;
  error?: string;
};

export default function CallbackPage(): JSX.Element {
  const router = useRouter();
  const [status, setStatus] = useState<Status>({
    message: "Processing login…",
  });

  useEffect(() => {
    async function complete(): Promise<void> {
      const params = new URLSearchParams(window.location.search);
      const source = (params.get("source") as Source | null) ?? "fastapi";
      const code = params.get("code");
      const state = params.get("state");

      if (!code || !state) {
        setStatus({
          message: "Missing response parameters from the identity provider.",
          error: "invalid",
        });
        return;
      }

      const baseUrl = source === "goa" ? goaBase : fastapiBase;
      try {
        setStatus({ message: "Exchanging authorization code for tokens…" });
        const response = await fetch(`${baseUrl}/auth/callback`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ code, state }),
        });
        if (!response.ok) {
          const detail = (await response.json().catch(() => null)) as {
            detail?: string;
          } | null;
          throw new Error(detail?.detail ?? "Token exchange failed");
        }
        window.localStorage.setItem(`${source}-state`, state);
        setStatus({
          message: "Login completed. Redirecting back to the dashboard…",
        });
        setTimeout(() => {
          router.replace("/");
        }, 1200);
      } catch (error) {
        setStatus({
          message: "Something went wrong while finishing the login flow.",
          error: error instanceof Error ? error.message : "Unexpected error",
        });
      }
    }

    void complete();
  }, [router]);

  return (
    <main className="flex min-h-screen items-center justify-center bg-slate-950 p-6 text-slate-100">
      <div className="w-full max-w-lg space-y-4 text-center">
        <h1 className="text-2xl font-semibold">Completing sign-in</h1>
        <p className="text-sm text-slate-300">{status.message}</p>
        {status.error ? (
          <div className="space-y-3">
            <p className="text-sm text-red-400">{status.error}</p>
            <Link
              className="inline-flex items-center justify-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-slate-100 hover:bg-slate-700"
              href="/"
            >
              Return to dashboard
            </Link>
          </div>
        ) : null}
      </div>
    </main>
  );
}
