<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Business review - EcoCycle</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="/css/admin/review.css"></head>
<body class="admin-page">
<header class="admin-header"><a class="admin-brand" href="/"><img src="/images/figma/verification/brand-mark.svg" alt=""><span>EcoCycle</span></a><div class="admin-header-actions"><span>Administrator · <?= e(auth('admin')->user()->name) ?></span><form method="POST" action="<?= e(route('logout')) ?>"><?= csrf_field() ?><button class="admin-outline" type="submit">Sign out</button></form></div></header>
<main class="admin-content">
    <div class="admin-title"><div><p class="admin-eyebrow">PLATFORM ADMINISTRATION</p><h1>Business applications</h1><p class="admin-muted">Review submitted details and documents before enabling business access.</p></div></div>
    <?php if (session('status')): ?><div class="admin-success" role="status"><?= e(session('status')) ?></div><?php endif; ?>
    <?php if ($errors->any()): ?><div class="admin-alert" role="alert"><?= e($errors->first()) ?></div><?php endif; ?>
    <section class="admin-stats" aria-label="Application totals"><article><span>Awaiting review</span><strong><?= $pendingCount ?></strong></article><article><span>Approved</span><strong><?= $approvedCount ?></strong></article><article><span>Changes requested</span><strong><?= $rejectedCount ?></strong></article></section>
    <section class="application-list"><h2>Submitted applications <span><?= $businesses->count() ?></span></h2>
        <?php if ($businesses->isEmpty()): ?><p class="admin-empty">No business applications have been submitted yet.</p><?php endif; ?>
        <?php foreach ($businesses as $business): ?>
        <article class="application-card">
            <div class="application-heading"><div><h3><?= e($business->business_name) ?></h3><p><?= e($business->name) ?> · <?= e($business->email) ?></p></div><span class="application-status status-<?= e($business->account_status) ?>"><?= e(ucfirst($business->account_status)) ?></span></div>
            <dl class="business-details"><div><dt>SSM number</dt><dd><?= e($business->business_registration_number) ?></dd></div><div><dt>Industry</dt><dd><?= e($business->industry) ?></dd></div><div><dt>Phone</dt><dd><?= e($business->business_phone) ?></dd></div><div><dt>Registered address</dt><dd><?= e(implode(', ', array_filter([$business->registered_address, $business->postcode, $business->city, $business->state]))) ?></dd></div><div><dt>Submitted</dt><dd><?= e($business->created_at?->timezone('Asia/Kuala_Lumpur')->format('j M Y, g:i A').' MYT') ?></dd></div></dl>
            <div class="document-links"><a href="<?= e(route('admin.business.document', [$business, 'certificate'])) ?>"><img src="/images/figma/verification/file-check.svg" alt="">SSM registration certificate</a><a href="<?= e(route('admin.business.document', [$business, 'profile'])) ?>"><img src="/images/figma/verification/file-check.svg" alt="">SSM company profile</a></div>
            <?php if ($business->review_note): ?><div class="review-note"><strong>Review note</strong><p><?= e($business->review_note) ?></p></div><?php endif; ?>
            <?php if ($business->account_status !== 'approved'): ?>
            <div class="application-actions"><form method="POST" action="<?= e(route('admin.business.approve', $business)) ?>"><?= csrf_field() ?><button class="admin-primary" type="submit">Approve business</button></form>
                <form class="reject-form" method="POST" action="<?= e(route('admin.business.reject', $business)) ?>"><?= csrf_field() ?><label class="visually-hidden" for="review-note-<?= $business->id ?>">Reason for changes</label><input id="review-note-<?= $business->id ?>" name="review_note" value="<?= e(old('review_note')) ?>" placeholder="Explain what needs to change" required maxlength="1000"><button class="admin-danger" type="submit">Request changes</button></form></div>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </section>
</main>
</body></html>
