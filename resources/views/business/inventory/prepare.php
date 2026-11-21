<?php
/** @var \App\Models\User $business */
/** @var array<string, mixed> $draft */
/** @var \Illuminate\Support\Collection<int, string> $categories */
/** @var \Illuminate\Support\ViewErrorBag $errors */

$errors = $errors ?? session('errors', new \Illuminate\Support\ViewErrorBag());
$activeNav = 'inventory';

$analysis = is_array($draft['analysis'] ?? null) ? $draft['analysis'] : [];
$suggestedReusability = ($analysis['suggested_reusability'] ?? 'reusable') === 'not_reusable' ? 'not_reusable' : 'reusable';
$suggestedCategory = trim((string) ($analysis['suggested_category'] ?? ''));
$suggestedCondition = in_array($analysis['suggested_condition'] ?? '', \App\Models\Item::ALLOWED_CONDITIONS, true)
    ? (string) $analysis['suggested_condition']
    : (string) ($draft['condition'] ?? 'Used');
$suggestedListingType = in_array($analysis['suggested_listing_type'] ?? '', \App\Models\Item::ALLOWED_LISTING_TYPES, true)
    ? (string) $analysis['suggested_listing_type']
    : 'buy';
$suggestedListingLabel = (string) ($analysis['suggested_listing_type_label'] ?? match ($suggestedListingType) {
    'free' => 'Free',
    'lend' => 'Lend',
    default => 'Sell',
});
$suggestedPriceRange = trim((string) ($analysis['suggested_price_range'] ?? ''));
$reason = trim((string) ($analysis['reason'] ?? 'Material classified based on the uploaded photos, condition, and item details.'));
$nextSteps = is_array($analysis['recommended_actions'] ?? null) ? $analysis['recommended_actions'] : [];
$reuseApps = is_array($analysis['reuse_applications'] ?? null) ? $analysis['reuse_applications'] : [];
$otherRecs = is_array($analysis['other_recommendations'] ?? null) ? $analysis['other_recommendations'] : [];
$needsCategoryReview = ! empty($analysis['needs_category_review']) || $suggestedCategory === '';

$businessAddress = trim(implode(', ', array_filter([
    $business->registered_address ?? null,
    trim(implode(' ', array_filter([$business->postcode ?? null, $business->city ?? null]))),
    $business->state ?? null,
])));

$valName = old('name', (string) ($draft['name'] ?? ''));
$valCategory = old('category', $suggestedCategory);
$valDescription = old('description', (string) ($draft['description'] ?? ''));
$valQuantity = old('quantity', (string) ($draft['quantity'] ?? ''));
$valUnit = old('unit', (string) ($draft['unit'] ?? 'kg'));
$valCondition = old('condition', in_array($draft['condition'] ?? '', \App\Models\Item::ALLOWED_CONDITIONS, true) ? (string) $draft['condition'] : $suggestedCondition);
$valReusability = old('reusability', $suggestedReusability);
$valListingType = old('listing_type', $suggestedListingType);
$valPrice = old('price', '');
$valPickupAddress = old('pickup_address', $businessAddress);
$valNotes = old('notes', '');

$draftSlots = is_array($draft['saved_image_slots'] ?? null) ? $draft['saved_image_slots'] : [null, null, null];
$imageSlots = [
    $draftSlots[0] ?? null,
    $draftSlots[1] ?? null,
    $draftSlots[2] ?? null,
];
$savedImages = array_values(array_filter($imageSlots));
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Update Inventory Details &mdash; EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .prep-wrap { max-width: 960px; margin: 0 auto; }
        .prep-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 0; margin-bottom: 20px; }
        .prep-card-header { padding: 14px 22px; background: #f8fafc; border-bottom: 1px solid #cbd5e1; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
        .prep-card-header h3 { margin: 0; font-size: 14.5px; font-weight: 700; color: #143825; }
        .prep-card-body { padding: 22px; }
        .prep-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .prep-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
        .prep-field { margin-bottom: 16px; }
        .prep-field label { display: block; font-size: 12.5px; font-weight: 700; color: #18422d; margin-bottom: 6px; }
        .prep-input { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 0; background: #ffffff; color: #0f172a; font: 400 13.5px var(--font-main); box-sizing: border-box; }
        .prep-input:focus { border-color: #18422d; outline: none; }
        .prep-hint { display: block; margin-top: 5px; font-size: 11.5px; color: #64748b; line-height: 1.45; }
        .prep-choice-card { display: flex; align-items: flex-start; gap: 10px; padding: 14px 16px; border: 1px solid #cbd5e1; border-radius: 0; background: #ffffff; cursor: pointer; }
        .prep-choice-card.selected { border: 2px solid #18422d; background: #f0fdf4; }
        .prep-analysis-box { padding: 12px 14px; background: #ffffff; border: 1px solid #cbd5e1; }
        .prep-analysis-label { display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px; }
        .prep-btn-primary { padding: 11px 24px; background: #143825; border: 1px solid #143825; color: #ffffff; font-size: 13.5px; font-weight: 700; border-radius: 0; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .prep-btn-primary:hover { background: #0d2b1b; }
        .prep-btn-secondary { padding: 10px 20px; background: #ffffff; border: 1px solid #cbd5e1; color: #334155; font-size: 13px; font-weight: 600; border-radius: 0; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .prep-btn-secondary:hover { background: #f1f5f9; }
        .prep-btn-utility { margin-top: 8px; padding: 6px 12px; background: #f8fafc; border: 1px solid #cbd5e1; color: #334155; font-size: 11.5px; font-weight: 600; border-radius: 0; cursor: pointer; }
        .prep-btn-utility:hover { background: #e2e8f0; }
        .inv-slots-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .inv-slot-wrapper { display: flex; flex-direction: column; border: 1px solid #cbd5e1; background: #ffffff; border-radius: 0; }
        .inv-slot-header { display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; background: #f1f5f9; border-bottom: 1px solid #cbd5e1; font-size: 11px; font-weight: 700; color: #334155; text-transform: uppercase; }
        .inv-slot-preview-area { height: 125px; background: #f8fafc; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; color: #64748b; font-size: 12px; font-weight: 600; gap: 6px; }
        .inv-slot-preview-area img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .inv-slot-actions { display: grid; grid-template-columns: 1fr 1fr; border-top: 1px solid #cbd5e1; }
        .inv-slot-action-btn { padding: 6px 8px; border: none; background: #ffffff; color: #334155; font-size: 11px; font-weight: 600; cursor: pointer; border-radius: 0; }
        .inv-slot-action-btn:hover { background: #f1f5f9; }
        .inv-slot-action-btn.btn-slot-remove { border-left: 1px solid #cbd5e1; color: #b91c1c; }
        .inv-slot-action-btn.btn-slot-upload-full { grid-column: 1 / -1; color: #18422d; font-weight: 700; }
        @media (max-width: 768px) {
            .prep-grid-2, .prep-grid-3, .inv-slots-grid-3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="business-dashboard-page">
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <?php include resource_path('views/business/partials/sidebar.php'); ?>

        <main class="dashboard-main">
            <div class="prep-wrap">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
                    <div>
                        <h1 style="margin:0 0 4px;font-size:22px;color:#123321;">Update Inventory Details</h1>
                        <p style="margin:0;font-size:13px;color:#526658;">
                            Review the material analysis results below, edit or complete your item information, and click <strong>Save Item</strong> to add it to Inventory Items.
                        </p>
                    </div>
                    <span style="display:inline-block;padding:6px 12px;background:#fef3c7;border:1px solid #f59e0b;color:#92400e;font-size:11.5px;font-weight:700;text-transform:uppercase;">
                        Pending Confirmation (Not Saved Yet)
                    </span>
                </div>

                <?php if ($errors->any()): ?>
                    <div role="alert" style="margin-bottom:16px;padding:12px 16px;background:#fff5f4;border:1px solid #fed7d2;border-left:3px solid #b91c1c;color:#b91c1c;font-size:13px;font-weight:600;">
                        <?= e($errors->first()) ?>
                    </div>
                <?php endif; ?>

                <!-- Material Analysis Summary Card (Clean, Simple Heading: Material Analysis) -->
                <div class="prep-card" style="border-left:4px solid #143825;background:#f8fbf8;">
                    <div class="prep-card-header" style="background:#eef6ed;">
                        <h3>Material Analysis</h3>
                        <span style="padding:4px 10px;background:<?= $suggestedReusability === 'reusable' ? '#143825' : '#475569' ?>;color:#ffffff;font-size:11.5px;font-weight:700;text-transform:uppercase;">
                            <?= $suggestedReusability === 'reusable' ? 'Reusable' : 'Non-Reusable' ?>
                        </span>
                    </div>
                    <div class="prep-card-body">
                        <?php if ($needsCategoryReview): ?>
                            <div style="margin-bottom:14px;padding:11px 14px;background:#fffbeb;border:1px solid #f59e0b;color:#92400e;font-size:12.5px;">
                                The item category could not be automatically determined with certainty. Please enter the most accurate Item Category in the form below before saving.
                            </div>
                        <?php endif; ?>

                        <div class="prep-grid-3" style="margin-bottom:12px;">
                            <div class="prep-analysis-box">
                                <span class="prep-analysis-label">Suggested Item Category</span>
                                <strong style="font-size:13.5px;color:#0f172a;"><?= e($suggestedCategory !== '' ? $suggestedCategory : 'Please specify below') ?></strong>
                            </div>
                            <div class="prep-analysis-box">
                                <span class="prep-analysis-label">Suggested Material Condition</span>
                                <strong style="font-size:13.5px;color:#0f172a;"><?= e($suggestedCondition) ?></strong>
                            </div>
                            <div class="prep-analysis-box">
                                <span class="prep-analysis-label">Suggested Listing Type</span>
                                <strong style="font-size:13.5px;color:#143825;"><?= $suggestedReusability === 'reusable' ? e($suggestedListingLabel) : 'Inventory Only (EcoCycle Helper)' ?></strong>
                            </div>
                        </div>

                        <?php if ($suggestedReusability === 'reusable' && $suggestedPriceRange !== ''): ?>
                            <div class="prep-analysis-box" style="margin-bottom:12px;">
                                <span class="prep-analysis-label">Suggested Price / Price Range (Guidance Only)</span>
                                <strong style="font-size:13.5px;color:#143825;"><?= e($suggestedPriceRange) ?></strong>
                            </div>
                        <?php endif; ?>

                        <div class="prep-analysis-box" style="margin-bottom:12px;">
                            <span class="prep-analysis-label">Reason for Classification</span>
                            <p style="margin:0;font-size:13px;color:#334155;line-height:1.5;"><?= e($reason) ?></p>
                        </div>

                        <div class="prep-grid-2">
                            <div class="prep-analysis-box">
                                <span class="prep-analysis-label">Suggested Next Steps</span>
                                <?php if (! empty($nextSteps)): ?>
                                    <ul style="margin:4px 0 0;padding-left:18px;font-size:12.5px;color:#334155;line-height:1.55;">
                                        <?php foreach ($nextSteps as $step): ?>
                                            <li><?= e($step) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p style="margin:0;font-size:12.5px;color:#334155;">Review and confirm the item details below, then click Save Item.</p>
                                <?php endif; ?>
                            </div>

                            <div class="prep-analysis-box">
                                <span class="prep-analysis-label"><?= $suggestedReusability === 'reusable' ? 'Suggested Reuse Applications' : 'Other Relevant Recommendations' ?></span>
                                <?php $displayList = ($suggestedReusability === 'reusable' && ! empty($reuseApps)) ? $reuseApps : $otherRecs; ?>
                                <?php if (! empty($displayList)): ?>
                                    <ul style="margin:4px 0 0;padding-left:18px;font-size:12.5px;color:#334155;line-height:1.55;">
                                        <?php foreach ($displayList as $entry): ?>
                                            <li><?= e($entry) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p style="margin:0;font-size:12.5px;color:#334155;">Confirm item specifications, condition, and pickup location before saving.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($suggestedReusability === 'reusable' && ! empty($otherRecs)): ?>
                            <div class="prep-analysis-box" style="margin-top:12px;">
                                <span class="prep-analysis-label">Other Relevant Recommendations</span>
                                <p style="margin:0;font-size:12.5px;color:#334155;line-height:1.5;"><?= e(implode(' ', $otherRecs)) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <datalist id="prep_categories_list">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <!-- Main Update Inventory Details Form -->
                <form method="POST" action="<?= route('business.inventory.store') ?>" enctype="multipart/form-data" id="prepare_inventory_form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="from_prepare" value="1">
                    <input type="hidden" name="saved_images" value="<?= e(json_encode($savedImages)) ?>">
                    <input type="hidden" name="saved_image_slots" value="<?= e(json_encode($imageSlots)) ?>">
                    <input type="hidden" name="analysis_token" value="<?= e((string) ($draft['analysis_token'] ?? '')) ?>">
                    <input type="hidden" name="ai_analysis_json" value="<?= e(json_encode($analysis)) ?>">

                    <!-- Section 1: Item Photos -->
                    <div class="prep-card">
                        <div class="prep-card-header">
                            <h3>Item Photos (1 to 3 Photos)</h3>
                            <span id="prep_img_counter" style="font-size:12px;font-weight:700;color:#18422d;">0 of 3 images</span>
                        </div>
                        <div class="prep-card-body">
                            <input type="file" id="prep_slot_input_0" name="image_1" accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;" onchange="handlePrepSlotFile(0, this)">
                            <input type="file" id="prep_slot_input_1" name="image_2" accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;" onchange="handlePrepSlotFile(1, this)">
                            <input type="file" id="prep_slot_input_2" name="image_3" accept="image/png,image/jpeg,image/webp,image/jpg" style="display:none;" onchange="handlePrepSlotFile(2, this)">
                            <input type="hidden" id="prep_remove_image_0" name="remove_image_1" value="0">
                            <input type="hidden" id="prep_remove_image_1" name="remove_image_2" value="0">
                            <input type="hidden" id="prep_remove_image_2" name="remove_image_3" value="0">

                            <div class="inv-slots-grid-3" id="prep_slots_grid"></div>
                        </div>
                    </div>

                    <!-- Section 2: Core Item Information -->
                    <div class="prep-card">
                        <div class="prep-card-header">
                            <h3>Item Information &amp; Quantity</h3>
                        </div>
                        <div class="prep-card-body">
                            <div class="prep-grid-2">
                                <div class="prep-field">
                                    <label for="prep_name">Item Name <span style="color:#b91c1c;">*</span></label>
                                    <input id="prep_name" name="name" type="text" required value="<?= e($valName) ?>" class="prep-input">
                                </div>
                                <div class="prep-field">
                                    <label for="prep_category">Item Category <span style="color:#b91c1c;">*</span></label>
                                    <select id="prep_category" name="category" required class="prep-input">
                                        <option value="">Select Category...</option>
                                        <?php foreach (\App\Models\Item::ALLOWED_CATEGORIES as $catOption): ?>
                                            <option value="<?= e($catOption) ?>" <?= (strcasecmp((string) $valCategory, $catOption) === 0) ? 'selected' : '' ?>><?= e($catOption) ?></option>
                                        <?php endforeach; ?>
                                        <?php if ($valCategory && ! in_array($valCategory, \App\Models\Item::ALLOWED_CATEGORIES, true)): ?>
                                            <option value="<?= e($valCategory) ?>" selected><?= e($valCategory) ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="prep-field">
                                <label for="prep_description">Item Description <span style="color:#b91c1c;">*</span></label>
                                <textarea id="prep_description" name="description" rows="3" required class="prep-input" style="resize:vertical;"><?= e($valDescription) ?></textarea>
                            </div>

                            <div class="prep-grid-3">
                                <div class="prep-field" style="margin-bottom:0;">
                                    <label for="prep_quantity">Quantity <span style="color:#b91c1c;">*</span></label>
                                    <input id="prep_quantity" name="quantity" type="number" step="0.01" min="0.01" required value="<?= e($valQuantity) ?>" class="prep-input">
                                </div>
                                <div class="prep-field" style="margin-bottom:0;">
                                    <label for="prep_unit">Unit of Measurement <span style="color:#b91c1c;">*</span></label>
                                    <select id="prep_unit" name="unit" required class="prep-input">
                                        <?php foreach (\App\Models\Item::ALLOWED_UNITS as $unitVal => $unitLabel): ?>
                                            <option value="<?= e($unitVal) ?>" <?= strtolower((string) $valUnit) === strtolower((string) $unitVal) ? 'selected' : '' ?>><?= e($unitLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="prep-field" style="margin-bottom:0;">
                                    <label for="prep_condition">Material Condition <span style="color:#b91c1c;">*</span></label>
                                    <select id="prep_condition" name="condition" required class="prep-input">
                                        <?php foreach (\App\Models\Item::ALLOWED_CONDITIONS as $condOpt): ?>
                                            <option value="<?= e($condOpt) ?>" <?= $valCondition === $condOpt ? 'selected' : '' ?>><?= e($condOpt) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Reusability Status, Listing Type, Price per Unit & Pickup Location -->
                    <div class="prep-card">
                        <div class="prep-card-header">
                            <h3>Reusability Status, Listing Options &amp; Pickup Location</h3>
                        </div>
                        <div class="prep-card-body">
                            <div class="prep-field">
                                <label>Reusability Status <span style="color:#b91c1c;">*</span></label>
                                <div class="prep-grid-2">
                                    <label class="prep-choice-card <?= $valReusability === 'reusable' ? 'selected' : '' ?>" id="prep_card_reusable">
                                        <input type="radio" name="reusability" value="reusable" <?= $valReusability === 'reusable' ? 'checked' : '' ?> onchange="updatePrepClassificationUI()">
                                        <div>
                                            <strong style="display:block;font-size:13px;color:#143825;">Reusable</strong>
                                            <span style="font-size:12px;color:#64748b;">Saves into Inventory Items where you can publish it to the Marketplace.</span>
                                        </div>
                                    </label>
                                    <label class="prep-choice-card <?= $valReusability === 'not_reusable' ? 'selected' : '' ?>" id="prep_card_not_reusable">
                                        <input type="radio" name="reusability" value="not_reusable" <?= $valReusability === 'not_reusable' ? 'checked' : '' ?> onchange="updatePrepClassificationUI()">
                                        <div>
                                            <strong style="display:block;font-size:13px;color:#334155;">Non-Reusable</strong>
                                            <span style="font-size:12px;color:#64748b;">Saves into Inventory Items and forwards directly to EcoCycle Helper for recycling/disposal guidance.</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Reusable Listing Configuration -->
                            <div id="prep_reusable_panel" style="<?= $valReusability === 'reusable' ? '' : 'display:none;' ?>padding:18px;background:#f8faf8;border:1px solid #cbd5e1;margin-top:12px;">
                                <div class="prep-field">
                                    <label>Listing Type <span style="color:#b91c1c;">*</span></label>
                                    <div class="prep-grid-3">
                                        <label class="prep-choice-card <?= $valListingType === 'buy' ? 'selected' : '' ?>" id="prep_type_buy">
                                            <input type="radio" name="listing_type" value="buy" <?= $valListingType === 'buy' ? 'checked' : '' ?> onchange="updatePrepListingTypeUI()">
                                            <div>
                                                <strong style="display:block;font-size:13px;color:#143825;">Sell</strong>
                                                <span style="font-size:11.5px;color:#64748b;">Set Price per Unit (RM &gt; 0.00).</span>
                                            </div>
                                        </label>
                                        <label class="prep-choice-card <?= $valListingType === 'free' ? 'selected' : '' ?>" id="prep_type_free">
                                            <input type="radio" name="listing_type" value="free" <?= $valListingType === 'free' ? 'checked' : '' ?> onchange="updatePrepListingTypeUI()">
                                            <div>
                                                <strong style="display:block;font-size:13px;color:#143825;">Free</strong>
                                                <span style="font-size:11.5px;color:#64748b;">Offered at RM 0.00.</span>
                                            </div>
                                        </label>
                                        <label class="prep-choice-card <?= $valListingType === 'lend' ? 'selected' : '' ?>" id="prep_type_lend">
                                            <input type="radio" name="listing_type" value="lend" <?= $valListingType === 'lend' ? 'checked' : '' ?> onchange="updatePrepListingTypeUI()">
                                            <div>
                                                <strong style="display:block;font-size:13px;color:#143825;">Lend</strong>
                                                <span style="font-size:11.5px;color:#64748b;">Temporary borrow (RM 0.00).</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="prep-field" id="prep_price_group" style="<?= $valListingType === 'buy' ? '' : 'display:none;' ?>">
                                    <label for="prep_price">Price per Unit (RM) <span style="color:#b91c1c;">*</span></label>
                                    <input id="prep_price" name="price" type="number" step="0.01" min="0.01" value="<?= e($valListingType === 'buy' ? $valPrice : '') ?>" placeholder="e.g. 25.00" class="prep-input" style="max-width:280px;" <?= ($valReusability === 'reusable' && $valListingType === 'buy') ? 'required' : '' ?>>
                                    <span class="prep-hint">
                                        Required for Sell listings (must be greater than RM 0.00).<?php if ($suggestedPriceRange !== ''): ?> Reference suggestion: <strong><?= e($suggestedPriceRange) ?></strong>.<?php endif; ?>
                                    </span>
                                </div>

                                <div id="prep_free_notice" style="<?= in_array($valListingType, ['free', 'lend'], true) ? '' : 'display:none;' ?>margin-bottom:16px;padding:10px 14px;background:#eef6ed;border:1px solid #b9dec0;color:#143825;font-size:12.5px;font-weight:600;">
                                    Price is set to RM 0.00 for Free and Lend listings. No payment is required.
                                </div>

                                <div class="prep-field" style="margin-bottom:0;">
                                    <label for="prep_pickup_address">Pickup Location</label>
                                    <input id="prep_pickup_address" name="pickup_address" type="text" maxlength="255" value="<?= e($valPickupAddress) ?>" placeholder="Enter warehouse or pickup address" class="prep-input">
                                    <?php if ($businessAddress !== ''): ?>
                                        <button type="button" class="prep-btn-utility" onclick="document.getElementById('prep_pickup_address').value = <?= json_encode($businessAddress) ?>">
                                            Use Business Registered Address
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Non-Reusable Notice -->
                            <div id="prep_non_reusable_panel" style="<?= $valReusability === 'not_reusable' ? '' : 'display:none;' ?>padding:14px 16px;background:#f8fafc;border:1px solid #cbd5e1;margin-top:12px;font-size:13px;color:#475569;line-height:1.5;">
                                <strong style="display:block;color:#1e293b;margin-bottom:4px;">Non-Reusable Material (Forwarded to EcoCycle Helper)</strong>
                                Clicking <strong>Save Item</strong> saves this material into Inventory Items (never published to the Marketplace) and opens <strong>EcoCycle Helper</strong> with recycling, repurposing, disposal, environmental guidance, and GPS recycling facility search.
                            </div>

                            <div class="prep-field" style="margin-top:16px;margin-bottom:0;">
                                <label for="prep_notes">Additional Notes (Optional)</label>
                                <textarea id="prep_notes" name="notes" rows="2" class="prep-input" placeholder="Handling notes, storage bay, or pickup instructions..." style="resize:vertical;"><?= e($valNotes) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:28px;flex-wrap:wrap;">
                        <button type="button" class="prep-btn-secondary" onclick="document.getElementById('cancel_draft_form').submit();">
                            Cancel
                        </button>
                        <button type="submit" class="prep-btn-primary">
                            Save Item
                        </button>
                    </div>
                </form>

                <form method="POST" action="<?= route('business.inventory.draft.cancel') ?>" id="cancel_draft_form" style="display:none;">
                    <?= csrf_field() ?>
                </form>
            </div>
        </main>
    </div>

    <script>
    var prepSlotInitial = <?= json_encode($imageSlots) ?>;
    var prepSlotNewFiles = [null, null, null];
    var prepSlotNewPreviews = [null, null, null];
    var prepSlotRemoved = [false, false, false];

    function triggerPrepSlotInput(idx) {
        var inp = document.getElementById('prep_slot_input_' + idx);
        if (inp) inp.click();
    }

    function handlePrepSlotFile(idx, input) {
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
        prepSlotNewFiles[idx] = file;
        prepSlotRemoved[idx] = false;
        document.getElementById('prep_remove_image_' + idx).value = '0';

        var reader = new FileReader();
        reader.onload = function(e) {
            prepSlotNewPreviews[idx] = e.target.result;
            renderPrepSlots();
        };
        reader.readAsDataURL(file);
    }

    function clearPrepSlot(idx) {
        prepSlotNewFiles[idx] = null;
        prepSlotNewPreviews[idx] = null;
        prepSlotRemoved[idx] = true;
        document.getElementById('prep_slot_input_' + idx).value = '';
        document.getElementById('prep_remove_image_' + idx).value = '1';
        renderPrepSlots();
    }

    function renderPrepSlots() {
        var grid = document.getElementById('prep_slots_grid');
        var counter = document.getElementById('prep_img_counter');
        if (!grid) return;

        grid.innerHTML = '';
        var labels = ['Image 1 (Cover)', 'Image 2', 'Image 3'];
        var activeCount = 0;

        for (var i = 0; i < 3; i++) {
            (function(slotIndex) {
                var previewUrl = null;
                var statusLabel = 'Empty';

                if (prepSlotNewFiles[slotIndex] && prepSlotNewPreviews[slotIndex]) {
                    previewUrl = prepSlotNewPreviews[slotIndex];
                    statusLabel = 'New';
                    activeCount++;
                } else if (!prepSlotRemoved[slotIndex] && prepSlotInitial[slotIndex]) {
                    previewUrl = prepSlotInitial[slotIndex];
                    statusLabel = 'Uploaded';
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
                preview.onclick = function() { triggerPrepSlotInput(slotIndex); };
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
                    btnReplace.onclick = function() { triggerPrepSlotInput(slotIndex); };

                    var btnRemove = document.createElement('button');
                    btnRemove.type = 'button';
                    btnRemove.className = 'inv-slot-action-btn btn-slot-remove';
                    btnRemove.textContent = 'Remove';
                    btnRemove.onclick = function() { clearPrepSlot(slotIndex); };

                    actions.appendChild(btnReplace);
                    actions.appendChild(btnRemove);
                } else {
                    var btnUpload = document.createElement('button');
                    btnUpload.type = 'button';
                    btnUpload.className = 'inv-slot-action-btn btn-slot-upload-full';
                    btnUpload.textContent = 'Upload';
                    btnUpload.onclick = function() { triggerPrepSlotInput(slotIndex); };
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

    function updatePrepClassificationUI() {
        var checked = document.querySelector('input[name="reusability"]:checked');
        var val = checked ? checked.value : 'reusable';
        var panelReus = document.getElementById('prep_reusable_panel');
        var panelNonReus = document.getElementById('prep_non_reusable_panel');
        var cardR = document.getElementById('prep_card_reusable');
        var cardN = document.getElementById('prep_card_not_reusable');
        var priceInput = document.getElementById('prep_price');

        if (val === 'reusable') {
            if (panelReus) panelReus.style.display = 'block';
            if (panelNonReus) panelNonReus.style.display = 'none';
            if (cardR) cardR.classList.add('selected');
            if (cardN) cardN.classList.remove('selected');
            updatePrepListingTypeUI();
        } else {
            if (panelReus) panelReus.style.display = 'none';
            if (panelNonReus) panelNonReus.style.display = 'block';
            if (cardR) cardR.classList.remove('selected');
            if (cardN) cardN.classList.add('selected');
            if (priceInput) priceInput.required = false;
        }
    }

    function updatePrepListingTypeUI() {
        var reusChecked = document.querySelector('input[name="reusability"]:checked');
        var isReusable = !reusChecked || reusChecked.value === 'reusable';
        var checked = document.querySelector('input[name="listing_type"]:checked');
        var val = checked ? checked.value : 'buy';
        var priceGroup = document.getElementById('prep_price_group');
        var freeNotice = document.getElementById('prep_free_notice');
        var priceInput = document.getElementById('prep_price');
        var cardBuy = document.getElementById('prep_type_buy');
        var cardFree = document.getElementById('prep_type_free');
        var cardLend = document.getElementById('prep_type_lend');

        if (cardBuy) cardBuy.classList.toggle('selected', val === 'buy');
        if (cardFree) cardFree.classList.toggle('selected', val === 'free');
        if (cardLend) cardLend.classList.toggle('selected', val === 'lend');

        if (val === 'free' || val === 'lend') {
            if (priceGroup) priceGroup.style.display = 'none';
            if (freeNotice) freeNotice.style.display = 'block';
            if (priceInput) {
                priceInput.value = '';
                priceInput.required = false;
            }
        } else {
            if (priceGroup) priceGroup.style.display = 'block';
            if (freeNotice) freeNotice.style.display = 'none';
            if (priceInput) priceInput.required = isReusable;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderPrepSlots();
        updatePrepClassificationUI();
    });
    </script>
</body>
</html>
