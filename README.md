# CCDI Image-Based Lost and Found Retrieval System

*The Development of an Image-Based Lost and Found Retrieval System for CCDI Using Feature-Matching Algorithms*

Laravel 13 · MySQL/MariaDB (XAMPP) · Blade + Tailwind CSS · OpenCV (SIFT first, ORB fallback)

Image matches are **potential matches only**. They never prove ownership, approve a claim or release an item;
authorized personnel verify every claim.

---

## 1. Requirements

| Component | Version | Notes |
|---|---|---|
| **PHP** | **8.4.1 or newer** | See the warning below. Needs `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `openssl`, `xml`, `curl`, `zip`. |
| MySQL / MariaDB | any version shipped with current XAMPP | database name `ccdi_lost_found` |
| Python | 3.9+ | only for image matching |
| OpenCV (Python) | 4.4+ (SIFT needs it) | `pip install -r scripts/matcher/requirements.txt` |
| Node.js | optional | only to rebuild CSS/JS; compiled assets are already in `public/build` |
| Composer | optional | `vendor/` is included |

> ### PHP version requirement
> The project **requires PHP 8.4.1 or newer**: `composer.json` and `composer.lock` now declare `"php": "^8.4"`, matching the
> locked packages (Symfony 8.1). On PHP 8.3 or older the site fails at start-up with *"Composer detected issues in your
> platform"*. This often hits XAMPP users: the `php` in your terminal may be newer than the PHP that XAMPP's Apache uses.
>
> * Check XAMPP's PHP: `C:\\xampp\\php\\php.exe -v` (also check `php -v`; they can differ).
> * Use a PHP 8.4.1+ build (for example the separate PHP 8.5 used with `php artisan serve`).

## 2. First-time setup (Windows + XAMPP + VS Code)

```bash
# 1. In the project folder
copy .env.example .env          # skip if you already have a working .env
php artisan key:generate        # skip if .env already has APP_KEY

# 2. Start Apache + MySQL in the XAMPP Control Panel, then create the database (section 3)

# 3. Python worker for image matching
python -m pip install -r scripts/matcher/requirements.txt

# 4. Run
php artisan serve               # http://127.0.0.1:8000
```

`.env` essentials (XAMPP defaults): `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_DATABASE=ccdi_lost_found`,
`DB_USERNAME=root`, empty `DB_PASSWORD`. If `python` is not on your PATH set `MATCHER_PYTHON="C:\Path\To\python.exe"`.
For anything other than a local demo, create a dedicated MySQL user instead of using `root`.

Photographs are stored on a **private** disk and served through an authorised route, so **`php artisan storage:link` is not needed**.

To rebuild the CSS/JS after editing views: `npm install` then `npm run build` (or `npm run dev` while developing).

## 3. Database: choose ONE path

**A. Import the SQL (phpMyAdmin)** – use `database/ccdi_lost_found_database.sql`. This is your original file, *revised*
(see `docs/AUDIT_AND_CHANGES.md`). It creates the database, tables and 7 starter categories and records all migrations,
so afterwards `php artisan migrate` reports *Nothing to migrate*. Do not import it on top of tables you already have; drop the
empty/old `ccdi_lost_found` database first.

**B. Migrations** – create an empty database named `ccdi_lost_found` (collation `utf8mb4_unicode_ci`), then:

```bash
php artisan migrate
php artisan db:seed        # categories only; creates no user accounts
```

Both paths produce the same schema (verified by comparing every column, index and foreign key).
Never run `php artisan migrate:fresh` on a database whose data you need.

## 4. Create the first administrator

There is **no default admin account or password**. Create one from the terminal:

```bash
php artisan ccdi:make-admin you@ccdi.example --name="Your Name"
```

You are prompted for a password (min. 12 characters; it is never passed as an argument). Running it for an existing e-mail
promotes that account after confirmation. After that, administrators manage other users under **Admin → Users**.
Registration can only create student, teacher or staff accounts.

## 5. Image matching (SIFT first, ORB fallback)

For each report with a photograph the system compares it with published reports of the **opposite type** (never itself,
never the same type):

1. **SIFT** features are extracted and matched; matches are filtered by Lowe's ratio test, then by RANSAC geometry.
2. A candidate is *accepted* if it has at least `min_keypoints` in both images **and** at least `min_inliers`
   geometrically consistent matches **and** an inlier ratio of at least `min_inlier_ratio`.
3. **ORB runs only if no candidate satisfied the SIFT criterion**, with its own criterion (Hamming distance, same filters).
4. Accepted candidates are stored as potential matches with the algorithm, evidence and the criterion used. If neither
   algorithm accepts anything the user sees a clear "no potential match" message.

The stored "score" is the **number of verified feature correspondences (RANSAC inliers)** – not a percentage, and not
proof of ownership. Descriptors are cached (`storage/app/private/features`) and the cache key includes the image hash, so a
replaced image is re-processed. If Python/OpenCV is missing or crashes, the report is still saved, text search keeps
working, and the user is told the actual reason; success is never simulated.

**Default criteria** (`.env`, documented in `config/matching.php`) – *starting points, not validated values*:

| | ratio test | min inliers | min inlier ratio | min keypoints |
|---|---|---|---|---|
| SIFT | 0.75 | 12 | 0.25 | 20 |
| ORB | 0.75 | 15 | 0.25 | 20 |

**What was measured** (smoke test with scikit-image sample photographs, `scripts/matcher/make_sample_dataset.py`, OpenCV 4.13):
five re-photographed variants (rotation/scale, dimming + blur + low JPEG quality, perspective, crop) were all accepted by SIFT
with **151–667** inliers; four negatives (two unrelated photographs, a blank image, a gradient) reached at most **6** SIFT
and **5** ORB inliers and were rejected after the ORB fallback ran. This only shows the pipeline works. It is **not**
evidence of accuracy on real CCDI belongings: collect your own lost/found photo pairs, tune the thresholds, and report those
results in Chapter 3.

**Try it yourself**

1. Register two members and an admin. As member A report a *found* item with a photo; the admin publishes it
   (Admin → Reports). Found reports are matched when published.
2. As member B report a *lost* item with a different photo of the same object. The result page shows whether a potential
   match was found; the report page shows the evidence.
3. Sample photos: `pip install scikit-image` then
   `python scripts/matcher/make_sample_dataset.py storage/app/private/sample-dataset`
   (use the `found_*` and `lost_*` files). Admins can re-run matching per report.

**UI:** see `docs/UI_REDESIGN.md` for the CCDI design system, components and responsive rules.

## Upload size limit (30 MB)

Photos may be up to **30 MB** (`UPLOAD_MAX_KB=30720` in `.env`; default in `config/ccdi.php`). PHP enforces its own limits *before* Laravel runs, so they must be raised too. In `php.ini` (XAMPP: Control Panel -> Apache -> Config -> `php.ini`; find it with `php --ini`) set:

```
upload_max_filesize = 32M
post_max_size = 40M
memory_limit = 512M
max_execution_time = 120
```

Then restart Apache (or stop and re-run `php artisan serve`). `public/.htaccess` and `public/.user.ini` also set these for Apache/mod_php and PHP-FPM hosts. Uploaded photos are re-encoded and shrunk to 2048 px, so stored files stay small.

## 6. Roles and access

| | Guest | Student/Teacher/Staff | Administrator |
|---|---|---|---|
| Browse/search published reports | ✔ | ✔ | ✔ |
| See found-item photos | – | ✔ (signed in) | ✔ |
| Report lost/found, claim, profile, notifications | – | ✔ | ✔ |
| See own private details & potential matches | – | ✔ (own reports) | ✔ (all) |
| Publish/reject reports, review matches, approve/reject claims, record release, manage users, audit log | – | – | ✔ |

All of this is enforced on the server (middleware, policies and checks inside the services), not only by hiding links. An administrator cannot
review or release their own claim, cannot demote themselves, and the last active administrator cannot be removed.
"Staff" in the role list means a school staff *member*, not an administrator.

## 7. Tests

```bash
php artisan test                         # Windows: set MATCHER_PYTHON first if `python` is not on PATH
```

Result on my verification run: **100 tests, 512 assertions, all passed** – on SQLite and again on MariaDB 10.11.
The 19 image-matching tests drive the real OpenCV worker and are **skipped** (not faked) if Python/OpenCV/scikit-image are missing.
They cover: registration/login/logout/rate limit, guest and role restrictions, report creation and validation, invalid / oversized /
disguised uploads, text search and filters, SIFT success, ORB fallback, no-match, corrupt images, worker failure, claims, unauthorized
approval, approval/rejection, release, other users' records and dashboard statistics. Beyond the automated suite I ran a 17-step
end-to-end journey over HTTP (register → report → publish → match → claim → approve → release) against MariaDB.

## 8. Known limitations / not done

* **Not verified by me:** XAMPP/Windows itself, PHP 8.3 with re-resolved dependencies, and how the pages *look* in a browser
  at phone/tablet/desktop widths (no browser was available to me; layouts use responsive Tailwind classes but need your eyes).
* Matching runs synchronously inside the request (simple for a student demo, up to `MATCHER_TIMEOUT_SECONDS`). Fine for dozens of
  reports; a queue worker would be needed for large volumes.
* Comparison scans up to 200 most-recent candidates per run; descriptors are cached but pairs are re-compared on each run.
* No e-mail notifications (in-app notifications only), no password-reset flow, no e-mail verification, and no editing of a
  submitted report by its owner. Accounts are not subject to an institutional approval step because the thesis does not define one.
* Thresholds are unvalidated defaults (section 5).
* Image-matching accuracy testing for the thesis (Chapter 3) still has to be done with real photographs.
