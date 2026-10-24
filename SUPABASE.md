# Supabase PostgreSQL setup

EcoCycle uses Laravel's PostgreSQL driver to connect to a Supabase database. PHP's `pdo_pgsql` extension is available in the current development environment, and the existing migrations use Laravel's schema builder.

## 1. Create a Supabase project

Create a project in the Supabase dashboard. Keep its database password private. In the project, open **Connect** and copy the **Session pooler** settings. For a local Laragon setup, the session pooler is generally the compatible choice for IPv4 networks; use the exact host and username shown by Supabase.

Use the session pooler on port `5432`. Do not use the transaction pooler on port `6543` as the normal Laravel database connection.

## 2. Configure Laravel

Update the database values in the local `.env` file (never commit that file):

```env
DB_CONNECTION=pgsql
DB_HOST=your-session-pooler-host-from-supabase
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.your-project-ref
DB_PASSWORD=your-supabase-database-password
DB_SSLMODE=require
```

Replace every example value with the values from **Connect → Session pooler**. Leave `DB_URL` unset unless you choose to use a connection URL instead. Do not paste the password into source code or commit it.

Then clear cached configuration and check the connection:

```sh
php artisan config:clear
php artisan migrate:status
```

For a new, empty Supabase database, create the EcoCycle tables with:

```sh
php artisan migrate
```

Only run migrations against the intended project. Back up any database that already contains data before changing its schema.

## 3. View your data

In the Supabase dashboard, open **Table Editor** and select the `public` schema. After registration and migration, the `users` table contains the account fields and the paths for the uploaded SSM files. The files themselves are currently saved on Laravel's local filesystem; a deployed app will need a persistent file store such as Supabase Storage for those uploads.

## 4. Run tests

The test configuration in `phpunit.xml` uses an in-memory SQLite database, so normal tests do not write to your Supabase project:

```sh
php artisan test
```

