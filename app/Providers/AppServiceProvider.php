<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
{
    Auth::provider('custom_jwt_provider', function ($app, array $config) {
        return new class implements UserProvider {
            public function retrieveById($identifier) {
                $user = new \App\Models\User();
                $user->id = $identifier;
                return $user;
            }

            public function retrieveByToken($identifier, $token) { return null; }
            public function updateRememberToken(Authenticatable $user, $token) {}
            public function retrieveByCredentials(array $credentials) { return null; }
            public function validateCredentials(Authenticatable $user, array $credentials) { return false; }
            
            // Método requerido por las últimas versiones de Laravel
            public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false) {}
        };
    });
}
}