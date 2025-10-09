<?php

declare(strict_types=1);

namespace App\Livewire\Customizer;

use App\Services\ActiveProfileManager;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Currencies extends Component
{
    public ?int $activeProfileId = null;

    public function boot(ActiveProfileManager $activeProfileManager): void
    {
        $this->activeProfileId = $activeProfileManager->getActiveProfileId();
    }

    #[On('active-profile-updated')]
    public function handleActiveProfileUpdated(?int $profileId = null): void
    {
        $this->activeProfileId = $profileId;
        $this->resetErrorBag();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCurrencies(): array
    {
        if ($this->activeProfileId === null) {
            return [];
        }

        try {
            $response = Http::remote()->get('/api/v2/currencies');

            return $response->json('data') ?? [];
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('api', 'Network error. Please try again.');

            return [];
        }
    }

    public function render()
    {
        $currencies = $this->getCurrencies();

        return view('livewire.customizer.currencies', [
            'currencies' => $currencies,
        ]);
    }
}
