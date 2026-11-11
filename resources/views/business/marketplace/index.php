<?php
/** @var \App\Models\User|null $business */
/** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\MarketplaceListing> $listings */
/** @var \Illuminate\Support\Collection<int, string> $categories */
/** @var \Illuminate\Support\Collection<int, string> $locations */
/** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\InventoryItem> $myInventoryItems */
/** @var string|null $search */
/** @var string|null $selectedCategory */
/** @var string|null $selectedLocation */
/** @var string|null $selectedListingType */
/** @var string|null $selectedReusability */
/** @var bool $myListingsOnly */

$business = $business ?? \Illuminate\Support\Facades\Auth::guard()->user();
$companyName = $business?->business_name ?: ($business?->name ?? 'EcoCycle Business');
$myListingsOnly = $myListingsOnly ?? request()->boolean('my_listings');
$activeNav = $myListingsOnly ? 'listings' : 'marketplace';
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $myListingsOnly ? 'My Listings' : 'Surplus Materials Marketplace' ?> — EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .marketplace-tabs {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
            border-bottom: 1px solid #dce6db;
            padding-bottom: 10px;
        }
        .mkt-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            transition: all 0.15s ease;
        }
        .mkt-tab-btn:hover {
            color: var(--brand-green);
            background: #eef6ec;
        }
        .mkt-tab-btn.active {
            color: #0e291b;
            background: #e2ede0;
            font-weight: 700;
        }
        .mkt-tab-badge {
            background: #ffffff;
            color: #1a4d2e;
            padding: 1px 7px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid #c4d9c0;
        }

        .marketplace-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
            gap: 22px;
        }

        /* Professional Card Styling */
        .listing-card {
            background: #ffffff;
            border: 1px solid #d8e5d6;
            border-radius: 14px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease, border-color 0.2s ease;
            box-shadow: 0 2px 6px rgba(18, 51, 33, 0.04);
            position: relative;
        }
        .listing-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px rgba(18, 51, 33, 0.09);
            border-color: #a8cfab;
        }

        /* Multi-Image Gallery Wrap */
        .listing-img-container {
            position: relative;
            height: 190px;
            width: 100%;
            background: linear-gradient(135deg, #eef4ed, #e3ece1);
            overflow: hidden;
        }
        .listing-img-slide {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 0.2s ease;
        }
        .listing-img-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 28px;
            height: 28px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            color: #123321;
            font-size: 13px;
            font-weight: 700;
            display: grid;
            place-items: center;
            cursor: pointer;
            z-index: 4;
            opacity: 0;
            transition: opacity 0.15s ease, background 0.15s ease;
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
            background: rgba(0, 0, 0, 0.35);
            padding: 3px 6px;
            border-radius: 10px;
        }
        .listing-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transition: all 0.15s ease;
        }
        .listing-dot.active {
            background: #ffffff;
            width: 14px;
            border-radius: 4px;
        }

        .img-count-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(14, 41, 27, 0.82);
            backdrop-filter: blur(4px);
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 12px;
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
            border-radius: 4px;
            font-size: 11px;
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
            border-radius: 4px;
            padding: 2px 6px;
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
            min-height: 42px;
        }

        .listing-company-row {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #556c5e;
            margin-bottom: 10px;
        }
        .listing-company-row strong {
            color: #1c3d2a;
            font-weight: 600;
        }

        .listing-pricing-box {
            background: #f8fbf7;
            border: 1px solid #e3ede1;
            border-radius: 8px;
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
            color: #0284c7;
        }
        .price-main.free-price {
            color: #16a34a;
        }
        .price-unit {
            font-size: 11px;
            color: #617769;
            font-weight: 500;
            margin-left: 4px;
        }
        .qty-badge {
            font-size: 11px;
            font-weight: 600;
            color: #2e573a;
            background: #eaf3e8;
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid #cfe1cd;
        }

        .listing-desc-excerpt {
            font-size: 12px;
            color: #5b6f63;
            line-height: 1.45;
            margin: 0 0 14px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 35px;
        }

        .listing-card-footer {
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid #edf3eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .btn-view-quick {
            background: #ffffff;
            border: 1px solid #cadbc9;
            color: #244b32;
            padding: 7px 12px;
            border-radius: 6px;
            font: 600 12px var(--font-main);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .btn-view-quick:hover {
            background: #eef6ec;
            border-color: #9ecc9f;
        }

        .btn-claim-action {
            background: var(--brand-green);
            border: 1px solid var(--brand-green);
            color: #ffffff;
            padding: 7px 14px;
            border-radius: 6px;
            font: 600 12px var(--font-main);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s ease;
        }
        .btn-claim-action:hover {
            background: #0d281a;
        }

        /* Create Listing Modal Multi-Image Upload Area */
        .image-upload-dropzone {
            border: 2px dashed #b9d8ba;
            background: #f7fbf6;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.15s ease;
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
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #c2d8bf;
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
            background: rgba(14, 41, 27, 0.85);
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

        .modal-backdrop,
        .modal-overlay {
            position: fixed !important;
            inset: 0 !important;
            z-index: 1050 !important;
            background: rgba(14, 38, 25, 0.72) !important;
            backdrop-filter: blur(3px) !important;
            align-items: flex-start !important;
            justify-content: center !important;
            padding: 24px 16px !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            -webkit-overflow-scrolling: touch !important;
        }
        .modal-backdrop[style*="display: none"],
        .modal-overlay[style*="display: none"] {
            display: none !important;
        }
        .modal-backdrop[style*="display: flex"],
        .modal-overlay[style*="display: flex"] {
            display: flex !important;
        }
        .modal-dialog,
        .modal-card,
        .modal-dialog-large {
            position: relative !important;
            width: min(640px, 95vw) !important;
            max-height: calc(100vh - 48px) !important;
            height: auto !important;
            display: flex !important;
            flex-direction: column !important;
            border-radius: 12px !important;
            background: #ffffff !important;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3) !important;
            overflow: hidden !important;
            margin: 0 auto !important;
            flex-shrink: 0 !important;
        }
        .modal-dialog > form,
        .modal-card > form,
        .modal-dialog form,
        .modal-card form {
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 auto !important;
            min-height: 0 !important;
            height: 100% !important;
            overflow: hidden !important;
            margin: 0 !important;
        }
        .modal-dialog .modal-header,
        .modal-card .modal-header,
        .modal-header {
            flex-shrink: 0 !important;
            background: #ffffff !important;
            border-bottom: 1px solid #e5ede3 !important;
        }
        .modal-dialog .modal-body,
        .modal-card .modal-body,
        .modal-body {
            overflow-y: auto !important;
            overflow-x: hidden !important;
            flex: 1 1 auto !important;
            min-height: 0 !important;
            max-height: none !important;
            padding: 22px 24px !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 16px !important;
            -webkit-overflow-scrolling: touch !important;
            scrollbar-width: thin !important;
            scrollbar-color: #176b40 #edf6ec !important;
        }
        .modal-dialog .modal-body::-webkit-scrollbar,
        .modal-card .modal-body::-webkit-scrollbar,
        .modal-body::-webkit-scrollbar {
            width: 8px !important;
            display: block !important;
        }
        .modal-dialog .modal-body::-webkit-scrollbar-track,
        .modal-card .modal-body::-webkit-scrollbar-track,
        .modal-body::-webkit-scrollbar-track {
            background: #f0f5ee !important;
            border-radius: 4px !important;
        }
        .modal-dialog .modal-body::-webkit-scrollbar-thumb,
        .modal-card .modal-body::-webkit-scrollbar-thumb,
        .modal-body::-webkit-scrollbar-thumb {
            background: #176b40 !important;
            border-radius: 4px !important;
        }
        .modal-dialog .modal-body::-webkit-scrollbar-thumb:hover,
        .modal-card .modal-body::-webkit-scrollbar-thumb:hover,
        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #0d3e24 !important;
        }
        .modal-dialog .modal-footer,
        .modal-card .modal-footer,
        .modal-footer {
            flex-shrink: 0 !important;
            padding: 14px 24px !important;
            border-top: 1px solid #e5ede3 !important;
            background: #fbfdfa !important;
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 10px !important;
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
                <span class="current"><?= $myListingsOnly ? 'My Listings' : 'Marketplace' ?></span>
            </div>

            <!-- Page Title Header -->
            <div class="page-header">
                <div>
                    <h1><?= $myListingsOnly ? 'My marketplace listings' : 'Surplus materials marketplace' ?></h1>
                    <p>Exchange, claim, or source secondary materials directly from verified Malaysian SMEs. Listings support <strong>Buy</strong>, <strong>Borrow</strong>, or <strong>Take for Free</strong> with circular reusability tracking.</p>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <button type="button" class="btn-create-listing" onclick="openCreateListingModal()" style="background:var(--brand-green);color:#fff;border:none;padding:10px 18px;border-radius:6px;font:600 13px var(--font-main);display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>+ Create New Listing</span>
                    </button>
                </div>
            </div>

            <!-- Flash Status Messages -->
            <?php if (session('status')): ?>
                <div style="margin-bottom:18px;background:#e8f5e9;border:1px solid #c8e6c9;color:#1b5e20;padding:12px 16px;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><?= e(session('status')) ?></span>
                </div>
            <?php endif; ?>

            <?php if (session('error')): ?>
                <div style="margin-bottom:18px;background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:12px 16px;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span><?= e(session('error')) ?></span>
                </div>
            <?php endif; ?>

            <!-- View Tabs: All vs My Listings -->
            <div class="marketplace-tabs">
                <a href="/business/marketplace" class="mkt-tab-btn <?= !$myListingsOnly ? 'active' : '' ?>">
                    <span>🛒 All Marketplace Listings</span>
                    <span class="mkt-tab-badge"><?= \App\Models\MarketplaceListing::where('status', 'active')->count() ?></span>
                </a>
                <a href="/business/marketplace?my_listings=1" class="mkt-tab-btn <?= $myListingsOnly ? 'active' : '' ?>">
                    <span>📋 My Listings</span>
                    <span class="mkt-tab-badge"><?= $business->marketplaceListings()->where('status', 'active')->count() ?></span>
                </a>
            </div>

            <!-- Filter Card -->
            <section class="filter-card">
                <form method="GET" action="/business/marketplace">
                    <?php if ($myListingsOnly): ?>
                        <input type="hidden" name="my_listings" value="1">
                    <?php endif; ?>

                    <div class="filter-row-top">
                        <div class="filter-field">
                            <label for="mkt-search">Search Materials</label>
                            <input id="mkt-search" name="q" type="text" value="<?= e(request('q')) ?>" placeholder="Search material name or keyword...">
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
                            <label for="mkt-type">Listing Type</label>
                            <select id="mkt-type" name="listing_type">
                                <option value="">All Types (Buy/Borrow/Free)</option>
                                <option value="buy" <?= request('listing_type') === 'buy' ? 'selected' : '' ?>>🛒 Buy (For Sale)</option>
                                <option value="borrow" <?= request('listing_type') === 'borrow' ? 'selected' : '' ?>>🤝 Borrow (Loan)</option>
                                <option value="free" <?= request('listing_type') === 'free' ? 'selected' : '' ?>>🎁 Take for Free</option>
                            </select>
                        </div>

                        <?php if ($myListingsOnly): ?>
                        <div class="filter-field">
                            <label for="mkt-reusability">Reusability</label>
                            <select id="mkt-reusability" name="reusability">
                                <option value="">All Reusability</option>
                                <option value="reusable" <?= request('reusability') === 'reusable' ? 'selected' : '' ?>>↻ Reusable (Circular)</option>
                                <option value="not_reusable" <?= request('reusability') === 'not_reusable' ? 'selected' : '' ?>>⊘ Not Reusable (Scrap)</option>
                            </select>
                        </div>
                        <?php endif; ?>

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
                            <div style="display:flex;gap:6px;">
                                <button type="submit" class="btn-search" style="height:38px;padding:0 14px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    <span>Filter</span>
                                </button>
                                <?php if (request()->hasAny(['q', 'category', 'location', 'listing_type', 'reusability'])): ?>
                                    <a href="<?= $myListingsOnly ? '/business/marketplace?my_listings=1' : '/business/marketplace' ?>" class="btn-back-directory" style="height:38px;padding:0 12px;display:grid;place-items:center;" title="Reset filters">
                                        ✕
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Section Title -->
            <div class="section-header-row" style="margin-top:20px;">
                <div class="section-header-title">
                    <h2><?= $myListingsOnly ? 'Your Published Listings' : 'Live Material Listings' ?></h2>
                    <p><?= $listings->count() ?> listing(s) available in this view</p>
                </div>
            </div>

            <!-- Listings Grid -->
            <?php if ($listings->isEmpty()): ?>
                <div style="background:#ffffff;border:1px solid var(--card-border);border-radius:14px;padding:52px 24px;text-align:center;margin-bottom:32px;">
                    <span class="signin-brand-mark" style="margin:0 auto 14px;"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                    <h3 style="margin:0 0 6px;font-size:17px;color:#123321;font-weight:700;">No material listings found</h3>
                    <p style="margin:0 0 20px;font-size:13px;color:var(--text-muted);max-width:500px;margin-inline:auto;line-height:1.5;">
                        <?= $myListingsOnly ? 'You have not published any material listings yet. List surplus items with 1 to 5 photos to share them with verified Malaysian SMEs.' : 'No surplus materials matched your filter criteria. Try clearing filters or create a new listing.' ?>
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
            <?php else: ?>
                <div class="marketplace-grid">
                    <?php foreach ($listings as $idx => $item): ?>
                        <?php
                            $isOwner = $item->business_id === $business->id;
                            $listingType = $item->listing_type ?: ($item->is_free ? 'free' : 'buy');
                            $isReusable = ($item->reusability === 'reusable');
                            $images = $item->getAllImages();
                            $imgCount = count($images);
                            $cardId = 'listing-card-' . $item->id;
                        ?>
                        <article class="listing-card" id="<?= $cardId ?>"
                            onclick="openListingDetailModal(<?= $item->id ?>)"
                            style="cursor: pointer;"
                            data-title="<?= e($item->title) ?>"
                            data-category="<?= e($item->category) ?>"
                            data-condition="<?= e($item->condition ?? 'Used - Good') ?>"
                            data-type="<?= e($listingType) ?>"
                            data-reusability="<?= e($item->reusability) ?>"
                            data-price="<?= (float) $item->price ?>"
                            data-qty="<?= (float) $item->quantity ?>"
                            data-unit="<?= e($item->unit) ?>"
                            data-city="<?= e($item->location_city) ?>"
                            data-state="<?= e($item->location_state) ?>"
                            data-company="<?= e($item->business?->business_name ?: ($item->business?->name ?? 'Partner')) ?>"
                            data-desc="<?= e($item->description) ?>"
                            data-images='<?= json_encode($images) ?>'
                        >
                            <!-- Multi-Image Carousel / Gallery Area -->
                            <div class="listing-img-container" id="gallery-container-<?= $item->id ?>">
                                <?php if ($imgCount > 0): ?>
                                    <img src="<?= e($images[0]) ?>" alt="<?= e($item->title) ?>" class="listing-img-slide" id="img-display-<?= $item->id ?>" loading="lazy">
                                    
                                    <?php if ($imgCount > 1): ?>
                                        <button type="button" class="listing-img-nav-btn prev" onclick="slideListingImage(<?= $item->id ?>, -1, event)" aria-label="Previous image">&lsaquo;</button>
                                        <button type="button" class="listing-img-nav-btn next" onclick="slideListingImage(<?= $item->id ?>, 1, event)" aria-label="Next image">&rsaquo;</button>
                                        
                                        <div class="listing-dots-indicator" id="dots-container-<?= $item->id ?>">
                                            <?php for ($i = 0; $i < $imgCount; $i++): ?>
                                                <span class="listing-dot <?= $i === 0 ? 'active' : '' ?>"></span>
                                            <?php endfor; ?>
                                        </div>

                                        <div class="img-count-badge">
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                            <span id="img-counter-<?= $item->id ?>">1/<?= $imgCount ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="img-count-badge">
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                            <span>1 photo</span>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div style="width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#eef4ed;gap:8px;">
                                        <span class="signin-brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                                        <span style="font-size:11px;font-weight:600;color:#617769;">Verified SME Material</span>
                                    </div>
                                <?php endif; ?>

                                <!-- Reusability Top-Right Pill -->
                                <div class="reusability-corner-badge">
                                    <?php if ($isReusable): ?>
                                        <span class="badge-reusable" style="box-shadow:0 2px 6px rgba(0,0,0,0.12);background:#ffffff;font-size:11px;">↻ Reusable</span>
                                    <?php else: ?>
                                        <span class="badge-not-reusable" style="box-shadow:0 2px 6px rgba(0,0,0,0.12);background:#ffffff;font-size:11px;">⊘ Not Reusable</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="listing-body">
                                <div class="card-badges-row">
                                    <!-- Offer type badge -->
                                    <?php if ($listingType === 'free'): ?>
                                        <span class="offer-type-badge free">🎁 Free Surplus</span>
                                    <?php elseif ($listingType === 'borrow'): ?>
                                        <span class="offer-type-badge borrow">🤝 Borrow</span>
                                    <?php else: ?>
                                        <span class="offer-type-badge buy">🛒 Buy</span>
                                    <?php endif; ?>

                                    <span class="category-tag"><?= e($item->category) ?></span>
                                    <span class="condition-pill"><?= e($item->condition ?? 'Used - Good') ?></span>
                                </div>

                                <h3 class="listing-title" title="<?= e($item->title) ?>"><?= e($item->title) ?></h3>

                                <div class="listing-company-row">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <span><?= e($item->location_city) ?>, <?= e($item->location_state) ?> &middot; <strong><?= e($item->business?->business_name ?: ($item->business?->name ?? 'Partner')) ?></strong></span>
                                </div>

                                <!-- Pricing Box -->
                                <div class="listing-pricing-box">
                                    <div>
                                        <?php if ($listingType === 'free'): ?>
                                            <span class="price-main free-price">FREE</span>
                                            <span class="price-unit">(RM 0.00)</span>
                                        <?php elseif ($listingType === 'borrow'): ?>
                                            <span class="price-main borrow-price">RM <?= number_format((float) $item->price, 2) ?></span>
                                            <span class="price-unit"><?= (float) $item->price > 0 ? 'deposit / loan rate' : 'free loan' ?></span>
                                        <?php else: ?>
                                            <span class="price-main">RM <?= number_format((float) $item->price, 2) ?></span>
                                            <span class="price-unit">/ <?= e($item->unit) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="qty-badge"><?= (float) $item->quantity ?> <?= e($item->unit) ?></span>
                                </div>

                                <?php if (!empty($item->description)): ?>
                                    <p class="listing-desc-excerpt"><?= e($item->description) ?></p>
                                <?php endif; ?>

                                <div class="listing-card-footer">
                                    <button type="button" class="btn-view-quick" onclick="event.stopPropagation(); openListingDetailModal(<?= $item->id ?>)">
                                        Quick View
                                    </button>

                                    <?php if ($isOwner): ?>
                                        <div style="display:flex;align-items:center;gap:6px;" onclick="event.stopPropagation();">
                                            <span style="font-size:11px;font-weight:700;color:#166534;background:#f0fdf4;padding:4px 8px;border-radius:4px;border:1px solid #bbf7d0;">Your Listing</span>
                                            <form method="POST" action="/business/marketplace/<?= $item->id ?>" onsubmit="return confirm('Are you sure you want to remove this listing?');" style="margin:0;" onclick="event.stopPropagation();">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="submit" style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:5px 9px;border-radius:5px;font-size:11px;font-weight:600;cursor:pointer;" title="Delete listing">
                                                    ✕ Delete
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <button type="button" class="btn-claim-action" onclick="event.stopPropagation(); openClaimModal('<?= e(addslashes($item->title)) ?>', '<?= e(addslashes($item->business?->business_name ?: ($item->business?->name ?? 'Partner'))) ?>', '<?= e($listingType) ?>')">
                                            <?= $listingType === 'free' ? '🎁 Claim Free' : ($listingType === 'borrow' ? '🤝 Borrow' : 'Inquire &rarr;') ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Step 1: Inventory Item Picker Modal (Click Reusable Item First, Then Listing Modal Pops Out) -->
    <div class="modal-overlay" id="select-inventory-modal" style="display:none;" onclick="if(event.target===this)closeSelectInventoryModal()">
        <div class="modal-card" style="max-width:680px;">
            <div class="modal-header">
                <div>
                    <h3 style="margin:0;">Select Reusable Item from Inventory</h3>
                    <small style="color:#526b5a;font-size:12px;">Choose a verified reusable material from your inventory to create a marketplace listing:</small>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeSelectInventoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (!isset($myInventoryItems) || $myInventoryItems->isEmpty()): ?>
                    <div style="text-align:center;padding:36px 16px;">
                        <div style="font-size:42px;margin-bottom:10px;">📦</div>
                        <h4 style="margin:0 0 6px;color:#18422d;font-size:16px;">No Reusable Inventory Found</h4>
                        <p style="margin:0 0 18px;color:#556c5e;font-size:13px;max-width:440px;margin-left:auto;margin-right:auto;line-height:1.5;">
                            Public marketplace listings are strictly for verified reusable circular materials. (Non-reusable scrap items remain safely stored in your private owner account).
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
                <?php else: ?>
                    <div class="inv-picker-grid">
                        <?php foreach ($myInventoryItems as $inv): 
                            $invImg = $inv->getPrimaryImage() ? asset($inv->getPrimaryImage()) : 'https://placehold.co/120x120/e8f5e9/1b5e20?text=' . urlencode(substr($inv->name, 0, 3));
                            $invAllImgs = json_encode($inv->getAllImages());
                        ?>
                            <div class="inv-pick-card" onclick='selectInventoryItemForListing(<?= $inv->id ?>, <?= json_encode($inv->name) ?>, <?= json_encode($inv->category) ?>, <?= (float)$inv->quantity ?>, <?= json_encode($inv->unit) ?>, <?= json_encode($inv->description ?? '') ?>, <?= $invAllImgs ?>)'>
                                <div class="inv-pick-thumb">
                                    <img src="<?= e($invImg) ?>" alt="<?= e($inv->name) ?>">
                                </div>
                                <div class="inv-pick-details">
                                    <div class="inv-pick-name"><?= e($inv->name) ?></div>
                                    <div class="inv-pick-meta"><?= e($inv->category) ?></div>
                                    <div class="inv-pick-stock">
                                        <span class="inv-stock-badge"><?= (float)$inv->quantity ?> <?= e($inv->unit) ?></span>
                                        <span class="inv-reuse-tag">↻ Reusable</span>
                                    </div>
                                </div>
                                <div class="inv-pick-arrow">&rarr;</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top:14px;padding-top:12px;border-top:1px dashed #cadbc9;display:flex;justify-content:space-between;align-items:center;">
                        <small style="color:#617769;">Want to create a listing directly without selecting an item?</small>
                        <button type="button" onclick="openDirectCustomListing()" style="background:transparent;border:none;color:#166534;font-weight:700;font-size:12.5px;cursor:pointer;text-decoration:underline;">
                            + Enter custom details manually &rarr;
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeSelectInventoryModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Step 2: Create Listing Modal (Pops Out with Pre-filled Item Details and 1 to 5 Photos) -->
    <div class="modal-overlay" id="create-listing-modal" style="display:none;" onclick="if(event.target===this)closeCreateListingModal()">
        <div class="modal-card" style="max-width:640px;">
            <div class="modal-header">
                <div>
                    <h3 style="margin:0;">List Material on Marketplace</h3>
                    <small style="color:#526b5a;font-size:12px;">Publish surplus material for verified SMEs to buy, borrow, or take for free.</small>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeCreateListingModal()">&times;</button>
            </div>
            <form method="POST" action="/business/marketplace" enctype="multipart/form-data" onsubmit="return validateListingForm(this)">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="inventory_item_id" id="modal_inventory_item_id" value="">
                
                <div class="modal-body">
                    <!-- Sourced from Inventory Banner -->
                    <div id="mkt_sourced_banner" style="display:none;background:#f0fdf4;border:1.5px solid #86efac;border-radius:8px;padding:10px 14px;margin-bottom:2px;align-items:center;justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="font-size:20px;">📦</span>
                            <div>
                                <strong style="color:#15803d;font-size:13px;display:block;" id="mkt_sourced_name">Sourced from Inventory</strong>
                                <small style="color:#4b6354;font-size:11.5px;" id="mkt_sourced_stock">Stock Available: 0</small>
                            </div>
                        </div>
                        <button type="button" onclick="changeInventoryItemForListing()" style="background:#ffffff;border:1px solid #86efac;color:#15803d;padding:5px 12px;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;">
                            ✎ Change Item
                        </button>
                    </div>

                    <!-- 1 to 5 Photos Upload Section with Explicit Add Button & Slots -->
                    <div class="form-group">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                            <label style="margin:0;font-weight:700;color:#18422d;">
                                Material Photos <span style="color:#b91c1c;">*</span> <small style="font-weight:500;color:#556c5e;">(Min 1, Max 5 photos)</small>
                            </label>
                            <span id="mkt_img_counter" style="font-size:12px;font-weight:700;color:#18422d;">0 of 5 selected</span>
                        </div>

                        <div class="inv-photo-uploader-box" id="mkt_photo_uploader_box">
                            <!-- Hidden Real Form File Input (synchronized via DataTransfer) -->
                            <input type="file" id="listing_images_form_input" name="images[]" multiple accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;">

                            <div class="inv-photo-top-bar">
                                <button type="button" class="btn-choose-photos" id="btn_mkt_add_photos" onclick="triggerMktFileInput()">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    <span>+ Add Photos</span>
                                </button>
                                <span class="inv-photo-hint">Click "+ Add Photos" or any empty slot to add 1 to 5 images. Slot 1 is the cover photo.</span>
                            </div>

                            <!-- 5 Photo Slots Grid -->
                            <div class="inv-slots-grid" id="mkt_slots_grid">
                                <!-- Rendered dynamically by renderMktSlots() -->
                            </div>

                            <div id="mkt_img_error" style="display:none;margin-top:8px;color:#dc2626;font-size:12px;font-weight:600;">
                                ⚠ Please select at least 1 photo for this listing (maximum 5 photos).
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="create_title">Material Title *</label>
                        <input type="text" id="create_title" name="title" required placeholder="e.g. Surplus Clean Euro Pallets (Grade A)">
                    </div>

                    <div class="form-group">
                        <label for="create_category">Category *</label>
                        <select id="create_category" name="category" required>
                            <option value="Packaging & paper">Packaging & paper</option>
                            <option value="Furniture & wood products">Furniture & wood products</option>
                            <option value="Textiles & apparel">Textiles & apparel</option>
                            <option value="Electronics & electrical">Electronics & electrical</option>
                            <option value="Metal & fabrication">Metal & fabrication</option>
                            <option value="Plastics & rubber">Plastics & rubber</option>
                            <option value="Chemicals & lubricants">Chemicals & lubricants</option>
                            <option value="Food & beverage organic">Food & beverage organic</option>
                            <option value="Other">Other surplus material</option>
                        </select>
                    </div>

                    <!-- Reusability Classification: Strictly Reusable on Marketplace -->
                    <input type="hidden" name="reusability" value="reusable">
                    <div class="form-group">
                        <label>Reusability Classification</label>
                        <div style="padding:10px 14px;border-radius:8px;background:#f0f7ef;border:1.5px solid #b8dab4;display:flex;align-items:center;gap:10px;">
                            <span style="font-size:20px;color:#15803d;">↻</span>
                            <div>
                                <strong style="display:block;font-size:13px;color:#15803d;">Reusable Circular Material</strong>
                                <small style="color:#526b5a;font-size:11px;">Public marketplace listings are strictly for reusable items. Non-reusable scrap items remain securely stored in your owner inventory account only.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Listing Offer Type: Buy, Borrow, or Free -->
                    <div class="form-group">
                        <label>Listing Offer Option *</label>
                        <div class="offer-toggle-grid">
                            <label class="offer-toggle-card">
                                <input type="radio" name="listing_type" value="buy" checked onchange="handleModalOfferChange('buy')">
                                <span class="offer-icon">🛒</span>
                                <span class="offer-label">Buy (Sell)</span>
                                <span class="offer-sub">Set RM price</span>
                            </label>
                            <label class="offer-toggle-card">
                                <input type="radio" name="listing_type" value="borrow" onchange="handleModalOfferChange('borrow')">
                                <span class="offer-icon">🤝</span>
                                <span class="offer-label">Borrow</span>
                                <span class="offer-sub">Loan / deposit</span>
                            </label>
                            <label class="offer-toggle-card">
                                <input type="radio" name="listing_type" value="free" onchange="handleModalOfferChange('free')">
                                <span class="offer-icon">🎁</span>
                                <span class="offer-label">Free Surplus</span>
                                <span class="offer-sub">RM 0.00 Giveaway</span>
                            </label>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                        <div class="form-group">
                            <label for="create_quantity">Quantity *</label>
                            <input type="number" step="0.01" min="0.01" id="create_quantity" name="quantity" required placeholder="100">
                        </div>

                        <div class="form-group">
                            <label for="create_unit">Unit *</label>
                            <input type="text" id="create_unit" name="unit" required placeholder="units, kg, pcs">
                        </div>

                        <div class="form-group" id="modal_price_group">
                            <label for="create_price" id="modal_price_label">Price (RM) *</label>
                            <input type="number" step="0.01" min="0" id="create_price" name="price" value="0.00" required>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                        <div class="form-group">
                            <label for="create_condition">Condition</label>
                            <select id="create_condition" name="condition">
                                <option value="Used - Good">Used - Good</option>
                                <option value="New / Surplus">New / Surplus</option>
                                <option value="Like New">Like New</option>
                                <option value="Used - Fair">Used - Fair</option>
                                <option value="Recyclable Scrap">Recyclable Scrap</option>
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
                        <label for="create_desc">Material Description & Exchange Terms</label>
                        <textarea id="create_desc" name="description" rows="3" placeholder="Condition, pickup location, return expectations if borrowed, or handling details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeCreateListingModal()">Cancel</button>
                    <button type="submit" class="btn-save">Publish Listing &rarr;</button>
                </div>
            </form>
        </div>
    </div>
        </div>
    </div>

    <!-- Quick View / Detail Modal with Photo Gallery -->
    <div class="modal-overlay" id="listing-detail-modal" style="display:none;" onclick="if(event.target===this)closeListingDetailModal()">
        <div class="modal-card" style="max-width:680px;">
            <div class="modal-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span id="modal-detail-offer-badge" class="offer-type-badge buy">Buy</span>
                    <span id="modal-detail-reusable-badge" class="badge-reusable">↻ Reusable</span>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeListingDetailModal()">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Large Image Viewer + Thumbnails -->
                <div style="background:#f1f5f0;border-radius:10px;overflow:hidden;position:relative;height:280px;display:flex;align-items:center;justify-content:center;">
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
                    <h4 style="margin:0 0 6px;font-size:12px;text-transform:uppercase;color:#556c5e;letter-spacing:0.04em;">Description & Exchange Terms</h4>
                    <p id="modal-detail-desc" style="margin:0;font-size:13px;color:#2e4436;line-height:1.6;background:#fbfdfa;border:1px solid #e1e9df;padding:12px;border-radius:8px;"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeListingDetailModal()">Close</button>
                <button type="button" class="btn-claim-action" id="modal-detail-action-btn" onclick="executeClaimFromModal()">
                    Inquire Now &rarr;
                </button>
            </div>
        </div>
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
        if (total >= 5) {
            alert('Maximum of 5 photos reached. Remove a photo to add a different one.');
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
                var availableSlots = 5 - currentTotal;

                if (availableSlots <= 0) {
                    alert('Maximum of 5 photos allowed per listing.');
                    return;
                }

                var filesToAdd = Array.from(newFiles).slice(0, availableSlots);
                if (newFiles.length > availableSlots) {
                    alert('Only ' + availableSlots + ' more photo(s) could be added to stay within the 5-photo limit.');
                }

                filesToAdd.forEach(function(file) {
                    mktPhotosTransfer.items.add(file);
                });

                renderMktSlots();
            });
        }
    });

    // Render 5 photo slots visually
    function renderMktSlots() {
        var grid = document.getElementById('mkt_slots_grid');
        var counter = document.getElementById('mkt_img_counter');
        var err = document.getElementById('mkt_img_error');
        if (err) err.style.display = 'none';

        var existingCount = mktExistingImages.length;
        var newFiles = mktPhotosTransfer.files;
        var newCount = newFiles.length;
        var totalCount = existingCount + newCount;

        if (counter) {
            counter.textContent = totalCount + ' of 5 selected' + (totalCount === 0 ? ' (At least 1 required)' : '');
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

        // 3. Fill remaining slots up to 5 with empty placeholders
        for (var k = totalCount; k < 5; k++) {
            (function(slotNumber) {
                var emptyCard = document.createElement('div');
                emptyCard.className = 'inv-slot-card empty-slot';
                emptyCard.onclick = triggerMktFileInput;
                emptyCard.innerHTML = slotNumber === 0 ? 
                    '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span>+ Add</span>' : 
                    '<span>Slot ' + (slotNumber + 1) + '</span>';
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
            alert('Please select at least 1 photo for this listing (maximum 5 photos).');
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

    // Step 2: Clicking an Item First Selects It & Pops Out Listing Modal
    function selectInventoryItemForListing(id, name, cat, qty, unit, desc, images) {
        closeSelectInventoryModal();

        // Fill Form Fields
        document.getElementById('modal_inventory_item_id').value = id;
        document.getElementById('create_title').value = name;
        document.getElementById('create_category').value = cat;
        document.getElementById('create_quantity').value = qty;
        document.getElementById('create_unit').value = unit;
        document.getElementById('create_desc').value = desc || '';

        // Sourced from inventory banner
        var banner = document.getElementById('mkt_sourced_banner');
        if (banner) {
            document.getElementById('mkt_sourced_name').textContent = 'Sourced from Inventory: ' + name;
            document.getElementById('mkt_sourced_stock').textContent = 'Stock Available: ' + qty + ' ' + unit + ' (Verified Reusable)';
            banner.style.display = 'flex';
        }

        // Preload Photos from inventory item
        mktExistingImages = Array.isArray(images) ? images.slice(0, 5) : [];
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
        if (type === 'free') {
            priceInput.value = '0.00';
            priceInput.readOnly = true;
            priceInput.style.background = '#f1f5f9';
            priceLabel.textContent = 'Price (Free)';
        } else if (type === 'borrow') {
            priceInput.readOnly = false;
            priceInput.style.background = '#ffffff';
            priceLabel.textContent = 'Deposit / Rate (RM)';
            if (priceInput.value === '0.00') priceInput.value = '0.00';
        } else {
            priceInput.readOnly = false;
            priceInput.style.background = '#ffffff';
            priceLabel.textContent = 'Price (RM) *';
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
        var type = card.dataset.type;
        offerBadge.className = 'offer-type-badge ' + type;
        offerBadge.textContent = type === 'free' ? '🎁 Free Surplus' : (type === 'borrow' ? '🤝 Available to Borrow' : '🛒 For Sale');

        var reusableBadge = document.getElementById('modal-detail-reusable-badge');
        if (card.dataset.reusability === 'reusable') {
            reusableBadge.className = 'badge-reusable';
            reusableBadge.textContent = '↻ Reusable';
        } else {
            reusableBadge.className = 'badge-not-reusable';
            reusableBadge.textContent = '⊘ Not Reusable';
        }

        document.getElementById('modal-detail-title').textContent = card.dataset.title;
        document.getElementById('modal-detail-company-loc').textContent = card.dataset.company + ' · ' + card.dataset.city + ', ' + card.dataset.state;
        
        var price = parseFloat(card.dataset.price || 0);
        var priceText = type === 'free' ? 'FREE (RM 0.00)' : (type === 'borrow' ? (price > 0 ? 'RM ' + price.toFixed(2) + ' deposit' : 'Free Loan') : 'RM ' + price.toFixed(2) + ' / ' + card.dataset.unit);
        document.getElementById('modal-detail-price').textContent = priceText;
        document.getElementById('modal-detail-qty').textContent = card.dataset.qty + ' ' + card.dataset.unit;
        document.getElementById('modal-detail-condition').textContent = card.dataset.condition;
        document.getElementById('modal-detail-category').textContent = card.dataset.category;
        document.getElementById('modal-detail-desc').textContent = card.dataset.desc || 'No additional terms specified.';

        var actionBtn = document.getElementById('modal-detail-action-btn');
        actionBtn.textContent = type === 'free' ? '🎁 Claim Free Material' : (type === 'borrow' ? '🤝 Request to Borrow' : 'Inquire to Buy &rarr;');

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

    function executeClaimFromModal() {
        if (!currentActiveModalListing) return;
        closeListingDetailModal();
        openClaimModal(currentActiveModalListing.title, currentActiveModalListing.company, currentActiveModalListing.type);
    }

    function openClaimModal(title, bizName, offerType) {
        var verb = offerType === 'free' ? 'claim request for' : (offerType === 'borrow' ? 'borrowing request for' : 'purchase inquiry for');
        alert('EcoCycle exchange initiated: A ' + verb + ' "' + title + '" from ' + bizName + ' has been sent to their workspace.');
    }
    </script>
</body>
</html>
