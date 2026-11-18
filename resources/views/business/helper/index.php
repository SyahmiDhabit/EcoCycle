<?php

use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/** @var User $business */
/** @var Collection<int, Item> $items */
/** @var Item|null $selectedItem */
/** @var array<string, mixed>|null $initialAnalysis */
/** @var array<string, float> $defaultCoords */
/** @var array<int, array<string, mixed>> $nearbyCentres */
/** @var string $googleMapsApiKey */

$companyName = $business->business_name ?: ($business->name ?? 'EcoCycle Business');
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
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <?php if ($googleMapsApiKey === ''): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <?php endif; ?>
    <style>
        .helper-grid {
            display: grid;
            grid-template-columns: 1.08fr 0.92fr;
            gap: 20px;
            align-items: start;
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
            max-width: 460px;
            font-size: 13px;
            color: #526658;
            line-height: 1.55;
        }
        .selected-item-strip {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            background: #ffffff;
            border-bottom: 1px solid var(--card-border);
        }
        .selected-item-thumb {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border: 1px solid var(--card-border);
            flex-shrink: 0;
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

        /* Chat Interface */
        .chat-messages {
            padding: 16px 18px;
            max-height: 520px;
            min-height: 300px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: #f8faf8;
        }
        .chat-bubble {
            max-width: 92%;
            padding: 12px 15px;
            border-radius: 0;
            font-size: 13px;
            line-height: 1.55;
        }
        .chat-bubble.assistant {
            align-self: flex-start;
            background: #ffffff;
            border: 1px solid var(--card-border);
            color: #1f2937;
            width: 100%;
            box-sizing: border-box;
        }
        .chat-bubble.user {
            align-self: flex-end;
            background: var(--forest-bg);
            color: #ffffff;
            border: 1px solid var(--forest-bg);
            white-space: pre-wrap;
        }
        .chat-guidance-block {
            margin-top: 10px;
            padding: 10px 12px;
            background: #f9fbf9;
            border: 1px solid #d5e2d5;
            border-left: 3px solid #143825;
        }
        .chat-guidance-block h5 {
            margin: 0 0 5px;
            font-size: 12px;
            font-weight: 700;
            color: #143825;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .chat-guidance-list {
            margin: 0;
            padding-left: 18px;
            font-size: 12.5px;
            color: #334155;
            line-height: 1.55;
        }
        .chat-guidance-list li {
            margin-bottom: 4px;
        }
        .chat-prompts {
            padding: 10px 16px;
            background: #f4f7f3;
            border-top: 1px solid var(--card-border);
            display: flex;
            gap: 6px;
            overflow-x: auto;
            white-space: nowrap;
        }
        .prompt-chip {
            padding: 6px 11px;
            border-radius: 0;
            background: #ffffff;
            border: 1px solid var(--input-border);
            font-size: 11.5px;
            font-weight: 600;
            color: var(--text-primary);
            cursor: pointer;
            flex-shrink: 0;
        }
        .prompt-chip:hover {
            background: #eef6ec;
            border-color: var(--brand-green);
            color: var(--brand-green);
        }
        .chat-input-bar {
            padding: 12px 16px;
            display: flex;
            gap: 8px;
            background: #ffffff;
            border-top: 1px solid var(--card-border);
        }
        .chat-input-bar input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid var(--input-border);
            border-radius: 0;
            font-size: 13px;
            outline: none;
        }
        .chat-input-bar input:focus {
            border-color: var(--brand-green);
        }
        .chat-send-btn {
            background: var(--forest-bg);
            color: #ffffff;
            border: 1px solid var(--forest-bg);
            padding: 0 18px;
            border-radius: 0;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .chat-send-btn:hover { background: #0d2b1b; }

        /* Google Maps GPS Panel */
        .gps-controls {
            padding: 12px 16px;
            background: var(--surface-subtle);
            border-bottom: 1px solid var(--card-border);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .gps-btn-live {
            background: var(--forest-bg);
            color: #ffffff;
            border: 1px solid var(--forest-bg);
            padding: 8px 13px;
            border-radius: 0;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .gps-btn-live:hover { background: #0d2b1b; }
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
            height: 330px;
            background: #e5ece5;
            border-bottom: 1px solid var(--card-border);
            z-index: 1;
        }
        .facility-list {
            padding: 14px 16px;
            max-height: 390px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
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
        }
        .facility-card:hover {
            border-color: var(--brand-green);
            background: #fafdfa;
        }
        .facility-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
        }
        .facility-name {
            font-size: 13px;
            font-weight: 700;
            color: #123321;
            margin: 0 0 3px;
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
        .facility-meta {
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.45;
        }
        .btn-directions {
            margin-top: 3px;
            background: var(--forest-bg);
            color: #ffffff;
            border: 1px solid var(--forest-bg);
            text-decoration: none;
            font-size: 11px;
            font-weight: 600;
            padding: 6px 11px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-directions:hover { background: #0d2b1b; }
        .leaflet-container {
            font-family: var(--font-main) !important;
        }
    </style>
</head>
<body class="business-dashboard-page">
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <?php include resource_path('views/business/partials/sidebar.php'); ?>

        <main class="dashboard-main">
            <?php if (session('status')): ?>
                <div role="status" style="margin-bottom:16px;padding:12px 16px;background:#eaf3e8;border:1px solid #c7dbc5;border-left:3px solid #176b40;color:#176b40;font-size:13px;font-weight:600;">
                    <?= e(session('status')) ?>
                </div>
            <?php endif; ?>

            <!-- Header Banner -->
            <div class="page-header">
                <div>
                    <h1>EcoCycle Helper</h1>
                    <p>This material has been classified as non-reusable. EcoCycle Helper provides recommendations on how to manage, recycle or dispose of the material properly.</p>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="/business/inventory" class="btn-reset" style="display:inline-flex;align-items:center;">
                        <span>Inventory Items</span>
                    </a>
                </div>
            </div>

            <!-- Main Helper Grid: Left = Material Analysis & Chat Guidance, Right = Interactive Google Maps GPS Locator -->
            <div class="helper-grid">
                <!-- Left Column: Non-Reusable Item Selector + Chat Guidance -->
                <section class="helper-card">
                    <div class="helper-card-header">
                        <h2 class="helper-card-title">Material Analysis &amp; Disposal Guidance</h2>
                        <span style="font-size:11px;color:#92400e;font-weight:700;background:#fef3c7;border:1px solid #fde68a;padding:3px 9px;">
                            Non-Reusable Items Only
                        </span>
                    </div>

                    <!-- Strictly Non-Reusable Inventory Selector -->
                    <div class="item-select-bar">
                        <label for="item-selector" style="font-size:12px;font-weight:700;color:var(--text-primary);white-space:nowrap;">Non-Reusable Item:</label>
                        <select id="item-selector" onchange="onSelectItem(this.value)">
                            <?php if ($items->isEmpty()): ?>
                                <option value="">No Non-Reusable items in your inventory yet</option>
                            <?php else: ?>
                                <option value="">-- Select a Non-Reusable item to open chat guidance --</option>
                                <?php foreach ($items as $it): ?>
                                    <option value="<?= $it->id ?>" <?= $selectedItem && $selectedItem->id === $it->id ? 'selected' : '' ?>>
                                        <?= e($it->name ?: $it->title) ?> (<?= (float) $it->quantity ?> <?= e($it->unit) ?> &middot; <?= e($it->category) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Initial Welcome Prompt (Hidden once an item is selected or a question is submitted) -->
                    <div id="helper-welcome-state" class="helper-welcome-box" style="<?= $hasSelected ? 'display:none;' : '' ?>">
                        <h3>Select a Non-Reusable Item or Ask a Question</h3>
                        <p>
                            EcoCycle Helper exclusively supports <strong>Non-Reusable</strong> materials from your inventory. Choose an item above or type a question below to open the guidance chat with recycling steps, safe disposal methods, and verified nearby facilities.
                        </p>
                        <?php if ($items->isEmpty()): ?>
                            <div style="margin-bottom:16px;padding:10px 14px;background:#ffffff;border:1px solid #cbd5e1;font-size:12.5px;color:#475569;display:inline-block;">
                                No Non-Reusable items found in your inventory yet. Add an item in <a href="/business/inventory?add=1" style="color:#143825;font-weight:700;">Inventory Items</a> or ask a general disposal question below.
                            </div>
                        <?php endif; ?>
                        <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
                            <button type="button" class="prompt-chip" onclick="askQuickPrompt(this)">How should we dispose of contaminated plastic packaging?</button>
                            <button type="button" class="prompt-chip" onclick="askQuickPrompt(this)">What are DOE rules for industrial e-waste in Malaysia?</button>
                            <button type="button" class="prompt-chip" onclick="askQuickPrompt(this)">How do we manage spoiled organic waste safely?</button>
                        </div>
                    </div>

                    <!-- Selected Non-Reusable Item Summary Strip -->
                    <div id="selected-item-strip" class="selected-item-strip" style="<?= $hasSelected ? '' : 'display:none;' ?>">
                        <img id="strip-item-img" src="<?= $hasSelected && $selectedItem->getPrimaryImage() ? e($selectedItem->getPrimaryImage()) : '/images/figma/ecocycle-leaf.svg' ?>" alt="Selected material" class="selected-item-thumb">
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:4px;">
                                <span class="metric-badge amber">Non-Reusable</span>
                                <span class="metric-badge green" id="strip-item-category"><?= $hasSelected ? e($selectedItem->category) : '' ?></span>
                                <span class="metric-badge blue" id="strip-item-qty"><?= $hasSelected ? ((float) $selectedItem->quantity).' '.e($selectedItem->unit) : '' ?></span>
                            </div>
                            <strong id="strip-item-name" style="font-size:14px;color:#123321;display:block;"><?= $hasSelected ? e($selectedItem->name ?: $selectedItem->title) : '' ?></strong>
                        </div>
                    </div>

                    <!-- Interactive Chat Interface (Opens when an item is selected OR when a question is submitted) -->
                    <div id="helper-chat-panel" style="<?= $hasSelected ? 'display:flex;flex-direction:column;' : 'display:none;flex-direction:column;' ?>">
                        <div class="chat-messages" id="chat-messages">
                            <?php if ($hasSelected && $initialAnalysis): ?>
                                <div class="chat-bubble assistant">
                                    <div style="font-weight:800;font-size:14px;color:#143825;margin-bottom:4px;">
                                        Material Analysis &amp; Recommendations: <?= e($selectedItem->name ?: $selectedItem->title) ?>
                                    </div>
                                    <p style="margin:0 0 8px;color:#334155;">
                                        <strong>Recommended Handling:</strong> <?= e($initialAnalysis['action_recommendation'] ?? '') ?> &mdash; <?= e($initialAnalysis['strategy'] ?? '') ?>
                                    </p>

                                    <div class="chat-guidance-block">
                                        <h5>What Can the Business Owner Do?</h5>
                                        <ol class="chat-guidance-list">
                                            <?php foreach (($initialAnalysis['owner_actions'] ?? $initialAnalysis['recycling_steps'] ?? []) as $act): ?>
                                                <li><?= e($act) ?></li>
                                            <?php endforeach; ?>
                                        </ol>
                                    </div>

                                    <?php if (! empty($initialAnalysis['repurpose_ideas'])): ?>
                                        <div class="chat-guidance-block">
                                            <h5>Alternative Recovery &amp; Repurposing Options</h5>
                                            <ul class="chat-guidance-list">
                                                <?php foreach ($initialAnalysis['repurpose_ideas'] as $idea): ?>
                                                    <li><?= e($idea) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:10px;">
                                        <div style="padding:9px 11px;background:#f4fbf5;border:1px solid #c2dec0;">
                                            <strong style="display:block;font-size:10.5px;text-transform:uppercase;color:#1b5e20;margin-bottom:3px;">Environmental Impact (Qualitative)</strong>
                                            <span style="font-size:12px;color:#123321;"><?= e($initialAnalysis['environmental_impact'] ?? 'Proper segregation and licensed recovery reduce landfill burden and prevent pollution.') ?></span>
                                        </div>
                                        <div style="padding:9px 11px;background:#eff6ff;border:1px solid #bfdbfe;">
                                            <strong style="display:block;font-size:10.5px;text-transform:uppercase;color:#1d4ed8;margin-bottom:3px;">Carbon Impact Guidance (Qualitative)</strong>
                                            <span style="font-size:12px;color:#1e3a8a;"><?= e($initialAnalysis['carbon_impact'] ?? $initialAnalysis['carbon_saving_estimate'] ?? 'Diverting non-reusable material to verified recycling helps reduce emissions compared to landfill disposal.') ?></span>
                                        </div>
                                    </div>

                                    <div style="margin-top:10px;padding:9px 11px;background:#fffbeb;border:1px solid #fde68a;border-left:3px solid #b45309;font-size:12px;color:#78350f;">
                                        <strong>Disposal Risks &amp; Compliance:</strong> <?= e($initialAnalysis['improper_disposal_risks'] ?? $initialAnalysis['disposal_warning'] ?? '') ?>
                                    </div>

                                    <div class="chat-guidance-block" style="margin-top:10px;">
                                        <h5>Suitable Verified Facilities Nearby</h5>
                                        <?php if (! empty($nearbyCentres)): ?>
                                            <ul class="chat-guidance-list">
                                                <?php foreach (array_slice($nearbyCentres, 0, 3) as $c): ?>
                                                    <li><strong><?= e($c['name']) ?></strong> (<?= $c['distance_km'] ?> km) &mdash; <?= e($c['address']) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <div style="font-size:11.5px;color:#526658;margin-top:4px;">Click any marker on the interactive map on the right for full facility details and Google Maps directions.</div>
                                        <?php else: ?>
                                            <p style="margin:0;font-size:12px;color:#92400e;">No verified facility in our directory explicitly matches this category near your current location. Check your local council (PBT) or DOE Malaysia licensed collectors before transport.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Quick Follow-Up Prompt Chips -->
                        <div class="chat-prompts">
                            <button type="button" class="prompt-chip" onclick="askQuickPrompt(this)">How should we store and prepare this before pickup?</button>
                            <button type="button" class="prompt-chip" onclick="askQuickPrompt(this)">Are there DOE or licensing requirements for this material?</button>
                            <button type="button" class="prompt-chip" onclick="askQuickPrompt(this)">What are the best recycling and repurposing options?</button>
                            <button type="button" class="prompt-chip" onclick="askQuickPrompt(this)">Which nearby verified facility should we contact?</button>
                        </div>
                    </div>

                    <!-- Always-Accessible Chat Input Bar (Submitting a question opens the chat interface immediately) -->
                    <form class="chat-input-bar" id="chat-form" onsubmit="handleChatSubmit(event)">
                        <input type="text" id="chat-input" placeholder="Ask about recycling methods, safe disposal, DOE rules, or nearby facilities..." autocomplete="off">
                        <button type="submit" class="chat-send-btn" id="chat-send-btn">Send</button>
                    </form>
                </section>

                <!-- Right Column: Interactive Google Maps GPS Locator -->
                <section class="helper-card">
                    <div class="helper-card-header">
                        <h2 class="helper-card-title">Google Maps GPS Locator</h2>
                        <span id="gps-status-pill" style="font-size:11px;color:#1e40af;background:#eff6ff;border:1px solid #bfdbfe;padding:3px 9px;font-weight:700;">
                            <?= e($business->city ?: 'Shah Alam') ?>, <?= e($business->state ?: 'Selangor') ?>
                        </span>
                    </div>

                    <!-- GPS Controls & Map Layer Toggle -->
                    <div class="gps-controls">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                            <button type="button" class="gps-btn-live" id="btn-request-gps" onclick="requestBrowserGPS()">
                                Use My Current GPS Location
                            </button>

                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                <select id="location-select" onchange="onSelectCity(this.value)" aria-label="Select Malaysian City" style="padding:6px 9px;border:1px solid var(--input-border);font-size:12px;background:#ffffff;color:#0f172a;">
                                    <option value="shah alam">Shah Alam, Selangor</option>
                                    <option value="petaling jaya">Petaling Jaya, Selangor</option>
                                    <option value="klang">Klang, Selangor</option>
                                    <option value="subang jaya">Subang Jaya, Selangor</option>
                                    <option value="kuala lumpur">Kuala Lumpur</option>
                                    <option value="perai">Perai / Penang</option>
                                    <option value="sungai petani">Sungai Petani, Kedah</option>
                                    <option value="ipoh">Ipoh, Perak</option>
                                    <option value="johor bahru">Johor Bahru, Johor</option>
                                    <option value="pasir gudang">Pasir Gudang, Johor</option>
                                    <option value="kuching">Kuching, Sarawak</option>
                                    <option value="kota kinabalu">Kota Kinabalu, Sabah</option>
                                </select>
                                <div style="display:inline-flex;">
                                    <button type="button" id="btn-map-roadmap" class="map-mode-btn active" onclick="switchMapType('roadmap')">Map</button>
                                    <button type="button" id="btn-map-satellite" class="map-mode-btn" onclick="switchMapType('satellite')" style="border-left:none;">Satellite</button>
                                </div>
                            </div>
                        </div>

                        <div id="gps-notice-banner" style="display:none;padding:8px 11px;background:#fffbeb;border:1px solid #fde68a;border-left:3px solid #b45309;color:#92400e;font-size:12px;line-height:1.45;"></div>

                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:11.5px;color:#526658;flex-wrap:wrap;gap:6px;">
                            <span>Click any map marker to inspect facility details &amp; Google Maps directions.</span>
                            <span id="map-marker-count" style="font-weight:700;color:#143825;"><?= count($nearbyCentres) ?> verified facility(s)</span>
                        </div>
                    </div>

                    <!-- Interactive Google Maps Container -->
                    <div id="ecocycle-gps-map" aria-label="Interactive Google Maps Recycling Facilities Locator"></div>

                    <!-- Verified Facility List (Clicking a card focuses its marker on the map) -->
                    <div class="facility-list" id="facility-list-container">
                        <?php if (empty($nearbyCentres)): ?>
                            <div style="padding:16px;background:#fffbeb;border:1px solid #fde68a;border-left:3px solid #b45309;color:#78350f;font-size:12.5px;line-height:1.55;">
                                <strong style="display:block;color:#92400e;margin-bottom:4px;">No Verified Nearby Facility Found</strong>
                                No verified recycling or disposal centre in our directory explicitly matches this material near your selected location.
                            </div>
                        <?php else: ?>
                            <?php foreach ($nearbyCentres as $idx => $centre): ?>
                                <article class="facility-card" onclick="focusFacilityOnMap(<?= $idx ?>)">
                                    <div class="facility-header">
                                        <div>
                                            <h3 class="facility-name"><?= e($centre['name']) ?></h3>
                                            <span style="font-size:10.5px;background:#e8f4ec;color:#1e5e3a;border:1px solid #c2dec0;padding:2px 6px;font-weight:700;">
                                                Verified &middot; <?= e($centre['license']) ?>
                                            </span>
                                        </div>
                                        <span class="distance-pill"><?= $centre['distance_km'] ?> km</span>
                                    </div>
                                    <div class="facility-meta">
                                        <div><strong>Address:</strong> <?= e($centre['address']) ?></div>
                                        <div style="margin-top:2px;"><strong>Phone:</strong> <?= e($centre['phone']) ?> &middot; <strong>Hours:</strong> <?= e($centre['operating_hours']) ?></div>
                                    </div>
                                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:2px;">
                                        <a href="<?= e($centre['directions_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn-directions" onclick="event.stopPropagation();">
                                            Get Directions on Google Maps &rarr;
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
var currentLat = <?= (float) $defaultCoords['lat'] ?>;
var currentLng = <?= (float) $defaultCoords['lng'] ?>;
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

function openChatInterface() {
    var welcome = document.getElementById('helper-welcome-state');
    var chatPanel = document.getElementById('helper-chat-panel');
    if (welcome) welcome.style.display = 'none';
    if (chatPanel) chatPanel.style.display = 'flex';
}

function buildFacilityPopupHtml(c) {
    var accepted = Array.isArray(c.subtypes) && c.subtypes.length > 0 ? c.subtypes.join(', ') : (c.category || 'Verified Facility');
    return '<div style="min-width:220px;max-width:270px;font-family:DM Sans,sans-serif;line-height:1.45;color:#0f172a;">' +
        '<div style="font-size:13px;font-weight:700;color:#143825;margin-bottom:3px;">' + escapeHtml(c.name) + '</div>' +
        '<div style="font-size:11px;font-weight:700;color:#15803d;margin-bottom:5px;">Verified &middot; ' + escapeHtml(c.license || 'Licensed Facility') + ' (' + c.distance_km + ' km)</div>' +
        '<div style="font-size:11.5px;color:#334155;margin-bottom:3px;"><strong>Address:</strong> ' + escapeHtml(c.address || '') + '</div>' +
        '<div style="font-size:11.5px;color:#334155;margin-bottom:3px;"><strong>Phone:</strong> ' + escapeHtml(c.phone || 'N/A') + '</div>' +
        '<div style="font-size:11.5px;color:#334155;margin-bottom:3px;"><strong>Hours:</strong> ' + escapeHtml(c.operating_hours || 'N/A') + '</div>' +
        '<div style="font-size:11px;color:#475569;margin-bottom:8px;"><strong>Accepts:</strong> ' + escapeHtml(accepted) + '</div>' +
        '<a href="' + escapeHtml(c.directions_url) + '" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:6px 10px;background:#143825;color:#ffffff;text-decoration:none;font-size:11.5px;font-weight:700;">Open in Google Maps &rarr;</a>' +
        '</div>';
}

function initInteractiveMap() {
    var mapEl = document.getElementById('ecocycle-gps-map');
    if (!mapEl) return;

    if (googleMapsApiKey && window.google && window.google.maps) {
        mapEngine = 'google';
        gMap = new google.maps.Map(mapEl, {
            center: { lat: currentLat, lng: currentLng },
            zoom: 11,
            mapTypeControl: false,
            streetViewControl: false
        });
        gInfoWindow = new google.maps.InfoWindow();
        updateMapMarkers(currentCentres);
        return;
    }

    if (typeof window.L !== 'undefined') {
        mapEngine = 'leaflet';
        lMap = L.map(mapEl).setView([currentLat, currentLng], 11);

        // Google Maps roadmap & hybrid satellite tile layers for familiar Google Maps visual experience
        lRoadLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            attribution: 'Map data &copy; Google'
        }).addTo(lMap);

        lSatLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            attribution: 'Map data &copy; Google'
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

function createPinSvg(fillColor) {
    return '<svg width="26" height="34" viewBox="0 0 26 34" xmlns="http://www.w3.org/2000/svg">' +
        '<path d="M13 0C5.8 0 0 5.8 0 13c0 9.4 13 21 13 21s13-11.6 13-21C26 5.8 20.2 0 13 0z" fill="' + fillColor + '" stroke="#ffffff" stroke-width="2"/>' +
        '<circle cx="13" cy="13" r="4.5" fill="#ffffff"/>' +
        '</svg>';
}

function updateMapMarkers(centres) {
    currentCentres = Array.isArray(centres) ? centres : [];
    var countEl = document.getElementById('map-marker-count');
    if (countEl) {
        countEl.textContent = currentCentres.length + ' verified facility(s)';
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
            title: 'Your Location',
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(createPinSvg('#2563eb')),
                scaledSize: new google.maps.Size(26, 34)
            }
        });
        bounds.extend(userPos);

        currentCentres.forEach(function(c) {
            if (typeof c.lat !== 'number' || typeof c.lng !== 'number') return;
            var pos = { lat: c.lat, lng: c.lng };
            var marker = new google.maps.Marker({
                position: pos,
                map: gMap,
                title: c.name,
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(createPinSvg('#143825')),
                    scaledSize: new google.maps.Size(26, 34)
                }
            });
            marker.addListener('click', function() {
                gInfoWindow.setContent(buildFacilityPopupHtml(c));
                gInfoWindow.open(gMap, marker);
            });
            gMarkers.push(marker);
            bounds.extend(pos);
        });

        if (currentCentres.length > 0) {
            gMap.fitBounds(bounds);
        } else {
            gMap.setCenter(userPos);
            gMap.setZoom(11);
        }
        return;
    }

    if (mapEngine === 'leaflet' && lMap) {
        lMarkers.forEach(function(m) { lMap.removeLayer(m); });
        lMarkers = [];
        if (lUserMarker) lMap.removeLayer(lUserMarker);

        var userIcon = L.divIcon({
            className: 'ecocycle-map-pin',
            html: createPinSvg('#2563eb'),
            iconSize: [26, 34],
            iconAnchor: [13, 34],
            popupAnchor: [0, -32]
        });

        lUserMarker = L.marker([currentLat, currentLng], { icon: userIcon })
            .addTo(lMap)
            .bindPopup('<strong>Your Location</strong><br>Searching verified recycling facilities nearby.');

        var latLngs = [[currentLat, currentLng]];

        var facIcon = L.divIcon({
            className: 'ecocycle-map-pin',
            html: createPinSvg('#143825'),
            iconSize: [26, 34],
            iconAnchor: [13, 34],
            popupAnchor: [0, -32]
        });

        currentCentres.forEach(function(c) {
            if (typeof c.lat !== 'number' || typeof c.lng !== 'number') return;
            var m = L.marker([c.lat, c.lng], { icon: facIcon })
                .addTo(lMap)
                .bindPopup(buildFacilityPopupHtml(c));
            lMarkers.push(m);
            latLngs.push([c.lat, c.lng]);
        });

        if (latLngs.length > 1) {
            lMap.fitBounds(latLngs, { padding: [32, 32], maxZoom: 13 });
        } else {
            lMap.setView([currentLat, currentLng], 11);
        }
    }
}

function focusFacilityOnMap(index) {
    var c = currentCentres[index];
    if (!c) return;

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

function onSelectItem(itemId) {
    if (!itemId) return;
    currentItemId = parseInt(itemId, 10);
    openChatInterface();

    appendAssistantHtml('<span style="color:#64748b;">Analyzing selected Non-Reusable material and matching verified nearby facilities...</span>');

    fetch('/business/helper/analyze/' + currentItemId, {
        headers: { 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data || !data.success) {
            appendAssistantBubble((data && data.message) ? data.message : 'EcoCycle Helper is only available for Non-Reusable items.');
            return;
        }
        renderSelectedItemIntoChat(data.item, data.analysis, data.nearby_centres || []);
        if (data.item && data.item.category) {
            currentCategory = data.item.category;
            fetchNearbyCentres(currentLat, currentLng, currentCategory);
        }
    })
    .catch(function() {
        appendAssistantBubble('Unable to load material guidance right now. Please try again.');
    });
}

function renderSelectedItemIntoChat(item, a, centres) {
    var strip = document.getElementById('selected-item-strip');
    if (strip) strip.style.display = 'flex';
    var imgEl = document.getElementById('strip-item-img');
    if (imgEl) imgEl.src = item.image_path || '/images/figma/ecocycle-leaf.svg';
    var catEl = document.getElementById('strip-item-category');
    if (catEl) catEl.textContent = item.category || 'Non-Reusable';
    var qtyEl = document.getElementById('strip-item-qty');
    if (qtyEl) qtyEl.textContent = (item.quantity || 0) + ' ' + (item.unit || '');
    var nameEl = document.getElementById('strip-item-name');
    if (nameEl) nameEl.textContent = item.name || 'Non-Reusable Material';

    var ownerActs = (a.owner_actions && a.owner_actions.length) ? a.owner_actions : (a.recycling_steps || []);
    var repIdeas = a.repurpose_ideas || [];

    var html = '<div style="font-weight:800;font-size:14px;color:#143825;margin-bottom:4px;">Material Analysis &amp; Recommendations: ' + escapeHtml(item.name) + '</div>' +
        '<p style="margin:0 0 8px;color:#334155;"><strong>Recommended Handling:</strong> ' + escapeHtml(a.action_recommendation || '') + ' &mdash; ' + escapeHtml(a.strategy || '') + '</p>';

    if (ownerActs.length > 0) {
        html += '<div class="chat-guidance-block"><h5>What Can the Business Owner Do?</h5><ol class="chat-guidance-list">' +
            ownerActs.map(function(s) { return '<li>' + escapeHtml(s) + '</li>'; }).join('') +
            '</ol></div>';
    }

    if (repIdeas.length > 0) {
        html += '<div class="chat-guidance-block"><h5>Alternative Recovery &amp; Repurposing Options</h5><ul class="chat-guidance-list">' +
            repIdeas.map(function(i) { return '<li>' + escapeHtml(i) + '</li>'; }).join('') +
            '</ul></div>';
    }

    html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:10px;">' +
        '<div style="padding:9px 11px;background:#f4fbf5;border:1px solid #c2dec0;">' +
            '<strong style="display:block;font-size:10.5px;text-transform:uppercase;color:#1b5e20;margin-bottom:3px;">Environmental Impact (Qualitative)</strong>' +
            '<span style="font-size:12px;color:#123321;">' + escapeHtml(a.environmental_impact || 'Proper recovery reduces landfill impact and prevents pollution.') + '</span>' +
        '</div>' +
        '<div style="padding:9px 11px;background:#eff6ff;border:1px solid #bfdbfe;">' +
            '<strong style="display:block;font-size:10.5px;text-transform:uppercase;color:#1d4ed8;margin-bottom:3px;">Carbon Impact Guidance (Qualitative)</strong>' +
            '<span style="font-size:12px;color:#1e3a8a;">' + escapeHtml(a.carbon_impact || a.carbon_saving_estimate || 'Diverting non-reusable material to verified recycling reduces emissions compared to landfilling.') + '</span>' +
        '</div>' +
    '</div>';

    if (a.improper_disposal_risks || a.disposal_warning) {
        html += '<div style="margin-top:10px;padding:9px 11px;background:#fffbeb;border:1px solid #fde68a;border-left:3px solid #b45309;font-size:12px;color:#78350f;">' +
            '<strong>Disposal Risks &amp; Compliance:</strong> ' + escapeHtml(a.improper_disposal_risks || a.disposal_warning) +
        '</div>';
    }

    html += '<div class="chat-guidance-block" style="margin-top:10px;"><h5>Suitable Verified Facilities Nearby</h5>';
    if (centres && centres.length > 0) {
        html += '<ul class="chat-guidance-list">' +
            centres.slice(0, 3).map(function(c) {
                return '<li><strong>' + escapeHtml(c.name) + '</strong> (' + c.distance_km + ' km) &mdash; ' + escapeHtml(c.address) + '</li>';
            }).join('') +
            '</ul><div style="font-size:11.5px;color:#526658;margin-top:4px;">Click any marker on the interactive map on the right for full details and Google Maps directions.</div>';
    } else {
        html += '<p style="margin:0;font-size:12px;color:#92400e;">No verified nearby facility in our directory explicitly accepts this exact material category. Please contact your local council (PBT) or DOE Malaysia licensed collectors before transport.</p>';
    }
    html += '</div>';

    appendAssistantHtml(html);
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

function requestBrowserGPS() {
    var btn = document.getElementById('btn-request-gps');
    showGpsNotice('');
    if (!navigator.geolocation) {
        showGpsNotice('GPS geolocation is not supported by your browser. Please select your city from the dropdown to locate verified facilities.');
        return;
    }

    if (btn) btn.textContent = 'Requesting GPS Location...';

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            currentLat = pos.coords.latitude;
            currentLng = pos.coords.longitude;
            var pill = document.getElementById('gps-status-pill');
            if (pill) pill.textContent = 'GPS: ' + currentLat.toFixed(4) + ', ' + currentLng.toFixed(4);
            if (btn) btn.textContent = 'Live GPS Connected';
            showGpsNotice('');
            fetchNearbyCentres(currentLat, currentLng, currentCategory);
        },
        function(err) {
            var reason = (err && err.code === 1)
                ? 'Location permission was denied. Enable browser location access or select a Malaysian city from the dropdown.'
                : 'Unable to retrieve your live GPS location. Showing verified facilities for your selected city.';
            showGpsNotice(reason);
            if (btn) btn.textContent = 'Use My Current GPS Location';
        },
        { enableHighAccuracy: true, timeout: 8000 }
    );
}

function onSelectCity(city) {
    var coords = {
        'shah alam': { lat: 3.0738, lng: 101.5183 },
        'petaling jaya': { lat: 3.1073, lng: 101.6067 },
        'klang': { lat: 3.0449, lng: 101.4456 },
        'subang jaya': { lat: 3.0485, lng: 101.5932 },
        'kuala lumpur': { lat: 3.1390, lng: 101.6869 },
        'perai': { lat: 5.3812, lng: 100.4045 },
        'sungai petani': { lat: 5.6435, lng: 100.4900 },
        'ipoh': { lat: 4.5975, lng: 101.0901 },
        'johor bahru': { lat: 1.4927, lng: 103.7414 },
        'pasir gudang': { lat: 1.4641, lng: 103.9064 },
        'kuching': { lat: 1.5533, lng: 110.3592 },
        'kota kinabalu': { lat: 5.9804, lng: 116.0735 }
    };
    var c = coords[city.toLowerCase()] || coords['shah alam'];
    currentLat = c.lat;
    currentLng = c.lng;
    var pill = document.getElementById('gps-status-pill');
    if (pill) pill.textContent = city.toUpperCase();
    fetchNearbyCentres(currentLat, currentLng, currentCategory);
}

function fetchNearbyCentres(lat, lng, category) {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    fetch('/business/helper/nearby-centres', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify({ lat: lat, lng: lng, category: category })
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
            '<strong style="display:block;color:#92400e;margin-bottom:6px;">No Verified Nearby Facility Found</strong>' +
            'No verified facility in our directory confirms acceptance for this material category near your location:' +
            '<ul style="margin:6px 0 0;padding-left:18px;">' +
            altList.map(function(a) { return '<li>' + escapeHtml(a) + '</li>'; }).join('') +
            '</ul></div>';
        return;
    }

    container.innerHTML = centres.map(function(c, idx) {
        return '<article class="facility-card" onclick="focusFacilityOnMap(' + idx + ')">' +
            '<div class="facility-header">' +
                '<div>' +
                    '<h3 class="facility-name">' + escapeHtml(c.name) + '</h3>' +
                    '<span style="font-size:10.5px;background:#e8f4ec;color:#1e5e3a;border:1px solid #c2dec0;padding:2px 6px;font-weight:700;">Verified &middot; ' + escapeHtml(c.license) + '</span>' +
                '</div>' +
                '<span class="distance-pill">' + c.distance_km + ' km</span>' +
            '</div>' +
            '<div class="facility-meta">' +
                '<div><strong>Address:</strong> ' + escapeHtml(c.address) + '</div>' +
                '<div style="margin-top:2px;"><strong>Phone:</strong> ' + escapeHtml(c.phone) + ' &middot; <strong>Hours:</strong> ' + escapeHtml(c.operating_hours) + '</div>' +
            '</div>' +
            '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:2px;">' +
                '<a href="' + escapeHtml(c.directions_url) + '" target="_blank" rel="noopener noreferrer" class="btn-directions" onclick="event.stopPropagation();">' +
                    'Get Directions on Google Maps &rarr;' +
                '</a>' +
            '</div>' +
        '</article>';
    }).join('');
}

function handleChatSubmit(e) {
    if (e) e.preventDefault();
    var inp = document.getElementById('chat-input');
    var msg = (inp ? inp.value : '').trim();
    if (!msg) return;

    openChatInterface();
    appendUserBubble(msg);
    if (inp) inp.value = '';
    sendChatMessage(msg);
}

function askQuickPrompt(btn) {
    var text = btn.textContent.trim();
    openChatInterface();
    appendUserBubble(text);
    sendChatMessage(text);
}

function sendChatMessage(msg) {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var sendBtn = document.getElementById('chat-send-btn');
    if (sendBtn) {
        sendBtn.disabled = true;
        sendBtn.textContent = '...';
    }

    fetch('/business/helper/chat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify({
            message: msg,
            item_id: currentItemId
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            appendAssistantBubble(data.reply);
        }
    })
    .catch(function() {
        appendAssistantBubble('There was an issue processing your question. Please try again.');
    })
    .finally(function() {
        if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.textContent = 'Send';
        }
    });
}

function appendUserBubble(text) {
    var box = document.getElementById('chat-messages');
    if (!box) return;
    var el = document.createElement('div');
    el.className = 'chat-bubble user';
    el.textContent = text;
    box.appendChild(el);
    box.scrollTop = box.scrollHeight;
}

function appendAssistantBubble(text) {
    var box = document.getElementById('chat-messages');
    if (!box) return;
    var el = document.createElement('div');
    el.className = 'chat-bubble assistant';
    el.style.whiteSpace = 'pre-wrap';
    el.innerHTML = formatMarkdown(text);
    box.appendChild(el);
    box.scrollTop = box.scrollHeight;
}

function appendAssistantHtml(html) {
    var box = document.getElementById('chat-messages');
    if (!box) return;
    var el = document.createElement('div');
    el.className = 'chat-bubble assistant';
    el.innerHTML = html;
    box.appendChild(el);
    box.scrollTop = box.scrollHeight;
}

function formatMarkdown(str) {
    var s = escapeHtml(str);
    s = s.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    s = s.replace(/\*(.*?)\*/g, '<em>$1</em>');
    return s;
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
