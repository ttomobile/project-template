<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Firebase\JWT\JWT;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Illuminate\View\View;

class OidcController extends Controller
{
    private const AUTH_SESSION_KEY = 'oidc.pending_request';
    private const CACHE_CODE_PREFIX = 'oidc.code.';
    private const CACHE_TOKEN_PREFIX = 'oidc.access.';

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly UrlGenerator $urlGenerator,
    ) {
    }

    public function discovery(): Response
    {
        $issuer = config('oidc.issuer');

        $authorizationEndpoint = $this->urlGenerator->to('/oidc/authorize');
        $tokenEndpoint = $this->urlGenerator->to('/oidc/token');
        $userinfoEndpoint = $this->urlGenerator->to('/oidc/userinfo');
        $jwksUri = $this->urlGenerator->to('/oidc/jwks.json');

        $metadata = [
            'issuer' => $issuer,
            'authorization_endpoint' => $authorizationEndpoint,
            'token_endpoint' => $tokenEndpoint,
            'userinfo_endpoint' => $userinfoEndpoint,
            'jwks_uri' => $jwksUri,
            'response_types_supported' => ['code'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'scopes_supported' => config('oidc.default_scopes'),
            'token_endpoint_auth_methods_supported' => ['none', 'client_secret_post'],
            'grant_types_supported' => ['authorization_code'],
            'code_challenge_methods_supported' => ['S256'],
        ];

        return response($metadata);
    }

    public function jwks(): Response
    {
        $publicKeyPem = config('oidc.signing.public_key');
        $publicKey = openssl_pkey_get_public($publicKeyPem);
        if ($publicKey === false) {
            return response(['error' => 'invalid_key'], 500);
        }

        $details = openssl_pkey_get_details($publicKey);
        if ($details === false || ! isset($details['rsa'])) {
            return response(['error' => 'invalid_key'], 500);
        }

        $n = $this->base64UrlEncode($details['rsa']['n']);
        $e = $this->base64UrlEncode($details['rsa']['e']);

        $jwks = [
            'keys' => [[
                'kty' => 'RSA',
                'kid' => config('oidc.signing.kid'),
                'use' => 'sig',
                'alg' => 'RS256',
                'n' => $n,
                'e' => $e,
            ]],
        ];

        return response($jwks);
    }

    public function authorize(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'url'],
            'response_type' => ['required', 'in:code'],
            'scope' => ['required', 'string'],
            'state' => ['required', 'string'],
            'code_challenge' => ['required', 'string'],
            'code_challenge_method' => ['nullable', 'in:S256'],
        ]);

        $client = $this->findClient($validated['client_id']);
        abort_if($client === null, 400, 'Unknown client');
        abort_if($client['redirect_uri'] !== $validated['redirect_uri'], 400, 'Invalid redirect URI');

        $request->session()->put(self::AUTH_SESSION_KEY, $validated);

        if (Auth::check()) {
            $request->session()->forget(self::AUTH_SESSION_KEY);

            return $this->issueCodeAndRedirect(Auth::user(), $validated);
        }

        return view('oidc.login', [
            'client' => $client,
            'clientId' => $client['id'] ?? $validated['client_id'],
            'pendingRequest' => $validated,
            'requestedScopes' => explode(' ', $validated['scope']),
        ]);
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::AUTH_SESSION_KEY);
        abort_if($pending === null, 400, 'Missing authorization request');

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();
        $hashedPassword = $user?->password ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $validPassword = Hash::check($credentials['password'], $hashedPassword);

        if ($user === null || ! $validPassword) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        Auth::login($user);
        $request->session()->forget(self::AUTH_SESSION_KEY);

        return $this->issueCodeAndRedirect($user, $pending);
    }

    public function token(Request $request): Response
    {
        $request->validate([
            'grant_type' => ['required', 'in:authorization_code'],
            'code' => ['required', 'string'],
            'redirect_uri' => ['required', 'url'],
            'client_id' => ['required', 'string'],
            'code_verifier' => ['required', 'string'],
            'client_secret' => ['nullable', 'string'],
        ]);

        $code = $this->cache->pull(self::CACHE_CODE_PREFIX . (string) $request->input('code'));
        if ($code === null) {
            return response(['error' => 'invalid_grant'], 400);
        }

        $clientId = (string) $request->input('client_id');
        $client = $this->findClient($clientId);
        if ($client === null) {
            return response(['error' => 'invalid_client'], 400);
        }

        $redirectUri = (string) $request->input('redirect_uri');
        if ($client['redirect_uri'] !== $redirectUri) {
            return response(['error' => 'invalid_grant'], 400);
        }

        if ($code['client_id'] !== $client['id']) {
            return response(['error' => 'invalid_grant'], 400);
        }

        $codeVerifier = (string) $request->input('code_verifier');
        if (! $this->verifyCodeChallenge($code, $codeVerifier)) {
            return response(['error' => 'invalid_grant'], 400);
        }

        $user = User::find($code['user_id']);
        if ($user === null) {
            return response(['error' => 'invalid_grant'], 400);
        }

        $tokens = $this->issueTokens($user, $client['id'], $code['scopes']);

        return response($tokens);
    }

    public function userinfo(Request $request): Response
    {
        $authorization = $request->bearerToken();
        if ($authorization === null) {
            return response(['error' => 'invalid_token'], 401);
        }

        $token = $this->cache->get(self::CACHE_TOKEN_PREFIX . $authorization);
        if ($token === null) {
            return response(['error' => 'invalid_token'], 401);
        }

        $user = User::find($token['user_id']);
        if ($user === null) {
            return response(['error' => 'invalid_token'], 401);
        }

        return response([
            'sub' => (string) $user->getKey(),
            'name' => $user->name ?? $user->email,
            'email' => $user->email,
            'email_verified' => true,
        ]);
    }

    private function issueCodeAndRedirect(User $user, array $pending): RedirectResponse
    {
        $code = bin2hex(random_bytes(32));
        $expiresAt = CarbonImmutable::now()->addMinutes(10);

        $this->cache->put(self::CACHE_CODE_PREFIX . $code, [
            'user_id' => $user->getKey(),
            'client_id' => $pending['client_id'],
            'redirect_uri' => $pending['redirect_uri'],
            'code_challenge' => $pending['code_challenge'],
            'code_challenge_method' => $pending['code_challenge_method'] ?? 'S256',
            'scopes' => explode(' ', $pending['scope']),
        ], $expiresAt);

        return redirect()->away($this->buildRedirectUri($pending['redirect_uri'], [
            'code' => $code,
            'state' => $pending['state'],
        ]));
    }

    private function issueTokens(User $user, string $audience, array $scopes): array
    {
        $accessToken = bin2hex(random_bytes(40));
        $expiresIn = 3600;
        $now = CarbonImmutable::now();
        $idToken = $this->buildIdToken($user, $audience, $now, $expiresIn, $scopes);

        $this->cache->put(self::CACHE_TOKEN_PREFIX . $accessToken, [
            'user_id' => $user->getKey(),
            'expires_at' => $now->addSeconds($expiresIn),
        ], $now->addSeconds($expiresIn));

        return [
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
            'access_token' => $accessToken,
            'id_token' => $idToken,
            'scope' => implode(' ', $scopes),
        ];
    }

    private function buildRedirectUri(string $redirect, array $parameters): string
    {
        $parts = parse_url($redirect);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException('Invalid redirect URI.');
        }

        $existingQuery = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $existingQuery);
        }

        $query = http_build_query(array_merge($existingQuery, $parameters), '', '&', PHP_QUERY_RFC3986);

        $authority = $parts['host'];
        if (isset($parts['user'])) {
            $authority = $parts['user'] . (isset($parts['pass']) ? ':' . $parts['pass'] : '') . '@' . $authority;
        }
        if (isset($parts['port'])) {
            $authority .= ':' . $parts['port'];
        }

        $path = $parts['path'] ?? '';

        $rebuilt = $parts['scheme'] . '://' . $authority . $path;
        if ($query !== '') {
            $rebuilt .= '?' . $query;
        }

        if (isset($parts['fragment'])) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
    }

    private function buildIdToken(User $user, string $audience, CarbonImmutable $issuedAt, int $expiresIn, array $scopes): string
    {
        $payload = [
            'iss' => config('oidc.issuer'),
            'sub' => (string) $user->getKey(),
            'aud' => $audience,
            'iat' => $issuedAt->getTimestamp(),
            'exp' => $issuedAt->addSeconds($expiresIn)->getTimestamp(),
        ];

        if (in_array('email', $scopes, true)) {
            $payload['email'] = $user->email;
            $payload['email_verified'] = true;
        }

        if (in_array('profile', $scopes, true)) {
            $payload['name'] = $user->name ?? $user->email;
        }

        $privateKey = config('oidc.signing.private_key');

        return JWT::encode($payload, $privateKey, 'RS256', config('oidc.signing.kid'));
    }

    private function verifyCodeChallenge(array $code, string $codeVerifier): bool
    {
        if (($code['code_challenge_method'] ?? 'S256') === 'S256') {
            $hashed = hash('sha256', $codeVerifier, true);
            $expected = $this->base64UrlEncode($hashed);

            return hash_equals($code['code_challenge'], $expected);
        }

        return false;
    }

    private function findClient(string $clientId): ?array
    {
        foreach (config('oidc.clients') as $client) {
            if ($client['id'] === $clientId) {
                return $client;
            }
        }

        return null;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
