<?php

return [
    'issuer' => env('OIDC_ISSUER', rtrim(env('APP_URL', 'http://localhost:8000'), '/')),
    'default_scopes' => ['openid', 'profile', 'email'],
    'clients' => [
        'fastapi' => [
            'id' => env('FASTAPI_OIDC_CLIENT_ID', 'fastapi-client'),
            'redirect_uri' => env('FASTAPI_OIDC_REDIRECT_URI', 'http://localhost:3000/callback?source=fastapi'),
        ],
        'goa' => [
            'id' => env('GOA_OIDC_CLIENT_ID', 'goa-client'),
            'redirect_uri' => env('GOA_OIDC_REDIRECT_URI', 'http://localhost:3000/callback?source=goa'),
        ],
    ],
    'signing' => [
        'kid' => env('OIDC_KEY_ID', 'demo-key'),
        'private_key' => env('OIDC_PRIVATE_KEY', <<<'KEY'
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCs21GJS6Yyudgl
6Y8ILnuOj+TSpo/OIcmBsZ6vJQoX2/+ruGghf6MffOGBoYns1bQpRXqVabtcQk0c
ur7MLXKHr1OLIMsV9HMZ35ub0FAfTfiVOLLJN4msljyg3RJO1mMwibMcPsCPAZ78
74X4LCqPH90HmpggBuOBmwtMWa8lxnyNrC6I5qDMHeLHGeR925xa43GG8y0/OfEo
R2mzDOOifHt+xLRpwCakrnfnjgj5OjlGrdXeIXa45vkBIVqStGj1AXu9IRVLGI07
8YAAS2/mlU15vDBVtumvmM5ey3DmOpdplxKRwMG5mIuRfoGqONwe5scS3PE13Sgm
WxmA4vjdAgMBAAECggEAVd2AbE4q2endiD+z8GF+uPubtFRO/RtliMpdxC0HDYEY
SQoKmBFaS6ryLj27UO17WEEOOqhSDOtWeN3/J1ewG9ypCGSa3WYXwxODrevV0Scf
Q6jtjuzKs/PRXCtVC9qNXTAZy/8UEjXA725gQz5VSdGPL1bJGKezXc9R1nR6f61/
cjWwsJBiLpWDRh8l7sOWyKKt2hG9985m+PkcM6VgRtVutZCGdRZoqtfgu0Xv/uno
f5Dvzvu27OXNuFWDZRq7d0j5raLBeY+jNEPBAkvBJRTGeqSDZ+2hPRVADUA9KiGT
7VAfKsGe2+VDHpzOURyJrrujXhHNzIb4dq95RvrzdQKBgQDUvkbpgW5d+9f99d8p
wjMO7L+2fJrUQJdS8GypFWUiiAXtMqWU7b2Ct08YBQFaU5QW8U8XGjB8pjm1YAj9
T19Jl4vYG7tVyHCsVWux4wdUJXb8BDnmwpZ4QwQREDCl9/xfgxSTsYg4GTRz2wok
Y4s8mGbdJ6f3j02SudO7vomf5wKBgQDQAN1Q/t/PoqygDYg7mESeCVMKy5CwrXLH
DQ1ce4PJZ9vt5zjAFgk3XDS3wHafZpNeSC3sxqp26zQezYnaqMOoKQ+P1z5785mZ
nBYFjWs18QYyMqcMpt03OEzDQ4PBxEDcLhmHPmOqKYACIszOl2hAf9Qr7aKsdXJQ
vOtEErKYmwKBgDaRX8sBIfgFYw+HA2jIoSQQ4dPC/ku3DZl+hcCQ9lH3Jd4DgalD
mbpvnmAA2Kn8ih2gY7L/SrSORnsZWPTwaPaNYpdZ9aE5On1Zo7gLDZQtz+kwhFGG
U+Yg+mgOCQxpIVi2XI7NmK0a+fNFmcJfrhUq2iebxl+faDxcYczkQJS9AoGAPgxj
+OtvHGNsl3ox74UmwvYJHalICkxTdul/2NzHnWcsBjX5ieOI8EjDOSVivX4969wg
RwekhkD3lVC/FMlPRHSrPb951kP+yAH118Yt+zNhI8xUZMPKLdTPoVgcj8rZhlUC
LIQB+xrSItD6w44K/WKkik4jPsryRP92NyJUwI0CgYEAsPLA6R5rIwyt3k8QUcAd
mYD9axwP2ISy2uZWGzwelor8bgF5SdcNkmPkFOFid0sFjz9eAAWzrF0KTLyIpoXH
rYpIZ9RiVfNzSheMXCrARW5FM59ZdIFVA5Y+T1pvuD4ef9+XcFcDWFNT6EhqDBPW
pon20vaoYhehxh/CeBLWxqs=
-----END PRIVATE KEY-----
KEY
        ),
        'public_key' => env('OIDC_PUBLIC_KEY', <<<'KEY'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArNtRiUumMrnYJemPCC57
jo/k0qaPziHJgbGeryUKF9v/q7hoIX+jH3zhgaGJ7NW0KUV6lWm7XEJNHLq+zC1y
h69TiyDLFfRzGd+bm9BQH034lTiyyTeJrJY8oN0STtZjMImzHD7AjwGe/O+F+Cwq
jx/dB5qYIAbjgZsLTFmvJcZ8jawuiOagzB3ixxnkfducWuNxhvMtPznxKEdpswzj
onx7fsS0acAmpK53544I+To5Rq3V3iF2uOb5ASFakrRo9QF7vSEVSxiNO/GAAEtv
5pVNebwwVbbpr5jOXstw5jqXaZcSkcDBuZiLkX6BqjjcHubHEtzxNd0oJlsZgOL4
3QIDAQAB
-----END PUBLIC KEY-----
KEY
        ),
    ],
];
