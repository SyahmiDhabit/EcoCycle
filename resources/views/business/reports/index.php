<!DOCTYPE html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Circular &amp; Financial Reports &middot; EcoCycle</title>
    <link rel="icon" type="image/svg+xml" href="/images/figma/ecocycle-leaf.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .report-section-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 0;
            padding: 24px;
            margin-bottom: 24px;
        }
        .report-metric-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        @media (min-width: 1000px) {
            .report-metric-row {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
        .report-metric-box {
            background: var(--surface-subtle);
            border: 1px solid var(--card-border);
            border-radius: 0;
            padding: 16px;
        }
        .report-metric-box small {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-secondary);
            display: block;
            margin-bottom: 4px;
        }
        .report-metric-box span {
            font-size: 24px;
            font-weight: 800;
            font-family: var(--font-display);
            color: var(--text-primary);
        }
        .cat-bar-item {
            margin-bottom: 12px;
        }
        .cat-bar-header {
            display: flex;
            justify-content: space-between;
            font-size: 12.5px;
            font-weight: 600;
            color: #2e4436;
            margin-bottom: 4px;
        }
        .cat-bar-track {
            height: 8px;
            background: #eef4ed;
            border-radius: 0;
            overflow: hidden;
        }
        .cat-bar-fill {
            height: 100%;
            background: #176b40;
            border-radius: 0;
        }
        .reports-workspace {
            padding-inline: max(clamp(20px, 4vw, 48px), calc((100% - 1180px) / 2));
        }
        .reports-workspace .page-header {
            align-items: center;
            flex-wrap: wrap;
            padding: 0;
            border: 0;
        }
        .reports-workspace .page-header h1 {
            color: #14532d;
            font-size: clamp(26px, 3vw, 40px);
        }
        .reports-workspace .btn-print-report {
            background: #176b40;
            border-color: #176b40;
            min-height: 40px;
            border-radius: 6px !important;
        }
        .reports-workspace .btn-print-report:focus-visible {
            outline: 2px solid #176b40;
            outline-offset: 2px;
        }
        .report-section-card h2 {
            font: 700 18px var(--font-display);
            color: #14532d;
            margin: 0 0 12px;
        }
        .report-metric-box p { margin: 4px 0 0; color: var(--text-secondary); }
        .report-chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 265px), 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .report-chart-grid > section { margin: 0; }
        .report-rating-columns { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 6px; margin: 0; padding: 0; list-style: none; }
        .report-rating-columns li { display: grid; grid-template-rows: auto 76px auto; justify-items: center; gap: 4px; font-size: 12px; }
        .report-rating-column { width: min(22px, 100%); align-self: end; background: #176b40; }
        .report-bars { list-style: none; margin: 0; padding: 0; }
        .cat-bar-header { gap: 12px; }
        .cat-bar-header span:last-child { white-space: nowrap; }
        .report-table-scroll { overflow-x: auto; }
        .report-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 12px;
        }
        .report-filters label { display: grid; gap: 5px; font-weight: 600; }
        .report-filters label[hidden] { display: none; }
        .report-filters :is(select, input) {
            min-height: 40px;
            padding: 6px 10px;
            border: 1px solid var(--input-border);
            background: #fff;
            color: var(--text-primary);
            font: inherit;
        }
        .reports-workspace .report-action {
            background: #176b40;
            border-color: #176b40;
            min-height: 40px;
            border-radius: 6px !important;
        }
        .report-filters p { flex-basis: 100%; margin: 0; }
        .report-filter-error { color: #991b1b; }
        .analytics-chart-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .report-print-heading { display: none; }
        .report-period-range { color: #475569; font-size: 13px; }
        .report-financial-trend { grid-column: 1 / -1; }
        .report-chart-description, .report-chart-empty { color: var(--text-secondary); }
        .report-canvas { display: none; height: 270px; position: relative; }
        .report-canvas.is-rendered { display: block; }
        .report-side-legend-chart:has(.is-rendered) {
            display: grid;
            grid-template-columns: minmax(0, 1fr) fit-content(45%);
            column-gap: 18px;
            align-content: start;
        }
        .report-side-legend-chart > :is(h2, p) { grid-column: 1 / -1; }
        .report-side-legend {
            list-style: none;
            padding: 0;
            margin: 12px 0;
            align-self: center;
            display: grid;
            gap: 10px;
        }
        .report-side-legend li {
            display: grid;
            grid-template-columns: 10px 1fr auto;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        .report-side-legend li span { overflow-wrap: anywhere; }
        .report-side-legend i {
            width: 10px;
            height: 10px;
            border-radius: 50% !important;
            background: var(--legend-color);
        }
        @media (max-width: 480px) {
            .report-side-legend-chart:has(.is-rendered) { grid-template-columns: minmax(0, 1fr); }
            .report-side-legend { justify-self: end; }
        }
        .report-chart-data { margin-top: 12px; }
        .report-chart-data summary { cursor: pointer; color: #14532d; }
        .report-chart-data dl { display: grid; grid-template-columns: 1fr auto; gap: 5px 12px; }
        .report-chart-data dd { margin: 0; text-align: right; }
        @media (max-width: 720px) {
            .analytics-chart-grid { grid-template-columns: minmax(0, 1fr); }
            .report-section-card { padding: 16px; }
        }
        @page { size: A4 portrait; margin: 14mm; }
        @media print {
            body.business-dashboard-page { background: #fff; }
            .topbar, .dashboard-sidebar, .sidebar-backdrop, .btn-print-report, .report-filters { display: none !important; }
            .report-print-heading { display: block; color: #14532d; font-weight: 700; }
            .reports-workspace .page-header { border-bottom: 1px solid #cbd5e1; margin: 0 0 12px; padding-bottom: 8px; }
            .reports-workspace .page-header h1 { font-size: 23pt; }
            .dashboard-layout { display: block !important; }
            .dashboard-main-content.reports-workspace { width: 100%; max-width: none; margin: 0; padding: 0 !important; }
            .report-section-card { padding: 12px; margin-bottom: 12px; }
            .report-chart-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px; }
            .report-chart-grid > section, .report-metric-box { break-inside: avoid; }
            .report-metric-row { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }
            .report-metric-box { padding: 8px; }
            .report-metric-box span { font-size: 15pt; }
            .report-canvas { height: 175px; }
            .report-table-scroll { overflow: visible; }
            .inventory-table thead { display: table-header-group; }
            .inventory-table tr { break-inside: avoid; }
            .inventory-table td, .inventory-table th { overflow-wrap: anywhere; }
            .report-section-card { print-color-adjust: exact; }
            .report-section-card h2, .report-section-card h4 { break-after: avoid; }
        }
    </style>
    <link rel="stylesheet" href="/css/site-preferences.css?v=<?= filemtime(public_path('css/site-preferences.css')) ?>">
    <script src="/js/site-theme.js?v=<?= filemtime(public_path('js/site-theme.js')) ?>"></script>
    <script src="/js/language-switcher.js?v=<?= filemtime(public_path('js/language-switcher.js')) ?>" defer></script>
</head>
<body class="business-dashboard-page">
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <?php 
            $activeNav = 'reports';
            include resource_path('views/business/partials/sidebar.php'); 
        ?>

        <main class="dashboard-main-content reports-workspace">
            <header class="page-header">
                <div>
                    <div class="report-print-heading">EcoCycle · <?= e($business->business_name ?: $business->name) ?></div>
                    <h1>Business Analytics</h1>
                    <p>Inventory, Marketplace performance, and waste recovery for your business.</p>
                    <p class="report-period-range">Reporting period: <?= e($start->format('d M Y')) ?> – <?= e($end->format('d M Y')) ?></p>
                </div>
                <button type="button" class="btn-search btn-print-report" onclick="window.print()">Print / Save as PDF</button>
            </header>

            <form class="report-section-card report-filters" method="get">
                <label>Period
                    <select id="report-period" name="period">
                        <?php foreach (['day' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'quarter' => 'Last 3 Months', 'year' => 'This Year', 'custom' => 'Custom Date Range'] as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $period === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label data-custom-date>From <input type="date" name="from" value="<?= $start->format('Y-m-d') ?>"></label>
                <label data-custom-date>To <input type="date" name="to" value="<?= $end->format('Y-m-d') ?>"></label>
                <button type="submit" class="btn-search report-action">Apply filters</button>
                <?php if ($filterErrors->any()): ?>
                    <p class="report-filter-error" role="alert"><?= e($filterErrors->first()) ?> Showing this week.</p>
                <?php endif; ?>
            </form>

            <section class="report-section-card" aria-labelledby="summary-heading">
                <h2 id="summary-heading">Dashboard summary</h2>
                <div class="report-metric-row">
                    <?php foreach ([
                        ['Total Items', number_format($totalItems), 'Inventory items added in this period'],
                        ['Total Transactions', number_format($totalTransactions), 'Orders placed in this period as buyer or seller'],
                        ['Total Revenue', 'RM '.number_format($totalSalesRevenue, 2), 'Paid sales in this period'],
                        ['Total Purchases', 'RM '.number_format($totalPurchasesSpent, 2), 'Spending on paid purchases in this period'],
                    ] as [$label, $value, $note]): ?>
                        <div class="report-metric-box">
                            <small><?= e($label) ?></small>
                            <span><?= e($value) ?></span>
                            <p><?= e($note) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <?php
                $analyticsCharts = [
                    ['financial-trend', 'Sales and purchases over time', 'Paid sales and purchase spending grouped by '.$trendInterval.'.', 'line', ['Sales revenue' => $salesTrend, 'Purchase spending' => $purchaseTrend], 'RM'],
                    ['listing-categories', 'Item listings by category', 'Marketplace listings from items added in this period.', 'bar', $listingCategories, 'Listings'],
                    ['material-reusability', 'Material reusability', 'Reusable and non-reusable inventory items added in this period.', 'doughnut', $reusability, 'Items'],
                ];
            ?>
            <div class="report-chart-grid analytics-chart-grid">
                <?php foreach ($analyticsCharts as [$chartId, $chartTitle, $description, $type, $series, $metric]): ?>
                    <?php
                        $chartColors = match ($chartId) {
                            'material-reusability' => ['Reusable' => '#14936f', 'Non-reusable' => '#a05bc4'],
                            'listing-categories' => array_combine(array_keys($series), array_map(
                                static fn (int $index): string => sprintf('hsl(%d, 60%%, 43%%)', (158 + 137 * $index) % 360),
                                array_keys(array_values($series)),
                            )),
                            default => [],
                        };
                    ?>
                    <section class="report-section-card<?= $type === 'line' ? ' report-financial-trend' : '' ?><?= $chartColors ? ' report-side-legend-chart' : '' ?>" aria-labelledby="<?= $chartId ?>">
                        <h2 id="<?= $chartId ?>"><?= e($chartTitle) ?></h2>
                        <p class="report-chart-description"><?= e($description) ?></p>
                        <?php if ($type === 'line' ? array_sum($salesTrend) + array_sum($purchaseTrend) > 0 : array_sum($series) > 0): ?>
                            <div class="report-canvas">
                                <canvas aria-hidden="true" data-chart-type="<?= $type ?>" data-series="<?= e(json_encode($series, JSON_INVALID_UTF8_SUBSTITUTE)) ?>" data-metric="<?= e($metric) ?>" data-colors="<?= e(json_encode($chartColors)) ?>"></canvas>
                            </div>
                            <?php if ($type === 'line'): ?>
                                <details class="report-chart-data" open>
                                    <summary>Chart data</summary>
                                    <div class="report-table-scroll">
                                        <table class="inventory-table">
                                            <thead><tr><th>Period</th><th>Sales revenue</th><th>Purchase spending</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($salesTrend as $label => $value): ?>
                                                    <tr><td><?= e($label) ?></td><td>RM <?= number_format($value, 2) ?></td><td>RM <?= number_format($purchaseTrend[$label], 2) ?></td></tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            <?php elseif ($chartColors): ?>
                                <ul class="report-side-legend" aria-label="<?= e($chartTitle) ?> counts">
                                    <?php foreach ($series as $label => $value): ?>
                                        <li>
                                            <i aria-hidden="true" style="--legend-color:<?= e($chartColors[$label]) ?>"></i>
                                            <span><?= e($label) ?></span>
                                            <strong><?= number_format($value) ?></strong>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                            <details class="report-chart-data" open>
                                <summary>Chart data</summary>
                                <dl>
                                    <?php foreach ($series as $label => $value): ?>
                                        <dt><?= e($label) ?></dt>
                                        <dd><?= $metric === 'RM' ? 'RM '.number_format($value, 2) : number_format($value) ?></dd>
                                    <?php endforeach; ?>
                                </dl>
                            </details>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="report-chart-empty">No records for this period.</p>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>

            <div class="report-chart-grid">
                <?php foreach ([
                    ['payments-chart', 'Paid sales vs purchases', ['Sales' => $totalSalesRevenue, 'Purchases' => $totalPurchasesSpent], true],
                    ['seller-ratings-chart', 'Buyer ratings received', [], false],
                    ['waste-status-chart', 'Waste records by status', $wasteStatuses, false],
                ] as [$chartId, $chartTitle, $series, $isMoney]): ?>
                    <section class="report-section-card" aria-labelledby="<?= $chartId ?>">
                        <h2 id="<?= $chartId ?>"><?= e($chartTitle) ?></h2>
                        <?php if ($chartId === 'seller-ratings-chart'): ?>
                            <?php if ($receivedRatings->isEmpty()): ?>
                                <p>No buyer reviews in this period.</p>
                            <?php else: ?>
                                <p class="report-chart-description">Number of reviews at each star rating.</p>
                                <ul class="report-rating-columns" aria-label="Review counts by star rating">
                                    <?php for ($stars = 5; $stars >= 0; $stars--): ?>
                                        <?php $count = $receivedRatings->get($stars, 0); ?>
                                        <li>
                                            <strong><?= $count ?></strong>
                                            <span class="report-rating-column" aria-hidden="true" style="height:<?= 100 * $count / $receivedRatings->max() ?>%;background:<?= ['#176b40', '#0e7490', '#356e98', '#674fa0', '#a55c22', '#9a4b63'][5 - $stars] ?>;"></span>
                                            <span><?= $stars ?><span style="color:#b77900;">★</span></span>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            <?php endif; ?>
                        <?php elseif (max($series ?: [0]) > 0): ?>
                            <?php $maximum = max($series); ?>
                            <ul class="report-bars">
                                <?php foreach ($series as $label => $amount): ?>
                                    <li class="cat-bar-item">
                                        <div class="cat-bar-header">
                                            <span><?= e($label) ?></span>
                                            <span><?= $isMoney ? 'RM '.number_format($amount, 2) : number_format($amount) ?></span>
                                        </div>
                                        <div class="cat-bar-track" aria-hidden="true">
                                            <div class="cat-bar-fill" style="background:<?= $chartId === 'waste-status-chart' ? '#674fa0' : ($label === 'Purchases' ? '#356e98' : '#176b40') ?>;width:<?= round($amount / $maximum * 100, 2) ?>%;"></div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p><?= $isMoney ? 'No paid transactions yet.' : 'No waste records yet.' ?></p>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>

            <div class="report-section-card">
                <h2>Recent activity</h2>
                
                <h4 style="margin:0 0 8px;font-size:13px;color:#18422d;">Marketplace transactions</h4>
                <p class="report-chart-description">Up to eight recent orders in each role, based on the order date.</p>
                <?php if ($recentTransactions->isNotEmpty()): ?>
                    <div class="report-table-scroll">
                        <table class="inventory-table" style="width:100%;border-collapse:collapse;margin-bottom:20px;font-size:12.5px;">
                        <thead>
                            <tr style="background:#f4f8f3;border-bottom:1px solid #d8e5d6;text-align:left;">
                                <th style="padding:10px 14px;">Order reference</th>
                                <th style="padding:10px 14px;">Material</th>
                                <th style="padding:10px 14px;">Your role</th>
                                <th style="padding:10px 14px;">Counterparty</th>
                                <th style="padding:10px 14px;">Amount</th>
                                <th style="padding:10px 14px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransactions as $tx): ?>
                                <?php $isSeller = $tx->provider_business_id === $business->id; ?>
                                <tr style="border-bottom:1px solid #eef4ed;">
                                    <td style="padding:10px 14px;font-family:monospace;"><?= e($tx->resolvedOrderReference()) ?></td>
                                    <td style="padding:10px 14px;"><?= e($tx->item_name ?: ($tx->listing?->title ?: 'Material')) ?></td>
                                    <td style="padding:10px 14px;"><?= $isSeller ? 'Seller' : 'Buyer' ?></td>
                                    <td style="padding:10px 14px;"><?= e(($isSeller ? $tx->requester : $tx->provider)?->business_name ?: 'Partner') ?></td>
                                    <td style="padding:10px 14px;font-weight:700;">RM <?= number_format((float)$tx->total_price, 2) ?></td>
                                    <td style="padding:10px 14px;"><?= e($tx->paymentStatusLabel()) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php else: ?>
                    <p style="font-size:12.5px;color:#617769;margin-bottom:20px;">No transaction logs yet.</p>
                <?php endif; ?>

                <h4 style="margin:0 0 8px;font-size:13px;color:#18422d;">Waste records</h4>
                <?php if ($recentWasteLogs->isNotEmpty()): ?>
                    <div class="report-table-scroll">
                        <table class="inventory-table" style="width:100%;border-collapse:collapse;font-size:12.5px;">
                        <thead>
                            <tr style="background:#f4f8f3;border-bottom:1px solid #d8e5d6;text-align:left;">
                                <th style="padding:10px 14px;">Material</th>
                                <th style="padding:10px 14px;">Category</th>
                                <th style="padding:10px 14px;">Quantity</th>
                                <th style="padding:10px 14px;">Destination Facility</th>
                                <th style="padding:10px 14px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentWasteLogs as $wl): ?>
                                <tr style="border-bottom:1px solid #eef4ed;">
                                    <td style="padding:10px 14px;"><strong><?= e($wl->material_name) ?></strong></td>
                                    <td style="padding:10px 14px;color:#556c5e;"><?= e($wl->waste_type) ?></td>
                                    <td style="padding:10px 14px;font-weight:700;"><?= (float)$wl->quantity ?> <?= e($wl->unit) ?></td>
                                    <td style="padding:10px 14px;"><?= e($wl->facility_name ?: 'Internal') ?></td>
                                    <td style="padding:10px 14px;"><?= e(ucfirst(str_replace('_', ' ', (string) $wl->status))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php else: ?>
                    <p style="font-size:12.5px;color:#617769;margin:0;">No scrap logs yet.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.js"></script>
    <script>
    const periodSelect = document.getElementById('report-period');
    function updateCustomDates() {
        const custom = periodSelect.value === 'custom';
        for (const label of document.querySelectorAll('[data-custom-date]')) {
            label.hidden = !custom;
            const input = label.querySelector('input');
            input.disabled = !custom;
            input.required = custom;
        }
    }
    periodSelect.addEventListener('change', updateCustomDates);
    updateCustomDates();

    if (window.Chart) {
        const palette = ['#176b40', '#356e98', '#a55c22', '#674fa0', '#348681', '#9a4b63', '#62734e', '#806846'];
        for (const canvas of document.querySelectorAll('canvas[data-series]')) {
            const type = canvas.dataset.chartType;
            const series = JSON.parse(canvas.dataset.series);
            const isTrend = type === 'line';
            const labels = Object.keys(isTrend ? Object.values(series)[0] : series);
            const values = Object.values(series);
            const colors = JSON.parse(canvas.dataset.colors);
            const hasSideLegend = Object.keys(colors).length > 0;
            const isBar = type === 'bar';
            const isCurrency = canvas.dataset.metric === 'RM';
            const formatValue = value => isCurrency
                ? new Intl.NumberFormat('en-MY', { style: 'currency', currency: 'MYR' }).format(value)
                : value;
            const datasets = isTrend ? Object.entries(series).map(([label, points], index) => ({
                label,
                data: Object.values(points),
                borderColor: palette[index],
                backgroundColor: palette[index],
                borderDash: index === 1 ? [6, 3] : [],
                pointStyle: index === 1 ? 'rect' : 'circle',
                pointRadius: labels.length > 10 ? 1 : 3,
                pointHoverRadius: 5
            })) : [{
                label: canvas.dataset.metric,
                data: values,
                backgroundColor: labels.map((label, index) => colors[label] ?? palette[index % palette.length])
            }];
            const chart = new Chart(canvas, {
                type,
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    indexAxis: isBar ? 'y' : 'x',
                    interaction: isTrend ? { mode: 'index', intersect: false } : undefined,
                    scales: isTrend ? {
                        x: { ticks: { maxTicksLimit: 7 }, grid: { display: false } },
                        y: { beginAtZero: true, ticks: isCurrency ? { callback: formatValue } : { precision: 0 } }
                    } : isBar ? { x: { beginAtZero: true, ticks: { precision: 0 } } } : undefined,
                    plugins: {
                        legend: { display: !hasSideLegend, position: 'bottom' },
                        tooltip: isTrend ? { callbacks: { label: context => context.dataset.label + ' · ' + formatValue(context.parsed.y) } } : undefined
                    }
                }
            });
            canvas.parentElement.classList.add('is-rendered');
            const details = canvas.parentElement.nextElementSibling;
            if (details instanceof HTMLDetailsElement) details.open = false;
            window.addEventListener('beforeprint', () => chart.resize());
            window.addEventListener('afterprint', () => chart.resize());
        }
    }
    </script>
</body>
</html>
