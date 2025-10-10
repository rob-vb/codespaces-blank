<div class="card bg-base-100 mt-2 shadow-sm">
    <div class="card-body">
        <div class="flex flex-wrap items-center gap-2 card-title mb-4 text-gray-800">
            <span>Enable currencies</span>
            <span class="loading loading-spinner loading-xs" wire:loading></span>
        </div>

        @error('api')
            <div class="alert alert-error text-sm mb-3" role="alert">
                {{ $message }}
            </div>
        @enderror

        @if($activeProfileId === null)
            <div class="alert alert-info text-sm mb-3" role="alert">
                <span>Select or create a profile to manage currencies.</span>
            </div>
        @endif

        <div class="flex flex-col gap-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 rounded-lg border border-base-200 bg-base-200/40">
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-semibold text-base-content">Include all currencies</span>
                    <span class="text-xs text-base-content/70">Enable the entire exchange instrument list without selecting pairs individually.</span>
                </div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <span class="text-sm text-base-content/80">{{ $includeAllInstruments ? 'Enabled' : 'Disabled' }}</span>
                    <input
                        type="checkbox"
                        class="toggle toggle-success"
                        wire:model.live="includeAllInstruments"
                        @disabled($activeProfileId === null || empty($instruments))
                    >
                </label>
            </div>

            @if(!empty($instruments))
                <div class="flex items-center justify-between text-xs text-base-content/70">
                    <span>{{ $totalEnabled }} of {{ $totalInstruments }} currencies enabled</span>
                    <button
                        type="button"
                        class="btn btn-ghost btn-xs"
                        wire:click="resetChanges"
                        wire:loading.attr="disabled"
                        wire:target="resetChanges"
                        @disabled(!$hasUnsavedChanges)
                    >
                        <span wire:loading.remove wire:target="resetChanges">Reset changes</span>
                        <span class="loading loading-spinner loading-xs" wire:loading wire:target="resetChanges"></span>
                    </button>
                </div>
            @endif

            <fieldset class="grid grid-cols-1 md:grid-cols-2 gap-2">
                @forelse($instruments as $index => $instrument)
                    @php
                        $instrumentId = (int) ($instrument['instrument_id'] ?? $instrument['id'] ?? 0);
                        $displaySymbol = $instrument['display_symbol'] ?? $instrument['symbol_on_exchange'] ?? '';
                        $baseLabel = $instrument['base_name'] ?? $instrument['base_symbol'] ?? null;
                        $quoteLabel = $instrument['quote_name'] ?? $instrument['quote_symbol'] ?? null;
                        $descriptor = array_values(array_filter([$baseLabel, $quoteLabel]));
                    @endphp
                    <label
                        class="flex items-start gap-3 p-3 rounded-lg border border-base-200 bg-base-100 hover:bg-base-200/50 transition"
                        wire:key="instrument-{{ $instrumentId }}-{{ $index }}"
                        for="instrument-{{ $instrumentId }}"
                    >
                        <input
                            type="checkbox"
                            id="instrument-{{ $instrumentId }}"
                            class="checkbox checkbox-primary checkbox-sm mt-1"
                            wire:model.live="instruments.{{ $index }}.enabled"
                            @disabled($includeAllInstruments || $activeProfileId === null)
                        >
                        <span class="flex flex-col">
                            <span class="font-semibold text-base-content">{{ $displaySymbol }}</span>
                            @if(!empty($descriptor))
                                <span class="text-xs text-base-content/70">{{ implode(' / ', $descriptor) }}</span>
                            @endif
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-base-content/70">No currencies available.</p>
                @endforelse
            </fieldset>
        </div>

        <div class="mt-6 flex items-center justify-end gap-2">
            @if($hasUnsavedChanges)
                <span class="badge badge-soft badge-warning badge-xs mr-2">Unsaved changes</span>
            @endif

            <button
                type="button"
                class="btn btn-primary"
                wire:click="save"
                wire:target="save"
                wire:loading.attr="disabled"
                @disabled($activeProfileId === null || empty($instruments))
            >
                <span wire:loading.remove wire:target="save">Save</span>
                <span class="loading loading-spinner loading-xs" wire:loading wire:target="save"></span>
            </button>
        </div>
    </div>
</div>
