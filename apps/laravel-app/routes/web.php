<?php

use App\Http\Controllers\OidcController;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/.well-known/openid-configuration', [OidcController::class, 'discovery']);
Route::get('/oidc/jwks.json', [OidcController::class, 'jwks']);

Route::get('/oidc/authorize', [OidcController::class, 'authorize'])->name('oidc.authorize');
Route::post('/oidc/authorize', [OidcController::class, 'authenticate'])
    ->name('oidc.authenticate');

Route::post('/oidc/token', [OidcController::class, 'token'])
    ->withoutMiddleware(['web', VerifyCsrfToken::class])
    ->middleware('api');

Route::get('/oidc/userinfo', [OidcController::class, 'userinfo'])
    ->withoutMiddleware(['web', VerifyCsrfToken::class])
    ->middleware('api');
