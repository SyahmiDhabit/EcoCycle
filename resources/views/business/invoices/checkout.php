<?php
$errors = $errors ?? session('errors', new \Illuminate\Support\ViewErrorBag());
$item = $exchange->item;
$seller = $exchange->provider;
$sellerSnap = app(\App\Services\InvoiceService::class)->buildBusinessSnapshot($seller);
$orderRef = $exchange->resolvedOrderReference();
$qtyFormatted = rtrim(rtrim(number_format((float) $exchange->quantity, 2, '.', ''), '0'), '.');
$hasTax = (float) ($totals['tax_amount'] ?? 0) > 0 && ! empty($totals['tax_label']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Checkout <?= e($orderRef) ?> &middot; EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .checkout-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 20px;
            max-width: 980px;
        }
        .checkout-box {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0;
        }
        .checkout-box-header {
            padding: 14px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            font-size: 13px;
            font-weight: 700;
            color: #143825;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .checkout-box-body {
            padding: 20px;
        }
        .checkout-row {
            display: flex;
            justify-content: space-between;
            padding: 9px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
            color: #334155;
        }
        .checkout-row:last-child {
            border-bottom: none;
        }
        .btn-pay-now {
            width: 100%;
            padding: 13px 20px;
            background: #143825;
            border: 1px solid #143825;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            border-radius: 0;
        }
        .btn-pay-now:hover {
            background: #0d2b1b;
        }
        @media (max-width: 820px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
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
                    <h2>Order Review &amp; Stripe Checkout</h2>
                    <p>Review your Reusable Marketplace order details below before proceeding to Stripe's secure payment page.</p>
                </div>
            </header>

            <?php if (session('status')): ?>
                <div style="background:#eef6ed;border:1px solid #b9dec0;border-left:3px solid #176b40;color:#143825;padding:12px 18px;font-size:13px;margin-bottom:18px;font-weight:600;">
                    <?= e(session('status')) ?>
                </div>
            <?php endif; ?>

            <?php if ($errors->any()): ?>
                <div style="background:#fff5f4;border:1px solid #fed7d2;border-left:3px solid #b91c1c;color:#b91c1c;padding:12px 18px;font-size:13px;margin-bottom:18px;font-weight:600;">
                    <?= e($errors->first()) ?>
                </div>
            <?php endif; ?>

            <div class="checkout-grid">
                <!-- Left: Order & Seller Summary -->
                <div style="display:flex;flex-direction:column;gap:18px;">
                    <div class="checkout-box">
                        <div class="checkout-box-header">Order Details &middot; <?= e($orderRef) ?></div>
                        <div class="checkout-box-body">
                            <div class="checkout-row">
                                <span>Material Name</span>
                                <strong style="color:#0f172a;"><?= e($item?->name ?: ($item?->title ?: 'Material')) ?></strong>
                            </div>
                            <?php if (! empty($item?->category)): ?>
                                <div class="checkout-row">
                                    <span>Category</span>
                                    <span><?= e($item->category) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (! empty($item?->description)): ?>
                                <div class="checkout-row" style="flex-direction:column;gap:4px;">
                                    <span>Description</span>
                                    <span style="color:#475569;font-size:12.5px;line-height:1.5;"><?= e($item->description) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="checkout-row">
                                <span>Quantity</span>
                                <strong><?= e($qtyFormatted) ?> <?= e($exchange->unit) ?></strong>
                            </div>
                            <div class="checkout-row">
                                <span>Unit Price</span>
                                <strong>RM <?= number_format((float) $unitPrice, 2) ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-box">
                        <div class="checkout-box-header">Seller Information</div>
                        <div class="checkout-box-body" style="font-size:13px;color:#334155;line-height:1.6;">
                            <strong style="display:block;font-size:14px;color:#0f172a;margin-bottom:4px;"><?= e($sellerSnap['business_name'] ?? 'Seller Business') ?></strong>
                            <?php if (! empty($sellerSnap['registration_number'])): ?>
                                <div>SSM Reg. No: <strong><?= e($sellerSnap['registration_number']) ?></strong></div>
                            <?php endif; ?>
                            <?php if (! empty($sellerSnap['address'])): ?>
                                <div><?= e($sellerSnap['address']) ?></div>
                            <?php endif; ?>
                            <?php if (! empty($sellerSnap['phone'])): ?>
                                <div>Tel: <?= e($sellerSnap['phone']) ?></div>
                            <?php endif; ?>
                            <?php if (! empty($sellerSnap['email'])): ?>
                                <div>Email: <?= e($sellerSnap['email']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Payment Summary & Pay Now -->
                <div class="checkout-box" style="align-self:start;">
                    <div class="checkout-box-header">Payment Summary (MYR)</div>
                    <div class="checkout-box-body">
                        <div class="checkout-row">
                            <span>Subtotal</span>
                            <strong>RM <?= number_format((float) $totals['subtotal'], 2) ?></strong>
                        </div>
                        <?php if ($hasTax): ?>
                            <div class="checkout-row">
                                <span><?= e($totals['tax_label']) ?> (<?= number_format((float) $totals['tax_rate'], 2) ?>%)</span>
                                <strong>RM <?= number_format((float) $totals['tax_amount'], 2) ?></strong>
                            </div>
                        <?php endif; ?>
                        <div style="margin:14px 0 18px;padding:14px;background:#eef6ed;border:1px solid #b9dec0;display:flex;justify-content:space-between;align-items:center;">
                            <strong style="font-size:13px;color:#143825;">TOTAL PAYABLE</strong>
                            <strong style="font-size:20px;color:#143825;">RM <?= number_format((float) $totals['total'], 2) ?></strong>
                        </div>

                        <?php if ($exchange->requiresStripePayment()): ?>
                            <form method="POST" action="<?= route('business.payments.stripe.checkout', $exchange) ?>" style="margin:0 0 14px;">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn-pay-now">
                                    Pay Now (RM <?= number_format((float) $totals['total'], 2) ?>)
                                </button>
                            </form>
                        <?php else: ?>
                            <div style="padding:12px;background:#f8fafc;border:1px solid #cbd5e1;font-size:12.5px;color:#475569;margin-bottom:14px;">
                                This order does not require Stripe Checkout payment.
                            </div>
                        <?php endif; ?>

                        <div style="padding:12px;background:#f8fafc;border:1px solid #cbd5e1;font-size:11.5px;color:#475569;line-height:1.55;">
                            <strong style="display:block;color:#1e293b;margin-bottom:4px;">Stripe Test Mode &amp; Payment Security</strong>
                            You will be redirected to Stripe's hosted checkout page. EcoCycle never stores card numbers or CVCs. Payments are collected by EcoCycle's Stripe test account in MYR, and your order is marked Paid with a downloadable PDF invoice once Stripe's webhook signature is verified.
                        </div>

                        <div style="margin-top:14px;">
                            <a href="<?= route('business.invoices.index', ['tab' => 'orders']) ?>" style="display:block;text-align:center;padding:9px 14px;border:1px solid #cbd5e1;color:#334155;text-decoration:none;font-size:12.5px;font-weight:600;">
                                Back to My Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
