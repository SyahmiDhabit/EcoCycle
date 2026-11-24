<?php

use App\Models\MarketplaceListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/** @var User|null $business */
/** @var User $company */
/** @var Collection<int, MarketplaceListing> $listings */
$business = $business ?? Auth::guard()->user();
$companyName = $company->business_name ?: $company->name;
$companyLocation = trim(($company->city ?: '').($company->city && $company->state ? ', ' : '').($company->state ?: 'Malaysia'));
$avatarInitials = strtoupper(substr($companyName, 0, 2));
$activeNav = 'overview';
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($companyName) ?> &mdash; Business Profile &middot; EcoCycle</title>
    <link rel="icon" type="image/svg+xml" href="/images/figma/ecocycle-leaf.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .company-profile-hero {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 0;
            padding: 28px 32px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 24px;
        }
        .company-profile-main {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .company-profile-avatar {
            width: 72px;
            height: 72px;
            border-radius: 0;
            background: #eef6ec;
            border: 1px solid #b7deb0;
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
            border-radius: 0;
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

        .company-profile-hero .info-cards-grid {
            flex: 0 0 100%;
            min-width: 0;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 0;
            padding-top: 18px;
            border-top: 1px solid var(--card-border);
        }

        .company-profile-hero .info-spec-card {
            min-width: 0;
            padding: 0;
            border: 0;
            background: transparent;
        }

        .company-address {
            grid-column: 1 / -1;
        }

        body.business-dashboard-page :is(.company-back, .company-contact, .company-contact-secondary, .company-marketplace-button) {
            min-height: 40px;
            align-items: center;
            justify-content: center;
            border-radius: 6px !important;
            text-decoration: none;
        }

        .company-back {
            min-width: 120px;
        }

        .company-marketplace-button {
            width: 100%;
        }

        :is(.company-back, .company-contact-secondary, .company-marketplace-button) {
            border-color: #9abb9f;
            color: #14532d;
        }

        :is(.company-back, .company-contact-secondary, .company-marketplace-button):hover {
            background: #e5efe1;
        }

        .company-contact {
            background: #176b40;
            border-color: #176b40;
        }

        .company-contact:hover {
            background: #10472c;
        }

        @media (max-width: 900px) {
            .company-profile-hero .info-cards-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .company-profile-hero .info-cards-grid {
                grid-template-columns: minmax(0, 1fr);
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
            <!-- Page Title Header -->
            <div class="page-header">
                <div>
                    <h1>Company profile</h1>
                    <p>View verified Malaysian SME details, credentials, and available marketplace materials.</p>
                </div>
                <div>
                    <a href="/business" class="btn-back-directory company-back">
                        <span>Back</span>
                    </a>
                </div>
            </div>

            <!-- Company Profile Hero Card -->
            <section class="company-profile-hero">
                <div class="company-profile-main">
                    <div class="company-profile-avatar" style="overflow:hidden;padding:0;">
                        <?php if ($company->profile_image) { ?>
                            <img src="<?= e($company->profile_image_url) ?>" alt="<?= e($companyName) ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
                        <?php } else { ?>
                            <?= e($avatarInitials) ?>
                        <?php } ?>
                    </div>
                    <div class="company-profile-info">
                        <h1><?= e($companyName) ?></h1>
                        <div class="company-meta-row">
                            <span class="verified-pill">
                                <span>Verified EcoCycle SME</span>
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
                    <a href="https://mail.google.com/mail/?view=cm&amp;fs=1&amp;to=<?= e(rawurlencode($company->email)) ?>&amp;su=EcoCycle%20Circular%20Material%20Inquiry" class="btn-search company-contact" target="_blank" rel="noopener noreferrer">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        <span>Contact Company</span>
                    </a>
                </div>
                <div class="info-cards-grid" aria-label="Company details">
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
                    <?php if (! empty($company->registered_address)) { ?>
                        <div class="info-spec-card company-address">
                            <small>Registered Facility Address</small>
                            <span><?= e($company->registered_address) ?>, <?= e($company->city) ?>, <?= e($company->state) ?></span>
                        </div>
                    <?php } ?>
                </div>
            </section>

            <!-- ACTIVE SURPLUS LISTINGS -->
            <section aria-labelledby="listings-heading">
                <div class="listings-section-header">
                    <div>
                        <h2 id="listings-heading">Active Material Listings</h2>
                        <p style="margin:0;font-size:12px;color:var(--text-muted);">Surplus inventory and recyclable materials published by <?= e($companyName) ?>.</p>
                    </div>
                </div>

                <?php if ($listings->isEmpty()) { ?>
                    <div style="background:#ffffff;border:1px solid var(--card-border);border-radius:0;padding:48px 24px;text-align:center;">
                        <span class="signin-brand-mark" style="margin:0 auto 12px;"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                        <h3 style="margin:0 0 6px;font-size:15px;color:#123321;">No active listings at this time</h3>
                        <p style="margin:0 0 16px;font-size:12px;color:var(--text-muted);max-width:440px;margin-inline:auto;">
                            <?= e($companyName) ?> has not published surplus materials to the marketplace yet. You can still reach out via email to discuss material needs.
                        </p>
                        <a href="https://mail.google.com/mail/?view=cm&amp;fs=1&amp;to=<?= e(rawurlencode($company->email)) ?>&amp;su=EcoCycle%20Circular%20Material%20Inquiry" class="btn-reset company-contact-secondary" target="_blank" rel="noopener noreferrer">
                            Contact Company Directly &rarr;
                        </a>
                    </div>
                <?php } else { ?>
                    <div class="listings-grid">
                        <?php foreach ($listings as $item) { ?>
                            <article class="business-card">
                                <div class="card-image-wrap">
                                    <?php if (! empty($item->image_path)) { ?>
                                        <img src="<?= e($item->image_path) ?>" alt="<?= e($item->title) ?>" loading="lazy">
                                    <?php } else { ?>
                                        <div style="width:100%;height:100%;display:grid;place-items:center;background:#f0f6ee;">
                                            <span class="signin-brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                                        </div>
                                    <?php } ?>
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

                                    <?php if ($item->is_free) { ?>
                                        <div style="font-weight:700;color:#15803d;margin-bottom:12px;">Free Surplus</div>
                                    <?php } else { ?>
                                        <div style="font-weight:700;color:var(--brand-green);margin-bottom:12px;">
                                            RM <?= number_format((float) $item->price, 2) ?> <span style="font-size:11px;font-weight:400;color:#6f7b72;">/ <?= e($item->unit) ?></span>
                                        </div>
                                    <?php } ?>

                                    <div class="card-footer" style="padding-top:10px;">
                                        <a href="/business/marketplace?q=<?= urlencode($item->title) ?>" class="btn-view-profile company-marketplace-button">
                                            Order on Marketplace
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php } ?>
                    </div>
                <?php } ?>
            </section>
        </main>
    </div>

    <script>
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
