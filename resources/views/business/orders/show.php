<?php
/** @var \App\Models\User $business */
/** @var \App\Models\MaterialExchange $exchange */
/** @var bool $isSeller */
/** @var bool $isBuyer */
/** @var string $sellerAddress */
/** @var string $pickupAddress */
/** @var array<int, string> $allowedSellerStatuses */
/** @var bool $canBuyerConfirmDelivery */
/** @var bool $canSellerMarkReturned */
$errors = $errors ?? session('errors', new \Illuminate\Support\ViewErrorBag());
$activeNav = 'orders';
$itemTitle = $exchange->item_name ?: ($exchange->item?->name ?: ($exchange->item?->title ?: 'Material #'.$exchange->item_id));
$sellerName = $exchange->seller_business_name ?: ($exchange->provider?->business_name ?: 'Seller Business');
$buyerName = $exchange->buyer_business_name ?: ($exchange->requester?->business_name ?: 'Buyer Business');
$qtyFormatted = rtrim(rtrim(number_format((float) $exchange->quantity, 2, '.', ''), '0'), '.').' '.$exchange->unit;
$isNoPay = in_array($exchange->resolvedListingType(), ['free', 'lend'], true);
$unitPriceDisplay = $isNoPay ? 'RM 0.00' : ('RM '.number_format((float) $exchange->unit_price, 2).' / '.$exchange->unit);
$totalDisplay = 'RM '.number_format($isNoPay ? 0.00 : (float) $exchange->total_price, 2);
$itemPhoto = $exchange->item?->getPrimaryImage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order <?= e($exchange->resolvedOrderReference()) ?> &middot; EcoCycle</title>
    <link rel="icon" type="image/svg+xml" href="/images/figma/ecocycle-leaf.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .od-wrap { max-width: 960px; }
        .od-grid { display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 20px; align-items: start; }
        .od-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 0; margin-bottom: 18px; }
        .od-card-head { padding: 13px 18px; background: #f8fafc; border-bottom: 1px solid #cbd5e1; display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .od-card-head h3 { margin: 0; font-size: 14px; font-weight: 700; color: #143825; }
        .od-card-body { padding: 18px; }
        .od-table { width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1; }
        .od-table th { width: 38%; text-align: left; padding: 10px 12px; background: #f8faf8; border: 1px solid #cbd5e1; font-size: 11.5px; font-weight: 700; color: #475569; text-transform: uppercase; }
        .od-table td { padding: 10px 12px; border: 1px solid #cbd5e1; font-size: 13px; color: #0f172a; }
        .od-timeline { list-style: none; margin: 0; padding: 0; }
        .od-timeline-item { position: relative; padding: 0 0 16px 20px; border-left: 2px solid #cbd5e1; }
        .od-timeline-item:last-child { border-left-color: transparent; padding-bottom: 0; }
        .od-timeline-dot { position: absolute; left: -6px; top: 3px; width: 10px; height: 10px; background: #143825; border: 2px solid #ffffff; outline: 1px solid #143825; }
        .od-btn-primary { padding: 9px 18px; background: #143825; border: 1px solid #143825; color: #ffffff; font-size: 12.5px; font-weight: 700; border-radius: 0; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .od-btn-secondary { padding: 8px 14px; background: #ffffff; border: 1px solid #cbd5e1; color: #334155; font-size: 12.5px; font-weight: 600; border-radius: 0; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .od-input { width: 100%; padding: 9px 11px; border: 1px solid #cbd5e1; border-radius: 0; font-size: 13px; box-sizing: border-box; background: #ffffff; color: #0f172a; }
        @media (max-width: 860px) { .od-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="business-dashboard-page">
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <?php include resource_path('views/business/partials/sidebar.php'); ?>

        <main class="dashboard-main-content">
            <div class="od-wrap">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
                    <div>
                        <a href="<?= route('business.orders.index', ['tab' => $isSeller ? 'sales' : 'purchases']) ?>" class="od-btn-secondary" style="margin-bottom:10px;">
                            &larr; Back to My Orders
                        </a>
                        <h2 style="margin:4px 0 2px;font-size:21px;color:#143825;">Order <?= e($exchange->resolvedOrderReference()) ?></h2>
                        <p style="margin:0;font-size:13px;color:#475569;">
                            Role: <strong><?= $isSeller ? 'Seller (Fulfillment)' : 'Buyer' ?></strong> &middot; Current Delivery Status: <strong><?= e($exchange->deliveryStatusLabel()) ?></strong>
                        </p>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <?php if ($isBuyer && ! $isNoPay && $exchange->requiresStripePayment() && ! in_array($exchange->normalizedPaymentStatus(), ['paid', 'refunded'], true)): ?>
                            <a href="<?= route('business.orders.checkout', $exchange) ?>" class="od-btn-primary">Proceed to Stripe Checkout</a>
                        <?php endif; ?>
                        <?php if ($exchange->invoice): ?>
                            <a href="<?= route('business.invoices.show', $exchange->invoice) ?>" class="od-btn-secondary">View Receipt</a>
                            <a href="<?= route('business.invoices.pdf', $exchange->invoice) ?>" class="od-btn-primary">Download PDF</a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (session('status')): ?>
                    <div style="background:#eef6ed;border:1px solid #b9dec0;border-left:3px solid #176b40;color:#143825;padding:12px 16px;font-size:13px;margin-bottom:16px;font-weight:600;">
                        <?= e(session('status')) ?>
                    </div>
                <?php endif; ?>

                <?php if ($errors->any()): ?>
                    <div style="background:#fff5f4;border:1px solid #fed7d2;border-left:3px solid #b91c1c;color:#b91c1c;padding:12px 16px;font-size:13px;margin-bottom:16px;font-weight:600;">
                        <?= e($errors->first()) ?>
                    </div>
                <?php endif; ?>

                <div class="od-grid">
                    <!-- Left Column: Order Summary & Parties -->
                    <div>
                        <div class="od-card">
                            <div class="od-card-head">
                                <h3>Order Summary</h3>
                                <span style="font-size:11.5px;font-weight:700;padding:3px 9px;background:#eef6ed;border:1px solid #b9dec0;color:#143825;">
                                    <?= e($exchange->listingTypeLabel()) ?>
                                </span>
                            </div>
                            <div class="od-card-body">
                                <?php if ($itemPhoto): ?>
                                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #e2e8f0;">
                                        <img src="<?= e($itemPhoto) ?>" alt="<?= e($itemTitle) ?>" style="width:68px;height:68px;object-fit:cover;border:1px solid #cbd5e1;">
                                        <div>
                                            <strong style="font-size:15px;color:#0f172a;display:block;"><?= e($itemTitle) ?></strong>
                                            <span style="font-size:12.5px;color:#475569;"><?= e($qtyFormatted) ?> &middot; <?= e($unitPriceDisplay) ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <table class="od-table">
                                    <tbody>
                                        <tr><th>Order Reference</th><td style="font-family:monospace;font-weight:700;"><?= e($exchange->resolvedOrderReference()) ?></td></tr>
                                        <tr><th>Item Name</th><td><strong><?= e($itemTitle) ?></strong></td></tr>
                                        <tr><th>Quantity &amp; Unit</th><td><?= e($qtyFormatted) ?></td></tr>
                                        <tr><th>Price per Unit</th><td><?= e($unitPriceDisplay) ?></td></tr>
                                        <tr><th>Total Amount (RM)</th><td style="font-weight:800;color:#143825;"><?= e($totalDisplay) ?></td></tr>
                                        <tr><th>Payment Status</th><td><strong><?= e($exchange->paymentStatusLabel()) ?></strong></td></tr>
                                        <tr><th>Delivery Status</th><td><strong><?= e($exchange->deliveryStatusLabel()) ?></strong></td></tr>
                                        <tr><th>Fulfillment Method</th><td><?= e(ucfirst($exchange->fulfillment_method ?: 'collection')) ?></td></tr>
                                        <tr><th>Collection / Delivery Address</th><td><?= e($pickupAddress) ?></td></tr>
                                        <tr><th>Order Date</th><td><?= $exchange->created_at ? $exchange->created_at->format('d M Y, h:i A') : 'N/A' ?></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="od-card">
                            <div class="od-card-head">
                                <h3>Seller &amp; Buyer Details</h3>
                            </div>
                            <div class="od-card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                                <div style="padding:12px 14px;background:#f8faf8;border:1px solid #cbd5e1;">
                                    <span style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Seller</span>
                                    <strong style="display:block;color:#143825;font-size:13.5px;"><?= e($sellerName) ?></strong>
                                    <div style="font-size:12px;color:#475569;margin-top:4px;line-height:1.45;"><?= e($sellerAddress) ?></div>
                                    <?php if ($exchange->provider?->business_phone): ?>
                                        <div style="font-size:12px;color:#334155;margin-top:4px;">Tel: <?= e($exchange->provider->business_phone) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div style="padding:12px 14px;background:#ffffff;border:1px solid #cbd5e1;">
                                    <span style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Buyer</span>
                                    <strong style="display:block;color:#0f172a;font-size:13.5px;"><?= e($buyerName) ?></strong>
                                    <?php if ($exchange->requester?->business_phone): ?>
                                        <div style="font-size:12px;color:#334155;margin-top:4px;">Tel: <?= e($exchange->requester->business_phone) ?></div>
                                    <?php endif; ?>
                                    <?php if ($exchange->requester?->email): ?>
                                        <div style="font-size:12px;color:#475569;margin-top:2px;"><?= e($exchange->requester->email) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Status Actions & Timeline -->
                    <div>
                        <?php if ($isSeller && ! empty($allowedSellerStatuses)): ?>
                            <div class="od-card" style="border-left:4px solid #143825;">
                                <div class="od-card-head">
                                    <h3>Update Delivery / Collection Status</h3>
                                </div>
                                <div class="od-card-body">
                                    <form method="POST" action="<?= route('business.orders.status', $exchange) ?>">
                                        <?= csrf_field() ?>
                                        <div style="margin-bottom:12px;">
                                            <label for="delivery_status" style="display:block;font-size:12px;font-weight:700;color:#143825;margin-bottom:5px;">Next Status</label>
                                            <select id="delivery_status" name="delivery_status" class="od-input" required>
                                                <?php foreach ($allowedSellerStatuses as $stKey): ?>
                                                    <option value="<?= e($stKey) ?>"><?= e(\App\Models\MaterialExchange::formatDeliveryStatus($stKey)) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div style="margin-bottom:12px;">
                                            <label for="status_note" style="display:block;font-size:12px;font-weight:700;color:#143825;margin-bottom:5px;">Tracking Note (Optional)</label>
                                            <textarea id="status_note" name="note" rows="2" class="od-input" placeholder="e.g. Ready at Loading Bay 2, or courier tracking details"></textarea>
                                        </div>
                                        <button type="submit" class="od-btn-primary" style="width:100%;justify-content:center;">
                                            Update Delivery Status
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($canBuyerConfirmDelivery): ?>
                            <div class="od-card" style="border-left:4px solid #15803d;">
                                <div class="od-card-head">
                                    <h3>Confirm Delivery / Collection</h3>
                                </div>
                                <div class="od-card-body">
                                    <p style="margin:0 0 12px;font-size:12.5px;color:#475569;line-height:1.5;">
                                        Have you collected or received this material from <?= e($sellerName) ?>? Confirming completion lets you leave a verified review.
                                    </p>
                                    <form method="POST" action="<?= route('business.orders.status', $exchange) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="delivery_status" value="completed">
                                        <input type="hidden" name="note" value="Buyer confirmed material received / collected.">
                                        <button type="submit" class="od-btn-primary" style="width:100%;justify-content:center;">
                                            Confirm Material Received
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($canSellerMarkReturned): ?>
                            <div class="od-card" style="border-left:4px solid #1d4ed8;">
                                <div class="od-card-head">
                                    <h3>Lend Return Confirmation</h3>
                                </div>
                                <div class="od-card-body">
                                    <p style="margin:0 0 12px;font-size:12.5px;color:#475569;line-height:1.5;">
                                        Once <?= e($buyerName) ?> returns the borrowed <?= e($qtyFormatted) ?>, click below to restore the reserved stock back to your available inventory.
                                    </p>
                                    <form method="POST" action="<?= route('business.orders.return', $exchange) ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="od-btn-primary" style="width:100%;justify-content:center;background:#1d4ed8;border-color:#1d4ed8;">
                                            Confirm Borrowed Item Returned &amp; Restore Stock
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($isBuyer && $exchange->isReviewable()): ?>
                            <div class="od-card" style="border-left:4px solid #d97706;">
                                <div class="od-card-head">
                                    <h3>Verified Buyer Review</h3>
                                </div>
                                <div class="od-card-body">
                                    <?php if ($exchange->review): ?>
                                        <div style="font-size:13px;font-weight:700;color:#143825;margin-bottom:4px;">
                                            Rating: <?= (int) $exchange->review->rating ?> / 5 Stars
                                        </div>
                                        <p style="margin:0;font-size:12.5px;color:#334155;line-height:1.5;">
                                            <?= e($exchange->review->comment) ?>
                                        </p>
                                        <div style="margin-top:8px;font-size:11.5px;color:#64748b;">
                                            Submitted on <?= $exchange->review->created_at?->format('d M Y') ?>
                                        </div>
                                    <?php else: ?>
                                        <form method="POST" action="<?= route('business.orders.review', $exchange) ?>">
                                            <?= csrf_field() ?>
                                            <div style="margin-bottom:10px;">
                                                <label for="order_review_rating" style="display:block;font-size:12px;font-weight:700;color:#143825;margin-bottom:5px;">Rating (1 to 5 Stars)</label>
                                                <select id="order_review_rating" name="rating" class="od-input" required>
                                                    <option value="5">5 Stars — Excellent</option>
                                                    <option value="4">4 Stars — Good</option>
                                                    <option value="3">3 Stars — Satisfactory</option>
                                                    <option value="2">2 Stars — Fair</option>
                                                    <option value="1">1 Star — Poor</option>
                                                </select>
                                            </div>
                                            <div style="margin-bottom:12px;">
                                                <label for="order_review_comment" style="display:block;font-size:12px;font-weight:700;color:#143825;margin-bottom:5px;">Review Comment</label>
                                                <textarea id="order_review_comment" name="comment" rows="3" class="od-input" required placeholder="Share your feedback on material quality, accuracy, and seller communication..."></textarea>
                                            </div>
                                            <button type="submit" class="od-btn-primary" style="width:100%;justify-content:center;">
                                                Submit Verified Review
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Delivery Status History Timeline -->
                        <div class="od-card">
                            <div class="od-card-head">
                                <h3>Delivery Status History</h3>
                                <span style="font-size:11px;color:#64748b;"><?= $exchange->statusHistory->count() ?> events</span>
                            </div>
                            <div class="od-card-body">
                                <?php if ($exchange->statusHistory->isNotEmpty()): ?>
                                    <ul class="od-timeline">
                                        <?php foreach ($exchange->statusHistory as $event): ?>
                                            <li class="od-timeline-item">
                                                <span class="od-timeline-dot"></span>
                                                <div style="font-size:13px;font-weight:700;color:#143825;">
                                                    <?= e(\App\Models\MaterialExchange::formatDeliveryStatus($event->status)) ?>
                                                </div>
                                                <?php if ($event->note): ?>
                                                    <div style="font-size:12.5px;color:#334155;margin-top:2px;line-height:1.45;">
                                                        <?= e($event->note) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div style="font-size:11px;color:#64748b;margin-top:3px;">
                                                    <?= $event->created_at ? $event->created_at->format('d M Y, h:i A') : '' ?>
                                                    <?php if ($event->updatedBy): ?>
                                                        &middot; Updated by <?= e($event->updatedBy->business_name ?: $event->updatedBy->name) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p style="margin:0;font-size:12.5px;color:#64748b;">
                                        Current status: <strong><?= e($exchange->deliveryStatusLabel()) ?></strong>. Status updates will appear here with timestamps.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
