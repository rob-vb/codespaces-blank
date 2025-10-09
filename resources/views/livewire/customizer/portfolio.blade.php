<div class="card bg-base-100 mt-2 shadow-sm">
    <div class="card-body">
        <div class="flex items-center card-title mb-4 text-gray-800">
            <span>Portfolio Management System</span>
            <span class="loading loading-spinner loading-xs" wire:loading></span>
        </div>
        @if($activeProfileId === null)
            <div class="alert alert-info text-sm mb-3 flex items-start gap-2" role="alert">
                <span>Select or create a profile to configure your portfolio management system.</span>
            </div>
        @endif
        <p>Set the % of your portfolio to allocate per trade depending on how much of your portfolio is already in use.</p>
    </div>
    <div class="overflow-x-auto px-2 md:px-6">
        <div class="border-1 border-gray-200 rounded-lg">
            <table class="table table--portfolio">
                <thead class="hidden md:table-header-group">
                    <tr class="grid grid-cols-2 grid-rows-3 py-2 md:p-0 border-b-1 border-gray-200 md:table-row md:border-b-0">
                        <th class="md:text-sm text-gray-800 bg-base-200">Portfolio Already Deployed</th>
                        @foreach($timeframes as $timeframe)
                            @if($timeframe['is_selectable'] !== 1) @continue @endif
                            <th class="md:text-sm text-gray-800 text-center bg-base-200">
                                {{ $timeframe['label'] }} % / entry
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="block md:table-row-group">
                    @foreach($buckets as $bucket)
                        <tr class="grid grid-cols-2 grid-rows-3 py-2 md:p-0 border-b-1 border-gray-200 md:table-row md:border-b-0">
                            <td class="w-full basis-full font-semibold place-items-stretch">
                                {{ $bucket['name'] }}
                            </td>
                            @foreach($timeframes as $timeframeIndex => $timeframe)
                                @if($timeframe['is_selectable'] !== 1) @continue @endif
                                <td class="flex items-center justify-end md:table-cell text-right md:text-center" wire:key="bucket-{{ $bucket['id'] }}-timeframe-{{ $timeframe['id'] }}">
                                    <label class="input input-sm w-20 pr-1 gap-0 text-right" aria-label="{{ $timeframe['label'] }} percent of equity">
                                        <input type="number"
                                                inputmode="decimal"
                                                wire:model.lazy="sizingPercentages.{{ $bucket['id'] }}.{{ $timeframe['id'] }}"
                                                data-timeframe="{{ (int) $timeframe['id'] }}"
                                                data-bucket-id="{{ $bucket['id'] }}"
                                                class="text-right js-number-input mr-1"
                                                step="0.01"
                                                min="0"
                                                max="100"
                                                @disabled($activeProfileId === null || empty($buckets))
                                        />
                                        <svg class="h-[1em]" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="5" y1="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                                    </label>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-body sticky bottom-0 bg-base-100 md:static">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="pr-4 md:pr-0 text-xs md:text-sm">Example: enter 1.5 for 1.5% per order.</p>
            <div class="flex grow-1 items-center justify-end gap-2">
                @if($hasUnsavedChanges)
                    <span class="badge badge-soft badge-warning badge-xs">Unsaved changes</span>
                @endif
                <button class="btn btn-ghost btn-sm js-reset-pms"
                        wire:click="resetToDefault"
                        wire:target="resetToDefault"
                        wire:loading.attr="disabled"
                        @disabled($activeProfileId === null || empty($buckets))
                >
                    <span wire:loading.remove wire:target="resetToDefault">Reset to default</span>
                    <span class="loading loading-spinner loading-xs" wire:loading wire:target="resetToDefault"></span>
                </button>
                <button class="btn btn-primary js-save-pms"
                        wire:click="save"
                        wire:target="save"
                        wire:loading.attr="disabled"
                        @disabled($activeProfileId === null || empty($buckets))
                >
                    <span wire:loading.remove wire:target="save">Save</span>
                    <span class="loading loading-spinner loading-xs" wire:loading wire:target="save"></span>
                </button>
            </div>
        </div>
    </div>
</div>
