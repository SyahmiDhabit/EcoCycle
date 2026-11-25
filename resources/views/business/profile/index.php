<?php
/** @var \App\Models\User $business */
$business = $business ?? \Illuminate\Support\Facades\Auth::guard('web')->user() ?: \Illuminate\Support\Facades\Auth::user();
$companyName = $business?->business_name ?: ($business?->name ?? 'EcoCycle Business');
$avatarInitials = strtoupper(substr($companyName, 0, 2));
$activeNav = 'profile'; // Note: removed from sidebar as requested, but kept for active context
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Profile &amp; Security &mdash; EcoCycle</title>
    <link rel="icon" type="image/svg+xml" href="/images/figma/ecocycle-leaf.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .profile-page-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
            gap: 24px;
            align-items: start;
        }
        @media (max-width: 992px) {
            .profile-page-grid {
                grid-template-columns: 1fr;
            }
        }
        .profile-panel-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 0;
            padding: clamp(16px, 2.4vw, 28px);
            margin-bottom: 24px;
        }
        .profile-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 16px;
            margin-bottom: 22px;
            border-bottom: 1px solid var(--card-border);
        }
        .profile-card-header h2,
        .profile-card-header h3 {
            margin: 0 0 4px;
            font-size: 17px;
            font-weight: 700;
            color: #123321;
            font-family: var(--font-display);
        }
        .profile-card-header p {
            margin: 0;
            font-size: 12.5px;
            color: var(--text-secondary);
        }

        /* Branding Hero Banner */
        .branding-hero-banner {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px 22px;
            background: var(--surface-subtle);
            border: 1px dashed #b5cab3;
            border-radius: 0;
            margin-bottom: 24px;
        }
        .branding-avatar-wrap {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 0;
            background: #edf6ec;
            border: 1px solid #176b40;
            overflow: hidden;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }
        .branding-avatar-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .branding-avatar-initials {
            font-size: 26px;
            font-weight: 800;
            color: #176b40;
            font-family: var(--font-display);
        }
        .btn-upload-logo-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 0;
            background: #ffffff;
            border: 1px solid #176b40;
            color: #176b40;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease;
        }
        .btn-upload-logo-pill:hover {
            background: #176b40;
            color: #ffffff;
        }

        /* Form Controls */
        .prof-field-group {
            margin-bottom: 16px;
        }
        .prof-field-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            font-weight: 600;
            color: #143825;
            letter-spacing: -0.01em;
        }
        .prof-field-group input[type="text"],
        .prof-field-group input[type="tel"],
        .prof-field-group input[type="email"],
        .prof-field-group input[type="password"],
        .prof-field-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--input-border);
            border-radius: 0;
            font-size: 13.5px;
            font-family: var(--font-main);
            color: #123321;
            background-color: #ffffff;
            transition: border-color 0.15s ease;
            box-sizing: border-box;
        }
        .prof-field-group input:focus,
        .prof-field-group select:focus {
            outline: none;
            border-color: #176b40;
        }
        .prof-field-group small.field-hint {
            display: block;
            margin-top: 4px;
            font-size: 11.5px;
            color: var(--text-muted);
        }
        .prof-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .prof-grid-3 {
            display: grid;
            grid-template-columns: 1.2fr 1fr 0.8fr;
            gap: 12px;
        }
        @media (max-width: 600px) {
            .prof-grid-2,
            .prof-grid-3 {
                grid-template-columns: 1fr;
            }
        }

        /* Password Input with Show/Hide Toggle */
        .password-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-input-wrap input {
            padding-right: 44px !important;
        }
        .btn-toggle-pwd {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 6px;
            display: grid;
            place-items: center;
            border-radius: 0;
        }
        .btn-toggle-pwd:hover {
            color: #143825;
            background: #f1f5f9;
        }

        /* Primary Action Buttons */
        .btn-save-profile {
            width: 100%;
            padding: 11px 20px;
            background: #143825;
            color: #ffffff;
            border: 1px solid #143825;
            border-radius: 0;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 0.15s ease;
        }
        .btn-save-profile:hover {
            background: #0d2b1b;
        }

        .btn-update-pwd {
            width: 100%;
            padding: 11px 20px;
            background: #143825;
            color: #ffffff;
            border: 1px solid #143825;
            border-radius: 0;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 0.15s ease;
        }
        .btn-update-pwd:hover {
            background: #0d2b1b;
        }

        /* Security Checklist */
        .pwd-checklist {
            background: var(--surface-subtle);
            border: 1px solid var(--card-border);
            border-radius: 0;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        .pwd-checklist-title {
            font-size: 12px;
            font-weight: 700;
            color: #143825;
            margin-bottom: 6px;
        }
        .pwd-checklist-list {
            margin: 0;
            padding-left: 18px;
            font-size: 11.5px;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        /* Accreditation Card */
        .accreditation-box {
            background: #eef6ed;
            border: 1px solid #c2dec0;
            border-left: 3px solid #176b40;
            border-radius: 0;
            padding: 18px 20px;
            margin-top: 24px;
        }
        .profile-workspace {
            padding-inline: max(clamp(20px, 4vw, 48px), calc((100% - 1180px) / 2));
        }
        .profile-workspace .page-header {
            padding: 0;
            border: 0;
        }
        .profile-workspace .page-header h1 {
            color: #14532d;
            font-size: clamp(26px, 3vw, 40px);
        }
        .profile-panel-card {
            box-shadow: 0 5px 12px rgba(20, 83, 45, .05);
        }
        .profile-card-header {
            flex-wrap: wrap;
            gap: 8px;
        }
        .profile-workspace :is(.btn-upload-logo-pill, .btn-save-profile, .btn-update-pwd, .btn-toggle-pwd) {
            border-radius: 6px !important;
        }
        .profile-workspace :is(.btn-save-profile, .btn-update-pwd) {
            background: #176b40;
            border-color: #176b40;
        }
        .profile-workspace :is(.btn-save-profile, .btn-update-pwd):hover {
            background: #105331;
        }
        .profile-workspace :is(button, input, select, label):focus-visible {
            outline: 2px solid #176b40;
            outline-offset: 2px;
        }
    </style>
</head>
<body class="business-dashboard-page">

    <!-- Reusable Top Navigation Bar -->
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <!-- Reusable Left Navigation Sidebar (Profile link cleanly removed from sidebar menu) -->
        <?php include resource_path('views/business/partials/sidebar.php'); ?>

        <main class="dashboard-main profile-workspace">
            <!-- Page Title Header -->
            <div class="page-header">
                <div>
                    <h1>
                        Business Profile &amp; Security
                    </h1>
                    <p style="margin:0;color:#556c5e;font-size:13.5px;">
                        Manage corporate branding, verified SSM registration, facility address, and secure account credentials.
                    </p>
                </div>
            </div>

            <!-- Flash Status Messages -->
            <?php if (session('status')): ?>
                <div class="dashboard-alert success" role="status" style="margin-bottom:20px;padding:14px 18px;border-radius:0;background:#eaf3e8;border:1px solid #a3cfbb;border-left:3px solid #176b40;color:#176b40;font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span><?= e(session('status')) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($errors->any()): ?>
                <div class="dashboard-alert error" role="alert" style="margin-bottom:20px;padding:14px 18px;border-radius:0;background:#fff5f4;border:1px solid #fecaca;border-left:3px solid #b91c1c;color:#b91c1c;font-size:13px;font-weight:600;">
                    <strong style="display:block;margin-bottom:6px;">Please correct the following errors:</strong>
                    <ul style="margin:0;padding-left:20px;font-size:12.5px;font-weight:500;line-height:1.5;">
                        <?php foreach ($errors->all() as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Two-Column Profile & Security Workspace -->
            <div class="profile-page-grid">
                <!-- Column 1: Corporate Profile & Verified Identity Form -->
                <div class="profile-panel-card">
                    <div class="profile-card-header">
                        <div>
                            <h2>Company Profile &amp; Branding</h2>
                            <p>Information displayed to partners across the marketplace and directory.</p>
                        </div>
                        <span style="font-size:11.5px;color:#176b40;font-weight:700;background:#eef6ec;padding:4px 10px;border-radius:0;border:1px solid #cce5c8;">
                            Active Profile
                        </span>
                    </div>

                    <form method="POST" action="/business/profile" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <?= method_field('PUT') ?>

                        <!-- Branding Logo Hero Section -->
                        <div class="branding-hero-banner">
                            <div class="branding-avatar-wrap" id="avatar-preview-box">
                                <?php if ($business->profile_image): ?>
                                    <img src="<?= e($business->profile_image_url ?: $business->profile_image) ?>" alt="<?= e($companyName) ?>" id="preview-logo">
                                <?php else: ?>
                                    <span class="branding-avatar-initials" id="preview-initials"><?= e($avatarInitials) ?></span>
                                    <img src="" alt="" id="preview-logo" style="display:none;width:100%;height:100%;object-fit:cover;">
                                <?php endif; ?>
                            </div>
                            <div style="flex:1;">
                                <strong style="display:block;font-size:15px;color:#123321;margin-bottom:2px;font-family:var(--font-display);">
                                    <?= e($companyName) ?>
                                </strong>
                                <span style="display:block;font-size:12px;color:#556c5e;margin-bottom:10px;">
                                    Username: <code style="background:#eaf0e9;padding:2px 6px;border-radius:0;color:#176b40;font-weight:600;">@<?= e($business->name) ?></code> &middot; SSM: <?= e($business->business_registration_number ?: ($business->registration_number ?? 'Verified')) ?>
                                </span>
                                <div>
                                    <label class="btn-upload-logo-pill">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                                        <span>Change Company Logo</span>
                                        <input type="file" name="profile_image" accept="image/png,image/jpeg,image/webp" style="display:none;" onchange="previewProfileLogo(this)">
                                    </label>
                                    <small style="display:block;font-size:11px;color:#6d8276;margin-top:5px;">PNG, JPG, or WEBP up to 5 MB.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Registered Company Name -->
                        <div class="prof-field-group">
                            <label for="business_name">Registered Business Name *</label>
                            <input type="text" id="business_name" name="business_name" value="<?= e(old('business_name', $business->business_name ?: $business->name)) ?>" required placeholder="e.g. KayuKita Manufacturing Sdn. Bhd.">
                        </div>

                        <div class="prof-grid-2">
                            <!-- SSM Registration Number -->
                            <div class="prof-field-group">
                                <label for="business_registration_number">SSM Registration No. *</label>
                                <input type="text" id="business_registration_number" name="business_registration_number" value="<?= e(old('business_registration_number', $business->business_registration_number ?: $business->registration_number)) ?>" required placeholder="e.g. 202101023456 (1425678-X)">
                            </div>

                            <!-- Industry Sector -->
                            <div class="prof-field-group">
                                <label for="industry">Industry Sector *</label>
                                <select id="industry" name="industry" required>
                                    <?php 
                                        $inds = [
                                            'Manufacturing & Industrial',
                                            'Furniture & Wood Products',
                                            'Plastics & Polymers Reprocessing',
                                            'Metal & Fabrication',
                                            'Textiles & Apparel',
                                            'Food & Beverage Production',
                                            'Electronics & Electrical Equipment',
                                            'Construction & Demolition Resources',
                                            'Packaging & Paper Goods',
                                            'Chemicals & Specialty Materials',
                                            'Other Surplus Commercial',
                                        ];
                                        $currInd = old('industry', $business->industry);
                                    ?>
                                    <?php foreach ($inds as $ind): ?>
                                        <option value="<?= e($ind) ?>" <?= $currInd === $ind ? 'selected' : '' ?>><?= e($ind) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Phone Number -->
                        <div class="prof-field-group">
                            <label for="business_phone">Corporate Contact Phone *</label>
                            <input type="tel" id="business_phone" name="business_phone" value="<?= e(old('business_phone', $business->business_phone ?: $business->phone)) ?>" required placeholder="e.g. +60 3-8888 1234 or +60 12-345 6789">
                        </div>

                        <!-- Corporate Address -->
                        <div class="prof-field-group">
                            <label for="registered_address">Operating Facility / Registered Address *</label>
                            <input type="text" id="registered_address" name="registered_address" value="<?= e(old('registered_address', $business->registered_address ?: $business->address)) ?>" required placeholder="Lot, Street address, or Industrial Park">
                        </div>

                        <!-- City, State, Postcode -->
                        <div class="prof-grid-3">
                            <div class="prof-field-group">
                                <label for="city">City *</label>
                                <input type="text" id="city" name="city" value="<?= e(old('city', $business->city)) ?>" required placeholder="e.g. Shah Alam">
                            </div>
                            <div class="prof-field-group">
                                <label for="state">State *</label>
                                <select id="state" name="state" required>
                                    <?php
                                        $statesList = ['Johor', 'Kedah', 'Kelantan', 'Kuala Lumpur', 'Labuan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Penang', 'Perak', 'Perlis', 'Putrajaya', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu'];
                                        $currState = old('state', $business->state ?: 'Selangor');
                                    ?>
                                    <?php foreach ($statesList as $st): ?>
                                        <option value="<?= e($st) ?>" <?= $currState === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="prof-field-group">
                                <label for="postcode">Postcode *</label>
                                <input type="text" id="postcode" name="postcode" value="<?= e(old('postcode', $business->postcode)) ?>" required placeholder="e.g. 40150">
                            </div>
                        </div>

                        <div style="margin-top:8px;">
                            <button type="submit" class="btn-save-profile">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                <span>Save Profile Updates</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Column 2: Account Security, Change Password & Accreditation -->
                <div>
                    <!-- Security Card -->
                    <div class="profile-panel-card">
                        <div class="profile-card-header">
                            <div>
                                <h3>Account Security &amp; Password</h3>
                                <p>Update your password to keep your company account secure.</p>
                            </div>
                        </div>

                        <form method="POST" action="/business/profile/password">
                            <?= csrf_field() ?>
                            <?= method_field('PUT') ?>

                            <!-- Current Password -->
                            <div class="prof-field-group">
                                <label for="cur_pass">Current Password *</label>
                                <div class="password-input-wrap">
                                    <input type="password" id="cur_pass" name="current_password" required placeholder="Enter current password">
                                    <button type="button" class="btn-toggle-pwd" onclick="togglePasswordVisibility('cur_pass', this)" title="Show/Hide Password">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- New Password -->
                            <div class="prof-field-group">
                                <label for="new_pass">New Password *</label>
                                <div class="password-input-wrap">
                                    <input type="password" id="new_pass" name="password" required placeholder="At least 8 characters">
                                    <button type="button" class="btn-toggle-pwd" onclick="togglePasswordVisibility('new_pass', this)" title="Show/Hide Password">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Confirm New Password -->
                            <div class="prof-field-group" style="margin-bottom:18px;">
                                <label for="new_pass_conf">Confirm New Password *</label>
                                <div class="password-input-wrap">
                                    <input type="password" id="new_pass_conf" name="password_confirmation" required placeholder="Re-type new password">
                                    <button type="button" class="btn-toggle-pwd" onclick="togglePasswordVisibility('new_pass_conf', this)" title="Show/Hide Password">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Requirements Checklist Box -->
                            <div class="pwd-checklist">
                                <div class="pwd-checklist-title">Password Security Requirements:</div>
                                <ul class="pwd-checklist-list">
                                    <li>Minimum of 8 characters in length.</li>
                                    <li>Must contain letters and at least one number.</li>
                                    <li>Do not reuse your previous password.</li>
                                </ul>
                            </div>

                            <button type="submit" class="btn-update-pwd">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                <span>Update Account Password</span>
                            </button>
                        </form>
                    </div>

                    <!-- B2B Accreditation & Verification Badge -->
                    <div class="accreditation-box">
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                            <div style="width:34px;height:34px;border-radius:0;background:#143825;color:#fff;display:grid;place-items:center;flex-shrink:0;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                            </div>
                            <div>
                                <strong style="font-size:14px;color:#113624;display:block;">Verified Malaysian B2B Enterprise</strong>
                                <span style="font-size:11.5px;color:#235839;">Companies Commission of Malaysia (SSM)</span>
                            </div>
                        </div>
                        <p style="margin:0 0 12px;font-size:12px;color:#355340;line-height:1.55;">
                            Your business is verified to trade on EcoCycle in Malaysia.
                        </p>
                        <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #c8ddc4;padding-top:10px;">
                            <span style="font-size:12px;font-weight:700;color:#185a36;">Status: Active / Approved</span>
                            <a href="<?= route('business.application') ?>" style="font-size:12px;color:#176b40;font-weight:700;text-decoration:underline;">
                                View SSM Documents &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Live Logo Image Preview on file select
    function previewProfileLogo(input) {
        if (input.files && input.files[0]) {
            var file = input.files[0];
            var reader = new FileReader();
            reader.onload = function(e) {
                var previewImg = document.getElementById('preview-logo');
                var previewInitials = document.getElementById('preview-initials');
                if (previewImg) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                    previewImg.style.width = '100%';
                    previewImg.style.height = '100%';
                    previewImg.style.objectFit = 'cover';
                }
                if (previewInitials) {
                    previewInitials.style.display = 'none';
                }
            };
            reader.readAsDataURL(file);
        }
    }

    // Password Show/Hide Toggle
    function togglePasswordVisibility(inputId, btn) {
        var input = document.getElementById(inputId);
        if (!input) return;

        if (input.type === 'password') {
            input.type = 'text';
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
            btn.title = 'Hide Password';
        } else {
            input.type = 'password';
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
            btn.title = 'Show Password';
        }
    }
    </script>
</body>
</html>
