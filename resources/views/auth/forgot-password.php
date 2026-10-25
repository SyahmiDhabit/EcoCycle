<?php
$errors = $errors ?? new \Illuminate\Support\MessageBag();
?>
<!doctype html>
<html lang="<?= e(app()->getLocale()) ?>">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Forgot password - EcoCycle</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="/css/auth/login.css"></head>
<body class="signin-page">
<main class="signin-shell"><aside class="signin-story"><a class="signin-brand" href="/"><span class="signin-brand-mark"><img src="/images/figma/ecocycle-leaf.svg" alt=""></span><span>EcoCycle</span></a><div class="signin-promise"><h1>Reset your password.</h1><p>We’ll email you a secure link to choose a new one.</p></div><p class="signin-tagline">SMART SME OPERATIONS &amp; WASTE-TO-VALUE EXCHANGE</p></aside>
<section class="signin-panel"><div class="signin-content"><header class="signin-welcome"><p class="signin-eyebrow">ACCOUNT RECOVERY</p><h2>Forgot your password?</h2><p>Enter the email on your business or administrator account.</p></header>
<?php if (session('status')): ?><div class="submission-notice" role="status"><?= e(session('status')) ?></div><?php endif; ?>
<?php if ($errors->first('email')): ?><div class="signin-error-summary" role="alert"><?= e($errors->first('email')) ?></div><?php endif; ?>
<form class="signin-form" method="POST" action="<?= e(route('password.email')) ?>"><?= csrf_field() ?><div class="signin-field"><label for="email">Account email</label><input id="email" name="email" type="email" value="<?= e(old('email')) ?>" autocomplete="email" required></div><button class="signin-submit" type="submit"><span>Send reset link</span></button></form>
<p class="signin-register-link"><a href="<?= e(route('login')) ?>">Back to sign in</a></p></div></section></main>
<script src="/js/language-switcher.js" defer></script></body></html>
