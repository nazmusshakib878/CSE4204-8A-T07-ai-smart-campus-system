# Backend

Laravel API backend for the AI Smart Campus System.

## Setup

```bash
composer install
```

Create `.env` from `.env.example`, generate an application key, configure MySQL, and run the migrations:

```powershell
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Tests

```bash
php artisan test
```

API routes are defined in `routes/api.php` and protected routes use Laravel Sanctum.
