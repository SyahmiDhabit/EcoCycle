<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Circular &amp; Financial Reports &middot; EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .report-section-card {
            background: #ffffff;
            border: 1px solid #d8e5d6;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        .report-metric-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        .report-metric-box {
            background: #fbfdfa;
            border: 1px solid #e2ece0;
            border-radius: 10px;
            padding: 16px;
        }
        .report-metric-box small {
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #556c5e;
            display: block;
            margin-bottom: 4px;
        }
        .report-metric-box span {
            font-size: 24px;
            font-weight: 800;
            font-family: var(--font-display);
            color: #123020;
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
            height: 10px;
            background: #eef4ed;
            border-radius: 5px;
            overflow: hidden;
        }
        .cat-bar-fill {
            height: 100%;
            background: #15803d;
            border-radius: 5px;
        }
        @media print {
            .topbar, .dashboard-sidebar, .btn-print-report {
                display: none !important;
            }
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

        <main class="dashboard-main-content">
            <header class="section-header" style="margin-bottom:20px;">
                <div class="section-header-title">
                    <h2>Financial, ESG &amp; Wastage Audit Report</h2>
                    <p>Verified analytics on trading revenue, material procurement savings, and circular scrap diversion metrics.</p>
                </div>
                <div>
                    <button type="button" class="btn-print-report" onclick="window.print()" style="background:#18422d;color:#fff;border:none;padding:10px 18px;border-radius:8px;font:700 13px var(--font-main);cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                        <span>🖨 Print / Export PDF</span>
                    </button>
                </div>
            </header>

            <!-- 1. Financial Analytics -->
            <div class="report-section-card">
                <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1.5px solid #eef4ed;padding-bottom:12px;">
                    <div>
                        <h3 style="margin:0 0 4px;color:#123020;font-size:18px;">1. Financial Performance &amp; Savings Summary</h3>
                        <p style="margin:0;font-size:12.5px;color:#556c5e;">Revenue generated from surplus materials sold vs expenditure on secondary materials.</p>
                    </div>
                    <span style="background:#f0fdf4;color:#15803d;font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:20px;border:1px solid #bbf7d0;">
                        Fiscal Year 2026
                    </span>
                </div>

                <div class="report-metric-row">
                    <div class="report-metric-box" style="border-left:4px solid #16a34a;">
                        <small>Total Keuntungan / Sales Revenue</small>
                        <span style="color:#15803d;">RM <?= number_format((float) $totalSalesRevenue, 2) ?></span>
                        <div style="font-size:11px;color:#617769;margin-top:4px;"><?= $totalSalesOrders ?> orders fulfilled</div>
                    </div>
                    <div class="report-metric-box" style="border-left:4px solid #dc2626;">
                        <small>Duit Keluar / Purchases Spent</small>
                        <span style="color:#b91c1c;">RM <?= number_format((float) $totalPurchasesSpent, 2) ?></span>
                        <div style="font-size:11px;color:#617769;margin-top:4px;"><?= $totalPurchasesOrders ?> secondary orders bought</div>
                    </div>
                    <div class="report-metric-box" style="border-left:4px solid #2563eb;">
                        <small>Net Profit / Material Savings</small>
                        <span style="color:<?= $netProfitOrSavings >= 0 ? '#15803d' : '#b91c1c' ?>;">
                            RM <?= number_format((float) $netProfitOrSavings, 2) ?>
                        </span>
                        <div style="font-size:11px;color:#617769;margin-top:4px;">Direct bottom-line circular savings</div>
                    </div>
                </div>
            </div>

            <!-- 2. Wastage & Circular Environmental Impact -->
            <div class="report-section-card">
                <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1.5px solid #eef4ed;padding-bottom:12px;">
                    <div>
                        <h3 style="margin:0 0 4px;color:#123020;font-size:18px;">2. Circular Materials &amp; Landfill Diversion Metrics</h3>
                        <p style="margin:0;font-size:12.5px;color:#556c5e;">Classification of reusable materials vs non-reusable industrial scrap recovered.</p>
                    </div>
                    <span style="background:#eff6ff;color:#1d4ed8;font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:20px;border:1px solid #bfdbfe;">
                        Circularity Rate: <?= $circularityRate ?>%
                    </span>
                </div>

                <div class="report-metric-row">
                    <div class="report-metric-box" style="border-left:4px solid #16a34a;">
                        <small>Total Materials Diverted</small>
                        <span style="color:#15803d;"><?= number_format((float) $totalDivertedFromLandfillKg, 1) ?> <small style="font-size:13px;">kg</small></span>
                        <div style="font-size:11px;color:#617769;margin-top:4px;">Diverted from Malaysian municipal landfills</div>
                    </div>
                    <div class="report-metric-box" style="border-left:4px solid #2563eb;">
                        <small>Reusable Inventory Circulated</small>
                        <span style="color:#1d4ed8;"><?= number_format((float) $reusableStockTotal, 1) ?> <small style="font-size:13px;">units/kg</small></span>
                        <div style="font-size:11px;color:#617769;margin-top:4px;">Kept in high-value circular reuse</div>
                    </div>
                    <div class="report-metric-box" style="border-left:4px solid #eab308;">
                        <small>Not-Reusable Scrap Recycled</small>
                        <span style="color:#a16207;"><?= number_format((float) $scrapDivertedKg, 1) ?> <small style="font-size:13px;">kg</small></span>
                        <div style="font-size:11px;color:#617769;margin-top:4px;">Transferred to licensed recyclers</div>
                    </div>
                    <div class="report-metric-box" style="border-left:4px solid #059669;">
                        <small>CO₂ Emissions Avoided</small>
                        <span style="color:#059669;"><?= number_format((float) $estimatedCo2AvoidedKg, 1) ?> <small style="font-size:13px;">kg CO₂e</small></span>
                        <div style="font-size:11px;color:#617769;margin-top:4px;">Estimated avoided virgin material production</div>
                    </div>
                </div>

                <!-- Scrap Category Distribution -->
                <div style="margin-top:24px;">
                    <h4 style="margin:0 0 12px;font-size:14px;color:#18422d;">Non-Reusable Scrap Generation by Category</h4>
                    <?php if (!empty($categoryBreakdown)): ?>
                        <?php 
                            $maxKg = max($categoryBreakdown) ?: 1;
                            foreach ($categoryBreakdown as $cName => $cKg): 
                                $pct = round(($cKg / $maxKg) * 100);
                        ?>
                            <div class="cat-bar-item">
                                <div class="cat-bar-header">
                                    <span><?= e($cName) ?></span>
                                    <span><?= number_format((float) $cKg, 1) ?> kg</span>
                                </div>
                                <div class="cat-bar-track">
                                    <div class="cat-bar-fill" style="width:<?= $pct ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="font-size:12.5px;color:#617769;margin:0;">No scrap records recorded yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 3. Recent Audit Logs -->
            <div class="report-section-card">
                <h3 style="margin:0 0 14px;color:#123020;font-size:18px;">3. Recent Verified Activity Logs</h3>
                
                <h4 style="margin:0 0 8px;font-size:13px;color:#18422d;">Recent Commercial Invoices &amp; Settlements</h4>
                <?php if ($recentTransactions->isNotEmpty()): ?>
                    <table class="inventory-table" style="width:100%;border-collapse:collapse;margin-bottom:20px;font-size:12.5px;">
                        <thead>
                            <tr style="background:#f4f8f3;border-bottom:1px solid #d8e5d6;text-align:left;">
                                <th style="padding:10px 14px;">Invoice #</th>
                                <th style="padding:10px 14px;">Material</th>
                                <th style="padding:10px 14px;">Counterparty</th>
                                <th style="padding:10px 14px;">Amount</th>
                                <th style="padding:10px 14px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransactions as $tx): ?>
                                <tr style="border-bottom:1px solid #eef4ed;">
                                    <td style="padding:10px 14px;font-family:monospace;"><?= e($tx->invoice_number ?: ('INV-' . $tx->id)) ?></td>
                                    <td style="padding:10px 14px;"><?= e($tx->listing?->title ?: 'Material') ?></td>
                                    <td style="padding:10px 14px;"><?= e($tx->requester?->business_name ?: ($tx->requester?->name ?? 'Partner')) ?></td>
                                    <td style="padding:10px 14px;font-weight:700;">RM <?= number_format((float)$tx->total_price, 2) ?></td>
                                    <td style="padding:10px 14px;"><?= ucfirst($tx->payment_status ?: 'pending') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="font-size:12.5px;color:#617769;margin-bottom:20px;">No transaction logs yet.</p>
                <?php endif; ?>

                <h4 style="margin:0 0 8px;font-size:13px;color:#18422d;">Recent Scrap Destination Dispatches</h4>
                <?php if ($recentWasteLogs->isNotEmpty()): ?>
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
                                    <td style="padding:10px 14px;"><?= ucfirst(str_replace('_', ' ', $wl->status)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="font-size:12.5px;color:#617769;margin:0;">No scrap logs yet.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
