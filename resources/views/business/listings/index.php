<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Listings &amp; Customer Feedback &middot; EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .listing-manage-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .my-listing-card {
            background: #ffffff;
            border: 1px solid #d8e5d6;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .my-listing-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }
        .my-listing-thumb {
            height: 140px;
            background: #eef4ed;
            position: relative;
            overflow: hidden;
        }
        .my-listing-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .my-listing-body {
            padding: 14px 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .review-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
        }
        .star-rating {
            color: #eab308;
            font-size: 14px;
            letter-spacing: 2px;
        }
    </style>
</head>
<body class="business-dashboard-page">
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <?php 
            $activeNav = 'listings';
            include resource_path('views/business/partials/sidebar.php'); 
        ?>

        <main class="dashboard-main-content">
            <header class="section-header" style="margin-bottom:20px;">
                <div class="section-header-title">
                    <h2>My Published Listings &amp; Feedback</h2>
                    <p>Track the status of your surplus materials, check items sold or borrowed, and review ratings and feedback from trading partners.</p>
                </div>
                <div>
                    <a href="/business/marketplace" class="btn-create-listing" style="background:#15803d;color:#fff;border:none;padding:10px 18px;border-radius:8px;font:700 13px var(--font-main);text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                        <span>+ Create New Listing</span>
                    </a>
                </div>
            </header>

            <?php if (session('status')): ?>
                <div style="background:#edf7ed;border:1.5px solid #a3cfbb;color:#1e4620;padding:12px 18px;border-radius:8px;font-size:13px;margin-bottom:20px;font-weight:600;">
                    ✓ <?= session('status') ?>
                </div>
            <?php endif; ?>

            <!-- KPI Metric Cards -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));gap:16px;margin-bottom:24px;">
                <div style="background:#ffffff;border:1px solid #d8e5d6;border-radius:10px;padding:16px 18px;border-left:4px solid #16a34a;">
                    <span style="font-size:11.5px;font-weight:700;color:#556c5e;text-transform:uppercase;">Active Listings</span>
                    <div style="font-size:26px;font-weight:800;color:#15803d;font-family:var(--font-display);"><?= $totalActive ?></div>
                    <small style="color:#617769;">Currently visible on marketplace</small>
                </div>
                <div style="background:#ffffff;border:1px solid #d8e5d6;border-radius:10px;padding:16px 18px;border-left:4px solid #2563eb;">
                    <span style="font-size:11.5px;font-weight:700;color:#556c5e;text-transform:uppercase;">Items Sold / Claimed</span>
                    <div style="font-size:26px;font-weight:800;color:#1d4ed8;font-family:var(--font-display);"><?= $totalSold ?></div>
                    <small style="color:#617769;">Successful circular exchanges</small>
                </div>
                <div style="background:#ffffff;border:1px solid #d8e5d6;border-radius:10px;padding:16px 18px;border-left:4px solid #eab308;">
                    <span style="font-size:11.5px;font-weight:700;color:#556c5e;text-transform:uppercase;">Borrowed Materials</span>
                    <div style="font-size:26px;font-weight:800;color:#a16207;font-family:var(--font-display);"><?= $totalBorrowed ?></div>
                    <small style="color:#617769;">Under loan / deposit terms</small>
                </div>
                <div style="background:#ffffff;border:1px solid #d8e5d6;border-radius:10px;padding:16px 18px;border-left:4px solid #f59e0b;">
                    <span style="font-size:11.5px;font-weight:700;color:#556c5e;text-transform:uppercase;">Partner Rating</span>
                    <div style="font-size:26px;font-weight:800;color:#b45309;font-family:var(--font-display);">
                        ★ <?= $avgRating ?> <small style="font-size:13px;font-weight:600;">(<?= $totalReviewCount ?> reviews)</small>
                    </div>
                    <small style="color:#617769;">Average customer satisfaction</small>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;border-bottom:1px solid #dce6db;padding-bottom:10px;">
                <a href="/business/my-listings?status=all" class="sidebar-link <?= $currentTab === 'all' ? 'active' : '' ?>" style="display:inline-flex;padding:7px 14px;border-radius:6px;text-decoration:none;font-size:13px;font-weight:700;">
                    All Listings (<?= $listings->total() ?>)
                </a>
                <a href="/business/my-listings?status=active" class="sidebar-link <?= $currentTab === 'active' ? 'active' : '' ?>" style="display:inline-flex;padding:7px 14px;border-radius:6px;text-decoration:none;font-size:13px;font-weight:700;">
                    🟢 Active (<?= $totalActive ?>)
                </a>
                <a href="/business/my-listings?status=sold" class="sidebar-link <?= $currentTab === 'sold' ? 'active' : '' ?>" style="display:inline-flex;padding:7px 14px;border-radius:6px;text-decoration:none;font-size:13px;font-weight:700;">
                    ✓ Sold / Claimed (<?= $totalSold ?>)
                </a>
                <a href="/business/my-listings?status=borrowed" class="sidebar-link <?= $currentTab === 'borrowed' ? 'active' : '' ?>" style="display:inline-flex;padding:7px 14px;border-radius:6px;text-decoration:none;font-size:13px;font-weight:700;">
                    🤝 Borrowed (<?= $totalBorrowed ?>)
                </a>
                <a href="#reviews-section" style="margin-left:auto;color:#166534;font-size:13px;font-weight:700;text-decoration:underline;">
                    View Feedback &amp; Reviews (<?= $totalReviewCount ?>) &darr;
                </a>
            </div>

            <!-- Listings Grid -->
            <?php if ($listings->isNotEmpty()): ?>
                <div class="listing-manage-grid">
                    <?php foreach ($listings as $item): 
                        $imgs = $item->getAllImages();
                        $thumb = !empty($imgs) ? $imgs[0] : 'https://placehold.co/300x180/e8f5e9/1b5e20?text=Material';
                        $isSold = ($item->status === 'sold');
                    ?>
                        <article class="my-listing-card">
                            <div class="my-listing-thumb">
                                <img src="<?= e($thumb) ?>" alt="<?= e($item->title) ?>">
                                <div style="position:absolute;top:8px;right:8px;display:flex;gap:4px;">
                                    <?php if ($isSold): ?>
                                        <span style="background:#fee2e2;color:#991b1b;font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:4px;border:1px solid #fecaca;">SOLD / CLAIMED</span>
                                    <?php else: ?>
                                        <span style="background:#dcfce7;color:#15803d;font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:4px;border:1px solid #86efac;">ACTIVE</span>
                                    <?php endif; ?>
                                </div>
                                <div style="position:absolute;bottom:8px;left:8px;">
                                    <span class="offer-type-badge <?= e($item->listing_type ?: 'buy') ?>" style="box-shadow:0 2px 4px rgba(0,0,0,0.15);">
                                        <?= ucfirst($item->listing_type ?: 'buy') ?>
                                    </span>
                                </div>
                            </div>
                            <div class="my-listing-body">
                                <h3 style="margin:0;font-size:15px;color:#113624;font-family:var(--font-display);"><?= e($item->title) ?></h3>
                                <span style="font-size:12px;color:#617769;"><?= e($item->category) ?> &middot; <?= (float) $item->quantity ?> <?= e($item->unit) ?></span>
                                
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto;padding-top:8px;border-top:1px solid #eef4ed;">
                                    <strong style="color:#15803d;font-size:15px;">
                                        <?= (float)$item->price > 0 ? ('RM ' . number_format((float)$item->price, 2)) : 'FREE CLAIM' ?>
                                    </strong>

                                    <!-- Status Switch Actions -->
                                    <div style="display:flex;gap:6px;">
                                        <form method="POST" action="/business/my-listings/<?= $item->id ?>/status" style="margin:0;">
                                            <?= csrf_field() ?>
                                            <?php if ($item->status === 'active'): ?>
                                                <input type="hidden" name="status" value="sold">
                                                <button type="submit" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:700;cursor:pointer;" title="Mark this material as sold / fully claimed">
                                                    ✓ Mark Sold
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" style="background:#eef6ff;border:1px solid #bfdbfe;color:#1d4ed8;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:700;cursor:pointer;" title="Reactivate this listing">
                                                    ↻ Re-list
                                                </button>
                                            <?php endif; ?>
                                        </form>

                                        <form method="POST" action="/business/marketplace/<?= $item->id ?>" onsubmit="return confirm('Remove listing permanently?');" style="margin:0;">
                                            <?= csrf_field() ?>
                                            <?= method_field('DELETE') ?>
                                            <button type="submit" style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:4px 7px;border-radius:4px;font-size:11px;cursor:pointer;" title="Delete">✕</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div style="margin-bottom:32px;">
                    <?= $listings->links()?->toHtml() ?>
                </div>
            <?php else: ?>
                <div style="background:#ffffff;border:1px solid #d8e5d6;border-radius:10px;padding:48px 24px;text-align:center;margin-bottom:32px;">
                    <div style="font-size:36px;margin-bottom:10px;">📦</div>
                    <h4 style="margin:0 0 6px;color:#18422d;font-size:16px;">No Listings in this View</h4>
                    <p style="margin:0 0 16px;color:#556c5e;font-size:13px;max-width:420px;margin-left:auto;margin-right:auto;">
                        List circular reusable materials on the Marketplace to help other SMEs find secondary supplies.
                    </p>
                    <a href="/business/marketplace" class="btn-create-listing" style="background:#15803d;color:#fff;border:none;padding:9px 18px;border-radius:6px;font:700 13px var(--font-main);text-decoration:none;display:inline-block;">
                        Create Listing on Marketplace &rarr;
                    </a>
                </div>
            <?php endif; ?>

            <!-- Feedback & Reviews Section -->
            <section id="reviews-section" style="background:#ffffff;border:1px solid #d8e5d6;border-radius:12px;padding:22px 24px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;border-bottom:1.5px solid #eef4ed;padding-bottom:12px;">
                    <div>
                        <h3 style="margin:0 0 4px;color:#123020;font-size:18px;">Customer Reviews &amp; Partner Feedback</h3>
                        <p style="margin:0;font-size:12.5px;color:#556c5e;">Feedback left by verified businesses who have inquired, purchased, or borrowed materials from you.</p>
                    </div>
                    <div style="text-align:right;">
                        <span class="star-rating" style="font-size:20px;">★ <?= $avgRating ?></span>
                        <small style="color:#617769;display:block;font-weight:600;"><?= $totalReviewCount ?> Verified Reviews</small>
                    </div>
                </div>

                <?php if ($reviews->isNotEmpty()): ?>
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <?php foreach ($reviews as $rev): 
                            $reviewerName = $rev->reviewer?->business_name ?: ($rev->reviewer?->name ?? 'Verified Partner');
                        ?>
                            <div class="review-card">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <strong style="color:#113624;font-size:13.5px;"><?= e($reviewerName) ?></strong>
                                        <span style="background:#edf7ed;color:#15803d;font-size:10.5px;font-weight:700;padding:2px 6px;border-radius:4px;">Verified SME</span>
                                        <span style="font-size:11px;color:#64748b;">&middot; <?= ucfirst($rev->exchange_type) ?> Transaction</span>
                                    </div>
                                    <div class="star-rating">
                                        <?= str_repeat('★', (int) $rev->rating) ?><?= str_repeat('☆', 5 - (int) $rev->rating) ?>
                                    </div>
                                </div>
                                <p style="margin:0 0 8px;font-size:13px;color:#2f4637;line-height:1.5;">
                                    "<?= e($rev->comment) ?>"
                                </p>
                                <div style="font-size:11.5px;color:#64748b;">
                                    Item: <strong><?= e($rev->listing?->title ?: 'Material') ?></strong> &middot; <?= $rev->created_at ? $rev->created_at->format('d M Y') : '' ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center;padding:32px 16px;">
                        <div style="font-size:32px;margin-bottom:8px;">💬</div>
                        <h4 style="margin:0 0 4px;color:#18422d;font-size:15px;">No Reviews Yet</h4>
                        <p style="margin:0;font-size:12.5px;color:#617769;">
                            When other businesses complete purchases or borrow items from you, their ratings and circular feedback will appear here.
                        </p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
