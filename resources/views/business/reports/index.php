<!DOCTYPE html>
<html lang="en">
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
        .report-bars { list-style: none; margin: 0; padding: 0; }
        .cat-bar-header { gap: 12px; }
        .cat-bar-header span:last-child { white-space: nowrap; }
        .report-table-scroll { overflow-x: auto; }
        @media print {
            .topbar, .dashboard-sidebar, .sidebar-backdrop, .btn-print-report {
                display: none !important;
            }
            .report-chart-grid { display: block; }
            .report-chart-grid > section { margin-bottom: 12px; break-inside: avoid; }
            .cat-bar-fill { print-color-adjust: exact; }
            .dashboard-layout {
                display: block !important;
            }
            .dashboard-main-content {
                padding: 0 !important;
            }
        }
    </style>
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
                    <h1>Business Reports</h1>
                    <p>All-time Marketplace payments and waste records for your business.</p>
                </div>
                <button type="button" class="btn-search btn-print-report" onclick="window.print()">Print / Save as PDF</button>
            </header>

            <section class="report-section-card" aria-labelledby="financial-heading">
                <h2 id="financial-heading">Marketplace payments</h2>
                <div class="report-metric-row">
                    <div class="report-metric-box">
                        <small>Total Sales</small>
                        <span>RM <?= number_format($totalSalesRevenue, 2) ?></span>
                        <p><?= $totalSalesOrders ?> paid <?= $totalSalesOrders === 1 ? 'sale' : 'sales' ?></p>
                    </div>
                    <div class="report-metric-box">
                        <small>Total Purchases</small>
                        <span>RM <?= number_format($totalPurchasesSpent, 2) ?></span>
                        <p><?= $totalPurchasesOrders ?> paid <?= $totalPurchasesOrders === 1 ? 'purchase' : 'purchases' ?></p>
                    </div>
                    <div class="report-metric-box">
                        <small>Sales less purchases</small>
                        <span>RM <?= number_format($totalSalesRevenue - $totalPurchasesSpent, 2) ?></span>
                    </div>
                </div>
            </section>

            <div class="report-chart-grid">
                <?php foreach ([
                    ['payments-chart', 'Paid sales vs purchases', ['Sales' => $totalSalesRevenue, 'Purchases' => $totalPurchasesSpent], true],
                    ['waste-category-chart', 'Waste records by category', $wasteCategories, false],
                    ['waste-status-chart', 'Waste records by status', $wasteStatuses, false],
                ] as [$chartId, $chartTitle, $series, $isMoney]): ?>
                    <section class="report-section-card" aria-labelledby="<?= $chartId ?>">
                        <h2 id="<?= $chartId ?>"><?= e($chartTitle) ?></h2>
                        <?php if (max($series ?: [0]) > 0): ?>
                            <?php $maximum = max($series); ?>
                            <ul class="report-bars">
                                <?php foreach ($series as $label => $amount): ?>
                                    <li class="cat-bar-item">
                                        <div class="cat-bar-header">
                                            <span><?= e($label) ?></span>
                                            <span><?= $isMoney ? 'RM '.number_format($amount, 2) : number_format($amount) ?></span>
                                        </div>
                                        <div class="cat-bar-track" aria-hidden="true">
                                            <div class="cat-bar-fill" style="width:<?= round($amount / $maximum * 100, 2) ?>%;"></div>
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
                <?php if ($recentTransactions->isNotEmpty()): ?>
                    <div class="report-table-scroll">
<table class="inventory-table" style="width:100%;border-collapse:collapse;margin-bottom:20px;font-size:12.5px;">
                        <thead>
                            <tr style="background:#f4f8f3;border-bottom:1px solid #d8e5d6;text-align:left;">
                                <th style="padding:10px 14px;">Order reference</th>
                                <th style="padding:10px 14px;">Material</th>
                                <th style="padding:10px 14px;">Counterparty</th>
                                <th style="padding:10px 14px;">Amount</th>
                                <th style="padding:10px 14px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransactions as $tx): ?>
                                <tr style="border-bottom:1px solid #eef4ed;">
                                    <td style="padding:10px 14px;font-family:monospace;"><?= e($tx->resolvedOrderReference()) ?></td>
                                    <td style="padding:10px 14px;"><?= e($tx->listing?->title ?: 'Material') ?></td>
                                    <td style="padding:10px 14px;"><?= e(($tx->provider_business_id === $business->id ? $tx->requester : $tx->provider)?->business_name ?: 'Partner') ?></td>
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
</body>
</html>
