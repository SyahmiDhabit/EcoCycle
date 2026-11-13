<?php

use App\Models\User;
use Illuminate\Support\ViewErrorBag;

/** @var User $business */
$business = $business ?? new User;
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
/** @var ViewErrorBag $errors */
$errors = $errors ?? session('errors', new ViewErrorBag);
$hasFormErrors = $errors->any();
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Track your EcoCycle business registration and admin approval status.">
    <title>Registration Pending Approval &mdash; EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/verification.css">
    <style>
        .pending-header {
            min-height: 80px;
            padding: 0 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            background: #ffffff;
            border-bottom: 1px solid var(--line);
        }
        .pending-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--forest);
            font-family: var(--font-display);
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.04em;
            text-decoration: none;
        }
        .pending-brand img {
            width: 34px;
            height: 34px;
        }
        .pending-account {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .pending-user-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 12px 4px 6px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: #fafbfa;
        }
        .pending-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #eaf2e8;
            border: 1.5px solid var(--forest);
            display: grid;
            place-items: center;
            overflow: hidden;
            flex-shrink: 0;
            font-size: 12px;
            font-weight: 700;
            color: var(--forest);
        }
        .pending-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .pending-user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        .pending-user-info strong {
            font-size: 12px;
            color: var(--ink);
            max-width: 180px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .pending-user-info small {
            font-size: 10px;
            color: var(--muted);
        }
        .approval-notice-card {
            padding: 24px 28px;
            border-radius: 12px;
            display: flex;
            align-items: flex-start;
            gap: 18px;
            background: #fbf6ec;
            border: 1px solid #fae2b8;
        }
        .approval-notice-card.rejected {
            background: #fef4f2;
            border-color: #fbd6d0;
        }
        .approval-notice-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #f7e6c4;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            color: var(--amber);
        }
        .approval-notice-card.rejected .approval-notice-icon {
            background: #fedad4;
            color: var(--red);
        }
        .approval-notice-body h2 {
            margin: 0 0 6px;
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--ink);
        }
        .approval-notice-body p {
            margin: 0;
            color: #4f5d54;
            font-size: 13px;
            line-height: 1.6;
        }
        @media (max-width: 768px) {
            .pending-header {
                padding: 16px 20px;
                flex-direction: column;
                align-items: stretch;
                gap: 14px;
            }
            .pending-account {
                justify-content: space-between;
                flex-wrap: wrap;
            }
            .approval-notice-card {
                padding: 18px 20px;
            }
        }
    </style>
</head>
<body class="verification-page">

    <!-- Standalone Pending Approval Header (No Dashboard Navbar / No Sidebar) -->
    <header class="pending-header" role="banner">
        <a class="pending-brand" href="/" aria-label="EcoCycle Home">
            <img src="/images/figma/ecocycle-leaf.svg" alt="EcoCycle Leaf">
            <span>EcoCycle</span>
        </a>

        <div class="pending-account">
            <div class="pending-user-card" title="Registered Business Entity">
                <div class="pending-avatar">
                    <?php if ($business->profile_image) { ?>
                        <img src="<?= e($business->profile_image_url) ?>" alt="<?= e($business->business_name) ?>">
                    <?php } else { ?>
                        <span><?= strtoupper(substr($business->business_name ?: $business->name, 0, 2)) ?></span>
                    <?php } ?>
                </div>
                <div class="pending-user-info">
                    <strong><?= e($business->business_name ?: $business->name) ?></strong>
                    <small><?= e($business->name) ?></small>
                </div>
                <span class="status-pill <?= e($statusClass) ?>" style="margin-left:4px;"><?= e($statusLabel) ?></span>
            </div>

            <a href="<?= route('business.pending') ?>" class="outline-button" title="Check if administrator has approved your application">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
                <span>Check approval status</span>
            </a>

            <form method="POST" action="<?= route('logout') ?>" style="margin:0;">
                <?= csrf_field() ?>
                <button type="submit" class="outline-button" title="Sign out of EcoCycle">
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

    <!-- Main Pending Approval Content -->
    <main class="verification-content" role="main">

        <?php if (session('status')) { ?>
            <div class="submission-notice" role="status" aria-live="polite">
                <div style="display:flex;align-items:center;gap:10px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <span><?= e(session('status')) ?></span>
                </div>
            </div>
        <?php } ?>

        <?php if ($hasFormErrors) { ?>
            <div class="submission-error" role="alert">
                <strong>Please correct the highlighted fields in the form below.</strong>
            </div>
        <?php } ?>

        <!-- Clear Status & Information Notice -->
        <div class="approval-notice-card <?= $isRejected ? 'rejected' : '' ?>">
            <div class="approval-notice-icon">
                <?php if ($isRejected) { ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                <?php } else { ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                <?php } ?>
            </div>
            <div class="approval-notice-body">
                <h2>
                    <?php if ($isRejected) { ?>
                        Action Required: Changes Requested by Administrator
                    <?php } else { ?>
                        Registration Submitted &mdash; Waiting for Admin Approval
                    <?php } ?>
                </h2>
                <p>
                    <?php if ($isRejected) { ?>
                        The administrator reviewed your application and requested revisions before approving your business account. Please review the feedback notes below, update your details or re-upload your SSM documents, and click <strong>"Save &amp; Resubmit Changes"</strong>.
                    <?php } else { ?>
                        Thank you for registering <strong><?= e($business->business_name) ?></strong> on EcoCycle. Your registration details and SSM verification documents have been received safely and are currently in the administrator review queue. Once an administrator approves your account, you will automatically have access to the full business dashboard, marketplace, and inventory management tools. We will notify you at <strong><?= e($business->email) ?></strong> once a decision is made.
                    <?php } ?>
                </p>
            </div>
        </div>

        <?php if ($isRejected && $business->review_note) { ?>
            <!-- Administrator Feedback Card -->
            <section class="review-card" style="border-left:4px solid var(--red);background:#fef7f6;padding:20px;display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="/images/figma/verification/circle-alert.svg" alt="" style="width:20px;height:20px;">
                        <strong style="color:var(--red);font-size:15px;">Administrator Feedback &mdash; What you need to fix:</strong>
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
        <?php } ?>

        <!-- Progress Timeline & Submitted Documents Grid -->
        <div class="review-grid">
            <section class="review-card progress-card">
                <h2>Verification progress</h2>
                <ol class="timeline">
                    <li class="timeline-done">
                        <img src="/images/figma/verification/circle-check.svg" alt="">
                        <span>
                            <strong>Account registered</strong>
                            <small><?= $submittedAt?->format('j M Y · g:i A') ?? 'Submitted' ?></small>
                        </span>
                    </li>
                    <li class="timeline-done">
                        <img src="/images/figma/verification/circle-check.svg" alt="">
                        <span>
                            <strong>Documents submitted</strong>
                            <small>SSM Certificate &amp; Profile recorded</small>
                        </span>
                    </li>
                    <li class="<?= $isApproved || $isRejected ? 'timeline-done' : 'timeline-current' ?>">
                        <img src="/images/figma/verification/<?= $isApproved ? 'circle-check.svg' : ($isRejected ? 'circle-alert.svg' : 'clock-pending.svg') ?>" alt="">
                        <span>
                            <strong>Administrator review</strong>
                            <small>
                                <?php if ($isApproved) { ?>
                                    Approved by platform administrator
                                <?php } elseif ($isRejected) { ?>
                                    Application declined &mdash; updates requested
                                <?php } else { ?>
                                    In queue &middot; Currently under review
                                <?php } ?>
                            </small>
                        </span>
                    </li>
                    <li class="<?= $isApproved ? 'timeline-done' : 'timeline-waiting' ?>">
                        <img src="/images/figma/verification/<?= $isApproved ? 'circle-check.svg' : 'clock-pending.svg' ?>" alt="">
                        <span>
                            <strong>Business dashboard &amp; marketplace access</strong>
                            <small><?= $isApproved ? 'Access active' : 'Unlocked automatically upon administrator approval' ?></small>
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
                    <?php if ($business->ssm_certificate_path) { ?>
                        <a class="outline-button download-link" href="<?= e(route('business.application.document', 'certificate')) ?>">Download</a>
                    <?php } ?>
                </div>

                <div class="document-row">
                    <img src="/images/figma/verification/file-check.svg" alt="">
                    <span>
                        <strong>SSM company / business profile</strong>
                        <small>Full profile including owners, directors and activities</small>
                        <em>✓ Uploaded</em>
                    </span>
                    <?php if ($business->ssm_profile_path) { ?>
                        <a class="outline-button download-link" href="<?= e(route('business.application.document', 'profile')) ?>">Download</a>
                    <?php } ?>
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
                    <?php if ($business->profile_image) { ?>
                        <img src="<?= e($business->profile_image_url) ?>" alt="<?= e($business->business_name) ?>" style="width:100%;height:100%;object-fit:cover;">
                    <?php } else { ?>
                        <span style="font-size:22px;font-weight:700;color:#185a36;letter-spacing:0.02em;"><?= strtoupper(substr($business->business_name ?: $business->name, 0, 2)) ?></span>
                    <?php } ?>
                </div>
                <div>
                    <h3 style="margin:0 0 4px;font-size:18px;font-weight:700;color:#123321;"><?= e($business->business_name) ?></h3>
                    <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#176b40;font-weight:600;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Registered Malaysian SME &middot; <?= e($business->industry) ?>
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
                <div>
                    <dt>Contact person</dt>
                    <dd><?= e($business->name) ?></dd>
                </div>
                <div>
                    <dt>State</dt>
                    <dd><?= e($business->state) ?></dd>
                </div>
                <div>
                    <dt>City &amp; Postcode</dt>
                    <dd><?= e($business->city) ?>, <?= e($business->postcode) ?></dd>
                </div>
                <div class="full-width">
                    <dt>Registered address</dt>
                    <dd><?= e($business->registered_address) ?></dd>
                </div>
            </dl>
        </section>

        <!-- Modify / Update Application Form -->
        <section class="review-card modify-card <?= $hasFormErrors ? 'form-visible' : '' ?>" id="modify-application">
            <div class="section-card-header">
                <div>
                    <h2>Modify application &amp; replace documents</h2>
                    <p>Update your registration details or replace uploaded SSM documents for administrator review.</p>
                </div>
                <button type="button" class="outline-button" onclick="toggleEditForm(false)">Close form</button>
            </div>

            <form class="edit-form" method="POST" action="<?= e(route('business.application.update')) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="field-grid">
                    <div class="form-field full-width">
                        <label for="edit_business_name">Registered business name <span style="color:var(--red);">*</span></label>
                        <input id="edit_business_name" name="business_name" type="text" value="<?= e(old('business_name', $business->business_name)) ?>" required>
                        <?php if ($errors->has('business_name')) { ?>
                            <small class="field-error"><?= e($errors->first('business_name')) ?></small>
                        <?php } ?>
                    </div>

                    <div class="form-field">
                        <label for="edit_business_registration_number">SSM registration number <span style="color:var(--red);">*</span></label>
                        <input id="edit_business_registration_number" name="business_registration_number" type="text" value="<?= e(old('business_registration_number', $business->business_registration_number)) ?>" required>
                        <?php if ($errors->has('business_registration_number')) { ?>
                            <small class="field-error"><?= e($errors->first('business_registration_number')) ?></small>
                        <?php } ?>
                    </div>

                    <div class="form-field">
                        <label for="edit_industry">Industry <span style="color:var(--red);">*</span></label>
                        <select id="edit_industry" name="industry" required>
                            <?php foreach ($industries as $ind) { ?>
                                <option value="<?= e($ind) ?>" <?= old('industry', $business->industry) === $ind ? 'selected' : '' ?>><?= e($ind) ?></option>
                            <?php } ?>
                        </select>
                        <?php if ($errors->has('industry')) { ?>
                            <small class="field-error"><?= e($errors->first('industry')) ?></small>
                        <?php } ?>
                    </div>

                    <div class="form-field">
                        <label for="edit_business_phone">Business phone number <span style="color:var(--red);">*</span></label>
                        <input id="edit_business_phone" name="business_phone" type="tel" value="<?= e(old('business_phone', $business->business_phone)) ?>" required>
                        <?php if ($errors->has('business_phone')) { ?>
                            <small class="field-error"><?= e($errors->first('business_phone')) ?></small>
                        <?php } ?>
                    </div>

                    <div class="form-field">
                        <label for="edit_name">Contact person name <span style="color:var(--red);">*</span></label>
                        <input id="edit_name" name="name" type="text" value="<?= e(old('name', $business->name)) ?>" required>
                        <?php if ($errors->has('name')) { ?>
                            <small class="field-error"><?= e($errors->first('name')) ?></small>
                        <?php } ?>
                    </div>

                    <div class="form-field full-width">
                        <label for="edit_registered_address">Registered address <span style="color:var(--red);">*</span></label>
                        <input id="edit_registered_address" name="registered_address" type="text" value="<?= e(old('registered_address', $business->registered_address)) ?>" required>
                        <?php if ($errors->has('registered_address')) { ?>
                            <small class="field-error"><?= e($errors->first('registered_address')) ?></small>
                        <?php } ?>
                    </div>

                    <div class="form-field">
                        <label for="edit_city">City <span style="color:var(--red);">*</span></label>
                        <input id="edit_city" name="city" type="text" value="<?= e(old('city', $business->city)) ?>" required>
                        <?php if ($errors->has('city')) { ?>
                            <small class="field-error"><?= e($errors->first('city')) ?></small>
                        <?php } ?>
                    </div>

                    <div class="form-field">
                        <label for="edit_postcode">Postcode <span style="color:var(--red);">*</span></label>
                        <input id="edit_postcode" name="postcode" type="text" value="<?= e(old('postcode', $business->postcode)) ?>" required>
                        <?php if ($errors->has('postcode')) { ?>
                            <small class="field-error"><?= e($errors->first('postcode')) ?></small>
                        <?php } ?>
                    </div>

                    <div class="form-field full-width">
                        <label for="edit_state">State <span style="color:var(--red);">*</span></label>
                        <select id="edit_state" name="state" required>
                            <?php foreach ($states as $st) { ?>
                                <option value="<?= e($st) ?>" <?= old('state', $business->state) === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                            <?php } ?>
                        </select>
                        <?php if ($errors->has('state')) { ?>
                            <small class="field-error"><?= e($errors->first('state')) ?></small>
                        <?php } ?>
                    </div>
                </div>

                <div class="documents-upload-grid">
                    <div class="doc-upload-box">
                        <label for="edit_ssm_certificate">
                            <strong>Replace SSM registration certificate</strong>
                            <small>Optional &mdash; upload a new PDF, JPG, PNG, or WEBP (up to 10 MB) to replace current file.</small>
                        </label>
                        <input id="edit_ssm_certificate" name="ssm_certificate" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
                        <?php if ($errors->has('ssm_certificate')) { ?>
                            <small class="field-error"><?= e($errors->first('ssm_certificate')) ?></small>
                        <?php } ?>
                    </div>

                    <div class="doc-upload-box">
                        <label for="edit_ssm_profile">
                            <strong>Replace SSM company / business profile</strong>
                            <small>Optional &mdash; upload a new PDF, JPG, PNG, or WEBP (up to 10 MB) to replace current file.</small>
                        </label>
                        <input id="edit_ssm_profile" name="ssm_profile" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
                        <?php if ($errors->has('ssm_profile')) { ?>
                            <small class="field-error"><?= e($errors->first('ssm_profile')) ?></small>
                        <?php } ?>
                    </div>
                </div>

                <div class="form-footer">
                    <p class="muted-note">Submitting will update your registration details and alert the administrator for review.</p>
                    <div class="footer-buttons">
                        <button type="button" class="outline-button" onclick="toggleEditForm(false)">Cancel</button>
                        <button type="submit" class="action-button-primary">Save &amp; Resubmit Changes</button>
                    </div>
                </div>
            </form>
        </section>

        <?php if ($isRejected) { ?>
            <!-- Restart Application Option -->
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
        <?php } ?>

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
