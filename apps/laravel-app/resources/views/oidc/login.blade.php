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
            <p>Client <strong>{{ $client['id'] }}</strong> is requesting access.</p>
        </header>
        <form method="POST" action="{{ url('/oidc/authorize') }}">
            @csrf
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <p>Requested scopes: {{ implode(', ', $requestedScopes) }}</p>

            <footer>
                <button type="submit">Sign in and continue</button>
            </footer>
        </form>
    </article>
</main>
</body>
</html>
