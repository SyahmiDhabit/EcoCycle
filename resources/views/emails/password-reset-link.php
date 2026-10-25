<!doctype html>
<html lang="<?= $language === 'ms' ? 'ms' : 'en' ?>"><body style="margin:0;background:#f6f7f3;color:#22352c;font:14px/1.6 Arial,sans-serif;padding:32px">
<main style="max-width:640px;margin:0 auto;background:#fff;border:1px solid #dde4d9;border-radius:12px;padding:28px">
    <?php if ($language === 'ms'): ?>
        <p style="color:#285c44;font-weight:bold">ECOCYCLE · PEMULIHAN AKAUN</p><h1 style="font-size:24px">Tetapkan semula kata laluan</h1><p>Hai <?= e($recipientName) ?>, klik butang di bawah untuk memilih kata laluan baharu. Pautan ini sah selama 60 minit.</p><p><a href="<?= e($resetUrl) ?>" style="display:inline-block;margin-top:16px;padding:11px 16px;border-radius:7px;background:#285c44;color:#fff;text-decoration:none">Tetapkan semula kata laluan</a></p><p>Jika anda tidak meminta pautan ini, abaikan emel ini.</p>
    <?php else: ?>
        <p style="color:#285c44;font-weight:bold">ECOCYCLE · ACCOUNT RECOVERY</p><h1 style="font-size:24px">Reset your password</h1><p>Hello <?= e($recipientName) ?>, use the button below to choose a new password. This link expires in 60 minutes.</p><p><a href="<?= e($resetUrl) ?>" style="display:inline-block;margin-top:16px;padding:11px 16px;border-radius:7px;background:#285c44;color:#fff;text-decoration:none">Reset password</a></p><p>If you did not request this link, you can ignore this email.</p>
    <?php endif; ?>
</main></body></html>
