# EcoCycle

EcoCycle is a Laravel application concept for Malaysian SMEs to track inventory, reduce waste, and exchange reusable materials with other businesses.

## Pages

- `/` — public landing page
- `/business` — business workspace or verification status
- `/login` — shared business and administrator sign-in
- `/admin/dashboard` — administrator review queue (requires administrator sign-in)

Business registration, private SSM document uploads, administrator review, approval, and change requests are implemented. Registration and review decision emails use Laravel Mail. To send through Gmail, set a Google App Password in `MAIL_PASSWORD` in your local `.env`; the normal Gmail account password will not work. Set `MAIL_MAILER=smtp`, `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, `MAIL_SCHEME=tls`, `MAIL_USERNAME`, `MAIL_FROM_ADDRESS`, and `ECOCYCLE_ADMIN_EMAIL`, then run `php artisan config:clear`.

Set `ECOCYCLE_ADMIN_EMAIL`, `ECOCYCLE_ADMIN_USERNAME`, and `ECOCYCLE_ADMIN_PASSWORD` in your local `.env`, then run `php artisan db:seed --class=Database\\Seeders\\AdminUserSeeder` to create the local administrator account. Both admins and business owners sign in through `/login`.

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
