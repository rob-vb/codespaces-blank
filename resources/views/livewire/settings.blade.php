@php
    $definitions = $this->definitions;
    $placeholderKey = \App\Livewire\Settings::PLACEHOLDER_KEY;
    $hasPersistedCredentials = $this->hasPersistedCredentials;
    $persistedCredentials = $this->persistedCredentials;
    $persistedExchange = $hasPersistedCredentials ? ($persistedCredentials['exchange'] ?? []) : [];
    $isPaperProfile = $this->isPaperProfile;
    $activeProfile = $this->activeProfile ?? [];
    $activeProfileLabel = trim((string) ($activeProfile['label'] ?? ''));
    $paperProfileLabel = filled($activeProfileLabel) ? $activeProfileLabel : 'This profile';
    $rawSelectedExchangeKey = $this->selectedExchangeKey;
    $selectedExchangeKey = (blank($rawSelectedExchangeKey) || $rawSelectedExchangeKey === $placeholderKey)
        ? null
        : $rawSelectedExchangeKey;
    $selectedExchange = collect($definitions)->firstWhere('key', $selectedExchangeKey);
    $fields = $selectedExchange['fields'] ?? [];
    $variants = ($selectedExchange['has_variants'] ?? false) ? ($selectedExchange['variants'] ?? []) : [];
    $variantLabel = $selectedExchange['variant_label'] ?? 'Select variant';
    $storedFieldValues = ($hasPersistedCredentials && ($persistedExchange['key'] ?? null) === $selectedExchangeKey)
        ? ($persistedCredentials['credentials'] ?? [])
        : [];
@endphp

<div class="flex grow h-full items-start justify-center p-4 md:p-8">
    <div class="w-full max-w-3xl">
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body space-y-8">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="card-title text-2xl font-semibold text-gray-800">Exchange</h2>
                    <button
                        type="button"
                        class="text-sm font-medium text-primary underline"
                        onclick="exchange_tutorials.showModal()"
                    >
                        See tutorials
                    </button>
                    <dialog id="exchange_tutorials" class="modal">
                        <div
                            class="modal-box max-w-3xl space-y-4"
                            x-data="{ tutorialExchange: '__select_tutorial__' }"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-bold">Exchange Tutorials</h3>
                                </div>
                                <form method="dialog">
                                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                                </form>
                            </div>
                            <div class="space-y-4">
                                <div class="form-control">
                                    <label class="label" for="tutorial-exchange">
                                        <span class="label-text font-medium">Exchange</span>
                                    </label>
                                    <select
                                        id="tutorial-exchange"
                                        class="select select-bordered w-full"
                                        x-model="tutorialExchange"
                                    >
                                        <option value="__select_tutorial__" disabled selected>Select an option</option>
                                        @foreach($definitions as $definition)
                                            <option value="{{ $definition['key'] }}">{{ $definition['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="space-y-8">
                                    @foreach($definitions as $definition)
                                        <div
                                            x-show="tutorialExchange === '{{ $definition['key'] }}'"
                                            x-cloak
                                        >
                                            @include('tutorials.' . $definition['key'])
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="modal-action">
                                <form method="dialog">
                                    <button class="btn">Close</button>
                                </form>
                            </div>
                        </div>
                        <form method="dialog" class="modal-backdrop">
                            <button aria-label="Close tutorials">Close</button>
                        </form>
                    </dialog>
                </div>

                @if($isPaperProfile)
                    <div class="rounded-lg bg-base-200 p-6 text-base-content/80">
                        <h3 class="text-lg font-semibold text-base-content">Paper trading profile</h3>
                        <p class="mt-2 text-sm leading-relaxed">
                            {{ $paperProfileLabel }} uses paper trading. Exchange credentials are not required. Switch to a live profile to connect API keys.
                        </p>
                    </div>
                @else
                    @if(empty($definitions))
                        <div class="rounded-lg bg-base-200 p-6 text-center text-base-content/70">
                            No exchanges are available right now. Please try again later.
                        </div>
                    @else
                        <div class="space-y-6">
                            <div class="form-control">
                                <label class="label" for="exchange-key">
                                    <span class="label-text font-medium text-base-content/80">Exchange</span>
                                </label>
                                <select
                                    id="exchange-key"
                                    class="select select-bordered w-full"
                                    wire:model.live="selectedExchangeKey"
                                    @disabled($hasPersistedCredentials)
                                >
                                    <option value="{{ $placeholderKey }}" @selected($this->selectedExchangeKey === $placeholderKey) disabled>
                                        Select an option
                                    </option>
                                    @foreach($definitions as $definition)
                                        <option value="{{ $definition['key'] }}">{{ $definition['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if($selectedExchange && ! empty($variants))
                                <div class="form-control">
                                    <label class="label" for="exchange-variant">
                                        <span class="label-text font-medium text-base-content/80">{{ $variantLabel }}</span>
                                    </label>
                                    <select
                                        id="exchange-variant"
                                        class="select select-bordered w-full"
                                        wire:model.live="selectedVariantId"
                                        @disabled($hasPersistedCredentials)
                                    >
                                        @foreach($variants as $variant)
                                            @php
                                                $variantValue = $variant['id'] ?? $variant['name'];
                                            @endphp
                                            <option value="{{ $variantValue }}">{{ $variant['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            @if($selectedExchange)
                                <div class="space-y-6">
                                    @foreach($fields as $field)
                                        @php
                                            $fieldId = sprintf('%s-%s', $selectedExchange['key'] ?? 'exchange', $field['name']);
                                            $storedValue = $storedFieldValues[$field['name']] ?? null;
                                            $isPassword = ($field['type'] ?? 'text') === 'password';
                                            $isRevealed = $this->revealState[$fieldId] ?? false;
                                            $inputType = $isPassword && ! $isRevealed ? 'password' : 'text';
                                        @endphp
                                        <div class="form-control" wire:key="{{ $fieldId }}">
                                            <label class="label" for="{{ $fieldId }}">
                                                <span class="label-text font-medium text-base-content/80">{{ $field['label'] }}</span>
                                                @if(filled($storedValue))
                                                    <span class="text-xs font-semibold uppercase tracking-wide text-success">
                                                        Saved
                                                    </span>
                                                @endif
                                            </label>
                                            <div class="relative">
                                                <input
                                                    id="{{ $fieldId }}"
                                                    type="{{ $inputType }}"
                                                    maxlength="{{ $field['max_length'] ?? 255 }}"
                                                    @if(! empty($field['placeholder']))
                                                        placeholder="{{ $field['placeholder'] }}"
                                                    @endif
                                                    @if(! empty($field['required']))
                                                        required
                                                    @endif
                                                    class="input input-bordered w-full pr-16"
                                                    wire:model.live="credentials.{{ $selectedExchange['key'] }}.{{ $field['name'] }}"
                                                >
                                                @if($isPassword)
                                                    <button
                                                        type="button"
                                                        class="btn btn-link btn-xs absolute inset-y-0 right-2 my-auto px-2 font-medium text-primary"
                                                        wire:click="toggleReveal('{{ $fieldId }}')"
                                                    >
                                                        {{ $isRevealed ? 'Hide' : 'Show' }}
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    @error('api')
                        <div class="rounded-md border border-error/30 bg-error/10 px-4 py-3 text-sm text-error">
                            {{ $message }}
                        </div>
                    @enderror

                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            class="btn btn-secondary btn-soft"
                        >
                            Check API Keys
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            wire:click="saveCredentials"
                            wire:loading.attr="disabled"
                            wire:target="saveCredentials"
                        >
                            <span wire:loading.remove wire:target="saveCredentials">Save Credentials</span>
                            <span wire:loading wire:target="saveCredentials">Saving…</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
