<?php
$buyerName = trim((string) ($invoice->buyer_business_name ?: ($invoice->buyer?->business_name ?: ($invoice->buyer?->name ?? 'Buyer Business'))));
$sellerName = trim((string) ($invoice->seller_business_name ?: ($invoice->seller?->business_name ?: ($invoice->seller?->name ?? 'Seller Business'))));

$sellerAddress = trim((string) ($invoice->seller_address ?? ''));
if ($sellerAddress === '' && $invoice->exchange) {
    $sellerAddress = app(\App\Services\InvoiceService::class)->resolveSellerAddress($invoice->exchange);
}
$sellerContact = trim((string) ($invoice->seller_contact ?? ''));
if ($sellerContact === '' && $invoice->seller) {
    $sellerContact = trim(implode(' | ', array_filter([
        trim((string) ($invoice->seller->business_phone ?? '')),
        trim((string) ($invoice->seller->email ?? '')),
    ])));
}
$buyerContact = trim((string) ($invoice->buyer_contact ?? ''));
if ($buyerContact === '' && $invoice->buyer) {
    $buyerContact = trim(implode(' | ', array_filter([
        trim((string) ($invoice->buyer->business_phone ?? '')),
        trim((string) ($invoice->buyer->email ?? '')),
    ])));
}

$pickupAddress = trim((string) ($invoice->pickup_address ?? ''));
if ($pickupAddress === '' && $invoice->exchange) {
    $pickupAddress = app(\App\Services\InvoiceService::class)->resolvePickupAddress($invoice->exchange);
}
if ($pickupAddress === '') {
    $pickupAddress = 'To be confirmed with seller';
}

$statusLabel = $invoice->statusLabel();
$qtyFormatted = $invoice->formattedQuantity();
$dateFormatted = $invoice->issued_at ? $invoice->issued_at->format('d M Y, h:i A') : date('d M Y');
$unitPriceFormatted = $invoice->formattedUnitPrice();
$totalFormatted = $invoice->formattedTotal();
$paymentRef = trim((string) ($invoice->payment_reference ?: ($invoice->payment?->referenceDisplay() ?: ($invoice->exchange?->payment_reference ?: 'N/A'))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoCycle Receipt <?= e($invoice->invoice_number) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .receipt-toolbar {
            max-width: 720px;
            margin: 0 auto 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .receipt-paper {
            max-width: 720px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0;
        }
        .receipt-header {
            background: #143825;
            color: #ffffff;
            padding: 22px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        .receipt-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .receipt-logo-box {
            width: 40px;
            height: 40px;
            background: #ffffff;
            border-radius: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            box-sizing: border-box;
        }
        .receipt-logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .receipt-body {
            padding: 26px 28px;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
        }
        .receipt-table th {
            width: 38%;
            text-align: left;
            padding: 12px 16px;
            background: #f8faf8;
            border: 1px solid #cbd5e1;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            vertical-align: top;
        }
        .receipt-table td {
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            font-size: 13.5px;
            color: #0f172a;
            line-height: 1.5;
        }
        .receipt-total-row th,
        .receipt-total-row td {
            background: #eef6ed;
            color: #143825;
            font-weight: 800;
            font-size: 15px;
        }
        .receipt-footer {
            margin-top: 18px;
            padding-top: 12px;
            border-top: 1px solid #cbd5e1;
            font-size: 11.5px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .btn-rcp-primary {
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
        .btn-rcp-secondary {
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
        @media print {
            .dashboard-topbar,
            .dashboard-sidebar,
            .sidebar-backdrop,
            .receipt-toolbar,
            .status-banner-screen {
                display: none !important;
            }
            .dashboard-layout,
            .dashboard-main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            .receipt-paper {
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
                <div class="status-banner-screen" style="max-width:720px;margin:0 auto 16px;background:#eef6ed;border:1px solid #b9dec0;border-left:3px solid #176b40;color:#143825;padding:12px 16px;font-size:13px;font-weight:600;">
                    <?= e(session('status')) ?>
                </div>
            <?php endif; ?>

            <div class="receipt-toolbar">
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <a href="<?= route('business.invoices.index') ?>" class="btn-rcp-secondary">
                        &larr; Back to Receipts
                    </a>
                    <?php if ($invoice->exchange): ?>
                        <a href="<?= route('business.orders.show', $invoice->exchange) ?>" class="btn-rcp-secondary">
                            Track Order
                        </a>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:10px;align-items:center;">
                    <button type="button" onclick="window.print()" class="btn-rcp-secondary">
                        Print Receipt
                    </button>
                    <a href="<?= route('business.invoices.pdf', $invoice) ?>" class="btn-rcp-primary">
                        Download PDF
                    </a>
                </div>
            </div>

            <div class="receipt-paper">
                <div class="receipt-header">
                    <div class="receipt-brand">
                        <span class="receipt-logo-box">
                            <img src="/images/figma/ecocycle-leaf.svg" alt="EcoCycle Logo">
                        </span>
                        <div>
                            <div style="font-size:20px;font-weight:800;">EcoCycle Receipt</div>
                            <div style="font-size:12px;color:#cde0d3;">EcoCycle Malaysia Marketplace</div>
                        </div>
                    </div>
                    <span style="padding:5px 12px;border:1px solid #86efac;background:#1d4d34;color:#ffffff;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;">
                        <?= e($statusLabel) ?>
                    </span>
                </div>

                <div class="receipt-body">
                    <!-- Seller & Buyer Information Grid -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
                        <div style="padding:14px 16px;background:#f8faf8;border:1px solid #cbd5e1;">
                            <div style="font-size:11px;font-weight:700;color:#143825;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:6px;">Seller Information</div>
                            <div style="font-size:14px;font-weight:700;color:#0f172a;margin-bottom:4px;"><?= e($sellerName) ?></div>
                            <?php if ($sellerAddress !== ''): ?>
                                <div style="font-size:12.5px;color:#475569;line-height:1.45;margin-bottom:4px;"><?= e($sellerAddress) ?></div>
                            <?php endif; ?>
                            <?php if ($sellerContact !== ''): ?>
                                <div style="font-size:12px;color:#334155;"><?= e($sellerContact) ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="padding:14px 16px;background:#ffffff;border:1px solid #cbd5e1;">
                            <div style="font-size:11px;font-weight:700;color:#143825;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:6px;">Buyer Information</div>
                            <div style="font-size:14px;font-weight:700;color:#0f172a;margin-bottom:4px;"><?= e($buyerName) ?></div>
                            <?php if ($buyerContact !== ''): ?>
                                <div style="font-size:12px;color:#334155;"><?= e($buyerContact) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <table class="receipt-table">
                        <tbody>
                            <tr>
                                <th>Receipt ID</th>
                                <td style="font-family:monospace;font-weight:700;"><?= e($invoice->invoice_number) ?></td>
                            </tr>
                            <tr>
                                <th>Payment ID / Reference</th>
                                <td style="font-family:monospace;"><?= e($paymentRef) ?></td>
                            </tr>
                            <tr>
                                <th>Purchase Date</th>
                                <td><?= e($dateFormatted) ?></td>
                            </tr>
                            <tr>
                                <th>Item Name</th>
                                <td style="font-weight:700;"><?= e($invoice->item_name) ?></td>
                            </tr>
                            <tr>
                                <th>Quantity and Unit</th>
                                <td><?= e($qtyFormatted) ?></td>
                            </tr>
                            <tr>
                                <th>Price per Unit (RM)</th>
                                <td><?= e($unitPriceFormatted) ?></td>
                            </tr>
                            <tr class="receipt-total-row">
                                <th>Total Amount (RM)</th>
                                <td><?= e($totalFormatted) ?></td>
                            </tr>
                            <tr>
                                <th>Collection / Pickup Address</th>
                                <td><?= e($pickupAddress) ?></td>
                            </tr>
                            <tr>
                                <th>Payment Status</th>
                                <td style="font-weight:700;color:#15803d;"><?= e($statusLabel) ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="receipt-footer">
                        <span>EcoCycle Malaysia &middot; Electronic Transaction Receipt</span>
                        <span>Currency: MYR</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
