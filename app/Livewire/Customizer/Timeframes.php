<?php

declare(strict_types=1);

namespace App\Livewire\Customizer;

use App\Services\ActiveProfileManager;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Timeframes extends Component
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

    public function saveTimeframes(): void
    {
        //
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getTimeframes(): array
    {
        if ($this->activeProfileId === null) {
            return [];
        }

        try {
            $response = Http::remote()->get('/api/v2/timeframes');

            return $response->json('data') ?? [];
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('api', 'Network error. Please try again.');

            return [];
        }
    }

    public function render()
    {
        $timeframes = $this->getTimeframes();

        return view('livewire.customizer.timeframes', [
            'timeframes' => $timeframes,
        ]);
    }
}
