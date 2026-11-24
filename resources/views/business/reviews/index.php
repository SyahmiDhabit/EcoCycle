<?php
/** @var \App\Models\User $business */
/** @var string $currentTab */
/** @var \Illuminate\Support\Collection<int, \App\Models\MaterialExchange> $eligibleOrders */
/** @var \Illuminate\Support\Collection<int, \App\Models\MarketplaceReview> $submittedReviews */
/** @var \Illuminate\Support\Collection<int, \App\Models\MarketplaceReview> $receivedReviews */
/** @var float $averageSellerRating */
$errors = $errors ?? session('errors', new \Illuminate\Support\ViewErrorBag());
$activeNav = 'reviews';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews &middot; EcoCycle</title>
    <link rel="icon" type="image/svg+xml" href="/images/figma/ecocycle-leaf.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .rev-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; margin-bottom: 22px; }
        .rev-kpi-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 0; padding: 16px 18px; }
        .rev-kpi-label { display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
        .rev-kpi-val { font-size: 24px; font-weight: 800; color: #143825; font-family: var(--font-display); }
        .rev-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid #cbd5e1; }
        .rev-tab { padding: 8px 16px; border: 1px solid #cbd5e1; border-radius: 0; background: #ffffff; color: #334155; font-size: 12.5px; font-weight: 700; text-decoration: none; }
        .rev-tab.active { background: #143825; border-color: #143825; color: #ffffff; }
        .rev-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 0; padding: 18px 20px; margin-bottom: 14px; }
        .rev-input { width: 100%; padding: 9px 11px; border: 1px solid #cbd5e1; border-radius: 0; font-size: 13px; box-sizing: border-box; background: #ffffff; color: #0f172a; }
        .rev-btn { padding: 9px 18px; background: #143825; border: 1px solid #143825; color: #ffffff; font-size: 12.5px; font-weight: 700; border-radius: 0; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
        .rev-stars { color: #d97706; font-weight: 800; letter-spacing: 1px; font-size: 14px; }
    </style>
</head>
<body class="business-dashboard-page">
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <?php include resource_path('views/business/partials/sidebar.php'); ?>

        <main class="dashboard-main-content">
            <header class="section-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                <div class="section-header-title">
                    <h2>My Reviews</h2>
                    <p>Verified buyer reviews submitted after delivery or collection, and seller ratings received by your business.</p>
                </div>
                <a href="<?= route('business.orders.index') ?>" class="rev-tab">View My Orders</a>
            </header>

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

            <div class="rev-kpi-grid">
                <div class="rev-kpi-card" style="border-left:3px solid #d97706;">
                    <span class="rev-kpi-label">Eligible Orders to Review</span>
                    <span class="rev-kpi-val"><?= $eligibleOrders->count() ?></span>
                </div>
                <div class="rev-kpi-card" style="border-left:3px solid #143825;">
                    <span class="rev-kpi-label">Reviews Submitted</span>
                    <span class="rev-kpi-val"><?= $submittedReviews->count() ?></span>
                </div>
                <div class="rev-kpi-card" style="border-left:3px solid #15803d;">
                    <span class="rev-kpi-label">Seller Rating Received</span>
                    <span class="rev-kpi-val"><?= $receivedReviews->isNotEmpty() ? number_format($averageSellerRating, 1).' / 5.0' : 'No ratings yet' ?></span>
                    <span style="display:block;font-size:11.5px;color:#64748b;margin-top:2px;"><?= $receivedReviews->count() ?> verified buyer <?= $receivedReviews->count() === 1 ? 'review' : 'reviews' ?></span>
                </div>
            </div>

            <div class="rev-tabs">
                <a href="<?= route('business.reviews.index', ['tab' => 'eligible']) ?>" class="rev-tab <?= $currentTab === 'eligible' ? 'active' : '' ?>">
                    Orders Ready to Review (<?= $eligibleOrders->count() ?>)
                </a>
                <a href="<?= route('business.reviews.index', ['tab' => 'submitted']) ?>" class="rev-tab <?= $currentTab === 'submitted' ? 'active' : '' ?>">
                    Submitted Reviews (<?= $submittedReviews->count() ?>)
                </a>
                <a href="<?= route('business.reviews.index', ['tab' => 'received']) ?>" class="rev-tab <?= $currentTab === 'received' ? 'active' : '' ?>">
                    Reviews Received as Seller (<?= $receivedReviews->count() ?>)
                </a>
            </div>

            <?php if ($currentTab === 'eligible'): ?>
                <?php if ($eligibleOrders->isNotEmpty()): ?>
                    <?php foreach ($eligibleOrders as $order):
                        $itemTitle = $order->item_name ?: ($order->item?->name ?: 'Material #'.$order->item_id);
                        $sellerName = $order->seller_business_name ?: ($order->provider?->business_name ?: 'Seller Business');
                        $qtyFormatted = rtrim(rtrim(number_format((float) $order->quantity, 2, '.', ''), '0'), '.').' '.$order->unit;
                    ?>
                        <div class="rev-card">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #e2e8f0;">
                                <div>
                                    <strong style="font-size:15px;color:#143825;display:block;"><?= e($itemTitle) ?></strong>
                                    <span style="font-size:12.5px;color:#475569;">
                                        Seller: <strong><?= e($sellerName) ?></strong> &middot; Quantity: <?= e($qtyFormatted) ?> &middot; Purchase Date: <?= $order->created_at?->format('d M Y') ?>
                                    </span>
                                </div>
                                <span style="padding:3px 9px;background:#dcfce7;border:1px solid #86efac;color:#15803d;font-size:11px;font-weight:700;">
                                    <?= e($order->deliveryStatusLabel()) ?>
                                </span>
                            </div>

                            <form method="POST" action="<?= route('business.orders.review', $order) ?>">
                                <?= csrf_field() ?>
                                <div style="display:grid;grid-template-columns:220px 1fr;gap:14px;align-items:start;">
                                    <div>
                                        <label for="rating_<?= $order->id ?>" style="display:block;font-size:12px;font-weight:700;color:#143825;margin-bottom:5px;">Rating (1 to 5 Stars)</label>
                                        <select id="rating_<?= $order->id ?>" name="rating" class="rev-input" required>
                                            <option value="5">5 Stars — Excellent</option>
                                            <option value="4">4 Stars — Good</option>
                                            <option value="3">3 Stars — Satisfactory</option>
                                            <option value="2">2 Stars — Fair</option>
                                            <option value="1">1 Star — Poor</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="comment_<?= $order->id ?>" style="display:block;font-size:12px;font-weight:700;color:#143825;margin-bottom:5px;">Review Comment</label>
                                        <textarea id="comment_<?= $order->id ?>" name="comment" rows="2" class="rev-input" required placeholder="Describe the material quality, packaging, and seller reliability..."></textarea>
                                    </div>
                                </div>
                                <div style="margin-top:12px;text-align:right;">
                                    <button type="submit" class="rev-btn">Submit Verified Review</button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="rev-card" style="text-align:center;padding:42px 24px;">
                        <h4 style="margin:0 0 6px;color:#143825;">No Delivered Orders Pending Review</h4>
                        <p style="margin:0;font-size:13px;color:#64748b;">
                            Verified buyers can rate and review an order once its delivery status reaches Delivered, Collected, Completed, or Returned.
                        </p>
                    </div>
                <?php endif; ?>

            <?php elseif ($currentTab === 'submitted'): ?>
                <?php if ($submittedReviews->isNotEmpty()): ?>
                    <?php foreach ($submittedReviews as $rev):
                        $itemTitle = $rev->exchange?->item_name ?: ($rev->listing?->name ?: 'Material');
                        $sellerName = $rev->exchange?->seller_business_name ?: ($rev->seller?->business_name ?: 'Seller Business');
                        $buyerName = $business->business_name ?: $business->name;
                        $purchaseDate = $rev->exchange?->created_at?->format('d M Y') ?: 'N/A';
                        $stars = str_repeat('★', (int) $rev->rating).str_repeat('☆', max(0, 5 - (int) $rev->rating));
                    ?>
                        <div class="rev-card">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:8px;">
                                <div>
                                    <strong style="font-size:15px;color:#143825;"><?= e($itemTitle) ?></strong>
                                    <div style="font-size:12px;color:#475569;margin-top:2px;">
                                        Seller: <strong><?= e($sellerName) ?></strong> &middot; Buyer: <?= e($buyerName) ?> &middot; Purchase Date: <?= e($purchaseDate) ?>
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <span class="rev-stars"><?= $stars ?></span>
                                    <span style="display:block;font-size:11.5px;color:#64748b;"><?= (int) $rev->rating ?>/5 &middot; Reviewed <?= $rev->created_at?->format('d M Y') ?></span>
                                </div>
                            </div>
                            <p style="margin:0;font-size:13px;color:#0f172a;line-height:1.55;"><?= e($rev->comment) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="rev-card" style="text-align:center;padding:42px 24px;">
                        <h4 style="margin:0 0 6px;color:#143825;">No Reviews Submitted Yet</h4>
                        <p style="margin:0;font-size:13px;color:#64748b;">Reviews you submit for delivered orders will appear here.</p>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <?php if ($receivedReviews->isNotEmpty()): ?>
                    <?php foreach ($receivedReviews as $rev):
                        $itemTitle = $rev->exchange?->item_name ?: ($rev->listing?->name ?: 'Material');
                        $buyerName = $rev->exchange?->buyer_business_name ?: ($rev->reviewer?->business_name ?: 'Buyer Business');
                        $sellerName = $business->business_name ?: $business->name;
                        $purchaseDate = $rev->exchange?->created_at?->format('d M Y') ?: 'N/A';
                        $stars = str_repeat('★', (int) $rev->rating).str_repeat('☆', max(0, 5 - (int) $rev->rating));
                    ?>
                        <div class="rev-card">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:8px;">
                                <div>
                                    <strong style="font-size:15px;color:#143825;"><?= e($itemTitle) ?></strong>
                                    <div style="font-size:12px;color:#475569;margin-top:2px;">
                                        Buyer: <strong><?= e($buyerName) ?></strong> &middot; Seller: <?= e($sellerName) ?> &middot; Purchase Date: <?= e($purchaseDate) ?>
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <span class="rev-stars"><?= $stars ?></span>
                                    <span style="display:block;font-size:11.5px;color:#64748b;"><?= (int) $rev->rating ?>/5 &middot; Reviewed <?= $rev->created_at?->format('d M Y') ?></span>
                                </div>
                            </div>
                            <p style="margin:0;font-size:13px;color:#0f172a;line-height:1.55;"><?= e($rev->comment) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="rev-card" style="text-align:center;padding:42px 24px;">
                        <h4 style="margin:0 0 6px;color:#143825;">No Seller Reviews Received Yet</h4>
                        <p style="margin:0;font-size:13px;color:#64748b;">Once buyers confirm delivery and rate your completed orders, their verified reviews will appear here and on your Marketplace listings.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
