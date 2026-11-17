<?php
$errors = $errors ?? session('errors', new \Illuminate\Support\ViewErrorBag());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History &amp; Receipts &middot; EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .history-table-wrap {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 0;
            overflow-x: auto;
        }
        .status-tag {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 0;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .status-paid { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .status-free { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .status-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .status-failed { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .status-cancelled { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .type-tag {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 0;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #1e293b;
        }
        .btn-rect-primary {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            background: #143825;
            border: 1px solid #143825;
            color: #ffffff;
            text-decoration: none;
            font-size: 11.5px;
            font-weight: 700;
            border-radius: 0;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn-rect-primary:hover {
            background: #0d2b1b;
        }
        .btn-rect-outline {
            display: inline-flex;
            align-items: center;
            padding: 6px 11px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            text-decoration: none;
            font-size: 11.5px;
            font-weight: 600;
            border-radius: 0;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn-rect-outline:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }
    </style>
</head>
<body class="business-dashboard-page">
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <?php
            $activeNav = 'invoices';
            include resource_path('views/business/partials/sidebar.php');
        ?>

        <main class="dashboard-main-content">
            <header class="section-header">
                <div class="section-header-title">
                    <h2>Transaction History &amp; Receipts</h2>
                    <p>View your Marketplace Sell and Free transactions, complete pending payments, and download PDF receipts.</p>
                </div>
            </header>

            <?php if (session('status')): ?>
                <div style="background:#eef6ed;border:1px solid #b9dec0;border-left:3px solid #176b40;color:#143825;padding:12px 18px;border-radius:0;font-size:13px;margin-bottom:20px;font-weight:600;">
                    <?= e(session('status')) ?>
                </div>
            <?php endif; ?>

            <?php if ($errors->any()): ?>
                <div style="background:#fff5f4;border:1px solid #fed7d2;border-left:3px solid #b91c1c;color:#b91c1c;padding:12px 18px;border-radius:0;font-size:13px;margin-bottom:20px;font-weight:600;">
                    <?= e($errors->first()) ?>
                </div>
            <?php endif; ?>

            <div class="history-table-wrap">
                <?php if ($orders->isNotEmpty()): ?>
                    <table class="directory-table" style="margin-bottom:0;">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Type</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th style="text-align:right;">Receipt / Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order):
                                $isBuyer = ($order->requester_business_id === $business->id);
                                $isFree = ($order->resolvedListingType() === 'free') || (float) $order->total_price <= 0;
                                $typeLabel = $isFree ? 'Free' : 'Sell';
                                $qtyFormatted = rtrim(rtrim(number_format((float) $order->quantity, 2, '.', ''), '0'), '.');
                                $itemTitle = $order->item?->name ?: ($order->item?->title ?: 'Material #' . $order->item_id);

                                $rawStatus = strtolower((string) ($order->payment_status ?: ($isFree ? 'free' : 'pending_payment')));
                                $statusLabel = match (true) {
                                    $isFree || $rawStatus === 'free' => 'Free',
                                    $rawStatus === 'paid' => 'Paid',
                                    $rawStatus === 'failed' => 'Failed',
                                    $rawStatus === 'cancelled' => 'Cancelled',
                                    default => 'Pending',
                                };
                                $statusClass = match (true) {
                                    $isFree || $rawStatus === 'free' => 'status-free',
                                    $rawStatus === 'paid' => 'status-paid',
                                    $rawStatus === 'failed' => 'status-failed',
                                    $rawStatus === 'cancelled' => 'status-cancelled',
                                    default => 'status-pending',
                                };
                                $totalDisplay = 'RM '.number_format($isFree ? 0.00 : (float) $order->total_price, 2);
                                $dateDisplay = $order->created_at ? $order->created_at->format('d M Y') : '';
                            ?>
                                <tr>
                                    <td>
                                        <strong style="color:#143825;font-size:13.5px;"><?= e($itemTitle) ?></strong>
                                        <span style="display:block;font-size:11.5px;color:var(--text-muted);"><?= e($qtyFormatted) ?> <?= e($order->unit) ?></span>
                                    </td>
                                    <td>
                                        <span class="type-tag"><?= e($typeLabel) ?></span>
                                    </td>
                                    <td style="font-weight:700;color:#113624;white-space:nowrap;">
                                        <?= e($totalDisplay) ?>
                                    </td>
                                    <td>
                                        <span class="status-tag <?= $statusClass ?>"><?= e($statusLabel) ?></span>
                                    </td>
                                    <td style="font-size:12.5px;color:#334155;white-space:nowrap;">
                                        <?= e($dateDisplay) ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:inline-flex;gap:6px;align-items:center;">
                                            <?php if ($isBuyer && ! $isFree && $order->requiresStripePayment() && $rawStatus !== 'paid'): ?>
                                                <a href="<?= route('business.orders.checkout', $order) ?>" class="btn-rect-primary">
                                                    Pay Now
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($order->invoice): ?>
                                                <a href="<?= route('business.invoices.show', $order->invoice) ?>" class="btn-rect-outline">
                                                    View Receipt
                                                </a>
                                                <a href="<?= route('business.invoices.pdf', $order->invoice) ?>" class="btn-rect-primary">
                                                    Download PDF
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="padding:16px 20px;">
                        <?= $orders->links()?->toHtml() ?>
                    </div>
                <?php else: ?>
                    <div style="padding:48px 24px;text-align:center;">
                        <h4 style="margin:0 0 6px;color:#143825;font-size:16px;">No Transactions Yet</h4>
                        <p style="margin:0 0 18px;color:var(--text-secondary);font-size:13px;">Your Sell purchases, Free claims, and downloadable PDF receipts will appear here.</p>
                        <a href="<?= route('business.marketplace') ?>" class="btn-rect-primary">Browse Marketplace</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
