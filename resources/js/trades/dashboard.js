const SCRIPT_SOURCES = [
    {
        id: 'apexcharts',
        src: 'https://cdn.jsdelivr.net/npm/apexcharts',
        global: 'ApexCharts',
    },
    {
        id: 'datatables',
        src: 'https://cdn.datatables.net/v/dt/dt-2.2.1/r-3.0.3/datatables.min.js',
        global: 'DataTable',
    },
    {
        id: 'easepick',
        src: 'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.umd.min.js',
        global: 'easepick',
    },
    {
        id: 'dompurify',
        src: 'https://cdn.jsdelivr.net/npm/dompurify@2.4.0/dist/purify.min.js',
        global: 'DOMPurify',
    },
];

const STYLE_SOURCES = [
    'https://cdn.datatables.net/v/dt/dt-2.2.1/r-3.0.3/datatables.min.css',
    'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.css',
];

const state = {
    positions: [],
    history: [],
    snapshot: {},
    chart: null,
    dataTable: null,
    datePicker: null,
    dependenciesLoaded: false,
};

const selectors = {
    payload: '#trades-dashboard-payload',
    chart: '#maeve-chart',
    table: '#maeve-positions',
    datePicker: '#datepicker',
};

const dom = {
    totalPnl: document.querySelector('.js-total-pnl'),
    currentPortfolio: document.querySelector('.js-current-portfolio'),
    percentageDeployed: document.querySelector('.js-percentage-deployed'),
    openTrades: document.querySelector('.js-open-trades'),
    openTradesCount: document.querySelector('.js-open-trades-count'),
    closedTrades: document.querySelector('.js-closed-trades'),
    closedTradesCount: document.querySelector('.js-closed-trades-count'),
    periodPnl: document.querySelector('.js-period-pnl'),
    lifetimePnl: document.querySelector('.js-lifetime-pnl'),
    pnlImageContainer: document.querySelector('#share_pnl_dialog .js-pnl-image'),
    pnlDownloadButton: document.querySelector('#share_pnl_dialog .js-pnl-download'),
};

document.addEventListener('DOMContentLoaded', () => {
    bootstrapFromPayload();
    registerLivewireListeners();
    registerDomListeners();
});

function registerLivewireListeners() {
    window.addEventListener('trades-dashboard:loaded', async (event) => {
        const detail = event.detail ?? {};
        await applyDashboardData(detail);
    });

    window.addEventListener('trades-dashboard:failed', () => {
        resetUi();
    });
}

function registerDomListeners() {
    document.addEventListener('click', (event) => {
        const tableArrow = event.target.closest('.js-toggle-connected');

        if (!tableArrow) {
            return;
        }

        handleToggleConnected(tableArrow);
    });

    document.addEventListener('click', (event) => {
        const shareButton = event.target.closest('.js-share-pnl-button');

        if (!shareButton) {
            return;
        }

        openShareDialog(shareButton.dataset.id ?? '');
    });

    window.addEventListener('resize', handleMobileChildToggleRows);
}

async function bootstrapFromPayload() {
    const payloadEl = document.querySelector(selectors.payload);

    if (!payloadEl) {
        return;
    }

    const positions = parseJsonAttribute(payloadEl.dataset.positions);
    const snapshot = parseJsonAttribute(payloadEl.dataset.snapshot);
    const history = parseJsonAttribute(payloadEl.dataset.history);

    await applyDashboardData({
        positions,
        snapshot,
        history,
    });
}

async function applyDashboardData(detail) {
    await ensureDependencies();
    state.positions = Array.isArray(detail.positions) ? detail.positions : [];
    state.history = Array.isArray(detail.history) ? detail.history : [];
    state.snapshot = detail.snapshot ?? {};

    renderCurrentPortfolio();

    const positionsMapped = mapPositions(state.positions);

    renderTable(positionsMapped);
    renderChart(state.history);
    ensureDatePicker(positionsMapped);
    updateBadges(positionsMapped);
    handleMobileChildToggleRows();
}

function resetUi() {
    if (state.chart) {
        state.chart.destroy();
        state.chart = null;
    }

    if (state.dataTable) {
        state.dataTable.destroy();
        state.dataTable = null;
    }

    if (state.datePicker) {
        state.datePicker.destroy();
        state.datePicker = null;
    }

    Object.entries(dom).forEach(([key, element]) => {
        if (!element) {
            return;
        }

        if (element instanceof HTMLElement) {
            if (key === 'pnlDownloadButton') {
                element.removeAttribute('href');
                return;
            }

            element.innerHTML = '';
        }
    });
}

function parseJsonAttribute(value) {
    if (!value) {
        return null;
    }

    try {
        return JSON.parse(value);
    } catch {
        return null;
    }
}

async function ensureDependencies() {
    if (state.dependenciesLoaded) {
        return;
    }

    STYLE_SOURCES.forEach((href) => {
        if (document.querySelector(`link[data-dashboard-style="${href}"]`)) {
            return;
        }

        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        link.dataset.dashboardStyle = href;
        document.head.appendChild(link);
    });

    for (const source of SCRIPT_SOURCES) {
        // eslint-disable-next-line no-await-in-loop
        await loadScriptOnce(source);
    }

    state.dependenciesLoaded = true;
}

function loadScriptOnce({ id, src, global }) {
    if (global && window[global]) {
        return Promise.resolve();
    }

    if (scriptPromises.has(id)) {
        return scriptPromises.get(id);
    }

    const existingScript = document.querySelector(`script[data-dashboard-script="${id}"]`);

    if (existingScript && existingScript.dataset.loaded === 'true') {
        return Promise.resolve();
    }

    const promise = new Promise((resolve, reject) => {
        const script = existingScript ?? document.createElement('script');

        if (!existingScript) {
            script.src = src;
            script.async = true;
            script.dataset.dashboardScript = id;
            document.head.appendChild(script);
        }

        script.addEventListener('load', () => {
            script.dataset.loaded = 'true';
            resolve();
        }, { once: true });

        script.addEventListener('error', () => {
            scriptPromises.delete(id);
            reject(new Error(`Failed to load script ${src}`));
        }, { once: true });
    });

    scriptPromises.set(id, promise);
    return promise;
}

function renderCurrentPortfolio() {
    if (!dom.currentPortfolio || !dom.percentageDeployed) {
        return;
    }

    const currentPortfolio = Number.parseFloat(state.snapshot.current_portfolio ?? 0);
    const pctDeployed = Number.parseFloat(state.snapshot.pct_deployed ?? 0);

    dom.currentPortfolio.textContent = isFinite(currentPortfolio)
        ? formatCurrency(currentPortfolio)
        : '--';

    dom.percentageDeployed.textContent = `(${formatPercentage(pctDeployed)} deployed)`;
}

function mapPositions(positions) {
    const filtered = positions.filter((item) => item.timeframe !== '1d');
    const positionsMap = new Map();

    filtered.forEach((position) => {
        positionsMap.set(position.id, {
            ...position,
            connected: [],
        });
    });

    filtered.forEach((position) => {
        if (position.dca_parent_id === null) {
            return;
        }

        const parent = positionsMap.get(position.dca_parent_id);

        if (!parent) {
            return;
        }

        parent.connected.push(position);
    });

    return Array.from(positionsMap.values()).filter((position) => position.dca_parent_id === null);
}

function renderTable(positionsMapped) {
    if (!window.DataTable) {
        return;
    }

    if (state.dataTable) {
        state.dataTable.destroy();
        state.dataTable = null;
    }

    const tableElement = document.querySelector(selectors.table);

    if (!tableElement) {
        return;
    }

    state.dataTable = new window.DataTable(tableElement, {
        data: positionsMapped,
        order: [[4, 'desc']],
        columnDefs: [
            {
                className: 'dtr-control',
                orderable: false,
                target: -1,
            },
        ],
        responsive: {
            details: {
                type: 'column',
                target: -1,
            },
        },
        layout: {
            topStart: {
                div: {
                    html: '<h2>Trades</h2>',
                },
            },
            topEnd: ['pageLength', 'search'],
        },
        columns: buildColumns(),
    });
}

function buildColumns() {
    return [
        {
            data: 'token',
            title: 'Currency',
            width: 80,
        },
        {
            data: 'pnl',
            title: 'PNL',
            width: 90,
            className: 'pnl-cell',
            render: (data, type, row) => renderPnlPercentage(data, type, row),
        },
        {
            data: null,
            title: 'PNL ($)',
            render: (data, type, row) => renderPnlCurrency(type, row),
        },
        {
            data: 'status',
            title: 'Status',
            width: 64,
            class: 'js-table-status',
            render: (data, type) => renderStatusBadge(data, type),
        },
        {
            data: 'id',
            title: 'ID',
            width: 64,
            class: 'id text-left',
        },
        {
            data: 'timeframe',
            title: 'Timeframe',
            width: 80,
            class: 'js-table-timeframe',
        },
        {
            data: 'buy_price',
            title: '(Avg.) Entry&nbsp;Price',
            className: 'wrap-header',
            render: (data, type, row) => renderEntryPrice(data, type, row),
        },
        {
            data: 'closing_price',
            title: 'Exit Price',
            class: 'nowrap',
            render: (data, type, row) => {
                if (!data || type !== 'display') {
                    return data;
                }

                return `$ ${formatPrice(row.token, data)}`;
            },
        },
        {
            data: 'buy_usd_amount',
            title: 'Entry&nbsp;Amount (USD)',
            className: 'wrap-header',
            render: (data, type, row) => renderEntryAmount(type, row),
        },
        {
            data: 'sell_usd_amount',
            title: 'Exit&nbsp;Amount (USD)',
            className: 'wrap-header',
            render: (data, type, row) => renderExitAmount(type, row),
        },
        {
            data: 'buy_timestamp',
            title: 'Timestamp (UTC)',
            width: 220,
            render: (data, type, row) => renderTimestamp(data, type, row),
        },
        {
            data: 'connected',
            title: '<span class="connected-title">Toggle DCA trades</span><div class="dca-title">DCA </div>',
            defaultContent: '',
            sortable: false,
            class: 'dca text-right',
            width: 24,
            render: (data, type, row) => {
                if (!data || data.length === 0) {
                    return '';
                }

                return `<span class="table-arrow js-toggle-connected" data-id="${row.id}"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M480-344 240-584l56-56 184 184 184-184 56 56-240 240Z"/></svg></span>`;
            },
        },
        {
            data: undefined,
            render: () => '',
        },
    ];
}

function renderPnlPercentage(data, type, row) {
    if (data) {
        if (type === 'display') {
            if (row.average_entry) {
                const badgeType = data.includes('-') ? 'error' : 'success';

                return `
                    <div class="flex items-center">
                        <span class="badge badge-${badgeType} badge-soft badge-xs">
                            ${badgeType === 'success' ? '+' : ''}${data}%
                        </span>
                        <button type="button" class="btn btn-primary btn-soft btn-xs ml-2 tooltip js-share-pnl-button" data-tip="Share" data-id="${row.id}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-share-icon lucide-share w-3 h-3"><path d="M12 2v13"/><path d="m16 6-4-4-4 4"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/></svg>
                        </button>
                    </div>
                `;
            }
        }

        return data;
    }

    if (row.status === 'OPEN') {
        const { percentageDifference, badgeType } = calculateOpenPositionDifference(row);

        if (type === 'display') {
            const outcome = percentageDifference < 0
                ? `-${Math.abs(percentageDifference).toFixed(4)}%`
                : `+${percentageDifference.toFixed(4)}%`;

            return `<span class="badge badge-${badgeType} badge-soft badge-xs">${outcome}</span>`;
        }

        return percentageDifference;
    }

    return '';
}

function calculateOpenPositionDifference(row) {
    let totalPrice = parseFloat(row.buy_price ?? 0);
    let totalTrades = 1;

    if (Array.isArray(row.connected) && row.connected.length > 0) {
        row.connected.forEach((trade) => {
            totalPrice += parseFloat(trade.buy_price ?? 0);
            totalTrades += 1;
        });
    }

    const averageEntry = totalPrice / totalTrades;
    const diff = (parseFloat(row.current_price ?? 0) - averageEntry) / averageEntry;
    const percentageDifference = diff * 100;
    const badgeType = percentageDifference < 0 ? 'error' : 'success';

    return { percentageDifference, badgeType };
}

function renderPnlCurrency(type, row) {
    const { profit } = computeTradeMetrics(row);

    if (profit == null) {
        return '--';
    }

    if (type === 'display') {
        return `$${profit.toFixed(2)}`;
    }

    return profit.toFixed(2);
}

function renderStatusBadge(status, type) {
    if (type !== 'display') {
        return status;
    }

    const statusClass = status === 'OPEN' ? 'info' : 'neutral';

    return `<span class="badge badge-${statusClass} badge-soft badge-xs">${status}</span>`;
}

function renderEntryPrice(data, type, row) {
    if (type !== 'display') {
        return data;
    }

    if (row.average_entry) {
        return `$ ${formatPrice(row.token, row.average_entry)}`;
    }

    if (Array.isArray(row.connected) && row.connected.length > 0) {
        const totalBuyPrice = row.connected.reduce(
            (sum, item) => sum + parseFloat(item.buy_price ?? 0),
            0,
        );
        const buyCount = row.connected.length;
        return `$ ${formatPrice(row.token, (parseFloat(data) + totalBuyPrice) / (buyCount + 1))}`;
    }

    return `$ ${formatPrice(row.token, data)}`;
}

function renderEntryAmount(type, row) {
    const { totalBuyUsd, buyFees } = computeTradeMetrics(row);

    if (type !== 'display' || totalBuyUsd == null) {
        return row.buy_usd_amount;
    }

    return `$${(totalBuyUsd - buyFees).toFixed(2)}`;
}

function renderExitAmount(type, row) {
    const { totalSellUsd, sellFees } = computeTradeMetrics(row);

    if (type !== 'display' || totalSellUsd == null) {
        return row.sell_usd_amount;
    }

    return `$${(totalSellUsd - sellFees).toFixed(2)}`;
}

function renderTimestamp(data, type, row) {
    if (type !== 'display' && type !== 'filter') {
        return data;
    }

    const options = {
        year: '2-digit',
        month: 'numeric',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
    };

    const value = row.status === 'CLOSED'
        ? Date.parse(row.closing_timestamp)
        : Date.parse(data);

    if (Number.isNaN(value)) {
        return data;
    }

    return new Intl.DateTimeFormat('en-GB', options).format(value);
}

function computeTradeMetrics(row) {
    const num = (value) => Number.parseFloat(value) || 0;
    const dcas = Array.isArray(row.connected)
        ? row.connected.filter((trade) => trade && (trade.status === 'OPEN' || trade.status === 'CLOSED'))
        : [];

    const all = [row, ...dcas];

    const hasBuyUsd = all.every((trade) => trade.buy_usd_amount != null && trade.buy_usd_amount !== '');
    const hasSellUsd = row.sell_usd_amount != null && row.sell_usd_amount !== '';

    const totalBuyUsd = hasBuyUsd
        ? all.reduce((sum, trade) => sum + num(trade.buy_usd_amount), 0)
        : all.reduce((sum, trade) => sum + (num(trade.buy_price) * num(trade.quantity_bought)), 0);

    const totalSellUsd = hasSellUsd
        ? num(row.sell_usd_amount)
        : num(row.closing_price) * num(row.quantity_sold);

    const buyFees = all.reduce((sum, trade) => sum + num(trade.buy_fee), 0);
    const sellFees = all.reduce((sum, trade) => sum + num(trade.sell_fee), 0);

    let profit;

    if (row.status === 'CLOSED') {
        const exitNet = totalSellUsd - sellFees;
        const entryNet = totalBuyUsd - buyFees;
        profit = exitNet - entryNet;
    } else if (row.status === 'OPEN') {
        const totalBuyQty = all.reduce((sum, trade) => sum + num(trade.quantity_bought), 0);
        const exitNet = (num(row.current_price) * totalBuyQty) - sellFees;
        const entryNet = totalBuyUsd - buyFees;
        profit = exitNet - entryNet;
    } else {
        profit = null;
    }

    return {
        totalBuyUsd,
        totalSellUsd,
        buyFees,
        sellFees,
        profit,
    };
}

function renderChart(history) {
    if (!window.ApexCharts) {
        return;
    }

    const element = document.querySelector(selectors.chart);

    if (!element) {
        return;
    }

    if (state.chart) {
        state.chart.destroy();
        state.chart = null;
    }

    const portfolioData = buildPortfolioSeries(history);

    const options = {
        series: [
            {
                name: 'PNL',
                type: 'area',
                data: portfolioData.map((item) => ({
                    x: new Date(item.date).getTime(),
                    y: Number.parseFloat(item.portfolioValue).toFixed(2),
                })),
            },
        ],
        chart: {
            height: 350,
            type: 'area',
            zoom: {
                enabled: false,
            },
            toolbar: {
                show: false,
            },
            margin: {
                left: 0,
                right: 0,
            },
        },
        colors: ['#DFD444'],
        fill: {
            type: ['gradient'],
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.5,
                opacityTo: 0.1,
                colorStops: [[
                    {
                        offset: 0,
                        color: '#00E0F7',
                        opacity: 1,
                    },
                    {
                        offset: 0.6,
                        color: '#00E0F7',
                        opacity: 0.2,
                    },
                    {
                        offset: 100,
                        color: '#00E0F7',
                        opacity: 0.05,
                    },
                ]],
            },
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            curve: 'straight',
            width: 1,
            colors: ['#DFD444'],
        },
        xaxis: {
            type: 'datetime',
            tickAmount: portfolioData.length > 0 ? portfolioData.length - 1 : 0,
            labels: {
                hideOverlappingLabels: true,
            },
        },
        yaxis: [
            {
                opposite: true,
                labels: {
                    style: {
                        cssClass: 'price',
                    },
                    formatter: (value) => `$${value}`,
                },
            },
        ],
        tooltip: {
            custom: ({ series, seriesIndex, dataPointIndex }) => {
                const value = series[seriesIndex][dataPointIndex];
                const formatted = value > 0 ? `+$${value}` : `$${value}`;
                return `PNL: ${formatted}`;
            },
            marker: {
                show: false,
            },
        },
        markers: {
            colors: ['#DFD444'],
            strokeWidth: 1.5,
            strokeOpacity: 0,
            hover: {
                sizezOffset: 0,
            },
        },
        responsive: [
            {
                breakpoint: 768,
                options: {
                    xaxis: {},
                },
            },
        ],
    };

    state.chart = new window.ApexCharts(element, options);
    state.chart.render();
}

function ensureDatePicker(positionsMapped) {
    const datePickerElement = document.querySelector(selectors.datePicker);

    if (!datePickerElement || !window.easepick) {
        return;
    }

    if (state.datePicker) {
        return;
    }

    const earliest = resolveEarliestDate(positionsMapped);
    const version = new Date().toISOString().replace(/[-:.TZ]/g, '').slice(0, 14);
    const cssFileUrl = `/css/vendor/easepick6.css?v=${version}`;

    state.datePicker = new window.easepick.create({
        element: datePickerElement,
        css: [
            'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.css',
            cssFileUrl,
        ],
        zIndex: 10,
        plugins: ['RangePlugin', 'AmpPlugin', 'LockPlugin', 'PresetPlugin', 'KbdPlugin'],
        format: 'DD/MM/YYYY',
        AmpPlugin: {
            dropdown: {
                months: true,
            },
            darkMode: false,
        },
        LockPlugin: {
            minDate: earliest,
            maxDate: new Date(),
            minDays: 2,
        },
        PresetPlugin: {
            position: window.innerWidth < 400 ? 'bottom' : 'left',
            customPreset: {
                All: [earliest, new Date()],
                '2 Months': [subtractMonths(new Date(), 2), new Date()],
                '1 Month': [subtractMonths(new Date(), 1), new Date()],
                '2 weeks': [subtractDays(new Date(), 14), new Date()],
                '1 Week': [subtractDays(new Date(), 7), new Date()],
                '3 Days': [subtractDays(new Date(), 3), new Date()],
            },
        },
        KbdPlugin: {
            dayIndex: 2,
        },
        setup(picker) {
            picker.on('select', (event) => {
                const startDate = event.detail.start.toLocaleDateString('sv-AX');
                const endDate = event.detail.end.toLocaleDateString('sv-AX');

                updateOnDateRangeChange(startDate, endDate);
            });
        },
    });

    state.datePicker.setDateRange(
        earliest.toLocaleDateString('en-GB'),
        new Date().toLocaleDateString('en-GB'),
    );
}

function updateOnDateRangeChange(startDate, endDate) {
    const dateRange = getDates(new Date(startDate), new Date(endDate));
    const chartData = calculatePnl(state.positions, dateRange, startDate, endDate);
    const userChartData = userHistoryChartData(state.history, dateRange, startDate, endDate);

    const updatedInitialBalance = resolveInitialBalance(userChartData);
    updateBadges(null, chartData, updatedInitialBalance);

    if (state.chart) {
        state.chart.updateOptions({
            series: [
                {
                    data: userChartData.map((item) => ({
                        x: new Date(item.date).getTime(),
                        y: Number.parseFloat(item.portfolioValue).toFixed(2),
                    })),
                },
            ],
            xaxis: {
                type: 'datetime',
                tickAmount: userChartData.length > 0 ? userChartData.length - 1 : 0,
            },
        });
    }
}

function resolveInitialBalance(userChartData) {
    if (!Array.isArray(userChartData) || userChartData.length === 0) {
        return 0;
    }

    if (userChartData[0].portfolioValue === 0 && userChartData.length > 1) {
        return userChartData[1].portfolioValue;
    }

    return userChartData[0].portfolioValue;
}

function buildPortfolioSeries(history) {
    const now = new Date();
    const earliest = history.reduce((acc, entry) => {
        const date = new Date(entry.snapshot_date);
        if (!acc || date < acc) {
            return date;
        }
        return acc;
    }, null);

    const startDate = earliest ?? now;
    const dateRange = getDates(startDate, now);

    return userHistoryChartData(history, dateRange);
}

function resolveEarliestDate(positionsMapped) {
    if (!Array.isArray(positionsMapped) || positionsMapped.length === 0) {
        return new Date();
    }

    const earliest = positionsMapped.reduce((acc, item) => {
        const date = new Date(item.buy_timestamp);
        if (!acc || date < acc) {
            return date;
        }
        return acc;
    }, null);

    const march30 = new Date('2025-03-30');
    if (earliest && earliest < march30) {
        return march30;
    }

    return earliest ?? new Date();
}

function userHistoryChartData(history, dateArray, startDate = null, endDate = null) {
    const filteredDateArray = filteredDates({
        dateArray,
        startDate,
        endDate,
    });
    const portfolioTotals = [];

    if (filteredDateArray.length === 0) {
        return portfolioTotals;
    }

    const earliestDate = new Date(filteredDateArray[0]);
    const oneDayEarlier = new Date(earliestDate);
    oneDayEarlier.setDate(oneDayEarlier.getDate() - 1);

    let previousValue = 0;
    const earlierEntry = history.find(
        (entry) => entry.snapshot_date === oneDayEarlier.toISOString().split('T')[0],
    );

    if (earlierEntry) {
        previousValue = Number.parseFloat(earlierEntry.portfolio_value);
    }

    filteredDateArray.forEach((date, index) => {
        const matchingEntry = history.find((entry) => entry.snapshot_date === date);

        if (matchingEntry) {
            previousValue = Number.parseFloat(matchingEntry.portfolio_value);
        }

        const lastPortfolioValue = index > 0
            ? portfolioTotals[index - 1].portfolioValue
            : 0;
        const dailyProfit = Number.parseFloat(previousValue) - Number.parseFloat(lastPortfolioValue);

        portfolioTotals.push({
            date,
            portfolioValue: previousValue,
            dailyProfit,
        });
    });

    if (portfolioTotals.length === 1) {
        const firstDateInData = new Date(
            Math.min(...history.map((entry) => new Date(entry.snapshot_date).getTime())),
        );
        const firstDateInFiltered = new Date(filteredDateArray[0]);

        if (firstDateInData.getTime() === firstDateInFiltered.getTime()) {
            const zeroDate = new Date(firstDateInFiltered);
            zeroDate.setDate(zeroDate.getDate() - 1);
            portfolioTotals.unshift({
                date: zeroDate.toISOString().split('T')[0],
                portfolioValue: 0,
            });
        }
    }

    return portfolioTotals;
}

function filteredDates({ dateArray, startDate, endDate }) {
    return dateArray.filter((date) => {
        if (!startDate && !endDate) {
            return true;
        }

        const currentDate = new Date(date);
        const start = startDate ? new Date(startDate) : new Date(0);
        const end = endDate ? new Date(endDate) : new Date(8640000000000000);

        return currentDate >= start && currentDate <= end;
    });
}

function calculatePnl(data, dateArray, startDate = null, endDate = null) {
    const filteredData = data.filter((item) => item.dca_parent_id === null);

    filteredDates({
        dateArray,
        startDate,
        endDate,
    });

    const openTrades = data.filter((trade) => trade.status === 'OPEN');
    const closedTrades = filteredData.filter((trade) => trade.status === 'CLOSED');

    const selectedClosedTrades = closedTrades.filter((trade) => {
        const tradeDate = trade.closing_timestamp.split(' ')[0];
        return dateArray.includes(tradeDate);
    });

    const reducedClosedTrades = (trades) => trades.reduce((acc, trade) => {
        const { buyUsdAmount, boughtQuantity, buyFee } = getDcaTradeSums(data, trade);
        const dcaBuyUsdAmount = buyUsdAmount ? Number.parseFloat(buyUsdAmount) : 0;
        const totalBoughtQuantity = Number.parseFloat(trade.quantity_bought) + Number.parseFloat(boughtQuantity);
        const price = trade.average_entry ?? trade.buy_price;

        const buyValue = trade.buy_usd_amount
            ? Number.parseFloat(trade.buy_usd_amount) + Number.parseFloat(dcaBuyUsdAmount)
            : Number.parseFloat(price) * Number.parseFloat(totalBoughtQuantity);

        const sellValue = trade.sell_usd_amount
            ? Number.parseFloat(trade.sell_usd_amount)
            : Number.parseFloat(trade.closing_price) * Number.parseFloat(trade.quantity_sold);

        const tradePnl = Number.parseFloat((sellValue - buyValue).toFixed(2));

        const buyValueFees = trade.buy_fee
            ? Number.parseFloat(trade.buy_fee) + (buyFee ? Number.parseFloat(buyFee) : 0)
            : 0;

        const sellValueFees = trade.sell_fee ? Number.parseFloat(trade.sell_fee) : 0;

        acc.tradePNL += tradePnl;
        acc.buyValue += buyValue;
        acc.sellValue += sellValue;
        acc.sellFees += sellValueFees;
        acc.buyFees += buyValueFees;
        acc.totalMinusFees += tradePnl - (sellValueFees + buyValueFees);
        acc.totalTrades += 1;

        return acc;
    }, {
        tradePNL: 0,
        buyValue: 0,
        sellValue: 0,
        buyFees: 0,
        sellFees: 0,
        totalMinusFees: 0,
        totalTrades: 0,
    });

    const closedTotals = reducedClosedTrades(closedTrades);
    const selectedClosedTotals = reducedClosedTrades(selectedClosedTrades);

    const reducedOpenTrades = openTrades.reduce((acc, trade) => {
        if (trade.dca_parent_id != null) {
            return acc;
        }

        const entries = openTrades.filter(
            (item) => item.id === trade.id || item.dca_parent_id === trade.id,
        );

        entries.forEach((entry) => {
            const buyUsd = entry.buy_usd_amount != null
                ? Number.parseFloat(entry.buy_usd_amount)
                : Number.parseFloat(entry.buy_price) * Number.parseFloat(entry.quantity_bought);
            const sellUsd = entry.sell_usd_amount != null
                ? Number.parseFloat(entry.sell_usd_amount)
                : Number.parseFloat(entry.current_price) * Number.parseFloat(entry.quantity_bought);

            const fee = (entry.buy_fee ? Number.parseFloat(entry.buy_fee) : 0)
                + (entry.sell_fee ? Number.parseFloat(entry.sell_fee) : 0);

            const net = sellUsd - buyUsd - fee;

            acc.totalMinusFees += net;
            acc.buyValue += buyUsd;
            acc.sellValue += sellUsd;
            acc.tradePNL += sellUsd - buyUsd;
            acc.buyFees += entry.buy_fee ? Number.parseFloat(entry.buy_fee) : 0;
            acc.sellFees += entry.sell_fee ? Number.parseFloat(entry.sell_fee) : 0;
            acc.totalTrades += 1;
        });

        return acc;
    }, {
        tradePNL: 0,
        buyValue: 0,
        sellValue: 0,
        totalMinusFees: 0,
        buyFees: 0,
        sellFees: 0,
        totalTrades: 0,
    });

    const {
        tradePNL: closedTradePNL,
        buyValue: closedBuyValue,
        buyFees: closedBuyFees,
        totalMinusFees: closedTotalMinusFees,
        totalTrades: closedTotalTrades,
    } = selectedClosedTotals;

    const {
        tradePNL: openTradePNL,
        buyValue: openBuyValue,
        sellValue: openSellValue,
        buyFees: openBuyFees,
        totalMinusFees: openTotalMinusFees,
        totalTrades: openTotalTrades,
    } = reducedOpenTrades;

    const periodPnlPercentage = (closedTotalMinusFees + openTotalMinusFees) !== 0
        ? (((closedTotalMinusFees + openTotalMinusFees)
            / ((closedBuyValue + closedBuyFees) + (openBuyValue + openBuyFees))) * 100).toFixed(2)
        : 0;

    return {
        openTradesCount: openTotalTrades,
        closedTradesCount: closedTotalTrades,
        portfolioTotals: {
            closedPriceTotals: {
                totalPercentage: (closedBuyValue + closedBuyFees) !== 0
                    ? (closedTotalMinusFees / (closedBuyValue + closedBuyFees)) * 100
                    : 0,
                totalTradePNL: closedTotalMinusFees,
            },
            openPriceTotals: {
                totalPercentage: (openBuyValue + openBuyFees)
                    ? (openTotalMinusFees / (openBuyValue + openBuyFees)) * 100
                    : 0,
                totalTradePNL: openTotalMinusFees,
            },
            totalPNL: {
                totalPeriodPNL: (openTotalMinusFees + closedTotalMinusFees).toFixed(2),
                totalPeriodPNLPercentage: Number.isFinite(Number(periodPnlPercentage))
                    ? periodPnlPercentage
                    : 0,
            },
        },
    };
}

function getDcaTradeSums(data, trade) {
    const dcaTrades = data.filter(
        (item) => item.dca_parent_id === trade.id
            && (item.status === 'CLOSED' || item.status === 'OPEN'),
    );

    return dcaTrades.reduce((acc, item) => {
        if (
            item.buy_fee == null
            || item.sell_fee == null
            || acc.buyFee === null
            || acc.sellFee === null
        ) {
            return {
                usd_buy_amount: null,
                boughtQuantity: acc.boughtQuantity + Number.parseFloat(item.quantity_bought ?? 0),
                buyFee: null,
            };
        }

        return {
            buyUsdAmount: acc.buyUsdAmount + Number.parseFloat(item.buy_usd_amount ?? 0),
            boughtQuantity: acc.boughtQuantity + Number.parseFloat(item.quantity_bought ?? 0),
            buyFee: acc.buyFee + Number.parseFloat(item.buy_fee ?? 0),
        };
    }, {
        buyUsdAmount: 0,
        boughtQuantity: 0,
        buyFee: 0,
    });
}

function updateBadges(positionsMapped, chartDataOverride = null, initialBalanceOverride = null) {
    if (!window.DOMPurify) {
        return;
    }

    const today = new Date();
    const positions = positionsMapped ?? mapPositions(state.positions);
    const earliest = resolveEarliestDate(positions);
    const earliestMinusOne = new Date(earliest);
    earliestMinusOne.setDate(earliestMinusOne.getDate() - 1);

    const dateRange = getDates(earliestMinusOne, today);
    const chartData = chartDataOverride ?? calculatePnl(state.positions, dateRange);

    const {
        portfolioTotals: {
            closedPriceTotals,
            openPriceTotals,
            totalPNL,
        },
        openTradesCount,
        closedTradesCount,
    } = chartData;

    const lifetimeChartData = calculatePnl(state.positions, dateRange);
    const initialBalance = initialBalanceOverride ?? Number.parseFloat(state.snapshot.current_portfolio ?? 0);

    const lifetimeBalance = Number.parseFloat(lifetimeChartData.portfolioTotals.totalPNL.totalPeriodPNL ?? 0);
    const lifetimePercent = initialBalance > 0 ? (lifetimeBalance / initialBalance) * 100 : 0;

    updateElement(dom.lifetimePnl, createBadge(lifetimeBalance, lifetimePercent));
    updateElement(dom.closedTrades, createBadge(
        closedPriceTotals.totalTradePNL,
        closedPriceTotals.totalPercentage,
    ));
    updateElement(dom.closedTradesCount, `Closed Trades: ${closedTradesCount}`);
    updateElement(dom.openTrades, createBadge(
        openPriceTotals.totalTradePNL,
        openPriceTotals.totalPercentage,
    ));
    updateElement(dom.openTradesCount, `Open Trades: ${openTradesCount}`);

    const periodPercent = initialBalance > 0
        ? (Number.parseFloat(totalPNL.totalPeriodPNL) / initialBalance) * 100
        : 0;

    updateElement(dom.periodPnl, createBadge(
        Number.parseFloat(totalPNL.totalPeriodPNL),
        periodPercent,
    ));
    updateElement(dom.totalPnl, createBadge(
        closedPriceTotals.totalTradePNL,
        closedPriceTotals.totalPercentage,
    ));
}

function createBadge(value, percentage) {
    const numericValue = Number.parseFloat(value ?? 0);
    const normalizedPercentage = Number.parseFloat(percentage ?? 0);
    const isPositive = numericValue > 0;
    const isNeutral = numericValue === 0;
    const badgeType = isNeutral ? 'warning' : isPositive ? 'success' : 'error';
    const sign = isPositive ? '+' : isNeutral ? '' : '-';

    const content = `
        <span class="badge badge-${badgeType} badge-soft">
            ${sign}$${Math.abs(numericValue).toFixed(2)}
            (${Math.abs(normalizedPercentage).toFixed(2)}%)
        </span>
    `;

    return window.DOMPurify.sanitize(content);
}

function updateElement(element, content) {
    if (!element) {
        return;
    }

    // DOMPurify returns a string, which is safe to inject
    element.innerHTML = content;
}

function getDates(startDate, endDate) {
    const dates = [];
    let current = normaliseToUtcMidnight(startDate);
    const last = normaliseToUtcMidnight(endDate);

    while (current <= last) {
        dates.push(current.toISOString().split('T')[0]);
        current = new Date(current.getTime());
        current.setUTCDate(current.getUTCDate() + 1);
    }

    return dates;
}

function normaliseToUtcMidnight(value) {
    const source = value instanceof Date ? value : new Date(value);
    return new Date(Date.UTC(
        source.getUTCFullYear(),
        source.getUTCMonth(),
        source.getUTCDate(),
        0,
        0,
        0,
        0,
    ));
}

function handleToggleConnected(tableArrow) {
    const tableElement = tableArrow.closest('table');

    if (!tableElement || !state.dataTable) {
        return;
    }

    const rowId = tableArrow.getAttribute('data-id');

    if (!rowId) {
        return;
    }

    const rowData = state.dataTable.rows().data().toArray().find((item) => `${item.id}` === rowId);

    if (!rowData) {
        return;
    }

    tableArrow.classList.toggle('is-toggled');

    if (tableArrow.classList.contains('is-toggled')) {
        const parentRow = tableArrow.closest('tr');
        if (!parentRow) {
            return;
        }

        removeExistingSubRows(rowId);
        const fragment = document.createDocumentFragment();

        rowData.connected.forEach((connectedRow) => {
            fragment.appendChild(createSubRow(rowId, connectedRow));
        });

        fragment.appendChild(createSubRow(rowId, rowData, true));

        parentRow.parentNode.insertBefore(fragment, parentRow.nextSibling);
    } else {
        removeExistingSubRows(rowId);
    }

    handleMobileChildToggleRows();
}

function removeExistingSubRows(rowId) {
    document.querySelectorAll(`.is-sub[data-parent-id="${rowId}"]`).forEach((row) => {
        row.remove();
    });
}

function createSubRow(rowId, connectedRow, parent = false) {
    const data = connectedRow;
    const tr = document.createElement('tr');
    const options = {
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        second: 'numeric',
    };

    tr.classList.add('is-sub');
    tr.setAttribute('data-parent-id', rowId);
    tr.innerHTML = `
        <td>${data.token}</td>
        <td class="hide-mobile"></td>
        <td class="hide-mobile"></td>
        <td class="hide-mobile">
            <span class="badge badge-${data.status === 'OPEN' ? 'info' : 'neutral'} badge-soft badge-xs">${data.status}</span>
        </td>
        <td class="hide-mobile">${data.id}</td>
        <td class="hide-mobile">${data.timeframe}</td>
        <td class="mobile-timestamp">${new Intl.DateTimeFormat('en-GB', options).format(Date.parse(data.buy_timestamp))}</td>
        <td class="mobile-price text-right">${parent ? '' : '(DCA)'} <span style="white-space: nowrap;">$ ${formatPrice(data.token, data.buy_price)}</span></td>
        <td class="hide-mobile"></td>
        <td class="hide-mobile"></td>
        <td class="hide-mobile"></td>
        <td class="hide-mobile"></td>
    `;

    return tr;
}

function handleMobileChildToggleRows() {
    const table = document.querySelector(selectors.table);

    if (!table) {
        return;
    }

    const headerCells = table.querySelectorAll('th');
    const visibleHeaders = table.querySelectorAll('th:not(.dtr-hidden)');

    document.querySelectorAll('.is-sub .mobile-timestamp').forEach((cell) => {
        cell.setAttribute('colspan', (window.innerWidth >= 992 || headerCells.length === visibleHeaders.length) ? '1' : (visibleHeaders.length > 3 ? '2' : '1'));
    });

    document.querySelectorAll('.is-sub .mobile-price').forEach((cell) => {
        if (window.innerWidth >= 992 || headerCells.length === visibleHeaders.length) {
            cell.setAttribute('colspan', '1');
        } else {
            cell.setAttribute('colspan', String(Math.max(visibleHeaders.length - 2, 1)));
        }
    });
}

function openShareDialog(id) {
    const dialog = document.getElementById('share_pnl_dialog');

    if (!dialog) {
        return;
    }

    const imgUrl = `https://cfgi.io/share/pnl/${id}?mode=file`;
    const img = document.createElement('img');
    img.src = imgUrl;

    if (dom.pnlImageContainer) {
        dom.pnlImageContainer.innerHTML = '';
        dom.pnlImageContainer.appendChild(img);
    }

    if (dom.pnlDownloadButton) {
        dom.pnlDownloadButton.setAttribute('href', `${imgUrl}&download=1`);
    }

    if (typeof dialog.showModal === 'function') {
        dialog.showModal();
    } else {
        dialog.setAttribute('open', 'true');
    }
}

function formatCurrency(value) {
    return `$${Number.parseFloat(value).toFixed(2)}`;
}

function formatPercentage(value) {
    return `${Number.parseFloat(value).toFixed(2)}%`;
}

function formatPrice(symbol, price) {
    const formattedPrice = Number.parseFloat(price);
    const smallCaps = ['SHIB', 'PEPE', 'BONK', 'BTT', 'MOG', 'BOBO', 'DOGE', 'APU'];

    if (smallCaps.includes(symbol)) {
        if (formattedPrice <= 0) {
            return formattedPrice.toFixed(8);
        }

        const decimals = Math.max(2, 14 - Math.floor(Math.log10(formattedPrice)));
        return Number.parseFloat(formattedPrice.toFixed(decimals))
            .toLocaleString(undefined, { minimumFractionDigits: Math.min(decimals, 8) });
    }

    return formattedPrice.toFixed(2).toLocaleString();
}

function subtractMonths(date, months) {
    const result = new Date(date);
    result.setMonth(result.getMonth() - months);
    return result;
}

function subtractDays(date, days) {
    const result = new Date(date);
    result.setDate(result.getDate() - days);
    return result;
}
const scriptPromises = new Map();
