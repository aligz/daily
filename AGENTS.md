# AGENTS.md — daily

Laravel 12 + Inertia/Svelte 5. Environment dev jalan di Docker; **host tidak punya PHP**.

## Testing rules

- Standar environment server ada di `/home/gue/apps/LOCAL.md` — baca dulu kalau belum.
- Semua test WAJIB via docker exec di container dev, dengan env testing:
  - `docker exec -u www-data daily-app php artisan test` (Laravel; `tests/bootstrap.php` + `phpunit.xml` memaksa `APP_ENV=testing`)
  - `docker exec -u www-data -e APP_ENV=testing daily-app php artisan ...` (perintah lain)
- DB yang dipakai test hanya yang ada di `.env.testing` (DB `daily_test`).
- DILARANG `migrate:fresh` / seeder di env `local` atau `prod`.
- Jangan nulis ulang `.env.local` / `.env.prod`.

## Catatan project

- Semua `php`/`artisan`/`composer` lewat `docker exec`; asset build lewat container `vite`.
- Nilai konkret mesin ini (container, DB, domain, perintah) ada di `LOCAL.md` project.
- Jangan commit `compose.override.yml`, `.env.local`, `.env.testing`, atau `LOCAL.md`.
