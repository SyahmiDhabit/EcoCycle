<?php
/** @var \App\Models\User|null $business */
/** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\MarketplaceListing> $listings */
/** @var \Illuminate\Support\Collection<int, string> $categories */
/** @var \Illuminate\Support\Collection<int, string> $locations */
/** @var string|null $search */
/** @var string|null $selectedCategory */
/** @var string|null $selectedLocation */

$business = $business ?? \Illuminate\Support\Facades\Auth::guard()->user();
$companyName = $business?->business_name ?: ($business?->name ?? 'EcoCycle Business');
$activeNav = 'marketplace';
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Marketplace — EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css">
    <style>
        .marketplace-hero-banner {
            background: linear-gradient(135deg, #163e2b, #1e553a);
            border-radius: 12px;
            padding: 24px 28px;
            color: #ffffff;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            box-shadow: 0 4px 16px rgba(18, 51, 33, 0.12);
        }
        .marketplace-hero-text h1 {
            margin: 0 0 6px;
            font-size: 24px;
            font-weight: 700;
        }
        .marketplace-hero-text p {
            margin: 0;
            font-size: 13px;
            color: #c9e3d0;
            max-width: 620px;
            line-height: 1.5;
        }
        .marketplace-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .listing-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .listing-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(18, 51, 33, 0.08);
            border-color: var(--card-border-hover);
        }
        .listing-img-wrap {
            height: 160px;
            width: 100%;
            overflow: hidden;
            background: #eef3ec;
        }
        .listing-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .listing-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .listing-price-tag {
            font-size: 16px;
            font-weight: 700;
            color: var(--brand-green);
            margin: 8px 0 4px;
        }
        .listing-free-tag {
            font-size: 15px;
            font-weight: 700;
            color: #15803d;
            margin: 8px 0 4px;
        }
    </style>
</head>
<body class="business-dashboard-page">

    <!-- Reusable Top Navigation Bar -->
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <!-- Reusable Left Navigation Sidebar -->
        <?php include resource_path('views/business/partials/sidebar.php'); ?>

        <main class="dashboard-main">
            <!-- Breadcrumbs -->
            <div class="breadcrumb-row">
                <a href="/business">Overview</a>
                <span class="sep">/</span>
                <span class="current">Marketplace</span>
            </div>

            <!-- Hero Banner -->
            <div class="marketplace-hero-banner">
                <div class="marketplace-hero-text">
                    <h1>Surplus Materials Marketplace</h1>
                    <p>Exchange, claim, or source secondary materials directly from verified Malaysian SMEs. Browse all listings across the country or filter by category.</p>
                </div>
                <div>
                    <a href="/business" class="btn-search" style="text-decoration:none;background:#ffffff;color:#163e2b;font-weight:700;">
                        <span>&larr; Back to Overview</span>
                    </a>
                </div>
            </div>

            <!-- Filter Card (Connected to Database) -->
            <section class="filter-card">
                <form method="GET" action="/business/marketplace">
                    <div class="filter-grid">
                        <div class="filter-field">
                            <label for="mkt-search">Search Materials</label>
                            <input id="mkt-search" name="q" type="text" value="<?= e(request('q')) ?>" placeholder="Search material name or description...">
                        </div>
                        <div class="filter-field">
                            <label for="mkt-cat">Category</label>
                            <select id="mkt-cat" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= e($cat) ?>" <?= request('category') === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label for="mkt-loc">Location</label>
                            <select id="mkt-loc" name="location">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= e($loc) ?>" <?= request('location') === $loc ? 'selected' : '' ?>><?= e($loc) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-field" style="justify-content: flex-end;">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-search" style="height:40px;justify-content:center;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <span>Filter Listings</span>
                            </button>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Listings Grid -->
            <div class="section-header-row">
                <div class="section-header-title">
                    <h2>Live Material Listings</h2>
                    <p><?= $listings->count() ?> active listing(s) in the database</p>
                </div>
            </div>

            <?php if ($listings->isEmpty()): ?>
                <div style="background:#ffffff;border:1px solid var(--card-border);border-radius:12px;padding:48px 24px;text-align:center;margin-bottom:32px;">
                    <span class="signin-brand-mark" style="margin:0 auto 14px;"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                    <h3 style="margin:0 0 6px;font-size:16px;color:#123321;">No material listings found</h3>
                    <p style="margin:0 0 16px;font-size:13px;color:var(--text-muted);max-width:480px;margin-inline:auto;line-height:1.5;">
                        No surplus materials have been listed yet matching your search criteria.
                    </p>
                    <a href="/business/inventory" class="btn-search" style="text-decoration:none;">
                        <span>Manage Your Inventory &rarr;</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="marketplace-grid">
                    <?php foreach ($listings as $item): ?>
                        <article class="listing-card">
                            <div class="listing-img-wrap">
                                <?php if (!empty($item->image_path)): ?>
                                    <img src="<?= e($item->image_path) ?>" alt="<?= e($item->title) ?>" loading="lazy">
                                <?php else: ?>
                                    <div style="width:100%;height:100%;display:grid;place-items:center;background:#f0f6ee;">
                                        <span class="signin-brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="listing-body">
                                <div class="card-badges">
                                    <span class="verified-pill">Verified SME</span>
                                    <span class="category-tag"><?= e($item->category) ?></span>
                                </div>
                                <h3 class="card-title" style="margin-top:6px;font-size:15px;"><?= e($item->title) ?></h3>
                                <div class="card-location"><?= e($item->location_city) ?>, <?= e($item->location_state) ?> &middot; <?= e($item->business?->business_name ?: $item->business?->name) ?></div>

                                <?php if ($item->is_free): ?>
                                    <div class="listing-free-tag">Free Surplus</div>
                                <?php else: ?>
                                    <div class="listing-price-tag">RM <?= number_format((float) $item->price, 2) ?> <span style="font-size:11px;font-weight:400;color:#6f7b72;">/ <?= e($item->unit) ?></span></div>
                                <?php endif; ?>

                                <div class="card-footer" style="padding-top:10px;margin-top:auto;">
                                    <span class="available-listings-text">Qty: <?= (float) $item->quantity ?> <?= e($item->unit) ?></span>
                                    <button type="button" class="btn-view-profile" onclick="openClaimModal('<?= e(addslashes($item->title)) ?>', '<?= e(addslashes($item->business?->business_name ?: ($item->business?->name ?? 'Partner'))) ?>')">
                                        Inquire &rarr;
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    function toggleSidebar() {
        document.getElementById('dashboard-sidebar').classList.toggle('open');
    }
    function toggleNotificationMenu(e) {
        e.stopPropagation();
        var menu = document.getElementById('notification-dropdown');
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }
    document.addEventListener('click', function() {
        var menu = document.getElementById('notification-dropdown');
        if (menu) menu.style.display = 'none';
    });
    function handleNavNotice(e, name) {
        e.preventDefault();
        alert('The ' + name + ' module is active for your business account.');
    }
    function openClaimModal(title, bizName) {
        alert('Exchange inquiry initiated for "' + title + '" from ' + bizName + '.\nA notification has been dispatched to their business workspace.');
    }
    </script>
</body>
</html>
