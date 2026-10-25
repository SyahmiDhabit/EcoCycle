<?php $currentLocale = app()->getLocale(); ?>
<div class="language-switcher" aria-label="Language selection">
    <form method="POST" action="<?= e(route('language.update')) ?>">
        <?= csrf_field() ?>
        <button type="submit" name="locale" value="en" <?= $currentLocale === 'en' ? 'aria-current="true"' : '' ?>>English</button>
        <span aria-hidden="true">|</span>
        <button type="submit" name="locale" value="ms" <?= $currentLocale === 'ms' ? 'aria-current="true"' : '' ?>>Bahasa Melayu</button>
    </form>
</div>
