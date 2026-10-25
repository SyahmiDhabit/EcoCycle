<?php /** @var \App\Models\User $business */ ?>
<!doctype html>
<html lang="en"><body style="margin:0;background:#f6f7f3;color:#22352c;font:14px/1.6 Arial,sans-serif;padding:32px">
<main style="max-width:640px;margin:0 auto;background:#fff;border:1px solid #dde4d9;border-radius:12px;padding:28px">
    <p style="color:#285c44;font-size:12px;font-weight:bold;letter-spacing:.04em">ECOCYCLE · BUSINESS REVIEW</p>
    <h1 style="font-size:24px">New business application</h1>
    <p>A business has submitted an application and is waiting for administrator review.</p>
    <table style="border-collapse:collapse;width:100%"><tbody>
        <tr><th align="left" style="padding:7px 0">Business</th><td><?= e($business->business_name) ?></td></tr>
        <tr><th align="left" style="padding:7px 0">Contact</th><td><?= e($business->name) ?></td></tr>
        <tr><th align="left" style="padding:7px 0">Email</th><td><?= e($business->email) ?></td></tr>
        <tr><th align="left" style="padding:7px 0">SSM number</th><td><?= e($business->business_registration_number) ?></td></tr>
        <tr><th align="left" style="padding:7px 0">Submitted</th><td><?= e($business->created_at?->format('j M Y, g:i A')) ?></td></tr>
    </tbody></table>
    <p><a href="<?= e(route('admin.dashboard')) ?>" style="display:inline-block;margin-top:16px;padding:11px 16px;border-radius:7px;background:#285c44;color:#fff;text-decoration:none">Open administrator review</a></p>
</main></body></html>
