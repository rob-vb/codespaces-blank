<?php

declare(strict_types=1);

namespace App\Livewire\Customizer;

use App\Livewire\Notifications\Toast as ToastNotifier;
use App\Services\ActiveProfileManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Timeframes extends Component
{
    public ?int $activeProfileId = null;
    public array $timeframes = [];

    public function boot(ActiveProfileManager $activeProfileManager): void
    {
        $this->activeProfileId = $activeProfileManager->getActiveProfileId();
    }

    public function mount(): void
    {
        $this->loadTimeframes();
    }

    #[On('active-profile-updated')]
    public function handleActiveProfileUpdated(?int $profileId = null): void
    {
        $this->activeProfileId = $profileId;
        $this->resetErrorBag();
        $this->loadTimeframes();
    }

    public function saveTimeframes(): void
    {
        if ($this->activeProfileId === null) {
            $message = 'Select a profile before saving.';
            $this->addError('api', $message);
            $this->dispatchToast($message, 'error');

            return;
        }

        $this->sanitizeTimeframes();

        $validated = $this->validate();
        $payload = [
            'timeframes' => $this->mapTimeframesToPayload($validated['timeframes'] ?? []),
        ];

        try {
            $response = Http::remote()->post(
                '/api/v2/profile/timeframes/' . $this->activeProfileId,
                $payload
            );
        } catch (Throwable $exception) {
            report($exception);
            $message = 'Unable to save timeframes. Please try again.';
            $this->addError('api', $message);
            $this->dispatchToast($message, 'error');

            return;
        }

        if ($response->failed()) {
            if ($response->status() === 422) {
                $errors = (array) $response->json('errors');
                $message = (string) collect($errors)->flatten()->filter()->first();

                if ($message === '') {
                    $message = (string) ($response->json('message') ?? $response->json('error') ?? '');
                }

                if ($message === '') {
                    $message = 'Unable to save timeframes. Please review your selection.';
                }

                $this->addError('api', $message);
                $this->dispatchToast($message, 'error');

                return;
            }

            report($response->toException());
            $message = (string) ($response->json('error') ?? $response->json('message') ?? 'Unable to save timeframes. Please try again.');
            $this->addError('api', $message);
            $this->dispatchToast($message, 'error');

            return;
        }

        $this->resetErrorBag('api');
        $this->dispatchToast('Timeframes saved.', 'success');
        $this->loadTimeframes();
    }

    public function resetToDefault(): void
    {
        if ($this->activeProfileId === null) {
            return;
        }

        $defaults = $this->normalizeTimeframes($this->getTimeframes());

        $this->timeframes = array_map(
            fn (array $timeframe): array => $this->applySelectableDefault($timeframe),
            $defaults
        );

        $this->resetErrorBag('api');
    }

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

    private function getProfileTimeframes(): array
    {
        if ($this->activeProfileId === null) {
            return [];
        }

        try {
            $response = Http::remote()->get('/api/v2/profile/timeframes/' . $this->activeProfileId);

            return $response->json('data') ?? [];
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('api', 'Network error. Please try again.');

            return [];
        }
    }

    private function resolveTimeframeId(array $timeframe): int
    {
        return (int) ($timeframe['timeframe_id'] ?? $timeframe['id'] ?? 0);
    }

    /**
     * @param array<int, array<string, mixed>> $timeframes
     * @return array<int, array<string, mixed>>
     */
    private function indexTimeframesById(array $timeframes): array
    {
        $indexed = [];

        foreach ($timeframes as $timeframe) {
            if (!is_array($timeframe)) {
                continue;
            }

            $timeframeId = $this->resolveTimeframeId($timeframe);

            if ($timeframeId <= 0) {
                continue;
            }

            $indexed[$timeframeId] = $timeframe;
        }

        return $indexed;
    }

    private function applySelectableDefault(array $timeframe): array
    {
        $timeframe['enabled'] = (int) ($timeframe['is_selectable'] ?? 0) === 1;

        return $timeframe;
    }

    private function resolveEnabledState(array $timeframe): bool
    {
        foreach (['enabled', 'is_enabled', 'default_enabled'] as $key) {
            if (array_key_exists($key, $timeframe)) {
                return $this->toBoolean($timeframe[$key]);
            }
        }

        if (array_key_exists('is_selectable', $timeframe)) {
            return $this->toBoolean($timeframe['is_selectable']);
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeTimeframe(array $timeframe): array
    {
        $normalized = $timeframe;
        $timeframeId = $this->resolveTimeframeId($normalized);

        $normalized['id'] = $timeframeId;
        $normalized['timeframe_id'] = $timeframeId;
        $normalized['code'] = (string) ($normalized['code'] ?? $normalized['timeframe'] ?? '');

        $label = $normalized['label'] ?? $normalized['name'] ?? $normalized['code'];
        $normalized['label'] = is_string($label) && $label !== '' ? $label : $normalized['code'];

        $normalized['is_selectable'] = (int) ($normalized['is_selectable'] ?? 1);
        $normalized['enabled'] = $this->resolveEnabledState($normalized);


        return $normalized;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeTimeframes(array $timeframes): array
    {
        $normalized = [];

        foreach ($timeframes as $timeframe) {
            if (!is_array($timeframe)) {
                continue;
            }

            $normalized[] = $this->normalizeTimeframe($timeframe);
        }

        return array_values($normalized);
    }

    private function sanitizeTimeframes(): void
    {
        foreach ($this->timeframes as $index => &$timeframe) {
            if (!is_array($timeframe)) {
                unset($this->timeframes[$index]);
                continue;
            }

            $timeframeId = $this->resolveTimeframeId($timeframe);

            if ($timeframeId <= 0) {
                unset($this->timeframes[$index]);
                continue;
            }

            $timeframe['timeframe_id'] = $timeframeId;
            $timeframe['enabled'] = $this->resolveEnabledState($timeframe);
            $timeframe['is_selectable'] = (int) ($timeframe['is_selectable'] ?? 1);
        }
        unset($timeframe);

        $this->timeframes = array_values($this->timeframes);
    }

    /**
     * @param array<int, array<string, mixed>> $timeframes
     * @return array<int, array{timeframe_id: int, enabled: int}>
     */
    private function mapTimeframesToPayload(array $timeframes): array
    {
        $payload = [];

        foreach ($timeframes as $timeframe) {
            if (!is_array($timeframe)) {
                continue;
            }

            if ((int) ($timeframe['is_selectable'] ?? 0) !== 1) {
                continue;
            }

            $timeframeId = $this->resolveTimeframeId($timeframe);

            if ($timeframeId <= 0) {
                continue;
            }

            $payload[$timeframeId] = [
                'timeframe_id' => $timeframeId,
                'enabled' => $this->resolveEnabledState($timeframe) ? 1 : 0,
            ];
        }

        return array_values($payload);
    }

    private function toBoolean(mixed $value): bool
    {
        return match (true) {
            is_bool($value) => $value,
            is_numeric($value) => (int) $value === 1,
            is_string($value) => in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true),
            default => false,
        };
    }

    private function loadTimeframes(): void
    {
        $defaultTimeframes = $this->normalizeTimeframes($this->getTimeframes());
        $profileTimeframes = $this->normalizeTimeframes($this->getProfileTimeframes());
        $profileById = $this->indexTimeframesById($profileTimeframes);

        if ($profileById === []) {
            $this->timeframes = array_map(
                fn (array $timeframe): array => $this->applySelectableDefault($timeframe),
                $defaultTimeframes
            );

            info('customizer.timeframes.loaded', [
                'active_profile_id' => $this->activeProfileId,
                'source' => 'defaults',
                'defaults' => $defaultTimeframes,
                'profile' => $profileTimeframes,
                'resolved' => $this->timeframes,
            ]);

            return;
        }

        $matchedIds = [];
        $resolvedTimeframes = [];

        foreach ($defaultTimeframes as $default) {
            $timeframeId = $this->resolveTimeframeId($default);

            if ($timeframeId > 0 && array_key_exists($timeframeId, $profileById)) {
                $profile = $profileById[$timeframeId];
                $matchedIds[] = $timeframeId;

                $merged = array_replace($default, $profile);
                $merged['enabled'] = $this->resolveEnabledState($profile);
                $merged['is_selectable'] = (int) ($default['is_selectable'] ?? 1);

                $resolvedTimeframes[] = $merged;

                continue;
            }

            $default['enabled'] = false;
            $default['is_selectable'] = (int) ($default['is_selectable'] ?? 1);
            $resolvedTimeframes[] = $default;
        }

        foreach ($profileTimeframes as $profile) {
            $timeframeId = $this->resolveTimeframeId($profile);

            if ($timeframeId > 0 && in_array($timeframeId, $matchedIds, true)) {
                continue;
            }

            $profile['enabled'] = $this->resolveEnabledState($profile);
            $profile['is_selectable'] = (int) ($profile['is_selectable'] ?? 1);
            $resolvedTimeframes[] = $profile;
        }

        $this->timeframes = array_values(
            array_map(
                fn (array $timeframe): array => $this->normalizeTimeframe($timeframe),
                $resolvedTimeframes
            )
        );

        info('customizer.timeframes.loaded', [
            'active_profile_id' => $this->activeProfileId,
            'source' => 'profile',
            'defaults' => $defaultTimeframes,
            'profile' => $profileTimeframes,
            'resolved' => $this->timeframes,
        ]);
    }

    private function dispatchToast(string $message, string $variant = 'info'): void
    {
        $this->dispatch('toast', message: $message, variant: $variant)
            ->to(ToastNotifier::class);
    }

    protected function rules(): array
    {
        return [
            'timeframes' => ['array'],
            'timeframes.*.timeframe_id' => ['required', 'integer', 'min:1'],
            'timeframes.*.enabled' => ['boolean'],
            'timeframes.*.is_selectable' => ['integer'],
        ];
    }

    public function render(): View
    {
        return view('livewire.customizer.timeframes', [
            'timeframes' => $this->timeframes,
            'activeProfileId' => $this->activeProfileId,
        ]);
    }
}
