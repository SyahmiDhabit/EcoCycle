<?php
/** @var \App\Models\User|null $business */
/** @var \Illuminate\Pagination\LengthAwarePaginator<\App\Models\InventoryItem> $items */
/** @var \Illuminate\Support\Collection<int, string> $categories */
/** @var string|null $search */
/** @var string|null $selectedCategory */
/** @var string|null $selectedStatus */
/** @var string|null $selectedReusability */
/** @var \Illuminate\Support\ViewErrorBag $errors */

$business = $business ?? \Illuminate\Support\Facades\Auth::guard()->user();
$items = $items ?? new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
$categories = $categories ?? collect();
$search = $search ?? '';
$selectedCategory = $selectedCategory ?? '';
$selectedStatus = $selectedStatus ?? '';
$selectedReusability = $selectedReusability ?? '';
$viewMode = $viewMode ?? 'table';
$errors = $errors ?? session('errors', new \Illuminate\Support\ViewErrorBag());
$activeNav = 'inventory';
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory Management — EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .inventory-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        .filter-form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .filter-input {
            padding: 8px 12px;
            border: 1px solid #cadbc9;
            border-radius: 6px;
            font: 400 13px var(--font-main);
            background: #fff;
            min-width: 220px;
        }
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #cadbc9;
            border-radius: 6px;
            font: 400 13px var(--font-main);
            background: #fff;
        }
        .btn-filter {
            padding: 8px 14px;
            border: 1px solid #78aa84;
            border-radius: 6px;
            background: #fff;
            color: var(--brand-green);
            font: 600 12px var(--font-main);
            cursor: pointer;
        }
        .btn-filter:hover {
            background: #eef5eb;
        }
        .btn-add-primary {
            padding: 9px 18px;
            border: 1px solid var(--brand-green);
            border-radius: 6px;
            background: var(--brand-green);
            color: #fff;
            font: 600 13px var(--font-main);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.15s ease;
        }
        .btn-add-primary:hover {
            background: #11502f;
        }
        .action-group {
            display: inline-flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }
        .btn-stock-in {
            padding: 4px 8px;
            border: 1px solid #a3cfbb;
            border-radius: 4px;
            background: #e8f5e9;
            color: #1b5e20;
            font: 600 11px var(--font-main);
            cursor: pointer;
        }
        .btn-stock-in:hover {
            background: #c8e6c9;
        }
        .btn-stock-out {
            padding: 4px 8px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            background: #fff5f5;
            color: #721c24;
            font: 600 11px var(--font-main);
            cursor: pointer;
        }
        .btn-stock-out:hover {
            background: #f8d7da;
        }
        .btn-edit {
            padding: 4px 8px;
            border: 1px solid #cadbc9;
            border-radius: 4px;
            background: #fff;
            color: #3b5042;
            font: 600 11px var(--font-main);
            cursor: pointer;
        }
        .btn-edit:hover {
            background: #f4f8ef;
        }
        .btn-delete {
            padding: 4px 8px;
            border: 1px solid #fecaca;
            border-radius: 4px;
            background: #fff;
            color: #dc2626;
            font: 600 11px var(--font-main);
            cursor: pointer;
        }
        .btn-delete:hover {
            background: #fee2e2;
        }
        .pagination-wrap {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }

        /* View Toggle Group */
        .view-switcher-wrap {
            display: inline-flex;
            align-items: center;
            background: #eef3ed;
            border-radius: 8px;
            padding: 3px;
            gap: 2px;
            border: 1px solid #d4dfd2;
        }
        .view-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border: none;
            background: transparent;
            color: #4a5c4e;
            font: 600 12px var(--font-main);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .view-toggle-btn:hover {
            color: #123321;
            background: rgba(255, 255, 255, 0.6);
        }
        .view-toggle-btn.active {
            background: #ffffff;
            color: #18422d;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            font-weight: 700;
        }

        /* Catalog Grid Layout */
        .inventory-catalog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(285px, 1fr));
            gap: 18px;
            margin-top: 14px;
        }
        .catalog-card {
            background: #ffffff;
            border: 1px solid #dbe6da;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 6px rgba(18, 51, 33, 0.04);
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            position: relative;
        }
        .catalog-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(18, 51, 33, 0.08);
            border-color: #b7ceb4;
        }
        .catalog-card-header {
            position: relative;
            height: 160px;
            background: #f1f6f0;
            overflow: hidden;
            cursor: pointer;
        }
        .catalog-card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.25s ease;
        }
        .catalog-card:hover .catalog-card-img {
            transform: scale(1.03);
        }
        .catalog-card-badges-top {
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            pointer-events: none;
        }
        .catalog-card-body {
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            flex: 1;
        }
        .catalog-card-title-row {
            cursor: pointer;
        }
        .catalog-card-title {
            font-size: 15px;
            font-weight: 700;
            color: #123321;
            margin: 0 0 3px;
            line-height: 1.3;
        }
        .catalog-card-sku {
            font-size: 11px;
            color: #6a7c6f;
            font-weight: 500;
        }
        .catalog-meta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 12px;
            color: #4a5c4e;
        }
        .catalog-cat-pill {
            background: #eef5eb;
            color: #1f5033;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 11px;
        }
        .catalog-condition-pill {
            background: #f4f7f4;
            color: #4a5c4e;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 11px;
            border: 1px solid #e1ebe0;
        }
        .catalog-stock-row {
            background: #f9fbf8;
            border: 1px solid #e8f0e6;
            border-radius: 8px;
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .catalog-stock-val {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }
        .catalog-stock-number {
            font-size: 16px;
            font-weight: 800;
            color: #18422d;
        }
        .catalog-stock-bar-wrap {
            height: 6px;
            background: #e2ede0;
            border-radius: 999px;
            overflow: hidden;
        }
        .catalog-stock-bar {
            height: 100%;
            border-radius: 999px;
        }
        .catalog-price-tag {
            display: flex;
            align-items: baseline;
            gap: 4px;
            font-size: 13px;
            font-weight: 700;
            color: #18422d;
        }
        .catalog-card-actions {
            padding: 10px 14px;
            border-top: 1px solid #eef3ed;
            background: #fcfdfa;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            flex-wrap: wrap;
        }

        /* Image Upload & Slots Preview */
        .inv-photo-uploader-box {
            border: 1.5px solid #c2d8bf;
            background: #fbfdfa;
            border-radius: 10px;
            padding: 16px;
            transition: border-color 0.15s ease;
        }
        .inv-photo-top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 12px;
        }
        .btn-choose-photos {
            padding: 8px 16px;
            border-radius: 6px;
            background: #18422d;
            color: #ffffff;
            font: 700 12px var(--font-main);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s ease;
        }
        .btn-choose-photos:hover {
            background: #103322;
        }
        .inv-photo-hint {
            font-size: 11px;
            color: #556c5e;
        }
        .inv-slots-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }
        .inv-slot-card {
            position: relative;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            border: 1.5px solid #c2d8bf;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .inv-slot-card.empty-slot {
            border: 1.5px dashed #cbd5e1;
            background: #f8fafc;
            cursor: pointer;
            flex-direction: column;
            gap: 4px;
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.15s ease;
        }
        .inv-slot-card.empty-slot:hover {
            border-color: #18422d;
            background: #eef6ec;
            color: #18422d;
        }
        .inv-slot-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .inv-slot-remove-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.7);
            color: #ffffff;
            border: none;
            font-size: 11px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s ease;
            z-index: 5;
        }
        .inv-slot-remove-btn:hover {
            background: #dc2626;
        }
        .inv-slot-cover-badge {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(14, 41, 27, 0.9);
            color: #ffffff;
            font-size: 9px;
            font-weight: 700;
            text-align: center;
            padding: 3px 0;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .btn-owner-only {
            padding: 4px 8px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            background: #f8fafc;
            color: #64748b;
            font: 600 11px var(--font-main);
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: default;
        }
        .btn-ai-advisor {
            padding: 4px 8px;
            border: 1px solid #93c5fd;
            border-radius: 4px;
            background: #eff6ff;
            color: #1e40af;
            font: 700 11px var(--font-main);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
        }
        .btn-ai-advisor:hover {
            background: #dbeafe;
            border-color: #60a5fa;
        }
        .btn-market-live {
            padding: 4px 8px;
            border: 1px solid #86efac;
            border-radius: 4px;
            background: #f0fdf4;
            color: #15803d;
            font: 700 11px var(--font-main);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
        }
        .btn-market-live:hover {
            background: #dcfce7;
            border-color: #4ade80;
            color: #166534;
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
        .modal-body::-webkit-scrollbar {
            width: 8px !important;
            display: block !important;
        }
        .modal-dialog .modal-body::-webkit-scrollbar-track,
        .modal-body::-webkit-scrollbar-track {
            background: #f0f5ee !important;
            border-radius: 4px !important;
        }
        .modal-dialog .modal-body::-webkit-scrollbar-thumb,
        .modal-body::-webkit-scrollbar-thumb {
            background: #176b40 !important;
            border-radius: 4px !important;
        }
        .modal-dialog .modal-body::-webkit-scrollbar-thumb:hover,
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

    <!-- Reusable Professional Top Navigation Bar -->
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <!-- Reusable Left Navigation Sidebar -->
        <?php include resource_path('views/business/partials/sidebar.php'); ?>

        <main class="dashboard-main">
            <?php if (session('status')): ?>
                <div class="dashboard-alert success" role="status" style="margin-bottom:16px;padding:12px 16px;border-radius:8px;background:#eaf3e8;border:1px solid #c7dbc5;color:#176b40;font-size:13px;font-weight:600;">
                    <?= e(session('status')) ?>
                </div>
            <?php endif; ?>
            <?php if ($errors->any()): ?>
                <div class="dashboard-alert error" role="alert" style="margin-bottom:16px;padding:12px 16px;border-radius:8px;background:#fff5f4;border:1px solid #fed7d2;color:#b91c1c;font-size:13px;font-weight:600;">
                    <?= e($errors->first()) ?>
                </div>
            <?php endif; ?>

            <!-- Page Title Header -->
            <div class="page-header">
                <div>
                    <h1>Inventory management</h1>
                    <p>Track materials, maintain safety thresholds, upload photos, and seamlessly list surplus to the marketplace.</p>
                </div>
                <button type="button" class="btn-add-primary" onclick="openAddModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>+ Add New Item</span>
                </button>
            </div>

            <section class="panel" style="margin-top:20px;">
                <div class="inventory-toolbar">
                    <form class="filter-form" method="GET" action="<?= route('business.inventory') ?>">
                        <input type="hidden" name="view" id="filter_view_input" value="<?= e($viewMode) ?>">
                        <input class="filter-input" type="search" name="search" value="<?= e($search) ?>" placeholder="Search by item name or SKU...">
                        
                        <select class="filter-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat) ?>" <?= $selectedCategory === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select class="filter-select" name="reusability">
                            <option value="">All Reusability</option>
                            <option value="reusable" <?= $selectedReusability === 'reusable' ? 'selected' : '' ?>>↻ Reusable</option>
                            <option value="not_reusable" <?= $selectedReusability === 'not_reusable' ? 'selected' : '' ?>>⊘ Not Reusable</option>
                        </select>

                        <select class="filter-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="in_stock" <?= $selectedStatus === 'in_stock' ? 'selected' : '' ?>>In stock</option>
                            <option value="low_stock" <?= $selectedStatus === 'low_stock' ? 'selected' : '' ?>>Low stock</option>
                            <option value="out_of_stock" <?= $selectedStatus === 'out_of_stock' ? 'selected' : '' ?>>Out of stock</option>
                        </select>

                        <button type="submit" class="btn-filter">Filter</button>
                        <?php if ($search || $selectedCategory || $selectedStatus || $selectedReusability): ?>
                            <a href="<?= route('business.inventory', ['view' => $viewMode]) ?>" style="font-size:12px;color:#6f7b72;text-decoration:underline;">Reset</a>
                        <?php endif; ?>
                    </form>

                    <div class="view-switcher-wrap" role="group" aria-label="Inventory View Layout">
                        <button type="button" class="view-toggle-btn <?= $viewMode !== 'catalog' ? 'active' : '' ?>" id="viewBtnTable" onclick="switchInventoryView('table')" title="Switch to Table View">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                            <span>Table</span>
                        </button>
                        <button type="button" class="view-toggle-btn <?= $viewMode === 'catalog' ? 'active' : '' ?>" id="viewBtnCatalog" onclick="switchInventoryView('catalog')" title="Switch to Catalog Grid View">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                            <span>Catalog Grid</span>
                        </button>
                    </div>
                </div>

                <?php if ($items->isNotEmpty()): ?>
                    <div id="inventory-table-container" style="display: <?= $viewMode === 'catalog' ? 'none' : 'block' ?>; overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width:28%;">Material / Item</th>
                                    <th style="width:14%;">Category</th>
                                    <th style="width:12%;">Reusability</th>
                                    <th style="width:13%;">Stock Level</th>
                                    <th style="width:11%;">Low Alert</th>
                                    <th style="width:10%;">Status</th>
                                    <th style="width:12%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td data-label="Item" onclick='openEditModal(<?= $item->id ?>, <?= json_encode($item->name) ?>, <?= json_encode($item->sku ?? "") ?>, <?= json_encode($item->category) ?>, <?= json_encode($item->unit) ?>, <?= $item->minimum_threshold ?? "null" ?>, <?= json_encode($item->notes ?? "") ?>, <?= json_encode($item->reusability ?? "reusable") ?>, <?= json_encode($item->description ?? "") ?>, <?= json_encode($item->getAllImages()) ?>, <?= json_encode((float) ($item->price ?? 0)) ?>, <?= json_encode($item->listing_type ?? "buy") ?>, <?= json_encode($item->condition ?? "Used - Good") ?>)' style="cursor:pointer;" title="Click to view/edit material details">
                                            <div style="display:flex;align-items:center;gap:12px;">
                                                <?php if ($item->image_path): ?>
                                                    <img src="<?= e($item->image_path) ?>" alt="<?= e($item->name) ?>" style="width:44px;height:44px;border-radius:8px;object-fit:cover;border:1px solid #d4e0d2;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,0.06);" loading="lazy">
                                                <?php else: ?>
                                                    <div style="width:44px;height:44px;border-radius:8px;background:#eef6ec;border:1px solid #cce5c8;display:grid;place-items:center;flex-shrink:0;color:#235839;font-weight:700;font-size:16px;">
                                                        📦
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <strong style="color:#123321;font-size:14px;"><?= e($item->name) ?></strong>
                                                    <?php if ($item->sku): ?>
                                                        <small style="display:block;color:#6f7b72;font-size:11px;">SKU: <?= e($item->sku) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="Category"><?= e($item->category) ?></td>
                                        <td data-label="Reusability">
                                            <?php if (($item->reusability ?? 'reusable') === 'reusable'): ?>
                                                <span class="badge-reusable" title="Reusable circular material - automatically published to Marketplace & Directory">↻ Reusable</span>
                                            <?php else: ?>
                                                <span class="badge-not-reusable" title="Single-use scrap - private internal inventory only">⊘ Scrap</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Stock Level">
                                            <span style="font-size:14px;font-weight:700;color:#173e2c;"><?= (float) $item->quantity ?></span> <?= e($item->unit) ?>
                                        </td>
                                        <td data-label="Threshold">
                                            <?= $item->minimum_threshold !== null ? (float) $item->minimum_threshold . ' ' . e($item->unit) : '<span style="color:#8a9990;">None</span>' ?>
                                        </td>
                                        <td data-label="Status">
                                            <?php if ($item->status === 'out_of_stock'): ?>
                                                <span class="status status-out-of-stock">Out of stock</span>
                                            <?php elseif ($item->status === 'low_stock'): ?>
                                                <span class="status status-low-stock">Low stock</span>
                                            <?php else: ?>
                                                <span class="status status-in-stock">In stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Actions">
                                            <div class="action-group">
                                                <button type="button" class="btn-stock-in" title="Add stock" onclick="openAdjustModal(<?= $item->id ?>, '<?= e(addslashes($item->name)) ?>', '<?= e($item->unit) ?>', <?= (float)$item->quantity ?>, 'stock_in')">
                                                    + In
                                                </button>
                                                <button type="button" class="btn-stock-out" title="Deduct stock" onclick="openAdjustModal(<?= $item->id ?>, '<?= e(addslashes($item->name)) ?>', '<?= e($item->unit) ?>', <?= (float)$item->quantity ?>, 'stock_out')">
                                                    - Out
                                                </button>
                                                <?php if (($item->reusability ?? 'reusable') === 'reusable'): ?>
                                                    <a href="<?= route('business.marketplace', ['search' => $item->name]) ?>" class="btn-market-live" title="Automatically live on Marketplace & Business Directory. Click to view on Marketplace.">
                                                        🛒 On Market
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?= route('business.helper', ['item_id' => $item->id]) ?>" class="btn-ai-advisor" title="Forward to EcoCycle Helper: Circular Economy Analysis & GPS Recycling Centres">
                                                        💡 EcoCycle Helper
                                                    </a>
                                                <?php endif; ?>
                                                <button type="button" class="btn-edit" title="Edit details & marketplace pricing" onclick='openEditModal(<?= $item->id ?>, <?= json_encode($item->name) ?>, <?= json_encode($item->sku ?? "") ?>, <?= json_encode($item->category) ?>, <?= json_encode($item->unit) ?>, <?= $item->minimum_threshold ?? "null" ?>, <?= json_encode($item->notes ?? "") ?>, <?= json_encode($item->reusability ?? "reusable") ?>, <?= json_encode($item->description ?? "") ?>, <?= json_encode($item->getAllImages()) ?>, <?= json_encode((float) ($item->price ?? 0)) ?>, <?= json_encode($item->listing_type ?? "buy") ?>, <?= json_encode($item->condition ?? "Used - Good") ?>)'>
                                                    Edit
                                                </button>
                                                <form method="POST" action="<?= route('business.inventory.destroy', $item) ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this inventory item?');">
                                                    <?= csrf_field() ?>
                                                    <?= method_field('DELETE') ?>
                                                    <button type="submit" class="btn-delete" title="Delete item">✕</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div id="inventory-catalog-container" class="inventory-catalog-grid" style="display: <?= $viewMode === 'catalog' ? 'grid' : 'none' ?>;">
                        <?php foreach ($items as $item): ?>
                            <div class="catalog-card">
                                <div class="catalog-card-header" onclick='openEditModal(<?= $item->id ?>, <?= json_encode($item->name) ?>, <?= json_encode($item->sku ?? "") ?>, <?= json_encode($item->category) ?>, <?= json_encode($item->unit) ?>, <?= $item->minimum_threshold ?? "null" ?>, <?= json_encode($item->notes ?? "") ?>, <?= json_encode($item->reusability ?? "reusable") ?>, <?= json_encode($item->description ?? "") ?>, <?= json_encode($item->getAllImages()) ?>, <?= json_encode((float) ($item->price ?? 0)) ?>, <?= json_encode($item->listing_type ?? "buy") ?>, <?= json_encode($item->condition ?? "Used - Good") ?>)' title="Click to view/edit details">
                                    <?php if ($item->image_path): ?>
                                        <img src="<?= e($item->image_path) ?>" alt="<?= e($item->name) ?>" class="catalog-card-img" loading="lazy">
                                    <?php else: ?>
                                        <div style="width:100%;height:100%;display:grid;place-items:center;background:#edf5eb;color:#235839;font-size:38px;">
                                            📦
                                        </div>
                                    <?php endif; ?>
                                    <div class="catalog-card-badges-top">
                                        <?php if (($item->reusability ?? 'reusable') === 'reusable'): ?>
                                            <span class="badge-reusable" style="font-size:11px;padding:3px 8px;box-shadow:0 1px 3px rgba(0,0,0,0.12);">↻ Reusable</span>
                                        <?php else: ?>
                                            <span class="badge-not-reusable" style="font-size:11px;padding:3px 8px;box-shadow:0 1px 3px rgba(0,0,0,0.12);">⊘ Scrap</span>
                                        <?php endif; ?>

                                        <?php if ($item->status === 'out_of_stock' || (float)$item->quantity <= 0): ?>
                                            <span class="status status-out-of-stock" style="font-size:11px;box-shadow:0 1px 3px rgba(0,0,0,0.12);">Out of stock</span>
                                        <?php elseif ($item->status === 'low_stock' || ($item->minimum_threshold !== null && (float)$item->quantity <= (float)$item->minimum_threshold)): ?>
                                            <span class="status status-low-stock" style="font-size:11px;box-shadow:0 1px 3px rgba(0,0,0,0.12);">Low stock</span>
                                        <?php else: ?>
                                            <span class="status status-in-stock" style="font-size:11px;box-shadow:0 1px 3px rgba(0,0,0,0.12);">In stock</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="catalog-card-body">
                                    <div class="catalog-card-title-row" onclick='openEditModal(<?= $item->id ?>, <?= json_encode($item->name) ?>, <?= json_encode($item->sku ?? "") ?>, <?= json_encode($item->category) ?>, <?= json_encode($item->unit) ?>, <?= $item->minimum_threshold ?? "null" ?>, <?= json_encode($item->notes ?? "") ?>, <?= json_encode($item->reusability ?? "reusable") ?>, <?= json_encode($item->description ?? "") ?>, <?= json_encode($item->getAllImages()) ?>, <?= json_encode((float) ($item->price ?? 0)) ?>, <?= json_encode($item->listing_type ?? "buy") ?>, <?= json_encode($item->condition ?? "Used - Good") ?>)'>
                                        <h4 class="catalog-card-title"><?= e($item->name) ?></h4>
                                        <?php if ($item->sku): ?>
                                            <span class="catalog-card-sku">SKU: <?= e($item->sku) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="catalog-meta-row">
                                        <span class="catalog-cat-pill"><?= e($item->category) ?></span>
                                        <span class="catalog-condition-pill"><?= e($item->condition ?? 'Used - Good') ?></span>
                                    </div>

                                    <div class="catalog-stock-row">
                                        <div class="catalog-stock-val">
                                            <div>
                                                <span class="catalog-stock-number"><?= (float) $item->quantity ?></span>
                                                <span style="font-size:12px;font-weight:600;color:#556c5e;"><?= e($item->unit) ?></span>
                                            </div>
                                            <div style="font-size:11px;color:#6f7b72;">
                                                <?php if ($item->minimum_threshold !== null): ?>
                                                    Min Alert: <strong><?= (float) $item->minimum_threshold ?> <?= e($item->unit) ?></strong>
                                                <?php else: ?>
                                                    Min Alert: <em>None</em>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php
                                            $qty = (float) $item->quantity;
                                            $thresh = $item->minimum_threshold !== null ? (float) $item->minimum_threshold : null;
                                            $barPct = 100;
                                            $barColor = '#16a34a';
                                            if ($qty <= 0) {
                                                $barPct = 0;
                                                $barColor = '#dc2626';
                                            } elseif ($thresh !== null && $thresh > 0) {
                                                $barPct = max(5, min(100, ($qty / ($thresh * 2)) * 100));
                                                if ($qty <= $thresh) {
                                                    $barColor = '#d97706';
                                                }
                                            }
                                        ?>
                                        <div class="catalog-stock-bar-wrap" title="Stock availability ratio">
                                            <div class="catalog-stock-bar" style="width: <?= $barPct ?>%; background: <?= $barColor ?>;"></div>
                                        </div>
                                    </div>

                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto;">
                                        <div class="catalog-price-tag">
                                            <?php if (($item->listing_type ?? 'buy') === 'free'): ?>
                                                <span style="color:#16a34a;font-weight:800;">FREE</span>
                                            <?php elseif (($item->listing_type ?? 'buy') === 'borrow'): ?>
                                                <span style="color:#2563eb;font-weight:700;">🤝 Borrow</span>
                                                <?php if ((float) ($item->price ?? 0) > 0): ?>
                                                    <span style="font-size:11px;color:#64748b;">(RM <?= number_format((float) $item->price, 2) ?>)</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span>RM <?= number_format((float) ($item->price ?? 0), 2) ?></span>
                                                <span style="font-size:11px;font-weight:500;color:#6a7c6f;">/ <?= e($item->unit) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="catalog-card-actions">
                                    <div style="display:inline-flex;gap:4px;">
                                        <button type="button" class="btn-stock-in" title="Add stock" onclick="openAdjustModal(<?= $item->id ?>, '<?= e(addslashes($item->name)) ?>', '<?= e($item->unit) ?>', <?= (float)$item->quantity ?>, 'stock_in')">
                                            + In
                                        </button>
                                        <button type="button" class="btn-stock-out" title="Deduct stock" onclick="openAdjustModal(<?= $item->id ?>, '<?= e(addslashes($item->name)) ?>', '<?= e($item->unit) ?>', <?= (float)$item->quantity ?>, 'stock_out')">
                                            - Out
                                        </button>
                                    </div>

                                    <div style="display:inline-flex;gap:4px;align-items:center;">
                                        <?php if (($item->reusability ?? 'reusable') === 'reusable'): ?>
                                            <a href="<?= route('business.marketplace', ['search' => $item->name]) ?>" class="btn-market-live" title="Automatically live on Marketplace & Business Directory">
                                                🛒 Market
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= route('business.helper', ['item_id' => $item->id]) ?>" class="btn-ai-advisor" title="Forward to EcoCycle Helper: AI Circular Recommendations & GPS Recycling Centres">
                                                💡 Helper
                                            </a>
                                        <?php endif; ?>

                                        <button type="button" class="btn-edit" title="Edit details" onclick='openEditModal(<?= $item->id ?>, <?= json_encode($item->name) ?>, <?= json_encode($item->sku ?? "") ?>, <?= json_encode($item->category) ?>, <?= json_encode($item->unit) ?>, <?= $item->minimum_threshold ?? "null" ?>, <?= json_encode($item->notes ?? "") ?>, <?= json_encode($item->reusability ?? "reusable") ?>, <?= json_encode($item->description ?? "") ?>, <?= json_encode($item->getAllImages()) ?>, <?= json_encode((float) ($item->price ?? 0)) ?>, <?= json_encode($item->listing_type ?? "buy") ?>, <?= json_encode($item->condition ?? "Used - Good") ?>)'>
                                            Edit
                                        </button>

                                        <form method="POST" action="<?= route('business.inventory.destroy', $item) ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this inventory item?');">
                                            <?= csrf_field() ?>
                                            <?= method_field('DELETE') ?>
                                            <button type="submit" class="btn-delete" title="Delete item">✕</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="pagination-wrap">
                        <?= $items->links()?->toHtml() ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-card" style="padding:48px 24px;text-align:center;">
                        <span class="signin-brand-mark" style="margin:0 auto 12px;"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                        <h3 style="margin:0 0 6px;font-size:16px;color:#123321;">No materials found in inventory</h3>
                        <p style="margin:0 0 16px;color:var(--text-muted);font-size:13px;">Add your secondary materials, production cutoffs, or durable assets with photos to manage stock and share with partners.</p>
                        <button type="button" class="btn-add-primary" onclick="openAddModal()">+ Add New Item</button>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

<!-- Add Inventory Modal (Upgraded with 1 to 5 Images Support & Polished Design) -->
<div class="modal-backdrop" id="add-modal" style="display:none;" onclick="if(event.target === this) closeAddModal()">
    <div class="modal-dialog modal-dialog-large">
        <div class="modal-header">
            <h3>Add New Inventory Item</h3>
            <button type="button" class="modal-close-btn" onclick="closeAddModal()">✕</button>
        </div>
        <form method="POST" action="<?= route('business.inventory.store') ?>" enctype="multipart/form-data" onsubmit="return validateInventoryAddForm(this)">
            <?= csrf_field() ?>
            <div class="modal-body">
                <!-- 1 to 5 Photos Upload Section with Explicit Add Button & Slots -->
                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <label style="margin:0;font-weight:700;color:#18422d;">
                            Material Photos <span style="color:#b91c1c;">*</span> <small style="font-weight:500;color:#556c5e;">(Min 1, Max 5 photos)</small>
                        </label>
                        <span id="add_img_counter" style="font-size:12px;font-weight:700;color:#18422d;">0 of 5 selected (Min 1 required)</span>
                    </div>

                    <div class="inv-photo-uploader-box" id="add_photo_uploader_box">
                        <!-- Hidden Real Form File Input (synchronized via DataTransfer) -->
                        <input type="file" id="add_images_form_input" name="images[]" multiple style="display:none;">
                        <!-- Hidden Picker File Input (resets each time so user can click + Add repeatedly) -->
                        <input type="file" id="add_images_picker" accept="image/png,image/jpeg,image/webp,image/jpg" multiple style="display:none;" onchange="handleNewAddImages(this)">

                        <div class="inv-photo-top-bar">
                            <button type="button" class="btn-choose-photos" id="btn_add_photos" onclick="document.getElementById('add_images_picker').click()">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                <span>+ Add Photos</span>
                            </button>
                            <span class="inv-photo-hint">Click "+ Add Photos" or any empty slot to add 1 to 5 images. Slot 1 is the cover photo.</span>
                        </div>

                        <!-- 5 Visual Slots Grid -->
                        <div class="inv-slots-grid" id="add_slots_grid">
                            <!-- Slots rendered dynamically via renderAddSlots() -->
                        </div>

                        <div id="add_img_error" style="display:none;margin-top:10px;padding:8px 12px;border-radius:6px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:12px;font-weight:600;">
                            ⚠️ Please add at least 1 photo for this material (minimum 1, maximum 5 photos required).
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="add_name">Item / Material Name *</label>
                    <input id="add_name" name="name" type="text" required placeholder="e.g. Reclaimed Timber Planks, Heavy Duty Pallets">
                </div>

                <div class="form-group">
                    <label for="add_description">Description / Material Specifications *</label>
                    <textarea id="add_description" name="description" rows="2" required placeholder="e.g. Clean reclaimed timber planks, kiln-dried, free of nails, suitable for secondary manufacturing or carpentry."></textarea>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="add_sku">SKU / Code (optional)</label>
                        <input id="add_sku" name="sku" type="text" placeholder="e.g. WOD-PLK-01">
                    </div>
                    <div class="form-group">
                        <label for="add_category">Category *</label>
                        <select id="add_category" name="category" required>
                            <option value="">Select category</option>
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
                </div>

                <div class="form-group">
                    <label>
                        <span>Reusability Classification *</span>
                        <small style="color:#617769;font-weight:400;">Multi-use circular vs single-use scrap</small>
                    </label>
                    <div class="reusability-toggle-grid">
                        <label class="reusability-toggle-card">
                            <input type="radio" name="reusability" value="reusable" checked onchange="toggleReusabilityNotice('add', this.value)">
                            <div class="toggle-card-content">
                                <span class="toggle-title" style="color:#15803d;">↻ Reusable Material</span>
                                <span class="toggle-desc">Durable, multi-use. Automatically published to Marketplace and Business Directory.</span>
                            </div>
                        </label>
                        <label class="reusability-toggle-card">
                            <input type="radio" name="reusability" value="not_reusable" onchange="toggleReusabilityNotice('add', this.value)">
                            <div class="toggle-card-content">
                                <span class="toggle-title" style="color:#475569;">⊘ Not Reusable (Scrap)</span>
                                <span class="toggle-desc">Single-use cutoffs / scrap. Kept private for waste tracking & AI Scrap Advisor.</span>
                            </div>
                        </label>
                    </div>

                    <!-- Marketplace & Directory Trade Settings (Automatic Publication for Reusable) -->
                    <div id="add_marketplace_trade_panel" style="margin-top:12px;background:#f4fbf6;border:1.5px solid #bbf0cb;border-radius:8px;padding:14px 16px;">
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px;">
                            <span style="font-size:16px;">🛒</span>
                            <strong style="color:#0f5132;font-size:13px;">Marketplace & Directory Publication Settings</strong>
                        </div>
                        <p style="margin:0 0 12px;font-size:12px;color:#1e4620;line-height:1.45;">
                            Reusable materials are <strong>automatically published to the EcoCycle Marketplace</strong> and showcased under your company profile in the Business Directory.
                        </p>
                        <div class="form-row-3" style="margin-bottom:0;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label for="add_listing_type">Listing Type *</label>
                                <select id="add_listing_type" name="listing_type" onchange="toggleAddPriceField(this.value)">
                                    <option value="buy">Sell (For Sale)</option>
                                    <option value="borrow">Lend (Borrow)</option>
                                    <option value="free">Free (Donate / Giveaway)</option>
                                </select>
                            </div>
                            <div class="form-group" id="add_price_group" style="margin-bottom:0;">
                                <label for="add_price">Price per Unit (RM)</label>
                                <input id="add_price" name="price" type="number" step="0.01" min="0" placeholder="e.g. 25.00">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label for="add_condition">Material Condition</label>
                                <select id="add_condition" name="condition">
                                    <option value="Used - Good">Used - Good</option>
                                    <option value="Like New">Like New</option>
                                    <option value="Surplus Unopened">Surplus Unopened</option>
                                    <option value="Used - Fair">Used - Fair</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="add_reusability_scrap_notice" style="display:none;margin-top:12px;padding:12px 14px;border-radius:8px;background:#fef7f6;border:1.5px solid #fecaca;color:#991b1b;font-size:12px;line-height:1.45;">
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                            <span style="font-size:15px;">🔒</span>
                            <strong style="color:#7f1d1d;">Internal Scrap Only (Not Listed on Marketplace)</strong>
                        </div>
                        This material is non-reusable scrap. It will remain saved in your private inventory for waste monitoring. It will <strong>NOT</strong> appear on the public marketplace or business directory.
                        <div style="margin-top:6px;color:#1e3a8a;font-weight:600;">
                            💡 EcoCycle AI Scrap Advisor is available directly from your inventory table to recommend compliant recycling & recovery solutions.
                        </div>
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group">
                        <label for="add_quantity">Initial Quantity *</label>
                        <input id="add_quantity" name="quantity" type="number" step="0.01" min="0" required placeholder="e.g. 50">
                    </div>
                    <div class="form-group">
                        <label for="add_unit">Unit of Measure *</label>
                        <input id="add_unit" name="unit" type="text" required placeholder="e.g. kg, pcs, units">
                    </div>
                    <div class="form-group">
                        <label for="add_threshold">Alert Threshold</label>
                        <input id="add_threshold" name="minimum_threshold" type="number" step="0.01" min="0" placeholder="e.g. 10">
                    </div>
                </div>

                <div class="form-group">
                    <label for="add_notes">Notes / Storage Specifications (optional)</label>
                    <textarea id="add_notes" name="notes" rows="2" placeholder="e.g. Stored in dry warehouse, ready for exchange"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn-save">+ Save Material</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Inventory Modal (Supports Adding/Updating Photos) -->
<div class="modal-backdrop" id="edit-modal" style="display:none;" onclick="if(event.target === this) closeEditModal()">
    <div class="modal-dialog modal-dialog-large">
        <div class="modal-header">
            <h3>Edit Item Details</h3>
            <button type="button" class="modal-close-btn" onclick="closeEditModal()">✕</button>
        </div>
        <form id="edit-form" method="POST" action="" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?= method_field('PUT') ?>
            <div class="modal-body">
                <!-- Update Photos with Explicit Add Button & Slots -->
                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <label style="margin:0;font-weight:700;color:#18422d;">
                            Update Material Photos <small style="font-weight:500;color:#556c5e;">(1 to 5 images - Optional)</small>
                        </label>
                        <span id="edit_img_counter" style="font-size:12px;font-weight:700;color:#18422d;">Leave empty to keep current photos</span>
                    </div>

                    <div class="inv-photo-uploader-box" id="edit_photo_uploader_box">
                        <input type="file" id="edit_images_form_input" name="images[]" multiple style="display:none;">
                        <input type="file" id="edit_images_picker" accept="image/png,image/jpeg,image/webp,image/jpg" multiple style="display:none;" onchange="handleNewEditImages(this)">

                        <div class="inv-photo-top-bar">
                            <button type="button" class="btn-choose-photos" id="btn_edit_photos" onclick="document.getElementById('edit_images_picker').click()">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                <span>+ Add / Replace Photos</span>
                            </button>
                            <span class="inv-photo-hint">Upload new photos (up to 5) to replace current photos. Slot 1 is cover photo.</span>
                        </div>

                        <div class="inv-slots-grid" id="edit_slots_grid">
                            <!-- Slots rendered dynamically -->
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_name">Item / Material Name *</label>
                    <input id="edit_name" name="name" type="text" required>
                </div>

                <div class="form-group">
                    <label for="edit_description">Description / Material Specifications</label>
                    <textarea id="edit_description" name="description" rows="2" placeholder="e.g. Detailed material specifications, condition, and handling notes."></textarea>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label for="edit_sku">SKU / Code</label>
                        <input id="edit_sku" name="sku" type="text">
                    </div>
                    <div class="form-group">
                        <label for="edit_category">Category *</label>
                        <select id="edit_category" name="category" required>
                            <option value="Packaging & paper">Packaging & paper</option>
                            <option value="Furniture & wood products">Furniture & wood products</option>
                            <option value="Textiles & apparel">Textiles & apparel</option>
                            <option value="Electronics & electrical">Electronics & electrical</option>
                            <option value="Metal & fabrication">Metal & fabrication</option>
                            <option value="Plastics & rubber">Plastics & rubber</option>
                            <option value="Chemicals & lubricants">Chemicals & lubricants</option>
                            <option value="Food & beverage organic">Food & beverage organic</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Reusability Classification *</label>
                    <div class="reusability-toggle-grid">
                        <label class="reusability-toggle-card">
                            <input type="radio" name="reusability" id="edit_reusability_reusable" value="reusable" onchange="toggleReusabilityNotice('edit', this.value)">
                            <div class="toggle-card-content">
                                <span class="toggle-title" style="color:#15803d;">↻ Reusable</span>
                                <span class="toggle-desc">Durable, multi-use, circular (Synced with Marketplace & Directory)</span>
                            </div>
                        </label>
                        <label class="reusability-toggle-card">
                            <input type="radio" name="reusability" id="edit_reusability_not_reusable" value="not_reusable" onchange="toggleReusabilityNotice('edit', this.value)">
                            <div class="toggle-card-content">
                                <span class="toggle-title" style="color:#475569;">⊘ Not Reusable</span>
                                <span class="toggle-desc">Single-use scrap / cutoffs (Private internal inventory)</span>
                            </div>
                        </label>
                    </div>

                    <!-- Marketplace & Directory Trade Settings (Automatic Sync for Reusable) -->
                    <div id="edit_marketplace_trade_panel" style="margin-top:12px;background:#f4fbf6;border:1.5px solid #bbf0cb;border-radius:8px;padding:14px 16px;">
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px;">
                            <span style="font-size:16px;">🛒</span>
                            <strong style="color:#0f5132;font-size:13px;">Marketplace & Directory Publication Settings</strong>
                        </div>
                        <div class="form-row-3" style="margin-bottom:0;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label for="edit_listing_type">Listing Type *</label>
                                <select id="edit_listing_type" name="listing_type" onchange="toggleEditPriceField(this.value)">
                                    <option value="buy">Sell (For Sale)</option>
                                    <option value="borrow">Lend (Borrow)</option>
                                    <option value="free">Free (Donate / Giveaway)</option>
                                </select>
                            </div>
                            <div class="form-group" id="edit_price_group" style="margin-bottom:0;">
                                <label for="edit_price">Price per Unit (RM)</label>
                                <input id="edit_price" name="price" type="number" step="0.01" min="0" placeholder="e.g. 25.00">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label for="edit_condition">Material Condition</label>
                                <select id="edit_condition" name="condition">
                                    <option value="Used - Good">Used - Good</option>
                                    <option value="Like New">Like New</option>
                                    <option value="Surplus Unopened">Surplus Unopened</option>
                                    <option value="Used - Fair">Used - Fair</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="edit_reusability_scrap_notice" style="display:none;margin-top:12px;padding:12px 14px;border-radius:8px;background:#fef7f6;border:1.5px solid #fecaca;color:#991b1b;font-size:12px;line-height:1.45;">
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                            <span style="font-size:15px;">🔒</span>
                            <strong style="color:#7f1d1d;">Internal Scrap Only (Unlisted from Marketplace)</strong>
                        </div>
                        Marking this item as Non-Reusable scrap will remove it from the public marketplace and business directory. It stays saved in your private inventory for waste monitoring and AI Scrap Advisor suggestions.
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="edit_unit">Unit of Measure *</label>
                        <input id="edit_unit" name="unit" type="text" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_threshold">Low Stock Threshold</label>
                        <input id="edit_threshold" name="minimum_threshold" type="number" step="0.01" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_notes">Notes</label>
                    <textarea id="edit_notes" name="notes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-save">Update Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Stock Adjust Modal -->
<div class="modal-backdrop" id="adjust-modal" style="display:none;" onclick="if(event.target === this) closeAdjustModal()">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 id="adjust-modal-title">Adjust Stock Level</h3>
            <button type="button" class="modal-close-btn" onclick="closeAdjustModal()">✕</button>
        </div>
        <form id="adjust-form" method="POST" action="">
            <?= csrf_field() ?>
            <div class="modal-body">
                <p style="margin:0 0 12px;font-size:12px;color:#526158;">
                    Material: <strong id="adjust-item-name" style="color:#173e2c;"></strong><br>
                    Current Stock: <span id="adjust-current-stock" style="font-weight:600;color:#176b40;"></span>
                </p>
                <div class="form-group">
                    <label for="adjust_type">Movement Type *</label>
                    <select id="adjust_type" name="type" required>
                        <option value="stock_in">+ Stock In (Receive / replenish)</option>
                        <option value="stock_out">- Stock Out (Dispatch / consume / sell)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="adjust_quantity">Quantity *</label>
                    <input id="adjust_quantity" name="quantity" type="number" step="0.01" min="0.01" required placeholder="e.g. 10">
                </div>
                <div class="form-group">
                    <label for="adjust_notes">Reason / Reference (optional)</label>
                    <input id="adjust_notes" name="notes" type="text" placeholder="e.g. Delivery order #203, Workshop batch B">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeAdjustModal()">Cancel</button>
                <button type="submit" class="btn-save">Confirm Movement</button>
            </div>
        </form>
    </div>
</div>

<!-- List on Marketplace Modal -->
<div class="modal-backdrop" id="list-market-modal" style="display:none;" onclick="if(event.target === this) closeListMarketModal()">
    <div class="modal-dialog modal-dialog-large">
        <div class="modal-header">
            <h3>List Material on Marketplace</h3>
            <button type="button" class="modal-close-btn" onclick="closeListMarketModal()">✕</button>
        </div>
        <form method="POST" action="<?= route('business.marketplace.store') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" id="lm_inventory_item_id" name="inventory_item_id" value="">
            <div class="modal-body">
                <p style="margin:0 0 14px;padding:10px 12px;border-radius:6px;background:#f0f6ee;border:1px solid #cce5c8;font-size:12px;color:#173e2c;">
                    From Inventory: <strong id="lm_source_name"></strong> &middot; Available Stock: <span id="lm_source_stock" style="color:#176b40;font-weight:700;"></span>
                </p>

                <!-- Photos Upload for Marketplace Listing with 5 slots & + Add Photos button -->
                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <label style="margin:0;font-weight:700;color:#18422d;">
                            Material Photos <small style="font-weight:500;color:#556c5e;">(1 to 5 images - Inherits inventory photos by default)</small>
                        </label>
                        <span id="lm_img_counter" style="font-size:12px;font-weight:700;color:#18422d;">Using inventory photos</span>
                    </div>

                    <div class="inv-photo-uploader-box" id="lm_photo_uploader_box">
                        <input type="file" id="lm_images_form_input" name="images[]" multiple style="display:none;">
                        <input type="file" id="lm_images_picker" accept="image/png,image/jpeg,image/webp,image/jpg" multiple style="display:none;" onchange="handleNewLmImages(this)">

                        <div class="inv-photo-top-bar">
                            <button type="button" class="btn-choose-photos" id="btn_lm_photos" onclick="document.getElementById('lm_images_picker').click()">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                <span>+ Add / Replace Photos</span>
                            </button>
                            <span class="inv-photo-hint">Add up to 5 photos. Slot 1 is the cover photo.</span>
                        </div>

                        <div class="inv-slots-grid" id="lm_slots_grid">
                            <!-- Slots rendered dynamically -->
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="lm_title">Listing Title *</label>
                    <input id="lm_title" name="title" type="text" required placeholder="e.g. Reclaimed Wooden Pallets 120x100cm">
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="lm_category">Category *</label>
                        <select id="lm_category" name="category" required>
                            <option value="Packaging & paper">Packaging & paper</option>
                            <option value="Furniture & wood products">Furniture & wood products</option>
                            <option value="Textiles & apparel">Textiles & apparel</option>
                            <option value="Electronics & electrical">Electronics & electrical</option>
                            <option value="Metal & fabrication">Metal & fabrication</option>
                            <option value="Plastics & rubber">Plastics & rubber</option>
                            <option value="Chemicals & lubricants">Chemicals & lubricants</option>
                            <option value="Food & beverage organic">Food & beverage organic</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="lm_condition">Material Condition *</label>
                        <select id="lm_condition" name="condition" required>
                            <option value="Used - Good" selected>Used - Good</option>
                            <option value="Brand New">Brand New</option>
                            <option value="Like New">Like New</option>
                            <option value="Surplus Material">Surplus Material</option>
                            <option value="Scrap / Recyclable">Scrap / Recyclable</option>
                        </select>
                    </div>
                </div>

                <!-- Listing Option (Offer Type) with Segmented Cards -->
                <div class="form-group">
                    <label>Listing Offer Option *</label>
                    <div class="offer-toggle-grid">
                        <label class="offer-toggle-card">
                            <input type="radio" name="listing_type" id="lm_type_buy" value="buy" checked onchange="handleListingTypeChange('buy', 'lm')">
                            <span class="offer-icon">🛒</span>
                            <span class="offer-label">Buy (Sell)</span>
                            <span class="offer-sub">Set RM price</span>
                        </label>
                        <label class="offer-toggle-card">
                            <input type="radio" name="listing_type" id="lm_type_borrow" value="borrow" onchange="handleListingTypeChange('borrow', 'lm')">
                            <span class="offer-icon">🤝</span>
                            <span class="offer-label">Borrow (Loan)</span>
                            <span class="offer-sub">Deposit / rental</span>
                        </label>
                        <label class="offer-toggle-card">
                            <input type="radio" name="listing_type" id="lm_type_free" value="free" onchange="handleListingTypeChange('free', 'lm')">
                            <span class="offer-icon">🎁</span>
                            <span class="offer-label">Take for Free</span>
                            <span class="offer-sub">RM 0.00 Giveaway</span>
                        </label>
                    </div>
                </div>

                <!-- Reusability Classification: Strictly Reusable for Marketplace -->
                <input type="hidden" name="reusability" value="reusable">
                <div class="form-group">
                    <label>Reusability Classification</label>
                    <div style="padding:10px 14px;border-radius:8px;background:#f0f7ef;border:1.5px solid #b8dab4;display:flex;align-items:center;gap:10px;">
                        <span style="font-size:18px;color:#15803d;">↻</span>
                        <div>
                            <strong style="display:block;font-size:13px;color:#15803d;">Reusable Circular Material</strong>
                            <small style="color:#526b5a;font-size:11px;">Only reusable materials can be listed on the public marketplace for other businesses.</small>
                        </div>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="lm_quantity">Quantity to Offer *</label>
                        <input id="lm_quantity" name="quantity" type="number" step="0.01" min="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="lm_unit">Unit of Measure *</label>
                        <input id="lm_unit" name="unit" type="text" required>
                    </div>
                </div>

                <div class="form-group" id="lm_price_group">
                    <label for="lm_price" id="lm_price_label">Unit Price (RM) *</label>
                    <input id="lm_price" name="price" type="number" step="0.01" min="0" placeholder="e.g. 15.00">
                    <small id="lm_price_hint" style="color:#6f7b72;font-size:11px;">Specify price in RM per unit.</small>
                </div>

                <div class="form-group">
                    <label for="lm_description">Description &amp; Terms *</label>
                    <textarea id="lm_description" name="description" rows="3" required placeholder="Describe material condition, specifications, and pickup/logistics instructions..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeListMarketModal()">Cancel</button>
                <button type="submit" class="btn-save">Publish to Marketplace</button>
            </div>
        </form>
    </div>
</div>

<!-- AI Circular Scrap Advisor Modal (Smart AI Recommendations for Not-Reusable Materials) -->
<div class="modal-backdrop" id="ai-scrap-advisor-modal" style="display:none;" onclick="if(event.target === this) closeAiAdvisorModal()">
    <div class="modal-dialog modal-dialog-large" style="max-width:640px;">
        <div class="modal-header" style="background:#f0f7ff;border-bottom:1px solid #bfdbfe;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;border-radius:8px;background:#2563eb;color:#fff;display:grid;place-items:center;font-size:18px;">💡</div>
                <div>
                    <h3 style="color:#1e3a8a;margin:0;">EcoCycle AI Scrap Advisor</h3>
                    <small style="color:#3b82f6;font-size:11.5px;font-weight:600;">Circular Valorization & Licensed Facility Matcher</small>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeAiAdvisorModal()">✕</button>
        </div>
        <form id="ai-advisor-form" method="POST" action="">
            <?= csrf_field() ?>
            <div class="modal-body" style="gap:14px;">
                <!-- Target Material Header -->
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <strong id="ai_scrap_item_name" style="font-size:14px;color:#0f172a;display:block;">Scrap Material</strong>
                        <span id="ai_scrap_meta" style="font-size:12px;color:#64748b;">Category · 0 kg in inventory</span>
                    </div>
                    <span style="font-size:11px;font-weight:700;background:#fef2f2;color:#b91c1c;padding:3px 8px;border-radius:4px;border:1px solid #fecaca;">⊘ Not Reusable Scrap</span>
                </div>

                <!-- AI Analysis Card -->
                <div style="background:#eff6ff;border:1.5px solid #93c5fd;border-radius:10px;padding:14px 16px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                        <span style="font-size:16px;">⚡</span>
                        <h4 style="margin:0;font-size:13px;color:#1e40af;">AI Circular Recommendation Engine</h4>
                    </div>
                    <p id="ai_treatment_strategy" style="margin:0 0 10px;font-size:12.5px;color:#1e3a8a;line-height:1.5;">
                        Analyzing material composition and industrial recovery paths...
                    </p>
                    <div style="background:#ffffff;border-radius:6px;padding:10px 12px;border:1px solid #bfdbfe;font-size:12px;">
                        <strong style="color:#1d4ed8;display:block;margin-bottom:4px;">📜 Recommended Action & Circular Process:</strong>
                        <span id="ai_compliance_note" style="color:#334155;">Compliant with Malaysian Environmental Quality Act 1974. Scheduled for licensed secondary recovery.</span>
                    </div>
                </div>

                <!-- Recommended Destination Recyclers -->
                <div>
                    <label style="font-weight:700;color:#1e293b;margin-bottom:6px;display:block;font-size:12.5px;">
                        📍 Recommended Licensed Recovery Destinations in Malaysia:
                    </label>
                    <div id="ai_destinations_container" style="display:flex;flex-direction:column;gap:8px;">
                        <!-- Rendered by JS -->
                    </div>
                </div>

                <!-- Action: Log to Wastage Monitoring -->
                <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:12px 14px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                        <span style="font-size:16px;">📊</span>
                        <strong style="font-size:12.5px;color:#15803d;">Track in Wastage Monitoring Module</strong>
                    </div>
                    <p style="margin:0 0 10px;font-size:11.5px;color:#33533e;line-height:1.4;">
                        Log this scrap directly into your official <strong>Wastage Monitoring</strong> tracker so you can monitor quantities, scheduled collection, and ESG diversion analytics.
                    </p>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label style="font-size:11.5px;color:#166534;">Quantity to Log *</label>
                            <input type="number" step="0.01" min="0.01" id="ai_log_qty" name="quantity" required style="font-size:12px;padding:6px 10px;">
                        </div>
                        <div class="form-group">
                            <label style="font-size:11.5px;color:#166534;">Selected Destination Facility</label>
                            <input type="text" id="ai_facility_name" name="facility_name" placeholder="e.g. Alam Flora Recovery Hub" style="font-size:12px;padding:6px 10px;">
                        </div>
                    </div>
                    <input type="hidden" id="ai_facility_location" name="facility_location" value="">
                    <input type="hidden" id="ai_recommendation_text" name="ai_recommendation" value="">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeAiAdvisorModal()">Close</button>
                <button type="submit" class="btn-save" style="background:#16a34a;border-color:#15803d;">
                    📊 Log to Wastage Monitoring &rarr;
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// DataTransfer stores for Add and Edit Modals
var addPhotosTransfer = new DataTransfer();
var editPhotosTransfer = new DataTransfer();

function toggleReusabilityNotice(prefix, val) {
    var notice = document.getElementById(prefix + '_reusability_scrap_notice');
    var tradePanel = document.getElementById(prefix + '_marketplace_trade_panel');
    if (val === 'not_reusable') {
        if (notice) notice.style.display = 'block';
        if (tradePanel) tradePanel.style.display = 'none';
    } else {
        if (notice) notice.style.display = 'none';
        if (tradePanel) tradePanel.style.display = 'block';
    }
}

function toggleAddPriceField(val) {
    var pGrp = document.getElementById('add_price_group');
    if (!pGrp) return;
    if (val === 'free') {
        pGrp.style.opacity = '0.35';
        pGrp.style.pointerEvents = 'none';
        var inp = document.getElementById('add_price');
        if (inp) inp.value = '0.00';
    } else {
        pGrp.style.opacity = '1';
        pGrp.style.pointerEvents = 'auto';
    }
}

function toggleEditPriceField(val) {
    var pGrp = document.getElementById('edit_price_group');
    if (!pGrp) return;
    if (val === 'free') {
        pGrp.style.opacity = '0.35';
        pGrp.style.pointerEvents = 'none';
        var inp = document.getElementById('edit_price');
        if (inp) inp.value = '0.00';
    } else {
        pGrp.style.opacity = '1';
        pGrp.style.pointerEvents = 'auto';
    }
}

function openAddModal() {
    addPhotosTransfer = new DataTransfer();
    renderAddSlots();
    var modal = document.getElementById('add-modal');
    modal.style.display = 'flex';
    var body = modal.querySelector('.modal-body');
    if (body) body.scrollTop = 0;
    document.body.style.overflow = 'hidden';
}

function closeAddModal() {
    document.getElementById('add-modal').style.display = 'none';
    document.body.style.overflow = '';
}

function renderAddSlots() {
    var grid = document.getElementById('add_slots_grid');
    var counter = document.getElementById('add_img_counter');
    var btn = document.getElementById('btn_add_photos');
    var err = document.getElementById('add_img_error');
    if (err) err.style.display = 'none';

    var files = addPhotosTransfer.files;
    var count = files.length;
    if (counter) {
        counter.textContent = count + ' of 5 selected' + (count === 0 ? ' (At least 1 required)' : '');
        counter.style.color = count >= 1 ? '#18422d' : '#b91c1c';
    }

    var formInput = document.getElementById('add_images_form_input');
    if (formInput) {
        formInput.files = addPhotosTransfer.files;
    }

    if (btn) {
        btn.disabled = count >= 5;
        btn.style.opacity = count >= 5 ? '0.5' : '1';
        btn.style.cursor = count >= 5 ? 'not-allowed' : 'pointer';
    }

    if (!grid) return;
    grid.innerHTML = '';

    for (var i = 0; i < count; i++) {
        (function(index) {
            var file = files[index];
            var card = document.createElement('div');
            card.className = 'inv-slot-card';

            var reader = new FileReader();
            reader.onload = function(e) {
                card.innerHTML = '<img src="' + e.target.result + '" alt="Photo ' + (index + 1) + '">' +
                    (index === 0 ? '<span class="inv-slot-cover-badge">Cover Photo</span>' : '<span style="position:absolute;bottom:2px;left:4px;background:rgba(0,0,0,0.65);color:#fff;font-size:9px;padding:1px 5px;border-radius:3px;font-weight:700;">#' + (index + 1) + '</span>') +
                    '<button type="button" class="inv-slot-remove-btn" title="Remove photo" onclick="removeAddPhoto(' + index + ')">✕</button>';
            };
            reader.readAsDataURL(file);
            grid.appendChild(card);
        })(i);
    }

    if (count < 5) {
        var addSlot = document.createElement('div');
        addSlot.className = 'inv-slot-card empty-slot';
        addSlot.title = 'Click to add more photos';
        addSlot.onclick = function() {
            document.getElementById('add_images_picker').click();
        };
        addSlot.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span>+ Add</span>';
        grid.appendChild(addSlot);

        for (var j = count + 1; j < 5; j++) {
            var emptySlot = document.createElement('div');
            emptySlot.className = 'inv-slot-card empty-slot';
            emptySlot.style.opacity = '0.45';
            emptySlot.style.cursor = 'pointer';
            emptySlot.title = 'Empty slot ' + (j + 1) + ' (click to add)';
            emptySlot.onclick = function() {
                document.getElementById('add_images_picker').click();
            };
            emptySlot.innerHTML = '<span style="font-size:11px;color:#94a3b8;">Slot ' + (j + 1) + '</span>';
            grid.appendChild(emptySlot);
        }
    }
}

function handleNewAddImages(picker) {
    if (!picker.files || picker.files.length === 0) return;

    var newFiles = picker.files;
    for (var i = 0; i < newFiles.length; i++) {
        if (addPhotosTransfer.files.length >= 5) {
            alert('Maximum 5 photos allowed for 1 item. Additional photos were not added.');
            break;
        }
        var file = newFiles[i];
        if (!file.type.match('image.*')) {
            alert('File "' + file.name + '" is not a valid image.');
            continue;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('File "' + file.name + '" exceeds the 5MB size limit.');
            continue;
        }
        addPhotosTransfer.items.add(file);
    }

    picker.value = '';
    renderAddSlots();
}

function removeAddPhoto(indexToRemove) {
    var newTransfer = new DataTransfer();
    var files = addPhotosTransfer.files;
    for (var i = 0; i < files.length; i++) {
        if (i !== indexToRemove) {
            newTransfer.items.add(files[i]);
        }
    }
    addPhotosTransfer = newTransfer;
    renderAddSlots();
}

function validateInventoryAddForm(form) {
    if (addPhotosTransfer.files.length < 1) {
        var err = document.getElementById('add_img_error');
        if (err) err.style.display = 'block';
        var box = document.getElementById('add_photo_uploader_box');
        if (box) {
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
            box.style.borderColor = '#dc2626';
        }
        alert('Please add at least 1 image for this material (minimum 1, maximum 5 photos required).');
        return false;
    }
    return true;
}

// Edit Modal Functions
var editInitialImages = [];

function openEditModal(id, name, sku, category, unit, threshold, notes, reusability, description, existingImages, price, listingType, condition) {
    document.getElementById('edit-form').action = '/business/inventory/' + id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_sku').value = sku || '';
    document.getElementById('edit_category').value = category;
    document.getElementById('edit_unit').value = unit;
    document.getElementById('edit_threshold').value = (threshold !== null && threshold !== undefined && threshold !== 'null') ? threshold : '';
    document.getElementById('edit_notes').value = notes || '';
    if (document.getElementById('edit_description')) {
        document.getElementById('edit_description').value = description || '';
    }

    if (document.getElementById('edit_listing_type')) {
        document.getElementById('edit_listing_type').value = listingType || 'buy';
    }
    if (document.getElementById('edit_price')) {
        document.getElementById('edit_price').value = (price !== null && price !== undefined && parseFloat(price) > 0) ? price : '';
    }
    if (document.getElementById('edit_condition')) {
        document.getElementById('edit_condition').value = condition || 'Used - Good';
    }
    toggleEditPriceField(listingType || 'buy');

    if (reusability === 'not_reusable') {
        var el = document.getElementById('edit_reusability_not_reusable');
        if (el) el.checked = true;
        toggleReusabilityNotice('edit', 'not_reusable');
    } else {
        var el2 = document.getElementById('edit_reusability_reusable');
        if (el2) el2.checked = true;
        toggleReusabilityNotice('edit', 'reusable');
    }
    editPhotosTransfer = new DataTransfer();
    editInitialImages = existingImages || [];
    renderEditSlots();
    var modal = document.getElementById('edit-modal');
    modal.style.display = 'flex';
    var body = modal.querySelector('.modal-body');
    if (body) body.scrollTop = 0;
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
    document.body.style.overflow = '';
}

function renderEditSlots() {
    var grid = document.getElementById('edit_slots_grid');
    var counter = document.getElementById('edit_img_counter');
    var btn = document.getElementById('btn_edit_photos');
    var files = editPhotosTransfer.files;
    var count = files.length;

    if (!grid) return;
    grid.innerHTML = '';

    if (count > 0) {
        if (counter) {
            counter.textContent = count + ' of 5 new photos selected';
            counter.style.color = '#18422d';
        }
        for (var i = 0; i < count; i++) {
            (function(index) {
                var file = files[index];
                var card = document.createElement('div');
                card.className = 'inv-slot-card';

                var reader = new FileReader();
                reader.onload = function(e) {
                    card.innerHTML = '<img src="' + e.target.result + '" alt="Photo ' + (index + 1) + '">' +
                        (index === 0 ? '<span class="inv-slot-cover-badge">New Cover</span>' : '<span style="position:absolute;bottom:2px;left:4px;background:rgba(0,0,0,0.65);color:#fff;font-size:9px;padding:1px 5px;border-radius:3px;font-weight:700;">#' + (index + 1) + '</span>') +
                        '<button type="button" class="inv-slot-remove-btn" title="Remove photo" onclick="removeEditPhoto(' + index + ')">✕</button>';
                };
                reader.readAsDataURL(file);
                grid.appendChild(card);
            })(i);
        }

        if (count < 5) {
            var addSlot = document.createElement('div');
            addSlot.className = 'inv-slot-card empty-slot';
            addSlot.title = 'Click to add more photos';
            addSlot.onclick = function() {
                document.getElementById('edit_images_picker').click();
            };
            addSlot.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span>+ Add</span>';
            grid.appendChild(addSlot);

            for (var j = count + 1; j < 5; j++) {
                var emptySlot = document.createElement('div');
                emptySlot.className = 'inv-slot-card empty-slot';
                emptySlot.style.opacity = '0.45';
                emptySlot.style.cursor = 'pointer';
                emptySlot.onclick = function() {
                    document.getElementById('edit_images_picker').click();
                };
                emptySlot.innerHTML = '<span style="font-size:11px;color:#94a3b8;">Slot ' + (j + 1) + '</span>';
                grid.appendChild(emptySlot);
            }
        }
    } else {
        // Show current existing photos
        var initCount = editInitialImages.length;
        if (counter) {
            counter.textContent = initCount > 0 ? initCount + ' current photos (upload below to replace)' : 'No photos yet';
            counter.style.color = '#556c5e';
        }

        for (var k = 0; k < initCount; k++) {
            var exCard = document.createElement('div');
            exCard.className = 'inv-slot-card';
            exCard.innerHTML = '<img src="' + editInitialImages[k] + '" alt="Photo ' + (k + 1) + '">' +
                (k === 0 ? '<span class="inv-slot-cover-badge">Current Cover</span>' : '<span style="position:absolute;bottom:2px;left:4px;background:rgba(0,0,0,0.65);color:#fff;font-size:9px;padding:1px 5px;border-radius:3px;font-weight:700;">#' + (k + 1) + '</span>');
            grid.appendChild(exCard);
        }

        if (initCount < 5) {
            var addSlotInit = document.createElement('div');
            addSlotInit.className = 'inv-slot-card empty-slot';
            addSlotInit.title = 'Click to add photos';
            addSlotInit.onclick = function() {
                document.getElementById('edit_images_picker').click();
            };
            addSlotInit.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span>+ Add</span>';
            grid.appendChild(addSlotInit);

            for (var m = initCount + 1; m < 5; m++) {
                var emptySlotInit = document.createElement('div');
                emptySlotInit.className = 'inv-slot-card empty-slot';
                emptySlotInit.style.opacity = '0.45';
                emptySlotInit.style.cursor = 'pointer';
                emptySlotInit.onclick = function() {
                    document.getElementById('edit_images_picker').click();
                };
                emptySlotInit.innerHTML = '<span style="font-size:11px;color:#94a3b8;">Slot ' + (m + 1) + '</span>';
                grid.appendChild(emptySlotInit);
            }
        }
    }

    var formInput = document.getElementById('edit_images_form_input');
    if (formInput) {
        formInput.files = editPhotosTransfer.files;
    }

    if (btn) {
        btn.disabled = count >= 5;
        btn.style.opacity = count >= 5 ? '0.5' : '1';
    }
}

function handleNewEditImages(picker) {
    if (!picker.files || picker.files.length === 0) return;

    var newFiles = picker.files;
    for (var i = 0; i < newFiles.length; i++) {
        if (editPhotosTransfer.files.length >= 5) {
            alert('Maximum 5 photos allowed.');
            break;
        }
        var file = newFiles[i];
        if (!file.type.match('image.*')) {
            alert('File "' + file.name + '" is not an image.');
            continue;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('File "' + file.name + '" exceeds the 5MB size limit.');
            continue;
        }
        editPhotosTransfer.items.add(file);
    }

    picker.value = '';
    renderEditSlots();
}

function removeEditPhoto(indexToRemove) {
    var newTransfer = new DataTransfer();
    var files = editPhotosTransfer.files;
    for (var i = 0; i < files.length; i++) {
        if (i !== indexToRemove) {
            newTransfer.items.add(files[i]);
        }
    }
    editPhotosTransfer = newTransfer;
    renderEditSlots();
}

function openAdjustModal(id, name, unit, currentQty, defaultType) {
    document.getElementById('adjust-form').action = '/business/inventory/' + id + '/adjust';
    document.getElementById('adjust-item-name').textContent = name;
    document.getElementById('adjust-current-stock').textContent = currentQty + ' ' + unit;
    document.getElementById('adjust_type').value = defaultType || 'stock_in';
    document.getElementById('adjust-modal-title').textContent = defaultType === 'stock_out' ? 'Stock Out (-)' : 'Stock In (+)';
    var modal = document.getElementById('adjust-modal');
    modal.style.display = 'flex';
    var body = modal.querySelector('.modal-body');
    if (body) body.scrollTop = 0;
    document.body.style.overflow = 'hidden';
}

function closeAdjustModal() {
    document.getElementById('adjust-modal').style.display = 'none';
    document.body.style.overflow = '';
}

var lmPhotosTransfer = new DataTransfer();
var lmInitialImages = [];

function openListMarketModal(id, name, category, unit, quantity, reusability, description, existingImages) {
    if (reusability === 'not_reusable') {
        alert('This material is classified as Not Reusable (Scrap). Per platform policy, only reusable circular materials can be listed on the public marketplace. This item remains saved in your owner account.');
        return;
    }
    document.getElementById('lm_inventory_item_id').value = id;
    document.getElementById('lm_source_name').textContent = name;
    document.getElementById('lm_source_stock').textContent = quantity + ' ' + unit;
    document.getElementById('lm_title').value = name;
    document.getElementById('lm_category').value = category;
    document.getElementById('lm_unit').value = unit;
    document.getElementById('lm_quantity').value = quantity;
    if (document.getElementById('lm_description')) {
        document.getElementById('lm_description').value = description || '';
    }
    var buyRadio = document.getElementById('lm_type_buy');
    if (buyRadio) buyRadio.checked = true;
    handleListingTypeChange('buy', 'lm');

    lmPhotosTransfer = new DataTransfer();
    lmInitialImages = existingImages || [];
    renderLmSlots();

    var modal = document.getElementById('list-market-modal');
    modal.style.display = 'flex';
    var body = modal.querySelector('.modal-body');
    if (body) body.scrollTop = 0;
    document.body.style.overflow = 'hidden';
}

function closeListMarketModal() {
    document.getElementById('list-market-modal').style.display = 'none';
    document.body.style.overflow = '';
}

function renderLmSlots() {
    var grid = document.getElementById('lm_slots_grid');
    var counter = document.getElementById('lm_img_counter');
    var btn = document.getElementById('btn_lm_photos');
    var files = lmPhotosTransfer.files;
    var count = files.length;

    if (!grid) return;
    grid.innerHTML = '';

    if (count > 0) {
        if (counter) {
            counter.textContent = count + ' of 5 custom photos selected';
            counter.style.color = '#18422d';
        }

        for (var i = 0; i < count; i++) {
            (function(index) {
                var file = files[index];
                var card = document.createElement('div');
                card.className = 'inv-slot-card';

                var reader = new FileReader();
                reader.onload = function(e) {
                    card.innerHTML = '<img src="' + e.target.result + '" alt="Photo ' + (index + 1) + '">' +
                        (index === 0 ? '<span class="inv-slot-cover-badge">Listing Cover</span>' : '<span style="position:absolute;bottom:2px;left:4px;background:rgba(0,0,0,0.65);color:#fff;font-size:9px;padding:1px 5px;border-radius:3px;font-weight:700;">#' + (index + 1) + '</span>') +
                        '<button type="button" class="inv-slot-remove-btn" title="Remove photo" onclick="removeLmPhoto(' + index + ')">✕</button>';
                };
                reader.readAsDataURL(file);
                grid.appendChild(card);
            })(i);
        }

        if (count < 5) {
            var addSlot = document.createElement('div');
            addSlot.className = 'inv-slot-card empty-slot';
            addSlot.title = 'Click to add more photos';
            addSlot.onclick = function() {
                document.getElementById('lm_images_picker').click();
            };
            addSlot.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span>+ Add</span>';
            grid.appendChild(addSlot);

            for (var j = count + 1; j < 5; j++) {
                var emptySlot = document.createElement('div');
                emptySlot.className = 'inv-slot-card empty-slot';
                emptySlot.style.opacity = '0.45';
                emptySlot.style.cursor = 'pointer';
                emptySlot.onclick = function() {
                    document.getElementById('lm_images_picker').click();
                };
                emptySlot.innerHTML = '<span style="font-size:11px;color:#94a3b8;">Slot ' + (j + 1) + '</span>';
                grid.appendChild(emptySlot);
            }
        }
    } else {
        // Show current inventory images
        var initCount = lmInitialImages.length;
        if (counter) {
            counter.textContent = initCount > 0 ? 'Using ' + initCount + ' inventory photo(s)' : 'No photos in inventory item';
            counter.style.color = '#556c5e';
        }

        for (var k = 0; k < initCount; k++) {
            var exCard = document.createElement('div');
            exCard.className = 'inv-slot-card';
            exCard.innerHTML = '<img src="' + lmInitialImages[k] + '" alt="Photo ' + (k + 1) + '">' +
                (k === 0 ? '<span class="inv-slot-cover-badge">Inherited Cover</span>' : '<span style="position:absolute;bottom:2px;left:4px;background:rgba(0,0,0,0.65);color:#fff;font-size:9px;padding:1px 5px;border-radius:3px;font-weight:700;">#' + (k + 1) + '</span>');
            grid.appendChild(exCard);
        }

        if (initCount < 5) {
            var addSlotInit = document.createElement('div');
            addSlotInit.className = 'inv-slot-card empty-slot';
            addSlotInit.title = 'Click to add custom photos';
            addSlotInit.onclick = function() {
                document.getElementById('lm_images_picker').click();
            };
            addSlotInit.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span>+ Add</span>';
            grid.appendChild(addSlotInit);

            for (var m = initCount + 1; m < 5; m++) {
                var emptySlotInit = document.createElement('div');
                emptySlotInit.className = 'inv-slot-card empty-slot';
                emptySlotInit.style.opacity = '0.45';
                emptySlotInit.style.cursor = 'pointer';
                emptySlotInit.onclick = function() {
                    document.getElementById('lm_images_picker').click();
                };
                emptySlotInit.innerHTML = '<span style="font-size:11px;color:#94a3b8;">Slot ' + (m + 1) + '</span>';
                grid.appendChild(emptySlotInit);
            }
        }
    }

    var formInput = document.getElementById('lm_images_form_input');
    if (formInput) {
        formInput.files = lmPhotosTransfer.files;
    }

    if (btn) {
        btn.disabled = count >= 5;
        btn.style.opacity = count >= 5 ? '0.5' : '1';
    }
}

function handleNewLmImages(picker) {
    if (!picker.files || picker.files.length === 0) return;

    var newFiles = picker.files;
    for (var i = 0; i < newFiles.length; i++) {
        if (lmPhotosTransfer.files.length >= 5) {
            alert('Maximum 5 photos allowed. Additional photos were not added.');
            break;
        }
        var file = newFiles[i];
        if (!file.type.match('image.*')) {
            alert('File "' + file.name + '" is not an image.');
            continue;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('File "' + file.name + '" exceeds the 5MB size limit.');
            continue;
        }
        lmPhotosTransfer.items.add(file);
    }

    picker.value = '';
    renderLmSlots();
}

function removeLmPhoto(indexToRemove) {
    var newTransfer = new DataTransfer();
    var files = lmPhotosTransfer.files;
    for (var i = 0; i < files.length; i++) {
        if (i !== indexToRemove) {
            newTransfer.items.add(files[i]);
        }
    }
    lmPhotosTransfer = newTransfer;
    renderLmSlots();
}

function openAiAdvisorModal(id, name, category, quantity, unit, notes) {
    document.getElementById('ai-advisor-form').action = '/business/waste-monitoring/from-inventory/' + id;
    document.getElementById('ai_scrap_item_name').textContent = name;
    document.getElementById('ai_scrap_meta').textContent = (category || 'Scrap') + ' · ' + quantity + ' ' + unit + ' in inventory';
    document.getElementById('ai_log_qty').value = quantity;

    var cat = (category || '').toLowerCase();
    var n = (name || '').toLowerCase();
    
    var strategy = '';
    var compliance = '';
    var destinations = [];

    if (cat.includes('metal') || n.includes('steel') || n.includes('iron') || n.includes('alum') || n.includes('copper')) {
        strategy = 'Material identified as industrial metallic scrap. Recommended circular path: Magnetic/eddy-current separation followed by secondary smelting and ingot billet casting. 100% recyclable with minimal carbon loss.';
        compliance = 'Scheduled Waste / Scrap Metal Control: Comply with Malaysian Environmental Quality (Clean Air) Regulations and licensed scrap recovery guidelines.';
        destinations = [
            { name: 'Shan Poornam Metals Sdn Bhd', loc: 'Bukit Minyak, Penang', type: 'Precious & Industrial Metal Smelting Hub', badge: 'DOE Licensed' },
            { name: 'Southern Steel Circular Recovery', loc: 'Klang, Selangor', type: 'Ferrous Scrap Smelting & Rebar Casting', badge: 'Certified Recycler' },
            { name: 'Daikkin Metal Recovery Plant', loc: 'Pasir Gudang, Johor', type: 'Non-ferrous & Aluminum Extrusion', badge: 'Green Vendor' }
        ];
    } else if (cat.includes('plastic') || cat.includes('rubber') || n.includes('poly') || n.includes('pet') || n.includes('pvc') || n.includes('hdpe')) {
        strategy = 'Material identified as synthetic polymer / scrap plastics. Recommended circular path: Mechanical shredding, optical flake sorting, and hot melt extrusion into post-industrial recycled resin pellets (rPET / rHDPE).';
        compliance = 'National Plastics Pact & SIRIM Eco-Label Standard 023. Divert from incineration to certified mechanical pelletizing.';
        destinations = [
            { name: 'Heng Hiap Industries Circular Polymer', loc: 'Johor Bahru, Johor', type: 'Ocean & Industrial Plastic Pelletizer', badge: 'FDA/SIRIM Eco-Label' },
            { name: 'Daikkin Recycling Sdn Bhd', loc: 'Shah Alam, Selangor', type: 'Rigid & Film Plastic Reprocessing', badge: 'DOE Licensed' },
            { name: 'Thong Guan Green Recovery', loc: 'Sungai Petani, Kedah', type: 'Stretch Film & Polyolefin Reclaiming', badge: 'Certified Recycler' }
        ];
    } else if (cat.includes('wood') || cat.includes('furniture') || n.includes('timber') || n.includes('sawdust') || n.includes('pallet')) {
        strategy = 'Material identified as cellulose/timber cutoffs. Recommended circular path: High-pressure chipper granulation for engineered composite particleboards, biomass heating fuel pellets, or biochar agricultural soil enhancer.';
        compliance = 'Malaysian Timber Industry Board (MTIB) Zero-Burning Directive. 100% biomass valorization compliant.';
        destinations = [
            { name: 'Merces Bio-Pelletizing Facility', loc: 'Rawang, Selangor', type: 'Industrial Biomass Wood Pellets', badge: 'Carbon Neutral' },
            { name: 'HeveaBoard Circular Wood Recovery', loc: 'Gemas, Negeri Sembilan', type: 'Particleboard & Bio-composite Core', badge: 'PEFC Certified' },
            { name: 'Eco Wood Resource Hub', loc: 'Kulai, Johor', type: 'Pallet Remanufacturing & Mulching', badge: 'Green Vendor' }
        ];
    } else if (cat.includes('electronic') || cat.includes('electrical') || n.includes('pcb') || n.includes('cable') || n.includes('wire')) {
        strategy = 'Material identified as electrical / electronic scrap (E-Waste). Recommended circular path: De-soldering, hydro-metallurgical extraction of copper, silver, and gold, plus automated plastics casing segregation.';
        compliance = 'Mandatory DOE Code SW110 (Scheduled Waste) Regulations. Must only be handled by licensed e-waste recovery centers.';
        destinations = [
            { name: 'Cenviro Scheduled Waste Centre', loc: 'Bukit Pelandok, N. Sembilan', type: 'Comprehensive SW110 E-Waste Refining', badge: 'DOE Tier-1 Facility' },
            { name: 'Shan Poornam E-Waste Refining', loc: 'Prai, Penang', type: 'Precious Metals & PCB Pyrolysis', badge: 'Full DOE License' },
            { name: 'Greenish E-Waste Solutions', loc: 'Shah Alam, Selangor', type: 'Component Recovery & Solder Extract', badge: 'Licensed Collector' }
        ];
    } else if (cat.includes('food') || cat.includes('organic') || n.includes('bio') || n.includes('sludge')) {
        strategy = 'Material identified as organic / biodegradable byproduct. Recommended circular path: Automated continuous thermophilic composting, Black Soldier Fly (BSFL) protein feed bio-conversion, or anaerobic biogas digestion.';
        compliance = 'National Solid Waste Management Policy (JPSPN) Circular Bio-Economy Framework.';
        destinations = [
            { name: 'EntoGenex Insect Bio-Refinery', loc: 'Sepang, Selangor', type: 'BSFL Larvae Protein & Frass Biofertilizer', badge: 'Certified Circular' },
            { name: 'Alam Flora Environmental Solutions Organics', loc: 'Putrajaya', type: 'Commercial In-Vessel Composting', badge: 'Government Partner' },
            { name: 'Biogas Green Grid Recovery', loc: 'Banting, Selangor', type: 'Anaerobic Digester & Methane Capture', badge: 'Clean Energy' }
        ];
    } else {
        strategy = 'Material identified for general industrial circular recovery. Recommended path: Physical classification, separation of recyclable components, and transfer to a specialized Materials Recovery Facility (MRF).';
        compliance = 'Solid Waste and Public Cleansing Management Act 2007 (Act 672). Scheduled diversion away from general landfills.';
        destinations = [
            { name: 'Alam Flora Environmental Solutions MRF', loc: 'Shah Alam, Selangor', type: 'Integrated Industrial Materials Recovery', badge: 'Licensed MRF' },
            { name: 'SWM Environment Circular Hub', loc: 'Senai, Johor', type: 'Multi-stream Resource Sorting & Baling', badge: 'State Licensed' },
            { name: 'E-Idaman Resource Recovery Complex', loc: 'Alor Setar, Kedah', type: 'Industrial Scrap Valorization Centre', badge: 'Certified Operator' }
        ];
    }

    document.getElementById('ai_treatment_strategy').textContent = strategy;
    document.getElementById('ai_compliance_note').textContent = compliance;
    document.getElementById('ai_recommendation_text').value = strategy + ' | ' + compliance;

    var container = document.getElementById('ai_destinations_container');
    container.innerHTML = '';

    destinations.forEach(function(dest, i) {
        var card = document.createElement('div');
        card.style.cssText = 'border:1.5px solid #cbd5e1;background:#ffffff;border-radius:8px;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;cursor:pointer;transition:border-color 0.15s;';
        card.innerHTML = '<div>' +
            '<div style="display:flex;align-items:center;gap:6px;">' +
                '<strong style="font-size:12.5px;color:#0f172a;">' + dest.name + '</strong>' +
                '<span style="background:#e0f2fe;color:#0369a1;font-size:10px;font-weight:700;padding:2px 6px;border-radius:4px;">' + dest.badge + '</span>' +
            '</div>' +
            '<span style="font-size:11.5px;color:#64748b;">📍 ' + dest.loc + ' · ' + dest.type + '</span>' +
        '</div>' +
        '<button type="button" style="background:#f1f5f9;border:1px solid #cbd5e1;color:#1e293b;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">Select</button>';
        
        card.onclick = function() {
            document.getElementById('ai_facility_name').value = dest.name;
            document.getElementById('ai_facility_location').value = dest.loc;
            container.querySelectorAll('div').forEach(function(el) {
                el.style.borderColor = '#cbd5e1';
                el.style.background = '#ffffff';
            });
            card.style.borderColor = '#2563eb';
            card.style.background = '#eff6ff';
        };

        if (i === 0) {
            card.click();
        }

        container.appendChild(card);
    });

    var modal = document.getElementById('ai-scrap-advisor-modal');
    modal.style.display = 'flex';
    var body = modal.querySelector('.modal-body');
    if (body) body.scrollTop = 0;
    document.body.style.overflow = 'hidden';
}

function closeAiAdvisorModal() {
    document.getElementById('ai-scrap-advisor-modal').style.display = 'none';
    document.body.style.overflow = '';
}

function switchInventoryView(mode) {
    var tableView = document.getElementById('inventory-table-container');
    var catalogView = document.getElementById('inventory-catalog-container');
    var btnTable = document.getElementById('viewBtnTable');
    var btnCatalog = document.getElementById('viewBtnCatalog');
    var filterViewInput = document.getElementById('filter_view_input');

    if (mode === 'catalog') {
        if (tableView) tableView.style.display = 'none';
        if (catalogView) catalogView.style.display = 'grid';
        if (btnTable) btnTable.classList.remove('active');
        if (btnCatalog) btnCatalog.classList.add('active');
        if (filterViewInput) filterViewInput.value = 'catalog';
        try { localStorage.setItem('ecocycle_inventory_view', 'catalog'); } catch (e) {}
    } else {
        if (tableView) tableView.style.display = 'block';
        if (catalogView) catalogView.style.display = 'none';
        if (btnTable) btnTable.classList.add('active');
        if (btnCatalog) btnCatalog.classList.remove('active');
        if (filterViewInput) filterViewInput.value = 'table';
        try { localStorage.setItem('ecocycle_inventory_view', 'table'); } catch (e) {}
    }
}

// Initial slot render & view initialization
document.addEventListener('DOMContentLoaded', function() {
    renderAddSlots();

    var urlParams = new URLSearchParams(window.location.search);
    var savedView = urlParams.get('view');
    if (!savedView) {
        try { savedView = localStorage.getItem('ecocycle_inventory_view'); } catch (e) {}
    }
    if (savedView === 'catalog') {
        switchInventoryView('catalog');
    }
});
</script>
</body>
</html>
