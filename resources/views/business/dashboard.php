<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Business workspace — EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css">
</head>
<body class="dashboard-page">
<header class="dashboard-header">
    <a class="brand" href="/" aria-label="EcoCycle home"><span class="brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span><span>EcoCycle</span></a>
    
    <nav class="header-nav" style="display:flex;gap:18px;align-items:center;">
        <a class="nav-link active" href="/business">Workspace</a>
        <a class="nav-link" href="/business/inventory">Inventory</a>
        <a class="nav-link" href="/business/application">Account Status</a>
    </nav>

    <form class="business-signout" method="POST" action="/logout"><?= csrf_field() ?><button type="submit">Sign out</button></form>
</header>
<main class="dashboard-wrap">
    <?php if (session('status')): ?>
        <div class="dashboard-alert success" role="status"><?= e(session('status')) ?></div>
    <?php endif; ?>
    <?php if ($errors->any()): ?>
        <div class="dashboard-alert error" role="alert"><?= e($errors->first()) ?></div>
    <?php endif; ?>

    <div class="dashboard-title">
        <div>
            <p class="eyebrow">BUSINESS WORKSPACE</p>
            <h1>Good morning, <?= e($business->business_name ?: $business->name) ?></h1>
            <p>A snapshot of your inventory and circular activity.</p>
        </div>
        <span class="preview-badge" style="background:#e8f4ec;color:#186538;font-weight:600;">✓ Verified SME</span>
    </div>

    <section class="stat-grid" aria-label="Business summary">
        <article class="stat-card">
            <span>Tracked items</span>
            <strong><?= number_format($trackedItemsCount) ?></strong>
            <small><?= $trackedItemsCount === 1 ? '1 active item' : "{$trackedItemsCount} items on record" ?></small>
        </article>
        <article class="stat-card">
            <span>Materials listed</span>
            <strong><?= number_format($materialsListedCount) ?></strong>
            <small><?= $materialsListedCount === 1 ? '1 live listing' : "{$materialsListedCount} active in marketplace" ?></small>
        </article>
        <article class="stat-card">
            <span>Waste diverted</span>
            <strong><?= number_format($wasteDivertedKg, 1) ?> kg</strong>
            <small>Diverted from landfill</small>
        </article>
        <article class="stat-card">
            <span>Exchange impact</span>
            <strong>RM <?= number_format($exchangeImpactRm, 2) ?></strong>
            <small>Estimated circular value</small>
        </article>
    </section>

    <div class="dashboard-grid">
        <section class="panel">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                <h2 style="margin:0;">Recent inventory</h2>
                <a href="<?= route('business.inventory') ?>" style="font-size:12px;color:#176b40;font-weight:600;text-decoration:none;">View all inventory →</a>
            </div>

            <?php if ($recentInventory->isNotEmpty()): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentInventory as $item): ?>
                            <tr>
                                <td data-label="Item">
                                    <strong><?= e($item->name) ?></strong>
                                    <?php if ($item->sku): ?>
                                        <small style="display:block;color:#6f7b72;font-size:10px;">SKU: <?= e($item->sku) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Category"><?= e($item->category) ?></td>
                                <td data-label="Quantity"><?= (float) $item->quantity ?> <?= e($item->unit) ?></td>
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
                                    <button type="button" class="action-pill-btn" onclick="openStockAdjustModal(<?= $item->id ?>, '<?= e(addslashes($item->name)) ?>', '<?= e($item->unit) ?>', <?= (float)$item->quantity ?>)">
                                        Adjust
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state-card">
                    <p>No inventory items tracked yet. Start managing your materials and stock levels.</p>
                    <button type="button" class="btn-primary-inline" onclick="openAddInventoryModal()">+ Add first item</button>
                </div>
            <?php endif; ?>

            <div class="quick-links">
                <button type="button" class="quick-link-action" onclick="openAddInventoryModal()">+ Add inventory</button>
                <a href="<?= route('business.inventory') ?>">📦 Manage catalog</a>
                <a href="#marketplace">Browse marketplace</a>
            </div>
        </section>

        <section class="panel">
            <h2>Recent activity</h2>
            <?php if ($recentActivity->isNotEmpty()): ?>
                <div class="activity">
                    <?php foreach ($recentActivity as $tx): ?>
                        <div class="activity-row">
                            <span class="activity-icon"><?= $tx->transaction_type === 'stock_in' ? '↗' : ($tx->transaction_type === 'stock_out' ? '↘' : '▣') ?></span>
                            <span>
                                <b><?= e(ucwords(str_replace('_', ' ', $tx->transaction_type))) ?>: <?= e($tx->inventoryItem?->name ?? 'Inventory Item') ?></b>
                                <small><?= ($tx->quantity_change > 0 ? '+' : '') . (float)$tx->quantity_change ?> <?= e($tx->inventoryItem?->unit ?? '') ?> · <?= e($tx->created_at?->diffForHumans() ?? 'Just now') ?></small>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state-card" style="padding:24px 0;">
                    <p style="color:#6f7b72;font-size:12px;margin:0;">No recent activity yet. Log your first inventory movement to see history here.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<!-- Add Inventory Modal -->
<div class="modal-backdrop" id="add-inventory-modal" style="display:none;" onclick="if(event.target === this) closeAddInventoryModal()">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Add New Inventory Item</h3>
            <button type="button" class="modal-close-btn" onclick="closeAddInventoryModal()">✕</button>
        </div>
        <form method="POST" action="<?= route('business.inventory.store') ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label for="item_name">Item / Material name *</label>
                    <input id="item_name" name="name" type="text" required placeholder="e.g. Recycled Kraft Packaging Boxes">
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label for="item_sku">SKU / Code (optional)</label>
                        <input id="item_sku" name="sku" type="text" placeholder="e.g. PKG-BOX-01">
                    </div>
                    <div class="form-group">
                        <label for="item_category">Category *</label>
                        <select id="item_category" name="category" required>
                            <option value="">Select category</option>
                            <option value="Packaging">Packaging</option>
                            <option value="Textiles">Textiles & Fabrics</option>
                            <option value="Wood & Timber">Wood & Timber</option>
                            <option value="Plastics">Plastics & Polymers</option>
                            <option value="Metals">Metals & Scrap</option>
                            <option value="Glass & Containers">Glass & Containers</option>
                            <option value="Organic & Food Byproducts">Organic & Food Byproducts</option>
                            <option value="Paper & Cardboard">Paper & Cardboard</option>
                            <option value="Other">Other Materials</option>
                        </select>
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label for="item_quantity">Initial Quantity *</label>
                        <input id="item_quantity" name="quantity" type="number" step="0.01" min="0" required placeholder="e.g. 100">
                    </div>
                    <div class="form-group">
                        <label for="item_unit">Unit of Measure *</label>
                        <input id="item_unit" name="unit" type="text" required placeholder="e.g. pcs, kg, boxes, rolls">
                    </div>
                </div>
                <div class="form-group">
                    <label for="item_threshold">Low Stock Alert Threshold (optional)</label>
                    <input id="item_threshold" name="minimum_threshold" type="number" step="0.01" min="0" placeholder="e.g. 20 (alert when stock drops to or below this level)">
                </div>
                <div class="form-group">
                    <label for="item_notes">Storage notes / specifications (optional)</label>
                    <textarea id="item_notes" name="notes" rows="2" placeholder="e.g. Stored in Warehouse Bay B, dry section"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeAddInventoryModal()">Cancel</button>
                <button type="submit" class="btn-save">+ Save Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Stock Adjust Modal -->
<div class="modal-backdrop" id="stock-adjust-modal" style="display:none;" onclick="if(event.target === this) closeStockAdjustModal()">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Adjust Stock Level</h3>
            <button type="button" class="modal-close-btn" onclick="closeStockAdjustModal()">✕</button>
        </div>
        <form id="stock-adjust-form" method="POST" action="">
            <?= csrf_field() ?>
            <div class="modal-body">
                <p style="margin:0 0 12px;font-size:12px;color:#526158;">
                    Item: <strong id="adjust-item-name" style="color:#173e2c;"></strong><br>
                    Current Stock: <span id="adjust-current-stock" style="font-weight:600;color:#176b40;"></span>
                </p>
                <div class="form-group">
                    <label for="adjust_type">Adjustment Type *</label>
                    <select id="adjust_type" name="type" required>
                        <option value="stock_in">+ Stock In (Receive / replenish)</option>
                        <option value="stock_out">- Stock Out (Dispatch / consume / sell)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="adjust_quantity">Quantity *</label>
                    <input id="adjust_quantity" name="quantity" type="number" step="0.01" min="0.01" required placeholder="e.g. 25">
                </div>
                <div class="form-group">
                    <label for="adjust_notes">Reason / Notes (optional)</label>
                    <input id="adjust_notes" name="notes" type="text" placeholder="e.g. Purchase order #104, Production run batch A">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeStockAdjustModal()">Cancel</button>
                <button type="submit" class="btn-save">Apply Adjustment</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddInventoryModal() {
    document.getElementById('add-inventory-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeAddInventoryModal() {
    document.getElementById('add-inventory-modal').style.display = 'none';
    document.body.style.overflow = '';
}
function openStockAdjustModal(itemId, itemName, itemUnit, currentQty) {
    document.getElementById('adjust-item-name').textContent = itemName;
    document.getElementById('adjust-current-stock').textContent = currentQty + ' ' + itemUnit;
    document.getElementById('stock-adjust-form').action = '/business/inventory/' + itemId + '/adjust';
    document.getElementById('stock-adjust-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeStockAdjustModal() {
    document.getElementById('stock-adjust-modal').style.display = 'none';
    document.body.style.overflow = '';
}
</script>
<script src="/js/language-switcher.js" defer></script>
</body>
</html>
