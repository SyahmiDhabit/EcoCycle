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
        .modal-dialog-large {
            width: min(640px, 96vw) !important;
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
            <!-- Breadcrumbs -->
            <div class="breadcrumb-row">
                <a href="/business">Overview</a>
                <span class="sep">/</span>
                <span class="current">Inventory</span>
            </div>

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
                            <a href="<?= route('business.inventory') ?>" style="font-size:12px;color:#6f7b72;text-decoration:underline;">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if ($items->isNotEmpty()): ?>
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
                                    <td data-label="Item">
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
                                            <span class="badge-reusable" title="Reusable circular material">↻ Reusable</span>
                                        <?php else: ?>
                                            <span class="badge-not-reusable" title="Single-use / non-reusable scrap">⊘ Not Reusable</span>
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
                                                <button type="button" class="btn-list-market" title="List on Marketplace (Buy, Borrow, or Free)" onclick="openListMarketModal(<?= $item->id ?>, '<?= e(addslashes($item->name)) ?>', '<?= e(addslashes($item->category)) ?>', '<?= e(addslashes($item->unit)) ?>', <?= (float)$item->quantity ?>, 'reusable')">
                                                    🛒 List
                                                </button>
                                            <?php else: ?>
                                                <span class="btn-owner-only" title="Non-reusable material is kept in your account only and cannot be listed on the marketplace">🔒 Owner only</span>
                                            <?php endif; ?>
                                            <button type="button" class="btn-edit" title="Edit details" onclick="openEditModal(<?= $item->id ?>, '<?= e(addslashes($item->name)) ?>', '<?= e(addslashes($item->sku ?? '')) ?>', '<?= e(addslashes($item->category)) ?>', '<?= e(addslashes($item->unit)) ?>', '<?= $item->minimum_threshold ?? '' ?>', '<?= e(addslashes($item->notes ?? '')) ?>', '<?= e($item->reusability ?? 'reusable') ?>', '<?= e(addslashes($item->description ?? '')) ?>')">
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
                            Material Photos * <small style="font-weight:500;color:#556c5e;">(Minimum 1, Maximum 5 photos)</small>
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
                                <span class="toggle-desc">Durable, multi-use. Can be listed on the public marketplace for other businesses to buy, borrow, or take.</span>
                            </div>
                        </label>
                        <label class="reusability-toggle-card">
                            <input type="radio" name="reusability" value="not_reusable" onchange="toggleReusabilityNotice('add', this.value)">
                            <div class="toggle-card-content">
                                <span class="toggle-title" style="color:#475569;">⊘ Not Reusable (Scrap)</span>
                                <span class="toggle-desc">Single-use cutoffs / scrap. Saved in your owner account only (will NOT appear or be listed on the marketplace).</span>
                            </div>
                        </label>
                    </div>
                    <div id="add_reusability_scrap_notice" style="display:none;margin-top:8px;padding:9px 12px;border-radius:6px;background:#fef7f6;border:1px solid #fecaca;color:#991b1b;font-size:12px;line-height:1.4;">
                        🔒 <strong>Owner Account Only:</strong> Not-reusable materials remain saved in your account for internal tracking. They cannot be listed on the public marketplace for other businesses.
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
                            <input type="radio" name="reusability" id="edit_reusability_reusable" value="reusable">
                            <div class="toggle-card-content">
                                <span class="toggle-title" style="color:#15803d;">↻ Reusable</span>
                                <span class="toggle-desc">Durable, multi-use, circular</span>
                            </div>
                        </label>
                        <label class="reusability-toggle-card">
                            <input type="radio" name="reusability" id="edit_reusability_not_reusable" value="not_reusable">
                            <div class="toggle-card-content">
                                <span class="toggle-title" style="color:#475569;">⊘ Not Reusable</span>
                                <span class="toggle-desc">Single-use scrap / cutoffs</span>
                            </div>
                        </label>
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

                <!-- Photos Upload for Marketplace Listing -->
                <div class="form-group">
                    <label style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-weight:700;color:#18422d;">Material Photos (1 to 5 Images - Optional)</span>
                        <small style="color:#6f7b72;" id="lm-img-notice">0 of 5 selected (Inherits inventory photos by default)</small>
                    </label>
                    <div class="inv-image-dropzone" onclick="document.getElementById('lm_images').click()">
                        <input type="file" id="lm_images" name="images[]" multiple accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;" onchange="previewInventoryListImages(this)">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#22543d" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            <span style="font-size:12px;font-weight:700;color:#18422d;">Click to upload or override photos</span>
                            <small style="font-size:11px;color:#617769;">If left blank, existing photos from this inventory item will be used.</small>
                        </div>
                    </div>
                    <div class="inv-thumbs-grid" id="lm-img-previews" style="display:none;"></div>
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

<script>
// DataTransfer stores for Add and Edit Modals
var addPhotosTransfer = new DataTransfer();
var editPhotosTransfer = new DataTransfer();

function toggleReusabilityNotice(prefix, val) {
    var notice = document.getElementById(prefix + '_reusability_scrap_notice');
    if (notice) {
        notice.style.display = val === 'not_reusable' ? 'block' : 'none';
    }
}

function openAddModal() {
    addPhotosTransfer = new DataTransfer();
    renderAddSlots();
    document.getElementById('add-modal').style.display = 'flex';
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
function openEditModal(id, name, sku, category, unit, threshold, notes, reusability, description) {
    document.getElementById('edit-form').action = '/business/inventory/' + id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_sku').value = sku;
    document.getElementById('edit_category').value = category;
    document.getElementById('edit_unit').value = unit;
    document.getElementById('edit_threshold').value = threshold;
    document.getElementById('edit_notes').value = notes;
    if (document.getElementById('edit_description')) {
        document.getElementById('edit_description').value = description || '';
    }
    if (reusability === 'not_reusable') {
        var el = document.getElementById('edit_reusability_not_reusable');
        if (el) el.checked = true;
    } else {
        var el2 = document.getElementById('edit_reusability_reusable');
        if (el2) el2.checked = true;
    }
    editPhotosTransfer = new DataTransfer();
    renderEditSlots();
    document.getElementById('edit-modal').style.display = 'flex';
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

    if (counter) {
        counter.textContent = count === 0 ? 'Leave empty to keep current photos' : count + ' of 5 new photos selected';
    }

    var formInput = document.getElementById('edit_images_form_input');
    if (formInput) {
        formInput.files = editPhotosTransfer.files;
    }

    if (btn) {
        btn.disabled = count >= 5;
        btn.style.opacity = count >= 5 ? '0.5' : '1';
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
    document.getElementById('adjust-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeAdjustModal() {
    document.getElementById('adjust-modal').style.display = 'none';
    document.body.style.overflow = '';
}

function openListMarketModal(id, name, category, unit, quantity, reusability) {
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
    var buyRadio = document.getElementById('lm_type_buy');
    if (buyRadio) buyRadio.checked = true;
    handleListingTypeChange('buy', 'lm');
    document.getElementById('list-market-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeListMarketModal() {
    document.getElementById('list-market-modal').style.display = 'none';
    document.body.style.overflow = '';
}

function handleListingTypeChange(type, prefix) {
    var priceGroup = document.getElementById(prefix + '_price_group');
    var priceInput = document.getElementById(prefix + '_price');
    var priceLabel = document.getElementById(prefix + '_price_label');
    var priceHint = document.getElementById(prefix + '_price_hint');
    if (!priceGroup || !priceInput) return;

    if (type === 'free') {
        priceGroup.style.display = 'none';
        priceInput.required = false;
        priceInput.value = '0';
    } else if (type === 'borrow') {
        priceGroup.style.display = 'block';
        priceInput.required = false;
        priceInput.value = '0';
        if (priceLabel) priceLabel.textContent = 'Security Deposit / Fee (RM)';
        if (priceHint) priceHint.textContent = 'Optional security deposit or loan fee (leave 0 for free borrow).';
    } else {
        priceGroup.style.display = 'block';
        priceInput.required = true;
        if (priceLabel) priceLabel.textContent = 'Unit Price (RM) *';
        if (priceHint) priceHint.textContent = 'Specify price in RM per unit.';
    }
}

function previewInventoryListImages(input) {
    var preview = document.getElementById('lm-img-previews');
    var notice = document.getElementById('lm-img-notice');
    if (!preview) return;
    preview.innerHTML = '';

    if (!input.files || input.files.length === 0) {
        preview.style.display = 'none';
        if (notice) notice.textContent = '0 of 5 selected (Inherits inventory photos by default)';
        return;
    }

    var count = Math.min(input.files.length, 5);
    if (notice) notice.textContent = count + ' of 5 images selected';
    preview.style.display = 'grid';

    for (var i = 0; i < count; i++) {
        (function(idx) {
            var file = input.files[idx];
            var reader = new FileReader();
            reader.onload = function(e) {
                var card = document.createElement('div');
                card.className = 'inv-slot-card';
                card.innerHTML = '<img src="' + e.target.result + '" alt="preview">' +
                    (idx === 0 ? '<span class="inv-slot-cover-badge">Cover</span>' : '');
                preview.appendChild(card);
            };
            reader.readAsDataURL(file);
        })(i);
    }
}

// Initial slot render
document.addEventListener('DOMContentLoaded', function() {
    renderAddSlots();
});
</script>
</body>
</html>
