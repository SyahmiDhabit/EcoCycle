<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wastage Monitoring &amp; Recovery &middot; EcoCycle</title>
    <link rel="icon" type="image/svg+xml" href="/images/figma/ecocycle-leaf.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .waste-workspace {
            padding-inline: max(clamp(20px, 4vw, 48px), calc((100% - 1180px) / 2));
        }
        .waste-workspace .page-header {
            align-items: center;
            flex-wrap: wrap;
            gap: 12px 20px;
            padding: 0;
            border: 0;
        }
        .waste-workspace .page-header h1 {
            color: #14532d;
            font-size: clamp(26px, 3vw, 40px);
        }
        .business-dashboard-page.waste-page :is(.waste-workspace, #add-waste-modal, #update-status-modal) button {
            border-radius: 6px !important;
        }
        .waste-workspace :is(.btn-search, .btn-save) {
            background: #176b40;
            border-color: #176b40;
        }
        .waste-workspace :is(.btn-search, .btn-save):hover {
            background: #145331;
        }
        .waste-workspace .directory-table th {
            background: #eef5eb;
            color: #14532d;
        }
        .waste-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .waste-kpi-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 0;
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .waste-kpi-card {
            box-shadow: 0 5px 12px rgba(20, 83, 45, .05);
        }
        .waste-kpi-value + .waste-kpi-value {
            border-top: 1px solid var(--card-border);
            padding-top: 4px;
        }
        .waste-kpi-value small {
            display: inline-block;
            padding: 2px 7px;
            background: #eef5eb;
            color: #14532d;
            font: 700 11px var(--font-main);
            vertical-align: middle;
        }
        .waste-kpi-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .waste-kpi-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-primary);
            font-family: var(--font-display);
        }
        .waste-unit-chart { display: flex; flex-wrap: wrap; gap: 12px; margin: 8px 0; padding: 0; list-style: none; }
        .waste-chart-column { flex: 1 0 72px; display: grid; justify-items: center; gap: 4px; font-size: 12px; }
        .waste-chart-column span { width: 24px; height: 60px; background: #176b40; }
        .waste-donut-row { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-top: 6px; }
        .waste-donut {
            flex: 0 0 64px;
            height: 64px;
            background: conic-gradient(var(--waste-donut-colors));
            mask-image: radial-gradient(circle closest-side, transparent 68%, #000 70% 97%, transparent 100%);
        }
        .waste-donut-values { font-size: 12px; margin: 0; padding: 0; list-style: none; }
        .waste-donut-values li::before {
            content: '';
            display: inline-block;
            width: 9px;
            height: 9px;
            margin-right: 5px;
            background: #176b40;
        }
        .waste-donut-values li + li::before { background: #ac7a24; }
        .waste-row-actions button:disabled { opacity: .55; cursor: not-allowed; }
        .waste-kpi-sub {
            font-size: 11.5px;
            color: var(--text-muted);
        }
        .status-badge-pill {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 0;
            font-size: 11px;
            font-weight: 700;
        }
        .status-in_storage { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .status-scheduled_pickup { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .status-sent_to_facility { background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; }
        .status-recycled { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .status-composted { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .btn-update-status {
            padding: 5px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 0;
            background: #ffffff;
            color: #334155;
            font: 600 11.5px var(--font-main);
            cursor: pointer;
        }
        .btn-update-status:hover {
            background: #f1f5f9;
        }
        .waste-row-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
            white-space: nowrap;
        }
        .waste-row-actions form { margin: 0; }
        .waste-row-actions .btn-update-status {
            display: inline-flex;
            align-items: center;
            border-radius: 6px;
            text-decoration: none;
            line-height: 1.4;
        }
        .waste-row-actions .waste-delete {
            background: #fff5f5;
            border-color: #fecaca;
            color: #991b1b;
        }
        #add-waste-modal {
            --scrap-modal-inset: clamp(16px, 3vw, 28px);
        }
        #add-waste-modal .modal-dialog {
            max-width: 680px;
            border-color: var(--card-border) !important;
        }
        #add-waste-modal :is(.modal-header, .modal-body, .modal-footer) {
            padding-inline: var(--scrap-modal-inset) !important;
        }
        #add-waste-modal .modal-header,
        #add-waste-modal .modal-footer {
            background: #eef5eb !important;
        }
        #add-waste-modal .modal-header {
            gap: 12px;
        }
        #add-waste-modal .modal-header h3 {
            color: #14532d !important;
            font-family: var(--font-display);
            font-size: 19px !important;
        }
        #add-waste-modal .waste-inventory-autofill {
            padding: 12px 14px;
            background: #eef5eb;
            border-inline-start: 3px solid #176b40;
        }
        #add-waste-modal .modal-body :is(input, select, textarea) {
            border-radius: 6px;
        }
        #add-waste-modal .modal-body input[type="date"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--input-border);
            background: #fff;
            color: #123321;
            font: 13.5px var(--font-main);
            box-sizing: border-box;
        }
        #add-waste-modal .modal-footer {
            justify-content: space-between !important;
            flex-wrap: wrap;
        }
        #add-waste-modal .modal-footer .btn-save {
            background: #176b40;
            border-color: #176b40;
        }
        #add-waste-modal .modal-footer .btn-save:hover {
            background: #145331;
            border-color: #145331;
        }
        #add-waste-modal :is(button, input, select, textarea):focus-visible {
            outline: 2px solid #176b40;
            outline-offset: 2px;
        }
    </style>
</head>
<body class="business-dashboard-page waste-page">
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <?php 
            $activeNav = 'waste';
            include resource_path('views/business/partials/sidebar.php'); 
        ?>

        <main class="dashboard-main-content waste-workspace">
            <header class="page-header">
                <div class="section-header-title">
                    <h1>Wastage Monitoring &amp; Scrap Recovery</h1>
                    <p>Track, manage, and divert industrial scrap and non-reusable byproducts away from landfills toward licensed Malaysian circular recyclers.</p>
                </div>
                <div>
                    <button type="button" class="btn-search" onclick="openAddWasteModal()">
                        <span>Log Scrap Entry</span>
                    </button>
                </div>
            </header>

            <?php if (session('status')): ?>
                <div style="background:#eef6ed;border:1px solid #b9dec0;border-left:3px solid #176b40;color:#143825;padding:12px 18px;border-radius:0;font-size:13px;margin-bottom:20px;font-weight:600;">
                    <?= session('status') ?>
                </div>
            <?php endif; ?>

            <?php if ($errors->has('facility_payment_rm')): ?>
                <p role="alert"><?= e($errors->first('facility_payment_rm')) ?></p>
            <?php endif; ?>

            <!-- KPI Metric Cards -->
            <?php $formatWasteQuantity = static fn (float $quantity): string => rtrim(rtrim(number_format($quantity, 5), '0'), '.'); ?>
            <div class="waste-kpi-grid">
                <div class="waste-kpi-card">
                    <span class="waste-kpi-title">Scrap Recorded by Unit</span>
                    <?php if ($totalsByUnit === []): ?>
                        <span class="waste-kpi-sub">No waste recorded</span>
                    <?php else: ?>
                        <ul class="waste-unit-chart">
                            <?php foreach ($totalsByUnit as $unit => $totals): ?>
                                <li class="waste-chart-column">
                                    <strong><?= $formatWasteQuantity($totals['total']) ?> <?= e($unit) ?></strong>
                                    <?php if ($totals['total'] > 0): ?><span aria-hidden="true"></span><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <span class="waste-kpi-sub">Each unit has its own scale.</span>
                    <?php endif; ?>
                </div>
                <div class="waste-kpi-card" style="border-left:3px solid #176b40;">
                    <span class="waste-kpi-title" style="color:#15803d;">Scrap Processing Outcomes</span>
                    <?php foreach ($totalsByUnit as $unit => $totals): ?>
                        <?php
                        $processed = $totals['recycled'] + $totals['composted'];
                        $recycledShare = $processed > 0 ? 100 * $totals['recycled'] / $processed : 0;
                        $donutColors = $processed > 0 ? "#176b40 {$recycledShare}%, #ac7a24 0" : '#e4ede2, #e4ede2';
                        ?>
                        <div class="waste-donut-row">
                            <div class="waste-donut" aria-hidden="true" style="--waste-donut-colors:<?= e($donutColors) ?>;"></div>
                            <ul class="waste-donut-values">
                                <li>Recycled / Repurposed · <?= $formatWasteQuantity($totals['recycled']) ?> <?= e($unit) ?></li>
                                <li>Composted · <?= $formatWasteQuantity($totals['composted']) ?> <?= e($unit) ?></li>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($totalsByUnit === []): ?>
                        <span class="waste-kpi-sub">No waste recorded</span>
                    <?php endif; ?>
                </div>
                <div class="waste-kpi-card" style="border-left:3px solid #1d4ed8;">
                    <span class="waste-kpi-title" style="color:#1d4ed8;">Share Received or Processed</span>
                    <?php foreach ($totalsByUnit as $unit => $totals): ?>
                        <span class="waste-kpi-value" style="color:#1d4ed8;"><?= $totals['total'] > 0 ? round(100 * $totals['diverted'] / $totals['total'], 1).'%' : '—' ?> <small><?= e($unit) ?> basis</small></span>
                    <?php endforeach; ?>
                    <?php if ($totalsByUnit === []): ?>
                        <span class="waste-kpi-value" style="color:#1d4ed8;">—</span>
                    <?php endif; ?>
                    <span class="waste-kpi-sub">Percentage of recorded scrap received or processed, calculated for each unit.</span>
                </div>
            </div>

            <!-- Filters -->
            <div style="background:#ffffff;border:1px solid var(--card-border);border-radius:0;padding:16px 20px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                <form method="GET" action="/business/waste-monitoring" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0;">
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search scrap or facility..." style="height:38px;padding:0 12px;border:1px solid var(--input-border);border-radius:0;font-size:13px;min-width:200px;">
                    
                    <select name="status" style="height:38px;padding:0 12px;border:1px solid var(--input-border);border-radius:0;font-size:13px;">
                        <option value="">All Statuses</option>
                        <option value="in_storage" <?= $selectedStatus === 'in_storage' ? 'selected' : '' ?>>In Storage</option>
                        <option value="scheduled_pickup" <?= $selectedStatus === 'scheduled_pickup' ? 'selected' : '' ?>>Pickup Scheduled</option>
                        <option value="sent_to_facility" <?= $selectedStatus === 'sent_to_facility' ? 'selected' : '' ?>>Received by Facility</option>
                        <option value="recycled" <?= $selectedStatus === 'recycled' ? 'selected' : '' ?>>Recycled / Repurposed</option>
                        <option value="composted" <?= $selectedStatus === 'composted' ? 'selected' : '' ?>>Composted</option>
                    </select>

                    <select name="waste_type" style="height:38px;padding:0 12px;border:1px solid var(--input-border);border-radius:0;font-size:13px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= $selectedCategory === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn-search" style="height:38px;padding:0 16px;">
                        Filter
                    </button>
                    <?php if ($search || $selectedStatus || $selectedCategory): ?>
                        <a href="/business/waste-monitoring" style="font-size:12.5px;color:#b91c1c;text-decoration:none;margin-left:4px;font-weight:600;">Clear</a>
                    <?php endif; ?>
                </form>

                <div style="font-size:12.5px;color:var(--text-secondary);font-weight:600;">
                    Showing <?= $records->total() ?> scrap logs
                </div>
            </div>

            <!-- Wastage Table -->
            <div style="background:#ffffff;border:1px solid var(--card-border);border-radius:0;overflow-x:auto;">
                <?php if ($records->isNotEmpty()): ?>
                    <table class="directory-table" style="margin-bottom:0;">
                        <thead>
                            <tr>
                                <th>Material / Scrap Name</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Facility / Destination</th>
                                <th>Date</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): 
                                $statusLabels = [
                                    'in_storage' => 'In Storage',
                                    'scheduled_pickup' => 'Pickup Scheduled',
                                    'sent_to_facility' => 'Received by Facility',
                                    'recycled' => 'Recycled / Repurposed',
                                    'composted' => 'Composted',
                                ];
                                $label = $statusLabels[$record->status] ?? ucfirst(str_replace('_', ' ', $record->status));
                            ?>
                                <tr>
                                    <td>
                                        <strong style="color:#113624;display:block;"><?= e($record->material_name) ?></strong>
                                    </td>
                                    <td style="color:var(--text-secondary);"><?= e($record->waste_type) ?></td>
                                    <td style="font-weight:700;color:#123020;">
                                        <?= number_format((float) $record->quantity, 2) ?> <?= e($record->unit) ?>
                                    </td>
                                    <td>
                                        <span class="status-badge-pill status-<?= e($record->status) ?>">
                                            <?= e($label) ?>
                                        </span>
                                    </td>
                                    <td style="color:#2f4637;">
                                        <strong><?= e($record->facility_name ?: 'Internal Holding Facility') ?></strong>
                                        <?php if (!empty($record->facility_location)): ?>
                                            <span style="font-size:11.5px;color:var(--text-muted);display:block;"><?= e($record->facility_location) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:var(--text-muted);font-size:12px;">
                                        <?= $record->recorded_at ? $record->recorded_at->format('d M Y') : 'N/A' ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <div class="waste-row-actions">
                                            <a class="btn-update-status" href="<?= e(route('business.waste.report', $record)) ?>">View Report</a>
                                            <button type="button" class="btn-update-status" <?= $record->isFinalized() ? 'disabled' : '' ?> onclick='openUpdateStatusModal(<?= $record->id ?>, "<?= e($record->status) ?>", "<?= e(addslashes($record->facility_name ?? '')) ?>", "<?= e(addslashes($record->facility_location ?? '')) ?>", "<?= e($record->facility_payment_rm ?? '') ?>")'>
                                                Update Status
                                            </button>
                                            <?php if (! $record->isFinalized()): ?>
                                            <form method="POST" action="<?= route('business.waste.destroy', $record) ?>" onsubmit="return confirm('Delete this scrap monitoring entry?');">
                                                <?= csrf_field() ?>
                                                <?= method_field('DELETE') ?>
                                                <button type="submit" class="btn-update-status waste-delete" title="Delete">Delete</button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
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
                        <h4 style="margin:0 0 6px;color:#143825;font-size:16px;">No Wastage Records Found</h4>
                        <p style="margin:0 0 18px;color:var(--text-secondary);font-size:13px;max-width:440px;margin-left:auto;margin-right:auto;">
                            Keep track of all non-reusable cutoffs and factory scrap. You can log manual entries or route scrap directly from your Inventory using EcoCycle Helper.
                        </p>
                        <button type="button" class="btn-search" onclick="openAddWasteModal()">
                            + Log First Scrap Entry
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add Wastage Modal -->
    <div class="modal-backdrop" id="add-waste-modal" style="display:none;" onclick="if(event.target===this)closeAddWasteModal()">
        <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="add-waste-heading">
            <div class="modal-header">
                <h3 id="add-waste-heading">Log Non-Reusable Scrap Entry</h3>
                <button type="button" class="modal-close-btn" onclick="closeAddWasteModal()" aria-label="Close">&times;</button>
            </div>
            <form method="POST" action="<?= route('business.waste.store') ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <!-- Quick Autofill from Inventory -->
                    <?php if (isset($notReusableInventory) && $notReusableInventory->isNotEmpty()): ?>
                        <div class="form-group waste-inventory-autofill">
                            <label for="w_inventory">
                                Select from Non-Reusable Inventory (Quick Autofill)
                            </label>
                            <select id="w_inventory" onchange="autofillWasteFromInventory(this)">
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
                        <input type="text" id="w_name" name="material_name" required placeholder="e.g. Broken PVC Pipes &amp; Cutoffs">
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="w_type">Category *</label>
                            <select id="w_type" name="waste_type" required>
                                <option value="Plastics & rubber">Plastics &amp; rubber</option>
                                <option value="Metal & fabrication">Metal &amp; fabrication</option>
                                <option value="Furniture & wood products">Furniture &amp; wood products</option>
                                <option value="Textiles & apparel">Textiles &amp; apparel</option>
                                <option value="Electronics & electrical">Electronics &amp; electrical</option>
                                <option value="Chemicals & lubricants">Chemicals &amp; lubricants</option>
                                <option value="Food & beverage organic">Food &amp; beverage organic</option>
                                <option value="Packaging & paper">Packaging &amp; paper</option>
                                <option value="Other">Other industrial scrap</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="w_status">Current Status *</label>
                            <select id="w_status" name="status" onchange="updateFacilityPaymentRequirement(this)" required>
                                <option value="in_storage">In Storage</option>
                                <option value="scheduled_pickup">Pickup Scheduled</option>
                                <option value="sent_to_facility">Received by Facility</option>
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
                        <label for="w_facility_payment">Facility Payment (RM)</label>
                        <input type="number" id="w_facility_payment" name="facility_payment_rm" step="0.01" min="0" max="99999999.99" aria-describedby="w_facility_payment_help">
                        <small id="w_facility_payment_help">Enter the total paid by the facility, or 0 if the facility paid nothing.</small>
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
                <button type="button" class="modal-close-btn" onclick="closeUpdateStatusModal()" aria-label="Close">&times;</button>
            </div>
            <form id="update-status-form" method="POST" action="">
                <?= csrf_field() ?>
                <?= method_field('PUT') ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="up_status"><span>New Status <span style="color:#b91c1c;" aria-hidden="true">*</span></span></label>
                        <select id="up_status" name="status" onchange="updateFacilityPaymentRequirement(this)" required>
                            <option value="in_storage">In Storage</option>
                            <option value="scheduled_pickup">Pickup Scheduled</option>
                            <option value="sent_to_facility">Received by Facility</option>
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
                        <label for="up_facility_payment_rm">Facility Payment (RM)</label>
                        <input type="number" id="up_facility_payment_rm" name="facility_payment_rm" step="0.01"
                               min="0" max="99999999.99" aria-describedby="up_facility_payment_rm_help">
                        <small id="up_facility_payment_rm_help">Enter the total the facility paid for this batch. Required for Received by Facility, Recycled / Repurposed, or Composted. Enter 0 if the facility paid nothing.</small>
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

    function openUpdateStatusModal(id, currentStatus, currentFacility, currentLocation, currentFacilityPayment) {
        document.getElementById('update-status-form').action = '/business/waste-monitoring/' + id;
        document.getElementById('up_status').value = currentStatus;
        document.getElementById('up_facility').value = currentFacility || '';
        document.getElementById('up_location').value = currentLocation || '';
        document.getElementById('up_facility_payment_rm').value = currentFacilityPayment;
        updateFacilityPaymentRequirement(document.getElementById('up_status'));
        var m = document.getElementById('update-status-modal');
        m.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function updateFacilityPaymentRequirement(statusSelect) {
        statusSelect.form.elements.facility_payment_rm.required =
            ['sent_to_facility', 'recycled', 'composted'].includes(statusSelect.value);
    }
    function closeUpdateStatusModal() {
        document.getElementById('update-status-modal').style.display = 'none';
        document.body.style.overflow = '';
    }
    </script>
</body>
</html>
