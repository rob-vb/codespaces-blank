<?php

declare(strict_types=1);

namespace App\Services\RemoteApi;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Throwable;

class TokenManager
{
    private const REFRESH_GRACE_SECONDS = 60;

    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    public function ensureFreshToken(): void
    {
        if (!$this->hasToken()) {
            return;
        }

        $accessExpiresAt = $this->expiresAt();

        if ($accessExpiresAt === null) {
            return;
        }

        $now = CarbonImmutable::now();

        if ($accessExpiresAt->isAfter($now->addSeconds(self::REFRESH_GRACE_SECONDS))) {
            return;
        }

        $refreshToken = $this->getRefreshToken();

        if ($refreshToken === null) {
            Log::info('Remote API access token is expiring but no refresh token is available.');

            return;
        }

        $refreshExpiresAt = $this->refreshTokenExpiresAt();

        if ($refreshExpiresAt !== null && $refreshExpiresAt->isBefore($now)) {
            $this->clearToken();

            return;
        }

        $this->refreshToken();
    }

    public function refreshToken(): ?string
    {
        $token = $this->getToken();

        if ($token === null) {
            return null;
        }

        $refreshToken = $this->getRefreshToken();

        if ($refreshToken === null) {
            Log::warning('Remote API token refresh skipped because no refresh token is stored.');

            return null;
        }

        $refreshExpiresAt = $this->refreshTokenExpiresAt();

        if ($refreshExpiresAt !== null && $refreshExpiresAt->isPast()) {
            $this->clearToken();

            return null;
        }

        try {
            $payload = [
                'refresh_token' => $refreshToken,
            ];

            $response = $this->http
                ->baseUrl(config('services.remote_api.base_url'))
                ->acceptJson()
                ->withToken($token)
                ->send('POST', '/api/v2/refresh/', [
                    'json' => $payload,
                ]);
        } catch (Throwable $exception) {
            Log::warning('Remote API token refresh failed due to transport error.', [
                'exception' => $exception,
            ]);

            return null;
        }

        if ($response->failed()) {
            $this->handleFailedRefresh($response);

            return null;
        }

        $newToken = $response->json('token');

        if (!is_string($newToken) || $newToken === '') {
            Log::warning('Remote API token refresh succeeded but did not return a token.');

            return null;
        }

        $expiresIn = (int) ($response->json('expires_in') ?? 0);

        $newRefreshToken = $response->json('refresh_token');
        $refreshTokenExpiresIn = $response->json('refresh_expires_in') ?? $response->json('refresh_token_expires_in');
        $refreshTokenExpiresIn = is_numeric($refreshTokenExpiresIn) ? (int) $refreshTokenExpiresIn : null;

        $this->storeToken(
            $newToken,
            $expiresIn,
            is_string($newRefreshToken) && $newRefreshToken !== '' ? $newRefreshToken : null,
            $refreshTokenExpiresIn
        );

        return $newToken;
    }

    public function storeToken(
        string $token,
        int $expiresIn = 0,
        ?string $refreshToken = null,
        ?int $refreshExpiresIn = null,
        ?array $user = null,
    ): void {
        Session::put('api.jwt', $token);

        if ($expiresIn > 0) {
            Session::put('api.jwt_expires_at', CarbonImmutable::now()->addSeconds($expiresIn));
        } else {
            Session::forget('api.jwt_expires_at');
        }

        if ($refreshToken !== null) {
            if ($refreshToken === '') {
                Session::forget(['api.refresh_token', 'api.refresh_token_expires_at']);
            } else {
                Session::put('api.refresh_token', $refreshToken);
                $this->storeRefreshExpiry($refreshExpiresIn);
            }
        } elseif ($refreshExpiresIn !== null) {
            $this->storeRefreshExpiry($refreshExpiresIn);
        }

        $this->storeUser($user);
    }

    public function clearToken(): void
    {
        Session::forget([
            'api.jwt',
            'api.jwt_expires_at',
            'api.refresh_token',
            'api.refresh_token_expires_at',
            'api.user',
        ]);
    }

    public function getToken(): ?string
    {
        $token = Session::get('api.jwt');

        return is_string($token) && $token !== '' ? $token : null;
    }

    public function hasToken(): bool
    {
        return $this->getToken() !== null;
    }

    public function getRefreshToken(): ?string
    {
        $token = Session::get('api.refresh_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    public function hasRefreshToken(): bool
    {
        return $this->getRefreshToken() !== null;
    }

    public function authenticatedUser(): ?array
    {
        $user = Session::get('api.user');

        if (!is_array($user) || !isset($user['id'], $user['name'])) {
            return null;
        }

        return [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
        ];
    }

    private function expiresAt(): ?CarbonImmutable
    {
        return $this->resolveCarbon(Session::get('api.jwt_expires_at'));
    }

    private function refreshTokenExpiresAt(): ?CarbonImmutable
    {
        return $this->resolveCarbon(Session::get('api.refresh_token_expires_at'));
    }

    private function resolveCarbon(mixed $value): ?CarbonImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof CarbonImmutable) {
            return $value;
        }

        if ($value instanceof CarbonInterface) {
            return CarbonImmutable::make($value);
        }

        if (is_string($value)) {
            return CarbonImmutable::parse($value);
        }

        return null;
    }

    private function storeRefreshExpiry(?int $refreshExpiresIn): void
    {
        if ($refreshExpiresIn === null) {
            return;
        }

        if ($refreshExpiresIn > 0) {
            Session::put('api.refresh_token_expires_at', CarbonImmutable::now()->addSeconds($refreshExpiresIn));
        } else {
            Session::forget('api.refresh_token_expires_at');
        }
    }

    private function handleFailedRefresh(Response $response): void
    {
        Log::warning('Remote API token refresh failed.', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        if (in_array($response->status(), [400, 401, 422], true)) {
            $this->clearToken();
        }
    }

    private function storeUser(?array $user): void
    {
        if (!is_array($user) || !isset($user['id'], $user['name'])) {
            return;
        }

        Session::put('api.user', [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
        ]);
    }
}
