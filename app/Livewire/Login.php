<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\RemoteApi\TokenManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Throwable;

class Login extends Component
{
    public string $email = '';
    public string $password = '';

    protected array $rules = [
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ];

    private TokenManager $tokenManager;

    public function boot(TokenManager $tokenManager): void
    {
        $this->tokenManager = $tokenManager;
    }

    public function signIn(): Redirector|RedirectResponse|null
    {
        $this->validate();

        try {
            $response = Http::remote()->post('/api/v2/login/', [
                'email' => $this->email,
                'password' => $this->password,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('api', 'Network error. Please try again.');

            return null;
        }

        if ($response->failed()) {
            $message = $response->json('message') ?? 'Invalid credentials.';
            $this->addError('api', $message);

            return null;
        }

        $token = $response->json('token');

        if (!is_string($token) || $token === '') {
            $this->addError('api', 'Login succeeded but no token was returned.');

            return null;
        }

        $expiresIn = (int) ($response->json('expires_in') ?? 0);
        $refreshToken = $response->json('refresh_token');
        $refreshExpiresIn = $response->json('refresh_expires_in') ?? $response->json('refresh_token_expires_in');
        $refreshExpiresIn = is_numeric($refreshExpiresIn) ? (int) $refreshExpiresIn : null;

        $user = $response->json('user');

        $this->tokenManager->storeToken(
            token: $token,
            expiresIn: $expiresIn,
            refreshToken: is_string($refreshToken) && $refreshToken !== '' ? $refreshToken : null,
            refreshExpiresIn: $refreshExpiresIn,
            user: is_array($user) ? $user : null,
        );

        return redirect()->route('customizer');
    }

    public function render()
    {
        return view('livewire.login');
    }
}
