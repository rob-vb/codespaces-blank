<?php

declare(strict_types=1);

namespace App\Livewire\Customizer;

use App\Livewire\Notifications\Toast as ToastNotifier;
use App\Services\ActiveProfileManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Portfolio extends Component
{
    public ?int $activeProfileId = null;
    public array $buckets = [];
    public array $timeframes = [];
    public array $sizingPercentages = [];
    public bool $hasUnsavedChanges = false;
    #[Locked]
    public array $originalSizingPercentages = [];

    public function boot(ActiveProfileManager $activeProfileManager): void
    {
        $this->activeProfileId = $activeProfileManager->getActiveProfileId();
    }

    public function mount(): void
    {
        $this->loadPortfolioData();
    }

    #[On('active-profile-updated')]
    public function handleActiveProfileUpdated(?int $profileId = null): void
    {
        $this->activeProfileId = $profileId;
        $this->resetErrorBag();
        $this->loadPortfolioData();
    }

    public function updatedSizingPercentages(): void
    {
        $this->resetErrorBag('api');
        $this->hasUnsavedChanges = $this->hasSizingChanges();
    }

    public function save(): void
    {
        if ($this->activeProfileId === null) {
            $message = 'Select a profile before saving.';
            $this->addError('api', $message);
            $this->dispatchToast($message, 'error');

            return;
        }

        $this->sanitizeSizingPercentages();

        $validated = $this->validate();
        $changes = $this->mapPercentagesToPayload($validated['sizingPercentages']);

        if ($changes === []) {
            $this->resetErrorBag('api');
            $this->dispatchToast('No changes to save.', 'info');

            return;
        }

        $payload = [
            'sizing_data' => $changes,
        ];

        try {
            $response = Http::remote()->post(
                '/api/v2/profile/sizing/' . $this->activeProfileId,
                $payload
            );

        } catch (Throwable $exception) {
            report($exception);
            $message = 'Unable to save portfolio sizing. Please try again.';
            $this->addError('api', $message);
            $this->dispatchToast($message, 'error');

            return;
        }

        if ($response->failed()) {
            if ($response->status() === 422) {
                $errors = $response->json('errors') ?? [];
                $message = (string) collect($errors)->flatten()->filter()->first() ?: 'Unable to save portfolio sizing. Please review your entries.';
                $this->addError('api', $message);
                $this->dispatchToast($message, 'error');

                return;
            }

            $message = (string) ($response->json('error') ?? 'Unable to save portfolio sizing. Please try again.');
            $this->addError('api', $message);
            $this->dispatchToast($message, 'error');

            return;
        }

        $this->resetErrorBag('api');
        $this->loadSizingData();
        $this->dispatchToast('Portfolio management system saved.', 'success');
    }

    public function resetToDefault(): void
    {
        if ($this->activeProfileId === null) {
            return;
        }

        $defaultSizing = $this->prepareSizingData(
            $this->fetchCollection('/api/v2/default-sizing/'),
            []
        );

        $this->sizingPercentages = $this->buildPercentageMatrix($defaultSizing);
        $this->resetErrorBag();
        $this->hasUnsavedChanges = $this->hasSizingChanges();
    }

    public function render(): View
    {
        return view('livewire.customizer.portfolio', [
            'buckets' => $this->buckets,
            'timeframes' => $this->timeframes,
        ]);
    }

    protected function rules(): array
    {
        return [
            'sizingPercentages' => ['array'],
            'sizingPercentages.*' => ['array'],
            'sizingPercentages.*.*' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }

    private function loadPortfolioData(): void
    {
        if ($this->activeProfileId === null) {
            $this->buckets = [];
            $this->timeframes = [];
            $this->sizingPercentages = [];
            $this->originalSizingPercentages = [];
            $this->hasUnsavedChanges = false;

            return;
        }

        $this->buckets = $this->getBuckets();
        $this->timeframes = $this->getTimeframes();
        $this->loadSizingData();
    }

    private function loadSizingData(): void
    {
        if ($this->activeProfileId === null) {
            $this->sizingPercentages = [];

            return;
        }

        $sizingDecimals = $this->getSizingData();
        $matrix = $this->buildPercentageMatrix($sizingDecimals);

        $this->sizingPercentages = $matrix;
        $this->originalSizingPercentages = $matrix;
        $this->hasUnsavedChanges = false;
    }

    private function dispatchToast(string $message, string $variant = 'info'): void
    {
        $this->dispatch('toast', message: $message, variant: $variant)
            ->to(ToastNotifier::class);
    }

    private function getBuckets(): array
    {
        return $this->fetchCollection('/api/v2/buckets/');
    }

    private function getTimeframes(): array
    {
        return $this->fetchCollection('/api/v2/timeframes/');
    }

    private function fetchCollection(string $endpoint): array
    {
        if ($this->activeProfileId === null) {
            return [];
        }

        try {
            $response = Http::remote()->get($endpoint);

            if ($response->failed()) {
                report($response->toException());
                $message = 'Unable to load data. Please try again.';
                $this->addError('api', $message);
                $this->dispatchToast($message, 'error');

                return [];
            }

            return $response->json('data', []);
        } catch (Throwable $exception) {
            report($exception);
            $message = 'Network error. Please try again.';
            $this->addError('api', $message);
            $this->dispatchToast($message, 'error');

            return [];
        }
    }

    private function getSizingData(): array
    {
        if ($this->activeProfileId === null) {
            return [];
        }

        $defaultSizing = $this->fetchCollection('/api/v2/default-sizing/');
        $userSizing = $this->fetchCollection('/api/v2/profile/sizing/' . $this->activeProfileId);

        return $this->prepareSizingData($defaultSizing, $userSizing);
    }

    private function prepareSizingData(array $defaultSizing, array $userSizing): array
    {
        $sizing = [];

        $hydrateSizing = function (array $rows) use (&$sizing): void {
            foreach ($rows as $row) {
                $bucketId = (int) data_get($row, 'bucket_id');
                $timeframeId = (int) data_get($row, 'timeframe_id');

                if ($bucketId <= 0 || $timeframeId <= 0) {
                    continue;
                }

                $percentOfEquity = (float) data_get($row, 'percent_of_equity', 0);
                $sizing[$bucketId][$timeframeId] = $percentOfEquity;
            }
        };

        $hydrateSizing($defaultSizing);
        $hydrateSizing($userSizing);

        return $sizing;
    }

    /**
     * @param array<int, array<int, float|int|string|null>> $percentages
     * @return array<int, array{bucket_id: int, timeframe_id: int, percent_of_equity: float}>
     */
    private function mapPercentagesToPayload(array $percentages): array
    {
        $payload = [];

        foreach ($percentages as $bucketId => $timeframes) {
            $bucketIdInt = (int) $bucketId;

            if ($bucketIdInt <= 0) {
                continue;
            }

            foreach ($timeframes as $timeframeId => $percent) {
                $timeframeIdInt = (int) $timeframeId;

                if ($timeframeIdInt <= 0 || $percent === null || $percent === '') {
                    continue;
                }

                $numericPercent = round((float) $percent, 4);
                $originalPercent = round(
                    (float) ($this->originalSizingPercentages[$bucketIdInt][$timeframeIdInt] ?? 0.0),
                    4
                );

                if (!$this->hasPercentChanged($numericPercent, $originalPercent)) {
                    continue;
                }

                $payload[] = [
                    'bucket_id' => $bucketIdInt,
                    'timeframe_id' => $timeframeIdInt,
                    'percent_of_equity' => round($numericPercent / 100, 6),
                ];
            }
        }

        return $payload;
    }

    /**
     * @param array<int, array<int, float>> $sizingData
     */
    private function buildPercentageMatrix(array $sizingData): array
    {
        $percentages = [];

        foreach ($this->buckets as $bucket) {
            $bucketId = (int) ($bucket['id'] ?? 0);

            if ($bucketId <= 0) {
                continue;
            }

            foreach ($this->timeframes as $timeframe) {
                if ((int) ($timeframe['is_selectable'] ?? 0) !== 1) {
                    continue;
                }

                $timeframeId = (int) ($timeframe['id'] ?? 0);

                if ($timeframeId <= 0) {
                    continue;
                }

                $decimal = (float) ($sizingData[$bucketId][$timeframeId] ?? 0.0);
                $percentages[$bucketId][$timeframeId] = round($decimal * 100, 4);
            }
        }

        return $percentages;
    }

    private function sanitizeSizingPercentages(): void
    {
        foreach ($this->sizingPercentages as $bucketId => &$timeframes) {
            if (!is_array($timeframes)) {
                unset($this->sizingPercentages[$bucketId]);
                continue;
            }

            foreach ($timeframes as $timeframeId => &$percent) {
                if ($percent === '') {
                    $percent = null;
                    continue;
                }

                if ($percent !== null && !is_numeric($percent)) {
                    $percent = null;
                }
            }
            unset($percent);
        }
        unset($timeframes);
    }

    private function hasPercentChanged(float $current, float $original): bool
    {
        return abs($current - $original) >= 0.0001;
    }

    private function hasSizingChanges(): bool
    {
        $bucketIds = array_unique(array_merge(
            array_keys($this->originalSizingPercentages),
            array_keys($this->sizingPercentages)
        ));

        foreach ($bucketIds as $bucketId) {
            $bucketIdInt = (int) $bucketId;
            $originalTimeframes = (array) ($this->originalSizingPercentages[$bucketIdInt] ?? []);
            $currentTimeframes = (array) ($this->sizingPercentages[$bucketId] ?? []);

            $timeframeIds = array_unique(array_merge(
                array_keys($originalTimeframes),
                array_keys($currentTimeframes)
            ));

            foreach ($timeframeIds as $timeframeId) {
                $timeframeIdInt = (int) $timeframeId;
                $original = round((float) ($originalTimeframes[$timeframeIdInt] ?? 0.0), 4);
                $rawCurrent = $currentTimeframes[$timeframeId] ?? 0.0;
                $current = round(is_numeric($rawCurrent) ? (float) $rawCurrent : 0.0, 4);

                if ($this->hasPercentChanged($current, $original)) {
                    return true;
                }
            }
        }

        return false;
    }
}
