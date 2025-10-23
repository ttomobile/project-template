<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OIDC Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
</head>
<body>
<main class="container">
    <article>
        <header>
            <h1>Sign in to continue</h1>
            <p>Client <strong>{{ $clientId }}</strong> is requesting access.</p>
        </header>
        <form method="POST" action="{{ url('/oidc/authorize') }}">
            @csrf
            <input type="hidden" name="client_id" value="{{ $pendingRequest['client_id'] }}">
            <input type="hidden" name="redirect_uri" value="{{ $pendingRequest['redirect_uri'] }}">
            <input type="hidden" name="response_type" value="{{ $pendingRequest['response_type'] }}">
            <input type="hidden" name="scope" value="{{ $pendingRequest['scope'] }}">
            <input type="hidden" name="state" value="{{ $pendingRequest['state'] }}">
            <input type="hidden" name="code_challenge" value="{{ $pendingRequest['code_challenge'] }}">
            <input type="hidden" name="code_challenge_method" value="{{ $pendingRequest['code_challenge_method'] ?? '' }}">

            @if ($errors->any())
                <p class="text-red-500">{{ $errors->first() }}</p>
            @endif

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
            @error('email')
                <p class="text-red-500">{{ $message }}</p>
            @enderror

            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            @error('password')
                <p class="text-red-500">{{ $message }}</p>
            @enderror

            <p>Requested scopes: {{ implode(', ', $requestedScopes) }}</p>

            <footer>
                <button type="submit">Sign in and continue</button>
            </footer>
        </form>
    </article>
</main>
</body>
</html>
