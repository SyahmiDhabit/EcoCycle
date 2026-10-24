# Supabase PostgreSQL setup

EcoCycle can use local MySQL and Supabase PostgreSQL as separate Laravel connections. PHP's `pdo_pgsql` extension is available in the current development environment, and the existing migrations use Laravel's schema builder.

## 1. Create a Supabase project

Create a project in the Supabase dashboard. Keep its database password private. In the project, open **Connect** and copy the **Session pooler** settings. For a local Laragon setup, the session pooler is generally the compatible choice for IPv4 networks; use the exact host and username shown by Supabase.

Use the session pooler on port `5432`. Do not use the transaction pooler on port `6543` as the normal Laravel database connection.

## 2. Configure Laravel

Keep the local MySQL connection as the default if you want registration to continue writing to the phpMyAdmin `ecocycle` database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecocycle
DB_USERNAME=root
DB_PASSWORD=
```

Add a separate Supabase connection to the local `.env` file (never commit that file):

```env
SUPABASE_DB_HOST=your-session-pooler-host-from-supabase
SUPABASE_DB_PORT=5432
SUPABASE_DB_DATABASE=postgres
SUPABASE_DB_USERNAME=postgres.your-project-ref
SUPABASE_DB_PASSWORD=your-supabase-database-password
SUPABASE_DB_SSLMODE=require
```

Replace every example value with the values from **Connect → Session pooler**. The application uses `mysql` by default; use the named `supabase` connection when you intentionally run queries or migrations against Supabase. Do not paste the password into source code or commit it.

Then clear cached configuration and check the Supabase connection:

```sh
php artisan config:clear
php artisan migrate:status --database=supabase
```

For a new, empty Supabase database, create the EcoCycle tables with:

```sh
php artisan migrate --database=supabase
```

Only run migrations against the intended project. Back up any database that already contains data before changing its schema.

## 3. View your data

In the Supabase dashboard, open **Table Editor** and select the `public` schema. The app tables are `business` and `administrator`; Laravel also keeps `migrations` there to record which schema changes ran. A cleanup migration removes the unused database-backed sessions, cache, queue, and password-reset tables because those drivers use local files, synchronous jobs, and no database password reset flow. Business registration stores SSM file paths in `business`; the files themselves are saved on Laravel's local filesystem, so a deployed app will need persistent file storage such as Supabase Storage.

## 4. Run tests

Laravel migrations run against only one connection at a time. `php artisan migrate` uses the default connection (MySQL in this setup); the command above migrates Supabase separately. Registration writes to the default MySQL connection and is not automatically mirrored to Supabase.

The test configuration in `phpunit.xml` uses an in-memory SQLite database, so normal tests do not write to either database:

```sh
php artisan test
```

