<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ActiveProfileManager;
use App\Services\RemoteApi\TokenManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(TokenManager $tokenManager, ActiveProfileManager $activeProfileManager): void
    {
        Http::macro('remote', function () use ($tokenManager, $activeProfileManager) {
            $tokenManager->ensureFreshToken();

            $client = Http::baseUrl(config('services.remote_api.base_url'))
                ->acceptJson()
                ->asJson();

            $token = $tokenManager->getToken();
            // dd($token);
            if ($token !== null) {
                $client = $client->withToken($token);
            }

            $activeProfileId = $activeProfileManager->getActiveProfileId();

            if ($activeProfileId !== null) {
                $client = $client->withHeader('X-Profile-Id', (string) $activeProfileId);
            }

            return $client;
        });
    }
}
