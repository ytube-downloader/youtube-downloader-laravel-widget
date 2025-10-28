# YouTube Downloader Laravel Widget Template

Laravel Widget Template delivers a modern Laravel 12 stack for building widget-driven media download experiences with a queued workflow, RESTful API, and Vite-powered front end.

## ℹ️ Project Highlights

- 🎯 Streamlined controllers and services for handling media download requests through a clean API layer.
- 🗂️ Tailwind-ready layout scaffolding designed for widget-style galleries and dashboard experiences.
- ⚡ Vite-powered asset pipeline with sensible defaults for extending JavaScript and CSS modules.

## 🖥️ System Requirements

- 🐘 PHP 8.2 or newer with the required PHP extensions enabled (bcmath, ctype, fileinfo, json, mbstring, openssl, pdo, tokenizer, xml).
- 🧰 Composer 2.6+ for managing PHP dependencies.
- 🧪 Node.js 20+ and npm 10+ (or pnpm/yarn equivalents) for compiling front-end assets.
- 🗄️ Database server such as MySQL 8+, MariaDB 10.11+, PostgreSQL 14+, or SQLite for local development.
- 🚀 Redis or another queue backend (optional) when running queued downloads in production-like environments.

## ⚙️ Setup Guide

1. 📥 Clone the repository and enter the project directory:
   ```bash
   git clone https://github.com/ytube-downloader/youtube-downloader-laravel-widget
   cd laravel-widget-template
   ```

2. 🔐 Copy the environment template and generate an application key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. 🧭 Update `.env` with your database, queue, and service credentials.
   - Set `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.
   - Configure `QUEUE_CONNECTION` (e.g., `database` during local development).
   - Add the Video Downloader API [https://video-downloader-api.com](https://video-downloader-api.com)

4. 📦 Install PHP dependencies with Composer:
   ```bash
   composer install
   ```

5. 🧶 Install JavaScript dependencies and compile assets:
   ```bash
   npm install
   npm run build   # use npm run dev for hot module reloading during development
   ```

6. 🗃️ Run database migrations (and optionally seed data):
   ```bash
   php artisan migrate
   # php artisan db:seed
   ```

7. 🚀 Start the development servers:
   ```bash
   php artisan serve
   npm run dev
   ```

   Visit http://localhost:8000 while the Vite dev server watches your front-end assets.

## 🧾 Additional Tips

- 🧵 Queue workers: run `php artisan queue:work` to process download jobs asynchronously.
- 🧪 Tests: execute `php artisan test` or `phpunit` to run the automated test suite.
- 🧼 Cache maintenance: use `php artisan optimize:clear` when configuration or route caches need to be rebuilt.