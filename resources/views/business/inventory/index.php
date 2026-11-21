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
            height: 38px;
            padding: 0 12px;
            border: 1px solid var(--input-border);
            border-radius: 0;
            font: 400 13px var(--font-main);
            background: #ffffff;
            min-width: 220px;
        }
        .filter-select {
            height: 38px;
            padding: 0 12px;
            border: 1px solid var(--input-border);
            border-radius: 0;
            font: 400 13px var(--font-main);
            background: #ffffff;
        }
        .btn-filter {
            height: 38px;
            padding: 0 14px;
            border: 1px solid var(--brand-green);
            border-radius: 0;
            background: #ffffff;
            color: var(--brand-green);
            font: 600 12.5px var(--font-main);
            cursor: pointer;
        }
        .btn-filter:hover {
            background: #eef5eb;
        }
        .toolbar-right-controls {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-add-primary {
            height: 38px;
            padding: 0 18px;
            border: 1px solid var(--forest-bg);
            border-radius: 0;
            background: var(--forest-bg);
            color: #ffffff;
            font: 600 13px var(--font-main);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
            transition: background-color 0.15s ease;
        }
        .btn-add-primary:hover {
            background: #0d2b1b;
        }
        .action-group {
            display: inline-flex;
            gap: 6px;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: nowrap;
        }
        .action-stock-pair {
            display: inline-flex;
            align-items: center;
        }
        .btn-stock-in,
        .btn-stock-out,
        .btn-edit,
        .btn-delete,
        .btn-ai-advisor,
        .btn-market-live,
        .btn-publish-market {
            height: 28px;
            padding: 0 10px;
            border-radius: 0;
            font: 600 11.5px var(--font-main);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            text-decoration: none;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }
        .btn-stock-in {
            border: 1px solid #b5d6bc;
            background: #f1f8f2;
            color: #165b33;
        }
        .btn-stock-in:hover {
            background: #dff0e2;
            border-color: #7eb88c;
        }
        .btn-stock-out {
            border: 1px solid #e8c3c3;
            border-left: none;
            background: #fdf6f6;
            color: #991b1b;
        }
        .btn-stock-out:hover {
            background: #fae4e4;
            border-color: #dc9a9a;
        }
        .btn-edit {
            border: 1px solid #c5d4c3;
            background: #ffffff;
            color: #243f30;
        }
        .btn-edit:hover {
            background: #f2f6f1;
            border-color: #8fa98d;
        }
        .btn-delete {
            border: 1px solid #f0c2c2;
            background: #ffffff;
            color: #b91c1c;
        }
        .btn-delete:hover {
            background: #fef2f2;
            border-color: #dc2626;
        }
        .btn-ai-advisor {
            border: 1px solid #b8d0f5;
            background: #f3f7fe;
            color: #1e40af;
        }
        .btn-ai-advisor:hover {
            background: #e1ecfd;
            border-color: #60a5fa;
        }
        .btn-market-live {
            border: 1px solid #143825;
            background: #143825;
            color: #ffffff;
        }
        .btn-market-live:hover {
            background: #0d2b1b;
            border-color: #0d2b1b;
        }
        .btn-publish-market {
            border: 1px solid #18422d;
            background: #ffffff;
            color: #18422d;
        }
        .btn-publish-market:hover {
            background: #18422d;
            color: #ffffff;
        }
        .pagination-wrap {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }

        /* View Toggle Group */
        .view-switcher-wrap {
            height: 38px;
            display: inline-flex;
            align-items: center;
            background: #eef3ed;
            border-radius: 0;
            padding: 3px;
            gap: 2px;
            border: 1px solid var(--card-border);
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
            border-radius: 0;
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease;
        }
        .view-toggle-btn:hover {
            color: #123321;
            background: rgba(255, 255, 255, 0.7);
        }
        .view-toggle-btn.active {
            background: #ffffff;
            color: #143825;
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
            border: 1px solid var(--card-border);
            border-radius: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: border-color 0.15s ease;
            position: relative;
        }
        .catalog-card:hover {
            border-color: var(--brand-green);
        }
        .catalog-card-header {
            position: relative;
            height: 160px;
            background: #f1f6f0;
            overflow: hidden;
            cursor: pointer;
            border-bottom: 1px solid var(--card-border);
        }
        .catalog-card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
            border: 1px solid #c9e2c6;
            padding: 3px 8px;
            border-radius: 0;
            font-weight: 600;
            font-size: 11px;
        }
        .catalog-condition-pill {
            background: #f4f7f4;
            color: #4a5c4e;
            padding: 3px 8px;
            border-radius: 0;
            font-weight: 500;
            font-size: 11px;
            border: 1px solid var(--card-border);
        }
        .catalog-stock-row {
            background: var(--surface-subtle);
            border: 1px solid var(--card-border);
            border-radius: 0;
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
            color: #143825;
        }
        .catalog-stock-bar-wrap {
            height: 6px;
            background: #e2ede0;
            border-radius: 0;
            overflow: hidden;
        }
        .catalog-stock-bar {
            height: 100%;
            border-radius: 0;
        }
        .catalog-price-tag {
            display: flex;
            align-items: baseline;
            gap: 4px;
            font-size: 13px;
            font-weight: 700;
            color: #143825;
        }
        .catalog-card-actions {
            padding: 10px 14px;
            border-top: 1px solid var(--card-border);
            background: var(--surface-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            flex-wrap: wrap;
        }

        /* Independent 3-Slot Image Uploader */
        .inv-slots-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }
        .inv-slot-wrapper {
            display: flex;
            flex-direction: column;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            border-radius: 0;
        }
        .inv-slot-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 10px;
            background: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            font-size: 11px;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .inv-slot-preview-area {
            position: relative;
            height: 120px;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            cursor: pointer;
        }
        .inv-slot-preview-area.empty-slot {
            border: none;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
            gap: 6px;
        }
        .inv-slot-preview-area.empty-slot:hover {
            background: #eef5eb;
            color: #18422d;
        }
        .inv-slot-preview-area img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .inv-slot-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-top: 1px solid #cbd5e1;
            background: #ffffff;
        }
        .inv-slot-action-btn {
            padding: 6px 8px;
            border: none;
            background: #ffffff;
            color: #334155;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            border-radius: 0;
        }
        .inv-slot-action-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .inv-slot-action-btn.btn-slot-remove {
            border-left: 1px solid #cbd5e1;
            color: #b91c1c;
        }
        .inv-slot-action-btn.btn-slot-remove:hover {
            background: #fef2f2;
        }
        .inv-slot-action-btn.btn-slot-upload-full {
            grid-column: 1 / -1;
            color: #18422d;
            font-weight: 700;
        }
        .inv-slot-action-btn.btn-slot-upload-full:hover {
            background: #eef5eb;
        }
        .inv-slots-grid-3 .inv-slot-card {
            height: 110px;
        }
        @media (max-width: 640px) {
            .inv-slots-grid-3 {
                grid-template-columns: 1fr !important;
            }
        }
        .btn-owner-only {
            padding: 4px 8px;
            border: 1px solid #cbd5e1;
            border-radius: 0;
            background: #f8fafc;
            color: #64748b;
            font: 600 11px var(--font-main);
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: default;
        }

        /* Spacious & Professional Rectangular Add Inventory Modal */
        #add-modal .modal-dialog {
            width: min(920px, 94vw) !important;
            max-width: 920px !important;
            min-height: min(560px, 86vh) !important;
            max-height: min(92vh, 900px) !important;
            border-radius: 0 !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.18) !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
            background: #ffffff !important;
        }
        #add-modal .modal-dialog *,
        #add-modal .modal-header,
        #add-modal .modal-body,
        #add-modal .modal-footer,
        #add-modal input,
        #add-modal textarea,
        #add-modal select,
        #add-modal button,
        #add-modal .inv-photo-uploader-box,
        #add-modal .inv-slot-card,
        #add-modal .inv-slot-remove-btn,
        #add-modal .inv-slot-cover-badge,
        #add-modal .reusability-toggle-card {
            border-radius: 0 !important;
        }
        #add-modal input[type="radio"] {
            border-radius: 50% !important;
        }
        #add-modal .modal-header {
            padding: 20px 32px !important;
            border-bottom: 1px solid #cbd5e1 !important;
            background: #ffffff !important;
            flex-shrink: 0 !important;
        }
        #add-modal .modal-body {
            padding: 26px 32px !important;
            gap: 18px !important;
            overflow-y: auto !important;
            flex: 1 1 auto !important;
            background: #ffffff !important;
        }
        #add-modal .modal-footer {
            padding: 18px 32px !important;
            border-top: 1px solid #cbd5e1 !important;
            background: #f8fafc !important;
            flex-shrink: 0 !important;
            justify-content: space-between !important;
        }
        #add-modal .modal-close-btn {
            border-radius: 0 !important;
            border: 1px solid #cbd5e1 !important;
            background: #ffffff !important;
            color: #334155 !important;
            width: 32px !important;
            height: 32px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        #add-modal .modal-close-btn:hover {
            background: #f1f5f9 !important;
            border-color: #94a3b8 !important;
        }
        #add-modal .inv-photo-uploader-box {
            border: 1px solid #cbd5e1 !important;
            background: #f8fafc !important;
            padding: 16px !important;
        }
        #add-modal .inv-slot-card {
            border: 1px solid #cbd5e1 !important;
        }
        #add-modal .inv-slot-card.empty-slot {
            border: 1px dashed #94a3b8 !important;
        }
        #add-modal .add-rect-box {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 20px;
        }
        #add-modal .add-rect-card {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            padding: 14px 16px;
        }
        #add-modal .add-input-field {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            font-size: 14px;
            box-sizing: border-box;
        }
        #add-modal .add-input-field:focus {
            border-color: #18422d;
            outline: none;
        }
        #add-modal .add-btn-primary {
            background: #18422d;
            color: #ffffff;
            border: 1px solid #18422d;
            font-weight: 700;
            font-size: 13px;
            padding: 10px 22px;
            cursor: pointer;
        }
        #add-modal .add-btn-primary:hover {
            background: #103322;
            border-color: #103322;
        }
        #add-modal .add-btn-secondary {
            background: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
            font-weight: 600;
            font-size: 13px;
            padding: 10px 18px;
            cursor: pointer;
        }
        #add-modal .add-btn-secondary:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }
        #add-modal .add-btn-utility {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
        }
        #add-modal .add-btn-utility:hover {
            background: #e2e8f0;
        }
        @keyframes rectProgressSlide {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(300%); }
        }
        #add-modal .rect-loading-track {
            width: 240px;
            height: 4px;
            background: #e2e8f0;
            margin: 0 auto 20px;
            overflow: hidden;
            position: relative;
        }
        #add-modal .rect-loading-bar {
            width: 35%;
            height: 100%;
            background: #18422d;
            animation: rectProgressSlide 1.1s linear infinite;
        }
        #add-modal .add-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        #add-modal .add-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 768px) {
            #add-modal .modal-dialog {
                width: 96vw !important;
                max-width: 96vw !important;
                max-height: 94vh !important;
                min-height: auto !important;
                margin: 8px auto !important;
            }
            #add-modal .modal-header,
            #add-modal .modal-body,
            #add-modal .modal-footer {
                padding: 16px 20px !important;
            }
            #add-modal .add-grid-2,
            #add-modal .add-grid-3 {
                grid-template-columns: 1fr !important;
            }
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
                <div class="dashboard-alert success" role="status" style="margin-bottom:16px;padding:12px 16px;border-radius:0;background:#eaf3e8;border:1px solid #c7dbc5;border-left:3px solid #176b40;color:#176b40;font-size:13px;font-weight:600;">
                    <?= e(session('status')) ?>
                </div>
            <?php endif; ?>
            <?php if ($errors->any()): ?>
                <div class="dashboard-alert error" role="alert" style="margin-bottom:16px;padding:12px 16px;border-radius:0;background:#fff5f4;border:1px solid #fed7d2;border-left:3px solid #b91c1c;color:#b91c1c;font-size:13px;font-weight:600;">
                    <?= e($errors->first()) ?>
                </div>
            <?php endif; ?>

            <!-- Page Title Header -->
            <div class="page-header">
                <div>
                    <h1>Inventory management</h1>
                    <p>Track materials, upload photos, and manage reusable listings or recycling recommendations.</p>
                </div>
            </div>

            <section class="panel" style="margin-top:20px;">
                <div class="inventory-toolbar">
                    <form class="filter-form" method="GET" action="<?= route('business.inventory') ?>">
                        <input type="hidden" name="view" id="filter_view_input" value="<?= e($viewMode) ?>">
                        <input class="filter-input" type="search" name="search" value="<?= e($search) ?>" placeholder="Search by item name...">
                        
                        <select class="filter-select" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat) ?>" <?= $selectedCategory === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select class="filter-select" name="reusability">
                            <option value="">All Reusability</option>
                            <option value="reusable" <?= $selectedReusability === 'reusable' ? 'selected' : '' ?>>Reusable</option>
                            <option value="not_reusable" <?= $selectedReusability === 'not_reusable' ? 'selected' : '' ?>>Non-Reusable</option>
                        </select>

                        <select class="filter-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="in_stock" <?= $selectedStatus === 'in_stock' ? 'selected' : '' ?>>In stock</option>
                            <option value="out_of_stock" <?= $selectedStatus === 'out_of_stock' ? 'selected' : '' ?>>Out of stock</option>
                        </select>

                        <button type="submit" class="btn-filter">Filter</button>
                        <?php if ($search || $selectedCategory || $selectedStatus || $selectedReusability): ?>
                            <a href="<?= route('business.inventory', ['view' => $viewMode]) ?>" style="font-size:12px;color:#6f7b72;text-decoration:underline;">Reset</a>
                        <?php endif; ?>
                    </form>

                    <div class="toolbar-right-controls">
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

                        <button type="button" class="btn-add-primary" onclick="openAddModal()">
                            <span>Add New Item</span>
                        </button>
                    </div>
                </div>

                <?php if ($items->isNotEmpty()): ?>
                    <div id="inventory-table-container" style="display: <?= $viewMode === 'catalog' ? 'none' : 'block' ?>; overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width:28%;">Material / Item</th>
                                    <th style="width:16%;">Category</th>
                                    <th style="width:12%;">Reusability</th>
                                    <th style="width:11%;">Stock Level</th>
                                    <th style="width:9%;">Status</th>
                                    <th style="width:24%;text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td data-label="Item">
                                            <a href="<?= route('business.inventory.edit', $item) ?>" style="display:flex;align-items:center;gap:12px;text-decoration:none;" title="Open Edit Inventory Details">
                                                <?php if ($item->image_path): ?>
                                                    <img src="<?= e($item->image_path) ?>" alt="<?= e($item->name) ?>" style="width:44px;height:44px;border-radius:0;object-fit:cover;border:1px solid #d4e0d2;flex-shrink:0;" loading="lazy">
                                                <?php else: ?>
                                                    <div style="width:44px;height:44px;border-radius:0;background:#eef6ec;border:1px solid #cce5c8;display:grid;place-items:center;flex-shrink:0;color:#235839;font-weight:700;font-size:10px;">
                                                        ITEM
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <strong style="color:#123321;font-size:14px;"><?= e($item->name) ?></strong>
                                                </div>
                                            </a>
                                        </td>
                                        <td data-label="Category"><?= e($item->category) ?></td>
                                        <td data-label="Reusability">
                                            <?php if (($item->reusability ?? 'reusable') === 'reusable'): ?>
                                                <span class="badge-reusable" title="Reusable circular material - can be published to the Marketplace">Reusable</span>
                                            <?php else: ?>
                                                <span class="badge-not-reusable" title="Non-reusable material - stays in inventory for EcoCycle Helper">Non-Reusable</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Stock Level">
                                            <span style="font-size:14px;font-weight:700;color:#173e2c;"><?= (float) $item->quantity ?></span> <?= e($item->unit) ?>
                                        </td>
                                        <td data-label="Status">
                                            <?php if ($item->isPermanentlySoldOut()): ?>
                                                <span class="status status-out-of-stock" style="background:#fee2e2;color:#991b1b;border-color:#fca5a5;">Sold Out</span>
                                            <?php elseif ($item->status === 'out_of_stock' || (float) $item->quantity <= 0): ?>
                                                <span class="status status-out-of-stock">Out of stock</span>
                                            <?php else: ?>
                                                <span class="status status-in-stock">In stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Actions" style="text-align:right;">
                                            <div class="action-group">
                                                <?php if (($item->reusability ?? 'reusable') === 'reusable'): ?>
                                                    <?php if ($item->isPermanentlySoldOut()): ?>
                                                        <span class="btn-owner-only" title="Permanently Sold Out — create a new submission for new surplus stock">Sold Out</span>
                                                    <?php elseif ($item->isPublishedToMarketplace()): ?>
                                                        <a href="<?= route('business.marketplace', ['q' => $item->name]) ?>" class="btn-market-live" title="View live listing on Marketplace">
                                                            On Market
                                                        </a>
                                                    <?php else: ?>
                                                        <button type="button" class="btn-publish-market" title="Publish reusable item to Marketplace" onclick='openListMarketModal(<?= $item->id ?>, <?= json_encode($item->name) ?>, <?= json_encode($item->category) ?>, <?= json_encode($item->unit) ?>, <?= (float) $item->quantity ?>, <?= json_encode($item->reusability ?? "reusable") ?>, <?= json_encode($item->description ?? "") ?>, <?= json_encode($item->getAllImages()) ?>, <?= json_encode($item->condition ?? "Used") ?>, <?= json_encode($item->listing_type ?? "buy") ?>, <?= json_encode($item->price !== null ? (float) $item->price : null) ?>, <?= json_encode($item->pickup_address ?? "") ?>)'>
                                                            Publish
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <a href="<?= route('business.helper', ['item_id' => $item->id]) ?>" class="btn-ai-advisor" title="Open in EcoCycle Helper">
                                                        EcoCycle Helper
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?= route('business.inventory.edit', $item) ?>" class="btn-edit" title="Update item details">
                                                    Update Details
                                                </a>
                                                <form method="POST" action="<?= route('business.inventory.destroy', $item) ?>" style="display:inline;margin:0;" onsubmit="return confirm('Are you sure you want to delete this inventory item?');">
                                                    <?= csrf_field() ?>
                                                    <?= method_field('DELETE') ?>
                                                    <button type="submit" class="btn-delete" title="Delete item">Delete</button>
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
                                <a href="<?= route('business.inventory.edit', $item) ?>" class="catalog-card-header" style="display:block;text-decoration:none;" title="Open Edit Inventory Details">
                                    <?php if ($item->image_path): ?>
                                        <img src="<?= e($item->image_path) ?>" alt="<?= e($item->name) ?>" class="catalog-card-img" loading="lazy">
                                    <?php else: ?>
                                        <div style="width:100%;height:100%;display:grid;place-items:center;background:#edf5eb;color:#235839;font-size:13px;font-weight:700;letter-spacing:0.05em;">
                                            NO PHOTO
                                        </div>
                                    <?php endif; ?>
                                    <div class="catalog-card-badges-top">
                                        <?php if (($item->reusability ?? 'reusable') === 'reusable'): ?>
                                            <span class="badge-reusable" style="font-size:11px;padding:3px 8px;">Reusable</span>
                                        <?php else: ?>
                                            <span class="badge-not-reusable" style="font-size:11px;padding:3px 8px;">Non-Reusable</span>
                                        <?php endif; ?>

                                        <?php if ($item->isPermanentlySoldOut()): ?>
                                            <span class="status status-out-of-stock" style="font-size:11px;background:#fee2e2;color:#991b1b;border-color:#fca5a5;">Sold Out</span>
                                        <?php elseif ($item->status === 'out_of_stock' || (float)$item->quantity <= 0): ?>
                                            <span class="status status-out-of-stock" style="font-size:11px;">Out of stock</span>
                                        <?php else: ?>
                                            <span class="status status-in-stock" style="font-size:11px;">In stock</span>
                                        <?php endif; ?>
                                    </div>
                                </a>

                                <div class="catalog-card-body">
                                    <div class="catalog-card-title-row">
                                        <a href="<?= route('business.inventory.edit', $item) ?>" style="text-decoration:none;">
                                            <h4 class="catalog-card-title"><?= e($item->name) ?></h4>
                                        </a>
                                    </div>

                                    <div class="catalog-meta-row">
                                        <span class="catalog-cat-pill"><?= e($item->category) ?></span>
                                        <span class="catalog-condition-pill"><?= e($item->condition ?? 'Used') ?></span>
                                    </div>

                                    <div class="catalog-stock-row">
                                        <div class="catalog-stock-val">
                                            <div>
                                                <span class="catalog-stock-number"><?= (float) $item->quantity ?></span>
                                                <span style="font-size:12px;font-weight:600;color:#556c5e;"><?= e($item->unit) ?></span>
                                            </div>
                                            <div style="font-size:11px;color:#6f7b72;">
                                                <?= (float) $item->quantity > 0 ? 'Available' : 'Out of stock' ?>
                                            </div>
                                        </div>
                                        <?php
                                            $qty = (float) $item->quantity;
                                            $barPct = $qty > 0 ? 100 : 0;
                                            $barColor = $qty > 0 ? '#16a34a' : '#dc2626';
                                        ?>
                                        <div class="catalog-stock-bar-wrap" title="Stock availability">
                                            <div class="catalog-stock-bar" style="width: <?= $barPct ?>%; background: <?= $barColor ?>;"></div>
                                        </div>
                                    </div>

                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto;">
                                        <div class="catalog-price-tag">
                                            <?php if (($item->listing_type ?? 'buy') === 'free'): ?>
                                                <span style="color:#16a34a;font-weight:800;">FREE (RM 0.00)</span>
                                            <?php elseif (($item->listing_type ?? 'buy') === 'lend'): ?>
                                                <span style="color:#1d4ed8;font-weight:800;">LEND (RM 0.00)</span>
                                            <?php elseif ($item->price !== null && (float) $item->price > 0): ?>
                                                <span>RM <?= number_format((float) $item->price, 2) ?> / <?= e($item->unit) ?></span>
                                            <?php else: ?>
                                                <span style="font-size:12px;font-weight:600;color:#64748b;">Price not set</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="catalog-card-actions">
                                    <div style="display:inline-flex;gap:5px;align-items:center;">
                                        <?php if (($item->reusability ?? 'reusable') === 'reusable'): ?>
                                            <?php if ($item->isPermanentlySoldOut()): ?>
                                                <!-- Locked permanently Sold Out -->
                                            <?php elseif ($item->isPublishedToMarketplace()): ?>
                                                <a href="<?= route('business.marketplace', ['q' => $item->name]) ?>" class="btn-market-live" title="View on Marketplace">
                                                    On Market
                                                </a>
                                            <?php else: ?>
                                                <button type="button" class="btn-publish-market" title="Publish reusable item to Marketplace" onclick='openListMarketModal(<?= $item->id ?>, <?= json_encode($item->name) ?>, <?= json_encode($item->category) ?>, <?= json_encode($item->unit) ?>, <?= (float) $item->quantity ?>, <?= json_encode($item->reusability ?? "reusable") ?>, <?= json_encode($item->description ?? "") ?>, <?= json_encode($item->getAllImages()) ?>, <?= json_encode($item->condition ?? "Used") ?>, <?= json_encode($item->listing_type ?? "buy") ?>, <?= json_encode($item->price !== null ? (float) $item->price : null) ?>, <?= json_encode($item->pickup_address ?? "") ?>)'>
                                                    Publish
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <a href="<?= route('business.helper', ['item_id' => $item->id]) ?>" class="btn-ai-advisor" title="Open in EcoCycle Helper">
                                                Helper
                                            </a>
                                        <?php endif; ?>

                                        <a href="<?= route('business.inventory.edit', $item) ?>" class="btn-edit" title="Update item details">
                                            Update Details
                                        </a>

                                        <form method="POST" action="<?= route('business.inventory.destroy', $item) ?>" style="display:inline;margin:0;" onsubmit="return confirm('Are you sure you want to delete this inventory item?');">
                                            <?= csrf_field() ?>
                                            <?= method_field('DELETE') ?>
                                            <button type="submit" class="btn-delete" title="Delete item">Delete</button>
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
                        <p style="margin:0;color:var(--text-muted);font-size:13px;">Add your secondary materials, production cutoffs, or durable assets with photos to manage stock and share with partners.</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

<!-- Shared Dynamic Category Suggestions -->
<datalist id="existing_categories_list">
    <?php foreach ($categories as $cat): ?>
        <option value="<?= e($cat) ?>"></option>
    <?php endforeach; ?>
</datalist>

<!-- Add Inventory Modal -->
<div class="modal-backdrop" id="add-modal" style="display:none;" onclick="if(event.target === this) closeAddModal()">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 style="margin:0;font-size:18px;font-weight:700;color:#18422d;">Add New Inventory Item</h3>
            <button type="button" class="modal-close-btn" onclick="closeAddModal()" aria-label="Close">&times;</button>
        </div>
        <form method="POST" action="<?= route('business.inventory.store') ?>" id="add_inventory_form" enctype="multipart/form-data" onsubmit="return validateInventoryAddForm(this)">
            <?= csrf_field() ?>
            <!-- Hidden inputs populated dynamically -->
            <input type="hidden" name="saved_images" id="add_saved_images" value="">
            <input type="hidden" name="saved_image_slots" id="add_saved_image_slots" value="">
            <input type="hidden" name="analysis_token" id="add_analysis_token" value="">
            <input type="hidden" name="ai_analysis_json" id="add_ai_analysis_json" value="">
            <input type="hidden" name="reusability" id="add_reusability_hidden" value="reusable">
            <input type="hidden" name="listing_type" id="add_listing_type_hidden" value="buy">
            <input type="hidden" name="redirect_to" id="add_redirect_to" value="listing">
            <input type="hidden" name="publish_to_marketplace" id="add_publish_to_marketplace" value="0">

            <div class="modal-body">
                <!-- Inline Error Banner for Image Upload or AI Analysis Failure -->
                <div id="add_analysis_error" role="alert" style="display:none;margin-bottom:16px;padding:12px 14px;background:#fef2f2;border:1px solid #fecaca;border-left:3px solid #b91c1c;color:#991b1b;font-size:13px;line-height:1.5;">
                    <strong style="display:block;margin-bottom:2px;">Notice</strong>
                    <span id="add_analysis_error_text"></span>
                </div>

                <!-- 1. Material Photos (3 Independent Slots: Image 1, Image 2, Image 3) -->
                <div class="form-group" style="margin-bottom:20px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <label style="margin:0;font-size:13px;font-weight:700;color:#18422d;">
                            Material Photos <span style="color:#b91c1c;">*</span> <span style="font-weight:500;color:#64748b;">(1 to 3 images)</span>
                        </label>
                        <span id="add_img_counter" style="font-size:12px;font-weight:600;color:#64748b;">0 of 3 selected</span>
                    </div>

                    <div class="inv-photo-uploader-box" id="add_photo_uploader_box">
                        <input type="file" id="add_slot_input_0" name="image_1" accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;" onchange="handleAddSlotFile(0, this)">
                        <input type="file" id="add_slot_input_1" name="image_2" accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;" onchange="handleAddSlotFile(1, this)">
                        <input type="file" id="add_slot_input_2" name="image_3" accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;" onchange="handleAddSlotFile(2, this)">

                        <div class="inv-photo-hint" style="margin-bottom:12px;display:block;font-size:12px;color:#475569;">
                            Upload 1 to 3 independent photos (JPG, PNG, or WEBP, max 5MB each). The first image will be used as the primary cover photo.
                        </div>

                        <div class="inv-slots-grid-3" id="add_slots_grid">
                            <!-- Rendered dynamically via renderAddSlots() -->
                        </div>

                        <div id="add_img_error" style="display:none;margin-top:10px;padding:10px 12px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:12px;font-weight:600;">
                            Please upload at least 1 photo for this material (maximum 3 photos).
                        </div>
                    </div>
                </div>

                <!-- 2. Material Name & Category -->
                <div class="add-grid-2" style="margin-bottom:18px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="add_name" style="font-size:13px;font-weight:700;color:#18422d;margin-bottom:6px;display:block;">Material Name <span style="color:#b91c1c;">*</span></label>
                        <input id="add_name" name="name" type="text" required placeholder="e.g. Pine Timber Planks, Industrial HDPE Offcuts" class="add-input-field" onblur="checkAutoTriggerNonReusable()">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="add_category_input" style="font-size:13px;font-weight:700;color:#18422d;margin-bottom:6px;display:block;">Material Category <span style="color:#b91c1c;">*</span></label>
                        <input id="add_category_input" name="category" type="text" list="existing_categories_list" maxlength="100" required placeholder="e.g. Metals & machinery, Plastics, Wood" class="add-input-field">
                    </div>
                </div>

                <!-- 3. Description -->
                <div class="form-group" style="margin-bottom:18px;">
                    <label for="add_description" style="font-size:13px;font-weight:700;color:#18422d;margin-bottom:6px;display:block;">Description <span style="color:#b91c1c;">*</span></label>
                    <textarea id="add_description" name="description" rows="3" required placeholder="Describe material specifications, dimensions, condition, or source..." class="add-input-field" style="resize:vertical;" onblur="checkAutoTriggerNonReusable()"></textarea>
                </div>

                <!-- 4. Quantity, Unit of Measurement & Material Condition -->
                <div class="add-grid-3" style="margin-bottom:20px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="add_quantity" style="font-size:13px;font-weight:700;color:#18422d;margin-bottom:6px;display:block;">Quantity <span style="color:#b91c1c;">*</span></label>
                        <input id="add_quantity" name="quantity" type="number" step="0.01" min="0.01" required placeholder="e.g. 50" class="add-input-field">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="add_unit" style="font-size:13px;font-weight:700;color:#18422d;margin-bottom:6px;display:block;">Unit of Measurement <span style="color:#b91c1c;">*</span></label>
                        <select id="add_unit" name="unit" required class="add-input-field">
                            <option value="">Select unit...</option>
                            <?php foreach (\App\Models\Item::ALLOWED_UNITS as $unitVal => $unitLabel): ?>
                                <option value="<?= e($unitVal) ?>"><?= e($unitLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="add_condition" style="font-size:13px;font-weight:700;color:#18422d;margin-bottom:6px;display:block;">Material Condition <span style="color:#b91c1c;">*</span></label>
                        <select id="add_condition" name="condition" required class="add-input-field">
                            <?php foreach (\App\Models\Item::ALLOWED_CONDITIONS as $condOpt): ?>
                                <option value="<?= e($condOpt) ?>" <?= $condOpt === 'Used' ? 'selected' : '' ?>><?= e($condOpt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- 5. Reusability Classification: Reusable vs Not Reusable -->
                <div class="form-group" style="margin-bottom:18px;">
                    <label style="font-size:13px;font-weight:700;color:#18422d;display:block;margin-bottom:8px;">
                        Material Classification <span style="color:#b91c1c;">*</span>
                    </label>
                    <div class="add-grid-2">
                        <label class="reusability-toggle-card" id="card_choice_reusable" style="cursor:pointer;border:2px solid #18422d;padding:16px;display:flex;align-items:flex-start;gap:12px;background:#f0fdf4;" onclick="selectReusability('reusable')">
                            <input type="radio" name="reusability_choice" value="reusable" id="radio_choice_reusable" checked style="margin-top:3px;">
                            <div>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <span style="font-weight:700;color:#18422d;font-size:13.5px;">Reusable Material</span>
                                    <span style="background:#166534;color:#ffffff;font-size:10px;font-weight:700;padding:2px 6px;text-transform:uppercase;">Direct to Listing</span>
                                </div>
                                <span style="font-size:12px;color:#475569;line-height:1.45;display:block;margin-top:4px;">
                                    Item can be reused, resold, or exchanged. Adds directly to inventory and proceeds straight to the listing process. No AI analysis needed.
                                </span>
                            </div>
                        </label>
                        <label class="reusability-toggle-card" id="card_choice_not_reusable" style="cursor:pointer;border:1px solid #cbd5e1;padding:16px;display:flex;align-items:flex-start;gap:12px;background:#ffffff;" onclick="selectReusability('not_reusable')">
                            <input type="radio" name="reusability_choice" value="not_reusable" id="radio_choice_not_reusable" style="margin-top:3px;">
                            <div>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <span style="font-weight:700;color:#0f172a;font-size:13.5px;">Not Reusable (Waste / Scrap)</span>
                                    <span style="background:#b45309;color:#ffffff;font-size:10px;font-weight:700;padding:2px 6px;text-transform:uppercase;">Automatic AI Guidance</span>
                                </div>
                                <span style="font-size:12px;color:#475569;line-height:1.45;display:block;margin-top:4px;">
                                    Cutoffs, scrap, or waste that cannot be reused. Automatically triggers AI analysis for disposal guidance and suitable locations.
                                </span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Reusable Information Banner -->
                <div id="add_section_reusable" class="add-rect-box" style="background:#f0fdf4;border:1px solid #b9dec0;padding:14px 16px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#166534" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        <strong style="color:#166534;font-size:13px;">Direct Inventory Addition &amp; Listing Process</strong>
                    </div>
                    <p style="margin:0;font-size:12px;color:#2c523b;line-height:1.5;">
                        No AI analysis or suggestions are needed for reusable materials. Clicking below will add the item directly to your inventory and take you straight to the listing process to configure Sell, Free, or Lend options and publish to the Marketplace.
                    </p>
                </div>

                <!-- Not Reusable Container -->
                <div id="add_section_not_reusable" style="display:none;">
                    <!-- Prompt when details or photos are missing -->
                    <div id="add_non_reusable_prompt" class="add-rect-box" style="background:#fffbeb;border:1px solid #fde68a;padding:14px 16px;margin-bottom:14px;">
                        <div style="font-weight:700;color:#92400e;font-size:13px;margin-bottom:4px;">
                            Enter Item Details &amp; Upload Photo for Automatic AI Guidance
                        </div>
                        <p style="margin:0;font-size:12px;color:#78350f;line-height:1.45;">
                            Provide the Material Name, Description, and upload at least 1 Photo above. AI analysis will automatically run to generate step-by-step disposal guidance and find suitable nearby facilities.
                        </p>
                    </div>

                    <!-- Loading State while AI Analysis is executing -->
                    <div id="add_non_reusable_loading" class="add-rect-box" style="display:none;text-align:center;padding:28px 20px;background:#f8fafc;border:1px solid #cbd5e1;margin-bottom:14px;">
                        <div class="rect-loading-track"><div class="rect-loading-bar"></div></div>
                        <div style="font-weight:700;color:#18422d;font-size:14px;margin-top:12px;">Generating AI Disposal &amp; Recycling Guidance...</div>
                        <div style="font-size:12px;color:#64748b;margin-top:4px;">Analyzing material properties and locating suitable Malaysian disposal facilities.</div>
                    </div>

                    <!-- Step-by-Step Guidance & Suitable Disposal Locations -->
                    <div id="add_non_reusable_guidance" style="display:none;">
                        <!-- 1. Step-by-step Guidance Block -->
                        <div class="add-rect-box" style="margin-bottom:14px;background:#ffffff;border:1px solid #cbd5e1;padding:16px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;border-bottom:1px solid #e2e8f0;padding-bottom:8px;">
                                <h4 style="margin:0;font-size:13.5px;font-weight:700;color:#18422d;">AI Material Handling &amp; Disposal Guidance</h4>
                                <span style="font-size:11px;font-weight:700;color:#92400e;background:#fef3c7;border:1px solid #fde68a;padding:2px 8px;">Non-Reusable</span>
                            </div>

                            <div style="margin-bottom:12px;">
                                <strong style="display:block;font-size:11px;color:#64748b;text-transform:uppercase;margin-bottom:3px;">Recommended Handling Strategy</strong>
                                <p id="nr_guidance_strategy" style="margin:0;font-size:13px;color:#0f172a;font-weight:600;line-height:1.45;">-</p>
                            </div>

                            <div style="margin-bottom:12px;">
                                <strong style="display:block;font-size:11px;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Step-by-Step Handling &amp; Disposal Steps</strong>
                                <ol id="nr_guidance_steps" style="margin:0;padding-left:18px;font-size:12.5px;color:#334155;line-height:1.55;">
                                </ol>
                            </div>

                            <div id="nr_repurpose_block" style="margin-bottom:12px;display:none;">
                                <strong style="display:block;font-size:11px;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Alternative Repurposing / Recovery Options</strong>
                                <ul id="nr_guidance_repurpose" style="margin:0;padding-left:18px;font-size:12.5px;color:#334155;line-height:1.5;">
                                </ul>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;">
                                <div style="padding:10px 12px;background:#f4fbf5;border:1px solid #c2dec0;">
                                    <strong style="display:block;font-size:10.5px;color:#1b5e20;text-transform:uppercase;margin-bottom:3px;">Environmental Impact (Qualitative)</strong>
                                    <span id="nr_guidance_env" style="font-size:12px;color:#123321;line-height:1.4;">-</span>
                                </div>
                                <div style="padding:10px 12px;background:#eff6ff;border:1px solid #bfdbfe;">
                                    <strong style="display:block;font-size:10.5px;color:#1d4ed8;text-transform:uppercase;margin-bottom:3px;">Carbon Impact Guidance (Qualitative)</strong>
                                    <span id="nr_guidance_carbon" style="font-size:12px;color:#1e3a8a;line-height:1.4;">-</span>
                                </div>
                            </div>

                            <div id="nr_guidance_risks_box" style="padding:10px 12px;background:#fffbeb;border:1px solid #fde68a;border-left:3px solid #b45309;font-size:12px;color:#78350f;line-height:1.45;">
                                <strong>Compliance &amp; Disposal Risks:</strong> <span id="nr_guidance_risks">-</span>
                            </div>
                        </div>

                        <!-- 2. Suggestions on Suitable Disposal Locations & Facilities (Where the business can send the item) -->
                        <div class="add-rect-box" style="background:#ffffff;border:1px solid #cbd5e1;padding:16px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;border-bottom:1px solid #e2e8f0;padding-bottom:8px;">
                                <h4 style="margin:0;font-size:13.5px;font-weight:700;color:#18422d;">Suitable Disposal Locations &amp; Facilities</h4>
                                <span style="font-size:11px;font-weight:600;color:#475569;">Where the Business Can Send This Item</span>
                            </div>
                            <div id="nr_locations_list" style="display:flex;flex-direction:column;gap:10px;">
                                <!-- Populated dynamically from RecyclingCentreLocatorService -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer" id="add_modal_footer">
                <button type="button" class="add-btn-secondary" onclick="closeAddModal()">Cancel</button>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <button type="button" class="add-btn-secondary" id="btn_reanalyze_non_reusable" onclick="triggerNonReusableAnalysis(true)" style="display:none;">
                        Re-run AI Guidance
                    </button>
                    <button type="submit" class="add-btn-primary" id="btn_submit_reusable">
                        Add Item &amp; Proceed to Listing &rarr;
                    </button>
                    <button type="submit" class="add-btn-primary" id="btn_submit_non_reusable" style="display:none;">
                        Save to Inventory &amp; Open EcoCycle Helper &rarr;
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Inventory Modal (Supports Adding/Updating Photos) -->
<div class="modal-backdrop" id="edit-modal" style="display:none;" onclick="if(event.target === this) closeEditModal()">
    <div class="modal-dialog modal-dialog-large">
        <div class="modal-header">
            <h3>Edit Item Details</h3>
            <button type="button" class="modal-close-btn" onclick="closeEditModal()" aria-label="Close">&times;</button>
        </div>
        <form id="edit-form" method="POST" action="" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?= method_field('PUT') ?>
            <div class="modal-body">
                <!-- Update Photos: 3 Independent Image Slots -->
                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <label style="margin:0;font-weight:700;color:#18422d;">
                            Material Photos <small style="font-weight:500;color:#556c5e;">(1 to 3 independent images)</small>
                        </label>
                        <span id="edit_img_counter" style="font-size:12px;font-weight:700;color:#18422d;">0 of 3 images</span>
                    </div>

                    <div class="inv-photo-uploader-box" id="edit_photo_uploader_box">
                        <input type="file" id="edit_slot_input_0" name="image_1" accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;" onchange="handleEditSlotFile(0, this)">
                        <input type="file" id="edit_slot_input_1" name="image_2" accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;" onchange="handleEditSlotFile(1, this)">
                        <input type="file" id="edit_slot_input_2" name="image_3" accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;" onchange="handleEditSlotFile(2, this)">
                        <input type="hidden" id="edit_remove_image_0" name="remove_image_1" value="0">
                        <input type="hidden" id="edit_remove_image_1" name="remove_image_2" value="0">
                        <input type="hidden" id="edit_remove_image_2" name="remove_image_3" value="0">

                        <div class="inv-photo-hint" style="margin-bottom:12px;display:block;font-size:12px;color:#475569;">
                            Replace or remove individual image slots independently without affecting other slots.
                        </div>

                        <div class="inv-slots-grid-3" id="edit_slots_grid">
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
                        <label for="edit_category">Material Category *</label>
                        <input id="edit_category" name="category" type="text" list="existing_categories_list" maxlength="100" required placeholder="Enter material category">
                    </div>
                    <div class="form-group">
                        <label for="edit_unit">Unit of Measurement *</label>
                        <select id="edit_unit" name="unit" required>
                            <?php foreach (\App\Models\Item::ALLOWED_UNITS as $unitVal => $unitLabel): ?>
                                <option value="<?= e($unitVal) ?>"><?= e($unitLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Reusability Classification *</label>
                    <div class="reusability-toggle-grid">
                        <label class="reusability-toggle-card">
                            <input type="radio" name="reusability" id="edit_reusability_reusable" value="reusable" onchange="toggleReusabilityNotice('edit', this.value)">
                            <div class="toggle-card-content">
                                <span class="toggle-title" style="color:#15803d;">Reusable</span>
                                <span class="toggle-desc">Durable, multi-use material (Can be published to Marketplace)</span>
                            </div>
                        </label>
                        <label class="reusability-toggle-card">
                            <input type="radio" name="reusability" id="edit_reusability_not_reusable" value="not_reusable" onchange="toggleReusabilityNotice('edit', this.value)">
                            <div class="toggle-card-content">
                                <span class="toggle-title" style="color:#475569;">Non-Reusable</span>
                                <span class="toggle-desc">Single-use scrap / cutoffs (Private internal inventory only)</span>
                            </div>
                        </label>
                    </div>

                    <!-- Material Condition & Listing Settings -->
                    <div id="edit_marketplace_trade_panel" style="margin-top:12px;background:#f4fbf6;border:1px solid #b9dec0;border-radius:0;padding:14px 16px;">
                        <div style="margin-bottom:8px;">
                            <strong style="color:#0f5132;font-size:13px;">Material Condition &amp; Listing Preferences</strong>
                        </div>
                        <div class="form-row-3" style="margin-bottom:0;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label for="edit_listing_type">Listing Type *</label>
                                <select id="edit_listing_type" name="listing_type" onchange="toggleEditPriceField(this.value)">
                                    <option value="buy">Sell</option>
                                    <option value="free">Free</option>
                                </select>
                            </div>
                            <div class="form-group" id="edit_price_group" style="margin-bottom:0;">
                                <label for="edit_price">Price (RM)</label>
                                <input id="edit_price" name="price" type="number" step="0.01" min="0.01" placeholder="e.g. 25.00">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label for="edit_condition">Material Condition *</label>
                                <select id="edit_condition" name="condition" required>
                                    <?php foreach (\App\Models\Item::ALLOWED_CONDITIONS as $condOpt): ?>
                                        <option value="<?= e($condOpt) ?>"><?= e($condOpt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="edit_reusability_scrap_notice" style="display:none;margin-top:12px;padding:12px 14px;border-radius:0;background:#fef7f6;border:1px solid #fecaca;color:#991b1b;font-size:12px;line-height:1.45;">
                        <div style="margin-bottom:4px;">
                            <strong style="color:#7f1d1d;">Internal Scrap Only (Unlisted from Marketplace)</strong>
                        </div>
                        Marking this item as Non-Reusable removes it from the public marketplace and business directory. It remains in your inventory for waste monitoring and EcoCycle Helper recommendations.
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

<!-- List on Marketplace Modal -->
<div class="modal-backdrop" id="list-market-modal" style="display:none;" onclick="if(event.target === this) closeListMarketModal()">
    <div class="modal-dialog modal-dialog-large">
        <div class="modal-header">
            <h3>List Material on Marketplace</h3>
            <button type="button" class="modal-close-btn" onclick="closeListMarketModal()" aria-label="Close">&times;</button>
        </div>
        <form method="POST" action="<?= route('business.marketplace.store') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" id="lm_inventory_item_id" name="inventory_item_id" value="">
            <div class="modal-body">
                <p style="margin:0 0 14px;padding:10px 12px;border-radius:0;background:#f0f6ee;border:1px solid #cce5c8;font-size:12px;color:#173e2c;">
                    From Inventory: <strong id="lm_source_name"></strong> &middot; Available Stock: <span id="lm_source_stock" style="color:#176b40;font-weight:700;"></span>
                </p>

                <!-- Photos Upload for Marketplace Listing (max 3 slots) -->
                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <label style="margin:0;font-weight:700;color:#18422d;">
                            Material Photos <small style="font-weight:500;color:#556c5e;">(1 to 3 images - Inherits inventory photos by default)</small>
                        </label>
                        <span id="lm_img_counter" style="font-size:12px;font-weight:700;color:#18422d;">Using inventory photos</span>
                    </div>

                    <div class="inv-photo-uploader-box" id="lm_photo_uploader_box">
                        <input type="file" id="lm_images_form_input" name="images[]" multiple style="display:none;">
                        <input type="file" id="lm_images_picker" accept="image/png,image/jpeg,image/webp,image/jpg" multiple style="display:none;" onchange="handleNewLmImages(this)">

                        <div class="inv-photo-top-bar">
                            <button type="button" class="btn-choose-photos" id="btn_lm_photos" onclick="document.getElementById('lm_images_picker').click()">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                <span>Add / Replace Photos</span>
                            </button>
                            <span class="inv-photo-hint">Add up to 3 photos. Image 1 is the cover photo.</span>
                        </div>

                        <div class="inv-slots-grid-3" id="lm_slots_grid">
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
                        <input id="lm_category" name="category" type="text" list="existing_categories_list" maxlength="100" required placeholder="Enter material category">
                    </div>

                    <div class="form-group">
                        <label for="lm_condition">Material Condition *</label>
                        <select id="lm_condition" name="condition" required>
                            <?php foreach (\App\Models\Item::ALLOWED_CONDITIONS as $condOpt): ?>
                                <option value="<?= e($condOpt) ?>" <?= $condOpt === 'Used' ? 'selected' : '' ?>><?= e($condOpt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Listing Option (Sell, Free, or Lend) -->
                <div class="form-group">
                    <label>Listing Option *</label>
                    <div class="offer-toggle-grid" style="grid-template-columns:1fr 1fr 1fr;">
                        <label class="offer-toggle-card">
                            <input type="radio" name="listing_type" id="lm_type_buy" value="buy" checked onchange="handleListingTypeChange('buy', 'lm')">
                            <span class="offer-label">Sell</span>
                            <span class="offer-sub">Set RM price / unit</span>
                        </label>
                        <label class="offer-toggle-card">
                            <input type="radio" name="listing_type" id="lm_type_free" value="free" onchange="handleListingTypeChange('free', 'lm')">
                            <span class="offer-label">Free</span>
                            <span class="offer-sub">RM 0.00 Claim</span>
                        </label>
                        <label class="offer-toggle-card">
                            <input type="radio" name="listing_type" id="lm_type_lend" value="lend" onchange="handleListingTypeChange('lend', 'lm')">
                            <span class="offer-label">Lend</span>
                            <span class="offer-sub">Temporary Borrow</span>
                        </label>
                    </div>
                </div>

                <!-- Reusability Classification: Strictly Reusable for Marketplace -->
                <input type="hidden" name="reusability" value="reusable">
                <div class="form-group">
                    <label>Reusability Classification</label>
                    <div style="padding:10px 14px;border-radius:0;background:#f0f7ef;border:1px solid #b8dab4;">
                        <strong style="display:block;font-size:13px;color:#15803d;">Reusable Circular Material</strong>
                        <small style="color:#526b5a;font-size:11.5px;">Only reusable materials can be listed on the public marketplace for other businesses.</small>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="lm_quantity">Quantity to Offer *</label>
                        <input id="lm_quantity" name="quantity" type="number" step="0.01" min="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="lm_unit">Unit of Measurement *</label>
                        <select id="lm_unit" name="unit" required>
                            <?php foreach (\App\Models\Item::ALLOWED_UNITS as $unitVal => $unitLabel): ?>
                                <option value="<?= e($unitVal) ?>"><?= e($unitLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="lm_price_group">
                    <label for="lm_price" id="lm_price_label">Price per Unit (RM) *</label>
                    <input id="lm_price" name="price" type="number" step="0.01" min="0.01" placeholder="e.g. 15.00">
                    <small id="lm_price_hint" style="color:#6f7b72;font-size:11px;">Enter a Price per Unit (RM) greater than RM 0.00 for Sell listings.</small>
                </div>

                <div class="form-group">
                    <label for="lm_pickup_address">Collection / Pickup Address</label>
                    <input id="lm_pickup_address" name="pickup_address" type="text" maxlength="255" placeholder="Warehouse or collection address">
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

<!-- Circular Scrap Advisor Modal -->
<div class="modal-backdrop" id="ai-scrap-advisor-modal" style="display:none;" onclick="if(event.target === this) closeAiAdvisorModal()">
    <div class="modal-dialog modal-dialog-large" style="max-width:640px;">
        <div class="modal-header" style="background:#f8fafc;border-bottom:1px solid #cbd5e1;">
            <div>
                <h3 style="color:#143825;margin:0;">EcoCycle Scrap Advisor</h3>
                <small style="color:#526658;font-size:11.5px;font-weight:600;">Circular Recovery &amp; Licensed Facility Matcher</small>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeAiAdvisorModal()" aria-label="Close">&times;</button>
        </div>
        <form id="ai-advisor-form" method="POST" action="">
            <?= csrf_field() ?>
            <div class="modal-body" style="gap:14px;">
                <!-- Target Material Header -->
                <div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:0;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <strong id="ai_scrap_item_name" style="font-size:14px;color:#0f172a;display:block;">Scrap Material</strong>
                        <span id="ai_scrap_meta" style="font-size:12px;color:#64748b;">Category &middot; 0 kg in inventory</span>
                    </div>
                    <span style="font-size:11px;font-weight:700;background:#fef2f2;color:#b91c1c;padding:3px 8px;border-radius:0;border:1px solid #fecaca;">Non-Reusable</span>
                </div>

                <!-- Analysis Card -->
                <div style="background:#f8fafc;border:1px solid #cbd5e1;border-left:3px solid #143825;border-radius:0;padding:14px 16px;">
                    <h4 style="margin:0 0 8px;font-size:13px;color:#143825;">Recovery Recommendation</h4>
                    <p id="ai_treatment_strategy" style="margin:0 0 10px;font-size:12.5px;color:#334155;line-height:1.5;">
                        Analyzing material composition and industrial recovery paths...
                    </p>
                    <div style="background:#ffffff;border-radius:0;padding:10px 12px;border:1px solid #cbd5e1;font-size:12px;">
                        <strong style="color:#143825;display:block;margin-bottom:4px;">Recommended Action &amp; Circular Process:</strong>
                        <span id="ai_compliance_note" style="color:#334155;">Compliant with Malaysian Environmental Quality Act 1974. Scheduled for licensed secondary recovery.</span>
                    </div>
                </div>

                <!-- Recommended Destination Recyclers -->
                <div>
                    <label style="font-weight:700;color:#1e293b;margin-bottom:6px;display:block;font-size:12.5px;">
                        Recommended Licensed Recovery Destinations in Malaysia:
                    </label>
                    <div id="ai_destinations_container" style="display:flex;flex-direction:column;gap:8px;">
                        <!-- Rendered by JS -->
                    </div>
                </div>

                <!-- Action: Log to Wastage Monitoring -->
                <div style="background:#f0fdf4;border:1px solid #b9dec0;border-radius:0;padding:12px 14px;">
                    <strong style="display:block;font-size:12.5px;color:#15803d;margin-bottom:6px;">Track in Wastage Monitoring Module</strong>
                    <p style="margin:0 0 10px;font-size:11.5px;color:#33533e;line-height:1.4;">
                        Log this scrap directly into your official <strong>Wastage Monitoring</strong> tracker so you can monitor quantities, scheduled collection, and diversion analytics.
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
                <button type="submit" class="btn-save">
                    Log to Wastage Monitoring &rarr;
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Independent 3-slot image state for Add Modal (Image 1, Image 2, Image 3)
var addPhotoSlots = [null, null, null];
var addPhotoPreviews = [null, null, null];

// Independent 3-slot image state for Edit Modal (Image 1, Image 2, Image 3)
var editSlotInitial = [null, null, null];
var editSlotNewFiles = [null, null, null];
var editSlotNewPreviews = [null, null, null];
var editSlotRemoved = [false, false, false];

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

function normalizeConditionValue(rawCond) {
    var val = (rawCond || '').trim();
    if (val === 'New' || val === 'Damaged' || val === 'Used') {
        return val;
    }
    var lower = val.toLowerCase();
    if (lower.indexOf('damag') !== -1 || lower.indexOf('broken') !== -1 || lower.indexOf('defect') !== -1 || lower.indexOf('scrap') !== -1) {
        return 'Damaged';
    }
    if (lower.indexOf('new') !== -1 || lower.indexOf('unused') !== -1 || lower.indexOf('unopened') !== -1) {
        return 'New';
    }
    return 'Used';
}

function setSelectUnitValue(selectEl, rawUnit) {
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
        'kg': 'kg', 'kilogram': 'kg', 'kilograms': 'kg',
        'g': 'g', 'gram': 'g', 'grams': 'g',
        'l': 'L', 'litre': 'L', 'litres': 'L', 'liter': 'L', 'liters': 'L',
        'ml': 'mL', 'millilitre': 'mL', 'millilitres': 'mL',
        'pcs': 'pcs', 'pc': 'pcs', 'piece': 'pcs', 'pieces': 'pcs', 'unit': 'pcs', 'units': 'pcs', 'pallet': 'pcs', 'pallets': 'pcs', 'roll': 'pcs', 'rolls': 'pcs', 'sheet': 'pcs', 'sheets': 'pcs', 'drum': 'pcs', 'drums': 'pcs', 'crate': 'pcs', 'crates': 'pcs', 'ream': 'pcs', 'reams': 'pcs',
        'bag': 'Bag', 'bags': 'Bag',
        'box': 'Box', 'boxes': 'Box',
        't': 't', 'tonne': 't', 'tonnes': 't', 'ton': 't', 'tons': 't',
        'm': 'm', 'metre': 'm', 'metres': 'm', 'meter': 'm', 'meters': 'm',
        'm2': 'm²', 'm²': 'm²', 'sqm': 'm²', 'sq m': 'm²',
        'm3': 'm³', 'm³': 'm³', 'cbm': 'm³'
    };
    selectEl.value = map[lower] || 'kg';
}

function handleListingTypeChange(type, prefix) {
    var priceGroup = document.getElementById(prefix + '_price_group');
    var priceInput = document.getElementById(prefix + '_price');
    var priceHint = document.getElementById(prefix + '_price_hint');

    if (type === 'free' || type === 'lend') {
        if (priceGroup) {
            priceGroup.style.display = 'none';
        }
        if (priceInput) {
            priceInput.min = '0';
            priceInput.value = '0.00';
            priceInput.required = false;
        }
        if (priceHint) {
            priceHint.textContent = type === 'lend'
                ? 'Lend listings are offered at RM 0.00 with stock returned upon completion.'
                : 'Free listings are offered at RM 0.00.';
        }
    } else {
        if (priceGroup) {
            priceGroup.style.display = 'block';
        }
        if (priceInput) {
            priceInput.min = '0.01';
            if (priceInput.value === '0.00' || priceInput.value === '0') {
                priceInput.value = '';
            }
            priceInput.required = true;
        }
        if (priceHint) {
            priceHint.textContent = 'Enter a Price per Unit (RM) greater than RM 0.00 for Sell listings.';
        }
    }
}

function showAddAnalysisError(message) {
    var box = document.getElementById('add_analysis_error');
    var text = document.getElementById('add_analysis_error_text');
    if (text) text.textContent = message || 'Unable to analyze material. Please check your photos and details, then try again.';
    if (box) box.style.display = 'block';
    var modal = document.getElementById('add-modal');
    if (modal) {
        var body = modal.querySelector('.modal-body');
        if (body) body.scrollTop = 0;
    }
}

function clearAddAnalysisError() {
    var box = document.getElementById('add_analysis_error');
    var text = document.getElementById('add_analysis_error_text');
    if (text) text.textContent = '';
    if (box) box.style.display = 'none';
}

var addAnalysisCompleted = false;

function setAddModalStageActions(stage) {
    var btnAi = document.getElementById('btn_trigger_ai');
    var btnBack = document.getElementById('btn_back_to_details');
    var btnSubmit = document.getElementById('btn_submit_inventory');
    var btnUpdateDetails = document.getElementById('btn_submit_update_details');
    var btnOpenHelper = document.getElementById('btn_submit_open_helper');

    if (btnAi) btnAi.style.display = 'none';
    if (btnBack) btnBack.style.display = 'none';
    if (btnSubmit) btnSubmit.style.display = 'none';
    if (btnUpdateDetails) btnUpdateDetails.style.display = 'none';
    if (btnOpenHelper) btnOpenHelper.style.display = 'none';

    if (stage === 'stage_1') {
        if (btnAi) btnAi.style.display = 'inline-block';
    } else if (stage === 'loading') {
        // Loading: only Cancel button remains visible
    } else if (stage === 'stage_2') {
        if (btnBack) btnBack.style.display = 'inline-block';
        if (btnSubmit) btnSubmit.style.display = 'inline-block';
        var currentChoice = document.getElementById('add_reusability_hidden') ? document.getElementById('add_reusability_hidden').value : 'reusable';
        if (currentChoice === 'not_reusable') {
            if (btnOpenHelper) btnOpenHelper.style.display = 'inline-block';
        } else {
            if (btnUpdateDetails) btnUpdateDetails.style.display = 'inline-block';
        }
    }
}

function resetAddPhotoInputs() {
    for (var i = 0; i < 3; i++) {
        var inp = document.getElementById('add_slot_input_' + i);
        if (inp) inp.value = '';
    }
}

function getAddPhotoCount() {
    var count = 0;
    for (var i = 0; i < 3; i++) {
        if (addPhotoSlots[i]) count++;
    }
    return count;
}

function openAddModal() {
    addPhotoSlots = [null, null, null];
    addPhotoPreviews = [null, null, null];
    resetAddPhotoInputs();
    addAnalysisCompleted = false;
    clearAddAnalysisError();

    var form = document.getElementById('add_inventory_form');
    if (form) form.reset();

    var savedImgInput = document.getElementById('add_saved_images');
    if (savedImgInput) savedImgInput.value = '';

    var savedSlotsInput = document.getElementById('add_saved_image_slots');
    if (savedSlotsInput) savedSlotsInput.value = '';

    var tokenInput = document.getElementById('add_analysis_token');
    if (tokenInput) tokenInput.value = '';

    var aiJsonInput = document.getElementById('add_ai_analysis_json');
    if (aiJsonInput) aiJsonInput.value = '';

    var catInput = document.getElementById('add_category_input');
    if (catInput) catInput.value = '';

    var condSelect = document.getElementById('add_condition');
    if (condSelect) condSelect.value = 'Used';

    var pubInput = document.getElementById('add_publish_to_marketplace');
    if (pubInput) pubInput.value = '0';

    var redInput = document.getElementById('add_redirect_to');
    if (redInput) redInput.value = 'listing';

    renderAddSlots();
    selectReusability('reusable');

    var modal = document.getElementById('add-modal');
    modal.style.display = 'flex';
    var body = modal.querySelector('.modal-body');
    if (body) body.scrollTop = 0;
    document.body.style.overflow = 'hidden';
}

function backToAddDetails() {
    addAnalysisCompleted = false;
    clearAddAnalysisError();
    document.getElementById('add_stage_2').style.display = 'none';
    document.getElementById('add_stage_loading').style.display = 'none';
    document.getElementById('add_stage_1').style.display = 'block';
    setAddModalStageActions('stage_1');
    var modal = document.getElementById('add-modal');
    if (modal) {
        var body = modal.querySelector('.modal-body');
        if (body) body.scrollTop = 0;
    }
}

function closeAddModal() {
    document.getElementById('add-modal').style.display = 'none';
    document.body.style.overflow = '';
}

function triggerAddSlotInput(slotIndex) {
    var inp = document.getElementById('add_slot_input_' + slotIndex);
    if (inp) {
        inp.value = '';
        inp.click();
    }
}

function handleAddSlotFile(slotIndex, input) {
    if (!input.files || input.files.length === 0) return;
    var file = input.files[0];

    var allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    if (!file.type || (allowedTypes.indexOf(file.type.toLowerCase()) === -1 && !file.type.match('image.*'))) {
        showAddAnalysisError('File "' + file.name + '" is not a supported image type. Please upload JPG, PNG, or WEBP images.');
        input.value = '';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        showAddAnalysisError('File "' + file.name + '" exceeds the 5MB file size limit.');
        input.value = '';
        return;
    }

    clearAddAnalysisError();

    // Store ONLY in this exact slot without affecting any other slot
    addPhotoSlots[slotIndex] = file;
    var reader = new FileReader();
    reader.onload = function(e) {
        addPhotoPreviews[slotIndex] = e.target.result;
        renderAddSlots();
    };
    reader.readAsDataURL(file);
}

function clearAddPhotoSlot(slotIndex) {
    addPhotoSlots[slotIndex] = null;
    addPhotoPreviews[slotIndex] = null;
    var inp = document.getElementById('add_slot_input_' + slotIndex);
    if (inp) inp.value = '';
    renderAddSlots();
}

function renderAddSlots() {
    var grid = document.getElementById('add_slots_grid');
    var counter = document.getElementById('add_img_counter');
    var err = document.getElementById('add_img_error');
    if (err) err.style.display = 'none';

    var count = getAddPhotoCount();
    if (counter) {
        counter.textContent = count + ' of 3 selected' + (count === 0 ? ' (Min 1 required)' : '');
        counter.style.color = count >= 1 ? '#18422d' : '#b91c1c';
    }

    if (!grid) return;
    grid.innerHTML = '';

    var slotLabels = ['Image 1 (Cover)', 'Image 2', 'Image 3'];

    for (var idx = 0; idx < 3; idx++) {
        (function(slotIndex) {
            var wrapper = document.createElement('div');
            wrapper.className = 'inv-slot-wrapper';

            var header = document.createElement('div');
            header.className = 'inv-slot-header';
            header.innerHTML = '<span>' + slotLabels[slotIndex] + '</span>' +
                '<span style="font-weight:600;color:' + (addPhotoSlots[slotIndex] ? '#18422d' : '#94a3b8') + ';">' + (addPhotoSlots[slotIndex] ? 'Selected' : 'Empty') + '</span>';
            wrapper.appendChild(header);

            var preview = document.createElement('div');
            if (addPhotoSlots[slotIndex] && addPhotoPreviews[slotIndex]) {
                preview.className = 'inv-slot-preview-area';
                preview.title = 'Click to replace ' + slotLabels[slotIndex];
                preview.onclick = function() { triggerAddSlotInput(slotIndex); };
                preview.innerHTML = '<img src="' + addPhotoPreviews[slotIndex] + '" alt="' + slotLabels[slotIndex] + '">';
            } else {
                preview.className = 'inv-slot-preview-area empty-slot';
                preview.title = 'Click to upload ' + slotLabels[slotIndex];
                preview.onclick = function() { triggerAddSlotInput(slotIndex); };
                preview.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span>Select Image</span>';
            }
            wrapper.appendChild(preview);

            var actions = document.createElement('div');
            actions.className = 'inv-slot-actions';
            if (addPhotoSlots[slotIndex]) {
                var btnReplace = document.createElement('button');
                btnReplace.type = 'button';
                btnReplace.className = 'inv-slot-action-btn';
                btnReplace.textContent = 'Replace';
                btnReplace.onclick = function() { triggerAddSlotInput(slotIndex); };

                var btnRemove = document.createElement('button');
                btnRemove.type = 'button';
                btnRemove.className = 'inv-slot-action-btn btn-slot-remove';
                btnRemove.textContent = 'Remove';
                btnRemove.onclick = function() { clearAddPhotoSlot(slotIndex); };

                actions.appendChild(btnReplace);
                actions.appendChild(btnRemove);
            } else {
                var btnUpload = document.createElement('button');
                btnUpload.type = 'button';
                btnUpload.className = 'inv-slot-action-btn btn-slot-upload-full';
                btnUpload.textContent = 'Upload';
                btnUpload.onclick = function() { triggerAddSlotInput(slotIndex); };
                actions.appendChild(btnUpload);
            }
            wrapper.appendChild(actions);

            grid.appendChild(wrapper);
        })(idx);
    }
}

function selectReusability(choice) {
    var reusInput = document.getElementById('add_reusability_hidden');
    if (reusInput) reusInput.value = choice;

    var radioReusable = document.getElementById('radio_choice_reusable');
    var radioNotReusable = document.getElementById('radio_choice_not_reusable');
    var cardReusable = document.getElementById('card_choice_reusable');
    var cardNotReusable = document.getElementById('card_choice_not_reusable');
    var secReusable = document.getElementById('add_section_reusable');
    var secNotReusable = document.getElementById('add_section_not_reusable');
    var btnSubmitReusable = document.getElementById('btn_submit_reusable');
    var btnSubmitNonReusable = document.getElementById('btn_submit_non_reusable');
    var btnReanalyze = document.getElementById('btn_reanalyze_non_reusable');
    var redirectInput = document.getElementById('add_redirect_to');

    if (choice === 'reusable') {
        if (radioReusable) radioReusable.checked = true;
        if (radioNotReusable) radioNotReusable.checked = false;
        if (cardReusable) {
            cardReusable.style.border = '2px solid #18422d';
            cardReusable.style.background = '#f0fdf4';
        }
        if (cardNotReusable) {
            cardNotReusable.style.border = '1px solid #cbd5e1';
            cardNotReusable.style.background = '#ffffff';
        }
        if (secReusable) secReusable.style.display = 'block';
        if (secNotReusable) secNotReusable.style.display = 'none';
        if (btnSubmitReusable) btnSubmitReusable.style.display = 'inline-block';
        if (btnSubmitNonReusable) btnSubmitNonReusable.style.display = 'none';
        if (btnReanalyze) btnReanalyze.style.display = 'none';
        if (redirectInput) redirectInput.value = 'listing';
    } else {
        if (radioReusable) radioReusable.checked = false;
        if (radioNotReusable) radioNotReusable.checked = true;
        if (cardReusable) {
            cardReusable.style.border = '1px solid #cbd5e1';
            cardReusable.style.background = '#ffffff';
        }
        if (cardNotReusable) {
            cardNotReusable.style.border = '2px solid #0f172a';
            cardNotReusable.style.background = '#f8fafc';
        }
        if (secReusable) secReusable.style.display = 'none';
        if (secNotReusable) secNotReusable.style.display = 'block';
        if (btnSubmitReusable) btnSubmitReusable.style.display = 'none';
        if (btnSubmitNonReusable) btnSubmitNonReusable.style.display = 'inline-block';
        if (redirectInput) redirectInput.value = 'helper';

        // Automatically trigger AI analysis if fields and photo are present
        checkAutoTriggerNonReusable();
    }
}

function selectDecision(choice) {
    selectReusability(choice);
}

function checkAutoTriggerNonReusable() {
    var reusInput = document.getElementById('add_reusability_hidden');
    if (!reusInput || reusInput.value !== 'not_reusable') return;

    var name = (document.getElementById('add_name') ? document.getElementById('add_name').value : '').trim();
    var desc = (document.getElementById('add_description') ? document.getElementById('add_description').value : '').trim();
    var photoCount = getAddPhotoCount();

    var promptBox = document.getElementById('add_non_reusable_prompt');
    var loadingBox = document.getElementById('add_non_reusable_loading');
    var guidanceBox = document.getElementById('add_non_reusable_guidance');

    if (!name || photoCount < 1) {
        if (!addAnalysisCompleted) {
            if (promptBox) promptBox.style.display = 'block';
            if (loadingBox) loadingBox.style.display = 'none';
            if (guidanceBox) guidanceBox.style.display = 'none';
        }
        return;
    }

    if (!addAnalysisCompleted) {
        triggerNonReusableAnalysis(false);
    }
}

var isAnalyzingNonReusable = false;

function triggerNonReusableAnalysis(force) {
    if (isAnalyzingNonReusable) return;

    var name = (document.getElementById('add_name') ? document.getElementById('add_name').value : '').trim();
    var desc = (document.getElementById('add_description') ? document.getElementById('add_description').value : '').trim();
    var qty = (document.getElementById('add_quantity') ? document.getElementById('add_quantity').value : '').trim() || '1';
    var unit = (document.getElementById('add_unit') ? document.getElementById('add_unit').value : '').trim() || 'kg';
    var condition = (document.getElementById('add_condition') ? document.getElementById('add_condition').value : '').trim() || 'Used';
    var category = (document.getElementById('add_category_input') ? document.getElementById('add_category_input').value : '').trim();

    if (!name) {
        if (force) {
            showAddAnalysisError('Please enter the Material Name before running AI analysis.');
            document.getElementById('add_name').focus();
        }
        return;
    }
    if (getAddPhotoCount() < 1) {
        if (force) {
            var err = document.getElementById('add_img_error');
            if (err) err.style.display = 'block';
            showAddAnalysisError('Please upload at least 1 material photo to generate disposal guidance.');
        }
        return;
    }

    clearAddAnalysisError();
    isAnalyzingNonReusable = true;

    var promptBox = document.getElementById('add_non_reusable_prompt');
    var loadingBox = document.getElementById('add_non_reusable_loading');
    var guidanceBox = document.getElementById('add_non_reusable_guidance');
    var btnReanalyze = document.getElementById('btn_reanalyze_non_reusable');

    if (promptBox) promptBox.style.display = 'none';
    if (loadingBox) loadingBox.style.display = 'block';
    if (guidanceBox && force) guidanceBox.style.display = 'none';
    if (btnReanalyze) btnReanalyze.style.display = 'none';

    var formData = new FormData();
    formData.append('_token', '<?= csrf_token() ?>');
    formData.append('name', name);
    formData.append('description', desc || ('Material specifications for ' + name));
    formData.append('quantity', qty);
    formData.append('unit', unit);
    formData.append('condition', condition);
    formData.append('category', category);
    formData.append('reusability', 'not_reusable');

    for (var i = 0; i < 3; i++) {
        if (addPhotoSlots[i]) {
            formData.append('image_' + (i + 1), addPhotoSlots[i]);
        }
    }

    fetch('<?= route('business.inventory.ai-analyze') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(function(res) {
        return res.json().catch(function() {
            return { success: false, message: 'Unexpected server response during disposal guidance generation.' };
        }).then(function(payload) {
            if (!res.ok) {
                var msg = (payload && payload.message) ? payload.message : 'Disposal analysis failed. Please retry.';
                if (payload && payload.errors) {
                    var firstKey = Object.keys(payload.errors)[0];
                    if (firstKey && Array.isArray(payload.errors[firstKey]) && payload.errors[firstKey][0]) {
                        msg = payload.errors[firstKey][0];
                    }
                }
                throw new Error(msg);
            }
            return payload;
        });
    })
    .then(function(data) {
        isAnalyzingNonReusable = false;
        if (loadingBox) loadingBox.style.display = 'none';

        if (!data || !data.success || !data.analysis) {
            var failMsg = (data && data.message) ? data.message : 'Could not generate disposal guidance for this material.';
            showAddAnalysisError(failMsg);
            if (promptBox) promptBox.style.display = 'block';
            return;
        }

        addAnalysisCompleted = true;

        if (data.analysis_token) {
            var tokenEl = document.getElementById('add_analysis_token');
            if (tokenEl) tokenEl.value = data.analysis_token;
        }
        if (data.saved_images && data.saved_images.length > 0) {
            var simg = document.getElementById('add_saved_images');
            if (simg) simg.value = JSON.stringify(data.saved_images);
        }
        if (data.saved_image_slots && data.saved_image_slots.length === 3) {
            var sslots = document.getElementById('add_saved_image_slots');
            if (sslots) sslots.value = JSON.stringify(data.saved_image_slots);
        }

        var analysis = data.analysis;
        var aiJsonInput = document.getElementById('add_ai_analysis_json');
        if (aiJsonInput) {
            try { aiJsonInput.value = JSON.stringify(analysis); } catch (e) { aiJsonInput.value = ''; }
        }

        // Auto-fill category if currently empty
        var catInput = document.getElementById('add_category_input');
        if (catInput && !catInput.value.trim() && analysis.suggested_category) {
            catInput.value = analysis.suggested_category;
        }

        // Render Strategy
        var stratEl = document.getElementById('nr_guidance_strategy');
        if (stratEl) {
            stratEl.textContent = (analysis.action_recommendation ? (analysis.action_recommendation + ' — ') : '') + (analysis.strategy || analysis.disposal_method || 'Segregate and route to licensed industrial waste recovery.');
        }

        // Render Step-by-Step Guidance Steps
        var stepsEl = document.getElementById('nr_guidance_steps');
        if (stepsEl) {
            stepsEl.innerHTML = '';
            var steps = analysis.owner_actions || analysis.recycling_steps || analysis.recommended_actions || [];
            if (Array.isArray(steps) && steps.length > 0) {
                steps.forEach(function(step) {
                    var li = document.createElement('li');
                    li.textContent = step;
                    li.style.marginBottom = '4px';
                    stepsEl.appendChild(li);
                });
            } else {
                var defaultSteps = [
                    'Segregate this non-reusable material at the source to prevent cross-contamination.',
                    'Package or store securely in labeled containers/drums awaiting collection.',
                    'Coordinate transport with a certified recycler or local council (PBT) licensed waste handler.'
                ];
                defaultSteps.forEach(function(step) {
                    var li = document.createElement('li');
                    li.textContent = step;
                    li.style.marginBottom = '4px';
                    stepsEl.appendChild(li);
                });
            }
        }

        // Render Repurposing Ideas
        var repurposeBlock = document.getElementById('nr_repurpose_block');
        var repurposeList = document.getElementById('nr_guidance_repurpose');
        var ideas = analysis.repurpose_ideas || analysis.repurposing_ideas || [];
        if (repurposeBlock && repurposeList) {
            if (Array.isArray(ideas) && ideas.length > 0) {
                repurposeList.innerHTML = '';
                ideas.forEach(function(idea) {
                    var li = document.createElement('li');
                    li.textContent = idea;
                    li.style.marginBottom = '3px';
                    repurposeList.appendChild(li);
                });
                repurposeBlock.style.display = 'block';
            } else {
                repurposeBlock.style.display = 'none';
            }
        }

        // Render Environmental Impact
        var envEl = document.getElementById('nr_guidance_env');
        if (envEl) {
            envEl.textContent = analysis.environmental_impact || 'Proper segregation and licensed recovery reduce landfill burden and prevent environmental pollution.';
        }

        // Render Carbon Impact
        var carbonEl = document.getElementById('nr_guidance_carbon');
        if (carbonEl) {
            carbonEl.textContent = analysis.carbon_impact || analysis.carbon_saving_estimate || 'Diverting non-reusable material to certified recovery facilities helps lower overall lifecycle emissions.';
        }

        // Render Risks
        var risksEl = document.getElementById('nr_guidance_risks');
        if (risksEl) {
            risksEl.textContent = analysis.improper_disposal_risks || analysis.disposal_warning || 'Improper disposal, mixing with general municipal waste, or open dumping violates local council and DOE environmental regulations.';
        }

        // Render Suitable Disposal Locations (Where the business can send the item)
        var locationsContainer = document.getElementById('nr_locations_list');
        var locations = data.disposal_locations || analysis.nearby_centres || [];
        if (locationsContainer) {
            locationsContainer.innerHTML = '';
            if (Array.isArray(locations) && locations.length > 0) {
                locations.slice(0, 3).forEach(function(loc) {
                    var locCard = document.createElement('div');
                    locCard.style.cssText = 'padding:11px 13px;border:1px solid #e2e8f0;background:#f8fafc;display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;';
                    
                    var left = document.createElement('div');
                    left.style.cssText = 'flex:1;min-width:200px;';
                    
                    var title = document.createElement('div');
                    title.style.cssText = 'font-weight:700;font-size:13px;color:#18422d;margin-bottom:2px;';
                    title.textContent = loc.name + ' (' + (loc.distance_km || loc.distance || 'Near') + ' km)';
                    left.appendChild(title);

                    var addr = document.createElement('div');
                    addr.style.cssText = 'font-size:11.5px;color:#475569;margin-bottom:3px;line-height:1.4;';
                    addr.textContent = loc.address || (loc.city + ', ' + loc.state);
                    left.appendChild(addr);

                    if (loc.phone || loc.operating_hours) {
                        var meta = document.createElement('div');
                        meta.style.cssText = 'font-size:11px;color:#64748b;';
                        meta.textContent = [loc.phone, loc.operating_hours].filter(Boolean).join(' • ');
                        left.appendChild(meta);
                    }

                    var right = document.createElement('div');
                    if (loc.directions_url) {
                        var mapLink = document.createElement('a');
                        mapLink.href = loc.directions_url;
                        mapLink.target = '_blank';
                        mapLink.rel = 'noopener noreferrer';
                        mapLink.style.cssText = 'display:inline-block;padding:5px 10px;background:#ffffff;border:1px solid #cbd5e1;color:#18422d;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap;';
                        mapLink.textContent = 'Directions ↗';
                        right.appendChild(mapLink);
                    }

                    locCard.appendChild(left);
                    if (right.childNodes.length > 0) locCard.appendChild(right);
                    locationsContainer.appendChild(locCard);
                });
            } else {
                locationsContainer.innerHTML = '<div style="font-size:12px;color:#64748b;padding:8px 10px;background:#f8fafc;border:1px solid #e2e8f0;">No specific facility found in the immediate radius. Check EcoCycle Helper or local council (PBT) licensed scheduled waste collectors.</div>';
            }
        }

        if (guidanceBox) guidanceBox.style.display = 'block';
        if (btnReanalyze) btnReanalyze.style.display = 'inline-block';
    })
    .catch(function(err) {
        isAnalyzingNonReusable = false;
        if (loadingBox) loadingBox.style.display = 'none';
        showAddAnalysisError(err && err.message ? err.message : 'Failed to generate disposal guidance. Please try again.');
        if (promptBox) promptBox.style.display = 'block';
    });
}

function startGeminiAnalysis() {
    triggerNonReusableAnalysis(true);
}

function validateInventoryAddForm(form) {
    clearAddAnalysisError();

    var nameInput = document.getElementById('add_name');
    if (!nameInput || !nameInput.value.trim()) {
        showAddAnalysisError('Please enter the Material Name.');
        if (nameInput) nameInput.focus();
        return false;
    }

    var catInput = document.getElementById('add_category_input');
    if (!catInput || !catInput.value.trim()) {
        showAddAnalysisError('Please enter or confirm the Material Category.');
        if (catInput) catInput.focus();
        return false;
    }

    var descInput = document.getElementById('add_description');
    if (!descInput || !descInput.value.trim()) {
        showAddAnalysisError('Please enter a description for the material.');
        if (descInput) descInput.focus();
        return false;
    }

    var qtyInput = document.getElementById('add_quantity');
    if (!qtyInput || !qtyInput.value.trim() || parseFloat(qtyInput.value) <= 0) {
        showAddAnalysisError('Please enter a valid Quantity greater than 0.');
        if (qtyInput) qtyInput.focus();
        return false;
    }

    var unitEl = document.getElementById('add_unit');
    if (!unitEl || !unitEl.value.trim()) {
        showAddAnalysisError('Please select a Unit of Measurement from the dropdown.');
        if (unitEl) unitEl.focus();
        return false;
    }

    var condEl = document.getElementById('add_condition');
    if (!condEl || !condEl.value.trim()) {
        showAddAnalysisError('Please select a Material Condition (New, Damaged, or Used).');
        if (condEl) condEl.focus();
        return false;
    }

    var savedImagesVal = document.getElementById('add_saved_images') ? document.getElementById('add_saved_images').value : '';
    var savedSlotsVal = document.getElementById('add_saved_image_slots') ? document.getElementById('add_saved_image_slots').value : '';
    if (getAddPhotoCount() < 1 && !savedImagesVal && !savedSlotsVal) {
        var err = document.getElementById('add_img_error');
        if (err) err.style.display = 'block';
        showAddAnalysisError('Please upload at least 1 photo for this material (minimum 1, maximum 3 photos required).');
        return false;
    }

    var reusability = document.getElementById('add_reusability_hidden') ? document.getElementById('add_reusability_hidden').value : 'reusable';

    // 1. Reusable items: Added directly to inventory and proceed to listing process. No AI needed!
    if (reusability === 'reusable') {
        document.getElementById('add_redirect_to').value = 'listing';
        document.getElementById('add_publish_to_marketplace').value = '0';
        return true;
    }

    // 2. Non-Reusable items: Open EcoCycle Helper after saving
    document.getElementById('add_redirect_to').value = 'helper';
    document.getElementById('add_publish_to_marketplace').value = '0';

    return true;
}

// Edit Modal Functions (3 Independent Image Slots)
function openEditModal(id, name, category, unit, notes, reusability, description, existingImageSlots, price, listingType, condition) {
    document.getElementById('edit-form').action = '/business/inventory/' + id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_category').value = category || '';
    setSelectUnitValue(document.getElementById('edit_unit'), unit);
    document.getElementById('edit_notes').value = notes || '';
    if (document.getElementById('edit_description')) {
        document.getElementById('edit_description').value = description || '';
    }

    var normalizedListingType = (listingType === 'free') ? 'free' : 'buy';
    if (document.getElementById('edit_listing_type')) {
        document.getElementById('edit_listing_type').value = normalizedListingType;
    }
    if (document.getElementById('edit_price')) {
        document.getElementById('edit_price').value = (price !== null && price !== undefined && parseFloat(price) > 0) ? price : '';
    }
    if (document.getElementById('edit_condition')) {
        document.getElementById('edit_condition').value = normalizeConditionValue(condition);
    }
    toggleEditPriceField(normalizedListingType);

    if (reusability === 'not_reusable') {
        var el = document.getElementById('edit_reusability_not_reusable');
        if (el) el.checked = true;
        toggleReusabilityNotice('edit', 'not_reusable');
    } else {
        var el2 = document.getElementById('edit_reusability_reusable');
        if (el2) el2.checked = true;
        toggleReusabilityNotice('edit', 'reusable');
    }

    var slotsArr = Array.isArray(existingImageSlots) ? existingImageSlots : [];
    editSlotInitial = [slotsArr[0] || null, slotsArr[1] || null, slotsArr[2] || null];
    editSlotNewFiles = [null, null, null];
    editSlotNewPreviews = [null, null, null];
    editSlotRemoved = [false, false, false];

    for (var i = 0; i < 3; i++) {
        var fileInp = document.getElementById('edit_slot_input_' + i);
        if (fileInp) fileInp.value = '';
        var remInp = document.getElementById('edit_remove_image_' + i);
        if (remInp) remInp.value = '0';
    }

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

function triggerEditSlotInput(slotIndex) {
    var inp = document.getElementById('edit_slot_input_' + slotIndex);
    if (inp) inp.click();
}

function handleEditSlotFile(slotIndex, input) {
    if (!input.files || input.files.length === 0) return;
    var file = input.files[0];

    if (!file.type || !file.type.match('image.*')) {
        alert('File "' + file.name + '" is not a valid image.');
        input.value = '';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        alert('File "' + file.name + '" exceeds the 5MB size limit.');
        input.value = '';
        return;
    }

    editSlotNewFiles[slotIndex] = file;
    editSlotRemoved[slotIndex] = false;
    var remInp = document.getElementById('edit_remove_image_' + slotIndex);
    if (remInp) remInp.value = '0';

    var reader = new FileReader();
    reader.onload = function(e) {
        editSlotNewPreviews[slotIndex] = e.target.result;
        renderEditSlots();
    };
    reader.readAsDataURL(file);
}

function clearEditPhotoSlot(slotIndex) {
    editSlotNewFiles[slotIndex] = null;
    editSlotNewPreviews[slotIndex] = null;
    editSlotRemoved[slotIndex] = true;

    var fileInp = document.getElementById('edit_slot_input_' + slotIndex);
    if (fileInp) fileInp.value = '';
    var remInp = document.getElementById('edit_remove_image_' + slotIndex);
    if (remInp) remInp.value = '1';

    renderEditSlots();
}

function renderEditSlots() {
    var grid = document.getElementById('edit_slots_grid');
    var counter = document.getElementById('edit_img_counter');
    if (!grid) return;

    grid.innerHTML = '';
    var slotLabels = ['Image 1 (Cover)', 'Image 2', 'Image 3'];
    var activeCount = 0;

    for (var idx = 0; idx < 3; idx++) {
        (function(slotIndex) {
            var previewUrl = null;
            var statusLabel = 'Empty';

            if (editSlotNewFiles[slotIndex] && editSlotNewPreviews[slotIndex]) {
                previewUrl = editSlotNewPreviews[slotIndex];
                statusLabel = 'New';
                activeCount++;
            } else if (!editSlotRemoved[slotIndex] && editSlotInitial[slotIndex]) {
                previewUrl = editSlotInitial[slotIndex];
                statusLabel = 'Saved';
                activeCount++;
            }

            var wrapper = document.createElement('div');
            wrapper.className = 'inv-slot-wrapper';

            var header = document.createElement('div');
            header.className = 'inv-slot-header';
            header.innerHTML = '<span>' + slotLabels[slotIndex] + '</span>' +
                '<span style="font-weight:600;color:' + (previewUrl ? '#18422d' : '#94a3b8') + ';">' + statusLabel + '</span>';
            wrapper.appendChild(header);

            var preview = document.createElement('div');
            if (previewUrl) {
                preview.className = 'inv-slot-preview-area';
                preview.title = 'Click to replace ' + slotLabels[slotIndex];
                preview.onclick = function() { triggerEditSlotInput(slotIndex); };
                preview.innerHTML = '<img src="' + previewUrl + '" alt="' + slotLabels[slotIndex] + '">';
            } else {
                preview.className = 'inv-slot-preview-area empty-slot';
                preview.title = 'Click to upload ' + slotLabels[slotIndex];
                preview.onclick = function() { triggerEditSlotInput(slotIndex); };
                preview.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span>Select Image</span>';
            }
            wrapper.appendChild(preview);

            var actions = document.createElement('div');
            actions.className = 'inv-slot-actions';
            if (previewUrl) {
                var btnReplace = document.createElement('button');
                btnReplace.type = 'button';
                btnReplace.className = 'inv-slot-action-btn';
                btnReplace.textContent = 'Replace';
                btnReplace.onclick = function() { triggerEditSlotInput(slotIndex); };

                var btnRemove = document.createElement('button');
                btnRemove.type = 'button';
                btnRemove.className = 'inv-slot-action-btn btn-slot-remove';
                btnRemove.textContent = 'Remove';
                btnRemove.onclick = function() { clearEditPhotoSlot(slotIndex); };

                actions.appendChild(btnReplace);
                actions.appendChild(btnRemove);
            } else {
                var btnUpload = document.createElement('button');
                btnUpload.type = 'button';
                btnUpload.className = 'inv-slot-action-btn btn-slot-upload-full';
                btnUpload.textContent = 'Upload';
                btnUpload.onclick = function() { triggerEditSlotInput(slotIndex); };
                actions.appendChild(btnUpload);
            }
            wrapper.appendChild(actions);

            grid.appendChild(wrapper);
        })(idx);
    }

    if (counter) {
        counter.textContent = activeCount + ' of 3 images';
        counter.style.color = activeCount >= 1 ? '#18422d' : '#64748b';
    }
}

var lmPhotosTransfer = new DataTransfer();
var lmInitialImages = [];

function openListMarketModal(id, name, category, unit, quantity, reusability, description, existingImages, condition, listingType, price, pickupAddress) {
    if (reusability === 'not_reusable') {
        alert('This material is classified as Non-Reusable. Only reusable materials can be published to the public Marketplace.');
        return;
    }
    document.getElementById('lm_inventory_item_id').value = id;
    document.getElementById('lm_source_name').textContent = name;
    document.getElementById('lm_source_stock').textContent = quantity + ' ' + unit;
    document.getElementById('lm_title').value = name;
    document.getElementById('lm_category').value = category;
    setSelectUnitValue(document.getElementById('lm_unit'), unit);
    if (document.getElementById('lm_condition')) {
        document.getElementById('lm_condition').value = normalizeConditionValue(condition);
    }
    document.getElementById('lm_quantity').value = quantity;
    if (document.getElementById('lm_description')) {
        document.getElementById('lm_description').value = description || '';
    }
    if (document.getElementById('lm_pickup_address')) {
        document.getElementById('lm_pickup_address').value = pickupAddress || '';
    }
    var resolvedType = (listingType === 'free' || listingType === 'lend') ? listingType : 'buy';
    var buyRadio = document.getElementById('lm_type_buy');
    var freeRadio = document.getElementById('lm_type_free');
    var lendRadio = document.getElementById('lm_type_lend');
    if (resolvedType === 'free' && freeRadio) {
        freeRadio.checked = true;
    } else if (resolvedType === 'lend' && lendRadio) {
        lendRadio.checked = true;
    } else if (buyRadio) {
        buyRadio.checked = true;
    }
    handleListingTypeChange(resolvedType, 'lm');
    var lmPriceEl = document.getElementById('lm_price');
    if (lmPriceEl && resolvedType === 'buy') {
        lmPriceEl.value = (price !== null && price !== undefined && parseFloat(price) > 0) ? parseFloat(price).toFixed(2) : '';
    }

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
            counter.textContent = count + ' of 3 custom photos selected';
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
                        (index === 0 ? '<span class="inv-slot-cover-badge">Listing Cover</span>' : '<span style="position:absolute;bottom:2px;left:4px;background:rgba(0,0,0,0.65);color:#fff;font-size:9px;padding:1px 5px;border-radius:0;font-weight:700;">#' + (index + 1) + '</span>') +
                        '<button type="button" class="inv-slot-remove-btn" title="Remove photo" onclick="removeLmPhoto(' + index + ')">&times;</button>';
                };
                reader.readAsDataURL(file);
                grid.appendChild(card);
            })(i);
        }

        if (count < 3) {
            var addSlot = document.createElement('div');
            addSlot.className = 'inv-slot-card empty-slot';
            addSlot.title = 'Click to add more photos';
            addSlot.onclick = function() {
                document.getElementById('lm_images_picker').click();
            };
            addSlot.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span>Add</span>';
            grid.appendChild(addSlot);

            for (var j = count + 1; j < 3; j++) {
                var emptySlot = document.createElement('div');
                emptySlot.className = 'inv-slot-card empty-slot';
                emptySlot.style.opacity = '0.45';
                emptySlot.style.cursor = 'pointer';
                emptySlot.onclick = function() {
                    document.getElementById('lm_images_picker').click();
                };
                emptySlot.innerHTML = '<span style="font-size:11px;color:#94a3b8;">Image ' + (j + 1) + '</span>';
                grid.appendChild(emptySlot);
            }
        }
    } else {
        // Show current inventory images
        var initCount = Math.min(lmInitialImages.length, 3);
        if (counter) {
            counter.textContent = initCount > 0 ? 'Using ' + initCount + ' inventory photo(s)' : 'No photos in inventory item';
            counter.style.color = '#556c5e';
        }

        for (var k = 0; k < initCount; k++) {
            var exCard = document.createElement('div');
            exCard.className = 'inv-slot-card';
            exCard.innerHTML = '<img src="' + lmInitialImages[k] + '" alt="Photo ' + (k + 1) + '">' +
                (k === 0 ? '<span class="inv-slot-cover-badge">Inherited Cover</span>' : '<span style="position:absolute;bottom:2px;left:4px;background:rgba(0,0,0,0.65);color:#fff;font-size:9px;padding:1px 5px;border-radius:0;font-weight:700;">#' + (k + 1) + '</span>');
            grid.appendChild(exCard);
        }

        if (initCount < 3) {
            var addSlotInit = document.createElement('div');
            addSlotInit.className = 'inv-slot-card empty-slot';
            addSlotInit.title = 'Click to add custom photos';
            addSlotInit.onclick = function() {
                document.getElementById('lm_images_picker').click();
            };
            addSlotInit.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg><span>Add</span>';
            grid.appendChild(addSlotInit);

            for (var m = initCount + 1; m < 3; m++) {
                var emptySlotInit = document.createElement('div');
                emptySlotInit.className = 'inv-slot-card empty-slot';
                emptySlotInit.style.opacity = '0.45';
                emptySlotInit.style.cursor = 'pointer';
                emptySlotInit.onclick = function() {
                    document.getElementById('lm_images_picker').click();
                };
                emptySlotInit.innerHTML = '<span style="font-size:11px;color:#94a3b8;">Image ' + (m + 1) + '</span>';
                grid.appendChild(emptySlotInit);
            }
        }
    }

    var formInput = document.getElementById('lm_images_form_input');
    if (formInput) {
        formInput.files = lmPhotosTransfer.files;
    }

    if (btn) {
        btn.disabled = count >= 3;
        btn.style.opacity = count >= 3 ? '0.5' : '1';
    }
}

function handleNewLmImages(picker) {
    if (!picker.files || picker.files.length === 0) return;

    var newFiles = picker.files;
    for (var i = 0; i < newFiles.length; i++) {
        if (lmPhotosTransfer.files.length >= 3) {
            alert('Maximum 3 photos allowed. Additional photos were not added.');
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
        card.style.cssText = 'border:1px solid #cbd5e1;background:#ffffff;border-radius:0;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;cursor:pointer;transition:border-color 0.15s;';
        card.innerHTML = '<div>' +
            '<div style="display:flex;align-items:center;gap:6px;">' +
                '<strong style="font-size:12.5px;color:#0f172a;">' + dest.name + '</strong>' +
                '<span style="background:#e0f2fe;color:#0369a1;font-size:10px;font-weight:700;padding:2px 6px;border-radius:0;">' + dest.badge + '</span>' +
            '</div>' +
            '<span style="font-size:11.5px;color:#64748b;">' + dest.loc + ' &middot; ' + dest.type + '</span>' +
        '</div>' +
        '<button type="button" style="background:#f8fafc;border:1px solid #cbd5e1;color:#1e293b;padding:4px 10px;border-radius:0;font-size:11px;font-weight:700;cursor:pointer;">Select</button>';
        
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
    if (urlParams.get('add') === '1') {
        openAddModal();
    }
});
</script>
</body>
</html>
