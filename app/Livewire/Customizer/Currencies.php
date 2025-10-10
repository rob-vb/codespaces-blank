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

class Currencies extends Component
{
    public ?int $activeProfileId = null;

    public array $instruments = [];

    public bool $includeAllInstruments = false;

    public bool $hasUnsavedChanges = false;

    public array $originalInstruments = [];

    public bool $originalIncludeAllInstruments = false;

    public ?int $exchangeId = null;

    public function boot(ActiveProfileManager $activeProfileManager): void
    {
        $this->activeProfileId = $activeProfileManager->getActiveProfileId();
    }

    public function mount(): void
    {
        $this->loadInstruments();
    }

    #[On('active-profile-updated')]
    public function handleActiveProfileUpdated(?int $profileId = null): void
    {
        $this->activeProfileId = $profileId;
        $this->resetErrorBag();
        $this->loadInstruments();
    }

    public function updatedInstruments(): void
    {
        $this->resetErrorBag('api');
        $this->evaluateUnsavedChanges();
    }

    public function updatedIncludeAllInstruments(): void
    {
        $this->resetErrorBag('api');

        if ($this->includeAllInstruments) {
            $this->enableAllInstruments();
        }

        $this->evaluateUnsavedChanges();
    }

    public function save(): void
    {
        if ($this->activeProfileId === null) {
            $message = 'Select a profile before saving.';
            $this->registerApiError($message);
            $this->dispatchToast($message, 'error');

            return;
        }

        if (!$this->hasUnsavedChanges) {
            $this->dispatchToast('No changes to save.', 'info');

            return;
        }

        $this->sanitizeInstruments();

        $validated = $this->validate();

        $payload = [
            'include_all_instruments' => $validated['includeAllInstruments'],
            'instruments' => $validated['includeAllInstruments']
                ? []
                : $this->mapInstrumentsToPayload($validated['instruments']),
        ];

        try {
            $response = Http::remote()->post(
                '/api/v2/profile/instruments/' . $this->activeProfileId,
                $payload
            );
        } catch (Throwable $exception) {
            report($exception);
            $message = 'Unable to save currency preferences. Please try again.';
            $this->registerApiError($message);
            $this->dispatchToast($message, 'error');

            return;
        }

        if ($response->failed()) {
            if ($response->status() === 422) {
                $errors = (array) $response->json('errors');
                $message = (string) collect($errors)->flatten()->filter()->first()
                    ?: (string) ($response->json('message') ?? 'Unable to save currency preferences. Please review your selection.');
                $this->registerApiError($message);
                $this->dispatchToast($message, 'error');

                return;
            }

            $message = (string) ($response->json('error') ?? $response->json('message') ?? 'Unable to save currency preferences. Please try again.');
            $this->registerApiError($message);
            $this->dispatchToast($message, 'error');

            return;
        }

        $this->resetErrorBag('api');
        $this->dispatchToast('Currency preferences saved.', 'success');
        $this->loadInstruments();
    }

    public function resetChanges(): void
    {
        $this->resetErrorBag('api');

        $this->instruments = array_values($this->originalInstruments);
        $this->includeAllInstruments = $this->originalIncludeAllInstruments;
        $this->hasUnsavedChanges = false;
    }

    public function render(): View
    {
        $totalInstruments = count($this->instruments);
        $totalEnabled = count(
            array_filter(
                $this->instruments,
                fn (array $instrument): bool => $this->toBoolean($instrument['enabled'] ?? false)
            )
        );

        return view('livewire.customizer.currencies', [
            'totalInstruments' => $totalInstruments,
            'totalEnabled' => $totalEnabled,
        ]);
    }

    protected function rules(): array
    {
        return [
            'includeAllInstruments' => ['boolean'],
            'instruments' => ['array'],
            'instruments.*.instrument_id' => ['required', 'integer', 'min:1'],
            'instruments.*.enabled' => ['boolean'],
        ];
    }

    private function loadInstruments(): void
    {
        if ($this->activeProfileId === null) {
            $this->clearState();

            return;
        }

        $this->resetErrorBag('api');

        $profile = $this->fetchProfileInstruments($this->activeProfileId);

        if ($profile === null) {
            $this->clearState();

            return;
        }

        $this->includeAllInstruments = $this->toBoolean($profile['include_all_instruments'] ?? false);
        $this->exchangeId = is_numeric($profile['exchange_id'] ?? null)
            ? (int) $profile['exchange_id']
            : null;

        $profileInstruments = $this->normalizeInstrumentList((array) ($profile['data'] ?? []));
        $exchangeInstruments = $this->exchangeId !== null
            ? $this->normalizeInstrumentList($this->fetchExchangeInstruments($this->exchangeId))
            : [];

        $resolved = $this->mergeInstruments($exchangeInstruments, $profileInstruments);

        $this->instruments = $resolved;
        $this->originalInstruments = $resolved;
        $this->originalIncludeAllInstruments = $this->includeAllInstruments;
        $this->hasUnsavedChanges = false;

        info('customizer.currencies.loaded', [
            'active_profile_id' => $this->activeProfileId,
            'exchange_id' => $this->exchangeId,
            'include_all' => $this->includeAllInstruments,
            'total_instruments' => count($this->instruments),
        ]);
    }

    private function clearState(): void
    {
        $this->instruments = [];
        $this->includeAllInstruments = false;
        $this->originalInstruments = [];
        $this->originalIncludeAllInstruments = false;
        $this->hasUnsavedChanges = false;
        $this->exchangeId = null;
    }

    private function fetchProfileInstruments(int $profileId): ?array
    {
        try {
            $response = Http::remote()->get('/api/v2/profile/instruments/' . $profileId);
        } catch (Throwable $exception) {
            report($exception);
            $this->registerApiError('Unable to load currencies. Please try again.');

            return null;
        }

        if ($response->failed()) {
            if ($response->status() === 404) {
                return [
                    'profile_id' => $profileId,
                    'exchange_id' => null,
                    'include_all_instruments' => false,
                    'data' => [],
                ];
            }

            $message = (string) ($response->json('error') ?? $response->json('message') ?? 'Unable to load currencies. Please try again.');
            $this->registerApiError($message);

            return null;
        }

        return $response->json() ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchExchangeInstruments(int $exchangeId): array
    {
        if ($exchangeId <= 0) {
            return [];
        }

        try {
            $response = Http::remote()->get('/api/v2/instruments', [
                'exchange_id' => $exchangeId,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $this->registerApiError('Unable to load currencies for the selected exchange.');

            return [];
        }

        if ($response->failed()) {
            if ($response->status() === 404) {
                return [];
            }

            $message = (string) ($response->json('error') ?? $response->json('message') ?? 'Unable to load currencies for the selected exchange.');
            $this->registerApiError($message);

            return [];
        }

        return $response->json('data', []);
    }

    /**
     * @param array<int, array<string, mixed>> $exchangeInstruments
     * @param array<int, array<string, mixed>> $profileInstruments
     * @return array<int, array<string, mixed>>
     */
    private function mergeInstruments(array $exchangeInstruments, array $profileInstruments): array
    {
        $resolved = $exchangeInstruments;

        foreach ($resolved as $instrumentId => &$instrument) {
            if (isset($profileInstruments[$instrumentId])) {
                $instrument = array_replace($instrument, $profileInstruments[$instrumentId]);
                $instrument['enabled'] = $profileInstruments[$instrumentId]['enabled'];
            } else {
                $instrument['enabled'] = $this->includeAllInstruments;
            }
        }
        unset($instrument);

        foreach ($profileInstruments as $instrumentId => $instrument) {
            if (!isset($resolved[$instrumentId])) {
                $instrument['enabled'] = $instrument['enabled'] ?? $this->includeAllInstruments;
                $resolved[$instrumentId] = $instrument;
            }
        }

        $resolved = array_values($resolved);

        usort(
            $resolved,
            static fn (array $a, array $b): int => strnatcasecmp(
                $a['display_symbol'] ?? $a['symbol_on_exchange'] ?? '',
                $b['display_symbol'] ?? $b['symbol_on_exchange'] ?? ''
            )
        );

        return $resolved;
    }

    /**
     * @param array<int, array<string, mixed>> $instruments
     * @return array<int, array<string, mixed>>
     */
    private function normalizeInstrumentList(array $instruments): array
    {
        $normalized = [];

        foreach ($instruments as $instrument) {
            if (!is_array($instrument)) {
                continue;
            }

            $normalizedInstrument = $this->normalizeInstrument($instrument);

            if ($normalizedInstrument === null) {
                continue;
            }

            $normalized[$normalizedInstrument['instrument_id']] = $normalizedInstrument;
        }

        return $normalized;
    }

    private function normalizeInstrument(array $instrument): ?array
    {
        $instrumentId = (int) ($instrument['instrument_id'] ?? $instrument['id'] ?? 0);

        if ($instrumentId <= 0) {
            return null;
        }

        $rawSymbol = (string) ($instrument['symbol_on_exchange'] ?? $instrument['symbol'] ?? '');

        if ($rawSymbol === '') {
            $baseSymbol = (string) (data_get($instrument, 'base_asset.symbol') ?? data_get($instrument, 'base_symbol', ''));
            $quoteSymbol = (string) (data_get($instrument, 'quote_asset.symbol') ?? data_get($instrument, 'quote_symbol', ''));

            if ($baseSymbol !== '' && $quoteSymbol !== '') {
                $rawSymbol = $baseSymbol . '/' . $quoteSymbol;
            }
        }

        $normalized = [
            'instrument_id' => $instrumentId,
            'id' => $instrumentId,
            'exchange_id' => (int) ($instrument['exchange_id'] ?? data_get($instrument, 'exchange.id', 0)),
            'symbol_on_exchange' => $rawSymbol,
            'display_symbol' => $this->formatDisplaySymbol($rawSymbol),
            'base_symbol' => $this->stringValue(data_get($instrument, 'base_asset.symbol') ?? data_get($instrument, 'base_symbol')),
            'base_name' => $this->stringValue(data_get($instrument, 'base_asset.name') ?? data_get($instrument, 'base_name')),
            'quote_symbol' => $this->stringValue(data_get($instrument, 'quote_asset.symbol') ?? data_get($instrument, 'quote_symbol')),
            'quote_name' => $this->stringValue(data_get($instrument, 'quote_asset.name') ?? data_get($instrument, 'quote_name')),
            'enabled' => $this->toBoolean($instrument['enabled'] ?? $instrument['is_enabled'] ?? $instrument['default_enabled'] ?? false),
        ];

        return $normalized;
    }

    private function formatDisplaySymbol(string $symbol): string
    {
        $trimmed = trim($symbol);

        if ($trimmed === '') {
            return '';
        }

        return str_replace('/', '-', strtoupper($trimmed));
    }

    private function stringValue(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $formatted = trim((string) $value);

        return $formatted === '' ? null : $formatted;
    }

    private function sanitizeInstruments(): void
    {
        foreach ($this->instruments as $index => &$instrument) {
            if (!is_array($instrument)) {
                unset($this->instruments[$index]);

                continue;
            }

            $instrumentId = (int) ($instrument['instrument_id'] ?? $instrument['id'] ?? 0);

            if ($instrumentId <= 0) {
                unset($this->instruments[$index]);

                continue;
            }

            $instrument['instrument_id'] = $instrumentId;
            $instrument['id'] = $instrumentId;
            $instrument['enabled'] = $this->toBoolean($instrument['enabled'] ?? false);
            $instrument['symbol_on_exchange'] = (string) ($instrument['symbol_on_exchange'] ?? '');
            $instrument['display_symbol'] = $this->formatDisplaySymbol($instrument['symbol_on_exchange']);
        }
        unset($instrument);

        $this->instruments = array_values($this->instruments);
    }

    /**
     * @param array<int, array<string, mixed>> $instruments
     * @return array<int, array{instrument_id: int, enabled: int}>
     */
    private function mapInstrumentsToPayload(array $instruments): array
    {
        $payload = [];

        foreach ($instruments as $instrument) {
            if (!is_array($instrument)) {
                continue;
            }

            $instrumentId = (int) ($instrument['instrument_id'] ?? $instrument['id'] ?? 0);

            if ($instrumentId <= 0) {
                continue;
            }

            $enabled = $this->toBoolean($instrument['enabled'] ?? false);
            $payload[] = [
                'instrument_id' => $instrumentId,
                'enabled' => $enabled ? 1 : 0,
            ];
        }

        return $payload;
    }

    private function enableAllInstruments(): void
    {
        foreach ($this->instruments as &$instrument) {
            if (!is_array($instrument)) {
                continue;
            }

            $instrument['enabled'] = true;
        }
        unset($instrument);
    }

    private function evaluateUnsavedChanges(): void
    {
        if ($this->includeAllInstruments !== $this->originalIncludeAllInstruments) {
            $this->hasUnsavedChanges = true;

            return;
        }

        if ($this->includeAllInstruments) {
            $this->hasUnsavedChanges = false;

            return;
        }

        $this->hasUnsavedChanges = $this->instrumentStatesDiffer();
    }

    private function instrumentStatesDiffer(): bool
    {
        $current = $this->mapEnabledStates($this->instruments);
        $original = $this->mapEnabledStates($this->originalInstruments);
        $instrumentIds = array_unique(array_merge(array_keys($current), array_keys($original)));

        foreach ($instrumentIds as $instrumentId) {
            $currentEnabled = $current[$instrumentId] ?? false;
            $originalEnabled = $original[$instrumentId] ?? false;

            if ($currentEnabled !== $originalEnabled) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $instruments
     * @return array<int, bool>
     */
    private function mapEnabledStates(array $instruments): array
    {
        $states = [];

        foreach ($instruments as $instrument) {
            if (!is_array($instrument)) {
                continue;
            }

            $instrumentId = (int) ($instrument['instrument_id'] ?? $instrument['id'] ?? 0);

            if ($instrumentId <= 0) {
                continue;
            }

            $states[$instrumentId] = $this->toBoolean($instrument['enabled'] ?? false);
        }

        return $states;
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

    private function dispatchToast(string $message, string $variant = 'info'): void
    {
        $this->dispatch('toast', message: $message, variant: $variant)
            ->to(ToastNotifier::class);
    }

    private function registerApiError(string $message): void
    {
        $normalized = trim($message);

        if ($normalized === '') {
            return;
        }

        if ($this->getErrorBag()->has('api')) {
            return;
        }

        $this->addError('api', $normalized);
    }
}
