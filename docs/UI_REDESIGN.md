# CCDI Lost & Found - UI/UX redesign notes

Backend, routes, models, validation, policies and the image matcher are **unchanged**. Only Blade views, CSS, JS and a few
configuration values for photo uploads were touched.

## Design system (`resources/css/app.css`)
* **Colour** - navy `#172B50` (primary, `navy-800`), deep navy `#10203F` (`navy-900`), secondary blue `#244781` (`navy-600`),
  CCDI red `#D92735` (`red-500`, used only as an accent), neutrals `#17243A` / `#66748A` / `#DFE5EE`. The default Tailwind red
  and slate scales are replaced by these values, so every existing utility is on-brand. The old teal accent is gone.
* **Logo** - the official file is `public/images/ccdi-logo.png`, rendered by `<x-brand>` (height only, never stretched or recoloured).
* **Type** - offline-safe stack (Segoe UI Variable / Segoe UI / system-ui). No web font is downloaded.
* **Buttons** - `.btn` + `-primary` (navy with a red bottom edge), `-secondary` (navy outline), `-accent` (red, hero CTA only),
  `-success`, `-danger` (red outline, fills on hover), `-ghost`, `-on-dark`; sizes `-sm`, `-lg`, `-block`.
  44px touch targets on touch devices, visible focus ring, disabled and loading (`aria-busy`) states.
* **Components** - `x-page-header`, `x-stat`, `x-status-badge` (outlined tags for Lost/Found, dotted badges for workflow
  status), `x-alerts`, `x-empty-state`, `x-item-photo`, `x-item-card`, `x-match-evidence`, `x-brand`; CSS classes `.card`,
  `.input`, `.label`, `.callout`, `.data-table`, `.dropzone`, `.panel-head`, `.kicker`.
* **Layouts** - `layouts/app` (public pages: top bar + header + footer), `layouts/shell` (sidebar dashboard shell) wrapped by
  `layouts/member` and `layouts/admin`.

## Responsive behaviour
* Public header collapses to a menu panel below 1024px; the dashboard sidebar becomes a slide-in drawer (overlay, Esc,
  closes on navigation, body scroll lock).
* `.table-stack` tables turn into labelled cards whenever **their container** is narrower than 760px (CSS container query),
  so they also adapt next to the sidebar on 1024px laptops. Nothing is hidden.
* Forms stack to one column on phones; filters collapse into a 2-column grid; item cards are 1 / 2 / 3 / 4 columns.

## Image matching
* Upload zone with drag & drop, preview, file name/size, Replace and Remove, client-side type/size check, loading state on submit.
* `x-match-evidence` shows the algorithm, the three real values stored by the matcher, and states that a match is a
  **suggestion only, not a percentage, not proof**. Lost/found candidates are shown side by side.

## Photo upload limit (30 MB)
`config/ccdi.php` (`UPLOAD_MAX_KB`, default 30720), `StoreReportRequest`, `ImageStorage` (pixel cap + memory guard),
`public/.htaccess`, `public/.user.ini`, a friendly `errors/413.blade.php`, and README section "Upload size limit".
**PHP itself must also allow it** (`upload_max_filesize`, `post_max_size` in php.ini).

## Rebuilding the frontend
```
npm install
npm run build        # or: npm run dev
```
