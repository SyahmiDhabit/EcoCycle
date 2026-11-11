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
    <title><?= $myListingsOnly ? 'My Listings' : 'Marketplace' ?> — EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css">
    <style>
        .marketplace-tabs {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            border-bottom: 1px solid #dce6db;
            padding-bottom: 8px;
        }
        .mkt-tab-btn {
            padding: 8px 16px;
            border-radius: 6px;
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
            color: #123321;
            background: #e3ede1;
        }
        .marketplace-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
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
            position: relative;
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
        .card-badges-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }
        .listing-price-tag {
            font-size: 16px;
            font-weight: 700;
            color: var(--brand-green);
            margin: 6px 0 4px;
        }
        .listing-borrow-tag {
            font-size: 15px;
            font-weight: 700;
            color: #0284c7;
            margin: 6px 0 4px;
        }
        .listing-free-tag {
            font-size: 15px;
            font-weight: 700;
            color: #15803d;
            margin: 6px 0 4px;
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
        .btn-create-listing {
            background: var(--brand-green);
            color: #ffffff;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            font: 600 13px var(--font-main);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s ease;
        }
        .btn-create-listing:hover {
            background: #0e291b;
        }
        .filter-row-top {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
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
                    <p>Exchange, claim, or source secondary materials directly from verified Malaysian SMEs. All listings support <strong>Buy</strong>, <strong>Borrow</strong>, or <strong>Take for Free</strong> with circular reusability tracking.</p>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <button type="button" class="btn-create-listing" onclick="openCreateListingModal()">
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
                    🛒 All Marketplace Listings
                </a>
                <a href="/business/marketplace?my_listings=1" class="mkt-tab-btn <?= $myListingsOnly ? 'active' : '' ?>">
                    📋 My Listings (<?= $business->marketplaceListings()->where('status', 'active')->count() ?>)
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
                            <label for="mkt-type">Listing Type</label>
                            <select id="mkt-type" name="listing_type">
                                <option value="">All Types (Buy/Borrow/Free)</option>
                                <option value="buy" <?= request('listing_type') === 'buy' ? 'selected' : '' ?>>🛒 Buy (For Sale)</option>
                                <option value="borrow" <?= request('listing_type') === 'borrow' ? 'selected' : '' ?>>🤝 Borrow (Loan)</option>
                                <option value="free" <?= request('listing_type') === 'free' ? 'selected' : '' ?>>🎁 Take for Free</option>
                            </select>
                        </div>

                        <div class="filter-field">
                            <label for="mkt-reusability">Reusability</label>
                            <select id="mkt-reusability" name="reusability">
                                <option value="">All Reusability</option>
                                <option value="reusable" <?= request('reusability') === 'reusable' ? 'selected' : '' ?>>↻ Reusable (Circular)</option>
                                <option value="not_reusable" <?= request('reusability') === 'not_reusable' ? 'selected' : '' ?>>⊘ Not Reusable (Scrap)</option>
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

            <!-- Listings Header -->
            <div class="section-header-row">
                <div class="section-header-title">
                    <h2><?= $myListingsOnly ? 'Your Published Listings' : 'Live Material Listings' ?></h2>
                    <p><?= $listings->count() ?> listing(s) available in this view</p>
                </div>
            </div>

            <!-- Listings Grid -->
            <?php if ($listings->isEmpty()): ?>
                <div style="background:#ffffff;border:1px solid var(--card-border);border-radius:12px;padding:48px 24px;text-align:center;margin-bottom:32px;">
                    <span class="signin-brand-mark" style="margin:0 auto 14px;"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                    <h3 style="margin:0 0 6px;font-size:16px;color:#123321;">No material listings found</h3>
                    <p style="margin:0 0 16px;font-size:13px;color:var(--text-muted);max-width:480px;margin-inline:auto;line-height:1.5;">
                        <?= $myListingsOnly ? 'You have not published any material listings yet. List surplus items from your inventory to share with other businesses.' : 'No surplus materials matched your filter criteria. Try clearing some filters or publish a new listing.' ?>
                    </p>
                    <div style="display:flex;justify-content:center;gap:10px;">
                        <button type="button" class="btn-create-listing" onclick="openCreateListingModal()">
                            <span>+ Create New Listing</span>
                        </button>
                        <a href="/business/inventory" class="btn-back-directory">
                            <span>Go to Inventory &rarr;</span>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="marketplace-grid">
                    <?php foreach ($listings as $item): ?>
                        <?php
                            $isOwner = $item->business_id === $business->id;
                            $listingType = $item->listing_type ?: ($item->is_free ? 'free' : 'buy');
                            $isReusable = ($item->reusability === 'reusable');
                        ?>
                        <article class="listing-card">
                            <div class="listing-img-wrap">
                                <?php if (!empty($item->image_path)): ?>
                                    <img src="<?= e($item->image_path) ?>" alt="<?= e($item->title) ?>" loading="lazy">
                                <?php else: ?>
                                    <div style="width:100%;height:100%;display:grid;place-items:center;background:#f0f6ee;">
                                        <span class="signin-brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Top-right reusability pill on image -->
                                <div style="position:absolute;top:10px;right:10px;">
                                    <?php if ($isReusable): ?>
                                        <span class="badge-reusable" style="box-shadow:0 2px 6px rgba(0,0,0,0.12);background:#ffffff;">↻ Reusable</span>
                                    <?php else: ?>
                                        <span class="badge-not-reusable" style="box-shadow:0 2px 6px rgba(0,0,0,0.12);background:#ffffff;">⊘ Not Reusable</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="listing-body">
                                <div class="card-badges-row">
                                    <!-- Offer type badge -->
                                    <?php if ($listingType === 'free'): ?>
                                        <span class="offer-type-badge free">🎁 Take for Free</span>
                                    <?php elseif ($listingType === 'borrow'): ?>
                                        <span class="offer-type-badge borrow">🤝 Borrow</span>
                                    <?php else: ?>
                                        <span class="offer-type-badge buy">🛒 Buy</span>
                                    <?php endif; ?>

                                    <span class="category-tag"><?= e($item->category) ?></span>
                                    <span class="verified-pill">Verified SME</span>
                                </div>

                                <h3 class="card-title" style="margin-top:4px;font-size:15px;"><?= e($item->title) ?></h3>
                                <div class="card-location">
                                    <?= e($item->location_city) ?>, <?= e($item->location_state) ?> &middot;
                                    <strong><?= e($item->business?->business_name ?: ($item->business?->name ?? 'Partner')) ?></strong>
                                </div>

                                <!-- Pricing / Offer details -->
                                <?php if ($listingType === 'free'): ?>
                                    <div class="listing-free-tag">Free Surplus (RM 0.00)</div>
                                <?php elseif ($listingType === 'borrow'): ?>
                                    <div class="listing-borrow-tag">
                                        <?= (float) $item->price > 0 ? 'Deposit RM ' . number_format((float) $item->price, 2) : 'Free Loan (No deposit)' ?>
                                    </div>
                                <?php else: ?>
                                    <div class="listing-price-tag">
                                        RM <?= number_format((float) $item->price, 2) ?>
                                        <span style="font-size:11px;font-weight:400;color:#6f7b72;">/ <?= e($item->unit) ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($item->description)): ?>
                                    <p style="margin:4px 0 10px;font-size:12px;color:var(--text-secondary);line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        <?= e($item->description) ?>
                                    </p>
                                <?php endif; ?>

                                <div class="card-footer" style="padding-top:10px;margin-top:auto;">
                                    <span class="available-listings-text">Qty: <?= (float) $item->quantity ?> <?= e($item->unit) ?></span>

                                    <?php if ($isOwner): ?>
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <span style="font-size:11px;font-weight:700;color:#166534;background:#f0fdf4;padding:3px 7px;border-radius:4px;border:1px solid #bbf7d0;">Your Listing</span>
                                            <form method="POST" action="/business/marketplace/<?= $item->id ?>" onsubmit="return confirm('Are you sure you want to remove this listing from the marketplace?');" style="margin:0;">
                                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="submit" style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:600;cursor:pointer;" title="Delete listing">
                                                    ✕ Delete
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <button type="button" class="btn-view-profile" onclick="openClaimModal('<?= e(addslashes($item->title)) ?>', '<?= e(addslashes($item->business?->business_name ?: ($item->business?->name ?? 'Partner'))) ?>', '<?= e($listingType) ?>')">
                                            <?= $listingType === 'free' ? 'Claim Free &rarr;' : ($listingType === 'borrow' ? 'Borrow &rarr;' : 'Inquire &rarr;') ?>
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

    <!-- Create Listing Modal -->
    <div class="modal-overlay" id="create-listing-modal" style="display:none;" onclick="if(event.target===this)closeCreateListingModal()">
        <div class="modal-card" style="max-width:560px;">
            <div class="modal-header">
                <h3>List Material on Marketplace</h3>
                <button type="button" class="modal-close-btn" onclick="closeCreateListingModal()">&times;</button>
            </div>
            <form method="POST" action="/business/marketplace">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <div class="modal-body">
                    <!-- Optional Pre-fill from Inventory -->
                    <?php if (isset($myInventoryItems) && $myInventoryItems->isNotEmpty()): ?>
                        <div class="form-group" style="background:#f4f9f4;border:1px solid #cce5cc;border-radius:8px;padding:12px 14px;">
                            <label style="font-size:12px;font-weight:700;color:#1b5e20;margin-bottom:4px;display:block;">
                                📦 Select from Your Inventory (Quick Autofill)
                            </label>
                            <select id="modal_inventory_picker" onchange="autofillFromInventory(this)" style="font-size:13px;border-color:#a8cbb1;">
                                <option value="">-- Choose an item to autofill or type custom below --</option>
                                <?php foreach ($myInventoryItems as $inv): ?>
                                    <option value="<?= $inv->id ?>"
                                        data-name="<?= e($inv->name) ?>"
                                        data-cat="<?= e($inv->category) ?>"
                                        data-qty="<?= (float) $inv->quantity ?>"
                                        data-unit="<?= e($inv->unit) ?>"
                                        data-reusability="<?= e($inv->reusability ?? 'reusable') ?>"
                                    >
                                        <?= e($inv->name) ?> (<?= (float) $inv->quantity ?> <?= e($inv->unit) ?> &middot; <?= $inv->reusability === 'reusable' ? 'Reusable' : 'Not Reusable' ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="inventory_item_id" id="modal_inventory_item_id" value="">
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="create_title">Material Title *</label>
                        <input type="text" id="create_title" name="title" required placeholder="e.g. Surplus Clean Pallets (Grade A)">
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
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

                        <div class="form-group">
                            <label for="create_reusability">Reusability Classification *</label>
                            <select id="create_reusability" name="reusability" required>
                                <option value="reusable">↻ Reusable (Multi-use & Circular)</option>
                                <option value="not_reusable">⊘ Not Reusable (Single-use / Scrap)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Listing Offer Type: Buy, Borrow, or Free -->
                    <div class="form-group" style="background:#f8faf6;border:1px solid #e1e9df;border-radius:8px;padding:12px 14px;">
                        <label style="font-size:12px;font-weight:700;color:#1c3d2a;margin-bottom:8px;display:block;">
                            Listing Offer Option *
                        </label>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;">
                            <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;cursor:pointer;background:#ffffff;border:1px solid #d4e0d2;padding:8px 10px;border-radius:6px;">
                                <input type="radio" name="listing_type" value="buy" checked onchange="handleModalOfferChange('buy')">
                                <span>🛒 Buy (Sell)</span>
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;cursor:pointer;background:#ffffff;border:1px solid #d4e0d2;padding:8px 10px;border-radius:6px;">
                                <input type="radio" name="listing_type" value="borrow" onchange="handleModalOfferChange('borrow')">
                                <span>🤝 Borrow</span>
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;cursor:pointer;background:#ffffff;border:1px solid #d4e0d2;padding:8px 10px;border-radius:6px;">
                                <input type="radio" name="listing_type" value="free" onchange="handleModalOfferChange('free')">
                                <span>🎁 Free Surplus</span>
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

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
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

    function openCreateListingModal() {
        document.getElementById('create-listing-modal').style.display = 'flex';
    }
    function closeCreateListingModal() {
        document.getElementById('create-listing-modal').style.display = 'none';
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

    function autofillFromInventory(selectEl) {
        var opt = selectEl.options[selectEl.selectedIndex];
        if (!opt || !opt.value) {
            document.getElementById('modal_inventory_item_id').value = '';
            return;
        }
        document.getElementById('modal_inventory_item_id').value = opt.value;
        if (opt.dataset.name) document.getElementById('create_title').value = opt.dataset.name;
        if (opt.dataset.cat) document.getElementById('create_category').value = opt.dataset.cat;
        if (opt.dataset.qty) document.getElementById('create_quantity').value = opt.dataset.qty;
        if (opt.dataset.unit) document.getElementById('create_unit').value = opt.dataset.unit;
        if (opt.dataset.reusability) document.getElementById('create_reusability').value = opt.dataset.reusability;
    }

    function openClaimModal(title, bizName, offerType) {
        var verb = offerType === 'free' ? 'claim request for' : (offerType === 'borrow' ? 'borrowing request for' : 'purchase inquiry for');
        alert('EcoCycle exchange initiated: A ' + verb + ' "' + title + '" from ' + bizName + ' has been sent to their workspace.');
    }
    </script>
</body>
</html>
