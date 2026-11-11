<?php
$errors = $errors ?? new \Illuminate\Support\MessageBag();
$passwordError = $errors->first('password');
$emailError = $errors->first('email');
$token = $token ?? '';
$email = $email ?? '';
$broker = $broker ?? 'business';
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Set a new password for your EcoCycle account.">
    <title>Set new password - EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/auth/login.css">
</head>
<body class="signin-page signin-login">
    <main class="signin-shell">
        <section class="signin-content" aria-labelledby="signin-heading">
            <a class="signin-brand" href="/" aria-label="EcoCycle home">
                <span class="signin-brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                <span>EcoCycle</span>
            </a>

            <header class="signin-welcome">
                <p class="signin-eyebrow">ACCOUNT RECOVERY</p>
                <h1 id="signin-heading">Set new password</h1>
                <p>Choose a secure password with at least 8 characters and a number.</p>
            </header>

            <?php if (session('status')): ?>
                <div style="padding:12px 16px;border-radius:7px;background:#eaf3e8;border:1px solid #c7dbc5;color:#285c44;font-size:12px;font-weight:600;margin-bottom:16px;" role="status">
                    <?= e(session('status')) ?>
                </div>
            <?php endif; ?>

            <?php if ($errors->any()): ?>
                <div class="signin-error-summary" role="alert" aria-live="assertive">
                    <strong>Please review your new password:</strong>
                    <span><?= e($passwordError ?: $emailError ?: $errors->first()) ?></span>
                </div>
            <?php endif; ?>

            <form class="signin-form" method="POST" action="<?= e(route('password.update')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <input type="hidden" name="email" value="<?= e(old('email', $email)) ?>">
                <input type="hidden" name="broker" value="<?= e($broker) ?>">

                <div class="signin-field">
                    <label for="password">New password</label>
                    <input id="password" name="password" type="password" placeholder="e.g. At least 8 characters with a number" minlength="8" autocomplete="new-password" required aria-invalid="<?= $passwordError ? 'true' : 'false' ?>">
                    <small style="color:var(--signin-muted);font-size:11px;">At least 8 characters, including a number.</small>
                    <?php if ($passwordError): ?><small class="signin-field-error"><?= e($passwordError) ?></small><?php endif; ?>
                </div>

                <div class="signin-field">
                    <label for="password_confirmation">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" placeholder="e.g. Re-enter your new password" minlength="8" autocomplete="new-password" required>
                </div>

                <button class="signin-submit" type="submit">
                    <img src="/images/figma/signup/arrow-right.svg" alt="">
                    <span>Save new password</span>
                </button>
            </form>

            <p class="signin-register-link"><a href="<?= e(route('login')) ?>"><span aria-hidden="true">&#8592;</span> Back to sign in</a></p>
        </section>
    </main>
<script src="/js/language-switcher.js" defer></script>
</body>
</html>
