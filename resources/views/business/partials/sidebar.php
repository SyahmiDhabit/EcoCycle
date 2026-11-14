<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/** @var string|null $activeNav */
/** @var User|null $business */
$activeNav = $activeNav ?? 'overview';
$business = $business ?? Auth::guard()->user();
$companyName = $business?->business_name ?: ($business?->name ?? 'EcoCycle Business');
?>
<aside class="dashboard-sidebar" id="dashboard-sidebar">
    <nav class="sidebar-nav" aria-label="Business Navigation Menu">
        <p class="sidebar-section-title">Navigation Menu</p>

        <a href="/business" class="sidebar-link <?= $activeNav === 'overview' ? 'active' : '' ?>" title="Overview & Directory">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            <span>Overview</span>
        </a>

        <a href="/business/inventory" class="sidebar-link <?= $activeNav === 'inventory' ? 'active' : '' ?>" title="Manage internal materials & stock">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
            <span>Inventory</span>
        </a>

        <a href="/business/waste-monitoring" class="sidebar-link <?= $activeNav === 'waste' ? 'active' : '' ?>" title="Monitor non-reusable materials and scrap">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M23 12a11.05 11.05 0 0 0-22 0zm-5 7a3 3 0 0 1-6 0v-7"></path>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            <span>Waste monitoring</span>
        </a>

        <a href="/business/marketplace" class="sidebar-link <?= $activeNav === 'marketplace' ? 'active' : '' ?>" title="Surplus Materials Marketplace">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            <span>Marketplace</span>
        </a>

        <a href="/business/helper" class="sidebar-link <?= $activeNav === 'helper' ? 'active' : '' ?>" title="AI Circular Scrap Advisor & GPS Recycling Centre Locator">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M12 2a10 10 0 1 0 10 10"></path>
                <polygon points="12 8 8 12 12 16 16 12 12 8"></polygon>
            </svg>
            <span>EcoCycle Helper (AI)</span>
        </a>

        <a href="/business/my-listings" class="sidebar-link <?= $activeNav === 'listings' ? 'active' : '' ?>" title="View your listings, item status, and buyer feedback">
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

        <a href="/business/transactions" class="sidebar-link <?= $activeNav === 'transactions' ? 'active' : '' ?>" title="Track sales, purchases, and payment requests">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="17 1 21 5 17 9"></polyline>
                <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                <polyline points="7 23 3 19 7 15"></polyline>
                <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
            </svg>
            <span>Transactions</span>
        </a>

        <a href="/business/reports" class="sidebar-link <?= $activeNav === 'reports' ? 'active' : '' ?>" title="Financial profit, expenditure, and wastage reports">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            <span>Reports</span>
        </a>
    </nav>

    <!-- Navbar Profile Card (Matches Topbar Profile - Click to view & update) -->
    <div style="padding: 10px 14px 4px;">
        <a href="<?= route('business.profile') ?>" class="sidebar-user-pill <?= $activeNav === 'profile' ? 'active' : '' ?>" style="display:flex;align-items:center;gap:10px;padding:9px 12px;background:#f4f8f3;border:1.5px solid #cadbc9;border-radius:10px;text-decoration:none;transition:all 0.15s ease;" title="Click to view & update Business Profile">
            <span style="width:34px;height:34px;border-radius:50%;overflow:hidden;background:#176b40;color:#ffffff;display:grid;place-items:center;font-weight:700;font-size:12px;flex-shrink:0;border:1.5px solid #235839;">
                <?php if ($business?->profile_image) { ?>
                    <img src="<?= e($business->profile_image_url ?: $business->profile_image) ?>" alt="<?= e($companyName) ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
                <?php } else { ?>
                    <?= e(strtoupper(substr($companyName, 0, 2))) ?>
                <?php } ?>
            </span>
            <span style="display:flex;flex-direction:column;min-width:0;flex:1;line-height:1.25;">
                <strong style="font-size:12px;color:#123321;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:700;"><?= e($companyName) ?></strong>
                <span style="font-size:10.5px;color:#15803d;font-weight:700;"><?= e(match ($business?->account_status) {
                    'approved' => '✓ Verified SME',
                    'rejected' => 'Changes Required',
                    default => 'Verification Pending',
                }) ?></span>
            </span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#526b5a" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </a>
    </div>

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
</aside>

<script>
if (!window.handleNavNotice) {
    window.handleNavNotice = function(e, name) {
        if (e) e.preventDefault();
        alert(name + ' feature is coming soon in the next release.');
    };
}
</script>
