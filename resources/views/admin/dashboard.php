<?php

use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\ViewErrorBag;

/** @var Collection<int, User> $businesses */
/** @var int $pendingCount */
/** @var int $approvedCount */
/** @var int $rejectedCount */
/** @var Collection<int, AdminNotification> $notifications */
/** @var int $unreadNotificationsCount */
/** @var ViewErrorBag $errors */
$notifications = $notifications ?? collect();
$unreadNotificationsCount = $unreadNotificationsCount ?? 0;
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Business review - EcoCycle</title>
    <link rel="icon" type="image/svg+xml" href="/images/figma/ecocycle-leaf.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/admin/review.css?v=<?= filemtime(public_path('css/admin/review.css')) ?>">
    <link rel="stylesheet" href="/css/site-preferences.css?v=<?= filemtime(public_path('css/site-preferences.css')) ?>">
    <script src="/js/site-theme.js?v=<?= filemtime(public_path('js/site-theme.js')) ?>"></script>
    <script src="/js/language-switcher.js?v=<?= filemtime(public_path('js/language-switcher.js')) ?>" defer></script>
</head>
<body class="admin-page">
<header class="admin-header admin-workspace-header">
    <div class="admin-header-start">
        <button type="button" class="admin-menu-button" id="admin-menu-toggle" aria-label="Open admin menu" aria-controls="admin-navigation" aria-haspopup="dialog">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <a class="admin-brand" href="/admin/dashboard" aria-label="EcoCycle admin dashboard">
            <span class="brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
            <span>EcoCycle</span>
        </a>
    </div>
    <div class="admin-header-actions">
        <!-- Admin In-System Notifications Bell -->
        <div class="admin-notif-wrap" id="admin-notif-container">
            <button type="button" class="admin-notif-btn" id="admin-notif-toggle" onclick="toggleAdminNotifications()" aria-label="Notifications (<?= $unreadNotificationsCount ?> unread)" title="Notifications" aria-expanded="false" aria-controls="admin-notif-dropdown">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <?php if ($unreadNotificationsCount > 0) { ?>
                    <span class="admin-notif-badge" id="admin-notif-badge"><?= $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount ?></span>
                <?php } ?>
            </button>

            <div class="admin-notif-dropdown" id="admin-notif-dropdown">
                <div class="admin-notif-header">
                    <h4>
                        <span>Notifications</span>
                        <?php if ($unreadNotificationsCount > 0) { ?>
                            <span class="admin-notif-count" id="admin-notif-header-count"><?= $unreadNotificationsCount ?> unread</span>
                        <?php } ?>
                    </h4>
                    <?php if ($unreadNotificationsCount > 0) { ?>
                        <button type="button" class="admin-notif-mark-btn" id="admin-notif-mark-all" onclick="markAllAdminNotificationsRead()">Mark all as read</button>
                    <?php } ?>
                </div>

                <div class="admin-notif-list" id="admin-notif-items">
                    <?php if ($notifications->isEmpty()) { ?>
                        <div class="admin-notif-empty">
                            <p style="margin:0;">No notifications yet.</p>
                        </div>
                    <?php } else { ?>
                        <?php foreach ($notifications as $notif) {
                            $destination = $notif->link ?: '/admin/dashboard';
                            if ($destination === '/admin/dashboard' && $notif->business_id) {
                                $destination .= '#business-card-'.$notif->business_id;
                            }
                            ?>
                            <a href="<?= e($destination) ?>" data-read-url="<?= e(route('admin.notifications.read', $notif->id)) ?>" class="admin-notif-item <?= ! $notif->is_read ? 'unread' : '' ?> <?= $notif->type === 'application_resubmitted' ? 'type-resubmitted' : '' ?>">
                                <div class="admin-notif-icon">
                                    <?php if ($notif->type === 'application_resubmitted') { ?>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="23 4 23 10 17 10"></polyline>
                                            <polyline points="1 20 1 14 7 14"></polyline>
                                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                                        </svg>
                                    <?php } else { ?>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="8.5" cy="7" r="4"></circle>
                                            <line x1="20" y1="8" x2="20" y2="14"></line>
                                            <line x1="23" y1="11" x2="17" y2="11"></line>
                                        </svg>
                                    <?php } ?>
                                </div>
                                <div class="admin-notif-body">
                                    <div class="admin-notif-title"><?= e($notif->title) ?></div>
                                    <div class="admin-notif-msg"><?= e($notif->message) ?></div>
                                    <div class="admin-notif-time"><?= $notif->created_at ? e($notif->created_at->diffForHumans()) : 'Just now' ?></div>
                                </div>
                            </a>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>

        <?php include resource_path('views/components/site-preferences.php'); ?>
    </div>
</header>
<dialog class="admin-navigation" id="admin-navigation" aria-labelledby="admin-navigation-title">
    <div class="admin-navigation-heading">
        <div class="admin-brand">
            <span class="brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
            <span>EcoCycle <small id="admin-navigation-title">Admin workspace</small></span>
        </div>
        <form method="dialog">
            <button type="submit" class="admin-menu-button" aria-label="Close admin menu" autofocus>&times;</button>
        </form>
    </div>
    <nav aria-labelledby="admin-navigation-title">
        <?php
        $adminMenuItems = [
            ['Dashboard', '#admin-overview', 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z'],
            ['Business Management', '#admin-applications', 'M3 21h18M5 21V7h14v14M9 7V3h6v4M9 11h1m4 0h1m-6 4h1m4 0h1'],
            ['Inventory Management', null, 'm12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5m-9 5v8'],
            ['Exchange Management', null, 'M3 7h17m-4-4 4 4-4 4m5 6H4m4-4-4 4 4 4'],
            ['Waste Management', null, 'M3 6h18M8 6V3h8v3M6 6l1 15h10l1-15M10 10v7m4-7v7'],
            ['Reports & Analytics', null, 'M3 3v18h18M8 16v-4m5 4V8m5 8V5'],
            ['Settings', null, 'M4 6h16M4 12h16M4 18h16M8 4v4m8 2v4m-6 2v4'],
        ];
        ?>
        <?php foreach ($adminMenuItems as [$label, $destination, $iconPath]) { ?>
            <?php if ($destination) { ?>
                <a class="admin-nav-item" href="<?= e($destination) ?>">
            <?php } else { ?>
                <span class="admin-nav-item" aria-disabled="true">
            <?php } ?>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="<?= e($iconPath) ?>"/>
                </svg>
                <span><?= e($label) ?></span>
            <?php if ($destination) { ?>
                </a>
            <?php } else { ?>
                </span>
            <?php } ?>
        <?php } ?>
    </nav>
    <div class="admin-navigation-footer">
        <form method="POST" action="<?= e(route('logout')) ?>">
            <?= csrf_field() ?>
            <button class="admin-navigation-logout" type="submit">Sign Out</button>
        </form>
    </div>
</dialog>
<main class="admin-content admin-workspace" id="admin-overview">


    <?php if (session('status')) { ?>
        <div class="admin-success" role="status"><?= e(session('status')) ?></div>
    <?php } ?>
    <?php if ($errors->any()) { ?>
        <div class="admin-alert" role="alert"><?= e($errors->first()) ?></div>
    <?php } ?>

    <div class="admin-dashboard-overview">
        <header class="admin-title admin-welcome">
            <div>
                <h1>Welcome, Administrator</h1>
                <p class="admin-muted">Track business registrations and review applications that need your attention.</p>
            </div>
            <a class="admin-primary" href="#admin-applications">View all applications</a>
        </header>
        <section class="admin-stats" aria-label="Application totals">
            <article>
                <span>Awaiting review</span>
                <strong><?= $pendingCount ?></strong>
            </article>
            <article>
                <span>Approved businesses</span>
                <strong><?= $approvedCount ?></strong>
            </article>
            <article>
                <span>Changes requested</span>
                <strong><?= $rejectedCount ?></strong>
            </article>
        </section>

        <section class="admin-pending-panel" aria-labelledby="admin-pending-title">
            <h2 id="admin-pending-title">Latest registrations awaiting review</h2>
            <?php $latestPending = $businesses->where('account_status', 'pending')->take(5); ?>
            <?php if ($latestPending->isEmpty()) { ?>
                <p class="admin-pending-empty">No registrations are awaiting review.</p>
            <?php } else { ?>
                <ul>
                    <?php foreach ($latestPending as $pendingBusiness) { ?>
                        <li>
                            <div>
                                <strong><?= e($pendingBusiness->business_name ?: $pendingBusiness->name) ?></strong>
                                <span><?= e($pendingBusiness->created_at?->timezone('Asia/Kuala_Lumpur')->format('j M Y, g:i A')) ?> MYT</span>
                            </div>
                            <a class="admin-outline" href="#business-card-<?= $pendingBusiness->id ?>">Review application</a>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </section>
    </div>

    <section class="application-list" id="admin-applications">
        <h2>Submitted applications <span><?= $businesses->count() ?></span></h2>
        <?php if ($businesses->isEmpty()) { ?>
            <p class="admin-empty">No business applications have been submitted yet.</p>
        <?php } ?>

        <?php foreach ($businesses as $business) { ?>
            <?php
            $bizInitials = strtoupper(substr($business->business_name ?: $business->name, 0, 2));
            $fullAddress = implode(', ', array_filter([
                $business->registered_address,
                $business->postcode,
                $business->city,
                $business->state,
            ]));
            ?>
            <article class="application-card" id="business-card-<?= $business->id ?>">
                <div class="application-heading">
                    <div class="application-identity">
                        <div class="business-profile-avatar">
                            <?php if ($business->profile_image) { ?>
                                <img src="<?= e($business->profile_image_url) ?>" alt="<?= e($business->business_name) ?>">
                            <?php } else { ?>
                                <span><?= e($bizInitials) ?></span>
                            <?php } ?>
                        </div>
                        <div>
                            <h3><?= e($business->business_name) ?></h3>
                            <p>
                                @<?= e($business->name) ?> &middot; <?= e($business->email) ?> &middot; <?= e($business->business_phone) ?>
                            </p>
                        </div>
                    </div>
                    <div class="application-heading-actions">
                        <button type="button" class="btn-view-biz-profile" onclick='openBusinessModal(<?= json_encode([
                            'id' => $business->id,
                            'business_name' => $business->business_name,
                            'name' => $business->name,
                            'email' => $business->email,
                            'phone' => $business->business_phone,
                            'registration_no' => $business->business_registration_number,
                            'industry' => $business->industry,
                            'address' => $fullAddress,
                            'city' => $business->city,
                            'state' => $business->state,
                            'status' => ucfirst($business->account_status),
                            'image' => $business->profile_image_url,
                            'inventory_count' => $business->inventory_items_count ?? 0,
                            'listings_count' => $business->marketplace_listings_count ?? 0,
                            'cert_url' => route('admin.business.document', [$business, 'certificate']),
                            'profile_url' => route('admin.business.document', [$business, 'profile']),
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                            View Profile
                        </button>
                        <span class="application-status status-<?= e($business->account_status) ?>">
                            <?= e(ucfirst($business->account_status)) ?>
                        </span>
                    </div>
                </div>

                <dl class="business-details">
                    <div>
                        <dt>SSM number</dt>
                        <dd><strong><?= e($business->business_registration_number) ?></strong></dd>
                    </div>
                    <div>
                        <dt>Industry</dt>
                        <dd><?= e($business->industry) ?></dd>
                    </div>
                    <div>
                        <dt>Location</dt>
                        <dd><?= e($business->city ?: 'N/A') ?>, <?= e($business->state ?: 'Malaysia') ?></dd>
                    </div>
                    <div>
                        <dt>Registered address</dt>
                        <dd><?= e($fullAddress ?: 'Not provided') ?></dd>
                    </div>
                    <div>
                        <dt>Circular Operations</dt>
                        <dd><?= (int) ($business->inventory_items_count ?? 0) ?> inventory items &middot; <?= (int) ($business->marketplace_listings_count ?? 0) ?> listings</dd>
                    </div>
                    <div>
                        <dt>Submitted Date</dt>
                        <dd><?= e($business->created_at?->timezone('Asia/Kuala_Lumpur')->format('j M Y, g:i A').' MYT') ?></dd>
                    </div>
                </dl>

                <div class="document-links">
                    <a href="<?= e(route('admin.business.document', [$business, 'certificate'])) ?>" target="_blank" rel="noopener">
                        <img src="/images/figma/verification/file-check.svg" alt="">
                        SSM registration certificate
                    </a>
                    <a href="<?= e(route('admin.business.document', [$business, 'profile'])) ?>" target="_blank" rel="noopener">
                        <img src="/images/figma/verification/file-check.svg" alt="">
                        SSM company profile
                    </a>
                </div>

                <?php if ($business->review_note) { ?>
                    <div class="review-note">
                        <strong>Review note</strong>
                        <p><?= e($business->review_note) ?></p>
                    </div>
                <?php } ?>

                <div class="application-actions">
                    <?php if ($business->account_status !== 'approved') { ?>
                        <form method="POST" action="<?= e(route('admin.business.approve', $business)) ?>">
                            <?= csrf_field() ?>
                            <button class="admin-primary" type="submit">Approve business</button>
                        </form>
                    <?php } else { ?>
                        <span class="application-approval">
                            ✓ Verified SME &middot; Approved on <?= $business->reviewed_at ? e($business->reviewed_at->timezone('Asia/Kuala_Lumpur')->format('j M Y')) : 'Record' ?>
                        </span>
                    <?php } ?>
                    <div class="application-decision-buttons" data-business-name="<?= e($business->business_name) ?>">
                        <button class="admin-warning" type="button" data-review-url="<?= e(route('admin.business.request-changes', $business)) ?>" data-review-action="request_changes">Request changes</button>
                        <button class="admin-danger" type="button" data-review-url="<?= e(route('admin.business.reject', $business)) ?>" data-review-action="reject">Reject</button>
                    </div>
                </div>
            </article>
        <?php } ?>
    </section>
</main>
<dialog class="admin-review-dialog" id="admin-review-dialog" aria-labelledby="admin-review-title" aria-describedby="admin-review-business">
    <h2 id="admin-review-title"></h2>
    <p id="admin-review-business"></p>
    <form id="admin-review-form" method="POST">
        <?= csrf_field() ?>
        <label for="admin-review-note">Reason or remarks</label>
        <textarea id="admin-review-note" name="review_note" rows="4" maxlength="1000" required autofocus></textarea>
        <div class="application-decision-buttons">
            <button class="admin-outline" type="button" id="admin-review-cancel">Cancel</button>
            <button type="submit" name="action_type" id="admin-review-submit"></button>
        </div>
    </form>
</dialog>

<!-- Full Business Profile Modal for Admin -->
<div class="admin-modal-backdrop" id="admin-biz-modal" style="display:none;" onclick="if(event.target === this) closeBusinessModal()">
    <div class="admin-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modal-admin-title">
        <div class="admin-modal-header">
            <h3 id="modal-admin-title">Company Profile Details</h3>
            <button type="button" class="admin-modal-close" onclick="closeBusinessModal()" aria-label="Close">&times;</button>
        </div>
        <div class="admin-modal-body">
            <div style="display:flex;align-items:center;gap:16px;padding-bottom:14px;border-bottom:1px solid var(--line);">
                <div id="modal-biz-avatar" class="business-profile-avatar" style="width:64px;height:64px;">
                    <span id="modal-biz-initials" style="font-size:22px;">EC</span>
                </div>
                <div>
                    <h2 id="modal-biz-name" style="margin:0 0 3px;font-size:18px;color:#123321;">Company Name</h2>
                    <span id="modal-biz-status" class="application-status status-approved">Approved</span>
                    <span id="modal-biz-username" style="margin-left:8px;font-size:12px;color:var(--muted);">@username</span>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;background:#fbfdfa;border:1px solid #e1e9df;border-radius:8px;padding:14px 16px;">
                <div>
                    <small style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:700;">SSM Number</small>
                    <span id="modal-biz-ssm" style="font-size:12px;font-weight:600;color:#183624;">-</span>
                </div>
                <div>
                    <small style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:700;">Industry</small>
                    <span id="modal-biz-industry" style="font-size:12px;font-weight:600;color:#183624;">-</span>
                </div>
                <div>
                    <small style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:700;">Phone</small>
                    <span id="modal-biz-phone" style="font-size:12px;font-weight:600;color:#183624;">-</span>
                </div>
                <div>
                    <small style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:700;">Email</small>
                    <span id="modal-biz-email" style="font-size:12px;font-weight:600;color:#183624;">-</span>
                </div>
                <div style="grid-column:1 / -1;">
                    <small style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:700;">Registered Address</small>
                    <span id="modal-biz-address" style="font-size:12px;color:#183624;">-</span>
                </div>
                <div>
                    <small style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:700;">Inventory Stock</small>
                    <span id="modal-biz-inv" style="font-size:12px;font-weight:600;color:#183624;">0 items</span>
                </div>
                <div>
                    <small style="display:block;font-size:10px;text-transform:uppercase;color:var(--muted);font-weight:700;">Marketplace Listings</small>
                    <span id="modal-biz-listings" style="font-size:12px;font-weight:600;color:#183624;">0 listings</span>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:8px;">
                <strong style="font-size:12px;color:#123321;">Submitted SSM Documentation</strong>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <a id="modal-cert-link" href="#" target="_blank" rel="noopener" class="admin-outline" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:11px;padding:6px 12px;">
                        <span>📄 Download SSM Certificate</span>
                    </a>
                    <a id="modal-profile-link" href="#" target="_blank" rel="noopener" class="admin-outline" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:11px;padding:6px 12px;">
                        <span>📊 Download SSM Profile</span>
                    </a>
                </div>
            </div>
        </div>
        <div class="admin-modal-footer">
            <button type="button" class="admin-outline" onclick="closeBusinessModal()">Close</button>
        </div>
    </div>
</div>

<script>
const reviewDialog = document.getElementById('admin-review-dialog');
const reviewForm = document.getElementById('admin-review-form');
const reviewSubmit = document.getElementById('admin-review-submit');

document.querySelectorAll('[data-review-url]').forEach((button) => {
    button.addEventListener('click', () => {
        reviewForm.reset();
        reviewForm.action = button.dataset.reviewUrl;
        document.getElementById('admin-review-title').textContent = button.textContent.trim();
        document.getElementById('admin-review-business').textContent = button.parentElement.dataset.businessName;
        reviewSubmit.value = button.dataset.reviewAction;
        reviewSubmit.className = button.className;
        reviewSubmit.textContent = button.textContent.trim();
        reviewDialog.showModal();
    });
});

document.getElementById('admin-review-cancel').addEventListener('click', () => reviewDialog.close());

const adminNavigation = document.getElementById('admin-navigation');
const adminMenuToggle = document.getElementById('admin-menu-toggle');

adminMenuToggle.addEventListener('click', () => adminNavigation.showModal());
adminNavigation.addEventListener('click', (event) => {
    if (event.target === adminNavigation || event.target.closest('nav a')) {
        adminNavigation.close();
    }
});
function openBusinessModal(biz) {
    document.getElementById('modal-biz-name').textContent = biz.business_name || 'Business';
    document.getElementById('modal-biz-username').textContent = '@' + (biz.name || '');
    document.getElementById('modal-biz-ssm').textContent = biz.registration_no || '-';
    document.getElementById('modal-biz-industry').textContent = biz.industry || '-';
    document.getElementById('modal-biz-phone').textContent = biz.phone || '-';
    document.getElementById('modal-biz-email').textContent = biz.email || '-';
    document.getElementById('modal-biz-address').textContent = biz.address || '-';
    document.getElementById('modal-biz-inv').textContent = (biz.inventory_count || 0) + ' items';
    document.getElementById('modal-biz-listings').textContent = (biz.listings_count || 0) + ' active listings';

    var statusEl = document.getElementById('modal-biz-status');
    statusEl.textContent = biz.status || 'Pending';
    statusEl.className = 'application-status status-' + (biz.status || 'pending').toLowerCase();

    var avatarBox = document.getElementById('modal-biz-avatar');
    if (biz.image) {
        avatarBox.innerHTML = '<img src="' + biz.image + '" alt="' + (biz.business_name || '') + '">';
    } else {
        var initials = (biz.business_name || biz.name || 'EC').substring(0, 2).toUpperCase();
        avatarBox.innerHTML = '<span style="font-size:22px;font-weight:700;color:var(--green);">' + initials + '</span>';
    }

    document.getElementById('modal-cert-link').href = biz.cert_url || '#';
    document.getElementById('modal-profile-link').href = biz.profile_url || '#';

    var modal = document.getElementById('admin-biz-modal');
    if (modal) modal.style.display = 'flex';
}

function closeBusinessModal() {
    var modal = document.getElementById('admin-biz-modal');
    if (modal) modal.style.display = 'none';
}

const adminNotificationBell = document.getElementById('admin-notif-toggle');
const adminNotificationPanel = document.getElementById('admin-notif-dropdown');

function setAdminNotificationsOpen(open) {
    adminNotificationPanel.classList.toggle('show', open);
    adminNotificationBell.setAttribute('aria-expanded', String(open));
}

function toggleAdminNotifications() {
    setAdminNotificationsOpen(!adminNotificationPanel.classList.contains('show'));
}

document.addEventListener('click', (event) => {
    if (!document.getElementById('admin-notif-container').contains(event.target)) {
        setAdminNotificationsOpen(false);
    }
});

function updateAdminNotificationCount(count) {
    const badge = document.getElementById('admin-notif-badge');
    const headerCount = document.getElementById('admin-notif-header-count');
    const markAll = document.getElementById('admin-notif-mark-all');

    adminNotificationBell.setAttribute('aria-label', 'Notifications (' + count + ' unread)');
    if (badge) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.hidden = count === 0;
    }
    if (headerCount) {
        headerCount.textContent = count + ' unread';
        headerCount.hidden = count === 0;
    }
    if (markAll) markAll.hidden = count === 0;
}

async function postAdminNotificationRead(url) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '<?= csrf_token() ?>',
            'Accept': 'application/json',
        },
    });

    if (!response.ok || response.redirected) {
        throw new Error('Unable to mark notification as read.');
    }

    return response.json();
}

document.querySelectorAll('.admin-notif-item').forEach((item) => {
    item.addEventListener('click', async (event) => {
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        if (item.dataset.markingRead) return;
        item.dataset.markingRead = 'true';

        try {
            if (item.classList.contains('unread')) {
                const { unread_count } = await postAdminNotificationRead(item.dataset.readUrl);
                item.classList.remove('unread');
                updateAdminNotificationCount(unread_count);
            }
        } catch (error) {
            console.error(error);
        } finally {
            delete item.dataset.markingRead;
        }

        setAdminNotificationsOpen(false);
        window.location.assign(item.href);
    });
});

async function markAllAdminNotificationsRead() {
    try {
        await postAdminNotificationRead('<?= route('admin.notifications.mark-read') ?>');
        updateAdminNotificationCount(0);
        document.querySelectorAll('.admin-notif-item.unread').forEach((item) => item.classList.remove('unread'));
    } catch (error) {
        console.error(error);
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBusinessModal();
        if (adminNotificationPanel.classList.contains('show')) {
            setAdminNotificationsOpen(false);
            adminNotificationBell.focus();
        }
    }
});
</script>
</body>
</html>
