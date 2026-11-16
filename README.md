# CCDI Lost and Found

**The Development of an Image-Based Lost and Found Retrieval System for CCDI Using Feature-Matching Algorithms**

![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![PHP 8.4+](https://img.shields.io/badge/PHP-8.4%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-XAMPP-4479A1?logo=mysql&logoColor=white)
![Tailwind CSS 4](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white)
![OpenCV](https://img.shields.io/badge/OpenCV-SIFT%20%2B%20ORB-5C3EE8?logo=opencv&logoColor=white)
![PHPUnit 12](https://img.shields.io/badge/tests-PHPUnit_12-366488)

A web-based lost-and-found system for the **Computer Communication Development Institute (CCDI)**. Members of the
CCDI community report lost and found belongings in one central place, and the system suggests possible matches by
comparing item photographs (SIFT first, ORB as a fallback) or, when there is no photo, by comparing text details.
Administrators review every suggestion and verify every claim before an item is released.

> **Image matches are potential matches only.** They never prove ownership, approve a claim, or release an item.

<!-- Add screenshots here, for example:
![Home page](docs/screenshots/home.png)
![Potential matches](docs/screenshots/matches.png)
-->

## Table of contents

- [Features](#features)
- [How matching works](#how-matching-works)
- [Tech stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Create the first administrator](#create-the-first-administrator)
- [Configuration](#configuration)
- [Roles and access](#roles-and-access)
- [Running the tests](#running-the-tests)
- [Project structure](#project-structure)
- [Security](#security)
- [Known limitations](#known-limitations)
- [Documentation](#documentation)
- [Developers](#developers)

## Features

**Centralized lost and found management**
- Registration, login, and logout with role-based access (student, teacher, staff, administrator)
- Report lost or found items with name, description, category, color, location, date, time, and an optional photo
- Browse and search published reports by text, category, location, date, and type
- Report statuses managed by administrators (pending review, published, rejected, resolved, archived)
- In-app notifications and an administrator activity log

**Image-based retrieval**
- SIFT feature matching first; ORB only when no candidate meets the SIFT criteria
- Lost-item photos are compared with found-item photos, and the other way around
- Qualifying results are saved as potential matches for review, with the evidence behind each one

**Text-based retrieval**
- Reports without photos still get suggestions, based on item name, description, color (and brand), with shared location and category as supporting signals

**Claim verification**
- Members submit ownership claims with a description and supporting proof
- Only administrators approve or reject claims (a rejection needs a reason) and record the item's release
- Releasing an item resolves the found report and rejects competing open claims

## How matching works

For each published report with a photo, the system compares it with up to the **200 most recent published reports of the
opposite type** that have photos. The thesis parameters are the defaults:

| Setting | Value |
|---|---|
| Maximum image dimension (matching) | 1,024 px |
| Maximum feature points | 1,500 |
| Minimum keypoints per image | 20 |
| Lowe's ratio test | 0.75 |
| RANSAC tolerance | 5 px |
| Minimum inliers | SIFT 12, ORB 15 |
| Minimum inlier ratio | 25% of ratio-test matches |

1. **SIFT** runs first. A candidate is accepted when it meets the keypoint, inlier, and inlier-ratio criteria.
2. **ORB** runs only if no candidate met the SIFT criteria.
3. Accepted candidates are stored as potential matches. The displayed score is the **number of verified feature
   correspondences (RANSAC inliers)**, not a percentage.
4. Administrators review matches; matches never trigger an approval or a release.

Laravel handles the web application, authentication, database, reports, claims, and administration. A Python script using
OpenCV and NumPy (`scripts/matcher/match_images.py`) handles the image processing. If Python or OpenCV is missing or fails,
the report is still saved, text search keeps working, and the user sees the real reason; success is never simulated.

> The thresholds are the starting values from the research design and have **not** been validated on real CCDI photographs yet.

## Tech stack

| Layer | Technology |
|---|---|
| Back end | Laravel 13, PHP 8.4 or newer |
| Front end | Blade templates, Tailwind CSS 4, Vite, plain JavaScript |
| Database | MySQL / MariaDB (XAMPP), database name `ccdi_lost_found` |
| Image matching | Python, OpenCV (SIFT, ORB), NumPy |
| Testing | PHPUnit 12 |

## Requirements

- **PHP 8.4.1 or newer** with `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `openssl`, `xml`, `curl`, `zip`
- MySQL or MariaDB (the version that ships with XAMPP is fine)
- Composer
- Node.js and npm (to build CSS and JavaScript)
- Python 3.9 or newer with OpenCV 4.4 or newer (SIFT needs it)

> **XAMPP users:** the `php` in your terminal may be newer than the PHP that XAMPP's Apache uses. The project does not run on
> PHP 8.3 or older. Check both with `php -v` and `C:\xampp\php\php.exe -v`.

## Installation

```bash
# 1. Clone and enter the project
git clone <your-repository-url>
cd ccdi_lost_found

# 2. Install dependencies
composer install
npm install
python -m pip install -r scripts/matcher/requirements.txt

# 3. Environment file and application key
copy .env.example .env          # macOS/Linux: cp .env.example .env
php artisan key:generate

# 4. Build the front-end assets
npm run build
```

**5. Create the database.** Start Apache and MySQL in the XAMPP Control Panel and choose **one** path:

- **Migrations (recommended):** create an empty database named `ccdi_lost_found` (collation `utf8mb4_unicode_ci`), then run
  ```bash
  php artisan migrate
  php artisan db:seed      # adds the starter categories only; creates no user accounts
  ```
- **SQL import:** import `database/ccdi_lost_found_database.sql` in phpMyAdmin into a fresh, empty `ccdi_lost_found` database.

**6. Run the app.**

```bash
php artisan serve        # http://127.0.0.1:8000
```

Photos are stored on a private disk and served through an authorized route, so `php artisan storage:link` is not needed.
Never run `php artisan migrate:fresh` on a database whose data you need.

## Create the first administrator

There is no default admin account or password. Create one from the terminal:

```bash
php artisan ccdi:make-admin you@example.com --name="Your Name"
```

You are prompted for a password (at least 12 characters). Registration through the website can only create student,
teacher, or staff accounts. After the first administrator exists, administrators manage other accounts under **Admin → Users**.

## Configuration

Main settings in `.env` (see `.env.example`):

| Variable | Default | Purpose |
|---|---|---|
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `ccdi_lost_found`, `root`, empty | XAMPP defaults; use a dedicated MySQL user outside local demos |
| `MATCHER_PYTHON` | `python` | Full path to Python if it is not on your PATH |
| `MATCHER_ENABLED` | `true` | Turn image matching on or off |
| `MATCHER_TIMEOUT_SECONDS` | `90` | Maximum time for one matching run |
| `MATCHER_SIFT_*`, `MATCHER_ORB_*`, `MATCHER_MIN_KEYPOINTS` | thesis values | Matching thresholds (see `config/matching.php`) |
| `REPORTS_AUTO_PUBLISH_LOST` | `true` | Lost reports are listed at once; found reports always wait for administrator review |
| `UPLOAD_MAX_KB` | `30720` | Maximum photo size (30 MB) |
| `APP_DEBUG` | `true` | Set to `false` for anything other than local development |

**Large uploads on XAMPP:** PHP applies its own limits before Laravel runs. In `php.ini` set `upload_max_filesize = 32M`,
`post_max_size = 40M`, `memory_limit = 512M`, and `max_execution_time = 120`, then restart Apache. Matching runs inside the web
request, and the first run on many new photos is the slowest because descriptors are cached afterwards.

## Roles and access

| | Guest | Student / Teacher / Staff | Administrator |
|---|---|---|---|
| Browse and search published reports | Yes | Yes | Yes |
| See found-item photos | No | Yes (signed in) | Yes |
| Report items, submit or cancel claims, notifications | No | Yes | Yes |
| See potential matches | No | Own lost reports | All |
| Publish or reject reports, review matches, approve or reject claims, record releases, manage users, view activity log | No | No | Yes |

Access rules are enforced on the server with middleware and policies, not just by hiding links. An administrator cannot review
or release their own claim, and the last active administrator cannot be removed.

## Running the tests

```bash
php artisan test
```

The suite covers registration and login, role restrictions, report creation and validation, invalid and disguised uploads,
text search, SIFT success, ORB fallback, no-match results, corrupt images, worker failure, claims, unauthorized approval,
approval and rejection, item release, and access to other users' records. The image-matching tests run the real OpenCV worker
and are skipped (not faked) when Python or OpenCV is missing. If `python` is not on your PATH, set `MATCHER_PYTHON` first.

## Project structure

```
app/
  Console/Commands/     ccdi:make-admin command
  Enums/                roles, report and claim statuses, match status
  Http/Controllers/     member pages; Admin/ for administrator pages
  Models/               users, item reports, images, claims, matches, releases, logs
  Policies/             authorization rules
  Services/             report, matching, text matching, claim, image storage services
config/                 matching.php (thresholds), ccdi.php (uploads, publishing)
database/               migrations, seeders, SQL import file
docs/                   audit notes and UI design notes
resources/              Blade views, CSS (Tailwind 4), JavaScript
routes/web.php          application routes
scripts/matcher/        Python + OpenCV matcher (SIFT, ORB)
tests/                  PHPUnit feature and unit tests
```

## Security

- Uploads are checked by file content, re-encoded as JPEG (embedded metadata removed), stored under random names on a private disk
- Rate limits on login, registration, report submission, and claims
- Server-side authorization for every admin action; claim decisions run in database transactions and are written to the activity log
- Private identifying details submitted for verification are never shown to other members and are not searchable
- Do not commit `.env`, `vendor/`, `node_modules/`, logs, or uploaded photos

## Known limitations

- Matching accuracy has not been measured on real CCDI photographs; the thresholds are unvalidated defaults
- Image matching runs synchronously inside the request (no queue worker)
- Registration is open: anyone can register as a student, teacher, or staff member, with no e-mail verification or approval step
- In-app notifications only (no e-mail), no password-reset flow, and members cannot edit a submitted report
- Not yet verified in a browser across phone, tablet, and desktop widths

## Documentation

- `docs/AUDIT_AND_CHANGES.md`: earlier audit and change notes
- `docs/UI_REDESIGN.md`: design system and responsive rules
- `config/matching.php`: matching parameters and their meaning

## Developers

This system was developed as a mini capstone / thesis project at the Computer Communication Development Institute (CCDI) by:

- **Antonio Mangampo**
- **Wenelyn Malaylay**
- **Marc Aron Mangampo**
- **Ronie Padua**

## License

No license has been chosen yet. Add a `LICENSE` file before publishing if you want others to be able to reuse the code.
