<?php
$seller = is_array($invoice->seller_snapshot) && ! empty($invoice->seller_snapshot)
    ? $invoice->seller_snapshot
    : app(\App\Services\InvoiceService::class)->buildBusinessSnapshot($invoice->seller);

$buyer = is_array($invoice->buyer_snapshot) && ! empty($invoice->buyer_snapshot)
    ? $invoice->buyer_snapshot
    : app(\App\Services\InvoiceService::class)->buildBusinessSnapshot($invoice->buyer);

$hasTax = (float) $invoice->tax_amount > 0 && ! empty($invoice->tax_label);
$qtyFormatted = rtrim(rtrim(number_format((float) $invoice->quantity, 2, '.', ''), '0'), '.');
$methodRaw = strtolower((string) ($invoice->payment_method ?: 'card'));
$methodDisplay = match ($methodRaw) {
    'card' => 'Stripe Card Payment',
    'fpx' => 'FPX Online Banking',
    'grabpay' => 'GrabPay',
    default => strtoupper($methodRaw),
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= e($invoice->invoice_number) ?> &middot; EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .invoice-action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        .invoice-paper {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0;
            max-width: 900px;
            margin: 0 auto;
        }
        .invoice-banner {
            background: #143825;
            color: #ffffff;
            padding: 26px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .invoice-brand-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .invoice-brand-logo {
            width: 44px;
            height: 44px;
            background: #ffffff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px;
            box-sizing: border-box;
        }
        .invoice-brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .invoice-body {
            padding: 28px 32px;
        }
        .invoice-meta-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border: 1px solid #cbd5e1;
            background: #f8faf8;
            margin-bottom: 22px;
        }
        .invoice-meta-cell {
            padding: 12px 14px;
            border-right: 1px solid #cbd5e1;
        }
        .invoice-meta-cell:last-child {
            border-right: none;
        }
        .invoice-meta-label {
            font-size: 10.5px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: block;
            margin-bottom: 4px;
        }
        .invoice-meta-value {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            font-family: monospace;
        }
        .invoice-parties-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 24px;
        }
        .invoice-party-box {
            border: 1px solid #cbd5e1;
            background: #ffffff;
        }
        .invoice-party-header {
            background: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            padding: 9px 14px;
            font-size: 11px;
            font-weight: 700;
            color: #143825;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .invoice-party-body {
            padding: 14px;
            font-size: 13px;
            color: #334155;
            line-height: 1.6;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            margin-bottom: 18px;
        }
        .invoice-table th {
            background: #143825;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 11px 14px;
            text-align: left;
            border: 1px solid #143825;
        }
        .invoice-table td {
            padding: 14px;
            font-size: 13px;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }
        .invoice-totals-wrap {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 24px;
        }
        .invoice-totals-box {
            width: 320px;
            border: 1px solid #cbd5e1;
        }
        .invoice-totals-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 14px;
            font-size: 13px;
            border-bottom: 1px solid #cbd5e1;
        }
        .invoice-totals-row.grand-total {
            background: #eef6ed;
            border-bottom: none;
            font-size: 15px;
            font-weight: 800;
            color: #143825;
        }
        .invoice-payment-box {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            margin-bottom: 20px;
        }
        .invoice-payment-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px 24px;
            padding: 14px;
            font-size: 12.5px;
            color: #334155;
        }
        .btn-inv-primary {
            padding: 9px 18px;
            background: #143825;
            border: 1px solid #143825;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border-radius: 0;
        }
        .btn-inv-secondary {
            padding: 9px 16px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #1e293b;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border-radius: 0;
        }
        @media (max-width: 768px) {
            .invoice-meta-strip,
            .invoice-parties-grid,
            .invoice-payment-grid {
                grid-template-columns: 1fr;
            }
            .invoice-meta-cell {
                border-right: none;
                border-bottom: 1px solid #cbd5e1;
            }
            .invoice-meta-cell:last-child {
                border-bottom: none;
            }
            .invoice-totals-box {
                width: 100%;
            }
        }
        @media print {
            .dashboard-topbar,
            .dashboard-sidebar,
            .sidebar-backdrop,
            .invoice-action-bar,
            .status-banner-screen {
                display: none !important;
            }
            .dashboard-layout,
            .dashboard-main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            .invoice-paper {
                border: none !important;
                max-width: 100% !important;
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
            <?php if (session('status')): ?>
                <div class="status-banner-screen" style="background:#eef6ed;border:1px solid #b9dec0;border-left:3px solid #176b40;color:#143825;padding:12px 18px;font-size:13px;margin-bottom:18px;font-weight:600;">
                    <?= e(session('status')) ?>
                </div>
            <?php endif; ?>

            <div class="invoice-action-bar">
                <a href="<?= route('business.invoices.index', ['tab' => 'invoices']) ?>" class="btn-inv-secondary">
                    &larr; Back to My Invoices
                </a>
                <div style="display:flex;gap:10px;align-items:center;">
                    <button type="button" onclick="window.print()" class="btn-inv-secondary">
                        Print Invoice
                    </button>
                    <a href="<?= route('business.invoices.pdf', $invoice) ?>" class="btn-inv-primary">
                        Download PDF
                    </a>
                </div>
            </div>

            <div class="invoice-paper">
                <!-- Header Banner -->
                <div class="invoice-banner">
                    <div class="invoice-brand-group">
                        <span class="invoice-brand-logo">
                            <img src="/images/figma/ecocycle-leaf.svg" alt="EcoCycle">
                        </span>
                        <div>
                            <div style="font-size:22px;font-weight:800;letter-spacing:-0.01em;">EcoCycle</div>
                            <div style="font-size:12px;color:#cde0d3;">Circular Economy Material Exchange Platform &middot; Malaysia</div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:24px;font-weight:800;letter-spacing:0.06em;">INVOICE</div>
                        <div style="font-size:12px;color:#cde0d3;">Official Electronic Invoice</div>
                    </div>
                </div>

                <div class="invoice-body">
                    <!-- Invoice Metadata -->
                    <div class="invoice-meta-strip">
                        <div class="invoice-meta-cell">
                            <span class="invoice-meta-label">Invoice Number</span>
                            <span class="invoice-meta-value"><?= e($invoice->invoice_number) ?></span>
                        </div>
                        <div class="invoice-meta-cell">
                            <span class="invoice-meta-label">Order Reference</span>
                            <span class="invoice-meta-value"><?= e($invoice->order_reference) ?></span>
                        </div>
                        <div class="invoice-meta-cell">
                            <span class="invoice-meta-label">Issue Date</span>
                            <span class="invoice-meta-value" style="font-family:inherit;"><?= $invoice->issued_at ? $invoice->issued_at->format('d M Y') : '' ?></span>
                        </div>
                        <div class="invoice-meta-cell">
                            <span class="invoice-meta-label">Payment Status</span>
                            <span class="invoice-meta-value" style="font-family:inherit;color:#15803d;text-transform:uppercase;"><?= e($invoice->payment_status) ?></span>
                        </div>
                    </div>

                    <!-- Seller & Buyer Information -->
                    <div class="invoice-parties-grid">
                        <div class="invoice-party-box">
                            <div class="invoice-party-header">Seller Information</div>
                            <div class="invoice-party-body">
                                <strong style="display:block;font-size:14px;color:#0f172a;margin-bottom:4px;"><?= e($seller['business_name'] ?? 'Seller') ?></strong>
                                <?php if (! empty($seller['registration_number'])): ?>
                                    <div>Registration No: <strong><?= e($seller['registration_number']) ?></strong></div>
                                <?php endif; ?>
                                <?php if (! empty($seller['address'])): ?>
                                    <div><?= e($seller['address']) ?></div>
                                <?php endif; ?>
                                <?php if (! empty($seller['phone'])): ?>
                                    <div>Tel: <?= e($seller['phone']) ?></div>
                                <?php endif; ?>
                                <?php if (! empty($seller['email'])): ?>
                                    <div>Email: <?= e($seller['email']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="invoice-party-box">
                            <div class="invoice-party-header">Buyer Information</div>
                            <div class="invoice-party-body">
                                <strong style="display:block;font-size:14px;color:#0f172a;margin-bottom:4px;"><?= e($buyer['business_name'] ?? 'Buyer') ?></strong>
                                <?php if (! empty($buyer['registration_number'])): ?>
                                    <div>Registration No: <strong><?= e($buyer['registration_number']) ?></strong></div>
                                <?php endif; ?>
                                <?php if (! empty($buyer['address'])): ?>
                                    <div><?= e($buyer['address']) ?></div>
                                <?php endif; ?>
                                <?php if (! empty($buyer['phone'])): ?>
                                    <div>Tel: <?= e($buyer['phone']) ?></div>
                                <?php endif; ?>
                                <?php if (! empty($buyer['email'])): ?>
                                    <div>Email: <?= e($buyer['email']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Order Details Table -->
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th style="width:48%;">Item &amp; Description</th>
                                <th style="width:16%;text-align:right;">Quantity</th>
                                <th style="width:18%;text-align:right;">Unit Price (RM)</th>
                                <th style="width:18%;text-align:right;">Subtotal (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong style="display:block;font-size:13.5px;color:#0f172a;margin-bottom:4px;"><?= e($invoice->item_name) ?></strong>
                                    <?php if (! empty($invoice->item_description)): ?>
                                        <span style="font-size:12px;color:#475569;line-height:1.5;"><?= e($invoice->item_description) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;white-space:nowrap;"><?= e($qtyFormatted) ?> <?= e($invoice->unit) ?></td>
                                <td style="text-align:right;white-space:nowrap;"><?= e($invoice->formattedUnitPrice()) ?></td>
                                <td style="text-align:right;font-weight:700;white-space:nowrap;"><?= e($invoice->formattedSubtotal()) ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Totals Box -->
                    <div class="invoice-totals-wrap">
                        <div class="invoice-totals-box">
                            <div class="invoice-totals-row">
                                <span>Subtotal</span>
                                <strong><?= e($invoice->formattedSubtotal()) ?></strong>
                            </div>
                            <?php if ($hasTax): ?>
                                <div class="invoice-totals-row">
                                    <span><?= e($invoice->tax_label) ?><?= $invoice->tax_rate ? ' (' . number_format((float) $invoice->tax_rate, 2) . '%)' : '' ?></span>
                                    <strong><?= e($invoice->formattedTaxAmount()) ?></strong>
                                </div>
                            <?php endif; ?>
                            <div class="invoice-totals-row grand-total">
                                <span>Total Amount (MYR)</span>
                                <span><?= e($invoice->formattedTotal()) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="invoice-payment-box">
                        <div class="invoice-party-header">Payment Information</div>
                        <div class="invoice-payment-grid">
                            <div>
                                <span style="color:#64748b;display:block;font-size:11px;font-weight:700;text-transform:uppercase;">Payment Method</span>
                                <strong><?= e($methodDisplay) ?></strong>
                            </div>
                            <div>
                                <span style="color:#64748b;display:block;font-size:11px;font-weight:700;text-transform:uppercase;">Stripe Payment Reference</span>
                                <strong style="font-family:monospace;font-size:12px;"><?= e($invoice->payment_reference ?: 'N/A') ?></strong>
                            </div>
                            <div>
                                <span style="color:#64748b;display:block;font-size:11px;font-weight:700;text-transform:uppercase;">Payment Date</span>
                                <strong><?= $invoice->paid_at ? $invoice->paid_at->format('d M Y, h:i A') : ($invoice->issued_at ? $invoice->issued_at->format('d M Y') : 'N/A') ?></strong>
                            </div>
                            <div>
                                <span style="color:#64748b;display:block;font-size:11px;font-weight:700;text-transform:uppercase;">Transaction Status</span>
                                <strong style="color:#15803d;text-transform:uppercase;"><?= e($invoice->payment_status) ?></strong>
                            </div>
                            <div>
                                <span style="color:#64748b;display:block;font-size:11px;font-weight:700;text-transform:uppercase;">Total Paid</span>
                                <strong style="color:#143825;font-size:14px;"><?= e($invoice->formattedTotal()) ?></strong>
                            </div>
                        </div>
                    </div>

                    <div style="border-top:1px solid #cbd5e1;padding-top:14px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;font-size:11.5px;color:#64748b;">
                        <span>This is a computer-generated invoice issued by EcoCycle Malaysia. No signature is required.</span>
                        <span>Currency: Malaysian Ringgit (MYR)</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
