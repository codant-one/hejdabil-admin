<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Device authentication

- `POST /api/auth/device-token` requires a JWT and returns a 64-character device token once (HTTP 201). Only its SHA-256 hash is stored; it expires after 90 days.
- `POST /api/auth/device-login` accepts `{"token":"..."}` without a JWT and is limited to five attempts per minute. Invalid, expired, revoked, malformed and orphaned tokens all return `{"message":"invalid_credentials","data":null}` (HTTP 401). Inactive users return `user_inactive` (HTTP 403).
- Its named IP limiter is equivalent to `throttle:5,1` and replaces the global API limiter on this route so the JWT guard does not attempt to parse the device token while resolving a throttle key.
- Successful device logins preserve the normal login response: `success`, `message`, and `data` containing `accessToken`, `token_type`, `user_data`, and `userAbilities`.
- `DEVICE_LOGIN_BYPASSES_2FA` defaults to `false`. Device login requires enabled 2FA even in debug mode unless this flag is `true`; it preserves `2fa-generate` when a secret needs provisioning and `2fa` otherwise. Normal password login retains its existing debug behavior.
- `DELETE /api/auth/device-token` requires a JWT and revokes all device tokens belonging to the authenticated user (HTTP 200).
- Eloquent password updates revoke device tokens before the password is persisted, including password resets and administrative updates.
- The existing `suppliers:delete-account` command deletes device tokens as part of its transactional purge of tables with a `user_id` column. Scheduled deletion alone does not deny device login; definitive deletion removes the user and its tokens.

## Help center

- The authenticated Vue pages are `/dashboard/admin/help-center`, `/dashboard/admin/help-center/category/:label` and `/dashboard/admin/help-center/video/:slug`; video administration is at `/dashboard/admin/help-videos`.
- `GET /api/help-center/categories` returns active categories with at least one active video and their active `videos_count`. `GET /api/help-center/category/{label}` returns the active category and all its active videos without pagination. Category cards link by `label`; video cards link by their stored `slug` and only load thumbnail images.
- `GET /api/help-center/video/{slug}` returns `data.helpVideo` with its category and `data.relatedHelpVideos`: all other active videos in that category, without pagination. Missing/inactive videos and videos in inactive categories return HTTP 404. These help-center actions require `view help-center` or `administrator`.
- The video detail reuses the YouTube/Vimeo/MP4 player, displays the title and plain-text description, and includes breadcrumb/back links and related-video thumbnail links. Desktop uses a main player and side column; mobile uses one column. Video creation and `HelpVideoSeeder` generate stored slugs from titles.
- Video administration follows the category administration layout: global search, status filters, newest-first ordering, selectable page size (10, 20, 30 or 50), a desktop table and mobile expansion panels. Videos can be previewed from the list and live in the create/edit form when a valid provider URL is entered.
- `GET /api/help-videos` returns `data.helpVideos` (paginator), `data.helpVideosTotalCount`, `data.categories` and `data.states`. Each listed video includes its `category`, `state` and normalized `embed_url`. Administration uses `view/create/edit/delete help-videos` permissions; status changes use `GET /api/help-videos/updateState/{id}` with `edit help-videos`.
- The video list category selector shows categories with `state_id = 2` by name and sends `help_category_id`. Clearing it removes the category filter; video status remains an independent filter. Category metadata includes `state_id`, while the editor retains the full category list.
- Create, show, update and status-change responses include `category` and `state` in `data.helpVideo`, so replacing a row in the frontend store preserves the fields required by the table.
- Help video status selectors and validation only allow Inaktiv (`1`) and Aktiv (`2`); other application states are unchanged.
- `GET /api/help-center` only returns videos with `state_id = 2` (Aktiv), matching plans. Filters are `module` (module ID), `search`, `sort` (`newest`, `oldest`, `title_asc`, `title_desc`), `page` and `limit`. Pagination links preserve the query string.
- `/api/help` is an API resource using `view/create/edit/delete help` permissions, or `administrator`. `GET /api/help/updateState/{id}` requires `edit help`. Normal authenticated users can read the help center without administrative permissions.
- Modules are maintained exclusively through `HelpCategorySeeder`, with no CRUD controller, routes or administration screen. The existing seeder uses `name` for the Swedish name and `label` for the internal identifier (for example, `Översikt` / `summary`). `HelpVideoSeeder` looks up modules by `label`. Videos do not use soft deletes because their existing table has no `deleted_at`.
- Video URLs accept HTTP(S) YouTube watch, short and embed links, Vimeo normal and embed links (including unlisted hashes), or direct MP4 URLs. Provider/URL mismatches are rejected. The API includes the normalized `embed_url`.
- Help UI strings are written directly in Swedish, following plans, without i18n translation calls.
- Example seeders are repeatable: the existing module seeder creates fifteen modules, and the video seeder creates three example videos for each of `summary`, `clients`, `billings`, `vehicles`, `agreements` and `documents`. Run `HelpCategorySeeder` before `HelpVideoSeeder`. Their video URLs are placeholders, not actual product tutorials. Existing migrations are unchanged.

Run from the backend directory, with the existing states already seeded:

```shell
php artisan migrate
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=HelpCategorySeeder
php artisan db:seed --class=HelpVideoSeeder
php artisan test tests/Feature/HelpCenterTest.php tests/Feature/PlanActivityTest.php
```

`PermissionSeeder` also synchronizes the existing predefined roles, as it does for plans. Sign in again after changing permissions to refresh the frontend abilities.

The feature tests use SQLite in memory and leave the configured application database untouched. If PDO SQLite is installed but disabled in the CLI configuration, run:

```shell
php -d extension=pdo_sqlite vendor/bin/phpunit tests/Feature/HelpCenterTest.php tests/Feature/PlanActivityTest.php
```

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
