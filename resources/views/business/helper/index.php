<?php

use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/** @var User $business */
/** @var string $businessAddress */
/** @var array{lat: float, lng: float} $businessCoords */
/** @var Collection<int, Item> $items */
/** @var Item|null $selectedItem */
/** @var array<string, mixed>|null $initialAnalysis */
/** @var array<string, float> $defaultCoords */
/** @var array<int, array<string, mixed>> $nearbyCentres */
/** @var string $googleMapsApiKey */

$companyName = $business->business_name ?: ($business->name ?? 'EcoCycle Business');
$businessAddress = $businessAddress ?? trim(implode(', ', array_filter([
    $business->registered_address,
    $business->postcode,
    $business->city,
    $business->state,
]))) ?: 'Registered Company Premises';
$businessCoords = $businessCoords ?? ($defaultCoords ?? ['lat' => 3.0738, 'lng' => 101.5183]);
$defaultCoords = $businessCoords;
$activeNav = 'helper';
$googleMapsApiKey = trim((string) ($googleMapsApiKey ?? ''));
$hasSelected = $selectedItem !== null && is_array($initialAnalysis);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EcoCycle Helper &amp; Google Maps GPS Locator &mdash; EcoCycle</title>
    <link rel="icon" type="image/svg+xml" href="/images/figma/ecocycle-leaf.svg">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <?php if ($googleMapsApiKey === ''): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <?php endif; ?>
    <style>
        .helper-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            align-items: stretch;
        }
        @media (max-width: 1080px) {
            .helper-grid {
                grid-template-columns: 1fr;
            }
        }
        .helper-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .helper-card-header {
            padding: 15px 18px;
            background: var(--surface-subtle);
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .helper-card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }
        .item-select-bar {
            padding: 13px 18px;
            background: #f4f8f3;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .item-select-bar select {
            flex: 1;
            min-width: 220px;
            padding: 9px 12px;
            border-radius: 0;
            border: 1px solid var(--input-border);
            font-size: 13px;
            background: #ffffff;
            color: var(--text-primary);
            font-weight: 600;
            cursor: pointer;
        }
        .helper-welcome-box {
            padding: 36px 24px;
            text-align: center;
            background: #fafcf9;
        }
        .helper-welcome-box h3 {
            margin: 0 0 8px;
            font-size: 16px;
            color: #143825;
        }
        .helper-welcome-box p {
            margin: 0 auto 18px;
            max-width: 480px;
            font-size: 13px;
            color: #526658;
            line-height: 1.55;
        }
        .selected-item-strip {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            background: #ffffff;
            border-bottom: 1px solid var(--card-border);
        }
        .selected-item-thumb {
            display: block;
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: contain;
            border: 1px solid var(--card-border);
            flex-shrink: 0;
            background: #f1f5f2;
        }
        .selected-item-gallery {
            display: grid;
            justify-items: center;
            gap: 4px;
            flex: 0 0 48%;
            min-width: 0;
        }
        @media (max-width: 460px) {
            .selected-item-strip {
                flex-direction: column;
                align-items: stretch;
            }
            .selected-item-gallery {
                flex: none;
            }
        }
        .selected-item-gallery-controls:not([hidden]) {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: #143825;
        }
        .selected-item-gallery-controls button {
            min-width: 24px;
            min-height: 24px;
            padding: 0;
            border: 1px solid var(--card-border);
            border-radius: 4px;
            background: #fff;
            color: inherit;
            cursor: pointer;
        }
        .metric-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 0;
            font-size: 10.5px;
            font-weight: 700;
        }
        .metric-badge.green { background: #e8f5e9; color: #1b5e20; border: 1px solid #c2dec0; }
        .metric-badge.amber { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .metric-badge.blue { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .metric-badge.red { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        /* Guidance Content Sections (Clean structured display - No Chatbox) */
        .guidance-body {
            flex: 1;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: #fafdfa;
        }
        .guidance-section {
            background: #ffffff;
            border: 1px solid var(--card-border);
            padding: 14px 16px;
        }
        .guidance-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }
        .guidance-section-title {
            font-size: 13px;
            font-weight: 700;
            color: #143825;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin: 0;
        }
        .guidance-list {
            margin: 0;
            padding-left: 20px;
            font-size: 13px;
            color: #334155;
            line-height: 1.6;
        }
        .guidance-list li {
            margin-bottom: 6px;
        }
        .guidance-list li:last-child {
            margin-bottom: 0;
        }
        .impact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        @media (max-width: 600px) {
            .impact-grid {
                grid-template-columns: 1fr;
            }
        }
        .impact-card {
            padding: 11px 13px;
            font-size: 12px;
            line-height: 1.5;
        }
        .impact-card.green {
            background: #f4fbf5;
            border: 1px solid #c2dec0;
            color: #143825;
        }
        .impact-card.blue {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e3a8a;
        }
        .impact-card-title {
            display: block;
            font-size: 10.5px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }
        .risk-warning-card {
            padding: 12px 14px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 4px solid #b45309;
            color: #78350f;
            font-size: 12.5px;
            line-height: 1.55;
        }

        /* GPS Location & Map Controls */
        .gps-controls {
            padding: 12px 16px;
            background: var(--surface-subtle);
            border-bottom: 1px solid var(--card-border);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .reference-address-box {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 10px 12px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .reference-address-icon {
            color: #143825;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .reference-address-text {
            font-size: 12px;
            color: #1e293b;
            line-height: 1.45;
        }
        .btn-location-ref {
            background: #ffffff;
            color: #143825;
            border: 1px solid #143825;
            padding: 7px 13px;
            border-radius: 0;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }
        .btn-location-ref:hover {
            background: #f0f7f2;
        }
        .btn-location-ref.active {
            background: #143825;
            color: #ffffff;
            border-color: #143825;
        }
        .map-mode-btn {
            padding: 5px 10px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #334155;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
        }
        .map-mode-btn.active {
            background: #143825;
            border-color: #143825;
            color: #ffffff;
        }
        #ecocycle-gps-map {
            width: 100%;
            height: 340px;
            background: #e5ece5;
            border-bottom: 1px solid var(--card-border);
            z-index: 1;
        }
        .facility-list {
            padding: 14px 16px;
            max-height: 480px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: #fafafa;
        }
        .facility-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 0;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .facility-card:hover {
            border-color: var(--brand-green);
            background: #fafdfa;
        }
        .facility-card.highlighted {
            border-color: #15803d;
            background: #f4fbf5;
            box-shadow: 0 0 0 2px rgba(21, 128, 61, 0.25);
        }
        .facility-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
        }
        .facility-name {
            font-size: 13.5px;
            font-weight: 700;
            color: #123321;
            margin: 0 0 4px;
        }
        .distance-pill {
            background: #eef7ee;
            color: #1e6338;
            border: 1px solid #c4dfc0;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }
        .suitability-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10.5px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 0;
        }
        .suitability-pill.most-suitable {
            background: #143825;
            color: #ffffff;
        }
        .suitability-pill.highly-suitable {
            background: #e8f5e9;
            color: #15803d;
            border: 1px solid #c2dec0;
        }
        .suitability-pill.alternative {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .facility-meta {
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.45;
        }
        .suitability-reason-text {
            font-size: 11.5px;
            color: #15803d;
            font-weight: 600;
            background: #f0fdf4;
            padding: 4px 8px;
            border-left: 2px solid #16a34a;
        }
        .btn-directions {
            margin-top: 4px;
            background: var(--forest-bg);
            color: #ffffff;
            border: 1px solid var(--forest-bg);
            text-decoration: none;
            font-size: 11.5px;
            font-weight: 600;
            padding: 6px 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-directions:hover { background: #0d2b1b; }
        .leaflet-container {
            font-family: var(--font-main) !important;
        }

        .helper-workspace {
            padding-inline: max(clamp(20px, 4vw, 48px), calc((100% - 1180px) / 2));
        }
        .helper-workspace .page-header {
            padding: 0;
            border: 0;
        }
        .helper-workspace .page-header h1 {
            color: #14532d;
            font-size: clamp(26px, 3vw, 40px);
        }
        .helper-workspace .helper-card {
            min-width: 0;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(20, 56, 37, .06);
        }
        .helper-workspace .helper-card-title {
            font-size: 17px;
        }
        .helper-workspace .helper-card-header {
            background: #f1f6ef;
        }
        .helper-workspace :is(.btn-location-ref, .btn-directions) {
            border-radius: 6px !important;
        }
        .helper-workspace :is(.item-select-bar select, .facility-card, .guidance-section, .impact-card, .risk-warning-card, .reference-address-box, .selected-item-thumb) {
            border-radius: 6px;
        }
        .helper-workspace :is(select, button, a):focus-visible {
            outline: 2px solid #176b40;
            outline-offset: 2px;
        }
        .helper-workspace .item-select-bar select {
            min-width: min(220px, 100%);
            max-width: 100%;
            min-height: 40px;
            font: inherit;
        }
        .helper-workspace :is(.btn-location-ref.active, .btn-directions) {
            background: #176b40;
            border-color: #176b40;
        }
        .selected-item-details {
            margin: 4px 0;
            padding-left: 17px;
            font-size: 12.5px;
            line-height: 1.55;
            color: var(--text-secondary);
        }
        .helper-workspace .facility-list {
            gap: 12px;
            background: #f1f6ef;
        }
        .helper-workspace .facility-card {
            flex-shrink: 0;
            gap: 10px;
            padding: 16px;
            border-radius: 10px;
        }
        .facility-header {
            flex-wrap: wrap;
        }
        .facility-header > div {
            flex: 1 1 220px;
            min-width: 0;
        }
        .facility-name {
            font-size: 15px;
            line-height: 1.4;
        }
        .distance-pill, .suitability-pill {
            border-radius: 5px;
        }
        .suitability-pill.most-suitable {
            background: #e8f5e9;
            color: #14532d;
            border: 1px solid #c2dec0;
        }
        .suitability-reason-text {
            border-radius: 4px;
            padding: 8px 10px;
        }
        .facility-meta {
            line-height: 1.6;
        }
        .helper-workspace .btn-directions {
            min-height: 36px;
        }
        .helper-workspace .gps-controls {
            padding: 16px;
            gap: 12px;
            background: #f1f6ef;
        }
        .helper-workspace .reference-address-box {
            padding: 14px;
            border-color: var(--card-border);
            border-radius: 8px;
        }
        .reference-address-text {
            display: block;
            margin-top: 4px;
            color: var(--text-secondary);
        }
        .map-mode-group {
            display: inline-flex;
            gap: 3px;
            padding: 3px;
            background: #e4eee3;
            border: 1px solid var(--card-border);
            border-radius: 7px;
        }
        .map-mode-group .map-mode-btn {
            min-height: 32px;
            border: 0;
            border-radius: 4px;
            background: transparent;
            color: #143825;
        }
        .map-mode-group .map-mode-btn.active {
            background: #fff;
            box-shadow: 0 1px 3px rgba(20, 56, 37, .15);
        }
        .helper-workspace .btn-location-ref {
            min-height: 40px;
        }
        .locator-notice {
            padding: 8px 11px;
            background: #fff;
            border-left: 3px solid #176b40;
            border-radius: 4px;
            color: var(--text-secondary);
            font-size: 12px;
            line-height: 1.5;
        }
    </style>
</head>
<body class="business-dashboard-page">
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <?php include resource_path('views/business/partials/sidebar.php'); ?>

        <main class="dashboard-main helper-workspace">
            <?php if (session('status')): ?>
                <div role="status" style="margin-bottom:16px;padding:12px 16px;background:#eaf3e8;border:1px solid #c7dbc5;border-left:3px solid #176b40;color:#176b40;font-size:13px;font-weight:600;">
                    <?= e(session('status')) ?>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1>EcoCycle Helper</h1>
                    <p>This material has been classified as non-reusable. EcoCycle Helper provides recommendations on how to manage, recycle or dispose of the material properly.</p>
                </div>
            </div>

            <!-- Main Helper Grid: Left = Material Analysis & Guidance, Right = Google Maps GPS Locator & Suitable Places -->
            <div class="helper-grid">
                <!-- Left Column: Non-Reusable Item Selector + Carried-Forward AI Guidance (NO CHATBOX) -->
                <section class="helper-card">
                    <div class="helper-card-header">
                        <h2 class="helper-card-title">Material Analysis &amp; Disposal Guidance</h2>
                    </div>

                    <!-- Strictly Non-Reusable Inventory Selector -->
                    <div class="item-select-bar">
                        <label for="item-selector" style="font-size:12px;font-weight:700;color:var(--text-primary);white-space:nowrap;">Non-Reusable Item:</label>
                        <select id="item-selector" onchange="onSelectItem(this.value)">
                            <?php if ($items->isEmpty()): ?>
                                <option value="">No Non-Reusable items in your inventory yet</option>
                            <?php else: ?>
                                <option value="">-- Select a Non-Reusable item to view guidance --</option>
                                <?php foreach ($items as $it): ?>
                                    <option value="<?= $it->id ?>" data-description="<?= e(trim((string) $it->description)) ?>" <?= $selectedItem && $selectedItem->id === $it->id ? 'selected' : '' ?>>
                                        <?= e($it->name ?: $it->title) ?> (<?= (float) $it->quantity ?> <?= e($it->unit) ?> &middot; <?= e($it->category) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Empty / No Selection State -->
                    <div id="helper-empty-state" class="helper-welcome-box" style="<?= $hasSelected ? 'display:none;' : '' ?>">
                        <h3>Select a Non-Reusable Item</h3>
                        <p>
                            EcoCycle Helper exclusively supports <strong>Non-Reusable</strong> materials. Select an item from your inventory above to review its material analysis, handling steps, and nearby certified recycling facilities.
                        </p>
                        <?php if ($items->isEmpty()): ?>
                            <div style="margin-bottom:16px;padding:12px 16px;background:#ffffff;border:1px solid #cbd5e1;font-size:12.5px;color:#475569;display:inline-block;">
                                No Non-Reusable items found in your inventory yet. Add non-reusable materials in <a href="/business/inventory?add=1" style="color:#143825;font-weight:700;">Inventory Items</a>.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Selected Item Strip -->
                    <div id="selected-item-strip" class="selected-item-strip" style="<?= $hasSelected ? '' : 'display:none;' ?>">
                        <?php $itemPhotos = $hasSelected ? $selectedItem->getAllImages() : []; ?>
                        <div class="selected-item-gallery" role="group" aria-label="Material photos" id="strip-item-gallery" data-photos="<?= e(json_encode($itemPhotos, JSON_INVALID_UTF8_SUBSTITUTE)) ?>">
                            <img id="strip-item-img" src="<?= e($itemPhotos[0] ?? '/images/figma/ecocycle-leaf.svg') ?>" alt="Selected material" class="selected-item-thumb">
                            <div id="strip-item-gallery-controls" class="selected-item-gallery-controls" <?= count($itemPhotos) < 2 ? 'hidden' : '' ?>>
                                <button type="button" aria-label="Previous photo" onclick="slideHelperPhoto(-1)">&lsaquo;</button>
                                <span id="strip-item-photo-count" aria-live="polite">1 / <?= count($itemPhotos) ?></span>
                                <button type="button" aria-label="Next photo" onclick="slideHelperPhoto(1)">&rsaquo;</button>
                            </div>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <strong id="strip-item-name" style="font-size:15px;color:#123321;display:block;"><?= $hasSelected ? e($selectedItem->name ?: $selectedItem->title) : '' ?></strong>
                            <ul class="selected-item-details">
                                <li>Non-Reusable</li>
                                <li id="strip-item-category"><?= $hasSelected ? e($selectedItem->category) : '' ?></li>
                                <li id="strip-item-qty"><?= $hasSelected ? ((float) $selectedItem->quantity).' '.e($selectedItem->unit) : '' ?></li>
                                <li id="strip-item-condition"><?= $hasSelected ? e($selectedItem->condition) : '' ?></li>
                            </ul>
                            <?php $description = $hasSelected ? trim((string) $selectedItem->description) : ''; ?>
                            <p id="strip-item-description" style="margin:4px 0 0;font-size:13px;line-height:1.5;white-space:pre-line;overflow-wrap:anywhere;" <?= $description === '' ? 'hidden' : '' ?>><?= e($description) ?></p>
                        </div>
                    </div>

                    <!-- Carried-Forward Analysis & Guidance Details (Direct display, NO CHATBOT) -->
                    <div id="guidance-display-panel" class="guidance-body" style="<?= $hasSelected ? '' : 'display:none;' ?>">
                        <!-- 1. Strategy & Recommended Handling -->
                        <div class="guidance-section" style="border-left:4px solid #143825;">
                            <div class="guidance-section-header">
                                <h3 class="guidance-section-title">Recommended Handling &amp; Strategy</h3>
                                <span id="guidance-value-badge" style="font-size:11px;font-weight:700;color:#15803d;background:#e8f5e9;padding:2px 7px;">
                                    <?= $hasSelected && !empty($initialAnalysis['estimated_value_myr']) ? e($initialAnalysis['estimated_value_myr']) : 'Industrial Waste Recovery' ?>
                                </span>
                            </div>
                            <p id="guidance-handling-text" style="margin:0;font-size:13.5px;color:#1e293b;font-weight:600;line-height:1.5;">
                                <?= $hasSelected ? e(($initialAnalysis['action_recommendation'] ?? '') . (!empty($initialAnalysis['strategy']) ? ' — ' . $initialAnalysis['strategy'] : '')) : '' ?>
                            </p>
                        </div>

                        <!-- 2. What Can the Business Owner Do? (Actionable steps) -->
                        <div class="guidance-section">
                            <div class="guidance-section-header">
                                <h3 class="guidance-section-title">What Can the Business Owner Do?</h3>
                            </div>
                            <ol class="guidance-list" id="guidance-owner-actions">
                                <?php if ($hasSelected): ?>
                                    <?php foreach (($initialAnalysis['owner_actions'] ?? $initialAnalysis['recycling_steps'] ?? []) as $step): ?>
                                        <li><?= e($step) ?></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ol>
                        </div>

                        <!-- 3. Alternative Recovery & Repurposing Options -->
                        <div class="guidance-section" id="guidance-repurpose-box" style="<?= $hasSelected && !empty($initialAnalysis['repurpose_ideas']) ? '' : 'display:none;' ?>">
                            <div class="guidance-section-header">
                                <h3 class="guidance-section-title">Alternative Recovery &amp; Repurposing Options</h3>
                            </div>
                            <ul class="guidance-list" id="guidance-repurpose-ideas">
                                <?php if ($hasSelected && !empty($initialAnalysis['repurpose_ideas'])): ?>
                                    <?php foreach ($initialAnalysis['repurpose_ideas'] as $idea): ?>
                                        <li><?= e($idea) ?></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <!-- 4. Qualitative Environmental & Carbon Impact Guidance -->
                        <div class="impact-grid">
                            <div class="impact-card green">
                                <span class="impact-card-title" style="color:#1b5e20;">Environmental Impact (Qualitative)</span>
                                <span id="guidance-env-impact">
                                    <?= $hasSelected ? e($initialAnalysis['environmental_impact'] ?? 'Proper segregation and licensed recovery reduce landfill burden and prevent environmental pollution.') : 'Proper segregation and licensed recovery reduce landfill burden and prevent environmental pollution.' ?>
                                </span>
                            </div>
                            <div class="impact-card blue">
                                <span class="impact-card-title" style="color:#1d4ed8;">Carbon Impact Guidance (Qualitative)</span>
                                <span id="guidance-carbon-impact">
                                    <?= $hasSelected ? e($initialAnalysis['carbon_impact'] ?? $initialAnalysis['carbon_saving_estimate'] ?? 'Diverting non-reusable secondary material to verified recycling avoids landfill methane emissions and conserves energy.') : 'Diverting non-reusable secondary material to verified recycling avoids landfill methane emissions and conserves energy.' ?>
                                </span>
                            </div>
                        </div>

                        <!-- 5. Compliance & Disposal Risks Notice -->
                        <div class="risk-warning-card" id="guidance-risks-box">
                            <strong style="display:block;margin-bottom:4px;color:#92400e;">Compliance &amp; Disposal Risks:</strong>
                            <span id="guidance-risks-text">
                                <?= $hasSelected ? e($initialAnalysis['improper_disposal_risks'] ?? $initialAnalysis['disposal_warning'] ?? 'Unauthorized dumping or illegal burning violates Environmental Quality Act regulations.') : 'Unauthorized dumping or illegal burning violates Environmental Quality Act regulations.' ?>
                            </span>
                        </div>
                    </div>
                </section>

                <!-- Right Column: Interactive Google Maps GPS Locator & Suitable Disposal Facilities -->
                <section class="helper-card">
                    <div class="helper-card-header">
                        <h2 class="helper-card-title">Google Maps GPS Locator</h2>
                    </div>

                    <!-- GPS Controls & Main Registered SME Address Reference Box -->
                    <div class="gps-controls">
                        <!-- SME Company Address as Main Reference Point -->
                        <div class="reference-address-box">
                            <svg class="reference-address-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <div style="flex:1;min-width:0;">
                                <strong style="display:block;font-size:12px;color:#143825;margin-bottom:1px;">
                                    Registered Company Address
                                </strong>
                                <span class="reference-address-text" id="company-registered-address">
                                    <strong><?= e($companyName) ?></strong> &mdash; <?= e($businessAddress) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Address and map controls -->
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                            <div style="display:inline-flex;gap:6px;align-items:center;">
                                <button type="button" class="btn-location-ref active" id="btn-use-my-address" onclick="useMyAddress()" title="Use registered company address as GPS reference point">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <span>My Address</span>
                                </button>
                            </div>

                            <div class="map-mode-group" role="group" aria-label="Map view">
                                <button type="button" id="btn-map-roadmap" class="map-mode-btn active" onclick="switchMapType('roadmap')">Map</button>
                                <button type="button" id="btn-map-satellite" class="map-mode-btn" onclick="switchMapType('satellite')">Satellite</button>
                            </div>
                        </div>

                        <div id="gps-notice-banner" class="locator-notice" role="status" style="display:none;"></div>

                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:11.5px;color:#526658;flex-wrap:wrap;gap:6px;">
                            <span>Click any map marker to view place details &amp; Google Maps directions.</span>
                            <span id="map-marker-count" style="font-weight:700;color:#143825;"><?= count($nearbyCentres) ?> suitable facility(s)</span>
                        </div>
                    </div>

                    <!-- Interactive Google Maps Container -->
                    <div id="ecocycle-gps-map" aria-label="Interactive Google Maps Recycling Facilities Locator"></div>

                    <!-- Suitable Places List (Filtered by material & prioritised by suitability) -->
                    <div class="facility-list" id="facility-list-container">
                        <?php if (empty($nearbyCentres)): ?>
                            <div style="padding:16px;background:#fffbeb;border:1px solid #fde68a;border-left:3px solid #b45309;color:#78350f;font-size:12.5px;line-height:1.55;">
                                <strong style="display:block;color:#92400e;margin-bottom:4px;">No Verified Facilities Found Nearby</strong>
                                No registered recovery centres in our directory explicitly match this material near your registered company location. Contact your local authority (PBT / SWCorp) or DOE Malaysia for approved handling.
                            </div>
                        <?php else: ?>
                            <?php foreach ($nearbyCentres as $idx => $centre): ?>
                                <?php
                                $tier = $centre['suitability_tier'] ?? 'Highly Suitable';
                                $badgeClass = match ($tier) {
                                    'Most Suitable' => 'most-suitable',
                                    'Highly Suitable' => 'highly-suitable',
                                    default => 'alternative',
                                };
                                ?>
                                <article class="facility-card" id="facility-card-<?= $centre['id'] ?>" onclick="focusFacilityOnMap(<?= $idx ?>)">
                                    <div class="facility-header">
                                        <div>
                                            <h3 class="facility-name"><?= e($centre['name']) ?></h3>
                                            <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:3px;">
                                                <span class="suitability-pill <?= $badgeClass ?>">
                                                    <?= e(trim(str_replace('⭐', '', $centre['suitability_badge'] ?? 'Verified Specialist'))) ?>
                                                </span>
                                                <span style="font-size:10.5px;background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;padding:2px 6px;font-weight:700;">
                                                    <?= e($centre['license']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <span class="distance-pill"><?= $centre['distance_km'] ?> km from My Address</span>
                                    </div>
                                    <?php if (!empty($centre['suitability_reason'])): ?>
                                        <div class="suitability-reason-text">
                                            <?= e($centre['suitability_reason']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="facility-meta">
                                        <div><strong>Address:</strong> <?= e($centre['address']) ?></div>
                                        <div style="margin-top:2px;"><strong>Phone:</strong> <?= e($centre['phone']) ?> &middot; <strong>Hours:</strong> <?= e($centre['operating_hours']) ?></div>
                                        <?php if (!empty($centre['subtypes'])): ?>
                                            <div style="margin-top:2px;color:#475569;"><strong>Specialized Subtypes:</strong> <?= e(implode(', ', $centre['subtypes'])) ?></div>
                                        <?php elseif (!empty($centre['accepted_materials'])): ?>
                                            <div style="margin-top:2px;color:#475569;"><strong>Accepts:</strong> <?= e(implode(', ', $centre['accepted_materials'])) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:4px;">
                                        <a href="<?= e($centre['directions_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn-directions" onclick="event.stopPropagation();">
                                            Get Directions on Google Maps
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>

<script>
var companyName = <?= json_encode($companyName) ?>;
var companyAddress = <?= json_encode($businessAddress) ?>;
var registeredLat = <?= (float) $businessCoords['lat'] ?>;
var registeredLng = <?= (float) $businessCoords['lng'] ?>;
var currentLat = registeredLat;
var currentLng = registeredLng;
var currentItemId = <?= $selectedItem ? (int) $selectedItem->id : 'null' ?>;
var currentCategory = <?= $selectedItem ? json_encode($selectedItem->category) : 'null' ?>;
var currentCentres = <?= json_encode($nearbyCentres) ?>;
var googleMapsApiKey = <?= json_encode($googleMapsApiKey) ?>;

var mapEngine = null; // 'google' or 'leaflet'
var gMap = null;
var gMarkers = [];
var gUserMarker = null;
var gInfoWindow = null;

var lMap = null;
var lRoadLayer = null;
var lSatLayer = null;
var lMarkers = [];
var lUserMarker = null;

function buildFacilityPopupHtml(c) {
    var accepted = Array.isArray(c.subtypes) && c.subtypes.length > 0
        ? c.subtypes.join(', ')
        : (Array.isArray(c.accepted_materials) ? c.accepted_materials.join(', ') : (c.category || 'Verified Facility'));
    var badgeLabel = (c.suitability_badge || 'Verified Specialist').replace('⭐', '').trim();
    var reasonHtml = c.suitability_reason
        ? '<div style="font-size:11px;color:#15803d;background:#f0fdf4;padding:3px 6px;margin-bottom:5px;border-left:2px solid #16a34a;">' + escapeHtml(c.suitability_reason) + '</div>'
        : '';

    return '<div style="min-width:230px;max-width:290px;font-family:DM Sans,sans-serif;line-height:1.45;color:#0f172a;">' +
        '<div style="font-size:13.5px;font-weight:700;color:#143825;margin-bottom:2px;">' + escapeHtml(c.name) + '</div>' +
        '<div style="display:flex;gap:5px;align-items:center;margin-bottom:4px;flex-wrap:wrap;">' +
            '<span style="font-size:10.5px;font-weight:700;background:#143825;color:#ffffff;padding:2px 6px;">' + escapeHtml(badgeLabel) + '</span>' +
            '<span style="font-size:10.5px;color:#475569;font-weight:700;">' + c.distance_km + ' km from My Address</span>' +
        '</div>' +
        reasonHtml +
        '<div style="font-size:11.5px;color:#334155;margin-bottom:3px;"><strong>Address:</strong> ' + escapeHtml(c.address || '') + '</div>' +
        '<div style="font-size:11.5px;color:#334155;margin-bottom:3px;"><strong>Phone:</strong> ' + escapeHtml(c.phone || 'N/A') + '</div>' +
        '<div style="font-size:11.5px;color:#334155;margin-bottom:3px;"><strong>Hours:</strong> ' + escapeHtml(c.operating_hours || 'N/A') + '</div>' +
        '<div style="font-size:11px;color:#475569;margin-bottom:8px;"><strong>Accepted Subtypes:</strong> ' + escapeHtml(accepted) + '</div>' +
        '<a href="' + escapeHtml(c.directions_url) + '" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:6px 11px;background:#143825;color:#ffffff;text-decoration:none;font-size:11.5px;font-weight:700;">Get Directions on Google Maps</a>' +
        '</div>';
}

function initInteractiveMap() {
    var mapEl = document.getElementById('ecocycle-gps-map');
    if (!mapEl) return;

    if (googleMapsApiKey && window.google && window.google.maps) {
        mapEngine = 'google';
        gMap = new google.maps.Map(mapEl, {
            center: { lat: currentLat, lng: currentLng },
            zoom: 12,
            mapTypeControl: false,
            streetViewControl: false
        });
        gInfoWindow = new google.maps.InfoWindow();
        updateMapMarkers(currentCentres);
        return;
    }

    if (typeof window.L !== 'undefined') {
        mapEngine = 'leaflet';
        lMap = L.map(mapEl).setView([currentLat, currentLng], 12);
        lMap.attributionControl.setPrefix(false);

        // Google Maps roadmap & hybrid satellite tile layers
        lRoadLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            attribution: 'Map data &copy; Google Maps'
        }).addTo(lMap);

        lSatLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            attribution: 'Map data &copy; Google Maps'
        });

        updateMapMarkers(currentCentres);
    }
}

function switchMapType(mode) {
    var btnMap = document.getElementById('btn-map-roadmap');
    var btnSat = document.getElementById('btn-map-satellite');
    if (btnMap) btnMap.classList.toggle('active', mode === 'roadmap');
    if (btnSat) btnSat.classList.toggle('active', mode === 'satellite');

    if (mapEngine === 'google' && gMap) {
        gMap.setMapTypeId(mode === 'satellite' ? 'hybrid' : 'roadmap');
    } else if (mapEngine === 'leaflet' && lMap) {
        if (mode === 'satellite') {
            if (lMap.hasLayer(lRoadLayer)) lMap.removeLayer(lRoadLayer);
            if (!lMap.hasLayer(lSatLayer)) lSatLayer.addTo(lMap);
        } else {
            if (lMap.hasLayer(lSatLayer)) lMap.removeLayer(lSatLayer);
            if (!lMap.hasLayer(lRoadLayer)) lRoadLayer.addTo(lMap);
        }
    }
}

function createPinSvg(fillColor, isHome) {
    if (isHome) {
        return '<svg width="28" height="36" viewBox="0 0 28 36" xmlns="http://www.w3.org/2000/svg">' +
            '<path d="M14 0C6.3 0 0 6.3 0 14c0 10.5 14 22 14 22s14-11.5 14-22C28 6.3 21.7 0 14 0z" fill="' + fillColor + '" stroke="#ffffff" stroke-width="2"/>' +
            '<circle cx="14" cy="14" r="5" fill="#ffffff"/>' +
            '</svg>';
    }
    return '<svg width="26" height="34" viewBox="0 0 26 34" xmlns="http://www.w3.org/2000/svg">' +
        '<path d="M13 0C5.8 0 0 5.8 0 13c0 9.4 13 21 13 21s13-11.6 13-21C26 5.8 20.2 0 13 0z" fill="' + fillColor + '" stroke="#ffffff" stroke-width="2"/>' +
        '<circle cx="13" cy="13" r="4.5" fill="#ffffff"/>' +
        '</svg>';
}

function highlightFacilityCard(centreId) {
    var cards = document.querySelectorAll('.facility-card');
    cards.forEach(function(card) { card.classList.remove('highlighted'); });
    var target = document.getElementById('facility-card-' + centreId);
    if (target) {
        target.classList.add('highlighted');
        target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function updateMapMarkers(centres) {
    currentCentres = Array.isArray(centres) ? centres : [];
    var countEl = document.getElementById('map-marker-count');
    if (countEl) {
        countEl.textContent = currentCentres.length + ' suitable facility(s)';
    }

    if (mapEngine === 'google' && gMap) {
        gMarkers.forEach(function(m) { m.setMap(null); });
        gMarkers = [];
        if (gUserMarker) gUserMarker.setMap(null);

        var bounds = new google.maps.LatLngBounds();
        var userPos = { lat: currentLat, lng: currentLng };
        gUserMarker = new google.maps.Marker({
            position: userPos,
            map: gMap,
            title: companyName + ' (My Address)',
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(createPinSvg('#2563eb', true)),
                scaledSize: new google.maps.Size(28, 36)
            }
        });
        bounds.extend(userPos);

        currentCentres.forEach(function(c, idx) {
            if (typeof c.lat !== 'number' || typeof c.lng !== 'number') return;
            var pos = { lat: c.lat, lng: c.lng };
            var isTopMatch = (c.suitability_tier === 'Most Suitable');
            var pinColor = isTopMatch ? '#143825' : '#15803d';

            var marker = new google.maps.Marker({
                position: pos,
                map: gMap,
                title: c.name + ' (' + (c.suitability_badge || 'Suitable').replace('⭐', '').trim() + ')',
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(createPinSvg(pinColor, false)),
                    scaledSize: new google.maps.Size(26, 34)
                }
            });
            marker.addListener('click', function() {
                gInfoWindow.setContent(buildFacilityPopupHtml(c));
                gInfoWindow.open(gMap, marker);
                highlightFacilityCard(c.id);
            });
            gMarkers.push(marker);
            bounds.extend(pos);
        });

        if (currentCentres.length > 0) {
            gMap.fitBounds(bounds);
        } else {
            gMap.setCenter(userPos);
            gMap.setZoom(12);
        }
        return;
    }

    if (mapEngine === 'leaflet' && lMap) {
        lMarkers.forEach(function(m) { lMap.removeLayer(m); });
        lMarkers = [];
        if (lUserMarker) lMap.removeLayer(lUserMarker);

        var userIcon = L.divIcon({
            className: 'ecocycle-map-pin',
            html: createPinSvg('#2563eb', true),
            iconSize: [28, 36],
            iconAnchor: [14, 36],
            popupAnchor: [0, -34]
        });

        lUserMarker = L.marker([currentLat, currentLng], { icon: userIcon })
            .addTo(lMap)
            .bindPopup('<strong>' + escapeHtml(companyName) + '</strong><br><span style="font-size:11.5px;color:#475569;">' + escapeHtml(companyAddress) + '</span><br><strong style="color:#2563eb;font-size:11px;">📍 My Address (Reference Point)</strong>');

        var latLngs = [[currentLat, currentLng]];

        currentCentres.forEach(function(c) {
            if (typeof c.lat !== 'number' || typeof c.lng !== 'number') return;
            var isTopMatch = (c.suitability_tier === 'Most Suitable');
            var pinColor = isTopMatch ? '#143825' : '#15803d';

            var facIcon = L.divIcon({
                className: 'ecocycle-map-pin',
                html: createPinSvg(pinColor, false),
                iconSize: [26, 34],
                iconAnchor: [13, 34],
                popupAnchor: [0, -32]
            });

            var m = L.marker([c.lat, c.lng], { icon: facIcon })
                .addTo(lMap)
                .bindPopup(buildFacilityPopupHtml(c));

            m.on('click', function() {
                highlightFacilityCard(c.id);
            });

            lMarkers.push(m);
            latLngs.push([c.lat, c.lng]);
        });

        if (latLngs.length > 1) {
            lMap.fitBounds(latLngs, { padding: [36, 36], maxZoom: 13 });
        } else {
            lMap.setView([currentLat, currentLng], 12);
        }
    }
}

function focusFacilityOnMap(index) {
    var c = currentCentres[index];
    if (!c) return;

    highlightFacilityCard(c.id);

    if (mapEngine === 'google' && gMap && gMarkers[index]) {
        gMap.panTo({ lat: c.lat, lng: c.lng });
        gMap.setZoom(14);
        gInfoWindow.setContent(buildFacilityPopupHtml(c));
        gInfoWindow.open(gMap, gMarkers[index]);
    } else if (mapEngine === 'leaflet' && lMap && lMarkers[index]) {
        lMap.setView([c.lat, c.lng], 14, { animate: true });
        lMarkers[index].openPopup();
    }
}

// When a business clicks "My Address", use the registered company address as GPS reference point
function useMyAddress() {
    currentLat = registeredLat;
    currentLng = registeredLng;


    showGpsNotice('Facility distances are measured from your registered company address.');

    fetchNearbyCentres(currentLat, currentLng, currentCategory, currentItemId);
}

// When user selects a non-reusable item from dropdown, carry forward stored AI analysis & update nearby facilities
function onSelectItem(itemId) {
    if (!itemId) {
        var emptyBox = document.getElementById('helper-empty-state');
        var strip = document.getElementById('selected-item-strip');
        var panel = document.getElementById('guidance-display-panel');
        if (emptyBox) emptyBox.style.display = 'block';
        if (strip) strip.style.display = 'none';
        if (panel) panel.style.display = 'none';
        return;
    }

    currentItemId = parseInt(itemId, 10);

    fetch('/business/helper/analyze/' + currentItemId, {
        headers: { 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data || !data.success) {
            alert((data && data.message) ? data.message : 'Unable to load material guidance.');
            return;
        }
        var selector = document.getElementById('item-selector');
        if (selector.value !== itemId) return;

        var description = selector.selectedOptions[0].dataset.description;
        var descriptionEl = document.getElementById('strip-item-description');
        descriptionEl.textContent = description;
        descriptionEl.hidden = description === '';

        renderSelectedItemGuidance(data.item, data.analysis, data.nearby_centres || []);
        if (data.item && data.item.category) {
            currentCategory = data.item.category;
            updateMapMarkers(data.nearby_centres || []);
        }
    })
    .catch(function(err) {
        console.error('Error fetching item guidance:', err);
    });
}

var helperPhotos = JSON.parse(document.getElementById('strip-item-gallery').dataset.photos);
var helperPhotoIndex = 0;

function slideHelperPhoto(direction) {
    if (helperPhotos.length > 0) {
        helperPhotoIndex = (helperPhotoIndex + direction + helperPhotos.length) % helperPhotos.length;
    }
    document.getElementById('strip-item-img').src = helperPhotos[helperPhotoIndex] || '/images/figma/ecocycle-leaf.svg';
    document.getElementById('strip-item-gallery-controls').hidden = helperPhotos.length < 2;
    document.getElementById('strip-item-photo-count').textContent = (helperPhotoIndex + 1) + ' / ' + helperPhotos.length;
}

function renderSelectedItemGuidance(item, a, centres) {
    var emptyBox = document.getElementById('helper-empty-state');
    if (emptyBox) emptyBox.style.display = 'none';

    var strip = document.getElementById('selected-item-strip');
    if (strip) strip.style.display = 'flex';

    var panel = document.getElementById('guidance-display-panel');
    if (panel) panel.style.display = 'flex';

    helperPhotos = item.images || [];
    helperPhotoIndex = 0;
    slideHelperPhoto(0);

    var catEl = document.getElementById('strip-item-category');
    if (catEl) catEl.textContent = item.category || 'Non-Reusable';

    var qtyEl = document.getElementById('strip-item-qty');
    if (qtyEl) qtyEl.textContent = (item.quantity || 0) + ' ' + (item.unit || '');

    var condEl = document.getElementById('strip-item-condition');
    if (condEl) condEl.textContent = item.condition || '';

    var nameEl = document.getElementById('strip-item-name');
    if (nameEl) nameEl.textContent = item.name || 'Non-Reusable Material';

    // 1. Handling & Strategy
    var valBadge = document.getElementById('guidance-value-badge');
    if (valBadge) valBadge.textContent = a.estimated_value_myr || 'Industrial Waste Recovery';

    var handlingText = document.getElementById('guidance-handling-text');
    if (handlingText) {
        var str = (a.action_recommendation || '') + (a.strategy ? ' — ' + a.strategy : '');
        handlingText.textContent = str;
    }

    // 2. Action steps
    var actionsEl = document.getElementById('guidance-owner-actions');
    if (actionsEl) {
        var steps = (a.owner_actions && a.owner_actions.length) ? a.owner_actions : (a.recycling_steps || []);
        actionsEl.innerHTML = steps.map(function(s) {
            return '<li>' + escapeHtml(s) + '</li>';
        }).join('');
    }

    // 3. Alternative repurposing
    var repurposeBox = document.getElementById('guidance-repurpose-box');
    var repurposeEl = document.getElementById('guidance-repurpose-ideas');
    if (repurposeBox && repurposeEl) {
        var ideas = a.repurpose_ideas || [];
        if (ideas.length > 0) {
            repurposeBox.style.display = 'block';
            repurposeEl.innerHTML = ideas.map(function(i) {
                return '<li>' + escapeHtml(i) + '</li>';
            }).join('');
        } else {
            repurposeBox.style.display = 'none';
        }
    }

    // 4. Qualitative environmental & carbon impact
    var envImpact = document.getElementById('guidance-env-impact');
    if (envImpact) envImpact.textContent = a.environmental_impact || 'Proper segregation and licensed recovery reduce landfill burden and prevent pollution.';

    var carbonImpact = document.getElementById('guidance-carbon-impact');
    if (carbonImpact) carbonImpact.textContent = a.carbon_impact || a.carbon_saving_estimate || 'Diverting non-reusable material to verified recycling avoids landfill emissions.';

    // 5. Risks
    var risksText = document.getElementById('guidance-risks-text');
    if (risksText) risksText.textContent = a.improper_disposal_risks || a.disposal_warning || 'Unauthorized dumping violates Environmental Quality Act regulations.';

    // Render suitable nearby facilities in right column list
    renderNearbyCentres(centres);
}

function showGpsNotice(message) {
    var banner = document.getElementById('gps-notice-banner');
    if (!banner) return;
    if (!message) {
        banner.style.display = 'none';
        banner.textContent = '';
        return;
    }
    banner.textContent = message;
    banner.style.display = 'block';
}

function fetchNearbyCentres(lat, lng, category, itemId) {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    fetch('/business/helper/nearby-centres', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify({ lat: lat, lng: lng, category: category, item_id: itemId })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            renderNearbyCentres(data.centres || [], data.alternative_recommendations || []);
            updateMapMarkers(data.centres || []);
        }
    })
    .catch(function(e) {
        console.error('Failed to fetch nearby centres:', e);
    });
}

function renderNearbyCentres(centres, alternatives) {
    var container = document.getElementById('facility-list-container');
    if (!container) return;

    if (!centres || centres.length === 0) {
        var altList = (Array.isArray(alternatives) && alternatives.length > 0) ? alternatives : [
            'Contact your local council (PBT) or state solid waste authority (SWCorp / KDEB) for approved disposal points.',
            'Verify licensing under DOE Malaysia before transporting specialized or contaminated materials.',
            'Keep the material segregated, dry, and clearly labelled until an authorized facility confirms acceptance.'
        ];
        container.innerHTML = '<div style="padding:16px;background:#fffbeb;border:1px solid #fde68a;border-left:3px solid #b45309;color:#78350f;font-size:12.5px;line-height:1.55;">' +
            '<strong style="display:block;color:#92400e;margin-bottom:6px;">No Verified Facilities Found Nearby</strong>' +
            'No verified facilities in our directory confirm acceptance for this material category near your registered location:' +
            '<ul style="margin:6px 0 0;padding-left:18px;">' +
            altList.map(function(a) { return '<li>' + escapeHtml(a) + '</li>'; }).join('') +
            '</ul></div>';
        return;
    }

    container.innerHTML = centres.map(function(c, idx) {
        var badgeClass = 'alternative';
        if (c.suitability_tier === 'Most Suitable') {
            badgeClass = 'most-suitable';
        } else if (c.suitability_tier === 'Highly Suitable') {
            badgeClass = 'highly-suitable';
        }

        var reasonHtml = c.suitability_reason
            ? '<div class="suitability-reason-text">' + escapeHtml(c.suitability_reason) + '</div>'
            : '';

        var subtypesHtml = (c.subtypes && c.subtypes.length)
            ? '<div style="margin-top:2px;color:#475569;"><strong>Specialized Subtypes:</strong> ' + escapeHtml(c.subtypes.join(', ')) + '</div>'
            : ((c.accepted_materials && c.accepted_materials.length)
                ? '<div style="margin-top:2px;color:#475569;"><strong>Accepts:</strong> ' + escapeHtml(c.accepted_materials.join(', ')) + '</div>'
                : '');

        return '<article class="facility-card" id="facility-card-' + c.id + '" onclick="focusFacilityOnMap(' + idx + ')">' +
            '<div class="facility-header">' +
                '<div>' +
                    '<h3 class="facility-name">' + escapeHtml(c.name) + '</h3>' +
                    '<div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:3px;">' +
                        '<span class="suitability-pill ' + badgeClass + '">' + escapeHtml((c.suitability_badge || 'Verified Specialist').replace('⭐', '').trim()) + '</span>' +
                        '<span style="font-size:10.5px;background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;padding:2px 6px;font-weight:700;">' + escapeHtml(c.license) + '</span>' +
                    '</div>' +
                '</div>' +
                '<span class="distance-pill">' + c.distance_km + ' km from My Address</span>' +
            '</div>' +
            reasonHtml +
            '<div class="facility-meta">' +
                '<div><strong>Address:</strong> ' + escapeHtml(c.address) + '</div>' +
                '<div style="margin-top:2px;"><strong>Phone:</strong> ' + escapeHtml(c.phone) + ' &middot; <strong>Hours:</strong> ' + escapeHtml(c.operating_hours) + '</div>' +
                subtypesHtml +
            '</div>' +
            '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:4px;">' +
                '<a href="' + escapeHtml(c.directions_url) + '" target="_blank" rel="noopener noreferrer" class="btn-directions" onclick="event.stopPropagation();">' +
                    'Get Directions on Google Maps' +
                '</a>' +
            '</div>' +
        '</article>';
    }).join('');
}

function escapeHtml(text) {
    var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

window.initEcoCycleGoogleMap = initInteractiveMap;
document.addEventListener('DOMContentLoaded', function() {
    if (!googleMapsApiKey) {
        initInteractiveMap();
    }
});
</script>
<?php if ($googleMapsApiKey !== ''): ?>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= urlencode($googleMapsApiKey) ?>&callback=initEcoCycleGoogleMap"></script>
<?php endif; ?>
</body>
</html>
