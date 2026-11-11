<?php
/** @var \App\Models\User $business */
$business = $business ?? new \App\Models\User();
$isRejected = ($business->account_status ?? 'pending') === 'rejected';
$isApproved = ($business->account_status ?? 'pending') === 'approved';
$submittedAt = $business->created_at?->timezone('Asia/Kuala_Lumpur');
$updatedAt = $business->updated_at?->timezone('Asia/Kuala_Lumpur');
$states = ['Johor', 'Kedah', 'Kelantan', 'Kuala Lumpur', 'Labuan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Penang', 'Perak', 'Perlis', 'Putrajaya', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu'];
$industries = ['Agriculture & food', 'Construction & building materials', 'Furniture & wood products', 'Manufacturing', 'Packaging & paper', 'Textiles & apparel', 'Other'];
$statusLabel = match ($business->account_status ?? 'pending') {
    'approved' => 'Approved',
    'rejected' => 'Changes required',
    default => 'Verification pending',
};
$statusClass = match ($business->account_status ?? 'pending') {
    'approved' => 'status-approved',
    'rejected' => 'status-rejected',
    default => 'status-pending',
};
/** @var \Illuminate\Support\ViewErrorBag $errors */
$errors = $errors ?? session('errors', new \Illuminate\Support\ViewErrorBag());
$hasFormErrors = $errors->any();
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Track and modify your EcoCycle business verification application.">
    <title>Application Status — EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/verification.css">
</head>
<body class="verification-page">
<header class="verification-header">
    <a class="verification-brand" href="/" aria-label="EcoCycle home">
        <img src="/images/figma/verification/brand-mark.svg" alt="">
        <span>EcoCycle</span>
    </a>
    <div class="verification-account">
        <?php if ($isApproved): ?>
            <a class="outline-button" href="<?= e(route('business.dashboard')) ?>">Workspace Preview</a>
        <?php endif; ?>
        <span class="status-pill <?= e($statusClass) ?>"><?= e($statusLabel) ?></span>
        <span><?= e($business->name) ?> · <strong><?= e($business->business_name) ?></strong></span>
        <form method="POST" action="<?= e(route('logout')) ?>">
            <?= csrf_field() ?>
            <button class="outline-button" type="submit">Sign out</button>
        </form>
    </div>
</header>

<main class="verification-content">
    <section class="verification-intro">
        <p class="application-id">@<?= e($business->name) ?></p>
        <h1>
            <?php if ($isApproved): ?>
                Your business has been verified!
            <?php elseif ($isRejected): ?>
                Your application needs changes.
            <?php else: ?>
                Your business application is under review.
            <?php endif; ?>
        </h1>
        <p>
            Submitted on <?= $submittedAt ? $submittedAt->format('j M Y \a\t g:i A').' MYT' : 'N/A' ?>.
            <?php if ($updatedAt && $submittedAt && $updatedAt->gt($submittedAt->addSeconds(60))): ?>
                Last modified on <?= $updatedAt->format('j M Y \a\t g:i A').' MYT' ?>.
            <?php endif; ?>
        </p>
    </section>

    <?php if (session('status')): ?>
        <div class="submission-notice" role="status" aria-live="polite">
            <?= e(session('status')) ?>
        </div>
    <?php endif; ?>

    <?php if ($hasFormErrors): ?>
        <div class="submission-error" role="alert">
            <strong>Please correct the errors in the form below.</strong>
        </div>
    <?php endif; ?>

    <aside class="guidance <?= $isRejected ? 'guidance-rejected' : ($isApproved ? 'guidance-approved' : '') ?>">
        <img src="/images/figma/verification/<?= $isRejected ? 'circle-alert.svg' : ($isApproved ? 'circle-check.svg' : 'clock.svg') ?>" alt="">
        <div>
            <strong>
                <?php if ($isApproved): ?>
                    Verification complete
                <?php elseif ($isRejected): ?>
                    Action required on your application
                <?php else: ?>
                    Marketplace &amp; transactions are locked until approval
                <?php endif; ?>
            </strong>
            <p>
                <?php if ($isApproved): ?>
                    Your business has been approved by the platform administrator. You now have full access to the EcoCycle marketplace and inventory management.
                <?php elseif ($isRejected): ?>
                    The administrator requested changes before approval. Please review the reviewer's feedback below, update your details or re-upload documents, and click "Save &amp; Resubmit Changes".
                <?php else: ?>
                    Your application is currently in the administrator review queue. You can review and modify your submitted details anytime below before final approval. We’ll notify you at <strong><?= e($business->email) ?></strong> when a decision is made.
                <?php endif; ?>
            </p>
        </div>
    </aside>

    <?php if ($isRejected && $business->review_note): ?>
        <section class="review-card" style="border-left:4px solid var(--red);background:#fef7f6;padding:20px;display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <img src="/images/figma/verification/circle-alert.svg" alt="" style="width:20px;height:20px;">
                    <strong style="color:var(--red);font-size:15px;">Administrator Feedback — What you need to fix:</strong>
                </div>
                <span class="status-pill status-rejected" style="font-size:11px;padding:4px 10px;">Action Required</span>
            </div>
            <div style="background:#fff;padding:14px 16px;border-radius:8px;border:1px solid #f2cfca;color:var(--ink);font-size:13px;line-height:1.6;">
                <?= nl2br(e($business->review_note)) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:4px;">
                <span style="color:var(--muted);font-size:12px;">Update your details or replace SSM files in the form below, then submit for re-review.</span>
                <a href="#modify-application" class="action-button-primary" style="font-size:12px;padding:6px 14px;" onclick="toggleEditForm(true)">
                    <span>✎ Go to Edit Form &darr;</span>
                </a>
            </div>
        </section>
    <?php endif; ?>

    <div class="review-grid">
        <section class="review-card progress-card">
            <h2>Verification progress</h2>
            <ol class="timeline">
                <li class="timeline-done">
                    <img src="/images/figma/verification/circle-check.svg" alt="">
                    <span>
                        <strong>Account created</strong>
                        <small><?= $submittedAt?->format('j M Y · g:i A') ?? 'Completed' ?></small>
                    </span>
                </li>
                <li class="timeline-done">
                    <img src="/images/figma/verification/circle-check.svg" alt="">
                    <span>
                        <strong>Documents submitted</strong>
                        <small>2 verification files uploaded</small>
                    </span>
                </li>
                <li class="<?= $isApproved || $isRejected ? 'timeline-done' : 'timeline-current' ?>">
                    <img src="/images/figma/verification/<?= $isApproved ? 'circle-check.svg' : ($isRejected ? 'circle-alert.svg' : 'clock-pending.svg') ?>" alt="">
                    <span>
                        <strong>Administrator review</strong>
                        <small>
                            <?php if ($isApproved): ?>
                                Approved by administrator
                            <?php elseif ($isRejected): ?>
                                Application declined by administrator
                            <?php else: ?>
                                In queue · Currently under review
                            <?php endif; ?>
                        </small>
                    </span>
                </li>
                <li class="<?= $isApproved ? 'timeline-done' : 'timeline-waiting' ?>">
                    <img src="/images/figma/verification/<?= $isApproved ? 'circle-check.svg' : 'clock-pending.svg' ?>" alt="">
                    <span>
                        <strong>Decision &amp; marketplace access</strong>
                        <small><?= $isApproved ? 'Marketplace access enabled' : 'Unlocked automatically upon approval' ?></small>
                    </span>
                </li>
            </ol>
            <p class="detail-line">Registered business <strong><?= e($business->business_name) ?></strong></p>
            <p class="detail-line">SSM number <strong><?= e($business->business_registration_number) ?></strong></p>
        </section>

        <section class="review-card documents-card" id="submitted-documents">
            <h2>Your submitted documents</h2>
            <div class="document-row">
                <img src="/images/figma/verification/file-check.svg" alt="">
                <span>
                    <strong>SSM registration certificate</strong>
                    <small>Official certificate of incorporation / registration</small>
                    <em>✓ Uploaded</em>
                </span>
                <?php if ($business->ssm_certificate_path): ?>
                    <a class="outline-button download-link" href="<?= e(route('business.application.document', 'certificate')) ?>">Download</a>
                <?php endif; ?>
            </div>

            <div class="document-row">
                <img src="/images/figma/verification/file-check.svg" alt="">
                <span>
                    <strong>SSM company / business profile</strong>
                    <small>Full profile including owners, directors and activities</small>
                    <em>✓ Uploaded</em>
                </span>
                <?php if ($business->ssm_profile_path): ?>
                    <a class="outline-button download-link" href="<?= e(route('business.application.document', 'profile')) ?>">Download</a>
                <?php endif; ?>
            </div>

            <p class="document-note">Documents are encrypted and stored privately. They are accessible only by you and EcoCycle administrators.</p>
            <div class="document-actions">
                <a class="outline-button inline-button" href="#modify-application" onclick="toggleEditForm(true)">
                    <span>✎ Modify or replace documents</span>
                </a>
            </div>
        </section>
    </div>

    <!-- Application Details Card -->
    <section class="review-card application-details-card" id="application-details">
        <div class="section-card-header">
            <div>
                <h2>Submitted application details</h2>
                <p>The information submitted for your company profile and legitimacy check.</p>
            </div>
            <button type="button" class="action-button-primary" onclick="toggleEditForm(true)">
                <span>✎ Edit application</span>
            </button>
        </div>

        <!-- Company Profile Avatar & Identity Header -->
        <div class="company-profile-header-banner" style="display:flex;align-items:center;gap:18px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px solid #e2ede0;">
            <div style="width:72px;height:72px;border-radius:50%;overflow:hidden;background:#eef6ec;border:2.5px solid #285c44;display:grid;place-items:center;flex-shrink:0;box-shadow:0 2px 8px rgba(40,92,68,0.15);">
                <?php if ($business->profile_image): ?>
                    <img src="<?= e($business->profile_image_url) ?>" alt="<?= e($business->business_name) ?>" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                    <span style="font-size:22px;font-weight:700;color:#185a36;letter-spacing:0.02em;"><?= strtoupper(substr($business->business_name ?: $business->name, 0, 2)) ?></span>
                <?php endif; ?>
            </div>
            <div>
                <h3 style="margin:0 0 4px;font-size:18px;font-weight:700;color:#123321;"><?= e($business->business_name) ?></h3>
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#176b40;font-weight:600;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Verified Malaysian SME &middot; <?= e($business->industry) ?>
                </span>
                <span style="display:block;font-size:11px;color:#556c5d;margin-top:2px;">SSM: <?= e($business->business_registration_number) ?></span>
            </div>
        </div>

        <dl class="business-info-grid">
            <div>
                <dt>Registered business name</dt>
                <dd><?= e($business->business_name) ?></dd>
            </div>
            <div>
                <dt>SSM registration number</dt>
                <dd><?= e($business->business_registration_number) ?></dd>
            </div>
            <div>
                <dt>Industry</dt>
                <dd><?= e($business->industry) ?></dd>
            </div>
            <div>
                <dt>Company username</dt>
                <dd><?= e($business->name) ?></dd>
            </div>
            <div>
                <dt>Business email</dt>
                <dd><?= e($business->email) ?></dd>
            </div>
            <div>
                <dt>Business phone</dt>
                <dd><?= e($business->business_phone) ?></dd>
            </div>
            <div class="full-width">
                <dt>Registered address</dt>
                <dd><?= e(implode(', ', array_filter([$business->registered_address, $business->postcode, $business->city, $business->state]))) ?></dd>
            </div>
        </dl>
    </section>

    <!-- Application Modify Form Card -->
    <section class="review-card modify-card <?= ($isRejected || $hasFormErrors) ? 'form-visible' : '' ?>" id="modify-application">
        <div class="section-card-header">
            <div>
                <h2><?= $isRejected ? 'Update application details &amp; fix issues' : 'Modify your application details' ?></h2>
                <p>Update any details or re-upload clear SSM documents. Saving will update your record and notify the administrator for review.</p>
            </div>
            <button type="button" class="outline-button" onclick="toggleEditForm(false)">
                <span>Cancel</span>
            </button>
        </div>

            <form class="edit-form" method="POST" action="<?= e(route('business.application.update')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <!-- Edit Company Profile Photo / Logo -->
            <div style="margin-bottom:20px;background:#fbfdfa;border:1px dashed #cadbc9;padding:16px;border-radius:10px;">
                <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
                    <div id="edit-avatar-preview-box" style="width:68px;height:68px;border-radius:50%;overflow:hidden;background:#eef6ec;border:2.5px solid #285c44;display:grid;place-items:center;flex-shrink:0;">
                        <?php if ($business->profile_image): ?>
                            <img src="<?= e($business->profile_image_url) ?>" alt="<?= e($business->business_name) ?>" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                            <span style="font-size:20px;font-weight:700;color:#185a36;"><?= strtoupper(substr($business->business_name ?: $business->name, 0, 2)) ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1;min-width:240px;">
                        <label for="edit_profile_image" style="cursor:pointer;display:block;">
                            <strong style="display:block;font-size:13px;color:#123321;margin-bottom:3px;">Business profile photo / company logo</strong>
                            <small style="display:block;font-size:11px;color:#556c5d;margin-bottom:8px;line-height:1.4;">Upload a new image (JPG, PNG, or WEBP up to 5 MB) to update your company avatar across the portal, directory cards, and marketplace.</small>
                        </label>
                        <input id="edit_profile_image" name="profile_image" type="file" accept="image/png,image/jpeg,image/webp" onchange="previewEditAvatar(this)">
                        <?php if ($errors->has('profile_image')): ?>
                            <small class="field-error" style="display:block;margin-top:4px;"><?= e($errors->first('profile_image')) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="field-grid">
                <div class="form-field">
                    <label for="business_name">Registered business name *</label>
                    <input id="business_name" name="business_name" type="text" value="<?= e(old('business_name', $business->business_name)) ?>" placeholder="e.g. KayuKita Manufacturing Sdn. Bhd." required maxlength="255">
                    <?php if ($errors->has('business_name')): ?>
                        <small class="field-error"><?= e($errors->first('business_name')) ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-field">
                    <label for="business_registration_number">SSM registration number *</label>
                    <input id="business_registration_number" name="business_registration_number" type="text" value="<?= e(old('business_registration_number', $business->business_registration_number)) ?>" placeholder="e.g. 202101023456 (1425678-X)" required maxlength="60">
                    <?php if ($errors->has('business_registration_number')): ?>
                        <small class="field-error"><?= e($errors->first('business_registration_number')) ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-field">
                    <label for="industry">Industry *</label>
                    <select id="industry" name="industry" required>
                        <option value="">e.g. Select industry</option>
                        <?php foreach ($industries as $industry): ?>
                            <option value="<?= e($industry) ?>" <?= old('industry', $business->industry) === $industry ? 'selected' : '' ?>><?= e($industry) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($errors->has('industry')): ?>
                        <small class="field-error"><?= e($errors->first('industry')) ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-field">
                    <label for="business_phone">Business phone *</label>
                    <input id="business_phone" name="business_phone" type="tel" value="<?= e(old('business_phone', $business->business_phone)) ?>" placeholder="e.g. +60 12-345 6789" required maxlength="30">
                    <?php if ($errors->has('business_phone')): ?>
                        <small class="field-error"><?= e($errors->first('business_phone')) ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-field full-width">
                    <label for="registered_address">Registered address *</label>
                    <input id="registered_address" name="registered_address" type="text" value="<?= e(old('registered_address', $business->registered_address)) ?>" placeholder="e.g. No. 18, Jalan Industri U6/8, Seksyen U6" required maxlength="255">
                    <?php if ($errors->has('registered_address')): ?>
                        <small class="field-error"><?= e($errors->first('registered_address')) ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-field">
                    <label for="city">City *</label>
                    <input id="city" name="city" type="text" value="<?= e(old('city', $business->city)) ?>" placeholder="e.g. Shah Alam" required maxlength="100">
                    <?php if ($errors->has('city')): ?>
                        <small class="field-error"><?= e($errors->first('city')) ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-field">
                    <label for="postcode">Postcode *</label>
                    <input id="postcode" name="postcode" type="text" value="<?= e(old('postcode', $business->postcode)) ?>" placeholder="e.g. 40150" required maxlength="12">
                    <?php if ($errors->has('postcode')): ?>
                        <small class="field-error"><?= e($errors->first('postcode')) ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-field">
                    <label for="state">State *</label>
                    <select id="state" name="state" required>
                        <option value="">e.g. Select state</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?= e($state) ?>" <?= old('state', $business->state) === $state ? 'selected' : '' ?>><?= e($state) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($errors->has('state')): ?>
                        <small class="field-error"><?= e($errors->first('state')) ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-field">
                    <label for="name">Company username *</label>
                    <input id="name" name="name" type="text" value="<?= e(old('name', $business->name)) ?>" placeholder="e.g. kayukita_admin" required maxlength="255">
                    <?php if ($errors->has('name')): ?>
                        <small class="field-error"><?= e($errors->first('name')) ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="documents-upload-grid">
                <div class="doc-upload-box">
                    <label for="edit_ssm_certificate">
                        <strong>Replace SSM registration certificate</strong>
                        <small>Optional — upload a new PDF, JPG, or PNG (up to 10 MB) to replace current file.</small>
                    </label>
                    <input id="edit_ssm_certificate" name="ssm_certificate" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                    <?php if ($errors->has('ssm_certificate')): ?>
                        <small class="field-error"><?= e($errors->first('ssm_certificate')) ?></small>
                    <?php endif; ?>
                </div>

                <div class="doc-upload-box">
                    <label for="edit_ssm_profile">
                        <strong>Replace SSM company / business profile</strong>
                        <small>Optional — upload a new PDF, JPG, or PNG (up to 10 MB) to replace current file.</small>
                    </label>
                    <input id="edit_ssm_profile" name="ssm_profile" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                    <?php if ($errors->has('ssm_profile')): ?>
                        <small class="field-error"><?= e($errors->first('ssm_profile')) ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-footer">
                <p class="muted-note">Submitting will update your application record and alert the administrator for review.</p>
                <div class="footer-buttons">
                    <button type="button" class="outline-button" onclick="toggleEditForm(false)">Cancel</button>
                    <button type="submit" class="action-button-primary">Save &amp; Resubmit Changes</button>
                </div>
            </div>
        </form>
    </section>

    <?php if ($isRejected): ?>
        <section class="review-card" style="border-left:4px solid #d99;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;background:#fafafa;">
            <div>
                <strong style="color:var(--ink);font-size:13px;">Don’t want to fix and resubmit?</strong>
                <p style="margin:2px 0 0;color:var(--muted);font-size:12px;">You can permanently delete your submitted application data and start over fresh.</p>
            </div>
            <form method="POST" action="<?= e(route('business.application.destroy')) ?>" onsubmit="return confirm('Are you sure you want to permanently delete all your application data and uploaded documents?');">
                <?= csrf_field() ?>
                <?= method_field('DELETE') ?>
                <button type="submit" class="outline-button" style="color:var(--red);border-color:#e0b2ac;background:#fff8f7;font-weight:600;">
                    <span>🗑 Delete all application data &amp; restart</span>
                </button>
            </form>
        </section>
    <?php endif; ?>

    <p class="document-trust">
        <img src="/images/figma/verification/lock.svg" alt="">
        Business documents and data are securely handled and visible only to authorised EcoCycle administrators.
    </p>
</main>

<script>
function previewEditAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var box = document.getElementById('edit-avatar-preview-box');
            if (box) {
                box.innerHTML = '<img src="' + e.target.result + '" alt="New Logo Preview" style="width:100%;height:100%;object-fit:cover;">';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleEditForm(show) {
    const formCard = document.getElementById('modify-application');
    if (!formCard) return;
    if (show) {
        formCard.classList.add('form-visible');
        formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        formCard.classList.remove('form-visible');
    }
}
</script>
<script src="/js/language-switcher.js" defer></script>
</body>
</html>
