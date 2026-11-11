<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wastage Monitoring & Recovery &middot; EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .waste-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .waste-kpi-card {
            background: #ffffff;
            border: 1px solid #d8e5d6;
            border-radius: 10px;
            padding: 18px 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .waste-kpi-title {
            font-size: 12px;
            font-weight: 700;
            color: #556c5e;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .waste-kpi-value {
            font-size: 26px;
            font-weight: 800;
            color: #123020;
            font-family: var(--font-display);
        }
        .waste-kpi-sub {
            font-size: 11.5px;
            color: #617769;
        }
        .status-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .status-in_storage { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .status-scheduled_pickup { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .status-sent_to_facility { background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; }
        .status-recycled { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .status-composted { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .btn-update-status {
            padding: 4px 8px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            background: #ffffff;
            color: #334155;
            font: 600 11px var(--font-main);
            cursor: pointer;
        }
        .btn-update-status:hover {
            background: #f1f5f9;
        }
    </style>
</head>
<body class="business-dashboard-page">
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <?php 
            $activeNav = 'waste';
            include resource_path('views/business/partials/sidebar.php'); 
        ?>

        <main class="dashboard-main-content">
            <header class="section-header" style="margin-bottom:20px;">
                <div class="section-header-title">
                    <h2>Wastage Monitoring &amp; Scrap Recovery</h2>
                    <p>Track, manage, and divert industrial scrap and non-reusable byproducts away from landfills toward licensed Malaysian circular recyclers.</p>
                </div>
                <div>
                    <button type="button" class="btn-create-listing" onclick="openAddWasteModal()" style="background:#15803d;color:#fff;border:none;padding:10px 18px;border-radius:8px;font:700 13px var(--font-main);cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                        <span>+ Log Scrap Entry</span>
                    </button>
                </div>
            </header>

            <?php if (session('status')): ?>
                <div style="background:#edf7ed;border:1.5px solid #a3cfbb;color:#1e4620;padding:12px 18px;border-radius:8px;font-size:13px;margin-bottom:20px;font-weight:600;">
                    ✓ <?= session('status') ?>
                </div>
            <?php endif; ?>

            <!-- KPI Metric Cards -->
            <div class="waste-kpi-grid">
                <div class="waste-kpi-card">
                    <span class="waste-kpi-title">Total Scrap Generated</span>
                    <span class="waste-kpi-value"><?= number_format((float) $totalScrapGeneratedKg, 1) ?> <small style="font-size:14px;font-weight:600;">kg</small></span>
                    <span class="waste-kpi-sub">Total non-reusable industrial cutoffs recorded</span>
                </div>
                <div class="waste-kpi-card" style="border-left:4px solid #16a34a;">
                    <span class="waste-kpi-title" style="color:#15803d;">Diverted to Recyclers</span>
                    <span class="waste-kpi-value" style="color:#15803d;"><?= number_format((float) $totalDivertedKg, 1) ?> <small style="font-size:14px;font-weight:600;">kg</small></span>
                    <span class="waste-kpi-sub">Processed through secondary recovery facilities</span>
                </div>
                <div class="waste-kpi-card" style="border-left:4px solid #eab308;">
                    <span class="waste-kpi-title" style="color:#a16207;">Pending In Storage / Pickup</span>
                    <span class="waste-kpi-value" style="color:#a16207;"><?= number_format((float) $totalPendingKg, 1) ?> <small style="font-size:14px;font-weight:600;">kg</small></span>
                    <span class="waste-kpi-sub">Awaiting scheduled circular logistics</span>
                </div>
                <div class="waste-kpi-card" style="border-left:4px solid #2563eb;">
                    <span class="waste-kpi-title" style="color:#1d4ed8;">Landfill Diversion Rate</span>
                    <span class="waste-kpi-value" style="color:#1d4ed8;"><?= $diversionRate ?>%</span>
                    <span class="waste-kpi-sub">Proportion of scrap successfully recovered</span>
                </div>
            </div>

            <!-- Filters -->
            <div style="background:#ffffff;border:1px solid #d8e5d6;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                <form method="GET" action="/business/waste-monitoring" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0;">
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search scrap or facility..." style="padding:8px 12px;border:1px solid #cadbc9;border-radius:6px;font-size:13px;min-width:200px;">
                    
                    <select name="status" style="padding:8px 12px;border:1px solid #cadbc9;border-radius:6px;font-size:13px;">
                        <option value="">All Statuses</option>
                        <option value="in_storage" <?= $selectedStatus === 'in_storage' ? 'selected' : '' ?>>In Storage</option>
                        <option value="scheduled_pickup" <?= $selectedStatus === 'scheduled_pickup' ? 'selected' : '' ?>>Scheduled Pickup</option>
                        <option value="sent_to_facility" <?= $selectedStatus === 'sent_to_facility' ? 'selected' : '' ?>>Sent to Facility</option>
                        <option value="recycled" <?= $selectedStatus === 'recycled' ? 'selected' : '' ?>>Recycled / Repurposed</option>
                        <option value="composted" <?= $selectedStatus === 'composted' ? 'selected' : '' ?>>Composted</option>
                    </select>

                    <select name="waste_type" style="padding:8px 12px;border:1px solid #cadbc9;border-radius:6px;font-size:13px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= $selectedCategory === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" style="padding:8px 14px;background:#18422d;color:#ffffff;border:none;border-radius:6px;font-weight:700;font-size:12.5px;cursor:pointer;">
                        Filter
                    </button>
                    <?php if ($search || $selectedStatus || $selectedCategory): ?>
                        <a href="/business/waste-monitoring" style="font-size:12px;color:#b91c1c;text-decoration:none;margin-left:4px;">Clear</a>
                    <?php endif; ?>
                </form>

                <div style="font-size:12.5px;color:#526b5a;font-weight:600;">
                    Showing <?= $records->total() ?> scrap logs
                </div>
            </div>

            <!-- Wastage Table -->
            <div style="background:#ffffff;border:1px solid #d8e5d6;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.03);">
                <?php if ($records->isNotEmpty()): ?>
                    <table class="inventory-table" style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f4f8f3;border-bottom:1.5px solid #d8e5d6;text-align:left;font-size:12px;color:#18422d;">
                                <th style="padding:14px 18px;">Material / Scrap Name</th>
                                <th style="padding:14px 18px;">Category</th>
                                <th style="padding:14px 18px;">Quantity</th>
                                <th style="padding:14px 18px;">Status</th>
                                <th style="padding:14px 18px;">Facility / Destination</th>
                                <th style="padding:14px 18px;">Date</th>
                                <th style="padding:14px 18px;text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): 
                                $statusLabels = [
                                    'in_storage' => 'In Storage',
                                    'scheduled_pickup' => 'Scheduled Pickup',
                                    'sent_to_facility' => 'Sent to Facility',
                                    'recycled' => 'Recycled / Diverted',
                                    'composted' => 'Composted',
                                ];
                                $label = $statusLabels[$record->status] ?? ucfirst(str_replace('_', ' ', $record->status));
                            ?>
                                <tr style="border-bottom:1px solid #e5ede3;font-size:13px;">
                                    <td style="padding:14px 18px;">
                                        <strong style="color:#113624;display:block;"><?= e($record->material_name) ?></strong>
                                        <?php if (!empty($record->ai_recommendation)): ?>
                                            <small style="color:#2563eb;font-size:11px;" title="<?= e($record->ai_recommendation) ?>">💡 AI Advised</small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:14px 18px;color:#526b5a;"><?= e($record->waste_type) ?></td>
                                    <td style="padding:14px 18px;font-weight:700;color:#123020;">
                                        <?= number_format((float) $record->quantity, 2) ?> <?= e($record->unit) ?>
                                    </td>
                                    <td style="padding:14px 18px;">
                                        <span class="status-badge-pill status-<?= e($record->status) ?>">
                                            ● <?= e($label) ?>
                                        </span>
                                    </td>
                                    <td style="padding:14px 18px;color:#2f4637;">
                                        <strong><?= e($record->facility_name ?: 'Internal Holding Facility') ?></strong>
                                        <?php if (!empty($record->facility_location)): ?>
                                            <span style="font-size:11.5px;color:#617769;display:block;"><?= e($record->facility_location) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:14px 18px;color:#617769;font-size:12px;">
                                        <?= $record->recorded_at ? $record->recorded_at->format('d M Y') : 'N/A' ?>
                                    </td>
                                    <td style="padding:14px 18px;text-align:right;">
                                        <button type="button" class="btn-update-status" onclick='openUpdateStatusModal(<?= $record->id ?>, "<?= e($record->status) ?>", "<?= e(addslashes($record->facility_name ?? '')) ?>", "<?= e(addslashes($record->facility_location ?? '')) ?>")'>
                                            Update Status
                                        </button>
                                        <form method="POST" action="<?= route('business.waste.destroy', $record) ?>" style="display:inline;margin-left:4px;" onsubmit="return confirm('Delete this scrap monitoring entry?');">
                                            <?= csrf_field() ?>
                                            <?= method_field('DELETE') ?>
                                            <button type="submit" style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:4px 7px;border-radius:4px;cursor:pointer;font-size:11px;" title="Delete">✕</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="padding:16px 20px;">
                        <?= $records->links()?->toHtml() ?>
                    </div>
                <?php else: ?>
                    <div style="padding:48px 24px;text-align:center;">
                        <div style="font-size:36px;margin-bottom:10px;">♻️</div>
                        <h4 style="margin:0 0 6px;color:#18422d;font-size:16px;">No Wastage Records Found</h4>
                        <p style="margin:0 0 16px;color:#556c5e;font-size:13px;max-width:420px;margin-left:auto;margin-right:auto;">
                            Keep track of all non-reusable cutoffs and factory scrap. You can log manual entries or route scrap directly from your Inventory using the AI Scrap Advisor.
                        </p>
                        <button type="button" class="btn-create-listing" onclick="openAddWasteModal()" style="background:#15803d;color:#fff;border:none;padding:9px 18px;border-radius:6px;font:700 13px var(--font-main);cursor:pointer;">
                            + Log First Scrap Entry
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add Wastage Modal -->
    <div class="modal-backdrop" id="add-waste-modal" style="display:none;" onclick="if(event.target===this)closeAddWasteModal()">
        <div class="modal-dialog" style="max-width:580px;">
            <div class="modal-header">
                <h3>Log Non-Reusable Scrap Entry</h3>
                <button type="button" class="modal-close-btn" onclick="closeAddWasteModal()">✕</button>
            </div>
            <form method="POST" action="<?= route('business.waste.store') ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <!-- Quick Autofill from Inventory -->
                    <?php if (isset($notReusableInventory) && $notReusableInventory->isNotEmpty()): ?>
                        <div class="form-group" style="background:#f4f9f4;border:1px solid #cce5cc;border-radius:8px;padding:12px;">
                            <label style="font-size:12px;font-weight:700;color:#1b5e20;margin-bottom:4px;display:block;">
                                📦 Select from Not-Reusable Inventory (Quick Autofill)
                            </label>
                            <select onchange="autofillWasteFromInventory(this)" style="font-size:13px;border-color:#a8cbb1;">
                                <option value="">-- Choose inventory scrap or type custom below --</option>
                                <?php foreach ($notReusableInventory as $inv): ?>
                                    <option value="<?= $inv->id ?>"
                                        data-name="<?= e($inv->name) ?>"
                                        data-cat="<?= e($inv->category) ?>"
                                        data-qty="<?= (float) $inv->quantity ?>"
                                        data-unit="<?= e($inv->unit) ?>"
                                    >
                                        <?= e($inv->name) ?> (<?= (float) $inv->quantity ?> <?= e($inv->unit) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="inventory_item_id" id="waste_inv_id" value="">
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="w_name">Scrap / Material Name *</label>
                        <input type="text" id="w_name" name="material_name" required placeholder="e.g. Broken PVC Pipes & Cutoffs">
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="w_type">Category *</label>
                            <select id="w_type" name="waste_type" required>
                                <option value="Plastics & rubber">Plastics & rubber</option>
                                <option value="Metal & fabrication">Metal & fabrication</option>
                                <option value="Furniture & wood products">Furniture & wood products</option>
                                <option value="Textiles & apparel">Textiles & apparel</option>
                                <option value="Electronics & electrical">Electronics & electrical</option>
                                <option value="Chemicals & lubricants">Chemicals & lubricants</option>
                                <option value="Food & beverage organic">Food & beverage organic</option>
                                <option value="Packaging & paper">Packaging & paper</option>
                                <option value="Other">Other industrial scrap</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="w_status">Current Status *</label>
                            <select id="w_status" name="status" required>
                                <option value="in_storage">In Storage</option>
                                <option value="scheduled_pickup">Scheduled Pickup</option>
                                <option value="sent_to_facility">Sent to Facility</option>
                                <option value="recycled">Recycled / Repurposed</option>
                                <option value="composted">Composted</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="w_qty">Quantity *</label>
                            <input type="number" step="0.01" min="0.01" id="w_qty" name="quantity" required placeholder="e.g. 150">
                        </div>
                        <div class="form-group">
                            <label for="w_unit">Unit of Measure *</label>
                            <input type="text" id="w_unit" name="unit" required placeholder="kg, tons, units" value="kg">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="w_disposal">Disposal / Circular Method *</label>
                        <input type="text" id="w_disposal" name="disposal_method" required placeholder="e.g. Mechanical Pelletizing, Smelting, Composting" value="Mechanical Recycling">
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="w_facility">Destination Facility</label>
                            <input type="text" id="w_facility" name="facility_name" placeholder="e.g. Daikkin Recycling Center">
                        </div>
                        <div class="form-group">
                            <label for="w_location">Facility Location</label>
                            <input type="text" id="w_location" name="facility_location" placeholder="e.g. Shah Alam, Selangor">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="w_date">Date Recorded *</label>
                        <input type="date" id="w_date" name="recorded_at" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="w_notes">Notes / Observations (Optional)</label>
                        <textarea id="w_notes" name="notes" rows="2" placeholder="Storage area, batch ID, or recycling partner contact..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeAddWasteModal()">Cancel</button>
                    <button type="submit" class="btn-save">+ Save Wastage Entry</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal-backdrop" id="update-status-modal" style="display:none;" onclick="if(event.target===this)closeUpdateStatusModal()">
        <div class="modal-dialog" style="max-width:500px;">
            <div class="modal-header">
                <h3>Update Scrap Status</h3>
                <button type="button" class="modal-close-btn" onclick="closeUpdateStatusModal()">✕</button>
            </div>
            <form id="update-status-form" method="POST" action="">
                <?= csrf_field() ?>
                <?= method_field('PUT') ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="up_status">New Status *</label>
                        <select id="up_status" name="status" required>
                            <option value="in_storage">In Storage</option>
                            <option value="scheduled_pickup">Scheduled Pickup</option>
                            <option value="sent_to_facility">Sent to Facility</option>
                            <option value="recycled">Recycled / Repurposed</option>
                            <option value="composted">Composted</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="up_facility">Facility Name</label>
                        <input type="text" id="up_facility" name="facility_name" placeholder="e.g. Shan Poornam Recovery Center">
                    </div>
                    <div class="form-group">
                        <label for="up_location">Facility Location</label>
                        <input type="text" id="up_location" name="facility_location" placeholder="e.g. Penang">
                    </div>
                    <div class="form-group">
                        <label for="up_notes">Status Notes</label>
                        <textarea id="up_notes" name="notes" rows="2" placeholder="e.g. Collected by logistics truck, consignment note #123"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeUpdateStatusModal()">Cancel</button>
                    <button type="submit" class="btn-save">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openAddWasteModal() {
        var m = document.getElementById('add-waste-modal');
        m.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeAddWasteModal() {
        document.getElementById('add-waste-modal').style.display = 'none';
        document.body.style.overflow = '';
    }
    function autofillWasteFromInventory(select) {
        var opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) return;
        document.getElementById('waste_inv_id').value = opt.value;
        if (opt.dataset.name) document.getElementById('w_name').value = opt.dataset.name;
        if (opt.dataset.cat) document.getElementById('w_type').value = opt.dataset.cat;
        if (opt.dataset.qty) document.getElementById('w_qty').value = opt.dataset.qty;
        if (opt.dataset.unit) document.getElementById('w_unit').value = opt.dataset.unit;
    }

    function openUpdateStatusModal(id, currentStatus, currentFacility, currentLocation) {
        document.getElementById('update-status-form').action = '/business/waste-monitoring/' + id;
        document.getElementById('up_status').value = currentStatus;
        document.getElementById('up_facility').value = currentFacility || '';
        document.getElementById('up_location').value = currentLocation || '';
        var m = document.getElementById('update-status-modal');
        m.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeUpdateStatusModal() {
        document.getElementById('update-status-modal').style.display = 'none';
        document.body.style.overflow = '';
    }
    </script>
</body>
</html>
