<?php /** @var \App\Models\User $business */ /** @var string $decision */ ?>
<!doctype html>
<html lang="en"><body style="margin:0;background:#f6f7f3;color:#22352c;font:14px/1.6 Arial,sans-serif;padding:32px">
<main style="max-width:640px;margin:0 auto;background:#fff;border:1px solid #dde4d9;border-radius:12px;padding:28px">
    <p style="color:#285c44;font-size:12px;font-weight:bold;letter-spacing:.04em">ECOCYCLE · BUSINESS VERIFICATION</p>
    <?php if ($decision === 'approved'): ?>
        <h1 style="font-size:24px">Your business is approved</h1>
        <p>Hello <?= e($business->email) ?>, <?= e($business->business_name) ?> has been approved for EcoCycle. Sign in to access your business workspace.</p>
        <p><a href="<?= e(route('login')) ?>" style="display:inline-block;margin-top:16px;padding:11px 16px;border-radius:7px;background:#285c44;color:#fff;text-decoration:none">Sign in to EcoCycle</a></p>
    <?php else: ?>
        <h1 style="font-size:24px;color:#a0483e;">Changes requested for your application</h1>
        <p>Hello <?= e($business->name) ?> (<?= e($business->business_name) ?>),</p>
        <p>An administrator reviewed your business registration and requested changes before your account can be approved.</p>
        <div style="background:#f9eae6;border:1px solid #e0b2ac;border-radius:8px;padding:14px 16px;margin:16px 0;">
            <strong style="color:#a0483e;">What you need to fix:</strong>
            <p style="margin:6px 0 0;color:#22352c;"><?= nl2br(e($business->review_note)) ?></p>
        </div>
        <p>Please sign in to your account to review the feedback, update your information or replace your SSM documents, and resubmit.</p>
        <p><a href="<?= e(route('login')) ?>" style="display:inline-block;margin-top:16px;padding:11px 20px;border-radius:7px;background:#285c44;color:#fff;text-decoration:none;font-weight:bold;">Sign in &amp; update application</a></p>
    <?php endif; ?>
</main></body></html>
