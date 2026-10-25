<?php
$errors = $errors ?? new \Illuminate\Support\MessageBag();
$emailError = $errors->first('identifier');
$passwordError = $errors->first('password');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sign in to your EcoCycle business workspace.">
    <title>Sign in - EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/auth/login.css">
</head>
<body class="signin-page">
    <main class="signin-shell">
        <aside class="signin-story" aria-label="EcoCycle business tools">
            <a class="signin-brand" href="/" aria-label="EcoCycle home">
                <span class="signin-brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span>
                <span>EcoCycle</span>
            </a>

            <div class="signin-promise">
                <h1>Less waste. More possibility.</h1>
                <p>Your everyday operations, connected to a more circular Malaysian business community.</p>
            </div>

            <div class="signin-workshop">
                <img class="signin-workshop-photo" src="/images/figma/signup/verification-hero.jpg" alt="Reusable materials organized in a business warehouse">
                <div class="signin-material-story">
                    <img src="/images/figma/signup/recycle.svg" alt="">
                    <span>A resource, not a leftover.<small>Better operations. More possibilities.</small></span>
                </div>
            </div>

            <div class="signin-benefits">
                <div><img src="/images/figma/signup/package.svg" alt=""><span>Track stock</span></div>
                <div><img src="/images/figma/signup/waste.svg" alt=""><span>Record waste</span></div>
                <div><img src="/images/figma/signup/exchange.svg" alt=""><span>Exchange surplus</span></div>
            </div>
            <p class="signin-tagline">SMART SME OPERATIONS &amp; WASTE-TO-VALUE EXCHANGE</p>
        </aside>

        <section class="signin-panel" aria-labelledby="signin-heading">
            <p class="signin-language">English <span>/</span> Bahasa Melayu</p>

            <div class="signin-content">
                <header class="signin-welcome">
                    <p class="signin-eyebrow">BUSINESS &amp; ADMIN ACCESS</p>
                    <h2 id="signin-heading">Welcome back.</h2>
                    <p>Sign in to keep your materials and operations moving.</p>
                </header>

                <?php if ($errors->any()): ?>
                    <div class="signin-error-summary" role="alert" aria-live="assertive">
                        <strong>We couldn’t sign you in.</strong>
                        <span><?= e($emailError ?: $passwordError) ?></span>
                    </div>
                <?php endif; ?>

                <form class="signin-form" method="POST" action="/login">
                    <?= csrf_field() ?>
                    <div class="signin-field">
                        <label for="identifier">Email</label>
                        <input id="identifier" name="identifier" type="text" value="<?= e(old('identifier')) ?>" placeholder="you@business.com" autocomplete="username" maxlength="255" required aria-invalid="<?= $emailError ? 'true' : 'false' ?>" <?= $emailError ? 'aria-describedby="identifier-error"' : '' ?>>
                        <?php if ($emailError): ?><small class="signin-field-error" id="identifier-error"><?= e($emailError) ?></small><?php endif; ?>
                    </div>

                    <div class="signin-field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required aria-invalid="<?= $passwordError ? 'true' : 'false' ?>" <?= $passwordError ? 'aria-describedby="password-error"' : '' ?>>
                        <?php if ($passwordError): ?><small class="signin-field-error" id="password-error"><?= e($passwordError) ?></small><?php endif; ?>
                    </div>

                    <div class="signin-options">
                        <label class="remember-option" for="remember"><input id="remember" name="remember" type="checkbox" value="1" <?= old('remember') ? 'checked' : '' ?>><span>Keep me signed in</span></label>
                        <a href="mailto:support@ecocycle.my?subject=EcoCycle%20password%20reset">Forgot password?</a>
                    </div>

                    <button class="signin-submit" type="submit">
                        <img src="/images/figma/signup/arrow-right.svg" alt="">
                        <span>Sign in to workspace</span>
                    </button>
                </form>

                <p class="signin-register-link">New to EcoCycle? <a href="/register">Register your business <span aria-hidden="true">&#8594;</span></a></p>
                <p class="signin-access-note">Sign in with your business email, or use your administrator username. Business access is available after approval.</p>
            </div>
        </section>
    </main>
</body>
</html>
