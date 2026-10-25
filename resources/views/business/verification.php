<?php
$isRejected = $business->account_status === 'rejected';
$submittedAt = $business->created_at?->timezone('Asia/Kuala_Lumpur');
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Track your EcoCycle business verification application.">
    <title>Business verification - EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/business/verification.css">

</head>
<body class="verification-page">
<header class="verification-header">
    <a class="verification-brand" href="/" aria-label="EcoCycle home"><img src="/images/figma/verification/brand-mark.svg" alt=""><span>EcoCycle</span></a>
    <div class="verification-account">
        <span class="status-pill <?= $isRejected ? 'status-rejected' : 'status-pending' ?>"><?= $isRejected ? 'Changes required' : 'Verification pending' ?></span>
        <span><?= e($business->name) ?></span><span> · Business Owner</span>

        <form method="POST" action="/logout"><?= csrf_field() ?><button class="outline-button" type="submit">Sign out</button></form>
    </div>
</header>
<main class="verification-content">
    <section class="verification-intro">
        <p class="application-id">BUSINESS APPLICATION #<?= e(str_pad((string) $business->id, 6, '0', STR_PAD_LEFT)) ?></p>
        <h1><?= $isRejected ? 'Your application needs changes.' : 'Your business is under review.' ?></h1>
        <p>Thank you, <?= e($business->name) ?>. Your application was submitted<?= $submittedAt ? ' on '.$submittedAt->format('j M Y').' at '.$submittedAt->format('g:i A').' MYT' : '' ?>.</p>
    </section>
    <?php if (session('status')): ?><div class="submission-notice" role="status"><?= e(session('status')) ?></div><?php endif; ?>
    <aside class="guidance <?= $isRejected ? 'guidance-rejected' : '' ?>">
        <img src="/images/figma/verification/<?= $isRejected ? 'circle-alert.svg' : 'clock.svg' ?>" alt="">
        <div><strong><?= $isRejected ? 'Please update your application' : 'Marketplace &amp; transactions are locked until approval' ?></strong>
            <p><?= $isRejected ? e($business->review_note ?: 'Please contact support for the changes needed to your application.') : 'You cannot browse listings, publish materials, place requests or access transactions yet. We’ll email '.e($business->email).' when an Administrator makes a decision.' ?></p>
        </div>
    </aside>
    <div class="review-grid">
        <section class="review-card progress-card">
            <h2>Verification progress</h2>
            <ol class="timeline">
                <li class="timeline-done"><img src="/images/figma/verification/circle-check.svg" alt=""><span><strong>Account created</strong><small><?= $submittedAt?->format('j M Y · g:i A') ?? 'Completed' ?></small></span></li>
                <li class="timeline-done"><img src="/images/figma/verification/circle-check.svg" alt=""><span><strong>Documents submitted</strong><small>2 files received</small></span></li>
                <li class="<?= $isRejected ? 'timeline-done' : 'timeline-waiting' ?>"><img src="/images/figma/verification/<?= $isRejected ? 'circle-alert.svg' : 'clock-pending.svg' ?>" alt=""><span><strong>Administrator review</strong><small><?= $isRejected ? 'Changes requested' : 'In queue · no action needed from you' ?></small></span></li>
                <li class="timeline-waiting"><img src="/images/figma/verification/clock-pending.svg" alt=""><span><strong>Decision &amp; marketplace access</strong><small>Access is enabled only if your application is approved.</small></span></li>
            </ol>
            <p class="detail-line">Registered business <strong><?= e($business->business_name) ?></strong></p>
            <p class="detail-line">SSM number <strong><?= e($business->business_registration_number) ?></strong></p>
            <a class="outline-button inline-button" href="#submitted-documents">View submitted application</a>
        </section>
        <section class="review-card documents-card" id="submitted-documents">
            <h2>Your submitted documents</h2>
            <div class="document-row"><img src="/images/figma/verification/file-check.svg" alt=""><span><strong>SSM registration certificate</strong><small>Uploaded for administrator review</small><em>✓ Uploaded</em></span></div>
            <div class="document-row"><img src="/images/figma/verification/file-check.svg" alt=""><span><strong>SSM company profile</strong><small>Uploaded for administrator review</small><em>✓ Uploaded</em></span></div>
            <p class="document-note">Your documents are stored privately and can only be viewed by an administrator.</p>
            <a class="outline-button inline-button" href="mailto:support@ecocycle.my"><img src="/images/figma/verification/mail.svg" alt=""> Contact support</a>
        </section>
    </div>
    <?php if ($isRejected): ?>
    <section class="review-card resubmission-card">
        <div class="resubmission-heading"><div><h2>If your application needs changes</h2><p>Update the requested document and submit it for another review.</p></div><span class="status-pill status-rejected">Changes required</span></div>
        <div class="resubmission-content"><div><div class="review-reason"><img src="/images/figma/verification/circle-alert.svg" alt=""><span><strong>Reviewer’s reason</strong><small><?= e($business->review_note ?: 'The administrator requested an updated SSM company profile.') ?></small></span></div><p class="resubmission-note">Marketplace and transaction access remain locked. Resubmission does not automatically grant approval.</p></div>
            <form class="resubmission-form" method="POST" action="<?= e(route('business.resubmit')) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <label class="replacement-upload" for="replacement-profile"><img src="/images/figma/verification/upload.svg" alt=""><span><strong>Replace SSM company profile</strong><small>Choose a file to upload</small><small>PDF, JPG or PNG · up to 10 MB</small><?php if ($errors->has('ssm_profile')): ?><em><?= e($errors->first('ssm_profile')) ?></em><?php endif; ?></span></label>
                <input class="replacement-input" id="replacement-profile" name="ssm_profile" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                <button class="resubmit-button" type="submit"><img src="/images/figma/verification/refresh.svg" alt="">Resubmit for verification</button>
            </form>
        </div>
    </section>
    <?php endif; ?>
    <p class="document-trust"><img src="/images/figma/verification/lock.svg" alt="">Business documents are visible only to authorised Administrators for verification.</p>
</main>
<script src="/js/language-switcher.js" defer></script>
</body>
</html>
