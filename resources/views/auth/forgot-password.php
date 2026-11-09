<?php
$errors = $errors ?? new \Illuminate\Support\MessageBag();
$emailError = $errors->first('email');
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Reset your EcoCycle password.">
    <title>Forgot password - EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                <h1 id="signin-heading">Forgot password?</h1>
                <p>Enter your registered account email and we'll send you a link to reset your password.</p>
            </header>

            <?php if (session('status')): ?>
                <div style="padding:12px 16px;border-radius:7px;background:#eaf3e8;border:1px solid #c7dbc5;color:#285c44;font-size:12px;font-weight:600;margin-bottom:16px;" role="status">
                    <?= e(session('status')) ?>
                </div>
            <?php endif; ?>

            <?php if ($errors->any()): ?>
                <div class="signin-error-summary" role="alert" aria-live="assertive">
                    <strong>We couldn't process your request.</strong>
                    <span><?= e($emailError ?: $errors->first()) ?></span>
                </div>
            <?php endif; ?>

            <form class="signin-form" method="POST" action="<?= e(route('password.email')) ?>">
                <?= csrf_field() ?>
                <div class="signin-field">
                    <label for="email">Account email</label>
                    <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" placeholder="e.g. owner@business.com" autocomplete="email" required aria-invalid="<?= $emailError ? 'true' : 'false' ?>" <?= $emailError ? 'aria-describedby="email-error"' : '' ?>>
                    <?php if ($emailError): ?><small class="signin-field-error" id="email-error"><?= e($emailError) ?></small><?php endif; ?>
                </div>

                <button class="signin-submit" type="submit">
                    <img src="/images/figma/signup/arrow-right.svg" alt="">
                    <span>Send reset link</span>
                </button>
            </form>

            <p class="signin-register-link"><a href="<?= e(route('login')) ?>"><span aria-hidden="true">&#8592;</span> Back to sign in</a></p>
            <p class="signin-access-note">Remember your password? Sign in with your business email or company username.</p>
        </section>
    </main>
<script src="/js/language-switcher.js" defer></script>
</body>
</html>
