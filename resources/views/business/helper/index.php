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

$companyName = $business->business_name ?: ($business->name ?? 'EcoCycle Business');
$activeNav = 'helper';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EcoCycle Helper &amp; GPS Recycling Locator &mdash; EcoCycle</title>
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .helper-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 22px;
        }

        @media (max-width: 1024px) {
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
            padding: 16px 20px;
            background: var(--surface-subtle);
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .helper-card-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .item-select-bar {
            padding: 14px 20px;
            background: #f4f8f3;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            gap: 12px;
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

        /* Analysis Panel */
        .analysis-panel {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            max-height: 520px;
            overflow-y: auto;
        }

        .analysis-header-box {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            background: var(--surface-subtle);
            border: 1px solid var(--card-border);
            padding: 14px 16px;
            border-radius: 0;
        }

        .analysis-img {
            width: 72px;
            height: 72px;
            border-radius: 0;
            object-fit: cover;
            border: 1px solid var(--card-border);
            flex-shrink: 0;
        }

        .metric-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            border-radius: 0;
            font-size: 11px;
            font-weight: 700;
        }

        .metric-badge.green {
            background: #e8f5e9;
            color: #1b5e20;
            border: 1px solid #c2dec0;
        }

        .metric-badge.amber {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .metric-badge.blue {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .advice-section {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 0;
            padding: 14px 16px;
        }

        .advice-section h4 {
            margin: 0 0 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .step-list {
            margin: 0;
            padding-left: 18px;
            font-size: 12.5px;
            color: #374151;
            line-height: 1.6;
        }

        .step-list li {
            margin-bottom: 6px;
        }

        /* Interactive Chat Section */
        .chat-container {
            display: flex;
            flex-direction: column;
            border-top: 1px solid var(--card-border);
            background: #ffffff;
        }

        .chat-messages {
            padding: 16px 20px;
            max-height: 280px;
            min-height: 160px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: var(--surface-subtle);
        }

        .chat-bubble {
            max-width: 82%;
            padding: 11px 15px;
            border-radius: 0;
            font-size: 13px;
            line-height: 1.55;
            white-space: pre-wrap;
        }

        .chat-bubble.assistant {
            align-self: flex-start;
            background: #ffffff;
            border: 1px solid var(--card-border);
            color: #1f2937;
        }

        .chat-bubble.user {
            align-self: flex-end;
            background: var(--forest-bg);
            color: #ffffff;
            border: 1px solid var(--forest-bg);
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
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
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
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .chat-send-btn:hover {
            background: #0d2b1b;
        }

        /* GPS Locator Panel */
        .gps-controls {
            padding: 14px 18px;
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
            padding: 9px 14px;
            border-radius: 0;
            font-size: 12.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .gps-btn-live:hover {
            background: #0d2b1b;
        }

        .facility-list {
            padding: 14px 18px;
            max-height: 720px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .facility-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 0;
            padding: 14px 16px;
            transition: border-color 0.15s ease;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .facility-card:hover {
            border-color: var(--brand-green);
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
            line-height: 1.3;
        }

        .distance-pill {
            background: #eef7ee;
            color: #1e6338;
            border: 1px solid #c4dfc0;
            padding: 3px 9px;
            border-radius: 0;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .facility-meta {
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .btn-directions {
            margin-top: 4px;
            align-self: flex-start;
            background: var(--forest-bg);
            color: #ffffff;
            border: 1px solid var(--forest-bg);
            text-decoration: none;
            font-size: 11.5px;
            font-weight: 600;
            padding: 7px 12px;
            border-radius: 0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.15s ease;
        }

        .btn-directions:hover {
            background: #0d2b1b;
        }
    </style>
</head>
<body class="business-dashboard-page">

    <!-- Reusable Top Navigation Bar -->
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <!-- Reusable Left Navigation Sidebar -->
        <?php include resource_path('views/business/partials/sidebar.php'); ?>

        <main class="dashboard-main">
            <!-- Header Banner -->
            <div class="page-header">
                <div>
                    <h1>EcoCycle Helper</h1>
                    <p>This material has been classified as non-reusable. EcoCycle Helper provides recommendations on how to manage, recycle or dispose of the material properly.</p>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="/business/inventory" class="btn-reset" style="display:inline-flex;align-items:center;">
                        <span>View Inventory</span>
                    </a>
                    <a href="/business/waste-monitoring" class="btn-search" style="text-decoration:none;">
                        <span>Waste Monitoring</span>
                    </a>
                </div>
            </div>

            <!-- Main Helper Grid (Circular Advisor + GPS Locator) -->
            <div class="helper-grid">
                <!-- Left Column: EcoCycle Helper & Material Analysis -->
                <section class="helper-card">
                    <div class="helper-card-header">
                        <h2 class="helper-card-title">
                            <span>Material Analysis &amp; Disposal Guidance</span>
                        </h2>
                        <span id="ai-status-indicator" style="font-size:11px;color:#15803d;font-weight:700;background:#eef6ed;border:1px solid #c2dec0;padding:3px 9px;">
                            Active
                        </span>
                    </div>

                    <!-- Material Selector -->
                    <div class="item-select-bar">
                        <label for="item-selector" style="font-size:12px;font-weight:700;color:var(--text-primary);white-space:nowrap;">Select Item to Analyze:</label>
                        <select id="item-selector" onchange="onSelectItem(this.value)">
                            <?php if ($items->isEmpty()) { ?>
                                <option value="">No inventory items found. Add items in inventory first.</option>
                            <?php } else { ?>
                                <?php foreach ($items as $it) { ?>
                                    <option value="<?= $it->id ?>" <?= $selectedItem && $selectedItem->id === $it->id ? 'selected' : '' ?>>
                                        <?= ($it->reusability === 'not_reusable') ? '[Non-Reusable] ' : '[Reusable] ' ?>
                                        <?= e($it->name ?: $it->title) ?> (<?= (float)$it->quantity ?> <?= e($it->unit) ?> &middot; <?= e($it->category) ?>)
                                    </option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- Analysis Output Container -->
                    <div class="analysis-panel" id="analysis-container">
                        <?php if ($selectedItem && $initialAnalysis) { ?>
                            <!-- Item Brief Header -->
                            <div class="analysis-header-box">
                                <?php if ($selectedItem->getPrimaryImage()) { ?>
                                    <img src="<?= e($selectedItem->getPrimaryImage()) ?>" alt="<?= e($selectedItem->name) ?>" class="analysis-img">
                                <?php } else { ?>
                                    <div class="analysis-img" style="background:#eef6ed;display:grid;place-items:center;font-size:11px;font-weight:700;color:#235839;">ITEM</div>
                                <?php } ?>
                                <div style="flex:1;min-width:0;">
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:6px;">
                                        <span class="metric-badge green"><?= e($selectedItem->category) ?></span>
                                        <span class="metric-badge <?= $selectedItem->reusability === 'not_reusable' ? 'amber' : 'blue' ?>">
                                            <?= $selectedItem->reusability === 'not_reusable' ? 'Non-Reusable Scrap' : 'Reusable Asset' ?>
                                        </span>
                                        <span class="metric-badge blue"><?= (float)$selectedItem->quantity ?> <?= e($selectedItem->unit) ?></span>
                                    </div>
                                    <h3 style="margin:0 0 2px;font-size:16px;color:#123321;" id="analysis-item-title"><?= e($selectedItem->name ?: $selectedItem->title) ?></h3>
                                    <p style="margin:0;font-size:12px;color:var(--text-muted);"><?= e($selectedItem->description ?: 'No additional notes provided.') ?></p>
                                </div>
                            </div>

                            <!-- Circular Recommendation Strategy -->
                            <div class="advice-section" style="border-left:3px solid var(--brand-green);">
                                <h4>Recycling &amp; Waste Handling Methods</h4>
                                <p style="margin:0 0 6px;font-size:13.5px;font-weight:700;color:#183624;" id="analysis-strategy">
                                    <?= e($initialAnalysis['action_recommendation']) ?>
                                </p>
                                <p style="margin:0;font-size:12.5px;color:#374151;line-height:1.55;" id="analysis-path">
                                    <?= e($initialAnalysis['strategy']) ?>
                                </p>
                            </div>

                            <!-- What Can the Business Owner Do? -->
                            <div class="advice-section" style="background:#f8fbf8;border-left:3px solid #143825;">
                                <h4>What Can the Business Owner Do?</h4>
                                <ol class="step-list" id="analysis-owner-actions">
                                    <?php foreach (($initialAnalysis['owner_actions'] ?? $initialAnalysis['recycling_steps'] ?? []) as $act) { ?>
                                        <li><?= e($act) ?></li>
                                    <?php } ?>
                                </ol>
                            </div>

                            <!-- Suggested Next Steps -->
                            <div class="advice-section">
                                <h4>Suggested Next Steps</h4>
                                <ul class="step-list" id="analysis-next-steps">
                                    <?php foreach (($initialAnalysis['recommended_next_steps'] ?? []) as $ns) { ?>
                                        <li><?= e($ns) ?></li>
                                    <?php } ?>
                                </ul>
                            </div>

                            <!-- Repurposing Ideas -->
                            <div class="advice-section">
                                <h4>Alternative Recovery &amp; Repurposing Ideas</h4>
                                <ul class="step-list" id="analysis-repurpose">
                                    <?php foreach (($initialAnalysis['repurpose_ideas'] ?? []) as $idea) { ?>
                                        <li><?= e($idea) ?></li>
                                    <?php } ?>
                                </ul>
                            </div>

                            <!-- Processing & Decontamination Checklist -->
                            <div class="advice-section">
                                <h4>Technical Pre-Processing &amp; Preparation Checklist</h4>
                                <ol class="step-list" id="analysis-steps">
                                    <?php foreach (($initialAnalysis['recycling_steps'] ?? []) as $step) { ?>
                                        <li><?= e($step) ?></li>
                                    <?php } ?>
                                </ol>
                            </div>

                            <!-- Qualitative Environmental & Carbon Impact (No Fabricated Statistics) -->
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;" id="analysis-metrics">
                                <div style="background:#f4fbf5;border:1px solid #c2dec0;border-radius:0;padding:12px 14px;">
                                    <small style="display:block;font-size:10.5px;text-transform:uppercase;color:#1b5e20;font-weight:700;margin-bottom:4px;">Environmental Impact (Qualitative)</small>
                                    <p style="margin:0;font-size:12px;color:#123321;line-height:1.5;" id="analysis-env-impact"><?= e($initialAnalysis['environmental_impact'] ?? 'Proper segregation and licensed recovery reduce landfill burden and prevent soil or waterway contamination.') ?></p>
                                </div>
                                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:0;padding:12px 14px;">
                                    <small style="display:block;font-size:10.5px;text-transform:uppercase;color:#1d4ed8;font-weight:700;margin-bottom:4px;">Carbon Impact Guidance (Qualitative)</small>
                                    <p style="margin:0;font-size:12px;color:#1e3a8a;line-height:1.5;" id="analysis-carbon"><?= e($initialAnalysis['carbon_impact'] ?? $initialAnalysis['carbon_saving_estimate'] ?? 'Diverting non-reusable material to verified recycling or composting reduces emissions compared to landfill disposal or open burning.') ?></p>
                                </div>
                            </div>

                            <!-- Improper Disposal Risks & Other Recommendations -->
                            <div class="advice-section" style="border-left:3px solid #b45309;background:#fffbeb;">
                                <h4 style="color:#92400e;">Risks of Improper Disposal &amp; Compliance Note</h4>
                                <p style="margin:0 0 8px;font-size:12.5px;color:#78350f;line-height:1.5;" id="analysis-risks">
                                    <?= e($initialAnalysis['improper_disposal_risks'] ?? 'Improper dumping or open burning can cause environmental pollution and violate Malaysian solid or scheduled waste regulations.') ?>
                                </p>
                                <?php if (! empty($initialAnalysis['other_recommendations'])): ?>
                                    <div style="font-size:12px;color:#475569;padding-top:6px;border-top:1px solid #fde68a;" id="analysis-other-recs">
                                        <strong>Additional Guidance:</strong> <?= e(implode(' ', (array) $initialAnalysis['other_recommendations'])) ?>
                                    </div>
                                <?php else: ?>
                                    <div style="font-size:12px;color:#475569;padding-top:6px;border-top:1px solid #fde68a;" id="analysis-other-recs"></div>
                                <?php endif; ?>
                            </div>
                        <?php } else { ?>
                            <div style="text-align:center;padding:48px 20px;color:var(--text-muted);">
                                <h3 style="margin:0 0 6px;color:#123321;">No items selected</h3>
                                <p style="font-size:13px;margin:0;">Select a material from the dropdown above to view recycling and repurposing recommendations.</p>
                            </div>
                        <?php } ?>
                    </div>

                    <!-- Interactive Chatbot -->
                    <div class="chat-container">
                        <div style="padding:10px 18px 6px;font-size:12px;font-weight:700;color:var(--text-primary);border-bottom:1px solid #edf2ec;">
                            Ask EcoCycle Helper Follow-Up Questions
                        </div>
                        <div class="chat-messages" id="chat-messages">
                            <div class="chat-bubble assistant">Hello <strong><?= e($companyName) ?></strong>. I am your <strong>EcoCycle Circular Economy Assistant</strong>.

You can ask about material preparation, scrap prices in Malaysia, DOE licensing rules, or suitable disposal methods. Select a suggested question below or type your own.</div>
                        </div>

                        <!-- Quick Suggested Prompt Chips -->
                        <div class="chat-prompts" id="prompt-chips">
                            <button type="button" class="prompt-chip" onclick="askQuickPrompt(this)">What is the estimated market price for this material in Malaysia?</button>
                            <button type="button" class="prompt-chip" onclick="askQuickPrompt(this)">How should we store and prepare this for pickup?</button>
                            <button type="button" class="prompt-chip" onclick="askQuickPrompt(this)">Are there DOE or licensing requirements for this waste?</button>
                            <button type="button" class="prompt-chip" onclick="askQuickPrompt(this)">What are the best repurposing and upcycling ideas?</button>
                        </div>

                        <!-- Input Bar -->
                        <form class="chat-input-bar" id="chat-form" onsubmit="handleChatSubmit(event)">
                            <input type="text" id="chat-input" placeholder="Ask about preparation, market price, DOE compliance..." autocomplete="off">
                            <button type="submit" class="chat-send-btn" id="chat-send-btn">
                                <span>Send</span>
                            </button>
                        </form>
                    </div>
                </section>

                <!-- Right Column: GPS Recycling Centre & Recovery Locator -->
                <section class="helper-card">
                    <div class="helper-card-header">
                        <h2 class="helper-card-title">
                            <span>GPS Recycling &amp; Disposal Locator</span>
                        </h2>
                        <span id="gps-status-pill" style="font-size:11px;color:#1e40af;background:#eff6ff;border:1px solid #bfdbfe;padding:3px 9px;border-radius:0;font-weight:700;">
                            <?= e($business->city ?: 'Shah Alam') ?>, <?= e($business->state ?: 'Selangor') ?>
                        </span>
                    </div>

                    <!-- GPS Controls -->
                    <div class="gps-controls">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                            <button type="button" class="gps-btn-live" id="btn-request-gps" onclick="requestBrowserGPS()">
                                <span>Use My Current GPS Location</span>
                            </button>

                            <div style="display:flex;align-items:center;gap:6px;">
                                <label for="location-select" style="font-size:11.5px;font-weight:700;color:var(--text-primary);">City:</label>
                                <select id="location-select" onchange="onSelectCity(this.value)" style="padding:7px 10px;border-radius:0;border:1px solid var(--input-border);font-size:12px;background:#ffffff;">
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
                            </div>
                        </div>

                        <!-- GPS Permission / Status Notice -->
                        <div id="gps-notice-banner" style="display:none;padding:9px 12px;background:#fffbeb;border:1px solid #fde68a;border-left:3px solid #b45309;color:#92400e;font-size:12px;line-height:1.45;"></div>

                        <!-- Material Filter Tabs -->
                        <div style="display:flex;gap:6px;overflow-x:auto;padding-bottom:2px;" id="material-filter-pills">
                            <button type="button" class="prompt-chip" style="background:var(--forest-bg);color:#fff;border-color:var(--forest-bg);" onclick="filterCentres('All', this)">All Verified</button>
                            <button type="button" class="prompt-chip" onclick="filterCentres('Plastics', this)">Plastics</button>
                            <button type="button" class="prompt-chip" onclick="filterCentres('Metals', this)">Metals &amp; Steel</button>
                            <button type="button" class="prompt-chip" onclick="filterCentres('Packaging & paper', this)">Paper &amp; Cartons</button>
                            <button type="button" class="prompt-chip" onclick="filterCentres('Electronics', this)">E-Waste</button>
                            <button type="button" class="prompt-chip" onclick="filterCentres('Timber', this)">Timber &amp; Pallets</button>
                            <button type="button" class="prompt-chip" onclick="filterCentres('Food & Organic Waste', this)">Food / Organic</button>
                            <button type="button" class="prompt-chip" onclick="filterCentres('Chemicals', this)">Scheduled Chemical</button>
                        </div>
                    </div>

                    <!-- Facility Cards List -->
                    <div class="facility-list" id="facility-list-container">
                        <?php if (empty($nearbyCentres)) { ?>
                            <div style="padding:18px;background:#fffbeb;border:1px solid #fde68a;border-left:3px solid #b45309;color:#78350f;font-size:12.5px;line-height:1.55;">
                                <strong style="display:block;color:#92400e;margin-bottom:6px;">No Verified Nearby Facility Found for This Material</strong>
                                Our verified facility directory does not currently list a centre confirmed to accept <strong><?= e($selectedItem?->category ?? 'this material') ?></strong> near your selected location.
                                <ul style="margin:8px 0 0;padding-left:18px;">
                                    <li>Contact your local council (PBT) or state solid waste authority (SWCorp / KDEB) to confirm approved disposal channels.</li>
                                    <li>Verify licensing under DOE Malaysia before transporting specialized or contaminated materials.</li>
                                    <li>Keep the material segregated, dry, and clearly labelled until an authorized facility confirms acceptance.</li>
                                </ul>
                            </div>
                        <?php } else { ?>
                            <?php foreach ($nearbyCentres as $centre) { ?>
                                <article class="facility-card" data-category="<?= e($centre['category']) ?>" data-accepted="<?= e(implode(',', $centre['accepted_materials'])) ?>">
                                    <div class="facility-header">
                                        <div>
                                            <h3 class="facility-name"><?= e($centre['name']) ?></h3>
                                            <span style="font-size:10.5px;background:#e8f4ec;color:#1e5e3a;border:1px solid #c2dec0;padding:2px 7px;border-radius:0;font-weight:700;">
                                                Verified &middot; <?= e($centre['license']) ?>
                                            </span>
                                        </div>
                                        <span class="distance-pill"><?= $centre['distance_km'] ?> km away</span>
                                    </div>
                                    <div class="facility-meta">
                                        <div><strong>Address:</strong> <?= e($centre['address']) ?></div>
                                        <div style="margin-top:2px;"><strong>Phone:</strong> <a href="tel:<?= e($centre['phone']) ?>" style="color:#1e3a8a;text-decoration:none;font-weight:600;"><?= e($centre['phone']) ?></a> &middot; <strong>Hours:</strong> <?= e($centre['operating_hours']) ?></div>
                                        <div style="margin-top:6px;display:flex;gap:4px;flex-wrap:wrap;">
                                            <?php foreach ($centre['subtypes'] as $sub) { ?>
                                                <span style="font-size:10px;background:#f3f4f6;color:#4b5563;border:1px solid #e2e8f0;padding:2px 6px;border-radius:0;"><?= e($sub) ?></span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <a href="<?= e($centre['directions_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn-directions">
                                        <span>Get Directions on Google Maps &rarr;</span>
                                    </a>
                                </article>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </section>
            </div>
        </main>
    </div>

<script>
var currentLat = <?= (float) $defaultCoords['lat'] ?>;
var currentLng = <?= (float) $defaultCoords['lng'] ?>;
var currentItemId = <?= $selectedItem ? (int)$selectedItem->id : 'null' ?>;
var currentCategory = '<?= $selectedItem ? e(addslashes($selectedItem->category)) : "All" ?>';

function onSelectItem(itemId) {
    if (!itemId) return;
    currentItemId = itemId;

    fetch('/business/helper/analyze/' + itemId, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) return;
        renderAnalysis(data.item, data.analysis);
        if (data.item.category) {
            currentCategory = data.item.category;
            renderNearbyCentres(data.nearby_centres);
        }
    })
    .catch(function(err) {
        console.error('Failed to analyze item:', err);
    });
}

function renderAnalysis(item, a) {
    var titleEl = document.getElementById('analysis-item-title');
    if (titleEl) titleEl.textContent = item.name;

    var stratEl = document.getElementById('analysis-strategy');
    if (stratEl) stratEl.textContent = a.action_recommendation || '';

    var pathEl = document.getElementById('analysis-path');
    if (pathEl) pathEl.textContent = a.strategy || '';

    var ownerList = document.getElementById('analysis-owner-actions');
    var ownerActs = a.owner_actions || a.recycling_steps || [];
    if (ownerList) {
        ownerList.innerHTML = ownerActs.map(function(act) {
            return '<li>' + escapeHtml(act) + '</li>';
        }).join('');
    }

    var nextList = document.getElementById('analysis-next-steps');
    if (nextList && Array.isArray(a.recommended_next_steps)) {
        nextList.innerHTML = a.recommended_next_steps.map(function(ns) {
            return '<li>' + escapeHtml(ns) + '</li>';
        }).join('');
    }

    var repList = document.getElementById('analysis-repurpose');
    if (repList && a.repurpose_ideas) {
        repList.innerHTML = a.repurpose_ideas.map(function(idea) {
            return '<li>' + escapeHtml(idea) + '</li>';
        }).join('');
    }

    var stepList = document.getElementById('analysis-steps');
    if (stepList && a.recycling_steps) {
        stepList.innerHTML = a.recycling_steps.map(function(s) {
            return '<li>' + escapeHtml(s) + '</li>';
        }).join('');
    }

    var envEl = document.getElementById('analysis-env-impact');
    if (envEl) envEl.textContent = a.environmental_impact || 'Proper segregation and licensed recovery reduce landfill burden and prevent environmental pollution.';

    var carbEl = document.getElementById('analysis-carbon');
    if (carbEl) carbEl.textContent = a.carbon_impact || a.carbon_saving_estimate || 'Diverting non-reusable material to verified recycling or composting reduces emissions compared to landfill disposal.';

    var riskEl = document.getElementById('analysis-risks');
    if (riskEl) riskEl.textContent = a.improper_disposal_risks || 'Improper dumping or open burning causes environmental harm and breaches waste regulations.';

    var otherEl = document.getElementById('analysis-other-recs');
    if (otherEl) {
        var others = Array.isArray(a.other_recommendations) ? a.other_recommendations : [];
        otherEl.innerHTML = others.length > 0 ? ('<strong>Additional Guidance:</strong> ' + escapeHtml(others.join(' '))) : '';
    }

    appendAssistantBubble('Loaded **' + item.name + '** (' + item.category + '). Recommended path: **' + (a.action_recommendation || 'Verified disposal / recovery') + '**. How can I assist with handling or disposal?');
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
        showGpsNotice('GPS geolocation is not supported by your browser. Please choose a Malaysian city from the dropdown to search verified recycling facilities.');
        return;
    }

    if (btn) btn.innerHTML = '<span>Requesting GPS Permission...</span>';

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            currentLat = pos.coords.latitude;
            currentLng = pos.coords.longitude;
            var pill = document.getElementById('gps-status-pill');
            if (pill) pill.textContent = 'GPS: ' + currentLat.toFixed(4) + ', ' + currentLng.toFixed(4);
            if (btn) btn.innerHTML = '<span>Live GPS Connected</span>';
            showGpsNotice('');

            fetchNearbyCentres(currentLat, currentLng, currentCategory);
        },
        function(err) {
            var reason = (err && err.code === 1)
                ? 'Location permission was denied. You can enable browser location access anytime or select your city manually from the dropdown below.'
                : 'Unable to retrieve your live GPS location (' + (err && err.message ? err.message : 'unavailable') + '). Showing verified facilities using your selected city.';
            showGpsNotice(reason);
            if (btn) btn.innerHTML = '<span>Use My Current GPS Location</span>';
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

function filterCentres(cat, btn) {
    currentCategory = cat;
    var pills = document.querySelectorAll('#material-filter-pills .prompt-chip');
    pills.forEach(function(p) {
        p.style.background = '#ffffff';
        p.style.color = 'var(--text-primary)';
        p.style.borderColor = 'var(--input-border)';
    });
    if (btn) {
        btn.style.background = 'var(--forest-bg)';
        btn.style.color = '#ffffff';
        btn.style.borderColor = 'var(--forest-bg)';
    }

    fetchNearbyCentres(currentLat, currentLng, cat === 'All' ? null : cat);
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
        body: JSON.stringify({
            lat: lat,
            lng: lng,
            category: category
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            renderNearbyCentres(data.centres, data.alternative_recommendations || []);
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
            'Contact your local council (PBT) or state waste authority (SWCorp / KDEB) for approved collection points.',
            'Consult licensed Malaysian waste collectors under DOE Malaysia before transporting specialized waste.',
            'Keep the material segregated, dry, and clearly labelled until an authorized facility confirms acceptance.'
        ];
        container.innerHTML = '<div style="padding:18px;background:#fffbeb;border:1px solid #fde68a;border-left:3px solid #b45309;color:#78350f;font-size:12.5px;line-height:1.55;">' +
            '<strong style="display:block;color:#92400e;margin-bottom:6px;">No Verified Nearby Facility Found for This Material</strong>' +
            'Our verified facility directory does not currently confirm a nearby centre accepting this exact material category. Consider these alternative handling steps:' +
            '<ul style="margin:8px 0 0;padding-left:18px;">' +
            altList.map(function(a) { return '<li>' + escapeHtml(a) + '</li>'; }).join('') +
            '</ul></div>';
        return;
    }

    container.innerHTML = centres.map(function(c) {
        var subtypesHtml = (c.subtypes || []).map(function(s) {
            return '<span style="font-size:10px;background:#f3f4f6;color:#4b5563;border:1px solid #e2e8f0;padding:2px 6px;border-radius:0;">' + escapeHtml(s) + '</span>';
        }).join(' ');

        return '<article class="facility-card">' +
            '<div class="facility-header">' +
                '<div>' +
                    '<h3 class="facility-name">' + escapeHtml(c.name) + '</h3>' +
                    '<span style="font-size:10.5px;background:#e8f4ec;color:#1e5e3a;border:1px solid #c2dec0;padding:2px 7px;border-radius:0;font-weight:700;">Verified &middot; ' + escapeHtml(c.license) + '</span>' +
                '</div>' +
                '<span class="distance-pill">' + c.distance_km + ' km away</span>' +
            '</div>' +
            '<div class="facility-meta">' +
                '<div><strong>Address:</strong> ' + escapeHtml(c.address) + '</div>' +
                '<div style="margin-top:2px;"><strong>Phone:</strong> <a href="tel:' + escapeHtml(c.phone) + '" style="color:#1e3a8a;text-decoration:none;font-weight:600;">' + escapeHtml(c.phone) + '</a> &middot; <strong>Hours:</strong> ' + escapeHtml(c.operating_hours) + '</div>' +
                '<div style="margin-top:6px;display:flex;gap:4px;flex-wrap:wrap;">' + subtypesHtml + '</div>' +
            '</div>' +
            '<a href="' + escapeHtml(c.directions_url) + '" target="_blank" rel="noopener noreferrer" class="btn-directions">' +
                '<span>Get Directions on Google Maps &rarr;</span>' +
            '</a>' +
        '</article>';
    }).join('');
}

function handleChatSubmit(e) {
    if (e) e.preventDefault();
    var inp = document.getElementById('chat-input');
    var msg = (inp ? inp.value : '').trim();
    if (!msg) return;

    appendUserBubble(msg);
    if (inp) inp.value = '';

    sendChatMessage(msg);
}

function askQuickPrompt(btn) {
    var text = btn.textContent.trim();
    appendUserBubble(text);
    sendChatMessage(text);
}

function sendChatMessage(msg) {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var sendBtn = document.getElementById('chat-send-btn');
    if (sendBtn) {
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<span>...</span>';
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
    .catch(function(err) {
        appendAssistantBubble('There was an issue processing your query. Please try again.');
    })
    .finally(function() {
        if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<span>Send</span>';
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
    el.innerHTML = formatMarkdown(text);
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
</script>
</body>
</html>
