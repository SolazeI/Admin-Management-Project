## Trucking Management System (Laravel + Vite)

This project is a Laravel 9.x application with a Vite-powered frontend for managing trucking operations such as driver records, fleet/truck assignments, trip tickets, maintenance, and basic reporting.

## Requirements

- PHP `8.0.2+`
- Composer
- Node.js + npm
- MySQL (configured via `.env`)

## Main Modules

- Driver Management
- Fleet Management
- Trip Tickets
- Maintenance
- Reports

## Setup (Windows / PowerShell)

1. Install PHP dependencies

   ```powershell
   composer install
   ```

2. Create your `.env` file (copy from the template)

   ```powershell
   copy .env.example .env
   ```

3. Generate the Laravel app key

   ```powershell
   php artisan key:generate
   ```

4. Configure database in `.env`

   Your `.env.example` uses MySQL with:
   - `DB_CONNECTION=mysql`
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=3306`
   - `DB_DATABASE=laravel`
   - `DB_USERNAME=root`
   - `DB_PASSWORD=` (empty by default)

   Make sure the database `laravel` exists (or change `DB_DATABASE` to match your MySQL database name).

5. Run migrations

   ```powershell
   php artisan migrate
   ```

6. Install frontend dependencies + run Vite dev server (in a second terminal)

   ```powershell
   npm install
   npm run dev
   ```

7. Start the Laravel server (backend)

   ```powershell
   php artisan serve
   ```

   By default, Laravel runs at `http://127.0.0.1:8000`.

## Seeding data (optional)

```powershell
php artisan db:seed
```

Note: the default `DatabaseSeeder` in this repo does not insert any records by default (custom seeders can be added later).

## Production build (optional)

If you want to build frontend assets:

```powershell
npm run build
```
