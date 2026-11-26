<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/** @var string|null $activeNav */
/** @var User|null $business */
$activeNav = $activeNav ?? 'overview';
$business = $business ?? Auth::guard()->user();
$companyName = $business?->business_name ?: ($business?->name ?? 'EcoCycle Business');

$workspaceActive = $activeNav === 'overview';
$inventoryActive = in_array($activeNav, ['inventory', 'helper', 'waste'], true);
$marketplaceActive = in_array($activeNav, ['marketplace', 'purchases', 'sales', 'listings'], true);
$transactionsActive = in_array($activeNav, ['transactions', 'invoices', 'orders'], true);
$reviewsActive = $activeNav === 'reviews';
$accountActive = in_array($activeNav, ['reports', 'profile'], true);
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

    <!-- Scrollable Layer-by-Layer Collapsible Navigation -->
    <div class="sidebar-scroll-body">
        <nav class="sidebar-nav" aria-label="Business Navigation Menu">
            <!-- 1. Workspace -->
            <div class="sidebar-group" data-sidebar-group="workspace">
                <button type="button" class="sidebar-group-toggle <?= $workspaceActive ? 'has-active' : '' ?>" onclick="toggleSidebarSubmenu('workspace', this)" aria-expanded="false" aria-controls="submenu-workspace">
                    <span class="sidebar-group-label">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        <span>Workspace</span>
                    </span>
                    <svg class="sidebar-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
                <div class="sidebar-submenu" id="submenu-workspace">
                    <a href="/business" class="sidebar-sublink <?= $activeNav === 'overview' ? 'active' : '' ?>" title="Workspace Dashboard">
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>

            <!-- 2. Inventory -->
            <div class="sidebar-group" data-sidebar-group="inventory">
                <button type="button" class="sidebar-group-toggle <?= $inventoryActive ? 'has-active' : '' ?>" onclick="toggleSidebarSubmenu('inventory', this)" aria-expanded="false" aria-controls="submenu-inventory">
                    <span class="sidebar-group-label">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                        <span>Inventory</span>
                    </span>
                    <svg class="sidebar-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
                <div class="sidebar-submenu" id="submenu-inventory">
                    <a href="/business/inventory" class="sidebar-sublink <?= $activeNav === 'inventory' ? 'active' : '' ?>" title="Manage internal materials and stock">
                        <span>Inventory Items</span>
                    </a>
                    <a href="/business/inventory?add=1" class="sidebar-sublink" title="Add and analyze a new item">
                        <span>Add New Item</span>
                    </a>
                    <a href="/business/helper" class="sidebar-sublink <?= $activeNav === 'helper' ? 'active' : '' ?>" title="EcoCycle Helper & Recycling Centre Locator">
                        <span>EcoCycle Helper</span>
                    </a>
                    <a href="<?= e(route('business.waste.index')) ?>" class="sidebar-sublink <?= $activeNav === 'waste' ? 'active' : '' ?>">
                        <span>Waste Monitoring</span>
                    </a>
                </div>
            </div>

            <!-- 3. Marketplace -->
            <div class="sidebar-group" data-sidebar-group="marketplace">
                <button type="button" class="sidebar-group-toggle <?= $marketplaceActive ? 'has-active' : '' ?>" onclick="toggleSidebarSubmenu('marketplace', this)" aria-expanded="false" aria-controls="submenu-marketplace">
                    <span class="sidebar-group-label">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <span>Marketplace</span>
                    </span>
                    <svg class="sidebar-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
                <div class="sidebar-submenu" id="submenu-marketplace">
                    <a href="/business/marketplace" class="sidebar-sublink <?= $activeNav === 'marketplace' ? 'active' : '' ?>" title="Surplus Materials Marketplace">
                        <span>Browse Marketplace</span>
                    </a>
                    <a href="/business/transactions?tab=purchases" class="sidebar-sublink <?= $activeNav === 'purchases' ? 'active' : '' ?>" title="View your purchased, claimed, and borrowed items">
                        <span>My Purchases</span>
                    </a>
                    <a href="/business/transactions?tab=sales" class="sidebar-sublink <?= $activeNav === 'sales' ? 'active' : '' ?>" title="View your material sales history">
                        <span>My Sales</span>
                    </a>
                    <a href="/business/my-listings" class="sidebar-sublink <?= $activeNav === 'listings' ? 'active' : '' ?>" title="View your published listings">
                        <span>My Listings</span>
                    </a>
                </div>
            </div>

            <!-- 4. Transactions -->
            <div class="sidebar-group" data-sidebar-group="transactions">
                <button type="button" class="sidebar-group-toggle <?= $transactionsActive ? 'has-active' : '' ?>" onclick="toggleSidebarSubmenu('transactions', this)" aria-expanded="false" aria-controls="submenu-transactions">
                    <span class="sidebar-group-label">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="17 1 21 5 17 9"></polyline>
                            <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                            <polyline points="7 23 3 19 7 15"></polyline>
                            <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                        </svg>
                        <span>Transactions</span>
                    </span>
                    <svg class="sidebar-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
                <div class="sidebar-submenu" id="submenu-transactions">
                    <a href="/business/transactions" class="sidebar-sublink <?= $activeNav === 'transactions' ? 'active' : '' ?>" title="Track payment history and status">
                        <span>Payment History</span>
                    </a>
                    <a href="/business/invoices" class="sidebar-sublink <?= $activeNav === 'invoices' ? 'active' : '' ?>" title="View and download transaction receipts">
                        <span>Receipts</span>
                    </a>
                    <a href="/business/orders" class="sidebar-sublink <?= $activeNav === 'orders' ? 'active' : '' ?>" title="Track order fulfillment, collection, and delivery">
                        <span>My Orders</span>
                    </a>
                </div>
            </div>

            <!-- 5. Reviews -->
            <div class="sidebar-group" data-sidebar-group="reviews">
                <button type="button" class="sidebar-group-toggle <?= $reviewsActive ? 'has-active' : '' ?>" onclick="toggleSidebarSubmenu('reviews', this)" aria-expanded="false" aria-controls="submenu-reviews">
                    <span class="sidebar-group-label">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                        </svg>
                        <span>Reviews</span>
                    </span>
                    <svg class="sidebar-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
                <div class="sidebar-submenu" id="submenu-reviews">
                    <a href="/business/reviews" class="sidebar-sublink <?= $activeNav === 'reviews' ? 'active' : '' ?>" title="Submit verified buyer reviews and view seller ratings">
                        <span>My Reviews</span>
                    </a>
                </div>
            </div>

            <!-- 6. Reports & Account -->
            <div class="sidebar-group" data-sidebar-group="account">
                <button type="button" class="sidebar-group-toggle <?= $accountActive ? 'has-active' : '' ?>" onclick="toggleSidebarSubmenu('account', this)" aria-expanded="false" aria-controls="submenu-account">
                    <span class="sidebar-group-label">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Reports &amp; Profile</span>
                    </span>
                    <svg class="sidebar-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
                <div class="sidebar-submenu" id="submenu-account">
                    <a href="/business/reports" class="sidebar-sublink <?= $activeNav === 'reports' ? 'active' : '' ?>" title="Financial profit, expenditure, and wastage reports">
                        <span>Reports</span>
                    </a>
                    <a href="<?= route('business.profile') ?>" class="sidebar-sublink <?= $activeNav === 'profile' ? 'active' : '' ?>" title="Business Profile & Settings">
                        <span>Business Profile</span>
                    </a>
                </div>
            </div>
        </nav>
    </div>

    <!-- Sidebar Bottom Logout Form -->
    <div class="sidebar-bottom-action">
        <form method="POST" action="/logout" style="margin:0;">
            <?= csrf_field() ?>
            <button type="submit" class="sidebar-logout-btn" title="Sign out of your workspace">
                <span>Sign Out</span>
            </button>
        </form>
    </div>
</aside>

<script>
function toggleSidebarSubmenu(groupKey, triggerBtn) {
    var submenu = document.getElementById('submenu-' + groupKey);
    if (!submenu) return;

    var isCurrentlyOpen = submenu.classList.contains('open');

    // Collapse all submenus first for a clean layer-by-layer experience
    var allSubmenus = document.querySelectorAll('#dashboard-sidebar .sidebar-submenu');
    var allButtons = document.querySelectorAll('#dashboard-sidebar .sidebar-group-toggle');
    allSubmenus.forEach(function(menu) {
        menu.classList.remove('open');
    });
    allButtons.forEach(function(btn) {
        btn.classList.remove('expanded');
        btn.setAttribute('aria-expanded', 'false');
    });

    // Toggle clicked submenu (re-clicking collapses it)
    if (!isCurrentlyOpen) {
        submenu.classList.add('open');
        if (triggerBtn) {
            triggerBtn.classList.add('expanded');
            triggerBtn.setAttribute('aria-expanded', 'true');
        }
    }
}
window.toggleSidebarSubmenu = toggleSidebarSubmenu;

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
