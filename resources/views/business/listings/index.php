<!DOCTYPE html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Listings &amp; Customer Feedback &middot; EcoCycle</title>
    <link rel="icon" type="image/svg+xml" href="/images/figma/ecocycle-leaf.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .listing-manage-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 290px), 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }
        .my-listing-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: border-color 0.15s ease;
        }
        .my-listing-card:hover {
            border-color: var(--brand-green);
        }
        .my-listing-thumb {
            height: 148px;
            background: #eef4ed;
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid var(--card-border);
        }
        .my-listing-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .my-listing-body {
            padding: 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .review-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 0;
            padding: 16px 18px;
            margin-bottom: 12px;
        }
        .rating-score {
            color: #92400e;
            font-size: 13px;
            font-weight: 700;
            background: #fef3c7;
            border: 1px solid #fde68a;
            padding: 2px 8px;
            display: inline-block;
        }
        .listings-workspace {
            padding-inline: max(clamp(20px, 4vw, 48px), calc((100% - 1180px) / 2));
        }
        .listings-workspace .page-header {
            align-items: center;
            flex-wrap: wrap;
            gap: 12px 20px;
            padding: 0;
            border: 0;
        }
        .listings-workspace .page-header h1 {
            color: #14532d;
            font-size: clamp(26px, 3vw, 40px);
        }
        .listing-toolbar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 20px;
            padding: 6px;
            background: #e4eee3;
            border: 1px solid var(--card-border);
        }
        .listing-toolbar > a:last-child { padding: 0 8px; }
        .listing-tab {
            display: inline-flex;
            align-items: center;
            min-height: 40px;
            padding: 8px 14px;
            border: 1px solid #a3bfa6;
            background: #fff;
            color: #143825;
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
        }
        .listing-tab:hover { background: #eef5eb; }
        .listings-workspace :is(.listing-tab.active, .btn-search) {
            background: #176b40;
            border-color: #176b40;
            color: #fff;
        }
        .listings-workspace :is(.listing-tab.active, .btn-search):hover { background: #105331; }
        .listings-workspace :is(.listing-tab, .btn-search, .my-listing-card button) {
            border-radius: 6px !important;
        }
        .listings-workspace :is(.my-listing-card, .review-card) {
            box-shadow: 0 5px 12px rgba(20, 83, 45, .05);
        }
        .listings-workspace :is(a, button):focus-visible {
            outline: 2px solid #176b40;
            outline-offset: 2px;
        }
    </style>
    <link rel="stylesheet" href="/css/site-preferences.css?v=<?= filemtime(public_path('css/site-preferences.css')) ?>">
    <script src="/js/site-theme.js?v=<?= filemtime(public_path('js/site-theme.js')) ?>"></script>
    <script src="/js/language-switcher.js?v=<?= filemtime(public_path('js/language-switcher.js')) ?>" defer></script>
</head>
<body class="business-dashboard-page">
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <?php 
            $activeNav = 'listings';
            include resource_path('views/business/partials/sidebar.php'); 
        ?>

        <main class="dashboard-main-content listings-workspace">
            <header class="page-header">
                <div class="section-header-title">
                    <h1>My Published Listings &amp; Feedback</h1>
                    <p>Track the status of your reusable materials, view Sell and Free listings, and review feedback from partner businesses.</p>
                </div>
                <div>
                    <a href="/business/marketplace" class="btn-search" style="text-decoration:none;">
                        <span>Create New Listing</span>
                    </a>
                </div>
            </header>

            <?php if (session('status')): ?>
                <div style="background:#eef6ed;border:1px solid #b9dec0;border-left:3px solid #176b40;color:#143825;padding:12px 18px;border-radius:0;font-size:13px;margin-bottom:20px;font-weight:600;">
                    <?= session('status') ?>
                </div>
            <?php endif; ?>

            <!-- KPI Metric Cards -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(min(100%, 210px), 1fr));gap:16px;margin-bottom:24px;">
                <div style="background:#ffffff;border:1px solid var(--card-border);border-radius:0;padding:16px 18px;border-left:3px solid #176b40;">
                    <span style="font-size:11px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.04em;">Active Listings</span>
                    <div style="font-size:26px;font-weight:800;color:#15803d;font-family:var(--font-display);margin:4px 0;"><?= $totalActive ?></div>
                    <small style="color:var(--text-muted);">Currently visible on marketplace</small>
                </div>
                <div style="background:#ffffff;border:1px solid var(--card-border);border-radius:0;padding:16px 18px;border-left:3px solid #1d4ed8;">
                    <span style="font-size:11px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.04em;">Items Sold / Claimed</span>
                    <div style="font-size:26px;font-weight:800;color:#1d4ed8;font-family:var(--font-display);margin:4px 0;"><?= $totalSold ?></div>
                    <small style="color:var(--text-muted);">Successful circular exchanges</small>
                </div>
                <div style="background:#ffffff;border:1px solid var(--card-border);border-radius:0;padding:16px 18px;border-left:3px solid #a16207;">
                    <span style="font-size:11px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.04em;">Free Listings</span>
                    <div style="font-size:26px;font-weight:800;color:#a16207;font-family:var(--font-display);margin:4px 0;"><?= (int) ($totalFree ?? 0) ?></div>
                    <small style="color:var(--text-muted);">Available at RM 0.00</small>
                </div>
                <div style="background:#ffffff;border:1px solid var(--card-border);border-radius:0;padding:16px 18px;border-left:3px solid #b45309;">
                    <span style="font-size:11px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.04em;">Rating</span>
                    <div style="font-size:26px;font-weight:800;color:#b45309;font-family:var(--font-display);margin:4px 0;">
                        <?= $avgRating ?> / 5 <small style="font-size:13px;font-weight:600;">(<?= $totalReviewCount ?> reviews)</small>
                    </div>
                    <small style="color:var(--text-muted);">Average customer satisfaction</small>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="listing-toolbar">
                <a href="/business/my-listings?status=all" class="listing-tab <?= $currentTab === 'all' ? 'active' : '' ?>">
                    All Listings (<?= $listings->total() ?>)
                </a>
                <a href="/business/my-listings?status=active" class="listing-tab <?= $currentTab === 'active' ? 'active' : '' ?>">
                    Active (<?= $totalActive ?>)
                </a>
                <a href="/business/my-listings?status=sold" class="listing-tab <?= $currentTab === 'sold' ? 'active' : '' ?>">
                    Sold / Claimed (<?= $totalSold ?>)
                </a>
                <a href="/business/my-listings?status=free" class="listing-tab <?= $currentTab === 'free' ? 'active' : '' ?>">
                    Free (<?= (int) ($totalFree ?? 0) ?>)
                </a>
                <a href="#reviews-section" style="margin-left:auto;color:#176b40;font-size:12.5px;font-weight:700;text-decoration:underline;">
                    View Feedback &amp; Reviews (<?= $totalReviewCount ?>)
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
                                        <span style="background:#fee2e2;color:#991b1b;font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:0;border:1px solid #fecaca;">SOLD / CLAIMED</span>
                                    <?php else: ?>
                                        <span style="background:#dcfce7;color:#15803d;font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:0;border:1px solid #86efac;">ACTIVE</span>
                                    <?php endif; ?>
                                </div>
                                <div style="position:absolute;bottom:8px;left:8px;">
                                    <span class="offer-pill <?= e($item->listing_type === 'free' ? 'free' : 'buy') ?>" style="margin-left:0;">
                                        <?= $item->listing_type === 'free' ? 'Free' : 'Sell' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="my-listing-body">
                                <h3 style="margin:0;font-size:15px;color:#113624;font-family:var(--font-display);"><?= e($item->title) ?></h3>
                                <span style="font-size:12px;color:var(--text-secondary);"><?= e($item->category) ?> &middot; <?= (float) $item->quantity ?> <?= e($item->unit) ?></span>
                                
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto;padding-top:10px;border-top:1px solid #e7eee6;">
                                    <strong style="color:#15803d;font-size:15px;">
                                        <?= (float)$item->price > 0 ? ('RM ' . number_format((float)$item->price, 2)) : 'FREE CLAIM' ?>
                                    </strong>

                                    <!-- Status Switch Actions -->
                                    <div style="display:flex;gap:6px;">
                                        <form method="POST" action="/business/my-listings/<?= $item->id ?>/status" style="margin:0;">
                                            <?= csrf_field() ?>
                                            <?php if ($item->status === 'active'): ?>
                                                <input type="hidden" name="status" value="sold">
                                                <button type="submit" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;padding:5px 10px;border-radius:0;font-size:11.5px;font-weight:600;cursor:pointer;" title="Mark this material as sold / fully claimed">
                                                    Mark Sold
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" style="background:#eef6ff;border:1px solid #bfdbfe;color:#1d4ed8;padding:5px 10px;border-radius:0;font-size:11.5px;font-weight:600;cursor:pointer;" title="Reactivate this listing">
                                                    Re-list
                                                </button>
                                            <?php endif; ?>
                                        </form>

                                        <form method="POST" action="/business/marketplace/<?= $item->id ?>" onsubmit="return confirm('Remove listing permanently?');" style="margin:0;">
                                            <?= csrf_field() ?>
                                            <?= method_field('DELETE') ?>
                                            <button type="submit" style="background:#fff5f5;border:1px solid #fecaca;color:#991b1b;padding:5px 10px;border-radius:0;font-size:11.5px;font-weight:600;cursor:pointer;" title="Delete">Delete</button>
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
                <div style="background:#ffffff;border:1px solid var(--card-border);border-radius:0;padding:48px 24px;text-align:center;margin-bottom:32px;">
                    <h4 style="margin:0 0 6px;color:#143825;font-size:16px;">No Listings in this View</h4>
                    <p style="margin:0 0 18px;color:var(--text-secondary);font-size:13px;max-width:440px;margin-left:auto;margin-right:auto;">
                        List circular reusable materials on the Marketplace to help other SMEs find secondary supplies.
                    </p>
                    <a href="/business/marketplace" class="btn-search" style="text-decoration:none;">
                        Create Listing on Marketplace
                    </a>
                </div>
            <?php endif; ?>

            <!-- Feedback & Reviews Section -->
            <section id="reviews-section" style="background:#ffffff;border:1px solid var(--card-border);border-radius:0;padding:24px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;border-bottom:1px solid var(--card-border);padding-bottom:12px;">
                    <div>
                        <h3 style="margin:0 0 4px;color:#123020;font-size:17px;">Customer Reviews &amp; Partner Feedback</h3>
                        <p style="margin:0;font-size:12.5px;color:var(--text-secondary);">Feedback left by verified businesses who have purchased or claimed materials from you.</p>
                    </div>
                    <div style="text-align:right;">
                        <span class="rating-score" style="font-size:15px;"><?= $avgRating ?> / 5</span>
                        <small style="color:var(--text-muted);display:block;font-weight:600;margin-top:4px;"><?= $totalReviewCount ?> Verified Reviews</small>
                    </div>
                </div>

                <?php if ($reviews->isNotEmpty()): ?>
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <?php foreach ($reviews as $rev): 
                            $reviewerName = $rev->reviewer?->business_name ?: ($rev->reviewer?->name ?? 'Verified Partner');
                        ?>
                            <div class="review-card">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                        <strong style="color:#113624;font-size:13.5px;"><?= e($reviewerName) ?></strong>
                                        <span style="background:#edf7ed;color:#15803d;font-size:10.5px;font-weight:700;padding:2px 6px;border:1px solid #c2dec0;">Verified SME</span>
                                        <span style="font-size:11.5px;color:var(--text-muted);">&middot; <?= ucfirst($rev->exchange_type) ?> Transaction</span>
                                    </div>
                                    <div class="rating-score">
                                        <?= (int) $rev->rating ?> / 5
                                    </div>
                                </div>
                                <p style="margin:0 0 8px;font-size:13px;color:#2f4637;line-height:1.5;">
                                    "<?= e($rev->comment) ?>"
                                </p>
                                <div style="font-size:11.5px;color:var(--text-muted);">
                                    Item: <strong><?= e($rev->listing?->title ?: 'Material') ?></strong> &middot; <?= $rev->created_at ? $rev->created_at->format('d M Y') : '' ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center;padding:32px 16px;">
                        <h4 style="margin:0 0 4px;color:#143825;font-size:15px;">No Reviews Yet</h4>
                        <p style="margin:0;font-size:12.5px;color:var(--text-secondary);">
                            When other businesses complete purchases or free claims from you, their ratings and feedback will appear here.
                        </p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
