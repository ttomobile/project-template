# Laravel OIDC Provider

This Laravel application exposes a minimal OpenID Connect provider used by the
FastAPI and Goa sample clients. It implements the discovery, authorization,
token, userinfo, and JWKS endpoints backed by Laravel sessions and cache.

## Endpoints

| Endpoint | Description |
| --- | --- |
| `/.well-known/openid-configuration` | OpenID discovery metadata |
| `/oidc/authorize` | Authorization endpoint with a simple email/password form |
| `/oidc/token` | Authorization code exchange supporting PKCE (S256) |
| `/oidc/userinfo` | Returns profile data for issued access tokens |
| `/oidc/jwks.json` | RSA public key set used to validate ID tokens |

The application seeds a single demo user (`demo@example.com` / `password`) via
`OidcDemoSeeder`. Update `config/oidc.php` to register additional clients or
provide different signing keys.

## Development

```bash
composer install
npm install
php artisan migrate --seed
php artisan serve
```

Run the feature tests to confirm the authorization-code flow:

```bash
php artisan test --filter=OidcProviderTest
```
