<?php

declare(strict_types=1);

namespace App\Livewire\Trades;

use App\Services\ActiveProfileManager;
use App\Services\Trades\TradesDataService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

class Dashboard extends Component
{
    public array $profiles = [];

    public ?int $selectedProfileId = null;

    public array $positions = [];

    public array $portfolioSnapshot = [];

    /** @var array<int, array<string, mixed>> */
    public array $portfolioHistory = [];

    public ?string $loadError = null;

    protected TradesDataService $trades;

    protected ActiveProfileManager $activeProfileManager;

    public function boot(TradesDataService $trades, ActiveProfileManager $activeProfileManager): void
    {
        $this->trades = $trades;
        $this->activeProfileManager = $activeProfileManager;
    }

    public function mount(): void
    {
        $this->profiles = $this->trades->fetchProfiles();

        $initialProfileId = $this->resolveInitialProfileId();

        if ($initialProfileId !== null) {
            $this->selectedProfileId = $initialProfileId;
            $this->loadDashboardData();
        }
    }

    public function render(): View
    {
        return view('livewire.trades.dashboard');
    }

    public function refreshDashboard(): void
    {
        $this->loadDashboardData();
    }

    public function updatedSelectedProfileId($value): void
    {
        $profileId = is_numeric($value) ? (int) $value : null;

        if ($profileId === null || $profileId <= 0) {
            $this->resetDashboardData();

            return;
        }

        if (! $this->profileExists($profileId)) {
            $this->resetDashboardData();

            return;
        }

        $this->selectedProfileId = $profileId;
        $this->activeProfileManager->setActiveProfileId($profileId);
        $this->loadDashboardData();
    }

    private function resolveInitialProfileId(): ?int
    {
        $activeProfileId = $this->activeProfileManager->getActiveProfileId();

        if ($activeProfileId !== null && $this->profileExists($activeProfileId)) {
            return $activeProfileId;
        }

        foreach ($this->profiles as $profile) {
            $profileId = (int) ($profile['id'] ?? 0);

            if ($profileId > 0) {
                return $profileId;
            }
        }

        return null;
    }

    private function profileExists(int $profileId): bool
    {
        foreach ($this->profiles as $profile) {
            if ((int) ($profile['id'] ?? 0) === $profileId) {
                return true;
            }
        }

        return false;
    }

    private function loadDashboardData(): void
    {
        if ($this->selectedProfileId === null || $this->selectedProfileId <= 0) {
            $this->resetDashboardData();

            return;
        }

        try {
            $profileId = $this->selectedProfileId;

            $this->positions = $this->trades->fetchPositions($profileId);
            $this->portfolioSnapshot = $this->trades->fetchPortfolioSnapshot($profileId);
            $this->portfolioHistory = $this->trades->fetchPortfolioHistory($profileId);

            $this->loadError = null;

            $this->dispatch(
                'trades-dashboard:loaded',
                positions: $this->positions,
                snapshot: $this->portfolioSnapshot,
                history: $this->portfolioHistory
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->loadError = 'Unable to load dashboard data. Please try again.';
            $this->resetDashboardData();

            $this->dispatch('trades-dashboard:failed');
        }
    }

    private function resetDashboardData(): void
    {
        $this->positions = [];
        $this->portfolioSnapshot = [];
        $this->portfolioHistory = [];
    }
}
