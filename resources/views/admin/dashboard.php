<?php
/** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $businesses */
/** @var int $pendingCount */
/** @var int $approvedCount */
/** @var int $rejectedCount */
/** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\AdminNotification> $notifications */
/** @var int $unreadNotificationsCount */
/** @var \Illuminate\Support\ViewErrorBag $errors */
$adminUser = \Illuminate\Support\Facades\Auth::guard('admin')->user();
$adminName = $adminUser?->name ?? 'Administrator';
$notifications = $notifications ?? collect();
$unreadNotificationsCount = $unreadNotificationsCount ?? 0;
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Business review - EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/admin/review.css">
</head>
<body class="admin-page">
<header class="admin-header">
    <a class="admin-brand" href="/" aria-label="EcoCycle home">
        <img src="/images/figma/verification/brand-mark.svg" alt="">
        <span>EcoCycle</span>
    </a>
    <div class="admin-header-actions">
        <!-- Admin In-System Notifications Bell -->
        <div class="admin-notif-wrap" id="admin-notif-container">
            <button type="button" class="admin-notif-btn" id="admin-notif-toggle" onclick="toggleAdminNotifications()" aria-label="Admin Notifications" title="Platform Notifications">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <?php if ($unreadNotificationsCount > 0): ?>
                    <span class="admin-notif-badge" id="admin-notif-badge"><?= $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount ?></span>
                <?php endif; ?>
            </button>

            <div class="admin-notif-dropdown" id="admin-notif-dropdown">
                <div class="admin-notif-header">
                    <h4>
                        <span>Notifications</span>
                        <?php if ($unreadNotificationsCount > 0): ?>
                            <span style="font-size:10px;background:#eef5ee;color:var(--green);padding:2px 6px;border-radius:10px;" id="admin-notif-header-count"><?= $unreadNotificationsCount ?> unread</span>
                        <?php endif; ?>
                    </h4>
                    <?php if ($unreadNotificationsCount > 0): ?>
                        <button type="button" class="admin-notif-mark-btn" id="admin-notif-mark-all" onclick="markAllAdminNotificationsRead()">Mark all as read</button>
                    <?php endif; ?>
                </div>

                <div class="admin-notif-list" id="admin-notif-items">
                    <?php if ($notifications->isEmpty()): ?>
                        <div class="admin-notif-empty">
                            <p style="margin:0;">No notifications yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                            <a href="<?= e($notif->link ?: '/admin') ?>" class="admin-notif-item <?= ! $notif->is_read ? 'unread' : '' ?> <?= $notif->type === 'application_resubmitted' ? 'type-resubmitted' : '' ?>">
                                <div class="admin-notif-icon">
                                    <?php if ($notif->type === 'application_resubmitted'): ?>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="23 4 23 10 17 10"></polyline>
                                            <polyline points="1 20 1 14 7 14"></polyline>
                                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="8.5" cy="7" r="4"></circle>
                                            <line x1="20" y1="8" x2="20" y2="14"></line>
                                            <line x1="23" y1="11" x2="17" y2="11"></line>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="admin-notif-body">
                                    <div class="admin-notif-title"><?= e($notif->title) ?></div>
                                    <div class="admin-notif-msg"><?= e($notif->message) ?></div>
                                    <div class="admin-notif-time"><?= $notif->created_at ? e($notif->created_at->diffForHumans()) : 'Just now' ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <span>Administrator · <?= e($adminName) ?></span>
        <form method="POST" action="<?= e(route('logout')) ?>">
            <?= csrf_field() ?>
            <button class="admin-outline" type="submit">Sign out</button>
        </form>
    </div>
</header>
<main class="admin-content">
    <div class="admin-title">
        <div>
            <p class="admin-eyebrow">PLATFORM ADMINISTRATION</p>
            <h1>Business applications &amp; directory review</h1>
            <p class="admin-muted">Review submitted company profiles, credentials, and SSM documents before enabling business access.</p>
        </div>
    </div>

    <?php if (session('status')): ?>
        <div class="admin-success" role="status"><?= e(session('status')) ?></div>
    <?php endif; ?>
    <?php if ($errors->any()): ?>
        <div class="admin-alert" role="alert"><?= e($errors->first()) ?></div>
    <?php endif; ?>

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

    <section class="application-list">
        <h2>Submitted applications <span><?= $businesses->count() ?></span></h2>
        <?php if ($businesses->isEmpty()): ?>
            <p class="admin-empty">No business applications have been submitted yet.</p>
        <?php endif; ?>

        <?php foreach ($businesses as $business): ?>
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
                    <div style="display:flex;align-items:center;gap:14px;">
                        <div class="business-profile-avatar">
                            <?php if ($business->profile_image): ?>
                                <img src="<?= e($business->profile_image_url) ?>" alt="<?= e($business->business_name) ?>">
                            <?php else: ?>
                                <span><?= e($bizInitials) ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h3 style="margin:0 0 2px;font-size:16px;color:#123321;"><?= e($business->business_name) ?></h3>
                            <p style="margin:0;font-size:12px;color:var(--muted);">
                                @<?= e($business->name) ?> &middot; <?= e($business->email) ?> &middot; <?= e($business->business_phone) ?>
                            </p>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <button type="button" class="btn-view-biz-profile" onclick='openBusinessModal(<?= json_encode([
                            "id" => $business->id,
                            "business_name" => $business->business_name,
                            "name" => $business->name,
                            "email" => $business->email,
                            "phone" => $business->business_phone,
                            "registration_no" => $business->business_registration_number,
                            "industry" => $business->industry,
                            "address" => $fullAddress,
                            "city" => $business->city,
                            "state" => $business->state,
                            "status" => ucfirst($business->account_status),
                            "image" => $business->profile_image_url,
                            "inventory_count" => $business->inventory_items_count ?? 0,
                            "listings_count" => $business->marketplace_listings_count ?? 0,
                            "cert_url" => route("admin.business.document", [$business, "certificate"]),
                            "profile_url" => route("admin.business.document", [$business, "profile"]),
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                            👁 View Profile
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
                    <a href="<?= e(route('admin.business.document', [$business, 'certificate'])) ?>">
                        <img src="/images/figma/verification/file-check.svg" alt="">
                        SSM registration certificate
                    </a>
                    <a href="<?= e(route('admin.business.document', [$business, 'profile'])) ?>">
                        <img src="/images/figma/verification/file-check.svg" alt="">
                        SSM company profile
                    </a>
                </div>

                <?php if ($business->review_note): ?>
                    <div class="review-note">
                        <strong>Review note</strong>
                        <p><?= e($business->review_note) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($business->account_status !== 'approved'): ?>
                    <div class="application-actions">
                        <form method="POST" action="<?= e(route('admin.business.approve', $business)) ?>">
                            <?= csrf_field() ?>
                            <button class="admin-primary" type="submit">Approve business</button>
                        </form>
                        <form class="reject-form" method="POST" action="<?= e(route('admin.business.reject', $business)) ?>">
                            <?= csrf_field() ?>
                            <label class="visually-hidden" for="review-note-<?= $business->id ?>">Reason for changes</label>
                            <input id="review-note-<?= $business->id ?>" name="review_note" value="<?= e(old('review_note')) ?>" placeholder="Explain what needs to change" required maxlength="1000">
                            <button class="admin-danger" type="submit">Request changes</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div style="margin-top:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                        <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--green);font-weight:600;">
                            ✓ Verified SME &middot; Approved on <?= $business->reviewed_at ? e($business->reviewed_at->timezone('Asia/Kuala_Lumpur')->format('j M Y')) : 'Record' ?>
                        </span>
                        <form class="reject-form" style="max-width:440px;" method="POST" action="<?= e(route('admin.business.reject', $business)) ?>">
                            <?= csrf_field() ?>
                            <label class="visually-hidden" for="re-review-note-<?= $business->id ?>">Re-review note</label>
                            <input id="re-review-note-<?= $business->id ?>" name="review_note" placeholder="Request updates/changes..." required maxlength="1000">
                            <button class="admin-danger" type="submit" style="white-space:nowrap;">Request re-review</button>
                        </form>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
</main>

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
                    <a id="modal-cert-link" href="#" class="admin-outline" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:11px;padding:6px 12px;">
                        <span>📄 Download SSM Certificate</span>
                    </a>
                    <a id="modal-profile-link" href="#" class="admin-outline" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:11px;padding:6px 12px;">
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

function toggleAdminNotifications() {
    var dd = document.getElementById('admin-notif-dropdown');
    if (!dd) return;
    dd.classList.toggle('show');
}

document.addEventListener('click', function(e) {
    var wrap = document.getElementById('admin-notif-container');
    var dd = document.getElementById('admin-notif-dropdown');
    if (wrap && dd && !wrap.contains(e.target)) {
        dd.classList.remove('show');
    }
});

function markAllAdminNotificationsRead() {
    fetch('<?= route('admin.notifications.mark-read') ?>', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '<?= csrf_token() ?>',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    }).then(function(res) {
        if (res.ok) {
            var badge = document.getElementById('admin-notif-badge');
            if (badge) badge.style.display = 'none';

            var headerCount = document.getElementById('admin-notif-header-count');
            if (headerCount) headerCount.style.display = 'none';

            var markBtn = document.getElementById('admin-notif-mark-all');
            if (markBtn) markBtn.style.display = 'none';

            var unreadItems = document.querySelectorAll('.admin-notif-item.unread');
            unreadItems.forEach(function(item) {
                item.classList.remove('unread');
            });
        }
    }).catch(function(err) {
        console.error('Failed to mark notifications read:', err);
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBusinessModal();
        var dd = document.getElementById('admin-notif-dropdown');
        if (dd) dd.classList.remove('show');
    }
});
</script>
<script src="/js/language-switcher.js" defer></script>
</body>
</html>
