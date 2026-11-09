<?php
$isRejected = $business->account_status === 'rejected';
$isApproved = $business->account_status === 'approved';
$submittedAt = $business->created_at?->timezone('Asia/Kuala_Lumpur');
$updatedAt = $business->updated_at?->timezone('Asia/Kuala_Lumpur');
$states = ['Johor', 'Kedah', 'Kelantan', 'Kuala Lumpur', 'Labuan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Penang', 'Perak', 'Perlis', 'Putrajaya', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu'];
$industries = ['Agriculture & food', 'Construction & building materials', 'Furniture & wood products', 'Manufacturing', 'Packaging & paper', 'Textiles & apparel', 'Other'];
$statusLabel = match ($business->account_status) {
    'approved' => 'Approved',
    'rejected' => 'Changes required',
    default => 'Verification pending',
};
$statusClass = match ($business->account_status) {
    'approved' => 'status-approved',
    'rejected' => 'status-rejected',
    default => 'status-pending',
};
$errors = $errors ?? new \Illuminate\Support\MessageBag();
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
        <p class="application-id">BUSINESS APPLICATION #<?= e(str_pad((string) $business->id, 6, '0', STR_PAD_LEFT)) ?></p>
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
                    <?= e($business->review_note ?: 'The administrator requested changes. Please review your information and documents below, make the necessary corrections, and click "Save & Resubmit Changes".') ?>
                <?php else: ?>
                    Your application is currently in the administrator review queue. You can review and modify your submitted details anytime below before final approval. We’ll notify you at <strong><?= e($business->email) ?></strong> when a decision is made.
                <?php endif; ?>
            </p>
        </div>
    </aside>

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
                                Changes requested by reviewer
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
                <a class="outline-button inline-button" href="mailto:support@ecocycle.my">
                    <img src="/images/figma/verification/mail.svg" alt=""> Contact support
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
    <section class="review-card modify-card <?= $hasFormErrors ? 'form-visible' : '' ?>" id="modify-application">
        <div class="section-card-header">
            <div>
                <h2>Modify your application details</h2>
                <p>Update any details or re-upload documents. Saving will update your record and notify the administrator for review.</p>
            </div>
            <button type="button" class="outline-button" onclick="toggleEditForm(false)">
                <span>Cancel</span>
            </button>
        </div>

        <?php if ($isRejected && $business->review_note): ?>
            <div class="review-reason">
                <img src="/images/figma/verification/circle-alert.svg" alt="">
                <span>
                    <strong>Reviewer’s note:</strong>
                    <small><?= e($business->review_note) ?></small>
                </span>
            </div>
        <?php endif; ?>

        <form class="edit-form" method="POST" action="<?= e(route('business.application.update')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

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

    <p class="document-trust">
        <img src="/images/figma/verification/lock.svg" alt="">
        Business documents and data are securely handled and visible only to authorised EcoCycle administrators.
    </p>
</main>

<script>
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
