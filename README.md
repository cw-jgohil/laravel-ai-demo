AI Tag Generator Demo (Laravel + Docker + OpenAI)
================================================

This repository contains a working Laravel 10+ backend demo that automatically generates standardized medical protocol tags with AI. It is tailored for EMS/paramedic workflows and showcases:

- Dockerised local environment (Laravel Sail) with MySQL
- Admin CRUD for treatment protocols
- One-click AI tag generation (pre-save preview, post-save update)
- Customisable AI instruction rules maintained by admins
- Multiple AI providers: mock (offline), OpenRouter, or OpenAI

Features
--------
- **Protocol management:** Create, edit, delete, and list protocols while reviewing tags.
- **Inline AI assist:** Generate or refresh tags before saving via an “Generate Tags with AI” button.
- **Rule engine:** Admins configure AI instructions (e.g., include abbreviations and full forms).
- **Tag storage:** Tags are persisted as JSON and can be manually adjusted.
- **Provider flexibility:** Default mock provider requires no key; swap to OpenAI or OpenRouter when ready.

Getting Started
---------------

### Prerequisites

- Docker Desktop (or a compatible Docker engine)
- Node.js is optional (for Vite/frontend assets if needed)

### Initial setup

```bash
cp .env.example .env
composer install
php artisan key:generate
```

Update `.env` (already scaffolded) as needed:

```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

AI_PROVIDER=mock              # Options: mock | openrouter | openai
OPENAI_MODEL=gpt-3.5-turbo
OPENROUTER_MODEL=openai/gpt-3.5-turbo
```

To use paid providers, also set the relevant API key:

- `OPENAI_API_KEY=sk-...`
- `OPENROUTER_API_KEY=sk-or-v1-...`

### Boot the Docker stack

```bash
./vendor/bin/sail up -d
```

Run migrations and seed demo data (protocols + default AI rules):

```bash
./vendor/bin/sail artisan migrate --seed
```

Visit the app at `http://localhost` (Sail’s Nginx proxy maps to port 80 by default). The admin dashboard lives at `/admin/protocols`.

Daily Usage
-----------

- **List protocols:** `/admin/protocols`
  - View current tags and regenerate them inline.
- **Create / Edit protocols:** buttons on the index page.
  - Use *Generate Tags with AI* before saving to prefill the tag field.
  - Manual adjustments remain editable.
- **Manage AI rules:** `/admin/ai-rules`
  - Update instructions to control how tags are generated (e.g., separate abbreviations, include full forms).
- **Switch providers:** change `AI_PROVIDER` in `.env` and restart Sail if necessary.

Mock Mode (Free / Offline)
--------------------------
The default `AI_PROVIDER=mock` uses heuristic logic to simulate AI behaviour:
- Splits combined abbreviations (e.g., `vf/vt` → `vf`, `vt`)
- Adds known expansions when rules request “full form”
- Works without network access or API keys

Switching to OpenAI or OpenRouter automatically reuses the saved rules when building prompts.

Helpful Commands
----------------

```bash
# Start services
./vendor/bin/sail up -d

# Stop services
./vendor/bin/sail down

# Regenerate tags for a protocol via Tinker (example)
./vendor/bin/sail tinker
>>> app(App\Services\AITagGeneratorService::class)->generateTags('Title', 'Description');

# Run tests (if/when added)
./vendor/bin/sail test
```

Troubleshooting
---------------
- **Database connection errors:** Ensure Sail containers are running and `.env` points to `mysql`.
- **CSRF / 419 errors on AI preview:** Confirm `<meta name="csrf-token">` exists (already included) and you’re using the same domain.
- **OpenAI/OpenRouter errors:** verify API keys and chosen models; check logs at `storage/logs/laravel.log`.

License
-------
This demo inherits the default Laravel MIT license. Use freely in evaluations or as a starting point for your production build.
