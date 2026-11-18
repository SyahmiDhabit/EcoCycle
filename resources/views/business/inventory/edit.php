<?php
/** @var \App\Models\User $business */
/** @var \App\Models\InventoryItem $item */
/** @var \Illuminate\Support\Collection<int, string> $categories */
/** @var \Illuminate\Support\ViewErrorBag $errors */

$errors = $errors ?? session('errors', new \Illuminate\Support\ViewErrorBag());
$activeNav = 'inventory';
$isReusable = ($item->reusability ?? 'reusable') === 'reusable';
$isPublished = $item->isPublishedToMarketplace();
$isPermanentlySoldOut = $item->isPermanentlySoldOut();
$currentListingType = in_array($item->listing_type, \App\Models\Item::ALLOWED_LISTING_TYPES, true) ? $item->listing_type : 'buy';
$currentPrice = old('price', $item->price !== null ? number_format((float) $item->price, 2, '.', '') : '');
$currentUnit = old('unit', $item->unit ?: 'kg');
$currentCondition = old('condition', in_array($item->condition, \App\Models\Item::ALLOWED_CONDITIONS, true) ? $item->condition : 'Used');
$currentReusability = old('reusability', $item->reusability ?? 'reusable');
$currentListingType = old('listing_type', $currentListingType);
$businessAddress = trim(implode(', ', array_filter([
    $business->registered_address ?? null,
    trim(implode(' ', array_filter([$business->postcode ?? null, $business->city ?? null]))),
    $business->state ?? null,
])));
$currentPickupAddress = old('pickup_address', $item->pickup_address ?: $businessAddress);
$imageSlots = $item->getImageSlots();
$aiAnalysis = is_array($item->ai_analysis) ? $item->ai_analysis : null;

$canPublishNow = ! $isPermanentlySoldOut
    && $isReusable
    && (float) $item->quantity > 0
    && (in_array($item->listing_type, ['free', 'lend'], true) || ($item->price !== null && (float) $item->price > 0));
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Update Item Details — EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .edit-page-wrap {
            max-width: 920px;
        }
        .edit-topbar-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
        }
        .edit-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0;
            margin-bottom: 20px;
        }
        .edit-card-header {
            padding: 14px 22px;
            background: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .edit-card-header h3 {
            margin: 0;
            font-size: 14.5px;
            font-weight: 700;
            color: #143825;
        }
        .edit-card-body {
            padding: 22px;
        }
        .edit-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .edit-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
        }
        .edit-field {
            margin-bottom: 16px;
        }
        .edit-field label {
            display: block;
            font-size: 12.5px;
            font-weight: 700;
            color: #18422d;
            margin-bottom: 6px;
        }
        .edit-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 0;
            background: #ffffff;
            color: #0f172a;
            font: 400 13.5px var(--font-main);
            box-sizing: border-box;
        }
        .edit-input:focus {
            border-color: #18422d;
            outline: none;
        }
        .edit-hint {
            display: block;
            margin-top: 5px;
            font-size: 11.5px;
            color: #64748b;
            line-height: 1.45;
        }
        .edit-choice-card {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 0;
            background: #ffffff;
            cursor: pointer;
        }
        .edit-choice-card.selected {
            border: 2px solid #18422d;
            background: #f0fdf4;
        }
        .edit-btn-primary {
            padding: 10px 22px;
            background: #143825;
            border: 1px solid #143825;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            border-radius: 0;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .edit-btn-primary:hover {
            background: #0d2b1b;
        }
        .edit-btn-primary:disabled {
            background: #94a3b8;
            border-color: #94a3b8;
            cursor: not-allowed;
        }
        .edit-btn-secondary {
            padding: 10px 18px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #334155;
            font-size: 13px;
            font-weight: 600;
            border-radius: 0;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .edit-btn-secondary:hover {
            background: #f1f5f9;
        }
        .edit-btn-utility {
            margin-top: 8px;
            padding: 6px 12px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #334155;
            font-size: 11.5px;
            font-weight: 600;
            border-radius: 0;
            cursor: pointer;
        }
        .edit-btn-utility:hover {
            background: #e2e8f0;
        }
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
        }
        .inv-slot-preview-area {
            height: 125px;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            cursor: pointer;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
            gap: 6px;
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
        }
        .inv-slot-action-btn {
            padding: 6px 8px;
            border: none;
            background: #ffffff;
            color: #334155;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 0;
        }
        .inv-slot-action-btn:hover { background: #f1f5f9; }
        .inv-slot-action-btn.btn-slot-remove { border-left: 1px solid #cbd5e1; color: #b91c1c; }
        .inv-slot-action-btn.btn-slot-upload-full { grid-column: 1 / -1; color: #18422d; font-weight: 700; }
        .publish-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .publish-summary-cell {
            padding: 12px 14px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
        }
        .publish-summary-cell small {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .publish-summary-cell strong {
            font-size: 13.5px;
            color: #0f172a;
        }
        @media (max-width: 768px) {
            .edit-grid-2,
            .edit-grid-3,
            .inv-slots-grid-3,
            .publish-summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="business-dashboard-page">
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <?php include resource_path('views/business/partials/sidebar.php'); ?>

        <main class="dashboard-main">
            <div class="edit-page-wrap">
                <div class="edit-topbar-row">
                    <div>
                        <a href="<?= route('business.inventory') ?>" class="edit-btn-secondary" style="padding:7px 14px;font-size:12px;margin-bottom:10px;">
                            &larr; Back to Inventory
                        </a>
                        <h1 style="margin:6px 0 4px;font-size:22px;color:#123321;">Update Item Details</h1>
                        <p style="margin:0;font-size:13px;color:#526658;">
                            Review AI suggestions, confirm material details, set listing type and price per unit, then save to your inventory.
                        </p>
                    </div>
                    <div>
                        <?php if ($isPermanentlySoldOut): ?>
                            <span style="display:inline-block;padding:6px 12px;background:#991b1b;color:#ffffff;font-size:11.5px;font-weight:700;text-transform:uppercase;">
                                Sold Out (Permanent)
                            </span>
                        <?php elseif ($isPublished): ?>
                            <span style="display:inline-block;padding:6px 12px;background:#143825;color:#ffffff;font-size:11.5px;font-weight:700;text-transform:uppercase;">
                                Published on Marketplace
                            </span>
                        <?php else: ?>
                            <span style="display:inline-block;padding:6px 12px;background:#f1f5f9;border:1px solid #cbd5e1;color:#334155;font-size:11.5px;font-weight:700;text-transform:uppercase;">
                                Saved in Inventory Only
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($isPermanentlySoldOut): ?>
                    <div role="alert" style="margin-bottom:16px;padding:14px 18px;background:#fff5f4;border:1px solid #fca5a5;border-left:4px solid #b91c1c;color:#7f1d1d;font-size:13px;line-height:1.5;">
                        <strong style="display:block;margin-bottom:4px;">This surplus submission is permanently Sold Out</strong>
                        All <?= (float) ($item->original_quantity ?? 0) ?> <?= e($item->unit) ?> from this submission have been sold or claimed. To preserve accurate purchase and sales history, this item cannot be restocked or reactivated.
                        <div style="margin-top:10px;">
                            <a href="<?= route('business.inventory', ['add' => 1]) ?>" class="edit-btn-primary" style="padding:7px 14px;font-size:12px;">
                                + Add New Item Submission
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($aiAnalysis && $isReusable): ?>
                    <div class="edit-card" style="border-left:4px solid #143825;background:#f7fbf7;">
                        <div class="edit-card-header" style="background:#eef6ed;">
                            <h3>Google Gemini AI Suggestions (Guidance Only)</h3>
                            <span style="font-size:11.5px;color:#143825;font-weight:600;">You control the final values below</span>
                        </div>
                        <div class="edit-card-body" style="padding:16px 20px;font-size:12.5px;color:#1e293b;line-height:1.55;">
                            <div class="edit-grid-3" style="margin-bottom:10px;">
                                <div>
                                    <span style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Suggested Category</span>
                                    <strong><?= e($aiAnalysis['suggested_category'] ?? $item->category) ?></strong>
                                </div>
                                <div>
                                    <span style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Suggested Listing Type</span>
                                    <strong><?= e($aiAnalysis['suggested_listing_type_label'] ?? 'Sell') ?></strong>
                                </div>
                                <div>
                                    <span style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Suggested Price Range</span>
                                    <strong><?= e($aiAnalysis['suggested_price_range'] ?? 'Set your own price per unit') ?></strong>
                                </div>
                            </div>
                            <?php if (! empty($aiAnalysis['reason'])): ?>
                                <div style="margin-top:6px;"><strong>Reason:</strong> <?= e($aiAnalysis['reason']) ?></div>
                            <?php endif; ?>
                            <?php if (! empty($aiAnalysis['reuse_applications'])): ?>
                                <div style="margin-top:6px;"><strong>Suggested Reuse Applications:</strong> <?= e(is_array($aiAnalysis['reuse_applications']) ? implode(' • ', $aiAnalysis['reuse_applications']) : $aiAnalysis['reuse_applications']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (session('status')): ?>
                    <div role="status" style="margin-bottom:16px;padding:12px 16px;background:#eaf3e8;border:1px solid #c7dbc5;border-left:3px solid #176b40;color:#176b40;font-size:13px;font-weight:600;">
                        <?= e(session('status')) ?>
                    </div>
                <?php endif; ?>

                <?php if ($errors->any()): ?>
                    <div role="alert" style="margin-bottom:16px;padding:12px 16px;background:#fff5f4;border:1px solid #fed7d2;border-left:3px solid #b91c1c;color:#b91c1c;font-size:13px;font-weight:600;">
                        <?= e($errors->first()) ?>
                    </div>
                <?php endif; ?>

                <datalist id="edit_categories_list">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <form method="POST" action="<?= route('business.inventory.update', $item) ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <?= method_field('PUT') ?>
                    <input type="hidden" name="redirect_to" value="edit">

                    <!-- Section 1: Material Photos -->
                    <div class="edit-card">
                        <div class="edit-card-header">
                            <h3>Material Photos (Maximum 3)</h3>
                            <span id="page_edit_img_counter" style="font-size:12px;font-weight:700;color:#18422d;">0 of 3 images</span>
                        </div>
                        <div class="edit-card-body">
                            <input type="file" id="page_slot_input_0" name="image_1" accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;" onchange="handlePageSlotFile(0, this)">
                            <input type="file" id="page_slot_input_1" name="image_2" accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;" onchange="handlePageSlotFile(1, this)">
                            <input type="file" id="page_slot_input_2" name="image_3" accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;" onchange="handlePageSlotFile(2, this)">
                            <input type="hidden" id="page_remove_image_0" name="remove_image_1" value="0">
                            <input type="hidden" id="page_remove_image_1" name="remove_image_2" value="0">
                            <input type="hidden" id="page_remove_image_2" name="remove_image_3" value="0">

                            <div class="inv-slots-grid-3" id="page_edit_slots_grid"></div>
                        </div>
                    </div>

                    <!-- Section 2: Material Details & Quantity -->
                    <div class="edit-card">
                        <div class="edit-card-header">
                            <h3>Material Information &amp; Available Quantity</h3>
                        </div>
                        <div class="edit-card-body">
                            <div class="edit-grid-2">
                                <div class="edit-field">
                                    <label for="page_name">Material Name <span style="color:#b91c1c;">*</span></label>
                                    <input id="page_name" name="name" type="text" required value="<?= e(old('name', $item->name)) ?>" class="edit-input">
                                </div>
                                <div class="edit-field">
                                    <label for="page_category">Material Category <span style="color:#b91c1c;">*</span></label>
                                    <input id="page_category" name="category" type="text" list="edit_categories_list" maxlength="100" required value="<?= e(old('category', $item->category)) ?>" class="edit-input">
                                </div>
                            </div>

                            <div class="edit-field">
                                <label for="page_description">Description</label>
                                <textarea id="page_description" name="description" rows="3" class="edit-input" style="resize:vertical;"><?= e(old('description', $item->description)) ?></textarea>
                            </div>

                            <div class="edit-grid-3">
                                <div class="edit-field" style="margin-bottom:0;">
                                    <label for="page_quantity">Available Quantity <span style="color:#b91c1c;">*</span></label>
                                    <input id="page_quantity" name="quantity" type="number" step="0.01" min="0" required value="<?= e(old('quantity', (float) $item->quantity)) ?>" class="edit-input" <?= $isPermanentlySoldOut ? 'readonly style="background:#f1f5f9;color:#64748b;"' : '' ?>>
                                    <?php if ($item->original_quantity !== null): ?>
                                        <span class="edit-hint">Initial submitted stock: <?= (float) $item->original_quantity ?> <?= e($item->unit) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="edit-field" style="margin-bottom:0;">
                                    <label for="page_unit">Unit of Measurement <span style="color:#b91c1c;">*</span></label>
                                    <select id="page_unit" name="unit" required class="edit-input">
                                        <?php foreach (\App\Models\Item::ALLOWED_UNITS as $unitVal => $unitLabel): ?>
                                            <option value="<?= e($unitVal) ?>" <?= $currentUnit === $unitVal ? 'selected' : '' ?>><?= e($unitLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="edit-field" style="margin-bottom:0;">
                                    <label for="page_condition">Material Condition <span style="color:#b91c1c;">*</span></label>
                                    <select id="page_condition" name="condition" required class="edit-input">
                                        <?php foreach (\App\Models\Item::ALLOWED_CONDITIONS as $condOpt): ?>
                                            <option value="<?= e($condOpt) ?>" <?= $currentCondition === $condOpt ? 'selected' : '' ?>><?= e($condOpt) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="edit-field" style="margin-top:16px;margin-bottom:0;">
                                <label for="page_notes">Internal Notes (Optional)</label>
                                <textarea id="page_notes" name="notes" rows="2" class="edit-input" style="resize:vertical;"><?= e(old('notes', $item->notes)) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Classification & Marketplace Preparation -->
                    <div class="edit-card">
                        <div class="edit-card-header">
                            <h3>Classification &amp; Marketplace Preparation</h3>
                        </div>
                        <div class="edit-card-body">
                            <div class="edit-field">
                                <label>Reusability Classification <span style="color:#b91c1c;">*</span></label>
                                <div class="edit-grid-2">
                                    <label class="edit-choice-card <?= $currentReusability === 'reusable' ? 'selected' : '' ?>" id="card_reus_reusable">
                                        <input type="radio" name="reusability" value="reusable" <?= $currentReusability === 'reusable' ? 'checked' : '' ?> onchange="updateEditClassificationUI()">
                                        <div>
                                            <strong style="display:block;font-size:13px;color:#143825;">Reusable</strong>
                                            <span style="font-size:12px;color:#64748b;">Eligible to prepare for Sell or Free Marketplace publishing.</span>
                                        </div>
                                    </label>
                                    <label class="edit-choice-card <?= $currentReusability === 'not_reusable' ? 'selected' : '' ?>" id="card_reus_not_reusable">
                                        <input type="radio" name="reusability" value="not_reusable" <?= $currentReusability === 'not_reusable' ? 'checked' : '' ?> onchange="updateEditClassificationUI()">
                                        <div>
                                            <strong style="display:block;font-size:13px;color:#334155;">Non-Reusable</strong>
                                            <span style="font-size:12px;color:#64748b;">Stays in Inventory only; handled through EcoCycle Helper.</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Reusable Marketplace Preparation Panel -->
                            <div id="reusable_marketplace_panel" style="<?= $currentReusability === 'reusable' ? '' : 'display:none;' ?>padding:18px;background:#f8faf8;border:1px solid #cbd5e1;margin-top:12px;">
                                <div class="edit-field">
                                    <label>Listing Type (Sell, Free, or Lend)</label>
                                    <div class="edit-grid-3">
                                        <label class="edit-choice-card <?= $currentListingType === 'buy' ? 'selected' : '' ?>" id="card_type_buy">
                                            <input type="radio" name="listing_type" value="buy" <?= $currentListingType === 'buy' ? 'checked' : '' ?> onchange="updateEditListingTypeUI()">
                                            <div>
                                                <strong style="display:block;font-size:13px;color:#143825;">Sell</strong>
                                                <span style="font-size:11.5px;color:#64748b;">Set Price per Unit (RM &gt; 0.00). Buyers pay via Stripe.</span>
                                            </div>
                                        </label>
                                        <label class="edit-choice-card <?= $currentListingType === 'free' ? 'selected' : '' ?>" id="card_type_free">
                                            <input type="radio" name="listing_type" value="free" <?= $currentListingType === 'free' ? 'checked' : '' ?> onchange="updateEditListingTypeUI()">
                                            <div>
                                                <strong style="display:block;font-size:13px;color:#143825;">Free</strong>
                                                <span style="font-size:11.5px;color:#64748b;">Offered at RM 0.00. No Stripe payment required.</span>
                                            </div>
                                        </label>
                                        <label class="edit-choice-card <?= $currentListingType === 'lend' ? 'selected' : '' ?>" id="card_type_lend">
                                            <input type="radio" name="listing_type" value="lend" <?= $currentListingType === 'lend' ? 'checked' : '' ?> onchange="updateEditListingTypeUI()">
                                            <div>
                                                <strong style="display:block;font-size:13px;color:#143825;">Lend</strong>
                                                <span style="font-size:11.5px;color:#64748b;">Temporary borrow (RM 0.00). Stock returns when returned.</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="edit-field" id="page_price_field_group" style="<?= $currentListingType === 'buy' ? '' : 'display:none;' ?>">
                                    <label for="page_price">Price per Unit (RM)</label>
                                    <input id="page_price" name="price" type="number" step="0.01" min="0.01" value="<?= e($currentListingType === 'buy' ? $currentPrice : '') ?>" placeholder="e.g. 25.00" class="edit-input" style="max-width:280px;">
                                    <span class="edit-hint">
                                        Enter the price per single unit (e.g. per kg or per pcs). Required (greater than RM 0.00) when publishing as Sell.
                                    </span>
                                </div>

                                <div id="page_free_price_notice" style="<?= in_array($currentListingType, ['free', 'lend'], true) ? '' : 'display:none;' ?>margin-bottom:16px;padding:10px 14px;background:#eef6ed;border:1px solid #b9dec0;color:#143825;font-size:12.5px;font-weight:600;">
                                    Price is automatically set to RM 0.00 for Free and Lend listings. No Stripe payment is required.
                                </div>

                                <div class="edit-field" style="margin-bottom:0;">
                                    <label for="page_pickup_address">Collection / Pickup Address</label>
                                    <input id="page_pickup_address" name="pickup_address" type="text" maxlength="255" value="<?= e($currentPickupAddress) ?>" placeholder="Enter warehouse or collection address" class="edit-input">
                                    <?php if ($businessAddress !== ''): ?>
                                        <button type="button" class="edit-btn-utility" onclick="document.getElementById('page_pickup_address').value = <?= json_encode($businessAddress) ?>">
                                            Use Business Registered Address
                                        </button>
                                    <?php endif; ?>
                                    <span class="edit-hint">
                                        Displayed on transaction receipts when this item is sold or claimed. Editing this will not alter past receipts.
                                    </span>
                                </div>
                            </div>

                            <!-- Non-Reusable Notice -->
                            <div id="non_reusable_panel" style="<?= $currentReusability === 'not_reusable' ? '' : 'display:none;' ?>padding:14px 16px;background:#f8fafc;border:1px solid #cbd5e1;margin-top:12px;font-size:13px;color:#475569;line-height:1.5;">
                                <strong style="display:block;color:#1e293b;margin-bottom:4px;">Non-Reusable Material (Inventory Only)</strong>
                                Non-reusable items cannot be published to the Marketplace. Save your changes and open EcoCycle Helper for recycling or disposal recommendations.
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
                        <a href="<?= route('business.inventory') ?>" class="edit-btn-secondary">Cancel</a>
                        <button type="submit" class="edit-btn-primary">Save Item</button>
                    </div>
                </form>

                <!-- Section 4: Separate Explicit Action — Publish to Marketplace -->
                <div class="edit-card">
                    <div class="edit-card-header">
                        <h3>Marketplace Publishing</h3>
                    </div>
                    <div class="edit-card-body">
                        <?php if ($isPermanentlySoldOut): ?>
                            <p style="margin:0 0 14px;font-size:13px;color:#991b1b;line-height:1.5;">
                                This item is permanently <strong>Sold Out</strong> and cannot be republished. Add a new inventory item submission for new surplus stock.
                            </p>
                            <a href="<?= route('business.inventory', ['add' => 1]) ?>" class="edit-btn-primary">
                                + Add New Item Submission
                            </a>
                        <?php elseif (! $isReusable): ?>
                            <p style="margin:0 0 14px;font-size:13px;color:#475569;line-height:1.5;">
                                This material is classified as <strong>Non-Reusable</strong> and cannot be published to the Marketplace. It remains in your Inventory for waste handling.
                            </p>
                            <a href="<?= route('business.helper', ['item_id' => $item->id]) ?>" class="edit-btn-secondary">
                                Open in EcoCycle Helper
                            </a>
                        <?php elseif ($isPublished): ?>
                            <p style="margin:0 0 14px;font-size:13px;color:#143825;line-height:1.5;">
                                This Reusable item is currently <strong>Published on the Marketplace</strong>. Saving changes above automatically keeps its live Marketplace details up to date.
                            </p>
                            <a href="<?= route('business.marketplace', ['q' => $item->name]) ?>" class="edit-btn-primary">
                                View on Marketplace
                            </a>
                        <?php else: ?>
                            <p style="margin:0 0 14px;font-size:13px;color:#475569;line-height:1.5;">
                                Publishing is a separate step. Review your saved Marketplace settings below, then click <strong>Publish to Marketplace</strong> when ready.
                            </p>

                            <div class="publish-summary-grid">
                                <div class="publish-summary-cell">
                                    <small>Listing Type</small>
                                    <strong><?= match ($item->listing_type) {
                                        'free' => 'Free',
                                        'lend' => 'Lend',
                                        default => 'Sell',
                                    } ?></strong>
                                </div>
                                <div class="publish-summary-cell">
                                    <small>Price per Unit (RM)</small>
                                    <strong>
                                        <?php if (in_array($item->listing_type, ['free', 'lend'], true)): ?>
                                            RM 0.00
                                        <?php elseif ($item->price !== null && (float) $item->price > 0): ?>
                                            RM <?= number_format((float) $item->price, 2) ?> / <?= e($item->unit) ?>
                                        <?php else: ?>
                                            <span style="color:#b91c1c;">Not set</span>
                                        <?php endif; ?>
                                    </strong>
                                </div>
                                <div class="publish-summary-cell">
                                    <small>Available Quantity</small>
                                    <strong><?= (float) $item->quantity ?> <?= e($item->unit) ?></strong>
                                </div>
                                <div class="publish-summary-cell">
                                    <small>Pickup Address</small>
                                    <strong><?= e($item->pickup_address ?: 'Business Address') ?></strong>
                                </div>
                            </div>

                            <?php if (! $canPublishNow): ?>
                                <div style="margin-bottom:14px;padding:10px 14px;background:#fffbeb;border:1px solid #f59e0b;color:#92400e;font-size:12.5px;">
                                    <?php if ((float) $item->quantity <= 0): ?>
                                        Available quantity must be greater than 0 before publishing.
                                    <?php else: ?>
                                        Save a valid <strong>Price (RM)</strong> greater than RM 0.00 (or select <strong>Free</strong>) in the form above before publishing to the Marketplace.
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="<?= route('business.inventory.publish', $item) ?>" style="margin:0;">
                                <?= csrf_field() ?>
                                <button type="submit" class="edit-btn-primary" <?= $canPublishNow ? '' : 'disabled' ?>>
                                    Publish to Marketplace
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    var pageSlotInitial = <?= json_encode($imageSlots) ?>;
    var pageSlotNewFiles = [null, null, null];
    var pageSlotNewPreviews = [null, null, null];
    var pageSlotRemoved = [false, false, false];

    function triggerPageSlotInput(idx) {
        var inp = document.getElementById('page_slot_input_' + idx);
        if (inp) inp.click();
    }

    function handlePageSlotFile(idx, input) {
        if (!input.files || input.files.length === 0) return;
        var file = input.files[0];
        if (!file.type || !file.type.match('image.*')) {
            alert('Please upload a valid image file (JPG, PNG, or WEBP).');
            input.value = '';
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('Image exceeds the 5MB file size limit.');
            input.value = '';
            return;
        }
        pageSlotNewFiles[idx] = file;
        pageSlotRemoved[idx] = false;
        document.getElementById('page_remove_image_' + idx).value = '0';

        var reader = new FileReader();
        reader.onload = function(e) {
            pageSlotNewPreviews[idx] = e.target.result;
            renderPageEditSlots();
        };
        reader.readAsDataURL(file);
    }

    function clearPageSlot(idx) {
        pageSlotNewFiles[idx] = null;
        pageSlotNewPreviews[idx] = null;
        pageSlotRemoved[idx] = true;
        document.getElementById('page_slot_input_' + idx).value = '';
        document.getElementById('page_remove_image_' + idx).value = '1';
        renderPageEditSlots();
    }

    function renderPageEditSlots() {
        var grid = document.getElementById('page_edit_slots_grid');
        var counter = document.getElementById('page_edit_img_counter');
        if (!grid) return;

        grid.innerHTML = '';
        var labels = ['Image 1 (Cover)', 'Image 2', 'Image 3'];
        var activeCount = 0;

        for (var i = 0; i < 3; i++) {
            (function(slotIndex) {
                var previewUrl = null;
                var statusLabel = 'Empty';

                if (pageSlotNewFiles[slotIndex] && pageSlotNewPreviews[slotIndex]) {
                    previewUrl = pageSlotNewPreviews[slotIndex];
                    statusLabel = 'New';
                    activeCount++;
                } else if (!pageSlotRemoved[slotIndex] && pageSlotInitial[slotIndex]) {
                    previewUrl = pageSlotInitial[slotIndex];
                    statusLabel = 'Saved';
                    activeCount++;
                }

                var wrapper = document.createElement('div');
                wrapper.className = 'inv-slot-wrapper';

                var header = document.createElement('div');
                header.className = 'inv-slot-header';
                header.innerHTML = '<span>' + labels[slotIndex] + '</span><span style="color:' + (previewUrl ? '#18422d' : '#94a3b8') + ';">' + statusLabel + '</span>';
                wrapper.appendChild(header);

                var preview = document.createElement('div');
                preview.className = 'inv-slot-preview-area';
                preview.onclick = function() { triggerPageSlotInput(slotIndex); };
                if (previewUrl) {
                    preview.innerHTML = '<img src="' + previewUrl + '" alt="' + labels[slotIndex] + '">';
                } else {
                    preview.innerHTML = '<span>Select Image</span>';
                }
                wrapper.appendChild(preview);

                var actions = document.createElement('div');
                actions.className = 'inv-slot-actions';
                if (previewUrl) {
                    var btnReplace = document.createElement('button');
                    btnReplace.type = 'button';
                    btnReplace.className = 'inv-slot-action-btn';
                    btnReplace.textContent = 'Replace';
                    btnReplace.onclick = function() { triggerPageSlotInput(slotIndex); };

                    var btnRemove = document.createElement('button');
                    btnRemove.type = 'button';
                    btnRemove.className = 'inv-slot-action-btn btn-slot-remove';
                    btnRemove.textContent = 'Remove';
                    btnRemove.onclick = function() { clearPageSlot(slotIndex); };

                    actions.appendChild(btnReplace);
                    actions.appendChild(btnRemove);
                } else {
                    var btnUpload = document.createElement('button');
                    btnUpload.type = 'button';
                    btnUpload.className = 'inv-slot-action-btn btn-slot-upload-full';
                    btnUpload.textContent = 'Upload';
                    btnUpload.onclick = function() { triggerPageSlotInput(slotIndex); };
                    actions.appendChild(btnUpload);
                }
                wrapper.appendChild(actions);
                grid.appendChild(wrapper);
            })(i);
        }

        if (counter) {
            counter.textContent = activeCount + ' of 3 images';
        }
    }

    function updateEditClassificationUI() {
        var checked = document.querySelector('input[name="reusability"]:checked');
        var val = checked ? checked.value : 'reusable';
        var panelReus = document.getElementById('reusable_marketplace_panel');
        var panelNonReus = document.getElementById('non_reusable_panel');
        var cardR = document.getElementById('card_reus_reusable');
        var cardN = document.getElementById('card_reus_not_reusable');

        if (val === 'reusable') {
            if (panelReus) panelReus.style.display = 'block';
            if (panelNonReus) panelNonReus.style.display = 'none';
            if (cardR) cardR.classList.add('selected');
            if (cardN) cardN.classList.remove('selected');
        } else {
            if (panelReus) panelReus.style.display = 'none';
            if (panelNonReus) panelNonReus.style.display = 'block';
            if (cardR) cardR.classList.remove('selected');
            if (cardN) cardN.classList.add('selected');
        }
    }

    function updateEditListingTypeUI() {
        var checked = document.querySelector('input[name="listing_type"]:checked');
        var val = checked ? checked.value : 'buy';
        var priceGroup = document.getElementById('page_price_field_group');
        var freeNotice = document.getElementById('page_free_price_notice');
        var priceInput = document.getElementById('page_price');
        var cardBuy = document.getElementById('card_type_buy');
        var cardFree = document.getElementById('card_type_free');
        var cardLend = document.getElementById('card_type_lend');

        if (cardBuy) cardBuy.classList.toggle('selected', val === 'buy');
        if (cardFree) cardFree.classList.toggle('selected', val === 'free');
        if (cardLend) cardLend.classList.toggle('selected', val === 'lend');

        if (val === 'free' || val === 'lend') {
            if (priceGroup) priceGroup.style.display = 'none';
            if (freeNotice) freeNotice.style.display = 'block';
            if (priceInput) priceInput.value = '';
        } else {
            if (priceGroup) priceGroup.style.display = 'block';
            if (freeNotice) freeNotice.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderPageEditSlots();
        updateEditClassificationUI();
        updateEditListingTypeUI();
    });
    </script>
</body>
</html>
