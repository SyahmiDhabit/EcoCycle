<!DOCTYPE html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wastage Item Report &middot; EcoCycle</title>
    <link rel="icon" type="image/svg+xml" href="/images/figma/ecocycle-leaf.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .waste-report {
            padding-inline: max(clamp(20px, 4vw, 48px), calc((100% - 1180px) / 2));
        }
        .waste-report .page-header {
            align-items: center;
            flex-wrap: wrap;
            gap: 12px 20px;
            padding: 0;
            border: 0;
        }
        .waste-report .page-header h1 {
            color: #14532d;
            font-size: clamp(26px, 3vw, 40px);
        }
        .waste-report .btn-search {
            background: #176b40;
            border-color: #176b40;
            border-radius: 6px !important;
            text-decoration: none;
        }
        .waste-report .btn-search:hover { background: #145331; }
        .waste-report-panel {
            background: #fff;
            border: 1px solid var(--card-border);
            box-shadow: 0 5px 12px rgba(20, 83, 45, .05);
        }
        .waste-report-panel h2 {
            margin: 0;
            padding: 12px clamp(16px, 2vw, 24px);
            background: #eef5eb;
            border-bottom: 1px solid var(--card-border);
            color: #14532d;
            font-size: 17px;
        }
        .waste-report-fields {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 230px), 1fr));
            gap: 20px 24px;
            margin: 0;
            padding: clamp(16px, 2vw, 24px);
        }
        .waste-report-fields > div { min-width: 0; }
        .waste-report-fields dt { font-size: 12px; font-weight: 700; color: #14532d; }
        .waste-report-fields dd { margin: 4px 0 0; overflow-wrap: anywhere; }
        .waste-tracking {
            list-style: none;
            margin: 0;
            padding: 20px clamp(16px, 2vw, 24px);
        }
        .waste-tracking-item {
            position: relative;
            padding: 0 0 16px 20px;
            border-left: 2px solid #cbd5e1;
            overflow-wrap: anywhere;
        }
        .waste-tracking-item:last-child { border-left-color: transparent; padding-bottom: 0; }
        .waste-tracking-dot {
            position: absolute;
            left: -6px;
            top: 3px;
            width: 10px;
            height: 10px;
            background: var(--timeline-color, #143825);
            border: 2px solid #fff;
            outline: 1px solid var(--timeline-color, #143825);
        }
        .waste-tracking-item[data-status="in_storage"] { --timeline-color: #b45309; }
        .waste-tracking-item[data-status="scheduled_pickup"] { --timeline-color: #1d4ed8; }
        .waste-tracking-item[data-status="sent_to_facility"] { --timeline-color: #0e7490; }
        .waste-tracking-item:is([data-status="recycled"], [data-status="composted"]) { --timeline-color: #15803d; }
        .waste-tracking strong { display: block; font-size: 13px; color: #143825; }
        .waste-tracking-meta { margin-top: 2px; font-size: 12px; color: #475569; }
        .waste-tracking-panel { margin-top: 16px; }
    </style>
    <link rel="stylesheet" href="/css/site-preferences.css?v=<?= filemtime(public_path('css/site-preferences.css')) ?>">
    <script src="/js/site-theme.js?v=<?= filemtime(public_path('js/site-theme.js')) ?>"></script>
    <script src="/js/language-switcher.js?v=<?= filemtime(public_path('js/language-switcher.js')) ?>" defer></script>
</head>
<body class="business-dashboard-page">
    <?php include resource_path('views/business/partials/topbar.php'); ?>
    <div class="dashboard-layout">
        <?php
        $activeNav = 'waste';
        include resource_path('views/business/partials/sidebar.php');
        ?>
        <main class="dashboard-main-content waste-report">
            <header class="page-header">
                <div>
                    <h1>Wastage Item Report</h1>
                    <p><?= e($record->material_name) ?></p>
                </div>
                <a class="btn-search" href="<?= e(route('business.waste.index')) ?>">Back to Waste Monitoring</a>
            </header>
            <section class="waste-report-panel" aria-labelledby="waste-record-heading">
                <h2 id="waste-record-heading">Waste record</h2>
                <dl class="waste-report-fields">
                    <?php foreach ([
                        'Material' => $record->material_name,
                        'Category' => $record->waste_type,
                        'Quantity' => $record->quantity.' '.$record->unit,
                        'Status' => \App\Models\WasteRecord::statusLabel($record->status),
                        'Facility' => $record->facility_name,
                        'Facility location' => $record->facility_location,
                        'Recorded as diverted' => $record->is_diverted ? 'Yes' : 'No',
                        'Recorded date' => $record->recorded_at?->format('d M Y'),
                        'Facility payment' => $record->facility_payment_rm === null ? null : 'RM '.number_format((float) $record->facility_payment_rm, 2),
                        'Notes' => $record->notes,
                    ] as $label => $value): ?>
                        <div><dt><?= e($label) ?></dt><dd><?= e($value ?? '—') ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            </section>
            <section class="waste-report-panel waste-tracking-panel" aria-labelledby="waste-tracking-heading">
                <h2 id="waste-tracking-heading">Tracking History</h2>
                <ol class="waste-tracking">
                    <?php foreach (array_reverse($record->tracking_history ?: [['status' => $record->status, 'at' => null, 'payment' => $record->facility_payment_rm]]) as $event): ?>
                        <li class="waste-tracking-item" data-status="<?= e($event['status']) ?>">
                            <span class="waste-tracking-dot" aria-hidden="true"></span>
                            <strong><?= e(\App\Models\WasteRecord::statusLabel($event['status'])) ?></strong>
                            <div class="waste-tracking-meta"><span><?= e($event['at'] ? \Illuminate\Support\Carbon::parse($event['at'])->format('d M Y, g:i A') : 'Previous status · date unavailable') ?></span>
                            <?php if ($event['payment'] !== null): ?>
                                <span>· Facility payment RM <?= e(number_format((float) $event['payment'], 2)) ?></span>
                            <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>
        </main>
    </div>
</body>
</html>
