<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Profile &amp; Security &middot; EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/dashboard.css?v=<?= filemtime(public_path('css/business/dashboard.css')) ?>">
    <style>
        .profile-container-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        @media (max-width: 900px) {
            .profile-container-grid {
                grid-template-columns: 1fr;
            }
        }
        .profile-card-box {
            background: #ffffff;
            border: 1px solid #d8e5d6;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        .profile-header-banner {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 20px;
            border-bottom: 1.5px solid #eef4ed;
            margin-bottom: 20px;
        }
        .profile-logo-wrap {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            border: 2px solid #2e7d32;
            overflow: hidden;
            background: #edf6ec;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            position: relative;
        }
        .profile-logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body class="business-dashboard-page">
    <?php include resource_path('views/business/partials/topbar.php'); ?>

    <div class="dashboard-layout">
        <?php 
            $activeNav = 'profile';
            include resource_path('views/business/partials/sidebar.php'); 
        ?>

        <main class="dashboard-main-content">
            <header class="section-header" style="margin-bottom:20px;">
                <div class="section-header-title">
                    <h2>Business Profile &amp; Account Settings</h2>
                    <p>Manage your company branding, verified registration details, corporate address, and account credentials.</p>
                </div>
            </header>

            <?php if (session('status')): ?>
                <div style="background:#edf7ed;border:1.5px solid #a3cfbb;color:#1e4620;padding:12px 18px;border-radius:8px;font-size:13px;margin-bottom:20px;font-weight:600;">
                    ✓ <?= session('status') ?>
                </div>
            <?php endif; ?>

            <?php if ($errors->any()): ?>
                <div style="background:#fef2f2;border:1.5px solid #fecaca;color:#991b1b;padding:12px 18px;border-radius:8px;font-size:13px;margin-bottom:20px;">
                    <strong style="display:block;margin-bottom:4px;">Please correct the following errors:</strong>
                    <ul style="margin:0;padding-left:18px;">
                        <?php foreach ($errors->all() as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="profile-container-grid">
                <!-- Company Profile & Branding Form -->
                <div class="profile-card-box">
                    <form method="POST" action="/business/profile" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <?= method_field('PUT') ?>

                        <div class="profile-header-banner">
                            <div class="profile-logo-wrap">
                                <?php if ($business->profile_image): ?>
                                    <img src="<?= e($business->profile_image) ?>" alt="<?= e($business->business_name) ?>" id="preview-logo">
                                <?php else: ?>
                                    <img src="/images/figma/ecocycle-leaf.svg" alt="EcoCycle" id="preview-logo" style="width:36px;height:36px;object-fit:contain;">
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 style="margin:0 0 4px;font-size:17px;color:#123020;font-family:var(--font-display);">
                                    <?= e($business->business_name ?: $business->name) ?>
                                </h3>
                                <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                                    <span style="background:#dcfce7;color:#15803d;font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px;border:1px solid #86efac;">
                                        ✓ Verified SME
                                    </span>
                                    <span style="font-size:12px;color:#617769;">SSM: <?= e($business->business_registration_number ?: ($business->registration_number ?? 'Registered')) ?></span>
                                </div>
                                <label style="display:inline-block;font-size:11.5px;color:#15803d;font-weight:700;cursor:pointer;text-decoration:underline;">
                                    📷 Change Company Logo
                                    <input type="file" name="profile_image" accept="image/png,image/jpeg,image/webp" style="display:none;" onchange="previewProfileImage(this)">
                                </label>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom:14px;">
                            <label for="prof_name" style="font-weight:700;color:#18422d;display:block;margin-bottom:4px;font-size:13px;">
                                Registered Business Name *
                            </label>
                            <input type="text" id="prof_name" name="business_name" value="<?= e(old('business_name', $business->business_name ?: $business->name)) ?>" required style="width:100%;padding:10px 14px;border:1.5px solid #c8d9c6;border-radius:8px;font-size:13.5px;">
                        </div>

                        <div class="form-row-2" style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                            <div class="form-group">
                                <label for="prof_reg" style="font-weight:700;color:#18422d;display:block;margin-bottom:4px;font-size:13px;">
                                    SSM Registration No. *
                                </label>
                                <input type="text" id="prof_reg" name="business_registration_number" value="<?= e(old('business_registration_number', $business->business_registration_number ?: $business->registration_number)) ?>" required style="width:100%;padding:10px 14px;border:1.5px solid #c8d9c6;border-radius:8px;font-size:13.5px;">
                            </div>
                            <div class="form-group">
                                <label for="prof_industry" style="font-weight:700;color:#18422d;display:block;margin-bottom:4px;font-size:13px;">
                                    Industry Sector *
                                </label>
                                <select id="prof_industry" name="industry" required style="width:100%;padding:10px 14px;border:1.5px solid #c8d9c6;border-radius:8px;font-size:13.5px;">
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

                        <div class="form-group" style="margin-bottom:14px;">
                            <label for="prof_phone" style="font-weight:700;color:#18422d;display:block;margin-bottom:4px;font-size:13px;">
                                Contact Phone Number *
                            </label>
                            <input type="text" id="prof_phone" name="business_phone" value="<?= e(old('business_phone', $business->business_phone ?: $business->phone)) ?>" required placeholder="e.g. +60 3-8888 1234" style="width:100%;padding:10px 14px;border:1.5px solid #c8d9c6;border-radius:8px;font-size:13.5px;">
                        </div>

                        <div class="form-group" style="margin-bottom:14px;">
                            <label for="prof_addr" style="font-weight:700;color:#18422d;display:block;margin-bottom:4px;font-size:13px;">
                                Corporate / Facility Address *
                            </label>
                            <input type="text" id="prof_addr" name="registered_address" value="<?= e(old('registered_address', $business->registered_address ?: $business->address)) ?>" required placeholder="Street address or industrial park" style="width:100%;padding:10px 14px;border:1.5px solid #c8d9c6;border-radius:8px;font-size:13.5px;">
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px;">
                            <div class="form-group">
                                <label for="prof_city" style="font-weight:700;color:#18422d;display:block;margin-bottom:4px;font-size:12.5px;">City *</label>
                                <input type="text" id="prof_city" name="city" value="<?= e(old('city', $business->city)) ?>" required style="width:100%;padding:10px 12px;border:1.5px solid #c8d9c6;border-radius:8px;font-size:13px;">
                            </div>
                            <div class="form-group">
                                <label for="prof_state" style="font-weight:700;color:#18422d;display:block;margin-bottom:4px;font-size:12.5px;">State *</label>
                                <input type="text" id="prof_state" name="state" value="<?= e(old('state', $business->state ?: 'Selangor')) ?>" required style="width:100%;padding:10px 12px;border:1.5px solid #c8d9c6;border-radius:8px;font-size:13px;">
                            </div>
                            <div class="form-group">
                                <label for="prof_postcode" style="font-weight:700;color:#18422d;display:block;margin-bottom:4px;font-size:12.5px;">Postcode *</label>
                                <input type="text" id="prof_postcode" name="postcode" value="<?= e(old('postcode', $business->postcode)) ?>" required style="width:100%;padding:10px 12px;border:1.5px solid #c8d9c6;border-radius:8px;font-size:13px;">
                            </div>
                        </div>

                        <button type="submit" class="btn-save" style="width:100%;padding:12px;background:#18422d;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:14px;cursor:pointer;">
                            Save Profile Updates
                        </button>
                    </form>
                </div>

                <!-- Account Security & Change Password -->
                <div class="profile-card-box">
                    <div style="border-bottom:1.5px solid #eef4ed;padding-bottom:14px;margin-bottom:18px;">
                        <h3 style="margin:0 0 4px;font-size:17px;color:#123020;font-family:var(--font-display);">
                            Account Security &amp; Password
                        </h3>
                        <p style="margin:0;font-size:12.5px;color:#556c5e;">Ensure your account remains safe with a strong, distinct password.</p>
                    </div>

                    <form method="POST" action="/business/profile/password">
                        <?= csrf_field() ?>
                        <?= method_field('PUT') ?>

                        <div class="form-group" style="margin-bottom:16px;">
                            <label for="cur_pass" style="font-weight:700;color:#18422d;display:block;margin-bottom:4px;font-size:13px;">
                                Current Password *
                            </label>
                            <input type="password" id="cur_pass" name="current_password" required placeholder="Enter current password" style="width:100%;padding:10px 14px;border:1.5px solid #c8d9c6;border-radius:8px;font-size:13.5px;">
                        </div>

                        <div class="form-group" style="margin-bottom:16px;">
                            <label for="new_pass" style="font-weight:700;color:#18422d;display:block;margin-bottom:4px;font-size:13px;">
                                New Password *
                            </label>
                            <input type="password" id="new_pass" name="password" required placeholder="Minimum 8 characters" style="width:100%;padding:10px 14px;border:1.5px solid #c8d9c6;border-radius:8px;font-size:13.5px;">
                        </div>

                        <div class="form-group" style="margin-bottom:22px;">
                            <label for="new_pass_conf" style="font-weight:700;color:#18422d;display:block;margin-bottom:4px;font-size:13px;">
                                Confirm New Password *
                            </label>
                            <input type="password" id="new_pass_conf" name="password_confirmation" required placeholder="Re-type new password" style="width:100%;padding:10px 14px;border:1.5px solid #c8d9c6;border-radius:8px;font-size:13.5px;">
                        </div>

                        <button type="submit" class="btn-save" style="width:100%;padding:12px;background:#15803d;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:14px;cursor:pointer;">
                            Update Password
                        </button>
                    </form>

                    <!-- Accreditations & SSM Status Card -->
                    <div style="background:#f8faf6;border:1.5px solid #d8e5d6;border-radius:10px;padding:16px;margin-top:24px;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                            <span style="font-size:20px;">🛡️</span>
                            <strong style="color:#113624;font-size:13.5px;">National B2B Verification Status</strong>
                        </div>
                        <p style="margin:0 0 10px;font-size:12px;color:#526b5a;line-height:1.5;">
                            Your business account is verified with the Companies Commission of Malaysia (SSM). This accreditation allows you to legally trade secondary raw materials, publish public circular listings, and request B2B payments nationwide.
                        </p>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:11.5px;color:#15803d;font-weight:700;">● Active Status: Approved</span>
                            <a href="<?= route('business.application') ?>" style="font-size:11.5px;color:#15803d;text-decoration:underline;">View SSM Certificate &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    function previewProfileImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = document.getElementById('preview-logo');
                if (preview) {
                    preview.src = e.target.result;
                    preview.style.width = '100%';
                    preview.style.height = '100%';
                    preview.style.objectFit = 'cover';
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html>
