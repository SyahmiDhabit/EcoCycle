<?php
$errors = $errors ?? session('errors', new \Illuminate\Support\ViewErrorBag());
$item = $exchange->item;
$listingType = $exchange->resolvedListingType();
$isFree = $listingType === 'free' || (float) $exchange->total_price <= 0;
$typeLabel = $isFree ? 'Free' : 'Sell';
$qtyFormatted = rtrim(rtrim(number_format((float) $exchange->quantity, 2, '.', ''), '0'), '.').' '.$exchange->unit;
$itemName = $exchange->invoice?->item_name ?: ($item?->name ?: ($item?->title ?: 'Material'));

$rawStatus = $exchange->normalizedPaymentStatus();
$statusLabel = $exchange->paymentStatusLabel();
$statusClass = match ($rawStatus) {
    'paid' => 'status-paid',
    'free' => 'status-free',
    'failed' => 'status-failed',
    'cancelled' => 'status-cancelled',
    'refunded' => 'status-refunded',
    default => 'status-pending',
};
$totalDisplay = 'RM '.number_format($isFree ? 0.00 : (float) $totals['total'], 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Information &middot; EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .pay-card {
            max-width: 640px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0;
        }
        .pay-card-header {
            padding: 16px 24px;
            background: #143825;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .pay-card-body {
            padding: 24px;
        }
        .pay-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            margin-bottom: 22px;
        }
        .pay-table th {
            width: 36%;
            text-align: left;
            padding: 13px 16px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .pay-table td {
            padding: 13px 16px;
            border: 1px solid #cbd5e1;
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
        }
        .status-tag {
            display: inline-block;
            padding: 3px 10px;
            font-size: 11.5px;
            font-weight: 700;
            border-radius: 0;
            text-transform: uppercase;
        }
        .status-paid { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .status-free { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .status-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .status-failed { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .status-cancelled { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .status-refunded { background: #f3e8ff; color: #6b21a8; border: 1px solid #d8b4fe; }
        .pay-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn-pay-primary {
            padding: 11px 24px;
            background: #143825;
            border: 1px solid #143825;
            color: #ffffff;
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border-radius: 0;
        }
        .btn-pay-primary:hover {
            background: #0d2b1b;
        }
        .btn-pay-secondary {
            padding: 10px 18px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border-radius: 0;
        }
        .btn-pay-secondary:hover {
            background: #f1f5f9;
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
                    <h2>Payment Information</h2>
                    <p>Review your order summary and complete payment.</p>
                </div>
            </header>

            <?php if (session('status')): ?>
                <div style="max-width:640px;background:#eef6ed;border:1px solid #b9dec0;border-left:3px solid #176b40;color:#143825;padding:12px 16px;font-size:13px;margin-bottom:18px;font-weight:600;">
                    <?= e(session('status')) ?>
                </div>
            <?php endif; ?>

            <?php if ($errors->any()): ?>
                <div style="max-width:640px;background:#fff5f4;border:1px solid #fed7d2;border-left:3px solid #b91c1c;color:#b91c1c;padding:12px 16px;font-size:13px;margin-bottom:18px;font-weight:600;">
                    <?= e($errors->first()) ?>
                </div>
            <?php endif; ?>

            <div class="pay-card">
                <div class="pay-card-header">
                    <strong style="font-size:15px;">Payment Information</strong>
                    <span style="font-size:12px;color:#cde0d3;"><?= e($exchange->resolvedOrderReference()) ?></span>
                </div>

                <div class="pay-card-body">
                    <table class="pay-table">
                        <tbody>
                            <tr>
                                <th>Item Name</th>
                                <td><?= e($itemName) ?></td>
                            </tr>
                            <tr>
                                <th>Quantity</th>
                                <td><?= e($qtyFormatted) ?></td>
                            </tr>
                            <tr>
                                <th>Type</th>
                                <td><?= e($typeLabel) ?></td>
                            </tr>
                            <tr>
                                <th>Total Amount (RM)</th>
                                <td style="font-size:17px;font-weight:800;color:#143825;"><?= e($totalDisplay) ?></td>
                            </tr>
                            <tr>
                                <th>Payment Status</th>
                                <td><span class="status-tag <?= $statusClass ?>"><?= e($statusLabel) ?></span></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="pay-actions">
                        <a href="<?= route('business.invoices.index') ?>" class="btn-pay-secondary">
                            &larr; Transaction History
                        </a>

                        <div style="display:flex;gap:10px;align-items:center;">
                            <?php if (! $isFree && $exchange->requiresStripePayment() && ! in_array($rawStatus, ['paid', 'refunded'], true)): ?>
                                <form method="POST" action="<?= route('business.payments.stripe.checkout', $exchange) ?>" style="margin:0;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn-pay-primary">
                                        Pay Now (<?= e($totalDisplay) ?>)
                                    </button>
                                </form>
                            <?php elseif ($exchange->invoice): ?>
                                <a href="<?= route('business.invoices.show', $exchange->invoice) ?>" class="btn-pay-secondary">
                                    View Receipt
                                </a>
                                <a href="<?= route('business.invoices.pdf', $exchange->invoice) ?>" class="btn-pay-primary">
                                    Download PDF
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
