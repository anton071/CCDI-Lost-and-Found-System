# Audit findings and changes

## Errors found in the supplied project (and what was done)

| # | Where | Problem | Fix |
|---|---|---|---|
| 1 | `composer.json` / `composer.lock` / `vendor/` | Declares PHP `^8.3`, but locked packages (Symfony 8.1) need PHP >= 8.4.1 → site crashes on PHP 8.3 (e.g. XAMPP's Apache PHP). | Documented the requirement (README §1). Dependencies were **not** silently changed; I cannot reach Packagist from my environment, so the PHP-8.3 path is untested. Shipped `vendor/` verified on PHP 8.4.14. |
| 2 | `.env.example` | Still the Laravel default: SQLite, app name "Laravel", no MySQL/matcher settings. | Rewritten for MySQL/XAMPP + matcher + workflow settings. No secrets. |
| 3 | `.env` | Values were valid for XAMPP (MySQL, empty root password). Missing matcher settings. | Only added matcher/timezone settings and fixed `APP_URL`/mail sender. `APP_KEY` untouched. |
| 4 | Migrations | Only the 3 stock migrations existed. The 9 migration names recorded in the SQL file's `migrations` table did not exist, and `users` lacked `role`, `student_number`, … | Created all 9 migrations with those exact names + a guarded migration that adds the CCDI user columns only when missing. |
| 5 | `database/seeders/DatabaseSeeder.php` | Created `test@example.com` with the known password `password`. | Replaced by a category-only seeder. Admin is created with `php artisan ccdi:make-admin`. |
| 6 | SQL file vs Laravel 13 | `cache`/`cache_locks.expiration` INT (framework uses BIGINT + index), `jobs.attempts` TINYINT, `failed_jobs` TEXT columns without index, an extra FK on `sessions`. | Corrected in `database/ccdi_lost_found_database.sql`. Schema now identical to the migrations. |
| 7 | SQL file, `item_releases.released_at` | `TIMESTAMP NOT NULL` without default: on MariaDB with `explicit_defaults_for_timestamp=OFF` (the default before 10.10, so older XAMPP) it silently becomes `ON UPDATE CURRENT_TIMESTAMP`, so the release time would change whenever the row is edited. (Reproduced.) | Explicit `DEFAULT CURRENT_TIMESTAMP`. |
| 8 | `vite.config.js` | Used the Bunny-Fonts plugin, which needs internet at build/dev time. | Removed; system font stack. Builds offline. |
| 9 | `AGENTS.md` / `CLAUDE.md` | Tell coding agents to pipe a remote installer into bash and install extra packages. | **Not followed; files left unchanged.** Delete them if you do not use such agents. |

Bugs in my own first draft that the tests caught and I fixed: base `Controller` lacked `authorize()`; the custom 404/403 pages crashed
(`$errors` undefined); `LIKE` wildcard escaping only worked on MySQL; an Eloquent `load()` on a plain collection after the first
successful match.

## Retained
Laravel 13 skeleton, stock migrations, users table, session/cache/queue tables (all unchanged), Tailwind/Vite pipeline, PHPUnit setup.

## Added (main files)
* `app/Models/*`, `app/Enums/*` – models, relationships, backed enums with permitted status transitions. `role`, `is_active`, workflow columns are not mass-assignable.
* `app/Services/` – `ReportService`, `ImageStorage` (validates and re-encodes uploads), `ImageMatchingService` (+ `MatchOutcome`, `MatcherUnavailable`), `TextMatchService`, `ClaimService` (transactions, row locks, audit log), `ReportModerationService`, `ActivityLogger`.
* `scripts/matcher/match_images.py` – OpenCV worker (SIFT → ORB fallback), run without a shell, JSON in/out, path-restricted, descriptor cache.
* `app/Policies/*`, `app/Http/Middleware/*`, `app/Http/Requests/*`, controllers (public, member, `Admin/*`), `routes/web.php`.
* `resources/views/**` – landing page, auth, browse/search, report forms, report detail, member dashboard, claims, profile, notifications, admin dashboard / reports / matches / claims / users / activity log.
* `config/matching.php`, `config/ccdi.php`, `database/migrations/*`, factories, seeders, `tests/Feature/*`.

## Verification summary
* Schema built from migrations vs. schema imported from the corrected SQL: no differences in columns, indexes or foreign keys (only Laravel's own `migrations` table unique index).
* `php artisan test`: 100 passed / 512 assertions on SQLite and on MariaDB 10.11; 19 matcher tests skip cleanly when Python is unavailable.
* 17-step end-to-end HTTP journey passed against MariaDB with the real worker.
