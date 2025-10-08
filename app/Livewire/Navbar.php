<?php

namespace App\Livewire;

use App\Services\RemoteApi\TokenManager;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Navbar extends Component
{
    private TokenManager $tokenManager;

    public function boot(TokenManager $tokenManager): void
    {
        $this->tokenManager = $tokenManager;
    }

    #[Computed]
    public function isLoggedIn(): bool
    {
        return $this->tokenManager->hasToken();
    }

    #[Computed]
    public function getUser(): array|null
    {
        return $this->tokenManager->authenticatedUser();
    }

    public function render()
    {
        return view('livewire.navbar');
    }
}
