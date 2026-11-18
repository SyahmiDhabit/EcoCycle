<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions &amp; Invoicing &middot; EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .trx-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .trx-kpi-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 0;
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .trx-kpi-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .trx-kpi-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-primary);
            font-family: var(--font-display);
        }
        .trx-kpi-sub {
            font-size: 11.5px;
            color: var(--text-muted);
        }
        .trx-tabs {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--card-border);
            padding-bottom: 10px;
        }
        .trx-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border: 1px solid var(--card-border);
            border-radius: 0;
            background: #ffffff;
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 700;
            color: #3f5546;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }
        .trx-tab-btn:hover {
            background: #eef6ec;
            border-color: var(--brand-green);
        }
        .trx-tab-btn.active {
            background: var(--forest-bg);
            border-color: var(--forest-bg);
            color: #ffffff;
        }
        .pay-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 0;
            font-size: 11px;
            font-weight: 700;
        }
        .pay-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .pay-requested { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .pay-paid { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .pay-free { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .pay-cancelled { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .pay-refunded { background: #f3e8ff; color: #6b21a8; border: 1px solid #d8b4fe; }
        .btn-action-sm {
            padding: 5px 10px;
            border-radius: 0;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
    </style>
</head>
<body class="business-dashboard-page">
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <?php
            $activeNav = match ($currentTab) {
                'purchases' => 'purchases',
                'sales' => 'sales',
                default => 'transactions',
            };
            include resource_path('views/business/partials/sidebar.php');
        ?>

        <main class="dashboard-main-content">
            <header class="section-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                <div class="section-header-title">
                    <h2><?= match ($currentTab) {
                        'purchases' => 'My Purchases &amp; Claims',
                        'sales' => 'My Sales History',
                        default => 'Transactions &amp; Payment History',
                    } ?></h2>
                    <p>Preserved purchase and sales history for Sell, Free, and Lend transactions, including Stripe payment status and PDF receipts.</p>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="<?= route('business.orders.index') ?>" class="trx-tab-btn">
                        My Orders &amp; Delivery
                    </a>
                    <a href="<?= route('business.invoices.index') ?>" class="trx-tab-btn active">
                        Receipts
                    </a>
                </div>
            </header>

            <?php if (session('status')): ?>
                <div style="background:#eef6ed;border:1px solid #b9dec0;border-left:3px solid #176b40;color:#143825;padding:12px 18px;border-radius:0;font-size:13px;margin-bottom:20px;font-weight:600;">
                    <?= session('status') ?>
                </div>
            <?php endif; ?>

            <!-- KPI Metric Cards -->
            <div class="trx-kpi-grid">
                <div class="trx-kpi-card" style="border-left:3px solid #176b40;">
                    <span class="trx-kpi-title" style="color:#15803d;">Total Revenue (Hasil Jualan)</span>
                    <span class="trx-kpi-value" style="color:#15803d;">RM <?= number_format((float) $totalRevenueRm, 2) ?></span>
                    <span class="trx-kpi-sub">Total received from material sales &amp; deposits</span>
                </div>
                <div class="trx-kpi-card" style="border-left:3px solid #dc2626;">
                    <span class="trx-kpi-title" style="color:#b91c1c;">Total Purchases (Duit Keluar)</span>
                    <span class="trx-kpi-value" style="color:#b91c1c;">RM <?= number_format((float) $totalPurchasesRm, 2) ?></span>
                    <span class="trx-kpi-sub">Total spent acquiring secondary surplus materials</span>
                </div>
                <div class="trx-kpi-card" style="border-left:3px solid #a16207;">
                    <span class="trx-kpi-title" style="color:#a16207;">Pending Payment Action</span>
                    <span class="trx-kpi-value" style="color:#a16207;"><?= $pendingPaymentsCount ?></span>
                    <span class="trx-kpi-sub">Orders awaiting invoice payment / verification</span>
                </div>
                <div class="trx-kpi-card" style="border-left:3px solid #1d4ed8;">
                    <span class="trx-kpi-title" style="color:#1d4ed8;">Completed Exchanges</span>
                    <span class="trx-kpi-value" style="color:#1d4ed8;"><?= $completedExchangesCount ?></span>
                    <span class="trx-kpi-sub">Fully fulfilled circular material transfers</span>
                </div>
            </div>

            <!-- Tabs -->
            <div class="trx-tabs">
                <a href="/business/transactions?tab=all" class="trx-tab-btn <?= $currentTab === 'all' ? 'active' : '' ?>">
                    All Transactions
                </a>
                <a href="/business/transactions?tab=sales" class="trx-tab-btn <?= $currentTab === 'sales' ? 'active' : '' ?>">
                    Sales
                </a>
                <a href="/business/transactions?tab=purchases" class="trx-tab-btn <?= $currentTab === 'purchases' ? 'active' : '' ?>">
                    Purchases &amp; Free Claims
                </a>
            </div>

            <!-- Transactions Table -->
            <div style="background:#ffffff;border:1px solid var(--card-border);border-radius:0;overflow-x:auto;">
                <?php if ($exchanges->isNotEmpty()): ?>
                    <table class="directory-table" style="margin-bottom:0;">
                        <thead>
                            <tr>
                                <th>Order &amp; Item</th>
                                <th>Type</th>
                                <th>Qty &amp; Unit Price</th>
                                <th>Total (RM)</th>
                                <th>Payment</th>
                                <th>Delivery</th>
                                <th>Date</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exchanges as $exchange):
                                $isSeller = ($exchange->provider_business_id === $business->id);
                                $isBuyer = ! $isSeller;
                                $listingType = $exchange->resolvedListingType();
                                $isNoPay = in_array($listingType, ['free', 'lend'], true) || (float) $exchange->total_price <= 0;
                                $typeLabel = $exchange->listingTypeLabel();
                                $sellerName = $exchange->seller_business_name ?: ($exchange->provider?->business_name ?: ($exchange->provider?->name ?? 'Seller Business'));
                                $buyerName = $exchange->buyer_business_name ?: ($exchange->requester?->business_name ?: ($exchange->requester?->name ?? 'Buyer Business'));
                                $counterpartName = $isSeller ? $buyerName : $sellerName;
                                $qtyFormatted = rtrim(rtrim(number_format((float) $exchange->quantity, 2, '.', ''), '0'), '.');

                                $payStatus = $exchange->normalizedPaymentStatus();
                                $payClass = match ($payStatus) {
                                    'paid' => 'pay-paid',
                                    'free' => 'pay-free',
                                    'failed' => 'pay-requested',
                                    'cancelled' => 'pay-cancelled',
                                    'refunded' => 'pay-refunded',
                                    default => 'pay-pending',
                                };
                                $payLabel = $exchange->paymentStatusLabel();
                                $deliveryLabel = $exchange->deliveryStatusLabel();
                                $itemTitle = $exchange->item_name ?: ($exchange->invoice?->item_name ?: ($exchange->listing?->title ?: 'Material #' . $exchange->item_id));
                                $unitPriceDisplay = $isNoPay ? 'RM 0.00' : ('RM '.number_format((float) $exchange->unit_price, 2).' / '.$exchange->unit);
                            ?>
                                <tr>
                                    <td>
                                        <strong style="color:#143825;"><?= e($itemTitle) ?></strong>
                                        <span style="font-size:11px;color:var(--text-secondary);display:block;"><?= e($exchange->resolvedOrderReference()) ?> &middot; <?= $isSeller ? 'Buyer: ' : 'Seller: ' ?><?= e($counterpartName) ?></span>
                                    </td>
                                    <td>
                                        <span style="display:inline-block;padding:3px 8px;border:1px solid #cbd5e1;background:#f8fafc;color:#1e293b;font-size:11px;font-weight:700;"><?= e($typeLabel) ?></span>
                                    </td>
                                    <td style="font-size:12.5px;color:#334155;white-space:nowrap;">
                                        <strong><?= e($qtyFormatted) ?> <?= e($exchange->unit) ?></strong>
                                        <span style="display:block;font-size:11px;color:#64748b;"><?= e($unitPriceDisplay) ?></span>
                                    </td>
                                    <td style="font-weight:700;color:#113624;white-space:nowrap;">
                                        RM <?= number_format($isNoPay ? 0.00 : (float) $exchange->total_price, 2) ?>
                                    </td>
                                    <td>
                                        <span class="pay-badge <?= $payClass ?>"><?= e($payLabel) ?></span>
                                    </td>
                                    <td>
                                        <span style="display:inline-block;padding:3px 8px;border:1px solid #cbd5e1;background:#ffffff;color:#1e293b;font-size:11px;font-weight:600;"><?= e($deliveryLabel) ?></span>
                                    </td>
                                    <td style="font-size:12px;color:#334155;white-space:nowrap;">
                                        <?= $exchange->created_at ? $exchange->created_at->format('d M Y, h:i A') : 'N/A' ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:inline-flex;gap:6px;align-items:center;flex-wrap:nowrap;">
                                            <a href="<?= route('business.orders.show', $exchange) ?>" class="btn-action-sm" style="background:#ffffff;border:1px solid #143825;color:#143825;text-decoration:none;">
                                                Track Order
                                            </a>
                                            <?php if ($isBuyer && ! $isNoPay && $exchange->requiresStripePayment() && ! in_array($payStatus, ['paid', 'refunded'], true)): ?>
                                                <a href="<?= route('business.orders.checkout', $exchange) ?>" class="btn-action-sm" style="background:#143825;border:1px solid #143825;color:#ffffff;text-decoration:none;">
                                                    Pay Now
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($exchange->invoice): ?>
                                                <a href="<?= route('business.invoices.show', $exchange->invoice) ?>" class="btn-action-sm" style="background:#f8fafc;border:1px solid #cbd5e1;color:#334155;text-decoration:none;">
                                                    Receipt
                                                </a>
                                                <a href="<?= route('business.invoices.pdf', $exchange->invoice) ?>" class="btn-action-sm" style="background:#f0fdf4;border:1px solid #86efac;color:#15803d;text-decoration:none;">
                                                    PDF
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="padding:16px 20px;">
                        <?= $exchanges->links()?->toHtml() ?>
                    </div>
                <?php else: ?>
                    <div style="padding:48px 24px;text-align:center;">
                        <h4 style="margin:0 0 6px;color:#143825;font-size:16px;">No Transactions Yet</h4>
                        <p style="margin:0 0 18px;color:var(--text-secondary);font-size:13px;max-width:440px;margin-left:auto;margin-right:auto;">
                            When you purchase or supply surplus materials on the Marketplace, orders, invoices, and payment requests will appear here.
                        </p>
                        <a href="/business/marketplace" class="btn-search" style="text-decoration:none;">
                            Explore Marketplace
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
