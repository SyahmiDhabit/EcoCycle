<?php

use App\Models\InventoryItem;
use App\Models\MarketplaceListing;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/** @var User|null $business */
/** @var Illuminate\Database\Eloquent\Collection<int, MarketplaceListing> $listings */
/** @var Collection<int, string> $categories */
/** @var Collection<int, string> $locations */
/** @var Illuminate\Database\Eloquent\Collection<int, InventoryItem> $myInventoryItems */
/** @var string|null $search */
/** @var string|null $selectedCategory */
/** @var string|null $selectedLocation */
/** @var string|null $selectedListingType */
/** @var bool $myListingsOnly */
$business = $business ?? Auth::guard()->user();
$companyName = $business?->business_name ?: ($business?->name ?? 'EcoCycle Business');
$myListingsOnly = $myListingsOnly ?? request()->boolean('my_listings');
$currentTab = $currentTab ?? (request('tab') ?: ($myListingsOnly ? 'my_listings' : 'reusable'));
$reusableCount = $reusableCount ?? MarketplaceListing::visibleInMarketplace()->count();
$myListingsCount = $myListingsCount ?? ($business ? $business->marketplaceListings()->visibleInMarketplace()->count() : 0);
$activeNav = $myListingsOnly ? 'listings' : 'marketplace';
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $myListingsOnly ? 'My Listings' : 'Reusable Materials Marketplace' ?> — EcoCycle</title>
    <link rel="icon" type="image/svg+xml" href="/images/figma/ecocycle-leaf.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .marketplace-tabs {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 22px;
            border-bottom: 1px solid var(--card-border);
            padding-bottom: 10px;
        }
        .mkt-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: 1px solid var(--card-border);
            border-radius: 0;
            background: #ffffff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }
        .mkt-tab-btn:hover {
            color: var(--brand-green);
            background: #eef6ec;
            border-color: var(--brand-green);
        }
        .mkt-tab-btn.active {
            color: #ffffff;
            background: var(--forest-bg);
            border-color: var(--forest-bg);
            font-weight: 700;
        }
        .mkt-tab-badge {
            background: #f1f5f0;
            color: #1a4d2e;
            padding: 1px 7px;
            border-radius: 0;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid #c4d9c0;
        }
        .mkt-tab-btn.active .mkt-tab-badge {
            background: rgba(255, 255, 255, 0.16);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.3);
        }

        .marketplace-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
            gap: 20px;
        }

        /* Professional Rectangular Card Styling */
        .listing-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: border-color 0.15s ease;
            position: relative;
        }
        .listing-card:hover {
            border-color: var(--brand-green);
        }

        /* Multi-Image Gallery Wrap */
        .listing-img-container {
            position: relative;
            height: 190px;
            width: 100%;
            background: #eef4ed;
            overflow: hidden;
            border-bottom: 1px solid var(--card-border);
        }
        .listing-img-slide {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .listing-sold-out-ribbon {
            position: absolute;
            top: 25px;
            right: -37px;
            z-index: 3;
            width: 152px;
            padding: 4px 0;
            transform: rotate(45deg);
            background: #37443d;
            color: #ffffff;
            text-align: center;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.06em;
            pointer-events: none;
        }
        .listing-img-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 28px;
            height: 28px;
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid var(--card-border);
            border-radius: 0;
            color: #123321;
            font-size: 14px;
            font-weight: 700;
            display: grid;
            place-items: center;
            cursor: pointer;
            z-index: 4;
            opacity: 0;
            transition: opacity 0.15s ease, background-color 0.15s ease;
        }
        .listing-card:hover .listing-img-nav-btn {
            opacity: 1;
        }
        .listing-img-nav-btn:hover {
            background: #ffffff;
            color: #0e291b;
        }
        .listing-img-nav-btn.prev { left: 8px; }
        .listing-img-nav-btn.next { right: 8px; }

        .listing-dots-indicator {
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 4px;
            z-index: 4;
            background: rgba(15, 23, 42, 0.55);
            padding: 3px 6px;
            border-radius: 0;
        }
        .listing-dot {
            width: 6px;
            height: 4px;
            border-radius: 0;
            background: rgba(255, 255, 255, 0.5);
        }
        .listing-dot.active {
            background: #ffffff;
            width: 14px;
        }

        .img-count-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(20, 56, 37, 0.88);
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 0;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            z-index: 3;
            letter-spacing: 0.02em;
        }

        .reusability-corner-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 3;
        }

        /* Card Body */
        .listing-body {
            padding: 16px 18px 18px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .card-badges-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }

        .offer-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 0;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .offer-type-badge.buy {
            background: #e8f5e9;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .offer-type-badge.borrow {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .offer-type-badge.free {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .condition-pill {
            font-size: 10px;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 0;
            padding: 2px 6px;
        }

        .listing-heading {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 4px;
        }
        .listing-heading .listing-title {
            min-width: 0;
        }
        .listing-heading .seller-rating-button {
            flex-shrink: 0;
            white-space: nowrap;
        }

        .listing-title {
            font-family: var(--font-display);
            font-size: 16px;
            font-weight: 700;
            color: #123321;
            margin: 4px 0 6px;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .listing-details {
            margin: 0 0 10px;
            padding-left: 18px;
            color: var(--text-secondary);
            font-size: 12px;
            line-height: 1.5;
        }

        .listing-company-row {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 10px;
        }
        .listing-company-row strong {
            color: #1c3d2a;
            font-weight: 600;
        }

        .listing-pricing-box {
            background: var(--surface-subtle);
            border: 1px solid var(--card-border);
            border-radius: 0;
            padding: 10px 12px;
            margin-bottom: 12px;
            display: flex;
            align-items: baseline;
            justify-content: space-between;
        }
        .price-main {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 800;
            color: #15803d;
        }
        .price-main.borrow-price {
            color: #0369a1;
        }
        .price-main.free-price {
            color: #15803d;
        }
        .price-unit {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 500;
            margin-left: 4px;
        }
        .qty-badge {
            font-size: 11px;
            font-weight: 600;
            color: #1b5e20;
            background: #eaf3e8;
            padding: 2px 8px;
            border-radius: 0;
            border: 1px solid #c2dec0;
        }

        .listing-desc-excerpt {
            font-size: 12.5px;
            color: var(--text-secondary);
            line-height: 1.45;
            margin: 0 0 14px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 36px;
        }

        .listing-card-footer {
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid #e7eee6;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .btn-view-quick {
            background: #ffffff;
            border: 1px solid var(--input-border);
            color: #244b32;
            padding: 7px 12px;
            border-radius: 0;
            font: 600 12px var(--font-main);
            cursor: pointer;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }
        .btn-view-quick:hover {
            background: #eef6ec;
            border-color: var(--brand-green);
        }

        .btn-claim-action {
            background: var(--forest-bg);
            border: 1px solid var(--forest-bg);
            color: #ffffff;
            padding: 7px 14px;
            border-radius: 0;
            font: 600 12px var(--font-main);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.15s ease;
        }
        .listing-card.is-sold-out {
            background: #f2f4f2;
        }
        .listing-card.is-sold-out .qty-badge,
        .marketplace-page .btn-claim-action:disabled {
            background: #e8ece9;
            border-color: #ccd4ce;
            color: #425148;
        }
        .marketplace-page .btn-claim-action:disabled {
            cursor: not-allowed;
        }
        .btn-claim-action:hover {
            background: #0d281a;
        }

        /* Create Listing Modal Multi-Image Upload Area */
        .image-upload-dropzone {
            border: 1px dashed #9fb3a0;
            background: var(--surface-subtle);
            border-radius: 0;
            padding: 20px;
            text-align: center;
            cursor: pointer;
        }
        .image-upload-dropzone:hover {
            border-color: var(--brand-green);
            background: #edf6eb;
        }
        .preview-thumbs-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin-top: 12px;
        }
        .thumb-preview-card {
            position: relative;
            height: 72px;
            border-radius: 0;
            overflow: hidden;
            border: 1px solid var(--card-border);
            background: #ffffff;
        }
        .thumb-preview-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .thumb-cover-tag {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(20, 56, 37, 0.9);
            color: #ffffff;
            font-size: 9px;
            font-weight: 700;
            text-align: center;
            padding: 2px 0;
            text-transform: uppercase;
        }

        .filter-row-top {
            display: grid;
            grid-template-columns: 2fr 1.2fr 1fr 1fr 1fr auto;
            gap: 12px;
            align-items: flex-end;
        }
        @media (max-width: 1100px) {
            .filter-row-top {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 640px) {
            .filter-row-top {
                grid-template-columns: 1fr;
            }
            .preview-thumbs-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        .marketplace-workspace {
            padding-inline: max(clamp(20px, 4vw, 48px), calc((100% - 1180px) / 2));
        }
        .marketplace-workspace .page-header {
            align-items: center;
            justify-content: flex-start;
            flex-wrap: wrap;
            gap: 16px;
            padding: 0;
            border: 0;
        }
        .marketplace-workspace .page-header > div {
            flex: 1 1 480px;
            min-width: 0;
        }
        .marketplace-workspace .page-header h1 {
            color: #14532d;
            font-size: clamp(26px, 3vw, 40px);
        }
        .marketplace-workspace .marketplace-tabs {
            margin-bottom: 0;
            gap: 6px;
            padding: 6px;
            width: fit-content;
            max-width: 100%;
            background: #e4eee3;
            border: 1px solid var(--card-border);
        }
        .marketplace-workspace .mkt-tab-btn {
            min-height: 40px;
        }
        .marketplace-workspace :is(.mkt-tab-btn.active, .filter-card .btn-search) {
            background: #176b40;
            border-color: #176b40;
        }
        .marketplace-page :is(.marketplace-workspace, .modal-overlay, .seller-reviews-dialog)
        :is(button, .mkt-tab-btn, .btn-search, .btn-back-directory, .btn-view-quick, .btn-claim-action, .btn-choose-photos) {
            border-radius: 6px !important;
        }
        .marketplace-filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        @media (max-width: 1100px) {
            .marketplace-workspace .marketplace-filter-actions {
                grid-column: 1 / -1;
                justify-content: flex-end;
            }
        }
        .marketplace-workspace .filter-card {
            box-shadow: 0 5px 12px rgba(20, 83, 45, .05);
        }
        .marketplace-workspace .section-header-title h2 {
            color: #14532d;
            font-size: clamp(19px, 2vw, 24px);
        }
        .marketplace-workspace .marketplace-grid {
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 310px), 1fr));
        }
        .marketplace-workspace .listing-card {
            min-width: 0;
            box-shadow: 0 5px 12px rgba(20, 83, 45, .05);
        }
        .marketplace-workspace :is(a, button, input, select):focus-visible {
            outline: 2px solid #176b40;
            outline-offset: 2px;
        }
        .seller-rating-button {
            color: #815014;
            background: #fff8ea;
            border: 1px solid #e9d8b5;
            border-radius: 5px;
            padding: 4px 7px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }
        .seller-rating-button:hover { background: #f8edda; }
        .seller-reviews-dialog {
            width: min(580px, calc(100% - 32px));
            max-height: 85dvh;
            padding: 0;
            color: var(--text-primary);
            border: 1px solid var(--card-border);
            background: #fff;
            box-shadow: 0 12px 35px #14382533;
        }
        .seller-reviews-dialog::backdrop { background: #102c20aa; }
        .seller-reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 18px 20px;
            background: #f1f6ef;
            border-bottom: 1px solid var(--card-border);
        }
        .seller-reviews-header :is(h2, p) { margin: 0; }
        .seller-reviews-header p { margin-top: 4px; font-size: 13px; }
        .seller-reviews-content { padding: 4px 20px 20px; }
        #seller-review-list { list-style: none; margin: 0; padding: 0; }
        #seller-review-list li { padding: 14px 0; border-bottom: 1px solid var(--card-border); overflow-wrap: anywhere; }
        #seller-review-list small { display: block; margin-top: 4px; color: var(--text-secondary); }
        #seller-review-list p { margin: 8px 0 0; white-space: pre-wrap; }
        #seller-review-more { margin-top: 14px; }
    </style>
</head>
<body class="business-dashboard-page marketplace-page">
    <!-- Reusable Top Navigation Bar -->
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <!-- Reusable Left Navigation Sidebar -->
        <?php include resource_path('views/business/partials/sidebar.php'); ?>

        <main class="dashboard-main marketplace-workspace">
            <!-- Page Title Header -->
            <div class="page-header">
                <div>
                    <h1><?= $myListingsOnly ? 'My marketplace listings' : 'Reusable materials marketplace' ?></h1>
                    <p>Purchase or claim reusable materials directly from verified Malaysian SMEs.</p>
                </div>
                <nav class="marketplace-tabs" aria-label="Marketplace views">
                    <a href="/business/marketplace?tab=reusable" class="mkt-tab-btn <?= $currentTab === 'reusable' ? 'active' : '' ?>">
                        <span>Reusable Items</span>
                        <span class="mkt-tab-badge"><?= $reusableCount ?></span>
                    </a>
                    <a href="/business/marketplace?my_listings=1" class="mkt-tab-btn <?= $currentTab === 'my_listings' ? 'active' : '' ?>">
                        <span>My Listings</span>
                        <span class="mkt-tab-badge"><?= $myListingsCount ?></span>
                    </a>
                </nav>
            </div>

            <!-- Flash Status Messages -->
            <?php if (session('status')) { ?>
                <div style="margin-bottom:18px;background:#e8f5e9;border:1px solid #c2dec0;border-left:3px solid #176b40;color:#1b5e20;padding:12px 16px;border-radius:0;font-size:13px;display:flex;align-items:center;gap:8px;font-weight:600;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><?= e(session('status')) ?></span>
                </div>
            <?php } ?>

            <?php if (session('error')) { ?>
                <div style="margin-bottom:18px;background:#fff5f5;border:1px solid #fecaca;border-left:3px solid #b91c1c;color:#991b1b;padding:12px 16px;border-radius:0;font-size:13px;display:flex;align-items:center;gap:8px;font-weight:600;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span><?= e(session('error')) ?></span>
                </div>
            <?php } ?>

            <!-- Filter Card -->
            <section class="filter-card">
                <form method="GET" action="/business/marketplace">
                    <?php if ($myListingsOnly) { ?>
                        <input type="hidden" name="my_listings" value="1">
                    <?php } ?>

                    <div class="filter-row-top">
                        <div class="filter-field">
                            <label for="mkt-search">Search Materials</label>
                            <input id="mkt-search" name="q" type="text" value="<?= e(request('q')) ?>" placeholder="Search material name or keyword...">
                        </div>

                        <div class="filter-field">
                            <label for="mkt-cat">Category</label>
                            <select id="mkt-cat" name="category">
                                <option value="">All Categories</option>
                                <?php
                                    $allMktCats = collect(\App\Models\Item::ALLOWED_CATEGORIES)
                                        ->merge($categories ?? collect())
                                        ->unique()
                                        ->filter()
                                        ->values();
                                ?>
                                <?php foreach ($allMktCats as $cat) { ?>
                                    <option value="<?= e($cat) ?>" <?= request('category') === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="filter-field">
                            <label for="mkt-type">Listing Type</label>
                            <select id="mkt-type" name="listing_type">
                                <option value="">All Types</option>
                                <option value="buy" <?= request('listing_type') === 'buy' ? 'selected' : '' ?>>Sell</option>
                                <option value="free" <?= request('listing_type') === 'free' ? 'selected' : '' ?>>Free</option>
                            </select>
                        </div>

                        <div class="filter-field">
                            <label for="mkt-loc">Location</label>
                            <select id="mkt-loc" name="location">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $loc) { ?>
                                    <option value="<?= e($loc) ?>" <?= request('location') === $loc ? 'selected' : '' ?>><?= e($loc) ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="filter-field">
                            <label for="mkt-stock-status">Stock Status</label>
                            <select id="mkt-stock-status" name="stock_status">
                                <option value="">All Stock</option>
                                <option value="in_stock" <?= $selectedStockStatus === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                                <option value="out_of_stock" <?= $selectedStockStatus === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                            </select>
                        </div>

                        <div class="marketplace-filter-actions">
                            <button type="submit" class="btn-search" style="height:40px;padding:0 14px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <span>Filter</span>
                            </button>
                            <?php if (request()->hasAny(['q', 'category', 'location', 'listing_type', 'stock_status'])) { ?>
                                <a href="<?= $myListingsOnly ? '/business/marketplace?my_listings=1' : '/business/marketplace' ?>" class="btn-back-directory" style="height:40px;padding:0 12px;display:grid;place-items:center;" title="Reset filters">
                                    Reset
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Section Title -->
            <div class="section-header-row" style="margin-top:20px;">
                <div class="section-header-title">
                    <h2><?= $myListingsOnly ? 'Your Published Listings' : 'Live Material Listings' ?></h2>
                    <p><?= $listings->count() ?> listing(s) in this view</p>
                </div>
            </div>

            <!-- Listings Grid -->
            <?php if ($listings->isEmpty()) { ?>
                <div style="background:#ffffff;border:1px solid var(--card-border);border-radius:0;padding:52px 24px;text-align:center;margin-bottom:32px;">
                    <span class="signin-brand-mark" style="margin:0 auto 14px;"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                    <h3 style="margin:0 0 6px;font-size:17px;color:#123321;font-weight:700;">No material listings found</h3>
                    <p style="margin:0 0 20px;font-size:13px;color:var(--text-muted);max-width:500px;margin-inline:auto;line-height:1.5;">
                        <?= $myListingsOnly ? 'You have not published any material listings yet. List surplus items with 1 to 3 photos to share them with verified Malaysian SMEs.' : 'No surplus materials matched your filter criteria. Try clearing filters or create a new listing.' ?>
                    </p>
                    <div style="display:flex;justify-content:center;gap:12px;">
                        <button type="button" class="btn-claim-action" onclick="openCreateListingModal()">
                            <span>+ Create New Listing</span>
                        </button>
                        <a href="/business/inventory" class="btn-view-quick" style="text-decoration:none;">
                            <span>Manage Inventory &rarr;</span>
                        </a>
                    </div>
                </div>
            <?php } else { ?>
                <div class="marketplace-grid">
                    <?php foreach ($listings as $idx => $item) { ?>
                        <?php
                        $isOwner = $item->business_id === $business->id;
                        $listingType = $item->listing_type ?: ($item->is_free ? 'free' : 'buy');
                        $isSoldOut = (float) $item->quantity <= 0;
                        $isReusable = ($item->reusability === 'reusable');
                        $images = $item->getAllImages();
                        $imgCount = count($images);
                        $cardId = 'listing-card-'.$item->id;
                        $listingRating = $listingRatings[(int) $item->id] ?? null;
                        $pickupAddr = trim((string) ($item->pickup_address ?: implode(', ', array_filter([$item->business?->registered_address, $item->location_city, $item->location_state]))));
                        ?>
                        <article class="listing-card<?= $isSoldOut ? ' is-sold-out' : '' ?>" id="<?= $cardId ?>"
                            onclick="openListingDetailModal(<?= $item->id ?>)"
                            style="cursor: pointer;"
                            data-title="<?= e($item->title) ?>"
                            data-category="<?= e($item->category) ?>"
                            data-condition="<?= e($item->condition ?? 'Used') ?>"
                            data-type="<?= e($listingType) ?>"
                            data-reusability="<?= e($item->reusability) ?>"
                            data-price="<?= (float) $item->price ?>"
                            data-qty="<?= (float) $item->quantity ?>"
                            data-unit="<?= e($item->unit) ?>"
                            data-city="<?= e($item->location_city) ?>"
                            data-state="<?= e($item->location_state) ?>"
                            data-pickup="<?= e($pickupAddr) ?>"
                            data-owner="<?= $isOwner ? '1' : '0' ?>"
                            data-company="<?= e($item->business?->business_name ?: ($item->business?->name ?? 'Partner')) ?>"
                            data-desc="<?= e($item->description) ?>"
                            data-images='<?= json_encode($images) ?>'
                        >
                            <!-- Multi-Image Carousel / Gallery Area -->
                            <div class="listing-img-container" id="gallery-container-<?= $item->id ?>">
                                <?php if ($isSoldOut) { ?>
                                    <span class="listing-sold-out-ribbon" aria-hidden="true">Sold Out</span>
                                <?php } ?>
                                <?php if ($imgCount > 0) { ?>
                                    <img src="<?= e($images[0]) ?>" alt="<?= e($item->title) ?>" class="listing-img-slide" id="img-display-<?= $item->id ?>" loading="lazy">
                                    
                                    <?php if ($imgCount > 1) { ?>
                                        <button type="button" class="listing-img-nav-btn prev" onclick="slideListingImage(<?= $item->id ?>, -1, event)" aria-label="Previous image">&lsaquo;</button>
                                        <button type="button" class="listing-img-nav-btn next" onclick="slideListingImage(<?= $item->id ?>, 1, event)" aria-label="Next image">&rsaquo;</button>
                                        
                                        <div class="listing-dots-indicator" id="dots-container-<?= $item->id ?>">
                                            <?php for ($i = 0; $i < $imgCount; $i++) { ?>
                                                <span class="listing-dot <?= $i === 0 ? 'active' : '' ?>"></span>
                                            <?php } ?>
                                        </div>

                                        <div class="img-count-badge">
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                            <span id="img-counter-<?= $item->id ?>">1/<?= $imgCount ?></span>
                                        </div>
                                    <?php } else { ?>
                                        <div class="img-count-badge">
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                            <span>1 photo</span>
                                        </div>
                                    <?php } ?>
                                <?php } else { ?>
                                    <div style="width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#eef4ed;gap:8px;">
                                        <span class="signin-brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                                        <span style="font-size:11px;font-weight:600;color:#617769;">Verified SME Material</span>
                                    </div>
                                <?php } ?>

                                <?php if (! $isReusable) { ?>
                                    <div class="reusability-corner-badge">
                                        <span class="badge-not-reusable" style="background:#ffffff;font-size:11px;">Non-Reusable</span>
                                    </div>
                                <?php } ?>
                            </div>

                            <!-- Card Body -->
                            <div class="listing-body">
                                <div class="listing-heading">
                                    <h3 class="listing-title" title="<?= e($item->title) ?>"><?= e($item->title) ?></h3>
                                    <?php if ($listingRating): ?>
                                        <button type="button" class="seller-rating-button" aria-haspopup="dialog"
                                            data-seller-reviews-url="<?= e(route('business.marketplace.seller-reviews', $item)) ?>"
                                            data-item-name="<?= e($item->title) ?>"
                                            aria-label="View item reviews, rated <?= number_format((float) $listingRating['avg'], 1) ?> out of 5">
                                            &#9733; <?= number_format((float) $listingRating['avg'], 1) ?> (<?= (int) $listingRating['count'] ?>)
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <ul class="listing-details">
                                    <li><?= match ($listingType) { 'free' => 'Free', 'lend' => 'Lend', default => 'Sell' } ?></li>
                                    <li><?= e($item->category) ?></li>
                                    <li><?= e($item->condition ?? 'Used') ?></li>
                                </ul>

                                <div class="listing-company-row" style="justify-content:space-between;flex-wrap:wrap;">
                                    <span style="display:inline-flex;align-items:center;gap:5px;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        <span><?= e($item->location_city) ?>, <?= e($item->location_state) ?> &middot; <strong><?= e($item->business?->business_name ?: ($item->business?->name ?? 'Partner')) ?></strong></span>
                                    </span>
                                </div>

                                <!-- Pricing Box -->
                                <div class="listing-pricing-box">
                                    <div>
                                        <?php if ($listingType === 'free') { ?>
                                            <span class="price-main free-price">FREE</span>
                                            <span class="price-unit">(RM 0.00)</span>
                                        <?php } elseif ($listingType === 'lend') { ?>
                                            <span class="price-main borrow-price">LEND</span>
                                            <span class="price-unit">(RM 0.00 &middot; Returnable)</span>
                                        <?php } else { ?>
                                            <span class="price-main">RM <?= number_format((float) $item->price, 2) ?></span>
                                            <span class="price-unit">/ <?= e($item->unit) ?></span>
                                        <?php } ?>
                                    </div>
                                    <span class="qty-badge"><?php if ($isSoldOut) { ?>Sold Out<?php } else { ?><?= (float) $item->quantity ?><?= (float) $item->original_quantity > 0 ? ' / '.(float) $item->original_quantity : '' ?> <?= e($item->unit) ?> left<?php } ?></span>
                                </div>

                                <?php if (! empty($item->description)) { ?>
                                    <p class="listing-desc-excerpt"><?= e($item->description) ?></p>
                                <?php } ?>

                                <div class="listing-card-footer">
                                    <button type="button" class="btn-view-quick" onclick="event.stopPropagation(); openListingDetailModal(<?= $item->id ?>)">
                                        Quick View
                                    </button>

                                    <?php if ($isSoldOut) { ?>
                                        <button type="button" class="btn-claim-action" disabled>Out of Stock</button>
                                    <?php } elseif ($isOwner) { ?>
                                        <div style="display:flex;align-items:center;gap:6px;" onclick="event.stopPropagation();">
                                            <span style="font-size:11px;font-weight:700;color:#166534;background:#f0fdf4;padding:4px 8px;border-radius:0;border:1px solid #bbf7d0;">Your Listing</span>
                                            <form method="POST" action="/business/marketplace/<?= $item->id ?>" onsubmit="return confirm('Are you sure you want to remove this listing?');" style="margin:0;" onclick="event.stopPropagation();">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="submit" style="background:#fff5f5;border:1px solid #fca5a5;color:#991b1b;padding:5px 9px;border-radius:0;font-size:11px;font-weight:600;cursor:pointer;" title="Delete listing">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    <?php } elseif (! $isReusable) { ?>
                                        <a href="/business/helper?item_id=<?= $item->id ?>" class="btn-claim-action" style="background:#1e3a8a;border-color:#1e3a8a;color:#ffffff;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:12px;padding:8px 14px;" onclick="event.stopPropagation();" title="Forward non-reusable item to EcoCycle Helper">
                                            <span>Ask Helper</span>
                                        </a>
                                    <?php } else { ?>
                                        <button type="button" class="btn-claim-action" onclick="event.stopPropagation(); openExchangeOrderModal(<?= $item->id ?>)">
                                            <?= match ($listingType) {
                                                'free' => 'Claim Item',
                                                'lend' => 'Request to Borrow',
                                                default => 'Buy Now &rarr;',
                                            } ?>
                                        </button>
                                    <?php } ?>
                                </div>
                            </div>
                        </article>
                    <?php } ?>
                </div>
            <?php } ?>
        </main>
    </div>

    <!-- Step 1: Inventory Item Picker Modal -->
    <div class="modal-overlay" id="select-inventory-modal" style="display:none;" onclick="if(event.target===this)closeSelectInventoryModal()">
        <div class="modal-card" style="max-width:680px;">
            <div class="modal-header">
                <div>
                    <h3 style="margin:0;">Select Reusable Item from Inventory</h3>
                    <small style="color:#526b5a;font-size:12px;">Choose a verified reusable material from your inventory to create a marketplace listing:</small>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeSelectInventoryModal()" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (! isset($myInventoryItems) || $myInventoryItems->isEmpty()) { ?>
                    <div style="text-align:center;padding:36px 16px;">
                        <h4 style="margin:0 0 6px;color:#143825;font-size:16px;">No Reusable Inventory Found</h4>
                        <p style="margin:0 0 18px;color:#556c5e;font-size:13px;max-width:440px;margin-left:auto;margin-right:auto;line-height:1.5;">
                            Public marketplace listings are strictly for verified reusable circular materials. Non-reusable scrap items remain stored in your private account.
                        </p>
                        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                            <a href="/business/inventory" class="btn-choose-photos" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                                + Add Reusable Material to Inventory
                            </a>
                            <button type="button" onclick="openDirectCustomListing()" class="btn-cancel" style="cursor:pointer;padding:8px 16px;">
                                Create Custom Listing &rarr;
                            </button>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="inv-picker-grid">
                        <?php foreach ($myInventoryItems as $inv) {
                            $invImg = $inv->getPrimaryImage() ? asset($inv->getPrimaryImage()) : 'https://placehold.co/120x120/e8f5e9/1b5e20?text='.urlencode(substr($inv->name, 0, 3));
                            $invAllImgs = json_encode($inv->getAllImages());
                            ?>
                            <div class="inv-pick-card" onclick='selectInventoryItemForListing(<?= $inv->id ?>, <?= json_encode($inv->name) ?>, <?= json_encode($inv->category) ?>, <?= (float) $inv->quantity ?>, <?= json_encode($inv->unit) ?>, <?= json_encode($inv->description ?? '') ?>, <?= $invAllImgs ?>, <?= json_encode($inv->condition ?? 'Used') ?>)'>
                                <div class="inv-pick-thumb">
                                    <img src="<?= e($invImg) ?>" alt="<?= e($inv->name) ?>">
                                </div>
                                <div class="inv-pick-details">
                                    <div class="inv-pick-name"><?= e($inv->name) ?></div>
                                    <div class="inv-pick-meta"><?= e($inv->category) ?></div>
                                    <div class="inv-pick-stock">
                                        <span class="inv-stock-badge"><?= (float) $inv->quantity ?> <?= e($inv->unit) ?></span>
                                        <span class="inv-reuse-tag">Reusable</span>
                                    </div>
                                </div>
                                <div class="inv-pick-arrow">&rarr;</div>
                            </div>
                        <?php } ?>
                    </div>
                    <div style="margin-top:14px;padding-top:12px;border-top:1px dashed #cadbc9;display:flex;justify-content:space-between;align-items:center;">
                        <small style="color:#617769;">Want to create a listing directly without selecting an item?</small>
                        <button type="button" onclick="openDirectCustomListing()" style="background:transparent;border:none;color:#166534;font-weight:700;font-size:12.5px;cursor:pointer;text-decoration:underline;">
                            + Enter custom details manually &rarr;
                        </button>
                    </div>
                <?php } ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeSelectInventoryModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Step 2: Create Listing Modal -->
    <div class="modal-overlay" id="create-listing-modal" style="display:none;" onclick="if(event.target===this)closeCreateListingModal()">
        <div class="modal-card" style="max-width:640px;">
            <div class="modal-header">
                <div>
                    <h3 style="margin:0;">List Material on Marketplace</h3>
                    <small style="color:#526b5a;font-size:12px;">Publish surplus material for verified SMEs to buy or claim for free.</small>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeCreateListingModal()" aria-label="Close">&times;</button>
            </div>
            <form method="POST" action="/business/marketplace" enctype="multipart/form-data" onsubmit="return validateListingForm(this)">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="inventory_item_id" id="modal_inventory_item_id" value="">
                
                <div class="modal-body">
                    <!-- Sourced from Inventory Banner -->
                    <div id="mkt_sourced_banner" style="display:none;background:#f0fdf4;border:1px solid #b9dec0;border-radius:0;padding:10px 14px;margin-bottom:2px;align-items:center;justify-content:space-between;">
                        <div>
                            <strong style="color:#15803d;font-size:13px;display:block;" id="mkt_sourced_name">Sourced from Inventory</strong>
                            <small style="color:#4b6354;font-size:11.5px;" id="mkt_sourced_stock">Stock Available: 0</small>
                        </div>
                        <button type="button" onclick="changeInventoryItemForListing()" style="background:#ffffff;border:1px solid #86efac;color:#15803d;padding:5px 12px;border-radius:0;font-size:12px;font-weight:700;cursor:pointer;">
                            Change Item
                        </button>
                    </div>

                    <!-- 1 to 3 Photos Upload Section -->
                    <div class="form-group">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                            <label style="margin:0;font-weight:700;color:#143825;">
                                Material Photos <span style="color:#b91c1c;">*</span> <small style="font-weight:500;color:#556c5e;">(Min 1, Max 3 photos)</small>
                            </label>
                            <span id="mkt_img_counter" style="font-size:12px;font-weight:700;color:#143825;">0 of 3 selected</span>
                        </div>

                        <div class="inv-photo-uploader-box" id="mkt_photo_uploader_box">
                            <input type="file" id="listing_images_form_input" name="images[]" multiple accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;">

                            <div class="inv-photo-top-bar">
                                <button type="button" class="btn-choose-photos" id="btn_mkt_add_photos" onclick="triggerMktFileInput()">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    <span>Add Photos</span>
                                </button>
                                <span class="inv-photo-hint">Click "Add Photos" or any empty slot to add 1 to 3 images. Image 1 is the cover photo.</span>
                            </div>

                            <div class="inv-slots-grid" id="mkt_slots_grid" style="grid-template-columns:repeat(3,1fr);"></div>

                            <div id="mkt_img_error" style="display:none;margin-top:8px;color:#dc2626;font-size:12px;font-weight:600;">
                                Please select at least 1 photo for this listing (maximum 3 photos).
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="create_title">Material Title *</label>
                        <input type="text" id="create_title" name="title" required placeholder="e.g. Surplus Clean Euro Pallets (Grade A)">
                    </div>

                    <div class="form-group">
                        <label for="create_category">Category *</label>
                        <select id="create_category" name="category" required style="width:100%;height:38px;padding:0 12px;border:1px solid #cbd5e1;border-radius:0;background:#ffffff;font:400 13px var(--font-main);">
                            <option value="">Select Category...</option>
                            <?php foreach (\App\Models\Item::ALLOWED_CATEGORIES as $catOption): ?>
                                <option value="<?= e($catOption) ?>"><?= e($catOption) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Reusability Classification: Strictly Reusable on Marketplace -->
                    <input type="hidden" name="reusability" value="reusable">
                    <div class="form-group">
                        <label>Reusability Classification</label>
                        <div style="padding:10px 14px;border-radius:0;background:#f0f7ef;border:1px solid #b8dab4;">
                            <strong style="display:block;font-size:13px;color:#15803d;">Reusable Circular Material</strong>
                            <small style="color:#526b5a;font-size:11.5px;">Public marketplace listings are strictly for reusable items. Non-reusable scrap items remain stored in your inventory account.</small>
                        </div>
                    </div>

                    <!-- Listing Option: Sell, Free, or Lend -->
                    <div class="form-group">
                        <label>Listing Option *</label>
                        <div class="offer-toggle-grid" style="grid-template-columns:1fr 1fr 1fr;">
                            <label class="offer-toggle-card">
                                <input type="radio" name="listing_type" value="buy" checked onchange="handleModalOfferChange('buy')">
                                <span class="offer-label">Sell</span>
                                <span class="offer-sub">Price per unit (RM)</span>
                            </label>
                            <label class="offer-toggle-card">
                                <input type="radio" name="listing_type" value="free" onchange="handleModalOfferChange('free')">
                                <span class="offer-label">Free</span>
                                <span class="offer-sub">RM 0.00 Claim</span>
                            </label>
                            <label class="offer-toggle-card">
                                <input type="radio" name="listing_type" value="lend" onchange="handleModalOfferChange('lend')">
                                <span class="offer-label">Lend</span>
                                <span class="offer-sub">Temporary Borrow</span>
                            </label>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                        <div class="form-group">
                            <label for="create_quantity">Quantity *</label>
                            <input type="number" step="0.01" min="0.01" id="create_quantity" name="quantity" required placeholder="100">
                        </div>

                        <div class="form-group">
                            <label for="create_unit">Unit of Measurement *</label>
                            <select id="create_unit" name="unit" required>
                                <option value="">Select unit...</option>
                                <?php foreach (\App\Models\Item::ALLOWED_UNITS as $unitVal => $unitLabel) { ?>
                                    <option value="<?= e($unitVal) ?>"><?= e($unitLabel) ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group" id="modal_price_group">
                            <label for="create_price" id="modal_price_label">Price per Unit (RM) *</label>
                            <input type="number" step="0.01" min="0.01" id="create_price" name="price" placeholder="e.g. 25.00" required>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                        <div class="form-group">
                            <label for="create_condition">Material Condition *</label>
                            <select id="create_condition" name="condition" required>
                                <?php foreach (\App\Models\Item::ALLOWED_CONDITIONS as $condOpt) { ?>
                                    <option value="<?= e($condOpt) ?>" <?= $condOpt === 'Used' ? 'selected' : '' ?>><?= e($condOpt) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="create_city">City</label>
                            <input type="text" id="create_city" name="location_city" value="<?= e($business->city ?? '') ?>" placeholder="e.g. Shah Alam">
                        </div>
                        <div class="form-group">
                            <label for="create_state">State</label>
                            <input type="text" id="create_state" name="location_state" value="<?= e($business->state ?? 'Selangor') ?>" placeholder="e.g. Selangor">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="create_desc">Material Description &amp; Exchange Terms</label>
                        <textarea id="create_desc" name="description" rows="3" placeholder="Condition, pickup location, or handling details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeCreateListingModal()">Cancel</button>
                    <button type="submit" class="btn-save">Publish Listing &rarr;</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick View / Detail Modal with Photo Gallery -->
    <div class="modal-overlay" id="listing-detail-modal" style="display:none;" onclick="if(event.target===this)closeListingDetailModal()">
        <div class="modal-card" style="max-width:680px;">
            <div class="modal-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span id="modal-detail-offer-badge" class="offer-type-badge buy">Buy</span>
                    <span id="modal-detail-reusable-badge" class="badge-reusable">Reusable</span>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeListingDetailModal()" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Large Image Viewer + Thumbnails -->
                <div style="background:#f1f5f0;border:1px solid var(--card-border);border-radius:0;overflow:hidden;position:relative;height:280px;display:flex;align-items:center;justify-content:center;">
                    <img id="modal-detail-main-img" src="" alt="Material photo" style="width:100%;height:100%;object-fit:cover;display:none;">
                    <div id="modal-detail-no-img" style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                        <span class="signin-brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                        <span style="font-size:12px;color:#617769;font-weight:600;">No photo uploaded</span>
                    </div>
                </div>
                <!-- Gallery Thumbnails Row -->
                <div class="preview-thumbs-grid" id="modal-detail-thumbs" style="display:none;margin-top:0;"></div>

                <div>
                    <h2 id="modal-detail-title" style="margin:0 0 6px;font-size:20px;font-weight:800;color:#123321;font-family:var(--font-display);"></h2>
                    <p id="modal-detail-company-loc" style="margin:0;font-size:13px;color:#556c5e;font-weight:600;"></p>
                </div>

                <div class="modal-detail-grid" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="modal-detail-item">
                        <small>Price / Terms</small>
                        <span id="modal-detail-price" style="color:#15803d;font-weight:700;"></span>
                    </div>
                    <div class="modal-detail-item">
                        <small>Available Qty</small>
                        <span id="modal-detail-qty"></span>
                    </div>
                    <div class="modal-detail-item">
                        <small>Condition</small>
                        <span id="modal-detail-condition"></span>
                    </div>
                    <div class="modal-detail-item">
                        <small>Category</small>
                        <span id="modal-detail-category"></span>
                    </div>
                </div>

                <div>
                    <h4 style="margin:0 0 6px;font-size:12px;text-transform:uppercase;color:#556c5e;letter-spacing:0.04em;">Description &amp; Exchange Terms</h4>
                    <p id="modal-detail-desc" style="margin:0;font-size:13px;color:#2e4436;line-height:1.6;background:#fbfdfa;border:1px solid var(--card-border);padding:12px;border-radius:0;"></p>
                </div>
            </div>
            <div class="modal-footer" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <div style="display:flex;gap:8px;">
                    <button type="button" class="btn-cancel" onclick="closeListingDetailModal()">Close</button>
                    <a id="modal-detail-helper-link" href="/business/helper" class="btn-view-quick" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:12px;padding:8px 14px;">
                        <span>Ask EcoCycle Helper</span>
                    </a>
                </div>
                <button type="button" class="btn-claim-action" id="modal-detail-action-btn" onclick="executeClaimFromModal()">
                    Inquire Now &rarr;
                </button>
            </div>
        </div>
    </div>

    <script>
    function toggleNotificationMenu(e) {
        e.stopPropagation();
        var menu = document.getElementById('notification-dropdown');
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }
    document.addEventListener('click', function() {
        var menu = document.getElementById('notification-dropdown');
        if (menu) menu.style.display = 'none';
    });

    // Carousel state tracking for cards
    var listingCarousels = {};

    function slideListingImage(listingId, direction, e) {
        if (e) e.stopPropagation();
        var card = document.getElementById('listing-card-' + listingId);
        if (!card) return;
        var images = JSON.parse(card.dataset.images || '[]');
        if (images.length <= 1) return;

        if (typeof listingCarousels[listingId] === 'undefined') {
            listingCarousels[listingId] = 0;
        }

        listingCarousels[listingId] += direction;
        if (listingCarousels[listingId] < 0) listingCarousels[listingId] = images.length - 1;
        if (listingCarousels[listingId] >= images.length) listingCarousels[listingId] = 0;

        var curIdx = listingCarousels[listingId];
        var imgEl = document.getElementById('img-display-' + listingId);
        if (imgEl) imgEl.src = images[curIdx];

        var counterEl = document.getElementById('img-counter-' + listingId);
        if (counterEl) counterEl.textContent = (curIdx + 1) + '/' + images.length;

        var dotsContainer = document.getElementById('dots-container-' + listingId);
        if (dotsContainer) {
            var dots = dotsContainer.children;
            for (var i = 0; i < dots.length; i++) {
                dots[i].className = 'listing-dot' + (i === curIdx ? ' active' : '');
            }
        }
    }

    // Multi-Image Accumulator & Slot Management for Marketplace Listing
    var mktPhotosTransfer = new DataTransfer();
    var mktExistingImages = [];
    var hasUserInventory = <?= (isset($myInventoryItems) && $myInventoryItems->isNotEmpty()) ? 'true' : 'false' ?>;

    // Trigger file chooser for marketplace listing
    function triggerMktFileInput() {
        var total = mktExistingImages.length + mktPhotosTransfer.files.length;
        if (total >= 3) {
            alert('Maximum of 3 photos reached. Remove a photo to add a different one.');
            return;
        }
        var input = document.getElementById('listing_images_form_input');
        if (input) {
            input.value = '';
            input.click();
        }
    }

    // Handle new images chosen via file input (appends with DataTransfer)
    document.addEventListener('DOMContentLoaded', function() {
        var fileInput = document.getElementById('listing_images_form_input');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                var newFiles = this.files;
                if (!newFiles || newFiles.length === 0) return;

                var currentTotal = mktExistingImages.length + mktPhotosTransfer.files.length;
                var availableSlots = 3 - currentTotal;

                if (availableSlots <= 0) {
                    alert('Maximum of 3 photos allowed per listing.');
                    return;
                }

                var filesToAdd = Array.from(newFiles).slice(0, availableSlots);
                if (newFiles.length > availableSlots) {
                    alert('Only ' + availableSlots + ' more photo(s) could be added to stay within the 3-photo limit.');
                }

                filesToAdd.forEach(function(file) {
                    mktPhotosTransfer.items.add(file);
                });

                renderMktSlots();
            });
        }
    });

    // Render 3 photo slots visually
    function renderMktSlots() {
        var grid = document.getElementById('mkt_slots_grid');
        var counter = document.getElementById('mkt_img_counter');
        var err = document.getElementById('mkt_img_error');
        if (err) err.style.display = 'none';

        var existingCount = Math.min(mktExistingImages.length, 3);
        var newFiles = mktPhotosTransfer.files;
        var newCount = newFiles.length;
        var totalCount = existingCount + newCount;

        if (counter) {
            counter.textContent = totalCount + ' of 3 selected' + (totalCount === 0 ? ' (At least 1 required)' : '');
            counter.style.color = totalCount >= 1 ? '#18422d' : '#b91c1c';
        }

        var formInput = document.getElementById('listing_images_form_input');
        if (formInput) {
            formInput.files = mktPhotosTransfer.files;
        }

        if (!grid) return;
        grid.innerHTML = '';

        // 1. Render pre-existing inventory images
        for (var i = 0; i < existingCount; i++) {
            (function(idx) {
                var imgSrc = mktExistingImages[idx];
                var card = document.createElement('div');
                card.className = 'inv-slot-card';
                card.innerHTML = '<img src="' + imgSrc + '" alt="Photo ' + (idx + 1) + '">' +
                    '<button type="button" class="inv-slot-remove-btn" onclick="removeMktPhoto(' + idx + ', true)" title="Remove photo">&times;</button>' +
                    (idx === 0 ? '<span class="inv-slot-cover-badge">Cover</span>' : '');
                grid.appendChild(card);
            })(i);
        }

        // 2. Render newly chosen files
        for (var j = 0; j < newCount; j++) {
            (function(slotIdx, fileIdx) {
                var file = newFiles[fileIdx];
                var card = document.createElement('div');
                card.className = 'inv-slot-card';

                var reader = new FileReader();
                reader.onload = function(e) {
                    card.innerHTML = '<img src="' + e.target.result + '" alt="Photo ' + (slotIdx + 1) + '">' +
                        '<button type="button" class="inv-slot-remove-btn" onclick="removeMktPhoto(' + fileIdx + ', false)" title="Remove photo">&times;</button>' +
                        (slotIdx === 0 ? '<span class="inv-slot-cover-badge">Cover</span>' : '');
                };
                reader.readAsDataURL(file);

                grid.appendChild(card);
            })(existingCount + j, j);
        }

        // 3. Fill remaining slots up to 3 with empty placeholders
        for (var k = totalCount; k < 3; k++) {
            (function(slotNumber) {
                var emptyCard = document.createElement('div');
                emptyCard.className = 'inv-slot-card empty-slot';
                emptyCard.onclick = triggerMktFileInput;
                emptyCard.innerHTML = slotNumber === 0 ? 
                    '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span>Add</span>' : 
                    '<span>Image ' + (slotNumber + 1) + '</span>';
                grid.appendChild(emptyCard);
            })(k);
        }
    }

    // Remove photo from slot
    function removeMktPhoto(index, isExisting) {
        if (isExisting) {
            mktExistingImages.splice(index, 1);
        } else {
            var updatedTransfer = new DataTransfer();
            var files = mktPhotosTransfer.files;
            for (var i = 0; i < files.length; i++) {
                if (i !== index) {
                    updatedTransfer.items.add(files[i]);
                }
            }
            mktPhotosTransfer = updatedTransfer;
        }
        renderMktSlots();
    }

    // Validate listing form
    function validateListingForm(form) {
        var total = mktExistingImages.length + mktPhotosTransfer.files.length;
        if (total < 1) {
            var err = document.getElementById('mkt_img_error');
            if (err) err.style.display = 'block';
            alert('Please select at least 1 photo for this listing (maximum 3 photos).');
            return false;
        }
        return true;
    }

    // Step 1: Open Inventory Picker or Direct Listing Modal
    function openCreateListingModal() {
        if (hasUserInventory) {
            openSelectInventoryModal();
        } else {
            openDirectCustomListing();
        }
    }

    function openSelectInventoryModal() {
        var modal = document.getElementById('select-inventory-modal');
        if (modal) {
            modal.style.display = 'flex';
            var body = modal.querySelector('.modal-body');
            if (body) body.scrollTop = 0;
            document.body.style.overflow = 'hidden';
        }
    }

    function closeSelectInventoryModal() {
        var modal = document.getElementById('select-inventory-modal');
        if (modal) modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    function normalizeMktCondition(rawCond) {
        var val = (rawCond || '').trim();
        if (val === 'New' || val === 'Damaged' || val === 'Used') return val;
        var lower = val.toLowerCase();
        if (lower.indexOf('damag') !== -1 || lower.indexOf('broken') !== -1) return 'Damaged';
        if (lower.indexOf('new') !== -1 || lower.indexOf('unused') !== -1) return 'New';
        return 'Used';
    }

    function setMktUnitValue(selectEl, rawUnit) {
        if (!selectEl) return;
        var val = (rawUnit || '').trim();
        for (var i = 0; i < selectEl.options.length; i++) {
            if (selectEl.options[i].value === val) {
                selectEl.value = val;
                return;
            }
        }
        var lower = val.toLowerCase();
        var map = {
            'kg': 'kg', 'kilogram': 'kg', 'g': 'g', 'gram': 'g', 'l': 'L', 'litre': 'L', 'ml': 'mL',
            'pcs': 'pcs', 'piece': 'pcs', 'pieces': 'pcs', 'unit': 'pcs', 'units': 'pcs',
            'bag': 'Bag', 'bags': 'Bag', 'box': 'Box', 'boxes': 'Box', 't': 't', 'tonne': 't',
            'm': 'm', 'metre': 'm', 'm2': 'm²', 'm²': 'm²', 'sqm': 'm²', 'm3': 'm³', 'm³': 'm³', 'cbm': 'm³'
        };
        selectEl.value = map[lower] || 'kg';
    }

    // Step 2: Clicking an Item First Selects It & Pops Out Listing Modal
    function selectInventoryItemForListing(id, name, cat, qty, unit, desc, images, condition) {
        closeSelectInventoryModal();

        // Fill Form Fields
        document.getElementById('modal_inventory_item_id').value = id;
        document.getElementById('create_title').value = name;
        document.getElementById('create_category').value = cat;
        document.getElementById('create_quantity').value = qty;
        setMktUnitValue(document.getElementById('create_unit'), unit);
        if (document.getElementById('create_condition')) {
            document.getElementById('create_condition').value = normalizeMktCondition(condition);
        }
        document.getElementById('create_desc').value = desc || '';

        // Sourced from inventory banner
        var banner = document.getElementById('mkt_sourced_banner');
        if (banner) {
            document.getElementById('mkt_sourced_name').textContent = 'Sourced from Inventory: ' + name;
            document.getElementById('mkt_sourced_stock').textContent = 'Stock Available: ' + qty + ' ' + unit + ' (Verified Reusable)';
            banner.style.display = 'flex';
        }

        // Preload Photos from inventory item (max 3)
        mktExistingImages = Array.isArray(images) ? images.slice(0, 3) : [];
        mktPhotosTransfer = new DataTransfer();
        renderMktSlots();

        // Pop Out Create Listing Modal
        var modal = document.getElementById('create-listing-modal');
        modal.style.display = 'flex';
        var body = modal.querySelector('.modal-body');
        if (body) body.scrollTop = 0;
        document.body.style.overflow = 'hidden';
    }

    // Direct custom listing without inventory selection
    function openDirectCustomListing() {
        closeSelectInventoryModal();

        document.getElementById('modal_inventory_item_id').value = '';
        var banner = document.getElementById('mkt_sourced_banner');
        if (banner) banner.style.display = 'none';

        document.getElementById('create_title').value = '';
        document.getElementById('create_quantity').value = '';
        document.getElementById('create_unit').value = '';
        document.getElementById('create_desc').value = '';

        mktExistingImages = [];
        mktPhotosTransfer = new DataTransfer();
        renderMktSlots();

        var modal = document.getElementById('create-listing-modal');
        modal.style.display = 'flex';
        var body = modal.querySelector('.modal-body');
        if (body) body.scrollTop = 0;
        document.body.style.overflow = 'hidden';
    }

    // Change inventory item from inside listing modal
    function changeInventoryItemForListing() {
        closeCreateListingModal();
        openSelectInventoryModal();
    }

    function closeCreateListingModal() {
        document.getElementById('create-listing-modal').style.display = 'none';
        document.body.style.overflow = '';
    }

    function handleModalOfferChange(type) {
        var priceInput = document.getElementById('create_price');
        var priceLabel = document.getElementById('modal_price_label');
        if (type === 'free' || type === 'lend') {
            priceInput.min = '0';
            priceInput.value = '0.00';
            priceInput.readOnly = true;
            priceInput.required = false;
            priceInput.style.background = '#f1f5f9';
            priceLabel.textContent = type === 'lend' ? 'Price (RM 0.00 - Lend)' : 'Price (RM 0.00 - Free)';
        } else {
            priceInput.min = '0.01';
            if (priceInput.value === '0.00' || priceInput.value === '0') {
                priceInput.value = '';
            }
            priceInput.readOnly = false;
            priceInput.required = true;
            priceInput.style.background = '#ffffff';
            priceLabel.textContent = 'Price per Unit (RM) *';
        }
    }

    // Quick View Detail Modal
    var currentActiveModalListing = null;

    function openListingDetailModal(listingId) {
        var card = document.getElementById('listing-card-' + listingId);
        if (!card) return;

        currentActiveModalListing = {
            id: listingId,
            title: card.dataset.title,
            company: card.dataset.company,
            type: card.dataset.type,
        };

        var images = JSON.parse(card.dataset.images || '[]');
        var mainImg = document.getElementById('modal-detail-main-img');
        var noImg = document.getElementById('modal-detail-no-img');
        var thumbs = document.getElementById('modal-detail-thumbs');
        thumbs.innerHTML = '';

        if (images.length > 0) {
            mainImg.src = images[0];
            mainImg.style.display = 'block';
            noImg.style.display = 'none';

            if (images.length > 1) {
                thumbs.style.display = 'grid';
                images.forEach(function(src, i) {
                    var t = document.createElement('div');
                    t.className = 'thumb-preview-card';
                    t.style.cursor = 'pointer';
                    t.innerHTML = '<img src="' + src + '" alt="thumb">';
                    t.onclick = function() {
                        mainImg.src = src;
                    };
                    thumbs.appendChild(t);
                });
            } else {
                thumbs.style.display = 'none';
            }
        } else {
            mainImg.style.display = 'none';
            noImg.style.display = 'flex';
            thumbs.style.display = 'none';
        }

        // Badges
        var offerBadge = document.getElementById('modal-detail-offer-badge');
        var type = (card.dataset.type === 'free' || card.dataset.type === 'lend') ? card.dataset.type : 'buy';
        offerBadge.className = 'offer-type-badge ' + (type === 'lend' ? 'borrow' : type);
        offerBadge.textContent = type === 'free' ? 'Free' : (type === 'lend' ? 'Lend' : 'Sell');

        var reusableBadge = document.getElementById('modal-detail-reusable-badge');
        if (card.dataset.reusability === 'reusable') {
            reusableBadge.className = 'badge-reusable';
            reusableBadge.textContent = 'Reusable';
        } else {
            reusableBadge.className = 'badge-not-reusable';
            reusableBadge.textContent = 'Non-Reusable';
        }

        document.getElementById('modal-detail-title').textContent = card.dataset.title;
        document.getElementById('modal-detail-company-loc').textContent = card.dataset.company + ' · ' + card.dataset.city + ', ' + card.dataset.state;
        
        var price = parseFloat(card.dataset.price || 0);
        var priceText = type === 'free' ? 'FREE (RM 0.00)' : (type === 'lend' ? 'LEND (RM 0.00 - Returnable)' : 'RM ' + price.toFixed(2) + ' / ' + card.dataset.unit);
        document.getElementById('modal-detail-price').textContent = priceText;
        document.getElementById('modal-detail-qty').textContent = card.dataset.qty + ' ' + card.dataset.unit;
        document.getElementById('modal-detail-condition').textContent = card.dataset.condition;
        document.getElementById('modal-detail-category').textContent = card.dataset.category;
        document.getElementById('modal-detail-desc').textContent = card.dataset.desc || 'No additional terms specified.';

        var actionBtn = document.getElementById('modal-detail-action-btn');
        var isSoldOut = Number(card.dataset.qty) <= 0;
        actionBtn.disabled = isSoldOut;
        if (isSoldOut) {
            actionBtn.style.display = 'inline-flex';
            actionBtn.textContent = 'Out of Stock';
        } else if (card.dataset.owner === '1') {
            actionBtn.style.display = 'none';
        } else {
            actionBtn.style.display = 'inline-flex';
            actionBtn.textContent = type === 'free' ? 'Claim Item' : (type === 'lend' ? 'Request to Borrow' : 'Buy Now');
        }

        var helperLink = document.getElementById('modal-detail-helper-link');
        if (helperLink) {
            if (card.dataset.reusability === 'not_reusable') {
                helperLink.style.display = 'inline-flex';
                helperLink.href = '/business/helper?item_id=' + listingId;
            } else {
                helperLink.style.display = 'none';
            }
        }

        var modal = document.getElementById('listing-detail-modal');
        modal.style.display = 'flex';
        var body = modal.querySelector('.modal-body');
        if (body) body.scrollTop = 0;
        document.body.style.overflow = 'hidden';
    }

    function closeListingDetailModal() {
        document.getElementById('listing-detail-modal').style.display = 'none';
        document.body.style.overflow = '';
    }
    </script>

    <!-- REAL MATERIAL EXCHANGE / ORDER MODAL -->
    <div class="modal-overlay" id="exchange-order-modal" style="display:none;" onclick="if(event.target===this)closeExchangeOrderModal()">
        <div class="modal-card" style="max-width:540px;">
            <div class="modal-header">
                <div>
                    <h3 id="order-modal-heading" style="margin:0;font-size:17px;color:#123321;">Order Material</h3>
                    <small style="color:#526b5a;font-size:12px;">Review quantity and total before completing your order.</small>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeExchangeOrderModal()" aria-label="Close">&times;</button>
            </div>
            <form id="exchange-order-form" method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" id="order-proceed-checkout" name="proceed_to_checkout" value="0">
                <div class="modal-body" style="display:flex;flex-direction:column;gap:16px;">
                    <!-- Item summary box -->
                    <div style="display:flex;gap:14px;padding:12px;background:#f8faf6;border:1px solid var(--card-border);border-radius:0;align-items:center;">
                        <img id="order-modal-img" src="" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:0;border:1px solid var(--card-border);">
                        <div style="flex:1;min-width:0;">
                            <strong id="order-modal-title" style="display:block;font-size:14px;color:#123321;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Item Name</strong>
                            <span id="order-modal-provider" style="display:block;font-size:12px;color:#45604e;">Provider</span>
                            <span id="order-modal-stock" style="display:block;font-size:11px;color:#15803d;font-weight:700;margin-top:2px;">Stock: 0</span>
                        </div>
                    </div>

                    <!-- Quantity Input -->
                    <div>
                        <label for="order-quantity" style="display:block;font-size:12px;font-weight:700;color:#123321;margin-bottom:6px;">
                            Quantity to Request (<span id="order-unit-label">units</span>) *
                        </label>
                        <input type="number" id="order-quantity" name="quantity" class="modal-field-input" step="any" min="0.01" required oninput="calculateOrderTotal()" style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid var(--input-border);border-radius:0;font-size:14px;">
                        <small id="order-max-hint" style="color:#6b8070;font-size:11px;margin-top:4px;display:block;">Available in stock: 0</small>
                    </div>

                    <!-- Live Price Summary -->
                    <div style="background:#eaf3e8;border:1px solid #c0d8bd;border-radius:0;padding:12px 14px;display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <span style="display:block;font-size:11px;color:#45604e;font-weight:600;">Unit Rate</span>
                            <strong id="order-unit-price" style="font-size:13px;color:#123321;">RM 0.00</strong>
                        </div>
                        <div style="text-align:right;">
                            <span style="display:block;font-size:11px;color:#45604e;font-weight:600;">Estimated Total</span>
                            <strong id="order-total-price" style="font-size:18px;color:#176b40;font-weight:800;">RM 0.00</strong>
                        </div>
                    </div>

                    <!-- Collection / Delivery Method & Address -->
                    <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:10px;">
                        <div>
                            <label for="order-fulfillment-method" style="display:block;font-size:12px;font-weight:700;color:#123321;margin-bottom:6px;">
                                Fulfillment Method
                            </label>
                            <select id="order-fulfillment-method" name="fulfillment_method" style="width:100%;box-sizing:border-box;padding:9px 10px;border:1px solid var(--input-border);border-radius:0;font-size:12.5px;background:#ffffff;">
                                <option value="collection">Self Collection</option>
                                <option value="delivery">Delivery</option>
                            </select>
                        </div>
                        <div>
                            <label for="order-collection-address" style="display:block;font-size:12px;font-weight:700;color:#123321;margin-bottom:6px;">
                                Collection / Delivery Address
                            </label>
                            <input type="text" id="order-collection-address" name="collection_address" maxlength="255" placeholder="Confirm pickup or delivery address" style="width:100%;box-sizing:border-box;padding:9px 10px;border:1px solid var(--input-border);border-radius:0;font-size:12.5px;">
                        </div>
                    </div>

                    <!-- Notes / Message -->
                    <div>
                        <label for="order-notes" style="display:block;font-size:12px;font-weight:700;color:#123321;margin-bottom:6px;">
                            Order Notes / Delivery or Pickup Instructions (Optional)
                        </label>
                        <textarea id="order-notes" name="notes" rows="2" placeholder="e.g., We will arrange our own logistics, or please confirm collection time..." style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid var(--input-border);border-radius:0;font-size:12.5px;resize:vertical;"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeExchangeOrderModal()">Cancel</button>
                    <button type="submit" id="order-submit-btn" class="btn-save">Confirm Order &rarr;</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function executeClaimFromModal() {
        if (!currentActiveModalListing) return;
        var listingId = currentActiveModalListing.id;
        closeListingDetailModal();
        openExchangeOrderModal(listingId);
    }

    var currentExchangeItem = null;

    function openExchangeOrderModal(listingId) {
        var card = document.getElementById('listing-card-' + listingId);
        if (!card || Number(card.dataset.qty) <= 0) return;

        var title = card.dataset.title;
        var company = card.dataset.company;
        var city = card.dataset.city;
        var state = card.dataset.state;
        var price = parseFloat(card.dataset.price || 0);
        var qty = parseFloat(card.dataset.qty || 0);
        var unit = card.dataset.unit || 'units';
        var type = card.dataset.type || 'buy';
        var images = [];
        try {
            images = JSON.parse(card.dataset.images || '[]');
        } catch(e) {}

        currentExchangeItem = {
            id: listingId,
            title: title,
            company: company,
            price: price,
            qty: qty,
            unit: unit,
            type: type
        };

        var form = document.getElementById('exchange-order-form');
        form.action = '/business/marketplace/' + listingId + '/exchange';

        document.getElementById('order-modal-title').textContent = title;
        document.getElementById('order-modal-provider').textContent = company + ' · ' + city + ', ' + state;
        document.getElementById('order-modal-stock').textContent = 'Stock Available: ' + qty + ' ' + unit;
        document.getElementById('order-unit-label').textContent = unit;
        document.getElementById('order-max-hint').textContent = 'Available in stock: ' + qty + ' ' + unit;

        var imgEl = document.getElementById('order-modal-img');
        if (images && images.length > 0) {
            imgEl.src = images[0];
            imgEl.style.display = 'block';
        } else {
            imgEl.src = '/images/figma/ecocycle-leaf.svg';
            imgEl.style.display = 'block';
        }

        var qtyInput = document.getElementById('order-quantity');
        qtyInput.max = qty;
        qtyInput.value = Math.min(1, qty);

        var heading = document.getElementById('order-modal-heading');
        var submitBtn = document.getElementById('order-submit-btn');
        var proceedCheckoutInput = document.getElementById('order-proceed-checkout');
        var addrInput = document.getElementById('order-collection-address');
        if (addrInput) {
            addrInput.value = card.dataset.pickup || (city + ', ' + state);
        }

        if (type === 'free') {
            heading.textContent = 'Claim Free Material';
            submitBtn.textContent = 'Confirm Free Claim';
            if (proceedCheckoutInput) proceedCheckoutInput.value = '0';
            document.getElementById('order-unit-price').textContent = 'FREE (RM 0.00)';
        } else if (type === 'lend') {
            heading.textContent = 'Request to Borrow Material';
            submitBtn.textContent = 'Confirm Borrow Request';
            if (proceedCheckoutInput) proceedCheckoutInput.value = '0';
            document.getElementById('order-unit-price').textContent = 'LEND (RM 0.00 - Returnable)';
        } else {
            heading.textContent = 'Purchase Material Order';
            submitBtn.textContent = 'Proceed to Checkout';
            if (proceedCheckoutInput) proceedCheckoutInput.value = '1';
            document.getElementById('order-unit-price').textContent = 'RM ' + price.toFixed(2) + ' / ' + unit;
        }

        calculateOrderTotal();

        var modal = document.getElementById('exchange-order-modal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function calculateOrderTotal() {
        if (!currentExchangeItem) return;
        var qtyInput = document.getElementById('order-quantity');
        var reqQty = parseFloat(qtyInput.value || 0);
        var totalEl = document.getElementById('order-total-price');

        if (currentExchangeItem.type === 'free') {
            totalEl.textContent = 'RM 0.00 (Free)';
            return;
        }
        if (currentExchangeItem.type === 'lend') {
            totalEl.textContent = 'RM 0.00 (Lend)';
            return;
        }

        var total = reqQty * currentExchangeItem.price;
        totalEl.textContent = 'RM ' + total.toFixed(2);
    }

    function closeExchangeOrderModal() {
        var modal = document.getElementById('exchange-order-modal');
        if (modal) modal.style.display = 'none';
        document.body.style.overflow = '';
    }
    </script>
    <dialog id="seller-reviews-dialog" class="seller-reviews-dialog" aria-labelledby="seller-reviews-title">
        <form method="dialog" class="seller-reviews-header">
            <div>
                <h2 id="seller-reviews-title">Item reviews</h2>
                <p id="seller-reviews-summary"></p>
            </div>
            <button class="btn-reset" type="submit" autofocus>Close</button>
        </form>
        <div class="seller-reviews-content">
            <ul id="seller-review-list"></ul>
            <p id="seller-review-status" role="status" hidden></p>
            <button type="button" class="btn-reset" id="seller-review-more" hidden>Load more reviews</button>
        </div>
    </dialog>

    <script>
    (() => {
        const dialog = document.getElementById('seller-reviews-dialog');
        const list = document.getElementById('seller-review-list');
        const status = document.getElementById('seller-review-status');
        const more = document.getElementById('seller-review-more');
        let nextUrl;
        let pending;

        async function loadReviews() {
            const request = new AbortController();
            pending = request;
            more.hidden = true;
            status.hidden = false;
            status.textContent = 'Loading reviews…';

            try {
                const response = await fetch(nextUrl, {
                    headers: { Accept: 'application/json' },
                    signal: request.signal
                });
                if (!response.ok) {
                    throw new Error('Review request failed');
                }
                const data = await response.json();
                if (request.signal.aborted) return;

                for (const review of data.reviews) {
                    const row = document.createElement('li');
                    const reviewer = document.createElement('strong');
                    const rating = document.createElement('small');
                    reviewer.textContent = review.reviewer;
                    rating.textContent = [review.rating + ' / 5', review.date].filter(Boolean).join(' · ');
                    row.append(reviewer, rating);
                    if (review.comment) {
                        const comment = document.createElement('p');
                        comment.textContent = review.comment;
                        row.append(comment);
                    }
                    list.append(row);
                }
                nextUrl = data.next;
                more.textContent = 'Load more reviews';
                more.hidden = !nextUrl;
                status.hidden = list.children.length > 0;
                status.textContent = status.hidden ? '' : 'No reviews yet.';
            } catch {
                if (request.signal.aborted) return;
                status.textContent = 'Could not load reviews.';
                more.textContent = 'Try again';
                more.hidden = false;
            }
        }

        more.addEventListener('click', loadReviews);
        dialog.addEventListener('close', () => pending?.abort());
        for (const button of document.querySelectorAll('[data-seller-reviews-url]')) {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                pending?.abort();
                list.replaceChildren();
                document.getElementById('seller-reviews-summary').textContent = button.dataset.itemName + ' · ' + button.textContent.trim();
                nextUrl = button.dataset.sellerReviewsUrl;
                dialog.showModal();
                loadReviews();
            });
        }
    })();
    </script>

</body>
</html>
