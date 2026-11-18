<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/** @var string|null $activeNav */
/** @var User|null $business */
$activeNav = $activeNav ?? 'overview';
$business = $business ?? Auth::guard()->user();
$companyName = $business?->business_name ?: ($business?->name ?? 'EcoCycle Business');
$avatarInitials = strtoupper(substr($companyName, 0, 2));
?>
<!-- Left Slide-Out Sidebar Backdrop -->
<div class="sidebar-backdrop" id="sidebar-backdrop" onclick="closeSidebar()" aria-hidden="true"></div>

<!-- Collapsible Slide-Out Left Sidebar -->
<aside class="dashboard-sidebar" id="dashboard-sidebar" role="dialog" aria-modal="true" aria-label="Business Navigation Menu">
    <!-- Sidebar Header with Brand & Close Button -->
    <div class="sidebar-header">
        <div class="sidebar-header-brand">
            <span class="sidebar-brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt="EcoCycle"></span>
            <div>
                <strong class="sidebar-brand-title">EcoCycle</strong>
                <span class="sidebar-brand-sub">Business Workspace</span>
            </div>
        </div>
        <button type="button" class="sidebar-close-btn" onclick="closeSidebar()" aria-label="Close navigation menu" title="Close Menu (Esc)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <!-- User Profile Card in Sidebar -->
    <div class="sidebar-user-card-wrap">
        <a href="<?= route('business.profile') ?>" class="sidebar-user-pill <?= $activeNav === 'profile' ? 'active' : '' ?>" title="Manage Business Profile & Settings">
            <span class="sidebar-user-avatar">
                <?php if ($business?->profile_image) { ?>
                    <img src="<?= e($business->profile_image_url ?: $business->profile_image) ?>" alt="<?= e($companyName) ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
                <?php } else { ?>
                    <?= e($avatarInitials) ?>
                <?php } ?>
            </span>
            <span class="sidebar-user-info">
                <strong class="sidebar-user-name"><?= e($companyName) ?></strong>
                <span class="sidebar-user-status"><?= e(match ($business?->account_status) {
                    'approved' => 'Verified SME',
                    'rejected' => 'Changes Required',
                    default => 'Verification Pending',
                }) ?></span>
            </span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#526b5a" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>
    </div>

    <!-- Scrollable Navigation Links -->
    <div class="sidebar-scroll-body">
        <nav class="sidebar-nav" aria-label="Business Navigation Menu">
            <p class="sidebar-section-title">Workspace</p>

            <a href="/business" class="sidebar-link <?= $activeNav === 'overview' ? 'active' : '' ?>" title="Overview & Directory">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span>Overview</span>
            </a>

            <p class="sidebar-section-title" style="margin-top:12px;">Inventory</p>

            <a href="/business/inventory" class="sidebar-link <?= $activeNav === 'inventory' ? 'active' : '' ?>" title="Manage internal materials & stock">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                <span>Inventory Items</span>
            </a>

            <a href="/business/inventory?add=1" class="sidebar-link" title="Add and analyze a new material item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Add New Item</span>
            </a>

            <a href="/business/helper" class="sidebar-link <?= $activeNav === 'helper' ? 'active' : '' ?>" title="EcoCycle Helper & Recycling Centre Locator">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 2a10 10 0 1 0 10 10"></path>
                    <polygon points="12 8 8 12 12 16 16 12 12 8"></polygon>
                </svg>
                <span>EcoCycle Helper</span>
            </a>

            <a href="/business/waste-monitoring" class="sidebar-link <?= $activeNav === 'waste' ? 'active' : '' ?>" title="Monitor non-reusable materials and scrap">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M23 12a11.05 11.05 0 0 0-22 0zm-5 7a3 3 0 0 1-6 0v-7"></path>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <span>Waste Monitoring</span>
            </a>

            <p class="sidebar-section-title" style="margin-top:12px;">Marketplace</p>

            <a href="/business/marketplace" class="sidebar-link <?= $activeNav === 'marketplace' ? 'active' : '' ?>" title="Surplus Materials Marketplace">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span>Browse Marketplace</span>
            </a>

            <a href="/business/transactions?tab=purchases" class="sidebar-link <?= $activeNav === 'purchases' ? 'active' : '' ?>" title="View your purchased, claimed, and borrowed items">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <span>My Purchases</span>
            </a>

            <a href="/business/transactions?tab=sales" class="sidebar-link <?= $activeNav === 'sales' ? 'active' : '' ?>" title="View your material sales history">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <span>My Sales</span>
            </a>

            <a href="/business/my-listings" class="sidebar-link <?= $activeNav === 'listings' ? 'active' : '' ?>" title="View your published listings and status">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
                <span>My Listings</span>
            </a>

            <p class="sidebar-section-title" style="margin-top:12px;">Transactions</p>

            <a href="/business/orders" class="sidebar-link <?= $activeNav === 'orders' ? 'active' : '' ?>" title="Track order fulfillment, collection, and delivery">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="3" width="15" height="13"></rect>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                    <circle cx="5.5" cy="18.5" r="2.5"></circle>
                    <circle cx="18.5" cy="18.5" r="2.5"></circle>
                </svg>
                <span>My Orders</span>
            </a>

            <a href="/business/transactions" class="sidebar-link <?= $activeNav === 'transactions' ? 'active' : '' ?>" title="Track sales, purchases, and Stripe payment status">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="17 1 21 5 17 9"></polyline>
                    <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                    <polyline points="7 23 3 19 7 15"></polyline>
                    <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                </svg>
                <span>Payment History</span>
            </a>

            <a href="/business/invoices" class="sidebar-link <?= $activeNav === 'invoices' ? 'active' : '' ?>" title="Transaction History and PDF Receipts">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <span>Receipts</span>
            </a>

            <p class="sidebar-section-title" style="margin-top:12px;">Reviews &amp; Account</p>

            <a href="/business/reviews" class="sidebar-link <?= $activeNav === 'reviews' ? 'active' : '' ?>" title="Submit verified buyer reviews and view seller ratings">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                <span>My Reviews</span>
            </a>

            <a href="/business/reports" class="sidebar-link <?= $activeNav === 'reports' ? 'active' : '' ?>" title="Financial profit, expenditure, and wastage reports">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                <span>Reports</span>
            </a>

            <a href="<?= route('business.profile') ?>" class="sidebar-link <?= $activeNav === 'profile' ? 'active' : '' ?>" title="Business Profile & Settings">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Business Profile</span>
            </a>
        </nav>

        <div class="sidebar-footer-card">
            <?php if ($business?->account_status === 'approved') { ?>
                <span>Verified SME</span>
                <p><?= e($companyName) ?> is approved for nationwide circular exchange.</p>
                <a href="/business/marketplace">Browse marketplace &rarr;</a>
            <?php } elseif ($business?->account_status === 'rejected') { ?>
                <span style="color:#dc2626;">Changes Required</span>
                <p>Your verification requires corrections before approval.</p>
                <a href="<?= route('business.application') ?>" style="color:#b91c1c;">Update application &rarr;</a>
            <?php } else { ?>
                <span style="color:#d97706;">Verification Pending</span>
                <p>Your registration is in review by administrator.</p>
                <a href="<?= route('business.application') ?>" style="color:#b45309;">Check status &rarr;</a>
            <?php } ?>
        </div>
    </div>

    <!-- Sidebar Bottom Logout Form -->
    <div class="sidebar-bottom-action">
        <form method="POST" action="/logout" style="margin:0;">
            <?= csrf_field() ?>
            <button type="submit" class="sidebar-logout-btn" title="Sign out of your workspace">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Sign Out (Logout)</span>
            </button>
        </form>
    </div>
</aside>

<script>
function toggleSidebar() {
    var sidebar = document.getElementById('dashboard-sidebar');
    if (!sidebar) return;
    if (sidebar.classList.contains('open') || sidebar.classList.contains('active')) {
        closeSidebar();
    } else {
        openSidebar();
    }
}
window.toggleSidebar = toggleSidebar;

function openSidebar() {
    var sidebar = document.getElementById('dashboard-sidebar');
    var backdrop = document.getElementById('sidebar-backdrop');
    var btn = document.getElementById('sidebar-toggle-btn');
    if (!sidebar) return;

    sidebar.classList.add('open', 'active');
    if (backdrop) backdrop.classList.add('active');
    if (btn) {
        btn.setAttribute('aria-expanded', 'true');
        btn.classList.add('active');
    }
    document.body.style.overflow = 'hidden';
}
window.openSidebar = openSidebar;

function closeSidebar() {
    var sidebar = document.getElementById('dashboard-sidebar');
    var backdrop = document.getElementById('sidebar-backdrop');
    var btn = document.getElementById('sidebar-toggle-btn');
    if (!sidebar) return;

    sidebar.classList.remove('open', 'active');
    if (backdrop) backdrop.classList.remove('active');
    if (btn) {
        btn.setAttribute('aria-expanded', 'false');
        btn.classList.remove('active');
    }
    document.body.style.overflow = '';
}
window.closeSidebar = closeSidebar;

if (!window._ecocycleSidebarListenerAttached) {
    document.addEventListener('click', function(e) {
        var sidebar = document.getElementById('dashboard-sidebar');
        var btn = document.getElementById('sidebar-toggle-btn');
        if (sidebar && (sidebar.classList.contains('open') || sidebar.classList.contains('active'))) {
            if (!sidebar.contains(e.target) && (!btn || !btn.contains(e.target))) {
                closeSidebar();
            }
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });

    window._ecocycleSidebarListenerAttached = true;
}
</script>

