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
            border: 1px solid #d8e5d6;
            border-radius: 10px;
            padding: 18px 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .trx-kpi-title {
            font-size: 12px;
            font-weight: 700;
            color: #556c5e;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .trx-kpi-value {
            font-size: 26px;
            font-weight: 800;
            color: #123020;
            font-family: var(--font-display);
        }
        .trx-kpi-sub {
            font-size: 11.5px;
            color: #617769;
        }
        .trx-tabs {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid #dce6db;
            padding-bottom: 10px;
        }
        .trx-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            color: #4b6354;
            transition: all 0.15s ease;
        }
        .trx-tab-btn.active {
            background: #18422d;
            color: #ffffff;
        }
        .pay-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .pay-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .pay-requested { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .pay-paid { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .btn-action-sm {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
            border: none;
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
            $activeNav = 'transactions';
            include resource_path('views/business/partials/sidebar.php'); 
        ?>

        <main class="dashboard-main-content">
            <header class="section-header" style="margin-bottom:20px;">
                <div class="section-header-title">
                    <h2>Transactions, Orders &amp; Invoicing</h2>
                    <p>Manage sales, material claims, borrowing deposits, and invoice payment settlements between circular businesses.</p>
                </div>
            </header>

            <?php if (session('status')): ?>
                <div style="background:#edf7ed;border:1.5px solid #a3cfbb;color:#1e4620;padding:12px 18px;border-radius:8px;font-size:13px;margin-bottom:20px;font-weight:600;">
                    ✓ <?= session('status') ?>
                </div>
            <?php endif; ?>

            <!-- KPI Metric Cards -->
            <div class="trx-kpi-grid">
                <div class="trx-kpi-card" style="border-left:4px solid #16a34a;">
                    <span class="trx-kpi-title" style="color:#15803d;">Total Revenue (Hasil Jualan)</span>
                    <span class="trx-kpi-value" style="color:#15803d;">RM <?= number_format((float) $totalRevenueRm, 2) ?></span>
                    <span class="trx-kpi-sub">Total received from material sales & deposits</span>
                </div>
                <div class="trx-kpi-card" style="border-left:4px solid #dc2626;">
                    <span class="trx-kpi-title" style="color:#b91c1c;">Total Purchases (Duit Keluar)</span>
                    <span class="trx-kpi-value" style="color:#b91c1c;">RM <?= number_format((float) $totalPurchasesRm, 2) ?></span>
                    <span class="trx-kpi-sub">Total spent acquiring secondary surplus materials</span>
                </div>
                <div class="trx-kpi-card" style="border-left:4px solid #eab308;">
                    <span class="trx-kpi-title" style="color:#a16207;">Pending Payment Action</span>
                    <span class="trx-kpi-value" style="color:#a16207;"><?= $pendingPaymentsCount ?></span>
                    <span class="trx-kpi-sub">Orders awaiting invoice payment / verification</span>
                </div>
                <div class="trx-kpi-card" style="border-left:4px solid #2563eb;">
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
                    💰 Sales (Barang Dijual / Dipinjamkan)
                </a>
                <a href="/business/transactions?tab=purchases" class="trx-tab-btn <?= $currentTab === 'purchases' ? 'active' : '' ?>">
                    🛒 Purchases (Barang Dibeli / Dituntut)
                </a>
            </div>

            <!-- Transactions Table -->
            <div style="background:#ffffff;border:1px solid #d8e5d6;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.03);">
                <?php if ($exchanges->isNotEmpty()): ?>
                    <table class="inventory-table" style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f4f8f3;border-bottom:1.5px solid #d8e5d6;text-align:left;font-size:12px;color:#18422d;">
                                <th style="padding:14px 18px;">Invoice / Order Ref</th>
                                <th style="padding:14px 18px;">Material</th>
                                <th style="padding:14px 18px;">Counterparty Business</th>
                                <th style="padding:14px 18px;">Qty &amp; Role</th>
                                <th style="padding:14px 18px;">Total Amount</th>
                                <th style="padding:14px 18px;">Payment Status</th>
                                <th style="padding:14px 18px;text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exchanges as $exchange): 
                                $isSeller = ($exchange->provider_business_id === $business->id);
                                $counterpart = $isSeller ? $exchange->requester : $exchange->provider;
                                $counterpartName = $counterpart?->business_name ?: ($counterpart?->name ?? 'Partner Business');
                                $invNumber = $exchange->invoice_number ?: ('INV-' . date('Y') . '-' . str_pad((string) $exchange->id, 5, '0', STR_PAD_LEFT));
                                
                                $payStatus = $exchange->payment_status ?: 'pending_payment';
                                $payClass = match($payStatus) {
                                    'paid' => 'pay-paid',
                                    'requested' => 'pay-requested',
                                    default => 'pay-pending',
                                };
                                $payLabel = match($payStatus) {
                                    'paid' => '✓ Paid',
                                    'requested' => '⚠ Payment Requested',
                                    default => 'Pending Payment',
                                };
                            ?>
                                <tr style="border-bottom:1px solid #e5ede3;font-size:13px;">
                                    <td style="padding:14px 18px;">
                                        <strong style="color:#113624;font-family:monospace;font-size:13px;"><?= e($invNumber) ?></strong>
                                        <small style="color:#617769;display:block;font-size:11px;"><?= $exchange->created_at ? $exchange->created_at->format('d M Y') : 'N/A' ?></small>
                                    </td>
                                    <td style="padding:14px 18px;">
                                        <strong style="color:#18422d;"><?= e($exchange->listing?->title ?: 'Material Batch #' . $exchange->listing_id) ?></strong>
                                        <span style="font-size:11px;color:#526b5a;display:block;"><?= e($exchange->listing?->category ?: 'Secondary Material') ?></span>
                                    </td>
                                    <td style="padding:14px 18px;">
                                        <strong><?= e($counterpartName) ?></strong>
                                        <span style="font-size:11px;color:#617769;display:block;"><?= $isSeller ? 'Customer / Buyer' : 'Supplier / Seller' ?></span>
                                    </td>
                                    <td style="padding:14px 18px;">
                                        <?= (float) $exchange->quantity ?> <?= e($exchange->unit) ?>
                                        <span style="display:block;font-size:10.5px;font-weight:700;color:<?= $isSeller ? '#15803d' : '#b45309' ?>;">
                                            <?= $isSeller ? '⬆ YOU ARE SELLER' : '⬇ YOU ARE BUYER' ?>
                                        </span>
                                    </td>
                                    <td style="padding:14px 18px;font-weight:800;color:#113624;">
                                        <?php if ((float)$exchange->total_price <= 0): ?>
                                            <span style="color:#15803d;">FREE (RM 0.00)</span>
                                        <?php else: ?>
                                            RM <?= number_format((float) $exchange->total_price, 2) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:14px 18px;">
                                        <span class="pay-badge <?= $payClass ?>"><?= $payLabel ?></span>
                                    </td>
                                    <td style="padding:14px 18px;text-align:right;">
                                        <div style="display:inline-flex;gap:6px;align-items:center;">
                                            <?php if ($isSeller && $payStatus !== 'paid' && (float)$exchange->total_price > 0): ?>
                                                <form method="POST" action="/business/transactions/<?= $exchange->id ?>/request-payment" style="margin:0;">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn-action-sm" style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;" title="Send payment request & invoice to buyer">
                                                        📩 Minta Bayar
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($payStatus !== 'paid' && (float)$exchange->total_price > 0): ?>
                                                <button type="button" class="btn-action-sm" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;" onclick='openMarkPaidModal(<?= $exchange->id ?>, "<?= e($invNumber) ?>", <?= (float)$exchange->total_price ?>)'>
                                                    💳 Mark Paid
                                                </button>
                                            <?php endif; ?>

                                            <button type="button" class="btn-action-sm" style="background:#f1f5f9;border:1px solid #cbd5e1;color:#334155;" onclick='openReceiptModal("<?= e($invNumber) ?>", "<?= e(addslashes($exchange->listing?->title ?? 'Material')) ?>", "<?= e(addslashes($counterpartName)) ?>", <?= (float)$exchange->quantity ?>, "<?= e($exchange->unit) ?>", <?= (float)$exchange->total_price ?>, "<?= e($payStatus) ?>", "<?= e($exchange->payment_reference ?? 'N/A') ?>", "<?= $exchange->created_at ? $exchange->created_at->format('d M Y') : '' ?>")'>
                                                📄 Invois
                                            </button>
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
                        <div style="font-size:36px;margin-bottom:10px;">🧾</div>
                        <h4 style="margin:0 0 6px;color:#18422d;font-size:16px;">No Transactions Yet</h4>
                        <p style="margin:0 0 16px;color:#556c5e;font-size:13px;max-width:420px;margin-left:auto;margin-right:auto;">
                            When you purchase or supply surplus materials on the Marketplace, orders, invoices, and payment requests will appear here.
                        </p>
                        <a href="/business/marketplace" class="btn-create-listing" style="background:#15803d;color:#fff;border:none;padding:9px 18px;border-radius:6px;font:700 13px var(--font-main);text-decoration:none;display:inline-block;">
                            Explore Marketplace &rarr;
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Mark Paid Modal -->
    <div class="modal-backdrop" id="mark-paid-modal" style="display:none;" onclick="if(event.target===this)closeMarkPaidModal()">
        <div class="modal-dialog" style="max-width:480px;">
            <div class="modal-header">
                <h3>Confirm Payment Receipt</h3>
                <button type="button" class="modal-close-btn" onclick="closeMarkPaidModal()">✕</button>
            </div>
            <form id="mark-paid-form" method="POST" action="">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p style="margin:0;font-size:13px;color:#334155;line-height:1.5;">
                        Confirming payment settlement for invoice <strong id="paid_inv_label">INV-000</strong> totaling <strong id="paid_amt_label" style="color:#15803d;">RM 0.00</strong>.
                    </p>
                    <div class="form-group">
                        <label for="mp_ref">Payment Reference / Transaction ID *</label>
                        <input type="text" id="mp_ref" name="payment_reference" required placeholder="e.g. FPX-9842109 or Bank Transfer Ref" value="FPX-<?= strtoupper(bin2hex(random_bytes(3))) ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeMarkPaidModal()">Cancel</button>
                    <button type="submit" class="btn-save" style="background:#16a34a;border-color:#15803d;">Confirm Payment Complete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Invoice / Receipt Modal -->
    <div class="modal-backdrop" id="receipt-modal" style="display:none;" onclick="if(event.target===this)closeReceiptModal()">
        <div class="modal-dialog" style="max-width:540px;">
            <div class="modal-header">
                <h3 id="rc_title">EcoCycle Electronic Invois</h3>
                <button type="button" class="modal-close-btn" onclick="closeReceiptModal()">✕</button>
            </div>
            <div class="modal-body" style="background:#fafdf9;border:1px solid #e2e8f0;margin:16px;border-radius:10px;padding:20px;">
                <div style="display:flex;justify-content:space-between;border-bottom:1.5px solid #cbd5e1;padding-bottom:12px;margin-bottom:14px;">
                    <div>
                        <strong style="font-size:18px;color:#18422d;display:block;">EcoCycle B2B Settlement</strong>
                        <span style="font-size:12px;color:#64748b;">Circular Economy Trade Platform</span>
                    </div>
                    <div style="text-align:right;">
                        <span id="rc_inv_no" style="font-family:monospace;font-weight:700;font-size:14px;color:#113624;">INV-0000</span>
                        <span id="rc_date" style="font-size:11.5px;color:#64748b;display:block;">12 Nov 2026</span>
                    </div>
                </div>

                <div style="font-size:12.5px;color:#334155;line-height:1.6;margin-bottom:14px;">
                    <div style="display:flex;justify-content:space-between;">
                        <span>Counterparty:</span>
                        <strong id="rc_counterpart">SME Partner</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span>Material:</span>
                        <strong id="rc_material">Surplus Material</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span>Quantity:</span>
                        <span id="rc_qty">100 units</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span>Payment Reference:</span>
                        <span id="rc_ref" style="font-family:monospace;">FPX-12345</span>
                    </div>
                </div>

                <div style="background:#ffffff;border:1.5px solid #86efac;border-radius:8px;padding:12px;display:flex;justify-content:space-between;align-items:center;">
                    <strong style="font-size:14px;color:#15803d;">Total Settlement:</strong>
                    <strong id="rc_total" style="font-size:20px;color:#15803d;">RM 0.00</strong>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="window.print()">🖨 Print Invois</button>
                <button type="button" class="btn-save" onclick="closeReceiptModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
    function openMarkPaidModal(id, invNo, price) {
        document.getElementById('mark-paid-form').action = '/business/transactions/' + id + '/mark-paid';
        document.getElementById('paid_inv_label').textContent = invNo;
        document.getElementById('paid_amt_label').textContent = 'RM ' + price.toFixed(2);
        var m = document.getElementById('mark-paid-modal');
        m.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeMarkPaidModal() {
        document.getElementById('mark-paid-modal').style.display = 'none';
        document.body.style.overflow = '';
    }

    function openReceiptModal(invNo, material, counterpart, qty, unit, total, status, ref, date) {
        document.getElementById('rc_inv_no').textContent = invNo;
        document.getElementById('rc_material').textContent = material;
        document.getElementById('rc_counterpart').textContent = counterpart;
        document.getElementById('rc_qty').textContent = qty + ' ' + unit;
        document.getElementById('rc_total').textContent = total > 0 ? ('RM ' + total.toFixed(2)) : 'FREE CLAIM';
        document.getElementById('rc_ref').textContent = ref;
        document.getElementById('rc_date').textContent = date;
        var m = document.getElementById('receipt-modal');
        m.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeReceiptModal() {
        document.getElementById('receipt-modal').style.display = 'none';
        document.body.style.overflow = '';
    }
    </script>
</body>
</html>
