<div
    class="p-4 md:p-8 w-full lg:w-4/5 mx-auto space-y-6"
    wire:loading.class="opacity-50"
>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col gap-1">
            <h1 class="text-3xl font-semibold text-accent">Trades</h1>
            @if ($loadError)
                <span class="text-sm text-error">{{ $loadError }}</span>
            @endif
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
            @if ($profiles !== [])
                <label class="flex flex-col gap-1 text-left">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Profile</span>
                    <select
                        wire:model.live="selectedProfileId"
                        id="profile-select"
                        class="select select-bordered select-sm min-w-[12rem]"
                    >
                        @foreach ($profiles as $profile)
                            @php
                                $profileId = (int) ($profile['id'] ?? 0);
                                $label = trim((string) ($profile['label'] ?? ''));

                                if ($label === '' && $profileId > 0) {
                                    $label = sprintf('Profile %d', $profileId);
                                }

                                $tradeMode = strtolower((string) ($profile['trade_mode'] ?? ''));

                                if ($tradeMode !== '') {
                                    $label .= sprintf(' - %s', ucfirst($tradeMode));
                                }
                            @endphp
                            <option value="{{ $profileId }}" @selected($profileId === $selectedProfileId)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>
            @endif
            <a class="btn btn-outline btn-sm" href="{{ route('customizer') }}">Back to Customizer</a>
            <button
                type="button"
                wire:click="refreshDashboard"
                class="btn btn-primary btn-sm"
            >
                Refresh
            </button>
        </div>
    </div>

    <div class="sm:grid sm:grid-cols-3 sm:grid-rows-5 gap-4">
        <div class="row-span-5 col-span-2 col-start-1 mb-4 sm:mb-0">
            <div class="card h-full bg-base-100 shadow-sm">
                <div class="card-body">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="card-title text-accent">
                                Portfolio Balance
                            </div>
                            <div class="flex items-center gap-1">
                                <p class="text-sm">PNL</p>
                                <div class="js-total-pnl"></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 relative">
                            <span class="text-xs">Period:</span>
                            <input class="input input-sm min-w-46" id="datepicker" />
                        </div>
                    </div>

                    <div id="maeve-chart"></div>
                </div>
            </div>
        </div>
        <div class="col-start-3 mb-4 sm:mb-0">
            <div class="card h-full bg-base-100 shadow-sm">
                <div class="card-body">
                    <span class="text-sm text-gray-500">
                        Current portfolio
                        <span class="text-xs">
                            <span class="js-percentage-deployed">(0.00% deployed)</span>
                        </span>
                    </span>
                    <div class="text-md font-semibold text-accent flex items-baseline gap-2 js-current-portfolio">
                        --
                    </div>
                </div>
            </div>
        </div>
        <div class="col-start-3 row-start-2 mb-4 sm:mb-0">
            <div class="card h-full bg-base-100 shadow-sm">
                <div class="card-body">
                    <span class="text-sm text-gray-500">
                        <p class="js-open-trades-count"></p>
                    </span>
                    <div class="text-md font-semibold text-accent flex items-baseline gap-2">
                        <div class="js-open-trades"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-start-3 row-start-3 mb-4 sm:mb-0">
            <div class="card h-full bg-base-100 shadow-sm">
                <div class="card-body">
                    <span class="text-sm text-gray-500">
                        <p class="js-closed-trades-count"></p>
                    </span>
                    <div class="text-md font-semibold text-accent flex items-baseline gap-2">
                        <div class="js-closed-trades"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-start-3 row-start-4 mb-4 sm:mb-0">
            <div class="card h-full bg-base-100 shadow-sm">
                <div class="card-body">
                    <span class="text-sm text-gray-500">Period PNL</span>
                    <div class="text-md font-semibold text-accent flex items-baseline gap-2">
                        <div class="js-period-pnl"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-start-3 row-start-5">
            <div class="card h-full bg-base-100 shadow-sm">
                <div class="card-body">
                    <span class="text-sm text-gray-500">Lifetime P&amp;L</span>
                    <div class="text-md font-semibold text-accent flex items-baseline gap-2">
                        <div class="js-lifetime-pnl"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm mt-4">
        <div class="overflow-x-auto table-positions">
            <table class="table table-xs" id="maeve-positions"></table>
        </div>
    </div>

    <dialog id="share_pnl_dialog" class="modal">
        <div class="modal-box">
            <form method="dialog">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
            </form>
            <h3 class="text-lg font-bold mb-4">Share PnL</h3>
            <div class="js-pnl-image"></div>

            <div class="flex justify-end mt-4">
                <a href="#" class="btn btn-secondary btn-soft js-pnl-download">Download</a>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <div
        id="trades-dashboard-payload"
        data-positions='@json($positions)'
        data-snapshot='@json($portfolioSnapshot)'
        data-history='@json($portfolioHistory)'
        hidden
    ></div>
</div>



@push('scripts')
    @vite('resources/js/trades/dashboard.js')
@endpush
