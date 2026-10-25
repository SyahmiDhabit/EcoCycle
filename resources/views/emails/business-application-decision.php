<!doctype html>
<html lang="en"><body style="margin:0;background:#f6f7f3;color:#22352c;font:14px/1.6 Arial,sans-serif;padding:32px">
<main style="max-width:640px;margin:0 auto;background:#fff;border:1px solid #dde4d9;border-radius:12px;padding:28px">
    <p style="color:#285c44;font-size:12px;font-weight:bold;letter-spacing:.04em">ECOCYCLE · BUSINESS VERIFICATION</p>
    <?php if ($decision === 'approved'): ?>
        <h1 style="font-size:24px">Your business is approved</h1>
        <p>Hello <?= e($business->name) ?>, <?= e($business->business_name) ?> has been approved for EcoCycle. Sign in to access your business workspace.</p>
        <p><a href="<?= e(route('login')) ?>" style="display:inline-block;margin-top:16px;padding:11px 16px;border-radius:7px;background:#285c44;color:#fff;text-decoration:none">Sign in to EcoCycle</a></p>
    <?php else: ?>
        <h1 style="font-size:24px">Please update your application</h1>
        <p>Hello <?= e($business->name) ?>, an administrator reviewed <?= e($business->business_name) ?> and requested an update before approval.</p>
        <p><strong>Review note:</strong> <?= e($business->review_note) ?></p>
        <p>Sign in to upload the requested document and resubmit the application.</p>
        <p><a href="<?= e(route('login')) ?>" style="display:inline-block;margin-top:16px;padding:11px 16px;border-radius:7px;background:#285c44;color:#fff;text-decoration:none">View your application</a></p>
    <?php endif; ?>
</main></body></html>
