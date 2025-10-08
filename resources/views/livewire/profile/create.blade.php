<form wire:submit.prevent="createProfile">
    <fieldset class="fieldset">
        <legend class="fieldset-legend">Profile name</legend>
        <input
            id="profile-label"
            name="label"
            type="text"
            maxlength="120"
            class="input"
            wire:model.live="profileLabel"
            required
        />
        @error('profileLabel')
            <p class="text-sm text-error mt-2">{{ $message }}</p>
        @enderror
    </fieldset>

    <div>
        <span class="label-text font-medium text-xs">Trade mode</span>
        <div class="mt-2 flex flex-col gap-2 md:flex-row md:items-center md:gap-4">
            <label class="inline-flex items-center gap-2">
                <input
                    type="radio"
                    name="trade_mode"
                    value="live"
                    class="radio radio-primary"
                    wire:model.change="profileTradeMode"
                />
                <span class="text-sm">Live</span>
            </label>
            <label class="inline-flex items-center gap-2">
                <input
                    type="radio"
                    name="trade_mode"
                    value="paper"
                    class="radio radio-primary"
                    wire:model.change="profileTradeMode"
                />
                <span class="text-sm">Paper</span>
            </label>
        </div>
        @error('profileTradeMode')
            <p class="text-sm text-error mt-2">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-4 md:grid-cols-2 {{ $profileTradeMode !== 'paper' ? 'hidden' : '' }}">
        <fieldset class="fieldset">
            <legend class="fieldset-legend">Paper fee (bps)</legend>
            <input
                id="paper-fee-bps"
                name="paper_fee_bps"
                type="number"
                step="0.01"
                min="0"
                class="input"
                wire:model.live="profilePaperFee"
            />
            <p class="label">Example: 10.0 represents 0.10%.</p>
            @error('profilePaperFee')
                <p class="text-sm text-error mt-2">{{ $message }}</p>
            @enderror
        </fieldset>

        <fieldset class="fieldset">
            <legend class="fieldset-legend">Starting balance (USD)</legend>
            <input
                id="starting-balance"
                name="starting_balance"
                type="number"
                step="0.01"
                min="0"
                class="input"
                wire:model.live="profileStartingBalance"
            />
            <p class="label">Defaults to 10,000 if left blank.</p>
            @error('profileStartingBalance')
                <p class="text-sm text-error mt-2">{{ $message }}</p>
            @enderror
        </fieldset>
    </div>

    <div class="modal-action">
        <button
            type="submit"
            class="btn btn-primary"
            wire:loading.attr="disabled"
            wire:target="createProfile"
        >
            <span wire:loading.remove wire:target="createProfile">Create profile</span>
            <span
                wire:loading
                wire:target="createProfile"
                class="loading loading-spinner loading-sm"
            ></span>
        </button>
    </div>
</form>
