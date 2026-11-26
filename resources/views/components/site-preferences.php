<?php $preferenceLocale = app()->getLocale(); ?>
<div class="site-preferences">
    <button class="site-theme-toggle" type="button" data-theme-toggle aria-label="<?= $preferenceLocale === 'ms' ? 'Mod gelap' : 'Dark mode' ?>" aria-pressed="false">
        <svg class="site-theme-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M20.9 13.35A9 9 0 0 1 10.65 3.1 9 9 0 1 0 20.9 13.35Z"/>
        </svg>
        <svg class="site-theme-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <circle cx="12" cy="12" r="4"/>
            <path d="M12 2v2m0 16v2M2 12h2m16 0h2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42m0-14.14-1.42 1.42m-11.3 11.3-1.42 1.42"/>
        </svg>
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
