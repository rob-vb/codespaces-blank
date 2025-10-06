<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class Login extends Component
{
    public string $email = '';
    public string $password = '';

    protected array $rules = [
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ];


    public function signIn()
    {
        $this->validate();

         try {
            // POST to your API /login endpoint
            $response = Http::remote()->post('/api/v2/login/', [
                'email' => $this->email,
                'password' => $this->password,
            ]);
        } catch (\Throwable $e) {
            $this->addError('api', 'Network error. Please try again.');
            return;
        }

        if ($response->failed()) {
            // Try to surface API error message if available
            $message = $response->json('message') ?? 'Invalid credentials.';
            $this->addError('api', $message);
            return;
        }

        $token = $response->json('token');

        if (!$token) {
            $this->addError('api', 'Login succeeded but no token was returned.');
            return;
        }

        $expiresIn = (int) ($response->json('expires_in') ?? 0);

        session()->put('api.jwt', $token);
        if ($expiresIn > 0) {
            session()->put('api.jwt_expires_at', now()->addSeconds($expiresIn));
        }

        return redirect()->route('customizer');
    }
    
    public function render()
    {
        return view('livewire.login');
    }
}
