<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\ActiveProfileManager;
use App\Services\RemoteApi\TokenManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Navbar extends Component
{
    public array $profiles = [];

    public int|string|null $selectedProfileId = null;

    private TokenManager $tokenManager;

    private ActiveProfileManager $activeProfileManager;

    public function boot(TokenManager $tokenManager, ActiveProfileManager $activeProfileManager): void
    {
        $this->tokenManager = $tokenManager;
        $this->activeProfileManager = $activeProfileManager;
    }

    public function mount(): void
    {
        $this->reloadProfiles();
    }

    #[Computed]
    public function isLoggedIn(): bool
    {
        return $this->tokenManager->hasToken();
    }

    #[Computed]
    public function getUser(): ?array
    {
        return $this->tokenManager->authenticatedUser();
    }

    #[On('update-profiles')]
    public function reloadProfiles(): void
    {
        $this->profiles = $this->fetchProfiles();

        $activeProfileId = $this->activeProfileManager->getActiveProfileId();

        if ($activeProfileId !== null && $this->profileExists($activeProfileId)) {
            $this->selectedProfileId = (string) $activeProfileId;

            return;
        }

        $firstProfileId = $this->profiles[0]['id'] ?? null;

        if (is_int($firstProfileId) && $firstProfileId > 0) {
            $this->selectedProfileId = (string) $firstProfileId;
            $this->activeProfileManager->setActiveProfileId($firstProfileId);
            $this->dispatch('active-profile-updated', profileId: $firstProfileId);

            return;
        }

        $this->selectedProfileId = null;
        $this->activeProfileManager->clearActiveProfile();
        $this->dispatch('active-profile-updated', profileId: null);
    }

    public function updatedSelectedProfileId(int|string|null $value): void
    {
        $profileId = is_numeric($value) ? (int) $value : null;

        if ($profileId === null || !$this->profileExists($profileId)) {
            $this->selectedProfileId = $this->activeProfileManager->getActiveProfileId();

            return;
        }

        $this->selectedProfileId = (string) $profileId;
        $this->activeProfileManager->setActiveProfileId($profileId);
        $this->dispatch('active-profile-updated', profileId: $profileId);
    }

    public function render(): View
    {
        return view('livewire.navbar');
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    private function fetchProfiles(): array
    {
        if (!$this->isLoggedIn()) {
            return [];
        }

        try {
            $response = Http::remote()->get('/api/v2/profiles');
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }

        if ($response->failed()) {
            report($response->toException());

            return [];
        }

        $profiles = array_map(
            static function (array $profile): array {
                $id = (int) ($profile['id'] ?? 0);
                $label = trim((string) ($profile['label'] ?? ''));

                if ($label === '') {
                    $label = 'Profile #' . $id;
                }

                return [
                    'id' => $id,
                    'label' => $label,
                ];
            },
            $response->json('data', [])
        );

        return array_values(
            array_filter(
                $profiles,
                static fn (array $profile): bool => $profile['id'] > 0
            )
        );
    }

    private function profileExists(int $profileId): bool
    {
        foreach ($this->profiles as $profile) {
            if (($profile['id'] ?? null) === $profileId) {
                return true;
            }
        }

        return false;
    }
}
