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
    <title>EcoCycle Helper &amp; GPS Recycling Locator - EcoCycle</title>
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css">
    <style>
        :root {
            --brand-green: #235839;
            --brand-green-hover: #1b452c;
            --brand-dark: #123321;
            --brand-light: #f6f8f5;
            --accent-blue: #1e40af;
            --accent-blue-bg: #eff6ff;
            --border-line: #d8e2d7;
            --text-dark: #1f2937;
            --text-muted: #5f7166;
        }

        .helper-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 22px;
            margin-top: 18px;
        }

        @media (max-width: 1024px) {
            .helper-grid {
                grid-template-columns: 1fr;
            }
        }

        .helper-card {
            background: #ffffff;
            border: 1px solid var(--border-line);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .helper-card-header {
            padding: 16px 20px;
            background: #fbfdfa;
            border-bottom: 1px solid var(--border-line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .helper-card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            font-weight: 700;
            color: var(--brand-dark);
            margin: 0;
        }

        .item-select-bar {
            padding: 14px 20px;
            background: #f4f8f3;
            border-bottom: 1px solid var(--border-line);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .item-select-bar select {
            flex: 1;
            min-width: 220px;
            padding: 9px 12px;
            border-radius: 8px;
            border: 1.5px solid #cadbc9;
            font-size: 13px;
            background: #ffffff;
            color: #123321;
            font-weight: 600;
            cursor: pointer;
        }

        /* AI Analysis Panel */
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
            background: #fbfdfb;
            border: 1px solid #e1ebe0;
            padding: 14px 16px;
            border-radius: 10px;
        }

        .analysis-img {
            width: 72px;
            height: 72px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #d0ded0;
            flex-shrink: 0;
        }

        .metric-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
        }

        .metric-badge.green {
            background: #e8f5e9;
            color: #1b5e20;
            border: 1px solid #c8e6c9;
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
            background: #fcfdfc;
            border: 1px solid #e3ece1;
            border-radius: 8px;
            padding: 14px 16px;
        }

        .advice-section h4 {
            margin: 0 0 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--brand-dark);
            display: flex;
            align-items: center;
            gap: 7px;
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
            border-top: 1px solid var(--border-line);
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
            background: #fafbfa;
        }

        .chat-bubble {
            max-width: 82%;
            padding: 11px 15px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.55;
            white-space: pre-wrap;
        }

        .chat-bubble.assistant {
            align-self: flex-start;
            background: #ffffff;
            border: 1px solid #dde5dd;
            color: #1f2937;
            border-bottom-left-radius: 2px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        .chat-bubble.user {
            align-self: flex-end;
            background: var(--brand-green);
            color: #ffffff;
            border-bottom-right-radius: 2px;
        }

        .chat-prompts {
            padding: 10px 16px;
            background: #f4f7f3;
            border-top: 1px solid #e3ebe1;
            display: flex;
            gap: 6px;
            overflow-x: auto;
            white-space: nowrap;
        }

        .prompt-chip {
            padding: 5px 11px;
            border-radius: 16px;
            background: #ffffff;
            border: 1px solid #cadbc9;
            font-size: 11.5px;
            font-weight: 600;
            color: var(--brand-dark);
            cursor: pointer;
            transition: all 0.15s ease;
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
            border-top: 1px solid var(--border-line);
        }

        .chat-input-bar input {
            flex: 1;
            padding: 10px 14px;
            border: 1.5px solid #d4ded3;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
        }

        .chat-input-bar input:focus {
            border-color: var(--brand-green);
        }

        .chat-send-btn {
            background: var(--brand-green);
            color: #ffffff;
            border: none;
            padding: 0 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .chat-send-btn:hover {
            background: var(--brand-green-hover);
        }

        /* GPS Locator Panel */
        .gps-controls {
            padding: 14px 18px;
            background: #f7faf6;
            border-bottom: 1px solid var(--border-line);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .gps-btn-live {
            background: #1e3a8a;
            color: #ffffff;
            border: none;
            padding: 9px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: background 0.15s;
        }

        .gps-btn-live:hover {
            background: #172554;
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
            border: 1px solid #dce5dc;
            border-radius: 10px;
            padding: 14px 16px;
            transition: all 0.15s ease;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .facility-card:hover {
            border-color: var(--brand-green);
            box-shadow: 0 3px 10px rgba(35,88,57,0.08);
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
            margin: 0;
            line-height: 1.3;
        }

        .distance-pill {
            background: #eef7ee;
            color: #1e6338;
            border: 1px solid #c9e4c8;
            padding: 3px 9px;
            border-radius: 12px;
            font-size: 11.5px;
            font-weight: 700;
            white-space: nowrap;
        }

        .facility-meta {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.45;
        }

        .btn-directions {
            margin-top: 4px;
            align-self: flex-start;
            background: #235839;
            color: #ffffff;
            text-decoration: none;
            font-size: 11.5px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s;
        }

        .btn-directions:hover {
            background: #173e27;
        }
    </style>
</head>
<body class="dashboard-body">

    <!-- Reusable Top Navigation Bar -->
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <!-- Reusable Left Navigation Sidebar -->
        <?php include resource_path('views/business/partials/sidebar.php'); ?>

        <main class="dashboard-content">
            <!-- Header Banner -->
            <div class="page-header" style="margin-bottom: 8px;">
                <div>
                    <h1 style="display:flex;align-items:center;gap:10px;">
                        <span>💡 EcoCycle Helper</span>
                        <span style="font-size:12px;background:#e8f4ec;color:#1e5e3a;padding:3px 10px;border-radius:12px;font-weight:700;">AI Circular Advisor &amp; GPS Locator</span>
                    </h1>
                    <p>Intelligent material analysis, circular repurposing strategies, scrap valuation, and live GPS matching to certified Malaysian recycling centres.</p>
                </div>
                <div style="display:flex;gap:8px;">
                    <a href="/business/inventory" class="btn-primary" style="background:#f4f8f3;color:#235839;border:1.5px solid #cadbc9;text-decoration:none;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:6px;">
                        <span>📦 View Inventory</span>
                    </a>
                    <a href="/business/marketplace?tab=non_reusable" class="btn-primary" style="background:#235839;color:#ffffff;text-decoration:none;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:6px;">
                        <span>⊘ Non-Reusable Listings</span>
                    </a>
                </div>
            </div>

            <!-- Main Helper Grid (AI Advisor + GPS Locator) -->
            <div class="helper-grid">
                <!-- Left Column: EcoCycle AI Chatbot & Material Analysis -->
                <section class="helper-card">
                    <div class="helper-card-header">
                        <h2 class="helper-card-title">
                            <span style="font-size:18px;">🤖</span>
                            <span>Circular Material AI Advisor</span>
                        </h2>
                        <span id="ai-status-indicator" style="font-size:11.5px;color:#15803d;font-weight:700;display:flex;align-items:center;gap:5px;">
                            <span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;"></span>
                            AI Model Active
                        </span>
                    </div>

                    <!-- Material Selector -->
                    <div class="item-select-bar">
                        <label for="item-selector" style="font-size:12px;font-weight:700;color:var(--brand-dark);white-space:nowrap;">Select Item to Analyze:</label>
                        <select id="item-selector" onchange="onSelectItem(this.value)">
                            <?php if ($items->isEmpty()) { ?>
                                <option value="">No inventory items found. Add items in inventory first.</option>
                            <?php } else { ?>
                                <?php foreach ($items as $it) { ?>
                                    <option value="<?= $it->id ?>" <?= $selectedItem && $selectedItem->id === $it->id ? 'selected' : '' ?>>
                                        <?= ($it->reusability === 'not_reusable') ? '⊘ [Scrap] ' : '↻ [Reusable] ' ?>
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
                                    <div class="analysis-img" style="background:#eef6ed;display:grid;place-items:center;font-size:24px;color:#235839;">📦</div>
                                <?php } ?>
                                <div style="flex:1;min-width:0;">
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:4px;">
                                        <span class="metric-badge green"><?= e($selectedItem->category) ?></span>
                                        <span class="metric-badge <?= $selectedItem->reusability === 'not_reusable' ? 'amber' : 'blue' ?>">
                                            <?= $selectedItem->reusability === 'not_reusable' ? '⊘ Non-Reusable Scrap' : '↻ Reusable Asset' ?>
                                        </span>
                                        <span class="metric-badge blue"><?= (float)$selectedItem->quantity ?> <?= e($selectedItem->unit) ?></span>
                                    </div>
                                    <h3 style="margin:0 0 2px;font-size:16px;color:#123321;" id="analysis-item-title"><?= e($selectedItem->name ?: $selectedItem->title) ?></h3>
                                    <p style="margin:0;font-size:12px;color:var(--text-muted);"><?= e($selectedItem->description ?: 'No additional notes provided.') ?></p>
                                </div>
                            </div>

                            <!-- Circular Recommendation Strategy -->
                            <div class="advice-section" style="border-left:4px solid var(--brand-green);">
                                <h4>🎯 Primary Circular Strategy</h4>
                                <p style="margin:0 0 6px;font-size:13.5px;font-weight:700;color:#183624;" id="analysis-strategy">
                                    <?= e($initialAnalysis['action_recommendation']) ?>
                                </p>
                                <p style="margin:0;font-size:12.5px;color:#374151;" id="analysis-path">
                                    <?= e($initialAnalysis['strategy']) ?>
                                </p>
                            </div>

                            <!-- Repurposing Ideas -->
                            <div class="advice-section">
                                <h4>💡 Upcycling &amp; Practical Repurposing Options</h4>
                                <ul class="step-list" id="analysis-repurpose">
                                    <?php foreach ($initialAnalysis['repurpose_ideas'] as $idea) { ?>
                                        <li><?= e($idea) ?></li>
                                    <?php } ?>
                                </ul>
                            </div>

                            <!-- Processing & Decontamination Checklist -->
                            <div class="advice-section">
                                <h4>⚙️ Technical Pre-Processing Checklist</h4>
                                <ol class="step-list" id="analysis-steps">
                                    <?php foreach ($initialAnalysis['recycling_steps'] as $step) { ?>
                                        <li><?= e($step) ?></li>
                                    <?php } ?>
                                </ol>
                            </div>

                            <!-- Economic & Carbon Metrics -->
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;" id="analysis-metrics">
                                <div style="background:#f4fbf5;border:1px solid #cce5c8;border-radius:8px;padding:12px 14px;">
                                    <small style="display:block;font-size:10.5px;text-transform:uppercase;color:#2b6b3e;font-weight:700;">Scrap Market Benchmark</small>
                                    <strong style="font-size:13px;color:#123321;" id="analysis-value"><?= e($initialAnalysis['estimated_value_myr']) ?></strong>
                                </div>
                                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 14px;">
                                    <small style="display:block;font-size:10.5px;text-transform:uppercase;color:#1d4ed8;font-weight:700;">Carbon Offset Potential</small>
                                    <strong style="font-size:13px;color:#1e3a8a;" id="analysis-carbon"><?= e($initialAnalysis['carbon_saving_estimate']) ?></strong>
                                </div>
                            </div>
                        <?php } else { ?>
                            <div style="text-align:center;padding:48px 20px;color:var(--text-muted);">
                                <span style="font-size:36px;display:block;margin-bottom:8px;">📦</span>
                                <h3 style="margin:0 0 6px;color:#123321;">No items selected</h3>
                                <p style="font-size:13px;margin:0;">Select a material from the dropdown above to run automated circular AI analysis.</p>
                            </div>
                        <?php } ?>
                    </div>

                    <!-- Interactive Chatbot -->
                    <div class="chat-container">
                        <div style="padding:10px 18px 4px;font-size:12px;font-weight:700;color:var(--brand-dark);display:flex;align-items:center;gap:6px;">
                            <span>💬 Ask EcoCycle Helper Follow-Up Questions:</span>
                        </div>
                        <div class="chat-messages" id="chat-messages">
                            <div class="chat-bubble assistant">
Hello <strong><?= e($companyName) ?></strong>! I am your <strong>EcoCycle Circular Economy Assistant</strong>. 

You can ask me anything about material preparation, scrap prices in Malaysia, DOE licensing rules, or finding circular exchange buyers. Try clicking a suggested question below or type your own!
                            </div>
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
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                            </button>
                        </form>
                    </div>
                </section>

                <!-- Right Column: GPS Recycling Centre & Recovery Locator -->
                <section class="helper-card">
                    <div class="helper-card-header">
                        <h2 class="helper-card-title">
                            <span style="font-size:18px;">📍</span>
                            <span>GPS Recycling &amp; Disposal Locator</span>
                        </h2>
                        <span id="gps-status-pill" style="font-size:11.5px;color:#1e40af;background:#eff6ff;padding:3px 9px;border-radius:12px;font-weight:700;">
                            📍 <?= e($business->city ?: 'Shah Alam') ?>, <?= e($business->state ?: 'Selangor') ?>
                        </span>
                    </div>

                    <!-- GPS Controls -->
                    <div class="gps-controls">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                            <button type="button" class="gps-btn-live" id="btn-request-gps" onclick="requestBrowserGPS()">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg>
                                <span>Use My Current GPS Location</span>
                            </button>

                            <div style="display:flex;align-items:center;gap:6px;">
                                <label for="location-select" style="font-size:11.5px;font-weight:700;color:var(--brand-dark);">City:</label>
                                <select id="location-select" onchange="onSelectCity(this.value)" style="padding:6px 10px;border-radius:6px;border:1px solid #cadbc9;font-size:12px;background:#fff;">
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

                        <!-- Material Filter Pills -->
                        <div style="display:flex;gap:6px;overflow-x:auto;padding-bottom:2px;" id="material-filter-pills">
                            <button type="button" class="prompt-chip" style="background:var(--brand-green);color:#fff;border-color:var(--brand-green);" onclick="filterCentres('All', this)">All Facilities</button>
                            <button type="button" class="prompt-chip" onclick="filterCentres('Plastics', this)">Plastics</button>
                            <button type="button" class="prompt-chip" onclick="filterCentres('Metals', this)">Metals &amp; Steel</button>
                            <button type="button" class="prompt-chip" onclick="filterCentres('Packaging & paper', this)">Paper &amp; Cartons</button>
                            <button type="button" class="prompt-chip" onclick="filterCentres('Electronics', this)">E-Waste</button>
                            <button type="button" class="prompt-chip" onclick="filterCentres('Timber', this)">Timber &amp; Pallets</button>
                            <button type="button" class="prompt-chip" onclick="filterCentres('Chemicals', this)">Scheduled Chemical</button>
                        </div>
                    </div>

                    <!-- Facility Cards List -->
                    <div class="facility-list" id="facility-list-container">
                        <?php foreach ($nearbyCentres as $centre) { ?>
                            <article class="facility-card" data-category="<?= e($centre['category']) ?>" data-accepted="<?= e(implode(',', $centre['accepted_materials'])) ?>">
                                <div class="facility-header">
                                    <div>
                                        <h3 class="facility-name"><?= e($centre['name']) ?></h3>
                                        <span style="font-size:10.5px;background:#e8f4ec;color:#1e5e3a;padding:2px 7px;border-radius:10px;font-weight:700;">
                                            <?= e($centre['license']) ?>
                                        </span>
                                    </div>
                                    <span class="distance-pill"><?= $centre['distance_km'] ?> km away</span>
                                </div>
                                <div class="facility-meta">
                                    <div>📍 <?= e($centre['address']) ?></div>
                                    <div style="margin-top:2px;">📞 <a href="tel:<?= e($centre['phone']) ?>" style="color:#1e3a8a;text-decoration:none;font-weight:600;"><?= e($centre['phone']) ?></a> &middot; ⏱ <?= e($centre['operating_hours']) ?></div>
                                    <div style="margin-top:4px;display:flex;gap:4px;flex-wrap:wrap;">
                                        <?php foreach ($centre['subtypes'] as $sub) { ?>
                                            <span style="font-size:10px;background:#f3f4f6;color:#4b5563;padding:1px 6px;border-radius:4px;"><?= e($sub) ?></span>
                                        <?php } ?>
                                    </div>
                                </div>
                                <a href="<?= e($centre['directions_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn-directions">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg>
                                    <span>Get Directions on Google Maps &rarr;</span>
                                </a>
                            </article>
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

// On selecting an inventory item
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
    if (stratEl) stratEl.textContent = a.action_recommendation;

    var pathEl = document.getElementById('analysis-path');
    if (pathEl) pathEl.textContent = a.strategy;

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

    var valEl = document.getElementById('analysis-value');
    if (valEl) valEl.textContent = a.estimated_value_myr;

    var carbEl = document.getElementById('analysis-carbon');
    if (carbEl) carbEl.textContent = a.carbon_saving_estimate;

    // Add assistant chat message introducing the item
    appendAssistantBubble('I have loaded **' + item.name + '** (' + item.category + '). Best circular path: **' + a.action_recommendation + '**. How can I help you handle or trade this material?');
}

// Browser Geolocation
function requestBrowserGPS() {
    var btn = document.getElementById('btn-request-gps');
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
    }

    if (btn) btn.innerHTML = '<span>📡 Locating...</span>';

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            currentLat = pos.coords.latitude;
            currentLng = pos.coords.longitude;
            var pill = document.getElementById('gps-status-pill');
            if (pill) pill.textContent = '📍 GPS: ' + currentLat.toFixed(4) + ', ' + currentLng.toFixed(4);
            if (btn) btn.innerHTML = '<span>✓ Live GPS Connected</span>';

            fetchNearbyCentres(currentLat, currentLng, currentCategory);
        },
        function(err) {
            alert('Unable to retrieve your location: ' + err.message + '. Defaulting to registered business city.');
            if (btn) btn.innerHTML = '<span>📍 Use My Current GPS Location</span>';
        },
        { enableHighAccuracy: true, timeout: 8000 }
    );
}

// Select city fallback
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
    if (pill) pill.textContent = '📍 ' + city.toUpperCase();

    fetchNearbyCentres(currentLat, currentLng, currentCategory);
}

function filterCentres(cat, btn) {
    currentCategory = cat;
    var pills = document.querySelectorAll('#material-filter-pills .prompt-chip');
    pills.forEach(function(p) {
        p.style.background = '#ffffff';
        p.style.color = 'var(--brand-dark)';
        p.style.borderColor = '#cadbc9';
    });
    if (btn) {
        btn.style.background = 'var(--brand-green)';
        btn.style.color = '#ffffff';
        btn.style.borderColor = 'var(--brand-green)';
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
            renderNearbyCentres(data.centres);
        }
    })
    .catch(function(e) {
        console.error('Failed to fetch nearby centres:', e);
    });
}

function renderNearbyCentres(centres) {
    var container = document.getElementById('facility-list-container');
    if (!container) return;

    if (!centres || centres.length === 0) {
        container.innerHTML = '<div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px;">No recycling centres found matching this filter nearby.</div>';
        return;
    }

    container.innerHTML = centres.map(function(c) {
        var subtypesHtml = (c.subtypes || []).map(function(s) {
            return '<span style="font-size:10px;background:#f3f4f6;color:#4b5563;padding:1px 6px;border-radius:4px;">' + escapeHtml(s) + '</span>';
        }).join(' ');

        return '<article class="facility-card">' +
            '<div class="facility-header">' +
                '<div>' +
                    '<h3 class="facility-name">' + escapeHtml(c.name) + '</h3>' +
                    '<span style="font-size:10.5px;background:#e8f4ec;color:#1e5e3a;padding:2px 7px;border-radius:10px;font-weight:700;">' + escapeHtml(c.license) + '</span>' +
                '</div>' +
                '<span class="distance-pill">' + c.distance_km + ' km away</span>' +
            '</div>' +
            '<div class="facility-meta">' +
                '<div>📍 ' + escapeHtml(c.address) + '</div>' +
                '<div style="margin-top:2px;">📞 <a href="tel:' + escapeHtml(c.phone) + '" style="color:#1e3a8a;text-decoration:none;font-weight:600;">' + escapeHtml(c.phone) + '</a> &middot; ⏱ ' + escapeHtml(c.operating_hours) + '</div>' +
                '<div style="margin-top:4px;display:flex;gap:4px;flex-wrap:wrap;">' + subtypesHtml + '</div>' +
            '</div>' +
            '<a href="' + escapeHtml(c.directions_url) + '" target="_blank" rel="noopener noreferrer" class="btn-directions">' +
                '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg>' +
                '<span>Get Directions on Google Maps &rarr;</span>' +
            '</a>' +
        '</article>';
    }).join('');
}

// Chat functions
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
        appendAssistantBubble('I apologize, there was an issue processing your query. Please try again.');
    })
    .finally(function() {
        if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<span>Send</span> <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>';
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
