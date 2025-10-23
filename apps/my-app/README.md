# Next.js OIDC Playground

This Next.js front-end coordinates the Laravel OpenID Connect provider with the
Goa and FastAPI sample APIs. It renders two dashboards that allow you to launch
PKCE authorization-code flows, inspect the tokens stored by each backend, and
clear their local session state.

## Getting Started

1. Copy `.env.local.example` to `.env.local` if you use non-default API ports
   or want to customise endpoints. Avoid trailing slashes in the base URLs to
   prevent double slashes in requests:

   ```bash
   cp .env.local.example .env.local
   # edit .env.local if required
   ```

2. Install dependencies and start the development server:

   ```bash
   npm install
   npm run dev
   ```

3. Open [http://localhost:3000](http://localhost:3000) to reach the dashboard.
   The `/callback` route completes the authorization-code exchange with the
   chosen backend and redirects you back to the landing page.

States are persisted in `localStorage` so you can refresh the dashboard after a
successful login. Use the **Clear state** action for each card to remove the
association when you want to start over.
