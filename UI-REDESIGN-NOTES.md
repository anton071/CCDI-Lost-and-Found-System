# CCDI Lost & Found UI refresh

This update is limited to presentation and frontend styling.

## What changed
- Integrated the supplied official CCDI logo in the public header, footer, and administrator sidebar.
- Refined the shared color palette toward CCDI navy, white, and restrained red accents.
- Updated shared button, card, input, navigation, title, and table styling.
- Improved mobile button and input sizing, table scrolling behavior, and administrator sidebar behavior on narrow screens.
- Kept existing Blade page content, routes, controllers, models, migrations, authentication, authorization, and matching services unchanged.

## Run locally
1. Copy `.env.example` to `.env` only if you need a fresh local configuration. Keep your existing `.env` file when updating an existing installation.
2. Configure the local MySQL database settings in `.env`.
3. Run `composer install` if dependencies are not already installed.
4. Run `npm install` and `npm run build` to rebuild frontend assets after future CSS changes.
5. Run `php artisan serve` or use the existing XAMPP/Apache setup.

The prebuilt CSS asset has also been updated so the visual changes are available through Laravel's existing Vite production manifest.

## Scope and testing note
No intentional backend or database logic changes were made. Laravel's `php artisan view:cache` could not be completed in the packaging environment because its active PHP CLI does not have the DOM extension (`DOMDocument`). The frontend build could not be regenerated because Vite dependencies were not available after the package installation attempt timed out; the existing compiled CSS asset was updated directly alongside the source CSS. Please run `npm install` and `npm run build` on the development machine to produce a fresh Vite build if desired.
