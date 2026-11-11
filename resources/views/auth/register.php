<?php
/** @var \Illuminate\Support\ViewErrorBag $errors */
$errors = $errors ?? session('errors', new \Illuminate\Support\ViewErrorBag());
$fields = ['business_name', 'business_registration_number', 'industry', 'business_phone', 'registered_address', 'city', 'postcode', 'state', 'name', 'email', 'password', 'password_confirmation', 'profile_image', 'ssm_certificate', 'ssm_profile'];
$fieldErrors = [];
foreach ($fields as $field) { $fieldErrors[$field] = $errors->first($field); }
$passwordMismatch = $fieldErrors['password'] === 'The passwords do not match.';
if ($passwordMismatch) {
    $fieldErrors['password_confirmation'] = $fieldErrors['password'];
    $fieldErrors['password'] = '';
}
$errorLabels = [
    'business_name' => 'Registered business name',
    'business_registration_number' => 'SSM registration number',
    'industry' => 'Industry',
    'business_phone' => 'Business phone',
    'registered_address' => 'Registered address',
    'city' => 'City',
    'postcode' => 'Postcode',
    'state' => 'State',
    'name' => 'Company username',
    'email' => 'Business email',
    'password' => 'Password',
    'password_confirmation' => 'Confirm password',
    'profile_image' => 'Business logo or profile photo',
    'ssm_certificate' => 'SSM registration certificate',
    'ssm_profile' => 'SSM business profile',
];
$states = ['Johor', 'Kedah', 'Kelantan', 'Kuala Lumpur', 'Labuan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Penang', 'Perak', 'Perlis', 'Putrajaya', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu'];
$industries = ['Agriculture & food', 'Construction & building materials', 'Furniture & wood products', 'Manufacturing', 'Packaging & paper', 'Textiles & apparel', 'Other'];
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Register your business with EcoCycle for verification.">
    <title>Register your business - EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/auth/register.css">

</head>
<body class="signup-page">
    <header class="signup-header">
        <a class="signup-brand" href="/" aria-label="EcoCycle home"><span class="brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span><span>EcoCycle</span></a>
        <a class="header-signin" href="/login">Already registered? <span>Sign in <span aria-hidden="true">&#8594;</span></span></a>

    </header>
    <main class="signup-layout">
        <aside class="signup-guidance">
            <span class="business-badge">BUSINESS OWNER</span>
            <h1>Start your circular business journey.</h1>
            <p class="guidance-intro">Create one business account to manage operations, list surplus and obtain materials.</p>
            <div class="signup-hero-photo"><img src="/images/figma/signup/verification-hero.jpg" alt="Materials being prepared for reuse at a business warehouse"><div class="photo-caption"><img src="/images/figma/signup/recycle.svg" alt=""><span>A resource, not a leftover.<small>Better operations. More possibilities.</small></span></div></div>
            <ol class="review-steps">
                <li><span class="step-icon complete">&#10003;</span><span><strong>Business information</strong><small>Tell us about your registered company.</small></span></li>
                <li><span class="step-icon complete">&#10003;</span><span><strong>Legitimacy documents</strong><small>Upload clear, current SSM documents.</small></span></li>
                <li><span class="step-icon pending">&#9711;</span><span><strong>Administrator review</strong><small>We will email you with the decision.</small></span></li>
            </ol>
            <div class="verification-note"><span class="note-check">&#10003;</span><span><strong>Verification comes first</strong><small>You can access the marketplace and transactions only after approval. There are no separate buyer or seller accounts.</small></span></div>
        </aside>
        <section class="signup-form-panel" aria-labelledby="signup-heading">
            <header class="form-heading"><h2 id="signup-heading">Register your business</h2><p>All fields marked * are required. Your documents are private.</p></header>
            <?php if (session('status')): ?><div class="success-notice" role="status" aria-live="polite"><?= e(session('status')) ?></div><?php endif; ?>
            <?php if ($errors->has('name') || $errors->has('email')): ?>
                <div class="cant-use-alert-banner" role="alert">
                    <div class="alert-banner-header">
                        <span class="alert-icon">⚠️</span>
                        <strong>Cannot use specified details:</strong>
                    </div>
                    <ul class="alert-banner-list">
                        <?php if ($errors->has('name')): ?>
                            <li><?= e($errors->first('name')) ?></li>
                        <?php endif; ?>
                        <?php if ($errors->has('email')): ?>
                            <li><?= e($errors->first('email')) ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($errors->any()): ?><div class="error-summary" id="signup-error-summary" role="alert" tabindex="-1"><strong>Please review these items:</strong><ul><?php foreach ($errors->getMessages() as $field => $messages): ?><li><a href="#<?= e($field) ?>"><?= e($errorLabels[$field] ?? ucfirst(str_replace('_', ' ', $field))) ?>:</a> <?= e($messages[0]) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form id="business-registration-form" class="registration-form" method="POST" action="/register" enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>
                <section class="form-card">
                    <div class="card-heading"><h3>Company &amp; contact information</h3><p>Use the details shown on your SSM registration.</p></div>

                    <!-- Business Profile Image / Company Logo -->
                    <div class="profile-upload-row" <?= $fieldErrors['profile_image'] ? 'data-invalid="true"' : '' ?>>
                        <div class="profile-avatar-preview" id="avatar-preview-box">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#285c44" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="profile-upload-meta">
                            <label for="profile_image">Company profile image or logo *</label>
                            <small>Upload your business logo or profile photo (PNG, JPG, or WEBP up to 5 MB) *. Required for your verified SME directory profile.</small>
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;position:relative;">
                                <input type="file" id="profile_image" name="profile_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required class="accessible-file-input" onchange="previewBusinessAvatar(this)" aria-invalid="<?= $fieldErrors['profile_image'] ? 'true' : 'false' ?>">
                                <button type="button" class="btn-select-avatar" onclick="document.getElementById('profile_image').click()">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                        <circle cx="12" cy="13" r="4"></circle>
                                    </svg>
                                    <span>Choose company image</span>
                                </button>
                                <span id="profile_image-filename" style="font-size:11px;color:#285c44;font-weight:600;"></span>
                            </div>
                            <?php if (!empty($fieldErrors['profile_image'])): ?><small class="field-error" id="profile_image-server-error" style="display:block;margin-top:4px;"><?= e($fieldErrors['profile_image']) ?></small><?php endif; ?>
                        </div>
                    </div>

                    <div class="field-grid">
                        <div class="form-field">
                            <label for="business_name">Registered business name *</label>
                            <input id="business_name" name="business_name" value="<?= e(old('business_name')) ?>" placeholder="e.g. KayuKita Manufacturing Sdn. Bhd." autocomplete="organization" maxlength="255" required aria-invalid="<?= $fieldErrors['business_name'] ? 'true' : 'false' ?>">
                            <?php if ($fieldErrors['business_name']): ?><small class="field-error"><?= e($fieldErrors['business_name']) ?></small><?php endif; ?>
                        </div>
                        <div class="form-field">
                            <label for="business_registration_number">SSM registration number *</label>
                            <input id="business_registration_number" name="business_registration_number" value="<?= e(old('business_registration_number')) ?>" placeholder="e.g. 202101023456 (1425678-X)" maxlength="60" required aria-invalid="<?= $fieldErrors['business_registration_number'] ? 'true' : 'false' ?>">
                            <?php if ($fieldErrors['business_registration_number']): ?><small class="field-error"><?= e($fieldErrors['business_registration_number']) ?></small><?php endif; ?>
                        </div>
                        <div class="form-field">
                            <label for="industry">Industry *</label>
                            <select id="industry" name="industry" required aria-invalid="<?= $fieldErrors['industry'] ? 'true' : 'false' ?>">
                                <option value="">e.g. Select your industry</option>
                                <?php foreach ($industries as $industry): ?>
                                    <option value="<?= e($industry) ?>" <?= old('industry') === $industry ? 'selected' : '' ?>><?= e($industry) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($fieldErrors['industry']): ?><small class="field-error"><?= e($fieldErrors['industry']) ?></small><?php endif; ?>
                        </div>
                        <div class="form-field">
                            <label for="business_phone">Business phone *</label>
                            <input id="business_phone" name="business_phone" type="tel" value="<?= e(old('business_phone')) ?>" placeholder="e.g. +60 12-345 6789" autocomplete="tel" maxlength="30" required aria-invalid="<?= $fieldErrors['business_phone'] ? 'true' : 'false' ?>">
                            <?php if ($fieldErrors['business_phone']): ?><small class="field-error"><?= e($fieldErrors['business_phone']) ?></small><?php endif; ?>
                        </div>
                        <div class="form-field full-field">
                            <label for="registered_address">Registered address *</label>
                            <input id="registered_address" name="registered_address" value="<?= e(old('registered_address')) ?>" placeholder="e.g. No. 18, Jalan Industri U6/8, Seksyen U6" autocomplete="street-address" maxlength="255" required aria-invalid="<?= $fieldErrors['registered_address'] ? 'true' : 'false' ?>">
                            <?php if ($fieldErrors['registered_address']): ?><small class="field-error"><?= e($fieldErrors['registered_address']) ?></small><?php endif; ?>
                        </div>
                        <div class="form-field">
                            <label for="city">City *</label>
                            <input id="city" name="city" value="<?= e(old('city')) ?>" placeholder="e.g. Shah Alam" autocomplete="address-level2" maxlength="100" required aria-invalid="<?= $fieldErrors['city'] ? 'true' : 'false' ?>">
                            <?php if ($fieldErrors['city']): ?><small class="field-error"><?= e($fieldErrors['city']) ?></small><?php endif; ?>
                        </div>
                        <div class="form-field">
                            <label for="postcode">Postcode *</label>
                            <input id="postcode" name="postcode" value="<?= e(old('postcode')) ?>" placeholder="e.g. 40150" autocomplete="postal-code" inputmode="numeric" maxlength="12" required aria-invalid="<?= $fieldErrors['postcode'] ? 'true' : 'false' ?>">
                            <?php if ($fieldErrors['postcode']): ?><small class="field-error"><?= e($fieldErrors['postcode']) ?></small><?php endif; ?>
                        </div>
                        <div class="form-field">
                            <label for="state">State *</label>
                            <select id="state" name="state" required aria-invalid="<?= $fieldErrors['state'] ? 'true' : 'false' ?>">
                                <option value="">e.g. Select registered state</option>
                                <?php foreach ($states as $state): ?>
                                    <option value="<?= e($state) ?>" <?= old('state') === $state ? 'selected' : '' ?>><?= e($state) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($fieldErrors['state']): ?><small class="field-error"><?= e($fieldErrors['state']) ?></small><?php endif; ?>
                        </div>
                        <div class="form-field">
                            <label for="name">Company username *</label>
                            <input id="name" name="name" value="<?= e(old('name')) ?>" placeholder="e.g. kayukita_admin" autocomplete="username" maxlength="255" required aria-invalid="<?= $fieldErrors['name'] ? 'true' : 'false' ?>">
                            <small class="field-hint">Your company login username</small>
                            <div class="field-alert-slot" id="name-availability-alert">
                                <?php if ($fieldErrors['name']): ?>
                                    <div class="field-inline-alert" role="alert">
                                        <span class="alert-badge">Cannot use</span> <?= e($fieldErrors['name']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-field">
                            <label for="email">Business email *</label>
                            <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" placeholder="e.g. owner@kayukita.com.my" autocomplete="email" maxlength="255" required aria-invalid="<?= $fieldErrors['email'] ? 'true' : 'false' ?>">
                            <div class="field-alert-slot" id="email-availability-alert">
                                <?php if ($fieldErrors['email']): ?>
                                    <div class="field-inline-alert" role="alert">
                                        <span class="alert-badge">Cannot use</span> <?= e($fieldErrors['email']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-field">
                            <label for="password">Password *</label>
                            <div class="password-wrap">
                                <input id="password" name="password" type="password" minlength="8" placeholder="e.g. Min. 8 characters with a number" autocomplete="new-password" required aria-invalid="<?= $fieldErrors['password'] ? 'true' : 'false' ?>">
                                <button type="button" data-password-toggle="password" aria-label="Show password">Show</button>
                            </div>
                            <small class="field-hint" id="password-hint">At least 8 characters, including a number.</small>
                            <?php if ($fieldErrors['password']): ?><small class="field-error"><?= e($fieldErrors['password']) ?></small><?php endif; ?>
                        </div>
                        <div class="form-field">
                            <label for="password_confirmation">Confirm password *</label>
                            <div class="password-wrap">
                                <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" placeholder="e.g. Re-enter password to match" autocomplete="new-password" required aria-invalid="<?= $fieldErrors['password_confirmation'] ? 'true' : 'false' ?>">
                                <button type="button" data-password-toggle="password_confirmation" aria-label="Show password confirmation">Show</button>
                            </div>
                            <small class="field-hint" id="password_confirmation-hint">At least 8 characters, including a number.</small>
                            <?php if ($fieldErrors['password_confirmation']): ?><small class="field-error"><?= e($fieldErrors['password_confirmation']) ?></small><?php endif; ?>
                        </div>
                    </div>
                </section>
                <section class="form-card document-card">
                    <div class="card-heading">
                        <h3>Business legitimacy documents</h3>
                        <p>PDF, JPG or PNG — Maximum 10 MB per file. Upload and click <strong>View &amp; Confirm File</strong> to verify your certificate before submitting.</p>
                    </div>

                    <div class="upload-wrapper">
                        <div class="upload-field" <?= $fieldErrors['ssm_certificate'] ? 'data-invalid="true"' : '' ?>>
                            <img src="/images/figma/signup/verification-file.svg" alt="">
                            <div>
                                <label for="ssm_certificate">SSM registration certificate *</label>
                                <small class="selected-file" data-file-name="ssm_certificate">Choose a PDF, JPG or PNG file (up to 10 MB).</small>
                                <?php if ($fieldErrors['ssm_certificate']): ?><small class="field-error" id="ssm_certificate-error"><?= e($fieldErrors['ssm_certificate']) ?></small><?php endif; ?>
                            </div>
                            <input class="file-input" id="ssm_certificate" name="ssm_certificate" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required aria-invalid="<?= $fieldErrors['ssm_certificate'] ? 'true' : 'false' ?>">
                        </div>
                        <div class="file-confirm-card" id="confirm-card-ssm_certificate" style="display:none;">
                            <div class="confirm-card-main">
                                <span class="confirm-badge">✓ Certificate Selected</span>
                                <div class="confirm-card-meta">
                                    <strong class="confirm-filename" id="filename-ssm_certificate"></strong>
                                    <small class="confirm-filesize" id="filesize-ssm_certificate"></small>
                                </div>
                            </div>
                            <div class="confirm-card-actions">
                                <button type="button" class="view-file-btn" onclick="openDocumentPreview('ssm_certificate')">
                                    <span>👁 View &amp; Confirm File</span>
                                </button>
                                <button type="button" class="change-file-btn" onclick="document.getElementById('ssm_certificate').click()">
                                    <span>Change</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="upload-wrapper" style="margin-top:12px;">
                        <div class="upload-field upload-dashed" <?= $fieldErrors['ssm_profile'] ? 'data-invalid="true"' : '' ?>>
                            <img src="/images/figma/signup/verification-upload.svg" alt="">
                            <div>
                                <label for="ssm_profile">Current SSM company / business profile *</label>
                                <small class="selected-file" data-file-name="ssm_profile">Choose file or drag and drop (up to 10 MB).</small>
                                <?php if ($fieldErrors['ssm_profile']): ?><small class="field-error" id="ssm_profile-error"><?= e($fieldErrors['ssm_profile']) ?></small><?php endif; ?>
                            </div>
                            <input class="file-input" id="ssm_profile" name="ssm_profile" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required aria-invalid="<?= $fieldErrors['ssm_profile'] ? 'true' : 'false' ?>">
                        </div>
                        <div class="file-confirm-card" id="confirm-card-ssm_profile" style="display:none;">
                            <div class="confirm-card-main">
                                <span class="confirm-badge">✓ Profile Selected</span>
                                <div class="confirm-card-meta">
                                    <strong class="confirm-filename" id="filename-ssm_profile"></strong>
                                    <small class="confirm-filesize" id="filesize-ssm_profile"></small>
                                </div>
                            </div>
                            <div class="confirm-card-actions">
                                <button type="button" class="view-file-btn" onclick="openDocumentPreview('ssm_profile')">
                                    <span>👁 View &amp; Confirm File</span>
                                </button>
                                <button type="button" class="change-file-btn" onclick="document.getElementById('ssm_profile').click()">
                                    <span>Change</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
                <div class="form-actions"><button type="submit" class="submit-button" data-submit-button><span aria-hidden="true">&#8594;</span><span data-submit-label>Submit for verification</span></button></div>
            </form>
        </section>
    </main>

    <!-- File Confirmation Modal -->
    <div class="preview-modal-backdrop" id="doc-preview-modal" style="display:none;" onclick="if(event.target === this) closeDocumentPreview()">
        <div class="preview-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modal-doc-title">
            <div class="preview-modal-header">
                <div>
                    <h3 id="modal-doc-title">Document Confirmation Preview</h3>
                    <p id="modal-doc-subtitle">Confirm that all text, company name and SSM numbers are readable</p>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeDocumentPreview()" aria-label="Close preview">✕</button>
            </div>
            <div class="preview-modal-body" id="modal-doc-body">
                <!-- Preview injected via JavaScript -->
            </div>
            <div class="preview-modal-footer">
                <div class="modal-file-status">
                    <span class="status-verified-pill">✓ Ready to submit</span>
                    <span id="modal-file-info" style="font-size:11px;color:#526158;margin-left:6px;"></span>
                </div>
                <div class="modal-footer-actions">
                    <a id="modal-open-newtab" href="#" target="_blank" rel="noopener" class="outline-button" style="display:none;">Open in new window ↗</a>
                    <button type="button" class="action-confirm-btn" onclick="closeDocumentPreview()">Confirm file</button>
                </div>
            </div>
        </div>
    </div>
    <script>
    function previewBusinessAvatar(input) {
        if (input.files && input.files[0]) {
            var file = input.files[0];
            var reader = new FileReader();
            reader.onload = function(e) {
                var box = document.getElementById('avatar-preview-box');
                if (box) {
                    box.innerHTML = '<img src="' + e.target.result + '" alt="Company Logo Preview" style="width:100%;height:100%;object-fit:cover;">';
                }
                var nameSpan = document.getElementById('profile_image-filename');
                if (nameSpan) {
                    nameSpan.textContent = '✓ ' + file.name;
                }
            };
            reader.readAsDataURL(file);
            input.setCustomValidity('');
            var clientError = document.getElementById('profile_image-client-error');
            if (clientError) clientError.remove();
            var serverError = document.getElementById('profile_image-server-error');
            if (serverError) serverError.remove();
            input.setAttribute('aria-invalid', 'false');
            var container = input.closest('.profile-upload-row');
            if (container) delete container.dataset.invalid;
        }
    }
    </script>
    <script src="/js/auth/signup.js" defer></script>
    <script src="/js/language-switcher.js" defer></script>
</body>
</html>
