<?php
/** @var \App\Models\User|null $business */
/** @var \App\Models\User $company */
/** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\MarketplaceListing> $listings */

$business = $business ?? \Illuminate\Support\Facades\Auth::guard()->user();
$companyName = $company->business_name ?: $company->name;
$companyLocation = trim(($company->city ?: '') . ($company->city && $company->state ? ', ' : '') . ($company->state ?: 'Malaysia'));
$avatarInitials = strtoupper(substr($companyName, 0, 2));
$activeNav = 'overview';
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($companyName) ?> &mdash; Business Profile &middot; EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css">
    <style>
        .company-profile-hero {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 28px 32px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }
        .company-profile-main {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .company-profile-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #eef6ec;
            border: 2px solid #b7deb0;
            color: #176b40;
            font-size: 24px;
            font-weight: 700;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }
        .company-profile-info h1 {
            margin: 0 0 6px;
            font-size: 24px;
            font-weight: 700;
            color: #123321;
        }
        .company-meta-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 12px;
            color: var(--text-muted);
        }
        .info-cards-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }
        .info-spec-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .info-spec-card small {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
        }
        .info-spec-card span {
            font-size: 13px;
            font-weight: 600;
            color: #143324;
            word-break: break-word;
        }
        .listings-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .listings-section-header h2 {
            margin: 0 0 4px;
            font-size: 18px;
            font-weight: 700;
            color: #123321;
        }
        .listings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        @media(max-width: 900px) {
            .info-cards-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media(max-width: 600px) {
            .info-cards-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="business-dashboard-page">

    <!-- Reusable Professional Top Navigation Bar -->
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <!-- Reusable Left Navigation Sidebar -->
        <?php include resource_path('views/business/partials/sidebar.php'); ?>

        <main class="dashboard-main">
            <!-- Breadcrumbs -->
            <div class="breadcrumb-row">
                <a href="/business">Overview</a>
                <span class="sep">/</span>
                <a href="/business">Business Directory</a>
                <span class="sep">/</span>
                <span class="current"><?= e($companyName) ?></span>
            </div>

            <!-- Company Profile Hero Card -->
            <section class="company-profile-hero">
                <div class="company-profile-main">
                    <div class="company-profile-avatar" style="overflow:hidden;padding:0;">
                        <?php if ($company->profile_image): ?>
                            <img src="<?= e($company->profile_image_url) ?>" alt="<?= e($companyName) ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
                        <?php else: ?>
                            <?= e($avatarInitials) ?>
                        <?php endif; ?>
                    </div>
                    <div class="company-profile-info">
                        <h1><?= e($companyName) ?></h1>
                        <div class="company-meta-row">
                            <span class="verified-pill">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span>Verified Malaysian SME</span>
                            </span>
                            <span>@<?= e($company->name) ?></span>
                            <span>&middot;</span>
                            <span><?= e($company->industry ?: 'General SME') ?></span>
                            <span>&middot;</span>
                            <span><?= e($companyLocation) ?></span>
                        </div>
                    </div>
                </div>

                <div>
                    <a href="mailto:<?= e($company->email) ?>?subject=<?= e(urlencode('EcoCycle Circular Material Inquiry')) ?>" class="btn-search" style="text-decoration:none;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        <span>Contact Company</span>
                    </a>
                </div>
            </section>

            <!-- Company Specifications Grid -->
            <section class="info-cards-grid">
                <div class="info-spec-card">
                    <small>SSM Registration Number</small>
                    <span><?= e($company->business_registration_number ?: 'Verified on SSM') ?></span>
                </div>
                <div class="info-spec-card">
                    <small>Business Phone</small>
                    <span><?= e($company->business_phone ?: 'Not provided') ?></span>
                </div>
                <div class="info-spec-card">
                    <small>Official Email</small>
                    <span><?= e($company->email) ?></span>
                </div>
                <div class="info-spec-card">
                    <small>Operating Location</small>
                    <span><?= e($companyLocation) ?></span>
                </div>
            </section>

            <?php if (!empty($company->registered_address)): ?>
                <div style="background:#ffffff;border:1px solid var(--card-border);border-radius:10px;padding:16px 20px;margin-bottom:28px;">
                    <small style="display:block;font-size:10px;font-weight:700;text-transform:uppercase;color:var(--text-muted);letter-spacing:0.05em;margin-bottom:4px;">Registered Facility Address</small>
                    <span style="font-size:13px;color:#143324;"><?= e($company->registered_address) ?>, <?= e($company->city) ?>, <?= e($company->state) ?></span>
                </div>
            <?php endif; ?>

            <!-- ACTIVE SURPLUS LISTINGS -->
            <section aria-labelledby="listings-heading">
                <div class="listings-section-header">
                    <div>
                        <h2 id="listings-heading">Active Material Listings</h2>
                        <p style="margin:0;font-size:12px;color:var(--text-muted);">Surplus inventory and recyclable materials published by <?= e($companyName) ?>.</p>
                    </div>
                </div>

                <?php if ($listings->isEmpty()): ?>
                    <div style="background:#ffffff;border:1px solid var(--card-border);border-radius:12px;padding:48px 24px;text-align:center;">
                        <span class="signin-brand-mark" style="margin:0 auto 12px;"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                        <h3 style="margin:0 0 6px;font-size:15px;color:#123321;">No active listings at this time</h3>
                        <p style="margin:0 0 16px;font-size:12px;color:var(--text-muted);max-width:440px;margin-inline:auto;">
                            <?= e($companyName) ?> has not published surplus materials to the marketplace yet. You can still reach out via email to discuss material needs.
                        </p>
                        <a href="mailto:<?= e($company->email) ?>" class="btn-reset" style="border:1px solid #cadbc9;border-radius:6px;padding:6px 14px;text-decoration:none;">
                            Contact Company Directly &rarr;
                        </a>
                    </div>
                <?php else: ?>
                    <div class="listings-grid">
                        <?php foreach ($listings as $item): ?>
                            <article class="business-card">
                                <div class="card-image-wrap">
                                    <?php if (!empty($item->image_path)): ?>
                                        <img src="<?= e($item->image_path) ?>" alt="<?= e($item->title) ?>" loading="lazy">
                                    <?php else: ?>
                                        <div style="width:100%;height:100%;display:grid;place-items:center;background:#f0f6ee;">
                                            <span class="signin-brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <div class="card-badges">
                                        <span class="verified-pill">Active Listing</span>
                                        <span class="category-tag"><?= e($item->category) ?></span>
                                    </div>
                                    <h3 class="card-title"><?= e($item->title) ?></h3>
                                    <p class="card-description"><?= e($item->description) ?></p>

                                    <div style="margin:8px 0;font-size:12px;">
                                        <strong>Available:</strong> <?= (float) $item->quantity ?> <?= e($item->unit) ?> &middot; <?= e($item->condition ?: 'Good surplus') ?>
                                    </div>

                                    <?php if ($item->is_free): ?>
                                        <div style="font-weight:700;color:#15803d;margin-bottom:12px;">Free Surplus</div>
                                    <?php else: ?>
                                        <div style="font-weight:700;color:var(--brand-green);margin-bottom:12px;">
                                            RM <?= number_format((float) $item->price, 2) ?> <span style="font-size:11px;font-weight:400;color:#6f7b72;">/ <?= e($item->unit) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="card-footer" style="padding-top:10px;">
                                        <a href="mailto:<?= e($company->email) ?>?subject=<?= e(urlencode('Inquiry for ' . $item->title)) ?>" class="btn-view-profile" style="width:100%;justify-content:center;text-decoration:none;">
                                            Inquire About This Material &rarr;
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
    function toggleSidebar() {
        var sidebar = document.getElementById('dashboard-sidebar');
        if (sidebar) sidebar.classList.toggle('open');
    }
    function toggleNotificationMenu(e) {
        e.stopPropagation();
        var menu = document.getElementById('notification-dropdown');
        if (menu) menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }
    document.addEventListener('click', function() {
        var menu = document.getElementById('notification-dropdown');
        if (menu) menu.style.display = 'none';
    });
    </script>
</body>
</html>
