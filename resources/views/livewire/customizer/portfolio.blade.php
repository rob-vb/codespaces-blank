<div class="card bg-base-100 mt-2 shadow-sm" id="portfolio">
    <div class="card-body">
        <div class="card-title mb-4 text-gray-800">Portfolio Management System</div>
        <p>Set the % of your portfolio to allocate per trade depending on how much of your portfolio is already in use.</p>
    </div>
    <div class="overflow-x-auto px-2 md:px-6">
        <div class="border-1 border-gray-200 rounded-lg">
            <table class="table table--portfolio">
                <thead class="hidden md:table-header-group">
                    <tr class="grid grid-cols-2 grid-rows-3 py-2 md:p-0 border-b-1 border-gray-200 md:table-row md:border-b-0">
                        <th class="md:text-sm text-gray-800 bg-base-200">Portfolio Already Deployed</th>
                        <!-- Timeframes -->
                    </tr>
                </thead>
                <tbody class="block md:table-row-group">
                    <!-- Buckets -->
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-body sticky bottom-0 bg-base-100 md:static">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="pr-4 md:pr-0 text-xs md:text-sm">Example: enter 1.5 for 1.5% per order.</p>
            <div class="flex items-center gap-2">
                <button class="btn btn-ghost btn-sm js-reset-pms">Reset to default</button>
                <button class="btn btn-primary js-save-pms">Save</button>
            </div>
        </div>
    </div>
</div>
