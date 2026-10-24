# EcoCycle

EcoCycle is a Laravel application concept for Malaysian SMEs to track inventory, reduce waste, and exchange reusable materials with other businesses.

## Pages

- `/` — public landing page
- `/business` — SME workspace interface preview
- `/admin` — platform administration interface preview

The business and admin pages are interface previews. Authentication, account roles, and live business data still need to be implemented before production use.

## Requirements

- PHP 8.3 or later with the extensions required by Laravel
- Composer
- Node.js and npm

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

On Windows PowerShell, use `Copy-Item .env.example .env` instead of `cp` if needed. Then open the local URL printed by `php artisan serve`.

The local `.env` file and SQLite database are intentionally excluded from Git. Each developer should create their own `.env` and local database.
