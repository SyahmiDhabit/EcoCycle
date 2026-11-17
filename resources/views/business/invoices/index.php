<?php
$errors = $errors ?? session('errors', new \Illuminate\Support\ViewErrorBag());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders, Payments &amp; Invoices &middot; EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .billing-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .billing-kpi-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 0;
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .billing-kpi-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .billing-kpi-value {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-primary);
            font-family: var(--font-display);
        }
        .billing-kpi-sub {
            font-size: 11.5px;
            color: var(--text-muted);
        }
        .billing-tabs {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--card-border);
            padding-bottom: 10px;
        }
        .billing-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            border: 1px solid var(--card-border);
            border-radius: 0;
            background: #ffffff;
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 700;
            color: #3f5546;
        }
        .billing-tab-btn:hover {
            background: #eef6ec;
            border-color: var(--brand-green);
        }
        .billing-tab-btn.active {
            background: var(--forest-bg);
            border-color: var(--forest-bg);
            color: #ffffff;
        }
        .billing-table-wrap {
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
        .status-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .status-failed { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .status-cancelled { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
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
                    <h2>Orders, Payments &amp; Invoices</h2>
                    <p>Manage your Marketplace purchase orders, Stripe payment history (MYR), and downloadable PDF invoices.</p>
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

            <!-- KPI Summary -->
            <div class="billing-kpi-grid">
                <div class="billing-kpi-card" style="border-left:3px solid #143825;">
                    <span class="billing-kpi-title">My Invoices</span>
                    <span class="billing-kpi-value"><?= (int) $totalInvoicesCount ?></span>
                    <span class="billing-kpi-sub">Official PDF invoices issued</span>
                </div>
                <div class="billing-kpi-card" style="border-left:3px solid #15803d;">
                    <span class="billing-kpi-title" style="color:#15803d;">Stripe Received (MYR)</span>
                    <span class="billing-kpi-value" style="color:#15803d;">RM <?= number_format((float) $totalReceivedRm, 2) ?></span>
                    <span class="billing-kpi-sub">Verified Stripe sales settlements</span>
                </div>
                <div class="billing-kpi-card" style="border-left:3px solid #1d4ed8;">
                    <span class="billing-kpi-title" style="color:#1d4ed8;">Stripe Paid Out (MYR)</span>
                    <span class="billing-kpi-value" style="color:#1d4ed8;">RM <?= number_format((float) $totalPaidOutRm, 2) ?></span>
                    <span class="billing-kpi-sub">Verified Stripe purchase payments</span>
                </div>
                <div class="billing-kpi-card" style="border-left:3px solid #a16207;">
                    <span class="billing-kpi-title" style="color:#a16207;">Awaiting Payment</span>
                    <span class="billing-kpi-value" style="color:#a16207;"><?= (int) $unpaidOrdersCount ?></span>
                    <span class="billing-kpi-sub">Unpaid Sell orders ready for checkout</span>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="billing-tabs">
                <a href="<?= route('business.invoices.index', ['tab' => 'orders']) ?>" class="billing-tab-btn <?= $activeTab === 'orders' ? 'active' : '' ?>">
                    My Orders
                </a>
                <a href="<?= route('business.invoices.index', ['tab' => 'payments']) ?>" class="billing-tab-btn <?= $activeTab === 'payments' ? 'active' : '' ?>">
                    Payment History
                </a>
                <a href="<?= route('business.invoices.index', ['tab' => 'invoices']) ?>" class="billing-tab-btn <?= $activeTab === 'invoices' ? 'active' : '' ?>">
                    My Invoices
                </a>
            </div>

            <!-- TAB 1: MY ORDERS -->
            <?php if ($activeTab === 'orders'): ?>
                <div class="billing-table-wrap">
                    <?php if ($orders->isNotEmpty()): ?>
                        <table class="directory-table" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th>Order Reference</th>
                                    <th>Material</th>
                                    <th>Seller / Buyer</th>
                                    <th>Quantity</th>
                                    <th>Total Amount</th>
                                    <th>Payment Status</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order):
                                    $isBuyer = ($order->requester_business_id === $business->id);
                                    $counterpart = $isBuyer ? $order->provider : $order->requester;
                                    $counterpartName = $counterpart?->business_name ?: ($counterpart?->name ?? 'Partner Business');
                                    $orderRef = $order->resolvedOrderReference();
                                    $payStatus = $order->payment_status ?: 'pending_payment';
                                    $statusClass = match ($payStatus) {
                                        'paid' => 'status-paid',
                                        'failed' => 'status-failed',
                                        'cancelled' => 'status-cancelled',
                                        default => 'status-pending',
                                    };
                                    $statusLabel = match ($payStatus) {
                                        'paid' => 'Paid',
                                        'requested' => 'Payment Requested',
                                        'failed' => 'Payment Failed',
                                        'cancelled' => 'Cancelled',
                                        'borrow_pending' => 'Borrow Request',
                                        default => 'Pending Payment',
                                    };
                                ?>
                                    <tr>
                                        <td>
                                            <strong style="font-family:monospace;font-size:13px;color:#113624;"><?= e($orderRef) ?></strong>
                                            <small style="display:block;font-size:11px;color:var(--text-muted);"><?= $order->created_at ? $order->created_at->format('d M Y') : '' ?></small>
                                        </td>
                                        <td>
                                            <strong style="color:#143825;"><?= e($order->item?->name ?: ($order->item?->title ?: 'Material #' . $order->item_id)) ?></strong>
                                            <span style="display:block;font-size:11px;color:var(--text-secondary);"><?= e($order->item?->category ?: 'Material') ?></span>
                                        </td>
                                        <td>
                                            <strong><?= e($counterpartName) ?></strong>
                                            <span style="display:block;font-size:11px;color:var(--text-muted);"><?= $isBuyer ? 'Seller' : 'Buyer' ?></span>
                                        </td>
                                        <td><?= (float) $order->quantity ?> <?= e($order->unit) ?></td>
                                        <td style="font-weight:700;color:#113624;">
                                            <?php if ((float) $order->total_price <= 0): ?>
                                                <span style="color:#15803d;">FREE (RM 0.00)</span>
                                            <?php else: ?>
                                                RM <?= number_format((float) $order->total_price, 2) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-tag <?= $statusClass ?>"><?= e($statusLabel) ?></span>
                                        </td>
                                        <td style="text-align:right;">
                                            <div style="display:inline-flex;gap:6px;align-items:center;">
                                                <?php if ($isBuyer && $order->requiresStripePayment() && $payStatus !== 'paid'): ?>
                                                    <a href="<?= route('business.orders.checkout', $order) ?>" class="btn-rect-primary">
                                                        Pay Now
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($order->invoice): ?>
                                                    <a href="<?= route('business.invoices.show', $order->invoice) ?>" class="btn-rect-outline">
                                                        View Invoice
                                                    </a>
                                                    <a href="<?= route('business.invoices.pdf', $order->invoice) ?>" class="btn-rect-outline">
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
                            <h4 style="margin:0 0 6px;color:#143825;font-size:16px;">No Orders Found</h4>
                            <p style="margin:0 0 18px;color:var(--text-secondary);font-size:13px;">Browse the Marketplace to order reusable materials or claim free surplus items.</p>
                            <a href="<?= route('business.marketplace') ?>" class="btn-rect-primary">Explore Marketplace</a>
                        </div>
                    <?php endif; ?>
                </div>

            <!-- TAB 2: PAYMENT HISTORY -->
            <?php elseif ($activeTab === 'payments'): ?>
                <div class="billing-table-wrap">
                    <?php if ($payments->isNotEmpty()): ?>
                        <table class="directory-table" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Order Reference</th>
                                    <th>Counterparty</th>
                                    <th>Stripe Reference</th>
                                    <th>Method</th>
                                    <th>Amount (MYR)</th>
                                    <th>Status</th>
                                    <th style="text-align:right;">Invoice</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment):
                                    $isBuyer = ($payment->buyer_business_id === $business->id);
                                    $counterpart = $isBuyer ? $payment->seller : $payment->buyer;
                                    $counterpartName = $counterpart?->business_name ?: ($counterpart?->name ?? 'Partner Business');
                                    $orderRef = $payment->exchange ? $payment->exchange->resolvedOrderReference() : 'N/A';
                                    $statusClass = match ($payment->status) {
                                        'paid' => 'status-paid',
                                        'failed' => 'status-failed',
                                        'cancelled', 'expired' => 'status-cancelled',
                                        default => 'status-pending',
                                    };
                                ?>
                                    <tr>
                                        <td>
                                            <strong style="font-size:12.5px;color:#0f172a;"><?= $payment->paid_at ? $payment->paid_at->format('d M Y') : ($payment->created_at ? $payment->created_at->format('d M Y') : '') ?></strong>
                                            <small style="display:block;font-size:11px;color:var(--text-muted);"><?= $payment->paid_at ? $payment->paid_at->format('h:i A') : ($payment->created_at ? $payment->created_at->format('h:i A') : '') ?></small>
                                        </td>
                                        <td style="font-family:monospace;font-size:12.5px;font-weight:700;color:#113624;"><?= e($orderRef) ?></td>
                                        <td>
                                            <strong><?= e($counterpartName) ?></strong>
                                            <span style="display:block;font-size:11px;color:var(--text-muted);"><?= $isBuyer ? 'Paid to Seller (via EcoCycle Test)' : 'Received from Buyer' ?></span>
                                        </td>
                                        <td style="font-family:monospace;font-size:11.5px;color:#334155;">
                                            <?= e($payment->referenceDisplay()) ?>
                                        </td>
                                        <td style="font-size:12px;text-transform:uppercase;font-weight:600;color:#334155;">
                                            <?= e($payment->payment_method ?: 'Stripe Checkout') ?>
                                        </td>
                                        <td style="font-weight:700;color:#113624;"><?= e($payment->formattedAmount()) ?></td>
                                        <td>
                                            <span class="status-tag <?= $statusClass ?>"><?= e(ucfirst($payment->status)) ?></span>
                                            <?php if ($payment->failure_reason && $payment->status !== 'paid'): ?>
                                                <small style="display:block;font-size:10.5px;color:#b91c1c;margin-top:3px;"><?= e($payment->failure_reason) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <?php if ($payment->invoice): ?>
                                                <div style="display:inline-flex;gap:6px;">
                                                    <a href="<?= route('business.invoices.show', $payment->invoice) ?>" class="btn-rect-outline">View</a>
                                                    <a href="<?= route('business.invoices.pdf', $payment->invoice) ?>" class="btn-rect-outline">PDF</a>
                                                </div>
                                            <?php elseif ($payment->exchange && $payment->exchange->invoice): ?>
                                                <div style="display:inline-flex;gap:6px;">
                                                    <a href="<?= route('business.invoices.show', $payment->exchange->invoice) ?>" class="btn-rect-outline">View</a>
                                                    <a href="<?= route('business.invoices.pdf', $payment->exchange->invoice) ?>" class="btn-rect-outline">PDF</a>
                                                </div>
                                            <?php else: ?>
                                                <span style="font-size:11.5px;color:#94a3b8;">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div style="padding:16px 20px;">
                            <?= $payments->links()?->toHtml() ?>
                        </div>
                    <?php else: ?>
                        <div style="padding:48px 24px;text-align:center;">
                            <h4 style="margin:0 0 6px;color:#143825;font-size:16px;">No Payment Records Yet</h4>
                            <p style="margin:0;color:var(--text-secondary);font-size:13px;">Stripe payment attempts and verified transactions will appear here automatically.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <!-- TAB 3: MY INVOICES -->
            <?php else: ?>
                <div class="billing-table-wrap">
                    <?php if ($invoices->isNotEmpty()): ?>
                        <table class="directory-table" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th>Invoice Number</th>
                                    <th>Order Reference</th>
                                    <th>Issue Date</th>
                                    <th>Seller / Buyer Business</th>
                                    <th>Total Amount</th>
                                    <th>Payment Status</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $invoice):
                                    $isBuyer = ($invoice->buyer_business_id === $business->id);
                                    $counterpartSnapshot = $isBuyer ? ($invoice->seller_snapshot ?? []) : ($invoice->buyer_snapshot ?? []);
                                    $counterpartModel = $isBuyer ? $invoice->seller : $invoice->buyer;
                                    $counterpartName = $counterpartSnapshot['business_name']
                                        ?? $counterpartModel?->business_name
                                        ?? $counterpartModel?->name
                                        ?? 'Partner Business';
                                ?>
                                    <tr>
                                        <td>
                                            <strong style="font-family:monospace;font-size:13px;color:#113624;"><?= e($invoice->invoice_number) ?></strong>
                                        </td>
                                        <td style="font-family:monospace;font-size:12.5px;color:#334155;">
                                            <?= e($invoice->order_reference) ?>
                                        </td>
                                        <td style="font-size:12.5px;color:#334155;">
                                            <?= $invoice->issued_at ? $invoice->issued_at->format('d M Y') : '' ?>
                                        </td>
                                        <td>
                                            <strong><?= e($counterpartName) ?></strong>
                                            <span style="display:block;font-size:11px;color:var(--text-muted);"><?= $isBuyer ? 'Seller' : 'Buyer' ?></span>
                                        </td>
                                        <td style="font-weight:700;color:#113624;">
                                            <?= e($invoice->formattedTotal()) ?>
                                        </td>
                                        <td>
                                            <span class="status-tag status-paid"><?= e(ucfirst($invoice->payment_status)) ?></span>
                                        </td>
                                        <td style="text-align:right;">
                                            <div style="display:inline-flex;gap:6px;align-items:center;">
                                                <a href="<?= route('business.invoices.show', $invoice) ?>" class="btn-rect-outline">
                                                    View Invoice
                                                </a>
                                                <a href="<?= route('business.invoices.pdf', $invoice) ?>" class="btn-rect-primary">
                                                    Download PDF
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div style="padding:16px 20px;">
                            <?= $invoices->links()?->toHtml() ?>
                        </div>
                    <?php else: ?>
                        <div style="padding:48px 24px;text-align:center;">
                            <h4 style="margin:0 0 6px;color:#143825;font-size:16px;">No Invoices Issued Yet</h4>
                            <p style="margin:0 0 18px;color:var(--text-secondary);font-size:13px;">Official downloadable PDF invoices are generated automatically once a Marketplace payment is confirmed.</p>
                            <a href="<?= route('business.invoices.index', ['tab' => 'orders']) ?>" class="btn-rect-outline">View My Orders</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
