<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/** @var User|null $business */
/** @var int $trackedItemsCount */
/** @var int $materialsListedCount */
/** @var float $wasteDivertedKg */
/** @var float $exchangeImpactRm */
/** @var int $approvedCount */
/** @var array<int, array<string, mixed>> $businesses */
/** @var array<int, string> $availableIndustries */
/** @var array<int, string> $availableLocations */
/** @var array<int, string> $availableMaterials */
$business = $business ?? Auth::guard()->user();
$companyName = $business?->business_name ?: ($business?->name ?? 'EcoCycle Business');
$companyLocation = trim(($business?->city ?: '').($business?->city && $business?->state ? ', ' : '').($business?->state ?: 'Malaysia'));
$activeNav = 'overview';
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Overview &middot; Business Directory &mdash; EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
</head>
<body class="business-dashboard-page">

    <!-- Reusable Professional Top Navigation Bar -->
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <!-- Reusable Left Navigation Sidebar -->
        <?php include resource_path('views/business/partials/sidebar.php'); ?>

        <!-- MAIN DASHBOARD CONTENT -->
        <main class="dashboard-main">
            <!-- Page Title -->
            <div class="directory-header">
                <div>
                    <h1>Business directory</h1>
                    <p>Discover approved Malaysian SMEs from the database, explore their materials and exchange with confidence.</p>
                </div>
            </div>

            <!-- Search & Filter Card (Referencing Database Attributes strictly) -->
            <section class="filter-card" aria-label="Filter businesses">
                <form id="filter-form" onsubmit="event.preventDefault(); applyFilters();">
                    <div class="filter-grid">
                        <div class="filter-field">
                            <label for="filter-search">Search approved businesses</label>
                            <input id="filter-search" type="text" placeholder="Search company name, username, or location..." oninput="applyFilters()">
                        </div>
                        <div class="filter-field">
                            <label for="filter-industry">Industry</label>
                            <select id="filter-industry" onchange="applyFilters()">
                                <option value="">All industries</option>
                                <?php foreach ($availableIndustries as $ind) { ?>
                                    <option value="<?= e($ind) ?>"><?= e($ind) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label for="filter-location">Location</label>
                            <select id="filter-location" onchange="applyFilters()">
                                <option value="">All locations</option>
                                <?php foreach ($availableLocations as $loc) { ?>
                                    <option value="<?= e($loc) ?>"><?= e($loc) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label for="filter-material">Material</label>
                            <select id="filter-material" onchange="applyFilters()">
                                <option value="">All materials</option>
                                <?php foreach ($availableMaterials as $mat) { ?>
                                    <option value="<?= e($mat) ?>"><?= e($mat) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-footer">
                        <div class="filter-tags">
                            <span class="tag-pill" id="location-tag" style="display:none;" onclick="clearLocationFilter()" title="Click to clear location filter">
                                <span id="location-tag-text">All locations</span>
                                <span aria-hidden="true">&times;</span>
                            </span>
                            <span class="tag-helper-text">Displaying verified businesses directly from the database.</span>
                        </div>
                        <div class="filter-actions">
                            <button type="button" class="btn-reset" onclick="resetFilters()">Reset filters</button>
                            <button type="submit" class="btn-search">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <span>Search businesses</span>
                            </button>
                        </div>
                    </div>
                </form>
            </section>

            <!-- SECTION 1: Verified Businesses in Database -->
            <section aria-labelledby="featured-heading">
                <div class="section-header-row">
                    <div class="section-header-title">
                        <h2 id="featured-heading">Verified Businesses</h2>
                        <p id="featured-count-text"><?= count($businesses) ?> <?= count($businesses) === 1 ? 'business' : 'businesses' ?> on record</p>
                    </div>
                    <?php if (! empty($businesses)) { ?>
                        <div class="sort-select-wrap">
                            <select id="featured-sort" onchange="sortCards(this.value)" aria-label="Sort businesses">
                                <option value="featured">Sort: Default &darr;</option>
                                <option value="name">Sort: Company Name (A-Z)</option>
                                <option value="listings">Sort: Most listings</option>
                            </select>
                        </div>
                    <?php } ?>
                </div>

                <?php if (empty($businesses)) { ?>
                    <div style="background:#ffffff;border:1px solid var(--card-border);border-radius:12px;padding:48px 24px;text-align:center;margin-bottom:32px;">
                        <span class="signin-brand-mark" style="margin:0 auto 14px;"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                        <h3 style="margin:0 0 6px;font-size:16px;color:#123321;">No other approved businesses found yet</h3>
                        <p style="margin:0;font-size:13px;color:var(--text-muted);max-width:480px;margin-inline:auto;line-height:1.5;">
                            When other businesses register and are approved by the administrator, they will appear here automatically.
                        </p>
                    </div>
                <?php } else { ?>
                    <div class="featured-grid" id="featured-grid">
                        <?php foreach ($businesses as $biz) { ?>
                            <article class="business-card"
                                     data-name="<?= e(strtolower($biz['business_name'])) ?>"
                                     data-industry="<?= e(strtolower($biz['industry'])) ?>"
                                     data-location="<?= e(strtolower($biz['location'])) ?>"
                                     data-materials="<?= e(strtolower($biz['materials'])) ?>"
                                     data-listings="<?= (int) $biz['listings_count'] ?>">
                                <div class="card-image-wrap">
                                    <?php if (! empty($biz['image'])) { ?>
                                        <img src="<?= e($biz['image']) ?>" alt="<?= e($biz['business_name']) ?>" loading="lazy">
                                    <?php } else { ?>
                                        <div style="width:100%;height:100%;display:grid;place-items:center;background:#f0f6ee;">
                                            <span class="signin-brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="card-body">
                                    <div class="card-badges">
                                        <span class="verified-pill">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                            <span>Verified SME</span>
                                        </span>
                                        <span class="category-tag"><?= e($biz['industry']) ?></span>
                                    </div>
                                    <h3 class="card-title"><?= e($biz['business_name']) ?></h3>
                                    <div class="card-location"><?= e($biz['location']) ?></div>
                                    <p class="card-description"><?= e($biz['description']) ?></p>
                                    <div class="card-materials"><?= e($biz['materials']) ?></div>
                                    <div class="card-footer">
                                        <span class="available-listings-text"><?= $biz['listings_count'] ?> active listing(s)</span>
                                        <button type="button"
                                                class="btn-view-profile"
                                                onclick='openProfileModal(<?= json_encode($biz, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                            View profile &rarr;
                                        </button>
                                    </div>
                                </div>
                            </article>
                        <?php } ?>
                    </div>
                <?php } ?>
            </section>

            <!-- SECTION 2: Businesses Directory Table (Pure Database) -->
            <?php if (! empty($businesses)) { ?>
                <section class="table-section-card" aria-labelledby="table-heading">
                    <div class="table-header-title">
                        <h2 id="table-heading">Company directory</h2>
                        <p>All verified businesses and materials currently active in the database.</p>
                    </div>

                    <div style="overflow-x:auto;">
                        <table class="directory-table" id="directory-table">
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Industry / Location</th>
                                    <th>Materials</th>
                                    <th style="text-align:right;">Profile</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($businesses as $biz) { ?>
                                    <tr class="table-biz-row"
                                        data-name="<?= e(strtolower($biz['business_name'])) ?>"
                                        data-industry="<?= e(strtolower($biz['industry'])) ?>"
                                        data-location="<?= e(strtolower($biz['location'])) ?>"
                                        data-materials="<?= e(strtolower($biz['materials'])) ?>">
                                        <td class="company-cell">
                                            <strong><?= e($biz['business_name']) ?></strong>
                                            <small>
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                                Verified business
                                            </small>
                                        </td>
                                        <td><?= e($biz['industry']) ?> &middot; <?= e($biz['location']) ?></td>
                                        <td class="materials-cell">
                                            <span><?= e($biz['materials']) ?></span>
                                            <small><?= $biz['listings_count'] ?> active listing(s)</small>
                                        </td>
                                        <td style="text-align:right;">
                                            <button type="button"
                                                    class="btn-view-profile"
                                                    onclick='openProfileModal(<?= json_encode($biz, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                View profile &rarr;
                                            </button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-pagination-row">
                        <span id="pagination-text">Showing <?= count($businesses) ?> <?= count($businesses) === 1 ? 'business' : 'businesses' ?></span>
                        <div class="pagination-controls">
                            <button type="button" class="pagination-btn" disabled>Previous</button>
                            <button type="button" class="pagination-btn active">1</button>
                            <button type="button" class="pagination-btn" disabled>Next</button>
                        </div>
                    </div>
                </section>
            <?php } ?>
        </main>
    </div>

    <!-- BUSINESS PROFILE DETAILS MODAL -->
    <div class="modal-backdrop" id="profile-modal" style="display:none;" onclick="if(event.target === this) closeProfileModal()">
        <div class="modal-dialog">
            <div class="modal-header">
                <div style="display:flex;align-items:center;gap:14px;">
                    <div id="modal-biz-avatar" style="width:46px;height:46px;border-radius:50%;overflow:hidden;background:#eef6ec;border:2px solid #285c44;display:grid;place-items:center;flex-shrink:0;">
                        <span style="font-size:15px;font-weight:700;color:#185a36;">EC</span>
                    </div>
                    <div>
                        <h3 id="modal-biz-name" style="margin:0;">Company Name</h3>
                        <span class="verified-pill" style="margin-top:4px;">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span>Verified EcoCycle SME</span>
                        </span>
                    </div>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeProfileModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="modal-biz-desc" style="color:#33503f;margin:0;"></p>

                <div class="modal-detail-grid">
                    <div class="modal-detail-item">
                        <small>Industry</small>
                        <span id="modal-biz-industry">-</span>
                    </div>
                    <div class="modal-detail-item">
                        <small>Location</small>
                        <span id="modal-biz-location">-</span>
                    </div>
                    <div class="modal-detail-item">
                        <small>SSM Registration No.</small>
                        <span id="modal-biz-reg">-</span>
                    </div>
                    <div class="modal-detail-item">
                        <small>Available Listings</small>
                        <span id="modal-biz-listings">-</span>
                    </div>
                    <div class="modal-detail-item">
                        <small>Business Phone</small>
                        <span id="modal-biz-phone">-</span>
                    </div>
                    <div class="modal-detail-item">
                        <small>Email</small>
                        <span id="modal-biz-email">-</span>
                    </div>
                </div>

                <div style="background:#edf6ec;border:1px solid #cce5c8;border-radius:8px;padding:12px 14px;">
                    <strong style="display:block;font-size:12px;color:#185e35;margin-bottom:4px;">Surplus &amp; Exchange Materials</strong>
                    <span id="modal-biz-materials" style="font-size:12px;color:#284f37;">-</span>
                </div>

                <div style="margin-top:4px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <strong style="font-size:12px;color:#123321;">Active Surplus Listings (<span id="modal-listings-count-badge">0</span>)</strong>
                    </div>
                    <div id="modal-listings-container" style="display:flex;flex-direction:column;gap:8px;max-height:220px;overflow-y:auto;">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-reset" onclick="closeProfileModal()">Close</button>
                <a id="modal-contact-link" href="#" class="btn-reset" style="border:1px solid #cadbc9;border-radius:6px;text-decoration:none;padding:8px 14px;">
                    <span>Contact Email</span>
                </a>
                <a id="modal-full-page-link" href="#" class="btn-search" style="text-decoration:none;">
                    <span>View Full Profile &rarr;</span>
                </a>
            </div>
        </div>
    </div>

    <script>
    function toggleSidebar() {
        var sidebar = document.getElementById('dashboard-sidebar');
        if (sidebar) sidebar.classList.toggle('open');
    }

    function handleNavNotice(e, sectionName) {
        e.preventDefault();
        alert('The ' + sectionName + ' module is active for your business workspace.');
    }

    function openProfileModal(biz) {
        var avatarBox = document.getElementById('modal-biz-avatar');
        if (avatarBox) {
            if (biz.image) {
                avatarBox.innerHTML = '<img src="' + biz.image + '" alt="' + (biz.business_name || '') + '" style="width:100%;height:100%;object-fit:cover;">';
            } else {
                var initials = (biz.business_name || biz.name || 'EC').substring(0, 2).toUpperCase();
                avatarBox.innerHTML = '<span style="font-size:15px;font-weight:700;color:#185a36;">' + initials + '</span>';
            }
        }

        document.getElementById('modal-biz-name').textContent = biz.business_name || 'Business Profile';
        document.getElementById('modal-biz-desc').textContent = biz.description || 'Verified Malaysian SME in the circular exchange network.';
        document.getElementById('modal-biz-industry').textContent = biz.industry || '-';
        document.getElementById('modal-biz-location').textContent = biz.location || '-';
        document.getElementById('modal-biz-reg').textContent = biz.registration_no || 'Verified on SSM';
        document.getElementById('modal-biz-listings').textContent = (biz.listings_count || 0) + ' active listing(s)';
        document.getElementById('modal-biz-phone').textContent = biz.phone || 'Not provided';
        document.getElementById('modal-biz-email').textContent = biz.email || '-';
        document.getElementById('modal-biz-materials').textContent = biz.materials || 'Circular materials on request.';

        var contactLink = document.getElementById('modal-contact-link');
        contactLink.href = 'mailto:' + encodeURIComponent(biz.email || '') + '?subject=' + encodeURIComponent('EcoCycle Material Exchange Inquiry: ' + (biz.business_name || ''));

        var fullPageLink = document.getElementById('modal-full-page-link');
        if (fullPageLink) {
            fullPageLink.href = '/business/directory/' + (biz.id || '');
        }

        var listingsContainer = document.getElementById('modal-listings-container');
        var countBadge = document.getElementById('modal-listings-count-badge');
        listingsContainer.innerHTML = '';

        if (biz.listings && biz.listings.length > 0) {
            countBadge.textContent = biz.listings.length;
            biz.listings.forEach(function(item) {
                var card = document.createElement('div');
                card.style.cssText = 'border:1px solid #e1e9df;border-radius:8px;padding:10px 12px;background:#fbfdfa;display:flex;justify-content:space-between;align-items:center;gap:12px;';
                card.innerHTML = '<div>' +
                    '<strong style="display:block;font-size:12px;color:#143324;">' + (item.title || 'Surplus Item') + '</strong>' +
                    '<span style="font-size:11px;color:#526658;">' + (item.quantity || '') + ' ' + (item.unit || '') + ' &middot; ' + (item.category || '') + ' &middot; ' + (item.condition || '') + '</span>' +
                    '</div>' +
                    '<div style="text-align:right;flex-shrink:0;">' +
                    (item.is_free ? '<span style="font-size:11px;font-weight:700;color:#15803d;">Free Surplus</span>' : '<span style="font-size:11px;font-weight:700;color:#176b40;">RM ' + (item.price || '0.00') + '</span>') +
                    '<br><a href="/business/marketplace?q=' + encodeURIComponent(item.title || '') + '" style="font-size:10.5px;color:#176b40;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:3px;margin-top:2px;">Order on Marketplace &rarr;</a>' +
                    '</div>';
                listingsContainer.appendChild(card);
            });
        } else {
            countBadge.textContent = '0';
            listingsContainer.innerHTML = '<div style="padding:14px;background:#f7faf5;border-radius:6px;border:1px dashed #cadbc9;text-align:center;font-size:11px;color:#758a7b;">This business has no active marketplace listings published yet.</div>';
        }

        document.getElementById('profile-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeProfileModal() {
        document.getElementById('profile-modal').style.display = 'none';
        document.body.style.overflow = '';
    }

    function clearLocationFilter() {
        var locSelect = document.getElementById('filter-location');
        locSelect.value = '';
        var locTag = document.getElementById('location-tag');
        if (locTag) locTag.style.display = 'none';
        applyFilters();
    }

    function resetFilters() {
        document.getElementById('filter-search').value = '';
        document.getElementById('filter-industry').value = '';
        document.getElementById('filter-location').value = '';
        document.getElementById('filter-material').value = '';
        var locTag = document.getElementById('location-tag');
        if (locTag) locTag.style.display = 'none';
        applyFilters();
    }

    function applyFilters() {
        var query = (document.getElementById('filter-search').value || '').toLowerCase().trim();
        var industry = (document.getElementById('filter-industry').value || '').toLowerCase().trim();
        var location = (document.getElementById('filter-location').value || '').toLowerCase().trim();
        var material = (document.getElementById('filter-material').value || '').toLowerCase().trim();

        var locTag = document.getElementById('location-tag');
        var locTagText = document.getElementById('location-tag-text');
        var heading = document.getElementById('featured-heading');

        if (location) {
            locTag.style.display = 'inline-flex';
            locTagText.textContent = location.charAt(0).toUpperCase() + location.slice(1);
            if (heading) heading.textContent = 'Businesses in ' + location.charAt(0).toUpperCase() + location.slice(1);
        } else {
            locTag.style.display = 'none';
            if (heading) heading.textContent = 'Verified Businesses';
        }

        // Filter cards
        var cards = document.querySelectorAll('.business-card');
        var visibleCards = 0;
        cards.forEach(function(card) {
            var name = card.dataset.name || '';
            var ind = card.dataset.industry || '';
            var loc = card.dataset.location || '';
            var mat = card.dataset.materials || '';

            var matchesQuery = !query || name.includes(query) || ind.includes(query) || loc.includes(query) || mat.includes(query);
            var matchesInd = !industry || ind.includes(industry);
            var matchesLoc = !location || loc.includes(location);
            var matchesMat = !material || mat.includes(material);

            if (matchesQuery && matchesInd && matchesLoc && matchesMat) {
                card.style.display = '';
                visibleCards++;
            } else {
                card.style.display = 'none';
            }
        });

        // Filter table rows
        var rows = document.querySelectorAll('.table-biz-row');
        var visibleRows = 0;
        rows.forEach(function(row) {
            var name = row.dataset.name || '';
            var ind = row.dataset.industry || '';
            var loc = row.dataset.location || '';
            var mat = row.dataset.materials || '';

            var matchesQuery = !query || name.includes(query) || ind.includes(query) || loc.includes(query) || mat.includes(query);
            var matchesInd = !industry || ind.includes(industry);
            var matchesLoc = !location || loc.includes(location);
            var matchesMat = !material || mat.includes(material);

            if (matchesQuery && matchesInd && matchesLoc && matchesMat) {
                row.style.display = '';
                visibleRows++;
            } else {
                row.style.display = 'none';
            }
        });

        var countText = document.getElementById('featured-count-text');
        if (countText) {
            countText.textContent = visibleCards + ' ' + (visibleCards === 1 ? 'business' : 'businesses') + ' found';
        }
    }

    function sortCards(type) {
        var grid = document.getElementById('featured-grid');
        if (!grid) return;
        var cards = Array.from(grid.querySelectorAll('.business-card'));

        cards.sort(function(a, b) {
            if (type === 'name') {
                return (a.dataset.name || '').localeCompare(b.dataset.name || '');
            } else if (type === 'listings') {
                return parseInt(b.dataset.listings || 0, 10) - parseInt(a.dataset.listings || 0, 10);
            }
            return 0;
        });

        cards.forEach(function(c) { grid.appendChild(c); });
    }
    </script>
</body>
</html>
