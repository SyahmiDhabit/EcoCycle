<?php $preferenceLocale = app()->getLocale(); ?>
<div class="site-preferences">
    <button class="site-theme-toggle" type="button" data-theme-toggle aria-label="<?= $preferenceLocale === 'ms' ? 'Mod gelap' : 'Dark mode' ?>" aria-pressed="false">
        <span class="site-theme-moon" aria-hidden="true">☾</span>
        <span class="site-theme-sun" aria-hidden="true">☀</span>
    </button>
    <details class="site-language">
        <summary aria-label="<?= $preferenceLocale === 'ms' ? 'Pilih bahasa' : 'Choose language' ?>"><?= $preferenceLocale === 'ms' ? 'BM' : 'EN' ?></summary>
        <form method="POST" action="<?= e(route('language.update')) ?>">
            <?= csrf_field() ?>
            <button type="submit" name="locale" value="ms" <?= $preferenceLocale === 'ms' ? 'aria-current="true"' : '' ?>>Bahasa Melayu</button>
            <button type="submit" name="locale" value="en" <?= $preferenceLocale === 'en' ? 'aria-current="true"' : '' ?>>English</button>
        </form>
    </details>
</div>
