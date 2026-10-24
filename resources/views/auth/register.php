<?php
$errors = $errors ?? new \Illuminate\Support\MessageBag();
$fields = ['business_name', 'business_registration_number', 'industry', 'business_phone', 'registered_address', 'city', 'postcode', 'state', 'name', 'email', 'password', 'password_confirmation', 'ssm_certificate', 'ssm_profile', 'terms_accepted'];
$fieldErrors = [];
foreach ($fields as $field) { $fieldErrors[$field] = $errors->first($field); }
$passwordMismatch = $fieldErrors['password'] === 'The passwords do not match.';
if ($passwordMismatch) {
    $fieldErrors['password_confirmation'] = $fieldErrors['password'];
    $fieldErrors['password'] = '';
}
$errorLabels = [
    'business_name' => 'Registered business name',
    'business_registration_number' => 'SSM registration number',
    'industry' => 'Industry',
    'business_phone' => 'Business phone',
    'registered_address' => 'Registered address',
    'city' => 'City',
    'postcode' => 'Postcode',
    'state' => 'State',
    'name' => 'Contact person',
    'email' => 'Business email',
    'password' => 'Password',
    'password_confirmation' => 'Confirm password',
    'ssm_certificate' => 'SSM registration certificate',
    'ssm_profile' => 'SSM business profile',
    'terms_accepted' => 'Business authorisation and terms',
];
$states = ['Johor', 'Kedah', 'Kelantan', 'Kuala Lumpur', 'Labuan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Penang', 'Perak', 'Perlis', 'Putrajaya', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu'];
$industries = ['Agriculture & food', 'Construction & building materials', 'Furniture & wood products', 'Manufacturing', 'Packaging & paper', 'Textiles & apparel', 'Other'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Register your business with EcoCycle for verification.">
    <title>Register your business - EcoCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/auth/register.css">
</head>
<body class="signup-page">
    <header class="signup-header">
        <a class="signup-brand" href="/" aria-label="EcoCycle home"><img src="/images/figma/ecocycle-leaf.svg" alt=""><span>EcoCycle</span></a>
        <a class="header-signin" href="/login">Already registered? <span>Sign in <span aria-hidden="true">&#8594;</span></span></a>
    </header>
    <main class="signup-layout">
        <aside class="signup-guidance">
            <span class="business-badge">BUSINESS OWNER</span>
            <h1>Start your circular business journey.</h1>
            <p class="guidance-intro">Create one business account to manage operations, list surplus and obtain materials.</p>
            <div class="signup-hero-photo"><img src="/images/figma/signup/verification-hero.jpg" alt="Materials being prepared for reuse at a business warehouse"><div class="photo-caption"><img src="/images/figma/signup/recycle.svg" alt=""><span>A resource, not a leftover.<small>Better operations. More possibilities.</small></span></div></div>
            <ol class="review-steps">
                <li><span class="step-icon complete">&#10003;</span><span><strong>Business information</strong><small>Tell us about your registered company.</small></span></li>
                <li><span class="step-icon complete">&#10003;</span><span><strong>Legitimacy documents</strong><small>Upload clear, current SSM documents.</small></span></li>
                <li><span class="step-icon pending">&#9711;</span><span><strong>Administrator review</strong><small>We will email you with the decision.</small></span></li>
            </ol>
            <div class="verification-note"><span class="note-check">&#10003;</span><span><strong>Verification comes first</strong><small>You can access the marketplace and transactions only after approval. There are no separate buyer or seller accounts.</small></span></div>
            <p class="signup-support">Need help with your documents?<br><a href="mailto:support@ecocycle.my">support@ecocycle.my</a></p>
        </aside>
        <section class="signup-form-panel" aria-labelledby="signup-heading">
            <header class="form-heading"><h2 id="signup-heading">Register your business</h2><p>All fields marked * are required. Your documents are private.</p></header>
            <?php if (session('status')): ?><div class="success-notice" role="status" aria-live="polite"><?= e(session('status')) ?></div><?php endif; ?>
            <?php if ($errors->any()): ?><div class="error-summary" id="signup-error-summary" role="alert" tabindex="-1"><strong>Please review these items:</strong><ul><?php foreach ($errors->getMessages() as $field => $messages): ?><li><a href="#<?= e($field) ?>"><?= e($errorLabels[$field] ?? ucfirst(str_replace('_', ' ', $field))) ?>:</a> <?= e($messages[0]) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form id="business-registration-form" class="registration-form" method="POST" action="/register" enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>
                <section class="form-card">
                    <div class="card-heading"><h3>Company &amp; contact information</h3><p>Use the details shown on your SSM registration.</p></div>
                    <div class="field-grid">
                        <div class="form-field"><label for="business_name">Registered business name *</label><input id="business_name" name="business_name" value="<?= e(old('business_name')) ?>" placeholder="KayuKita Manufacturing Sdn. Bhd." autocomplete="organization" maxlength="255" required aria-invalid="<?= $fieldErrors['business_name'] ? 'true' : 'false' ?>"><?php if ($fieldErrors['business_name']): ?><small class="field-error"><?= e($fieldErrors['business_name']) ?></small><?php endif; ?></div>
                        <div class="form-field"><label for="business_registration_number">SSM registration number *</label><input id="business_registration_number" name="business_registration_number" value="<?= e(old('business_registration_number')) ?>" placeholder="202101023456 (1425678-X)" maxlength="60" required aria-invalid="<?= $fieldErrors['business_registration_number'] ? 'true' : 'false' ?>"><?php if ($fieldErrors['business_registration_number']): ?><small class="field-error"><?= e($fieldErrors['business_registration_number']) ?></small><?php endif; ?></div>
                        <div class="form-field"><label for="industry">Industry *</label><select id="industry" name="industry" required aria-invalid="<?= $fieldErrors['industry'] ? 'true' : 'false' ?>"><option value="">Select your industry</option><?php foreach ($industries as $industry): ?><option value="<?= e($industry) ?>" <?= old('industry') === $industry ? 'selected' : '' ?>><?= e($industry) ?></option><?php endforeach; ?></select><?php if ($fieldErrors['industry']): ?><small class="field-error"><?= e($fieldErrors['industry']) ?></small><?php endif; ?></div>
                        <div class="form-field"><label for="business_phone">Business phone *</label><input id="business_phone" name="business_phone" type="tel" value="<?= e(old('business_phone')) ?>" placeholder="+60 3-5510 2488" autocomplete="tel" maxlength="30" required aria-invalid="<?= $fieldErrors['business_phone'] ? 'true' : 'false' ?>"><?php if ($fieldErrors['business_phone']): ?><small class="field-error"><?= e($fieldErrors['business_phone']) ?></small><?php endif; ?></div>
                        <div class="form-field full-field"><label for="registered_address">Registered address *</label><input id="registered_address" name="registered_address" value="<?= e(old('registered_address')) ?>" placeholder="18, Jalan Industri U6/8, Seksyen U6" autocomplete="street-address" maxlength="255" required aria-invalid="<?= $fieldErrors['registered_address'] ? 'true' : 'false' ?>"><?php if ($fieldErrors['registered_address']): ?><small class="field-error"><?= e($fieldErrors['registered_address']) ?></small><?php endif; ?></div>
                        <div class="form-field"><label for="city">City *</label><input id="city" name="city" value="<?= e(old('city')) ?>" placeholder="Shah Alam" autocomplete="address-level2" maxlength="100" required aria-invalid="<?= $fieldErrors['city'] ? 'true' : 'false' ?>"><?php if ($fieldErrors['city']): ?><small class="field-error"><?= e($fieldErrors['city']) ?></small><?php endif; ?></div>
                        <div class="form-field"><label for="postcode">Postcode *</label><input id="postcode" name="postcode" value="<?= e(old('postcode')) ?>" placeholder="40150" autocomplete="postal-code" inputmode="numeric" maxlength="12" required aria-invalid="<?= $fieldErrors['postcode'] ? 'true' : 'false' ?>"><?php if ($fieldErrors['postcode']): ?><small class="field-error"><?= e($fieldErrors['postcode']) ?></small><?php endif; ?></div>
                        <div class="form-field"><label for="state">State *</label><select id="state" name="state" required aria-invalid="<?= $fieldErrors['state'] ? 'true' : 'false' ?>"><option value="">Select state</option><?php foreach ($states as $state): ?><option value="<?= e($state) ?>" <?= old('state') === $state ? 'selected' : '' ?>><?= e($state) ?></option><?php endforeach; ?></select><?php if ($fieldErrors['state']): ?><small class="field-error"><?= e($fieldErrors['state']) ?></small><?php endif; ?></div>
                        <div class="form-field"><label for="name">Contact person *</label><input id="name" name="name" value="<?= e(old('name')) ?>" placeholder="Amir Lim" autocomplete="name" maxlength="255" required aria-invalid="<?= $fieldErrors['name'] ? 'true' : 'false' ?>"><?php if ($fieldErrors['name']): ?><small class="field-error"><?= e($fieldErrors['name']) ?></small><?php endif; ?></div>
                        <div class="form-field"><label for="email">Business email *</label><input id="email" name="email" type="email" value="<?= e(old('email')) ?>" placeholder="amir@kayukita.com.my" autocomplete="email" maxlength="255" required aria-invalid="<?= $fieldErrors['email'] ? 'true' : 'false' ?>"><?php if ($fieldErrors['email']): ?><small class="field-error"><?= e($fieldErrors['email']) ?></small><?php endif; ?></div>
                        <div class="form-field"><label for="password">Password *</label><div class="password-wrap"><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required aria-invalid="<?= $fieldErrors['password'] ? 'true' : 'false' ?>"><button type="button" data-password-toggle="password" aria-label="Show password">Show</button></div><small class="field-hint">At least 8 characters, including a number.</small><?php if ($fieldErrors['password']): ?><small class="field-error"><?= e($fieldErrors['password']) ?></small><?php endif; ?></div>
                        <div class="form-field"><label for="password_confirmation">Confirm password *</label><div class="password-wrap"><input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required><button type="button" data-password-toggle="password_confirmation" aria-label="Show password confirmation">Show</button></div><?php if ($fieldErrors['password_confirmation']): ?><small class="field-error"><?= e($fieldErrors['password_confirmation']) ?></small><?php endif; ?></div>
                    </div>
                </section>
                <section class="form-card document-card">
                    <div class="card-heading"><h3>Business legitimacy documents</h3><p>PDF, JPG or PNG - Maximum 10 MB per file. Ensure all text and SSM numbers are readable.</p></div>
                    <div class="upload-field" <?= $fieldErrors['ssm_certificate'] ? 'data-invalid="true"' : '' ?>><img src="/images/figma/signup/verification-file.svg" alt=""><div><label for="ssm_certificate">SSM registration certificate *</label><small>Certificate of incorporation or business registration with full company name and registration number.</small><small class="selected-file" data-file-name="ssm_certificate">Choose a PDF, JPG or PNG file (up to 10 MB).</small><?php if ($fieldErrors['ssm_certificate']): ?><small class="field-error" id="ssm_certificate-error"><?= e($fieldErrors['ssm_certificate']) ?></small><?php endif; ?></div><input class="file-input" id="ssm_certificate" name="ssm_certificate" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required aria-invalid="<?= $fieldErrors['ssm_certificate'] ? 'true' : 'false' ?>"></div>
                    <div class="upload-field upload-dashed" <?= $fieldErrors['ssm_profile'] ? 'data-invalid="true"' : '' ?>><img src="/images/figma/signup/verification-upload.svg" alt=""><div><label for="ssm_profile">Current SSM company / business profile *</label><small>Include registration status, address, and proprietor or director information. Upload the complete document.</small><small class="selected-file" data-file-name="ssm_profile">Choose file or drag and drop (up to 10 MB).</small><?php if ($fieldErrors['ssm_profile']): ?><small class="field-error" id="ssm_profile-error"><?= e($fieldErrors['ssm_profile']) ?></small><?php endif; ?></div><input class="file-input" id="ssm_profile" name="ssm_profile" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required aria-invalid="<?= $fieldErrors['ssm_profile'] ? 'true' : 'false' ?>"></div>
                </section>
                <div class="terms-field" <?= $fieldErrors['terms_accepted'] ? 'data-invalid="true"' : '' ?>><input id="terms_accepted" name="terms_accepted" type="checkbox" value="1" required <?= old('terms_accepted') ? 'checked' : '' ?> aria-invalid="<?= $fieldErrors['terms_accepted'] ? 'true' : 'false' ?>"><label for="terms_accepted">I confirm that I am authorised to represent this business and that the information is accurate. I agree to the <a href="#terms">Terms of Use</a> and <a href="#privacy">Privacy Notice</a>.</label></div>
                <?php if ($fieldErrors['terms_accepted']): ?><small class="field-error terms-error" id="terms_accepted-error"><?= e($fieldErrors['terms_accepted']) ?></small><?php endif; ?>
                <div class="form-actions"><span>Your account role will be Business Owner.</span><button type="submit" class="submit-button" data-submit-button><span aria-hidden="true">&#8594;</span><span data-submit-label>Submit for verification</span></button></div>
            </form>
        </section>
    </main>
    <script src="/js/auth/signup.js" defer></script>
</body>
</html>
