<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Notifications\Toast as ToastNotifier;
use App\Services\ActiveProfileManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

final class Settings extends Component
{
    public const PLACEHOLDER_KEY = '__select_exchange__';

    public ?int $activeProfileId = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $activeProfile = null;

    public bool $isPaperProfile = false;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $definitions = [];

    public ?string $selectedExchangeKey = self::PLACEHOLDER_KEY;

    public string|int|null $selectedVariantId = null;

    /**
     * @var array<string, array<string, string>>
     */
    public array $credentials = [];

    /**
     * @var array{
     *     exchange: array<string, mixed>,
     *     credentials: array<string, string>
     * }|null
     */
    public ?array $persistedCredentials = null;

    /**
     * @var array<string, bool>
     */
    public array $revealState = [];

    public function boot(ActiveProfileManager $activeProfileManager): void
    {
        $this->activeProfileId = $activeProfileManager->getActiveProfileId();
    }

    public function mount(): void
    {
        $this->definitions = $this->getDefinitions();
        $this->refreshActiveProfileMetadata();
        $this->loadPersistedCredentials();
    }

    public function updatedSelectedExchangeKey(?string $key): void
    {
        if ($this->isPlaceholderKey($key)) {
            $this->selectedExchangeKey = self::PLACEHOLDER_KEY;
            $this->selectedVariantId = null;

            return;
        }

        $this->selectedExchangeKey = $key;
        $this->hydrateSelectedExchangeState();
    }

    #[On('active-profile-updated')]
    public function handleActiveProfileUpdated(?int $profileId = null): void
    {
        $this->activeProfileId = $profileId;
        $this->resetErrorBag('api');
        $this->credentials = [];
        $this->activeProfile = null;
        $this->isPaperProfile = false;
        $this->persistedCredentials = null;
        $this->selectedExchangeKey = self::PLACEHOLDER_KEY;
        $this->selectedVariantId = null;
        $this->refreshActiveProfileMetadata();
        $this->loadPersistedCredentials();
    }

    public function saveCredentials(): void
    {
        $this->resetErrorBag('api');

        if ($this->isPaperProfile) {
            $message = 'Paper trading profiles do not require exchange credentials.';
            $this->dispatchToast($message, 'info');

            return;
        }

        if ($this->activeProfileId === null) {
            $message = 'Select a profile before saving credentials.';
            $this->addError('api', $message);
            $this->dispatchToast($message, 'error');

            return;
        }

        $exchange = $this->selectedExchange();

        if ($exchange === null) {
            $message = 'Choose an exchange before saving.';
            $this->addError('api', $message);
            $this->dispatchToast($message, 'error');

            return;
        }

        $variantId = null;

        if (($exchange['has_variants'] ?? false) && ! empty($exchange['variants'])) {
            if ($this->selectedVariantId === null || $this->selectedVariantId === '') {
                $message = 'Select a variant before saving.';
                $this->addError('api', $message);
                $this->dispatchToast($message, 'error');

                return;
            }

            $variantId = is_numeric($this->selectedVariantId)
                ? (int) $this->selectedVariantId
                : (string) $this->selectedVariantId;
        }

        $validatedCredentials = $this->validateCredentials($exchange);

        $payload = [
            'profile_id' => $this->activeProfileId,
            'exchange' => $exchange['key'],
            'credentials' => $validatedCredentials,
        ];

        if ($variantId !== null) {
            $payload['variant_id'] = $variantId;
        }

        try {
            $response = Http::remote()->post('/api/v2/settings/credentials/', $payload);
        } catch (Throwable $exception) {
            report($exception);
            $message = 'Unable to save credentials. Please try again.';
            $this->addError('api', $message);
            $this->dispatchToast($message, 'error');

            return;
        }

        if ($response->failed()) {
            if ($response->status() === 422) {
                $errors = $response->json('errors') ?? [];
                $message = (string) Collection::make($errors)->flatten()->filter()->first()
                    ?: 'Unable to save credentials. Please review your entries.';
                $this->addError('api', $message);
                $this->dispatchToast($message, 'error');

                return;
            }

            $message = (string) ($response->json('error') ?? 'Unable to save credentials. Please try again.');
            $this->addError('api', $message);
            $this->dispatchToast($message, 'error');

            return;
        }

        $exchangeKey = $exchange['key'];

        $persistedExchange = [
            'key' => $exchangeKey,
            'label' => $exchange['label'] ?? null,
        ];

        if (isset($exchange['exchange_id'])) {
            $persistedExchange['exchange_id'] = $exchange['exchange_id'];
        }

        if ($variantId !== null) {
            $variant = Collection::make($exchange['variants'] ?? [])
                ->first(static function (array $candidate) use ($variantId): bool {
                    $identifier = $candidate['id'] ?? $candidate['name'] ?? null;

                    if ($identifier === null) {
                        return false;
                    }

                    if (is_numeric($identifier)) {
                        return (int) $identifier === (int) $variantId;
                    }

                    return (string) $identifier === (string) $variantId;
                });

            if (is_array($variant)) {
                $persistedExchange['variant'] = $variant;
            } else {
                $persistedExchange['variant'] = [
                    'id' => is_numeric($variantId) ? (int) $variantId : null,
                    'name' => is_string($variantId) ? $variantId : null,
                    'label' => null,
                ];
            }

            $this->selectedVariantId = $variantId;
        } else {
            $this->selectedVariantId = null;
        }

        $this->persistedCredentials = [
            'exchange' => $persistedExchange,
            'credentials' => $validatedCredentials,
        ];

        $this->credentials[$exchangeKey] = [];

        foreach ($exchange['fields'] ?? [] as $field) {
            $fieldName = $field['name'];
            $this->credentials[$exchangeKey][$fieldName] = $validatedCredentials[$fieldName] ?? '';
        }

        $this->hydrateSelectedExchangeState();

        $this->dispatchToast('Credentials saved.', 'success');
    }

    public function toggleReveal(string $fieldId): void
    {
        $this->revealState[$fieldId] = ! ($this->revealState[$fieldId] ?? false);
    }

    private function getDefinitions(): array
    {
        try {
            $response = Http::remote()->get('/api/v2/settings/definitions');

            return $response->json('data')['exchanges'] ?? [];
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('api', 'Network error. Please try again.');

            return [];
        }
    }

    private function loadPersistedCredentials(): void
    {
        if ($this->activeProfileId === null) {
            $this->persistedCredentials = null;
            $this->selectedExchangeKey = self::PLACEHOLDER_KEY;
            $this->selectedVariantId = null;

            return;
        }

        if ($this->isPaperProfile) {
            $this->persistedCredentials = null;
            $this->selectedExchangeKey = self::PLACEHOLDER_KEY;
            $this->selectedVariantId = null;

            return;
        }

        try {
            $response = Http::remote()->get('/api/v2/settings/credentials/' . $this->activeProfileId);
        } catch (Throwable $exception) {
            report($exception);
            $this->persistedCredentials = null;
            $this->selectedExchangeKey = self::PLACEHOLDER_KEY;
            $this->selectedVariantId = null;

            return;
        }

        if ($response->failed()) {
            if ($response->status() === 404) {
                $this->persistedCredentials = null;
                $this->selectedExchangeKey = self::PLACEHOLDER_KEY;
                $this->selectedVariantId = null;

                return;
            }

            $message = (string) ($response->json('error') ?? 'Unable to load saved credentials.');
            $this->addError('api', $message);
            $this->persistedCredentials = null;
            $this->selectedExchangeKey = self::PLACEHOLDER_KEY;
            $this->selectedVariantId = null;

            return;
        }

        $payload = $response->json();

        if (! is_array($payload) || ($payload['success'] ?? false) !== true) {
            $this->persistedCredentials = null;
            $this->selectedExchangeKey = self::PLACEHOLDER_KEY;
            $this->selectedVariantId = null;

            return;
        }

        $exchangeData = $payload['exchange'] ?? null;
        $credentials = $payload['credentials'] ?? [];

        if (! is_array($exchangeData) || ! is_array($credentials)) {
            $this->persistedCredentials = null;
            $this->selectedExchangeKey = self::PLACEHOLDER_KEY;
            $this->selectedVariantId = null;

            return;
        }

        $exchangeKey = $exchangeData['key'] ?? null;

        if ($exchangeKey === null) {
            $this->persistedCredentials = null;
            $this->selectedExchangeKey = self::PLACEHOLDER_KEY;
            $this->selectedVariantId = null;

            return;
        }

        $this->persistedCredentials = [
            'exchange' => $exchangeData,
            'credentials' => $credentials,
        ];

        $this->credentials[$exchangeKey] = [];

        foreach ($credentials as $fieldName => $value) {
            $this->credentials[$exchangeKey][$fieldName] = is_string($value) ? $value : (string) $value;
        }

        $this->selectedExchangeKey = $exchangeKey;

        $variant = $exchangeData['variant'] ?? null;

        if (is_array($variant)) {
            $this->selectedVariantId = $variant['id'] ?? $variant['name'] ?? null;
        } else {
            $this->selectedVariantId = null;
        }

        $this->hydrateSelectedExchangeState();
    }

    private function hydrateSelectedExchangeState(): void
    {
        if ($this->isPlaceholderKey($this->selectedExchangeKey)) {
            $this->selectedVariantId = null;

            return;
        }

        $exchange = $this->selectedExchange();

        if ($exchange === null) {
            $this->selectedVariantId = null;

            return;
        }

        $exchangeKey = $exchange['key'];

        if (! isset($this->credentials[$exchangeKey])) {
            $this->credentials[$exchangeKey] = [];
        }

        $persisted = [];

        if ($this->hasPersistedCredentials && ($this->persistedCredentials['exchange']['key'] ?? null) === $exchangeKey) {
            $persisted = $this->persistedCredentials['credentials'] ?? [];
        }

        foreach ($exchange['fields'] ?? [] as $field) {
            $fieldName = $field['name'];

            if (! array_key_exists($fieldName, $this->credentials[$exchangeKey])) {
                $this->credentials[$exchangeKey][$fieldName] = array_key_exists($fieldName, $persisted)
                    ? (string) $persisted[$fieldName]
                    : '';
            }
        }

        $variants = $exchange['variants'] ?? [];

        if (($exchange['has_variants'] ?? false) && ! empty($variants)) {
            $storedVariant = null;

            if ($this->hasPersistedCredentials && ($this->persistedCredentials['exchange']['key'] ?? null) === $exchangeKey) {
                $persistedVariant = $this->persistedCredentials['exchange']['variant'] ?? null;

                if (is_array($persistedVariant)) {
                    $storedVariant = $persistedVariant['id'] ?? $persistedVariant['name'] ?? null;
                }
            }

            if ($storedVariant !== null && $storedVariant !== '') {
                $this->selectedVariantId = $storedVariant;

                return;
            }

            if ($this->selectedVariantId === null || $this->selectedVariantId === '') {
                $firstVariant = $variants[0];
                $this->selectedVariantId = $firstVariant['id'] ?? $firstVariant['name'] ?? null;
            }

            return;
        }

        $this->selectedVariantId = null;
    }

    private function selectedExchange(): ?array
    {
        if ($this->isPlaceholderKey($this->selectedExchangeKey)) {
            return null;
        }

        foreach ($this->definitions as $definition) {
            if (($definition['key'] ?? null) === $this->selectedExchangeKey) {
                return $definition;
            }
        }

        return null;
    }

    private function validateCredentials(array $exchange): array
    {
        $exchangeKey = $exchange['key'];
        $fields = $exchange['fields'] ?? [];

        $input = $this->credentials[$exchangeKey] ?? [];
        $rules = [];

        foreach ($fields as $field) {
            $ruleSet = [];
            $ruleSet[] = ($field['required'] ?? false) ? 'required' : 'nullable';
            $ruleSet[] = 'string';

            if (! empty($field['max_length'])) {
                $ruleSet[] = 'max:' . (int) $field['max_length'];
            }

            $rules[$field['name']] = $ruleSet;
        }

        $validated = Validator::make($input, $rules)->validate();

        return Collection::make($fields)
            ->pluck('name')
            ->mapWithKeys(static fn (string $name) => [$name => $validated[$name] ?? null])
            ->filter(static fn ($value) => $value !== null && $value !== '')
            ->all();
    }

    private function dispatchToast(string $message, string $variant = 'info'): void
    {
        $this->dispatch('toast', message: $message, variant: $variant)
            ->to(ToastNotifier::class);
    }

    private function isPlaceholderKey(?string $key): bool
    {
        return $key === null || $key === '' || $key === self::PLACEHOLDER_KEY;
    }

    public function getHasPersistedCredentialsProperty(): bool
    {
        return is_array($this->persistedCredentials)
            && isset($this->persistedCredentials['exchange'], $this->persistedCredentials['credentials']);
    }

    private function refreshActiveProfileMetadata(): void
    {
        $this->activeProfile = null;
        $this->isPaperProfile = false;

        if ($this->activeProfileId === null) {
            return;
        }

        try {
            $response = Http::remote()->get('/api/v2/profiles');
        } catch (Throwable $exception) {
            report($exception);

            return;
        }

        if ($response->failed()) {
            report($response->toException());

            return;
        }

        $profiles = $response->json('data');

        if (! is_array($profiles)) {
            return;
        }

        $activeProfileId = $this->activeProfileId;

        $activeProfile = Collection::make($profiles)
            ->first(static function (array $profile) use ($activeProfileId): bool {
                if ($activeProfileId === null) {
                    return false;
                }

                return (int) ($profile['id'] ?? 0) === $activeProfileId;
            });

        if (! is_array($activeProfile)) {
            return;
        }

        $this->activeProfile = $activeProfile;

        $tradeMode = strtolower((string) ($activeProfile['trade_mode'] ?? ''));

        $this->isPaperProfile = $tradeMode === 'paper';
    }

    public function render(): View
    {
        return view('livewire.settings');
    }
}
