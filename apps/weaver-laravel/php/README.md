<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

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

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Project Setup Flow (Iteration 1)

Email/password registration logs a user in and redirects them to `/project/setup` if they have no project yet. Once a project is created they are sent to `/dashboard` on subsequent logins. Editing the project is available at `/project/edit`.

Fields stored (non-secret): name, owner, repo, GitHub App ID, GitHub App Client ID.

### Local Testing Quick Start

1. Bring up containers:
   ```bash
   docker compose up --build
   ```
2. Ensure SQLite exists (default created at `database/database.sqlite`).
3. Visit `http://localhost:8080`, register, complete Project Setup form.
4. You will be redirected to Dashboard.
5. Use Edit Project link to modify details.

### Local Auth (Email/Password)
1. Navigate to http://localhost:8080/register to create an account.
2. After registration/login you'll be redirected to Project Setup if no project exists, else Dashboard.
3. Use /login for returning users. Protected routes redirect to /login when unauthenticated.

Social login buttons are placeholders (disabled) in this iteration.

### Dev Database (SQLite First)

Default dev DB uses SQLite at `storage/database/database.sqlite`.

Inside the running php container run:
```bash
php artisan weaver:dev:prepare-db
```
Fresh rebuild:
```bash
php artisan weaver:dev:prepare-db --fresh
```
Switch to MySQL: update `.env` to `DB_CONNECTION=mysql` and set credentials for the `weaver-db` service.

### Onboarding Middleware

Authenticated users without a Project trying to access protected routes (dashboard, project edit) are redirected to `/project/setup`. Once a project exists, visiting `/project/setup` redirects to `/dashboard`. Middleware: `EnsureProjectOrRedirect` applied to the authenticated route group in `routes/web.php`.

### Run & Use (Iteration 1)

1. Start containers:
   ```bash
   docker compose up --build
   ```
2. Prepare database (inside php container):
   ```bash
   docker compose exec weaver-php php artisan weaver:dev:prepare-db
   ```
3. Visit http://localhost:8080, register, complete Project Setup form.
4. Land on Dashboard with project summary.
5. Edit project anytime via nav Project link.
6. Emails (if reset enabled) logged via MAIL_MAILER=log.

### Testing

Run inside the PHP container:
```bash
php artisan test
```
Covers onboarding redirect, project creation, validation, and project access control.

### Secrets & GitHub App

Private keys are never stored in the database. Supply the GitHub App private key via Docker secret file or inline env.

Preferred (Docker secret mount):
```
GITHUB_APP_PRIVATE_KEY_PATH=/run/secrets/github_app_private_key
```

Fallback (inline, escaped newlines):
```
GITHUB_APP_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----"
```

Identifiers:
```
GITHUB_APP_ID=123456
GITHUB_APP_CLIENT_ID=Ivabc123...
```

Webhook secret (if using webhooks locally):
```
GITHUB_WEBHOOK_SECRET=change_me
```

### GitHub App Connectivity Smoke Test

Local-only verification that the App JWT works, the installation is resolved, and an installation token can be minted (token value NOT shown).

Run inside container:
```
docker compose exec weaver-php php artisan weaver:github:verify <owner> <repo>
```

Example success output (values truncated):
```
{"ok":true,"installation_id":12345678,"token_expires_at":"2025-08-17T12:34:56Z"}
```

Example failure when installation missing:
```
{"ok":false,"reason":"Installation not found for repository (ensure App is installed)."}
```

The command is gated to the local environment and never prints secrets or tokens.

### Smoke Command

Run a one-command JSON environment sanity check.

Base:
```bash
docker compose exec weaver-php php artisan weaver:smoke
```

With demo user (demo@local.test / password) creation or reuse:
```bash
docker compose exec weaver-php php artisan weaver:smoke --demo
```

Example healthy output:
```
{"ok":true,"app_url":"http://localhost","env":"local","db":{"driver":"sqlite","reachable":true},"migrations_ok":true,"counts":{"users":1,"projects":0},"routes":{"/login":true,"/register":true,"/dashboard":true,"/project/setup":true},"demo_user":{"created":true,"email":"demo@local.test","password_hint":"password","next":"/project/setup"}}
```

If not local:
```
{"ok":false,"reason":"environment not local","env":"production"}
```

### Local Smoke Test (Register → Setup Project → Dashboard)

Approx 2–3 minute verification inside Docker.

Checklist:
1. Start stack (build + up):
   ```bash
   docker compose up --build
   ```
   Expect: containers start; web listens on :8080.
2. Prepare DB (inside PHP container):
   ```bash
   docker compose exec weaver-php php artisan weaver:dev:prepare-db
   ```
   Expect first run: line similar to
   `Created SQLite database file: /var/www/html/database/database.sqlite` (or `SQLite database file already exists.`) followed by migration lines (`Migrating:` and `Migrated:`) ending with success.
3. (Optional sanity tests):
   ```bash
   docker compose exec weaver-php php artisan test
   ```
   Expect all tests pass.
4. Open app in browser: http://localhost:8080
5. Click Register; submit email + password.
   Expect redirect to `/project/setup`.
6. Fill Project form fields (Project Name, Owner, Repo, GitHub App ID, GitHub App Client ID) and click Save.
   Expect success flash banner and redirect to `/dashboard` showing those saved values.
7. Logout (nav Logout), then login again.
   Expect landing directly on `/dashboard` with identical project data (no redirect to setup).

### Troubleshooting

Container logs (web server & php-fpm/app):
```bash
docker compose logs -f weaver-web
docker compose logs -f weaver-php
```

DB config (.env inside container) should include:
```bash
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/storage/database/database.sqlite
```

DB file exists:
```bash
docker compose exec weaver-php ls -l storage/database
```

SQLite extension loaded:
```bash
docker compose exec weaver-php php -m | grep -i sqlite
```

Migration status:
```bash
docker compose exec weaver-php php artisan migrate:status
```

CSRF/token mismatch? Ensure you’re using the exact URL `http://localhost:8080` (not https://, not another port/hostname).

Redirect loop (dashboard → setup)? Verify a project row exists for your user:
```bash
docker compose exec weaver-php php artisan tinker
>>> App\Models\User::first()->project()->first()
```
Should return a Project model, not null.

Permissions (write failures to storage):
```bash
docker compose exec weaver-php chmod -R 775 storage bootstrap/cache
```

