<?php

use Illuminate\Support\ViewErrorBag;

/** @var ViewErrorBag $errors */
$errors = $errors ?? session('errors', new ViewErrorBag);
$emailError = $errors->first('identifier');
$passwordError = $errors->first('password');
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sign in to your EcoCycle business workspace.">
    <title>Sign in - EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/auth/login.css">

</head>
<body class="signin-page signin-login">
    <main class="signin-shell">
        <a class="signin-back" href="/"><span aria-hidden="true">&#8592;</span> Back to home</a>
        <section class="signin-content" aria-labelledby="signin-heading">
            <a class="signin-brand" href="/" aria-label="EcoCycle home">
                <span class="signin-brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                <span>EcoCycle</span>
            </a>

                <header class="signin-welcome">
                    <p class="signin-eyebrow">BUSINESS</p>
                    <h1 id="signin-heading">Welcome back.</h1>
                    <p>Sign in to keep your materials and operations moving.</p>
                </header>

                <?php if (session('status')) { ?>
                    <div style="padding:12px 16px;border-radius:7px;background:#eaf3e8;border:1px solid #c7dbc5;color:#285c44;font-size:12px;font-weight:600;margin-bottom:16px;" role="status">
                        <?= e(session('status')) ?>
                    </div>
                <?php } ?>

                <?php if (session('error')) { ?>
                    <div class="signin-error-summary" role="alert" aria-live="assertive" style="background:#f9eae6;border-color:#e0b2ac;margin-bottom:16px;">
                        <strong style="color:#a0483e;">Account Not Approved</strong>
                        <span style="color:#a0483e;"><?= e(session('error')) ?></span>
                    </div>
                <?php } ?>

                <?php if ($errors->any()) { ?>
                    <div class="signin-error-summary" role="alert" aria-live="assertive">
                        <strong>We couldn’t sign you in.</strong>
                        <span><?= e($emailError ?: ($passwordError ?: $errors->first())) ?></span>
                    </div>
                <?php } ?>

                <form class="signin-form" method="POST" action="/login">
                    <?= csrf_field() ?>
                    <div class="signin-field">
                        <label for="identifier">Email or Username</label>
                        <input id="identifier" name="identifier" type="text" value="<?= e(old('identifier')) ?>" placeholder="e.g. owner@business.com or company username" autocomplete="username" maxlength="255" required aria-invalid="<?= $emailError ? 'true' : 'false' ?>" <?= $emailError ? 'aria-describedby="identifier-error"' : '' ?>>
                        <?php if ($emailError) { ?><small class="signin-field-error" id="identifier-error"><?= e($emailError) ?></small><?php } ?>
                    </div>

                    <div class="signin-field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" placeholder="e.g. Enter your password" autocomplete="current-password" required aria-invalid="<?= $passwordError ? 'true' : 'false' ?>" <?= $passwordError ? 'aria-describedby="password-error"' : '' ?>>
                        <?php if ($passwordError) { ?><small class="signin-field-error" id="password-error"><?= e($passwordError) ?></small><?php } ?>
                    </div>

                    <div class="signin-options">
                        <a href="<?= e(route('password.request')) ?>">Forgot password?</a>
                    </div>

                    <button class="signin-submit" type="submit">
                        Sign In
                    </button>
                </form>

                <p class="signin-register-link">New to EcoCycle? <a href="/register">Register your business <span aria-hidden="true">&#8594;</span></a></p>
        </section>
    </main>
<script src="/js/language-switcher.js" defer></script>
</body>
</html>
