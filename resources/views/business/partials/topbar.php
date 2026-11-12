<?php
/** @var \App\Models\User|null $business */
$business = $business ?? \Illuminate\Support\Facades\Auth::guard()->user();
$companyName = $business?->business_name ?: ($business?->name ?? 'EcoCycle Business');
$avatarInitials = strtoupper(substr($companyName, 0, 2));

$realNotifications = $business?->notifications()->take(8)->get() ?? collect();
$unreadNotificationsCount = $realNotifications->where('is_read', false)->count();
?>
<!-- EcoCycle Clean Professional Topbar -->
<header class="topbar" role="banner">
    <div class="topbar-left">
        <button type="button" class="sidebar-toggle-btn" onclick="toggleSidebar()" aria-label="Toggle navigation menu" title="Toggle Menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <a class="dashboard-brand" href="/business" aria-label="EcoCycle home">
            <span class="brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
            <span class="dashboard-brand-name">EcoCycle</span>
        </a>
    </div>

    <div class="topbar-right">
        <!-- Notification Bell with Modern Dropdown -->
        <div class="topbar-dropdown-wrapper">
            <button type="button" class="topbar-icon-btn" id="notif-toggle-btn" onclick="toggleNotificationMenu(event)" aria-label="Notifications" title="Notifications">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span class="notification-dot" id="header-notif-dot" style="<?= $unreadNotificationsCount > 0 ? '' : 'display:none;' ?>"></span>
            </button>

            <div class="topbar-dropdown notification-dropdown" id="notification-dropdown" style="display:none;" onclick="event.stopPropagation()">
                <div class="dropdown-header">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <strong>Notifications</strong>
                        <span class="badge-unread" id="notif-count-badge" style="<?= $unreadNotificationsCount > 0 ? '' : 'background:#eaf0ea;color:#617769;border-color:#d0ded0;' ?>"><?= $unreadNotificationsCount ?> new</span>
                    </div>
                    <?php if ($unreadNotificationsCount > 0): ?>
                        <button type="button" class="notif-mark-btn" id="notif-mark-all-btn" onclick="markAllNotificationsAsRead(event)">Mark all as read</button>
                    <?php endif; ?>
                </div>

                <div class="dropdown-body" id="notif-list-body">
                    <?php if ($realNotifications->isEmpty()): ?>
                        <div style="padding:32px 20px;text-align:center;">
                            <div class="notif-icon-circle green" style="margin:0 auto 10px;width:38px;height:38px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                            </div>
                            <p style="margin:0 0 4px;font-size:12px;font-weight:700;color:#143622;">No notifications yet</p>
                            <small style="display:block;color:#617769;font-size:11px;line-height:1.4;">Real updates on verification, transactions, and inventory will appear here.</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($realNotifications as $notif): ?>
                            <a href="<?= e($notif->link ?: '/business') ?>" class="notification-item <?= !$notif->is_read ? 'unread' : '' ?>" style="text-decoration:none;color:inherit;">
                                <div class="notif-icon-circle <?= e($notif->icon_type) ?>">
                                    <?php if ($notif->icon_type === 'green'): ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    <?php elseif ($notif->icon_type === 'amber'): ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="12" y1="8" x2="12" y2="12"></line>
                                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="notif-content">
                                    <p class="notif-title"><?= e($notif->title) ?></p>
                                    <small class="notif-desc"><?= e($notif->message) ?></small>
                                    <span class="notif-time"><?= $notif->created_at?->diffForHumans() ?: 'Recently' ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="dropdown-footer">
                    <a href="/business" style="font-size:11px;color:#176b40;font-weight:600;text-decoration:none;">View All Activity &rarr;</a>
                </div>
            </div>
        </div>

        <!-- User Profile Pill (Opens Business Profile & Password Settings) -->
        <a href="<?= route('business.profile') ?>" class="topbar-profile" title="Manage Business Profile & Password Settings">
            <span class="topbar-avatar" style="overflow:hidden;padding:0;">
                <?php if ($business?->profile_image): ?>
                    <img src="<?= e($business->profile_image_url ?: $business->profile_image) ?>" alt="<?= e($companyName) ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
                <?php else: ?>
                    <?= e($avatarInitials) ?>
                <?php endif; ?>
            </span>
            <span class="topbar-profile-info">
                <span class="topbar-name"><?= e($companyName) ?></span>
                <span class="topbar-role"><?= e(match ($business?->account_status) {
                    'approved' => 'Verified SME',
                    'rejected' => 'Changes Required',
                    default => 'Verification Pending',
                }) ?></span>
            </span>
        </a>

        <!-- Logout Button -->
        <form class="topbar-logout-form" method="POST" action="/logout">
            <?= csrf_field() ?>
            <button type="submit" class="topbar-logout-btn" title="Sign out of your workspace">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Logout</span>
            </button>
        </form>
    </div>
</header>

<script>
if (!window.toggleSidebar) {
    window.toggleSidebar = function() {
        var sidebar = document.getElementById('dashboard-sidebar');
        if (sidebar) {
            sidebar.classList.toggle('open');
        }
    };
}
function toggleNotificationMenu(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    var menu = document.getElementById('notification-dropdown');
    if (!menu) return;
    var isHidden = menu.style.display === 'none' || menu.style.display === '';
    menu.style.display = isHidden ? 'block' : 'none';
}

function markAllNotificationsAsRead(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    var dot = document.getElementById('header-notif-dot');
    if (dot) dot.style.display = 'none';

    var badge = document.getElementById('notif-count-badge');
    if (badge) {
        badge.textContent = '0 new';
        badge.style.background = '#eaf0ea';
        badge.style.color = '#617769';
        badge.style.borderColor = '#d0ded0';
    }

    var markBtn = document.getElementById('notif-mark-all-btn');
    if (markBtn) markBtn.style.display = 'none';

    var items = document.querySelectorAll('.notification-item.unread');
    items.forEach(function(item) {
        item.classList.remove('unread');
    });

    // Send async request to persist read state in database
    fetch('<?= route('business.notifications.mark-read') ?>', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '<?= csrf_token() ?>',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    }).catch(function(err) {
        console.error('Failed to update notifications in DB:', err);
    });
}

if (!window._ecocycleTopbarScriptAttached) {
    document.addEventListener('click', function(e) {
        var menu = document.getElementById('notification-dropdown');
        var btn = document.getElementById('notif-toggle-btn');
        if (menu && menu.style.display === 'block') {
            if (!menu.contains(e.target) && (!btn || !btn.contains(e.target))) {
                menu.style.display = 'none';
            }
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var menu = document.getElementById('notification-dropdown');
            if (menu) menu.style.display = 'none';
        }
    });

    window._ecocycleTopbarScriptAttached = true;
}
</script>
