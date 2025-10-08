<div class="card bg-base-100 mt-2 shadow-sm hidden js-customizer-tab" id="timeframes">
    <div class="card-body">
        <div class="card-title mb-4 text-gray-800">Enable timeframes</div>

        <!-- Timeframes -->
        @foreach($timeframes as $timeframe)
            <div class="flex items-center">
                <div class="flex items-center">
                    <input type="checkbox" class="checkbox checkbox-primary checkbox-sm" {{ $timeframe['is_selectable'] !== 1 ? 'disabled' : '' }} id="timeframe-{{ $timeframe['code'] }}">
                    <label class="ml-1" for="timeframe-{{ $timeframe['code'] }}">
                        {{ $timeframe['label'] }}
                    </label>
                </div>
            </div>
        @endforeach

        <div class="flex items-center justify-end gap-2">
            <button class="btn btn-ghost btn-sm">Reset to default</button>
            <button class="btn btn-primary">Save</button>
        </div>
    </div>
</div>
