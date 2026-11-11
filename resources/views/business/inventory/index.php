<?php
/** @var \App\Models\User|null $business */
/** @var \Illuminate\Pagination\LengthAwarePaginator<\App\Models\InventoryItem> $items */
/** @var \Illuminate\Support\Collection<int, string> $categories */
/** @var string|null $search */
/** @var string|null $selectedCategory */
/** @var string|null $selectedStatus */
/** @var \Illuminate\Support\ViewErrorBag $errors */

$business = $business ?? \Illuminate\Support\Facades\Auth::guard()->user();
$items = $items ?? new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
$categories = $categories ?? collect();
$search = $search ?? '';
$selectedCategory = $selectedCategory ?? '';
$selectedStatus = $selectedStatus ?? '';
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
            font: 400 13px var(--sans);
            background: #fff;
            min-width: 220px;
        }
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #cadbc9;
            border-radius: 6px;
            font: 400 13px var(--sans);
            background: #fff;
        }
        .btn-filter {
            padding: 8px 14px;
            border: 1px solid #78aa84;
            border-radius: 6px;
            background: #fff;
            color: var(--green);
            font: 600 12px var(--sans);
            cursor: pointer;
        }
        .btn-filter:hover {
            background: #eef5eb;
        }
        .btn-add-primary {
            padding: 8px 16px;
            border: 1px solid var(--green);
            border-radius: 6px;
            background: var(--green);
            color: #fff;
            font: 600 13px var(--sans);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-add-primary:hover {
            background: #11502f;
        }
        .action-group {
            display: inline-flex;
            gap: 6px;
            align-items: center;
        }
        .btn-stock-in {
            padding: 3px 8px;
            border: 1px solid #a3cfbb;
            border-radius: 4px;
            background: #e8f5e9;
            color: #1b5e20;
            font: 600 11px var(--sans);
            cursor: pointer;
        }
        .btn-stock-in:hover {
            background: #c8e6c9;
        }
        .btn-stock-out {
            padding: 3px 8px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            background: #fff5f5;
            color: #721c24;
            font: 600 11px var(--sans);
            cursor: pointer;
        }
        .btn-stock-out:hover {
            background: #f8d7da;
        }
        .btn-edit {
            padding: 3px 8px;
            border: 1px solid #cadbc9;
            border-radius: 4px;
            background: #fff;
            color: #3b5042;
            font: 600 11px var(--sans);
            cursor: pointer;
        }
        .btn-edit:hover {
            background: #f4f8ef;
        }
        .btn-delete {
            padding: 3px 8px;
            border: 1px solid #fecaca;
            border-radius: 4px;
            background: #fff;
            color: #dc2626;
            font: 600 11px var(--sans);
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
        <div class="dashboard-alert success" role="status"><?= e(session('status')) ?></div>
    <?php endif; ?>
    <?php if ($errors->any()): ?>
        <div class="dashboard-alert error" role="alert"><?= e($errors->first()) ?></div>
    <?php endif; ?>

    <div class="dashboard-title">
        <div>
            <p class="eyebrow">RAW MATERIALS &amp; CATALOG</p>
            <h1>Inventory Management</h1>
            <p>Track materials, maintain safety thresholds, and record stock movements.</p>
        </div>
        <button type="button" class="btn-add-primary" onclick="openAddModal()">+ Add New Item</button>
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

                <select class="filter-select" name="status">
                    <option value="">All Statuses</option>
                    <option value="in_stock" <?= $selectedStatus === 'in_stock' ? 'selected' : '' ?>>In stock</option>
                    <option value="low_stock" <?= $selectedStatus === 'low_stock' ? 'selected' : '' ?>>Low stock</option>
                    <option value="out_of_stock" <?= $selectedStatus === 'out_of_stock' ? 'selected' : '' ?>>Out of stock</option>
                </select>

                <button type="submit" class="btn-filter">Filter</button>
                <?php if ($search || $selectedCategory || $selectedStatus): ?>
                    <a href="<?= route('business.inventory') ?>" style="font-size:12px;color:#6f7b72;text-decoration:underline;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($items->isNotEmpty()): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:28%;">Material / Item</th>
                        <th style="width:16%;">Category</th>
                        <th style="width:15%;">Stock Level</th>
                        <th style="width:14%;">Low Threshold</th>
                        <th style="width:12%;">Status</th>
                        <th style="width:15%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td data-label="Item">
                                <strong><?= e($item->name) ?></strong>
                                <?php if ($item->sku): ?>
                                    <small style="display:block;color:#6f7b72;font-size:10px;">SKU: <?= e($item->sku) ?></small>
                                <?php endif; ?>
                            </td>
                            <td data-label="Category"><?= e($item->category) ?></td>
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
                                    <button type="button" class="btn-edit" title="Edit details" onclick="openEditModal(<?= $item->id ?>, '<?= e(addslashes($item->name)) ?>', '<?= e(addslashes($item->sku ?? '')) ?>', '<?= e(addslashes($item->category)) ?>', '<?= e(addslashes($item->unit)) ?>', '<?= $item->minimum_threshold ?? '' ?>', '<?= e(addslashes($item->notes ?? '')) ?>')">
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
            <div class="empty-state-card">
                <p>No materials found matching your criteria.</p>
                <button type="button" class="btn-primary-inline" onclick="openAddModal()">+ Add New Item</button>
            </div>
        <?php endif; ?>
    </section>
        </main>
    </div>

<!-- Add Inventory Modal -->
<div class="modal-backdrop" id="add-modal" style="display:none;" onclick="if(event.target === this) closeAddModal()">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Add New Inventory Item</h3>
            <button type="button" class="modal-close-btn" onclick="closeAddModal()">✕</button>
        </div>
        <form method="POST" action="<?= route('business.inventory.store') ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label for="add_name">Item / Material Name *</label>
                    <input id="add_name" name="name" type="text" required placeholder="e.g. Clean Cotton Offcuts">
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label for="add_sku">SKU / Code (optional)</label>
                        <input id="add_sku" name="sku" type="text" placeholder="e.g. TEX-COT-02">
                    </div>
                    <div class="form-group">
                        <label for="add_category">Category *</label>
                        <select id="add_category" name="category" required>
                            <option value="">Select category</option>
                            <option value="Packaging">Packaging</option>
                            <option value="Textiles">Textiles &amp; Fabrics</option>
                            <option value="Wood & Timber">Wood &amp; Timber</option>
                            <option value="Plastics">Plastics &amp; Polymers</option>
                            <option value="Metals">Metals &amp; Scrap</option>
                            <option value="Glass & Containers">Glass &amp; Containers</option>
                            <option value="Organic & Food Byproducts">Organic &amp; Food Byproducts</option>
                            <option value="Paper & Cardboard">Paper &amp; Cardboard</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label for="add_quantity">Initial Quantity *</label>
                        <input id="add_quantity" name="quantity" type="number" step="0.01" min="0" required placeholder="e.g. 50">
                    </div>
                    <div class="form-group">
                        <label for="add_unit">Unit of Measure *</label>
                        <input id="add_unit" name="unit" type="text" required placeholder="e.g. kg, pcs, meters, boxes">
                    </div>
                </div>
                <div class="form-group">
                    <label for="add_threshold">Low Stock Alert Threshold (optional)</label>
                    <input id="add_threshold" name="minimum_threshold" type="number" step="0.01" min="0" placeholder="e.g. 10 (Alert when stock falls to or below this level)">
                </div>
                <div class="form-group">
                    <label for="add_notes">Notes / Storage Specifications (optional)</label>
                    <textarea id="add_notes" name="notes" rows="2" placeholder="e.g. Clean scrap ready for recycling or resale"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn-save">+ Save Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Inventory Modal -->
<div class="modal-backdrop" id="edit-modal" style="display:none;" onclick="if(event.target === this) closeEditModal()">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Edit Item Details</h3>
            <button type="button" class="modal-close-btn" onclick="closeEditModal()">✕</button>
        </div>
        <form id="edit-form" method="POST" action="">
            <?= csrf_field() ?>
            <?= method_field('PUT') ?>
            <div class="modal-body">
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
                            <option value="Packaging">Packaging</option>
                            <option value="Textiles">Textiles &amp; Fabrics</option>
                            <option value="Wood & Timber">Wood &amp; Timber</option>
                            <option value="Plastics">Plastics &amp; Polymers</option>
                            <option value="Metals">Metals &amp; Scrap</option>
                            <option value="Glass & Containers">Glass &amp; Containers</option>
                            <option value="Organic & Food Byproducts">Organic &amp; Food Byproducts</option>
                            <option value="Paper & Cardboard">Paper &amp; Cardboard</option>
                            <option value="Other">Other</option>
                        </select>
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

<script>
function openAddModal() {
    document.getElementById('add-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeAddModal() {
    document.getElementById('add-modal').style.display = 'none';
    document.body.style.overflow = '';
}
function openEditModal(id, name, sku, category, unit, threshold, notes) {
    document.getElementById('edit-form').action = '/business/inventory/' + id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_sku').value = sku;
    document.getElementById('edit_category').value = category;
    document.getElementById('edit_unit').value = unit;
    document.getElementById('edit_threshold').value = threshold;
    document.getElementById('edit_notes').value = notes;
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
function toggleSidebar() {
    var sidebar = document.getElementById('dashboard-sidebar');
    if (sidebar) sidebar.classList.toggle('open');
}
function toggleNotificationMenu(e) {
    e.stopPropagation();
    var menu = document.getElementById('notification-dropdown');
    if (menu) menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', function() {
    var menu = document.getElementById('notification-dropdown');
    if (menu) menu.style.display = 'none';
});
function handleNavNotice(e, name) {
    e.preventDefault();
    alert('The ' + name + ' module is active for your business account.');
}
</script>
<script src="/js/language-switcher.js" defer></script>
</body>
</html>
