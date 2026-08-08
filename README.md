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

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Local environment (Docker)

This project ships a Docker Compose setup for local development — no manual service installation required.

**Services:** `nginx` (web server, port `8000`), `app` (PHP-FPM 8.5), `worker` (Horizon queue worker), `reverb` (WebSocket server, port `8081`), `postgres` (with `pgvector`), `redis`, `minio` (S3-compatible storage, API on port `9002`, console on port `9091`).

### First-time setup

```bash
cp .env.example .env
docker compose up -d --build
```

That's it. On first boot the `app` container will:

1. Install Composer dependencies if `vendor/` is missing.
2. Generate `APP_KEY` if it's not already set.
3. Run pending migrations.

Once every container reports healthy, the app is available at http://localhost:8000 (health check: http://localhost:8000/up), the Reverb WebSocket server at `localhost:8081`, and the MinIO console at http://localhost:9091.

### Everyday use

```bash
docker compose up -d       # start everything in the background
docker compose logs -f app # tail a service's logs
docker compose exec app php artisan migrate   # run artisan commands inside the app container
docker compose down        # stop everything (add -v to also wipe volumes/data)
```

Database, Redis, and MinIO data persist across restarts in named Docker volumes.

### Makefile shortcuts

A `Makefile` wraps the commands above so you don't have to type `docker compose` for everyday tasks. Run `make help` for the full list — highlights: `make up`, `make down`, `make down-v`, `make exec-app` (shell into the app container), `make artisan cmd="..."`, `make migrate`, `make seed`, `make fresh` (migrate:fresh + seed), `make test`, `make pint`.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
