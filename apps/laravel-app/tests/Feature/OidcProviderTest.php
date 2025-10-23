<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OidcProviderTest extends TestCase
{
    use RefreshDatabase;

    public function testDiscoveryEndpointProvidesMetadata(): void
    {
        $response = $this->get('/.well-known/openid-configuration');

        $response->assertOk();
        $response->assertJsonFragment([
            'issuer' => config('oidc.issuer'),
            'authorization_endpoint' => url('/oidc/authorize'),
        ]);
    }

    public function testAuthorizationCodeFlowIssuesTokens(): void
    {
        $user = User::factory()->create([
            'email' => 'demo@example.com',
            'password' => 'password',
        ]);

        $client = config('oidc.clients.fastapi');
        $codeVerifier = Str::random(64);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $params = [
            'client_id' => $client['id'],
            'redirect_uri' => $client['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => 'test-state',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ];

        $this->get('/oidc/authorize?' . http_build_query($params))->assertOk();

        $loginResponse = $this->post('/oidc/authorize', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $loginResponse->assertRedirect();
        $location = $loginResponse->headers->get('Location');
        $this->assertNotNull($location);

        $parsedUrl = parse_url($location);
        $this->assertIsArray($parsedUrl, 'Redirect location must be a valid URL');
        $this->assertArrayHasKey('query', $parsedUrl, 'Redirect must include query parameters');

        parse_str($parsedUrl['query'], $query);
        $this->assertArrayHasKey('code', $query);
        $this->assertSame('test-state', $query['state']);

        $tokenResponse = $this->post('/oidc/token', [
            'grant_type' => 'authorization_code',
            'code' => $query['code'],
            'redirect_uri' => $client['redirect_uri'],
            'client_id' => $client['id'],
            'code_verifier' => $codeVerifier,
        ]);

        $tokenResponse->assertOk();
        $tokenData = $tokenResponse->json();
        $this->assertArrayHasKey('access_token', $tokenData);
        $this->assertArrayHasKey('id_token', $tokenData);

        $userinfoResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokenData['access_token'],
        ])->get('/oidc/userinfo');

        $userinfoResponse->assertOk();
        $userinfoResponse->assertJsonFragment([
            'sub' => (string) $user->getKey(),
            'email' => $user->email,
        ]);
    }
}
