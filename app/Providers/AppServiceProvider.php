<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;

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
    public function boot(): void
    {
        Http::macro('remote', function () {
            $client = Http::baseUrl(config('services.remote_api.base_url'))
                ->acceptJson()
                ->asJson();

            if (session()->has('api.jwt')) {
                $client = $client->withToken(session('api.jwt'));
            }

            return $client;
        });
    }
}
