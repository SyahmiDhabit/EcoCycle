<?php
/** @var \App\Models\User $business */
/** @var \Illuminate\Pagination\LengthAwarePaginator $orders */
/** @var string $currentTab */
/** @var string $selectedDeliveryStatus */
$errors = $errors ?? session('errors', new \Illuminate\Support\ViewErrorBag());
$activeNav = $currentTab === 'all' ? 'orders' : $currentTab;
$pageTitle = match ($currentTab) {
    'sales' => 'My Sales',
    'purchases' => 'My Purchases',
    default => 'Order Tracking',
};
$pageDescription = match ($currentTab) {
    'sales' => 'Track orders for materials from your listings.',
    'purchases' => 'Track materials you bought, claimed or borrowed.',
    default => 'Track collection, delivery and returns.',
};
?>
<!DOCTYPE html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> &middot; EcoCycle</title>
    <link rel="icon" type="image/svg+xml" href="/images/figma/ecocycle-leaf.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .ord-toolbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid #cbd5e1;
        }
        .ord-tab {
            padding: 8px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 0;
            background: #ffffff;
            color: #334155;
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
        }
        .ord-tab:hover { background: #eef6ec; border-color: #143825; }
        .ord-tab.active { background: #143825; border-color: #143825; color: #ffffff; }
        .ord-badge {
            display: inline-block;
            padding: 3px 9px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 0;
            white-space: nowrap;
        }
        .ord-status-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .ord-status-progress { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .ord-status-done { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .ord-status-cancelled { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .ord-btn-sm {
            padding: 5px 11px;
            border-radius: 0;
            font-size: 11.5px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }
        .orders-workspace {
            padding-inline: max(clamp(20px, 4vw, 48px), calc((100% - 1180px) / 2));
        }
        .orders-workspace .page-header {
            align-items: center;
            flex-wrap: wrap;
            gap: 12px 20px;
            padding: 0;
            border: 0;
        }
        .orders-workspace .page-header h1 {
            color: #14532d;
            font-size: clamp(26px, 3vw, 40px);
        }
        .ord-toolbar {
            border: 0;
            padding: 0;
        }
        .orders-workspace .ord-tab {
            display: inline-flex;
            align-items: center;
            min-height: 40px;
            border-color: #a3bfa6;
        }
        .orders-workspace .ord-tab.active {
            background: #176b40;
            border-color: #176b40;
        }
        .orders-workspace :is(.ord-tab, .ord-btn-sm) {
            justify-content: center;
            border-radius: 6px !important;
        }
        .ord-results {
            background: #fff;
            border: 1px solid var(--card-border);
            overflow-x: auto;
        }
        .orders-workspace .ord-results {
            box-shadow: 0 5px 12px rgba(20, 83, 45, .05);
        }
        .ord-results .directory-table th {
            background: #eef5eb;
            color: #14532d;
        }
        .orders-workspace :is(a, select, .ord-results):focus-visible {
            outline: 2px solid #176b40;
            outline-offset: -2px;
        }
    </style>
    <link rel="stylesheet" href="/css/site-preferences.css?v=<?= filemtime(public_path('css/site-preferences.css')) ?>">
    <script src="/js/site-theme.js?v=<?= filemtime(public_path('js/site-theme.js')) ?>"></script>
    <script src="/js/language-switcher.js?v=<?= filemtime(public_path('js/language-switcher.js')) ?>" defer></script>
</head>
<body class="business-dashboard-page">
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <?php include resource_path('views/business/partials/sidebar.php'); ?>

        <main class="dashboard-main-content orders-workspace">
            <header class="page-header">
                <div class="section-header-title">
                    <h1><?= e($pageTitle) ?></h1>
                    <p><?= e($pageDescription) ?></p>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="<?= e(route('business.reviews.index', ['tab' => $currentTab === 'sales' ? 'received' : 'submitted'])) ?>" class="ord-tab"><?= $currentTab === 'sales' ? 'Received Reviews' : 'Given Reviews' ?></a>
                    <a href="<?= route('business.marketplace') ?>" class="ord-tab active">Browse Marketplace</a>
                </div>
            </header>

            <?php if (session('status')): ?>
                <div style="background:#eef6ed;border:1px solid #b9dec0;border-left:3px solid #176b40;color:#143825;padding:12px 16px;font-size:13px;margin-bottom:18px;font-weight:600;">
                    <?= e(session('status')) ?>
                </div>
            <?php endif; ?>

            <?php if ($errors->any()): ?>
                <div style="background:#fff5f4;border:1px solid #fed7d2;border-left:3px solid #b91c1c;color:#b91c1c;padding:12px 16px;font-size:13px;margin-bottom:18px;font-weight:600;">
                    <?= e($errors->first()) ?>
                </div>
            <?php endif; ?>

            <div class="ord-toolbar">
                <form method="GET" action="<?= route('business.orders.index') ?>" style="display:flex;gap:8px;align-items:center;margin:0;">
                    <input type="hidden" name="tab" value="<?= e($currentTab) ?>">
                    <select aria-label="Delivery status" name="delivery_status" onchange="this.form.submit()" style="padding:7px 10px;border:1px solid #cbd5e1;border-radius:0;font-size:12.5px;background:#ffffff;color:#0f172a;">
                        <option value="">All Delivery Statuses</option>
                        <?php foreach (\App\Models\MaterialExchange::DELIVERY_STATUS_LABELS as $statusKey => $statusText): ?>
                            <option value="<?= e($statusKey) ?>" <?= $selectedDeliveryStatus === $statusKey ? 'selected' : '' ?>><?= e($statusText) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="ord-results" role="region" aria-label="Orders" tabindex="0">
                <?php if ($orders->isNotEmpty()): ?>
                    <table class="directory-table" style="margin-bottom:0;">
                        <thead>
                            <tr>
                                <th>Order Ref &amp; Item</th>
                                <th>Role &amp; Partner</th>
                                <th>Type &amp; Qty</th>
                                <th>Total (RM)</th>
                                <th>Payment</th>
                                <th>Delivery Status</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order):
                                $isSeller = $order->provider_business_id === $business->id;
                                $itemTitle = $order->item_name ?: ($order->item?->name ?: ($order->item?->title ?: 'Material #'.$order->item_id));
                                $sellerName = $order->seller_business_name ?: ($order->provider?->business_name ?: 'Seller');
                                $buyerName = $order->buyer_business_name ?: ($order->requester?->business_name ?: 'Buyer');
                                $qtyFormatted = rtrim(rtrim(number_format((float) $order->quantity, 2, '.', ''), '0'), '.').' '.$order->unit;
                                $delStatus = $order->delivery_status ?: 'payment_pending';
                                $delBadgeClass = match ($delStatus) {
                                    'delivered', 'completed', 'returned' => 'ord-status-done',
                                    'confirmed', 'preparing', 'ready_for_collection', 'in_transit' => 'ord-status-progress',
                                    'cancelled' => 'ord-status-cancelled',
                                    default => 'ord-status-pending',
                                };
                                $isNoPay = in_array($order->resolvedListingType(), ['free', 'lend'], true);
                            ?>
                                <tr>
                                    <td>
                                        <strong style="color:#143825;display:block;"><?= e($itemTitle) ?></strong>
                                        <span style="font-size:11px;color:#64748b;font-family:monospace;"><?= e($order->resolvedOrderReference()) ?> &middot; <?= $order->created_at ? $order->created_at->format('d M Y') : '' ?></span>
                                    </td>
                                    <td style="font-size:12.5px;color:#334155;">
                                        <strong><?= $isSeller ? 'Seller' : 'Buyer' ?></strong>
                                        <span style="display:block;font-size:11.5px;color:#64748b;"><?= $isSeller ? 'Buyer: '.e($buyerName) : 'Seller: '.e($sellerName) ?></span>
                                    </td>
                                    <td style="font-size:12.5px;">
                                        <span style="display:inline-block;padding:2px 7px;border:1px solid #cbd5e1;background:#f8fafc;font-size:11px;font-weight:700;margin-right:4px;"><?= e($order->listingTypeLabel()) ?></span>
                                        <span><?= e($qtyFormatted) ?></span>
                                    </td>
                                    <td style="font-weight:700;color:#143825;white-space:nowrap;">
                                        RM <?= number_format($isNoPay ? 0.00 : (float) $order->total_price, 2) ?>
                                    </td>
                                    <td>
                                        <span class="ord-badge <?= $order->normalizedPaymentStatus() === 'paid' ? 'ord-status-done' : ($order->normalizedPaymentStatus() === 'free' ? 'ord-status-progress' : 'ord-status-pending') ?>">
                                            <?= e($order->paymentStatusLabel()) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="ord-badge <?= $delBadgeClass ?>">
                                            <?= e($order->deliveryStatusLabel()) ?>
                                        </span>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:inline-flex;gap:6px;align-items:center;flex-wrap:nowrap;">
                                            <a href="<?= route('business.orders.show', $order) ?>" class="ord-btn-sm" style="background:#176b40;border:1px solid #176b40;color:#ffffff;">
                                                <?= $isSeller ? 'Manage Order' : 'Track Order' ?>
                                            </a>
                                            <?php if (! $isSeller && $order->isReviewable() && ! $order->review): ?>
                                                <a href="<?= route('business.reviews.index', ['tab' => 'eligible']) ?>" class="ord-btn-sm" style="background:#fef3c7;border:1px solid #f59e0b;color:#92400e;">
                                                    Leave Review
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($order->invoice): ?>
                                                <a href="<?= route('business.invoices.show', $order->invoice) ?>" class="ord-btn-sm" style="background:#ffffff;border:1px solid #cbd5e1;color:#334155;">
                                                    Receipt
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="padding:14px 18px;">
                        <?= $orders->links()?->toHtml() ?>
                    </div>
                <?php else: ?>
                    <div style="padding:48px 24px;text-align:center;">
                        <h4 style="margin:0 0 6px;color:#143825;font-size:16px;">No Orders Found</h4>
                        <p style="margin:0 0 18px;color:#64748b;font-size:13px;">
                            Once you purchase, claim, borrow, or sell materials on the Marketplace, you can track fulfillment and delivery here.
                        </p>
                        <a href="<?= route('business.marketplace') ?>" class="ord-tab active">Browse Marketplace</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
