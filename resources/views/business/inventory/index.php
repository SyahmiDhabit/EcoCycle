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
    <link rel="stylesheet" href="/css/business/dashboard.css">
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

        /* Image Dropzone & Preview Styles */
        .inv-image-dropzone {
            border: 2px dashed #b7d6b8;
            background: #f7fbf6;
            border-radius: 10px;
            padding: 18px 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .inv-image-dropzone:hover {
            border-color: var(--brand-green);
            background: #eef6ec;
        }
        .inv-thumbs-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin-top: 10px;
        }
        .inv-thumb-card {
            position: relative;
            height: 68px;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #c2d8bf;
            background: #ffffff;
        }
        .inv-thumb-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .inv-thumb-cover-tag {
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
                                            <button type="button" class="btn-list-market" title="List on Marketplace (Buy, Borrow, or Free)" onclick="openListMarketModal(<?= $item->id ?>, '<?= e(addslashes($item->name)) ?>', '<?= e(addslashes($item->category)) ?>', '<?= e(addslashes($item->unit)) ?>', <?= (float)$item->quantity ?>, '<?= e($item->reusability ?? 'reusable') ?>')">
                                                🛒 List
                                            </button>
                                            <button type="button" class="btn-edit" title="Edit details" onclick="openEditModal(<?= $item->id ?>, '<?= e(addslashes($item->name)) ?>', '<?= e(addslashes($item->sku ?? '')) ?>', '<?= e(addslashes($item->category)) ?>', '<?= e(addslashes($item->unit)) ?>', '<?= $item->minimum_threshold ?? '' ?>', '<?= e(addslashes($item->notes ?? '')) ?>', '<?= e($item->reusability ?? 'reusable') ?>')">
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
        <form method="POST" action="<?= route('business.inventory.store') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="modal-body">
                <!-- 1 to 5 Images Upload Section -->
                <div class="form-group">
                    <label style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-weight:700;color:#18422d;">Material Photos (1 to 5 Images)</span>
                        <small style="color:#617769;font-weight:500;" id="add_img_notice">0 of 5 selected</small>
                    </label>
                    <div class="inv-image-dropzone" onclick="document.getElementById('add_images_input').click()">
                        <input type="file" id="add_images_input" name="images[]" multiple accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;" onchange="previewAddInventoryImages(this)">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#22543d" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            <span style="font-size:13px;font-weight:700;color:#18422d;">Click to upload 1 to 5 photos</span>
                            <small style="font-size:11px;color:#617769;">PNG, JPG, or WEBP up to 5MB each. First image becomes the cover photo.</small>
                        </div>
                    </div>
                    <div class="inv-thumbs-grid" id="add_thumbs_preview" style="display:none;"></div>
                </div>

                <div class="form-group">
                    <label for="add_name">Item / Material Name *</label>
                    <input id="add_name" name="name" type="text" required placeholder="e.g. Reclaimed Timber Planks, Heavy Duty Pallets">
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

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="add_reusability">Reusability Classification *</label>
                        <select id="add_reusability" name="reusability" required>
                            <option value="reusable" selected>↻ Reusable (Multi-use / circular / exchangeable)</option>
                            <option value="not_reusable">⊘ Not Reusable (Single-use / non-reusable scrap)</option>
                        </select>
                        <small style="color:#6f7b72;font-size:11px;">Indicate whether this material can be reused or is single-use scrap.</small>
                    </div>
                    <div class="form-group">
                        <label for="add_quantity">Initial Quantity *</label>
                        <input id="add_quantity" name="quantity" type="number" step="0.01" min="0" required placeholder="e.g. 50">
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="add_unit">Unit of Measure *</label>
                        <input id="add_unit" name="unit" type="text" required placeholder="e.g. kg, pcs, meters, boxes">
                    </div>
                    <div class="form-group">
                        <label for="add_threshold">Low Stock Alert Threshold (optional)</label>
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
                <!-- Update Photos -->
                <div class="form-group">
                    <label style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-weight:700;color:#18422d;">Update Photos (1 to 5 Images - Optional)</span>
                        <small style="color:#617769;font-weight:500;" id="edit_img_notice">Leave empty to keep current photos</small>
                    </label>
                    <div class="inv-image-dropzone" onclick="document.getElementById('edit_images_input').click()">
                        <input type="file" id="edit_images_input" name="images[]" multiple accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;" onchange="previewEditInventoryImages(this)">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#22543d" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            <span style="font-size:12px;font-weight:700;color:#18422d;">Click to upload new photos</span>
                            <small style="font-size:10px;color:#617769;">Replacing uploads will update your primary cover image.</small>
                        </div>
                    </div>
                    <div class="inv-thumbs-grid" id="edit_thumbs_preview" style="display:none;"></div>
                </div>

                <div class="form-group">
                    <label for="edit_name">Item / Material Name *</label>
                    <input id="edit_name" name="name" type="text" required>
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
                    <label for="edit_reusability">Reusability Classification *</label>
                    <select id="edit_reusability" name="reusability" required>
                        <option value="reusable">↻ Reusable (Multi-use / circular / exchangeable)</option>
                        <option value="not_reusable">⊘ Not Reusable (Single-use / non-reusable scrap)</option>
                    </select>
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

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="lm_listing_type">Listing Option (How to Offer) *</label>
                        <select id="lm_listing_type" name="listing_type" required onchange="handleListingTypeChange(this.value, 'lm')">
                            <option value="buy" selected>💰 Buy (For Sale with Price)</option>
                            <option value="borrow">🔄 Borrow (Loan / Temporary Rental)</option>
                            <option value="free">🎁 Take for Free (Free Giveaway)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="lm_reusability">Reusability Classification *</label>
                        <select id="lm_reusability" name="reusability" required>
                            <option value="reusable">↻ Reusable</option>
                            <option value="not_reusable">⊘ Not Reusable</option>
                        </select>
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
function openAddModal() {
    document.getElementById('add-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeAddModal() {
    document.getElementById('add-modal').style.display = 'none';
    document.body.style.overflow = '';
}
function openEditModal(id, name, sku, category, unit, threshold, notes, reusability) {
    document.getElementById('edit-form').action = '/business/inventory/' + id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_sku').value = sku;
    document.getElementById('edit_category').value = category;
    document.getElementById('edit_unit').value = unit;
    document.getElementById('edit_threshold').value = threshold;
    document.getElementById('edit_notes').value = notes;
    if (document.getElementById('edit_reusability')) {
        document.getElementById('edit_reusability').value = reusability || 'reusable';
    }
    document.getElementById('edit-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
    document.body.style.overflow = '';
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
    document.getElementById('lm_inventory_item_id').value = id;
    document.getElementById('lm_source_name').textContent = name;
    document.getElementById('lm_source_stock').textContent = quantity + ' ' + unit;
    document.getElementById('lm_title').value = name;
    document.getElementById('lm_category').value = category;
    document.getElementById('lm_unit').value = unit;
    document.getElementById('lm_quantity').value = quantity;
    document.getElementById('lm_reusability').value = reusability || 'reusable';
    document.getElementById('lm_listing_type').value = 'buy';
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

// Image Preview Helper for Add Modal
function previewAddInventoryImages(input) {
    var preview = document.getElementById('add_thumbs_preview');
    var notice = document.getElementById('add_img_notice');
    preview.innerHTML = '';

    if (!input.files || input.files.length === 0) {
        preview.style.display = 'none';
        notice.textContent = '0 of 5 selected';
        return;
    }

    if (input.files.length > 5) {
        alert('You can select a maximum of 5 images. Only the first 5 will be uploaded.');
    }

    var count = Math.min(input.files.length, 5);
    notice.textContent = count + ' of 5 selected';
    preview.style.display = 'grid';

    for (var i = 0; i < count; i++) {
        (function(idx) {
            var file = input.files[idx];
            var reader = new FileReader();
            reader.onload = function(e) {
                var card = document.createElement('div');
                card.className = 'inv-thumb-card';
                card.innerHTML = '<img src="' + e.target.result + '" alt="preview">' +
                    (idx === 0 ? '<div class="inv-thumb-cover-tag">Cover</div>' : '');
                preview.appendChild(card);
            };
            reader.readAsDataURL(file);
        })(i);
    }
}

// Image Preview Helper for Edit Modal
function previewEditInventoryImages(input) {
    var preview = document.getElementById('edit_thumbs_preview');
    var notice = document.getElementById('edit_img_notice');
    preview.innerHTML = '';

    if (!input.files || input.files.length === 0) {
        preview.style.display = 'none';
        notice.textContent = 'Leave empty to keep current photos';
        return;
    }

    var count = Math.min(input.files.length, 5);
    notice.textContent = count + ' of 5 new images selected';
    preview.style.display = 'grid';

    for (var i = 0; i < count; i++) {
        (function(idx) {
            var file = input.files[idx];
            var reader = new FileReader();
            reader.onload = function(e) {
                var card = document.createElement('div');
                card.className = 'inv-thumb-card';
                card.innerHTML = '<img src="' + e.target.result + '" alt="preview">' +
                    (idx === 0 ? '<div class="inv-thumb-cover-tag">Cover</div>' : '');
                preview.appendChild(card);
            };
            reader.readAsDataURL(file);
        })(i);
    }
}

// Image Preview Helper for List on Marketplace Modal
function previewInventoryListImages(input) {
    var preview = document.getElementById('lm-img-previews');
    var notice = document.getElementById('lm-img-notice');
    preview.innerHTML = '';

    if (!input.files || input.files.length === 0) {
        preview.style.display = 'none';
        notice.textContent = '0 of 5 selected (Inherits inventory photos by default)';
        return;
    }

    var count = Math.min(input.files.length, 5);
    notice.textContent = count + ' of 5 images selected';
    preview.style.display = 'grid';

    for (var i = 0; i < count; i++) {
        (function(idx) {
            var file = input.files[idx];
            var reader = new FileReader();
            reader.onload = function(e) {
                var card = document.createElement('div');
                card.className = 'inv-thumb-card';
                card.innerHTML = '<img src="' + e.target.result + '" alt="preview">' +
                    (idx === 0 ? '<div class="inv-thumb-cover-tag">Cover</div>' : '');
                preview.appendChild(card);
            };
            reader.readAsDataURL(file);
        })(i);
    }
}
</script>
</body>
</html>
