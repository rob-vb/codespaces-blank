<div class="card bg-base-100 mt-2 shadow-sm">
    <div class="card-body">
        <div class="flex items-center card-title mb-4 text-gray-800">
            <span>Enable timeframes</span>
            <span class="loading loading-spinner loading-xs ml-2" wire:loading></span>
        </div>

        @error('api')
            <div class="alert alert-error text-sm mb-3" role="alert">
                {{ $message }}
            </div>
        @enderror

        @if($activeProfileId === null)
            <div class="alert alert-info text-sm mb-3" role="alert">
                <span>Select or create a profile to manage timeframes.</span>
            </div>
        @endif

        <form wire:submit.prevent="saveTimeframes" class="flex flex-col gap-4">
            <fieldset class="flex flex-col gap-3">
                @forelse($timeframes as $index => $timeframe)
                    <div class="flex items-center" wire:key="timeframe-{{ $timeframe['timeframe_id'] }}-{{ $index }}">
                        <label class="flex items-center gap-2 cursor-pointer" for="timeframe-{{ $timeframe['timeframe_id'] }}">
                            <input type="checkbox"
                                   class="checkbox checkbox-primary checkbox-sm"
                                   wire:model.boolean="timeframes.{{ $index }}.enabled"
                                   id="timeframe-{{ $timeframe['timeframe_id'] }}"
                                   @checked($timeframe['enabled'] ?? false)
                                   @disabled($timeframe['is_selectable'] !== 1 || $activeProfileId === null)
                            >
                            <span>{{ $timeframe['label'] }}</span>
                        </label>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No timeframes available.</p>
                @endforelse
            </fieldset>

            <div class="flex items-center justify-end gap-2">
                <button type="button"
                        class="btn btn-ghost btn-sm"
                        wire:click="resetToDefault"
                        wire:target="resetToDefault"
                        wire:loading.attr="disabled"
                        @disabled($activeProfileId === null || empty($timeframes))
                >
                    <span wire:loading.remove wire:target="resetToDefault">Reset to default</span>
                    <span class="loading loading-spinner loading-xs" wire:loading wire:target="resetToDefault"></span>
                </button>
                <button type="submit"
                        class="btn btn-primary"
                        wire:target="saveTimeframes"
                        wire:loading.attr="disabled"
                        @disabled($activeProfileId === null || empty($timeframes))
                >
                    <span wire:loading.remove wire:target="saveTimeframes">Save</span>
                    <span class="loading loading-spinner loading-xs" wire:loading wire:target="saveTimeframes"></span>
                </button>
            </div>
        </form>
    </div>
</div>
