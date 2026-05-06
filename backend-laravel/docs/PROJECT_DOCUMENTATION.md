# AI3 Backend — Project Documentation

This document summarizes the backend API and architecture for the AI3 project (Laravel). It covers: quick overview, installation, environment variables, architecture & key files, API reference (public, authenticated, admin), service integrations, and examples.

**Status**: Draft — core documentation created automatically from codebase scan.

---

## Quick Overview

- Purpose: Laravel backend for an AI-powered design generation service with user accounts, design previews, saved designs, ordering, coupons, notifications, and an admin panel.
- Main responsibilities:
    - Accept user prompts and request previews from an external AI service
    - Persist user designs (Spatie Media Library)
    - Handle orders and coupons with transactional safety
    - Deliver user notifications and admin management APIs

## Requirements

- PHP ^8.2 (see `composer.json`)
- Composer
- A supported database (MySQL, Postgres, SQLite)
- Node.js + npm (for Vite assets)
- Spatie Media Library requirements (filesystem + `php-gd` or `imagick` for image processing)

## Quick Setup

Run the recommended script in `composer.json` or follow the steps below.

Bash (Linux / macOS):

```bash
git clone <repo>
cd backend-laravel
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --force
php artisan storage:link
npm install
npm run build
```

Windows (PowerShell):

```powershell
git clone <repo>
cd backend-laravel
composer install
copy .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --force
php artisan storage:link
npm install
npm run build
```

You can also run the composer scripted `setup` defined in `composer.json`:

```bash
composer run setup
```

## Environment variables (important)

- `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `JWT_SECRET` (run `php artisan jwt:secret` to generate)
- `JWT_ALGO`, `JWT_TTL`, `JWT_REFRESH_TTL` (see `config/jwt.php`)
- AI service endpoint: recommended env key `AI_SERVICE_URL` (used by `config/services.php` if added). Default used by the code is `http://127.0.0.1:8002`.
- Mail & third-party keys: `POSTMARK_API_KEY`, `RESEND_API_KEY`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `SLACK_BOT_USER_OAUTH_TOKEN`
- Filesystem/storage driver as required by Spatie Media Library.

Notes:

- `config/jwt.php` controls the JWT library settings. It expects `JWT_SECRET` for symmetric algorithms.
- If you use asymmetric JWT signing, set `JWT_PUBLIC_KEY` / `JWT_PRIVATE_KEY` and `JWT_ALGO` accordingly.

## Architecture & Key Files

- Routes: [routes/api.php](routes/api.php)
- Customer controllers: [app/Http/Controllers/Customer](app/Http/Controllers/Customer)
- Admin controllers: [app/Http/Controllers/Admin](app/Http/Controllers/Admin)
- Services: [app/Services/AiService.php](app/Services/AiService.php), [app/Services/DesignService.php](app/Services/DesignService.php), [app/Services/OrderService.php](app/Services/OrderService.php), [app/Services/CouponService.php](app/Services/CouponService.php), [app/Services/CustomerNotificationService.php](app/Services/CustomerNotificationService.php)
- Models: [app/Models/User.php](app/Models/User.php), [app/Models/Design.php](app/Models/Design.php), [app/Models/Order.php](app/Models/Order.php), [app/Models/Coupon.php](app/Models/Coupon.php), [app/Models/CouponUsage.php](app/Models/CouponUsage.php), [app/Models/ShowcaseDesign.php](app/Models/ShowcaseDesign.php)

### Notable libraries (from `composer.json`)

- `php-open-source-saver/jwt-auth` — JWT authentication
- `spatie/laravel-medialibrary` — storing design images
- `knuckleswtf/scribe` — API documentation generation

## API Reference (summary)

All API endpoints are defined in [routes/api.php](routes/api.php).

Base URL (example): `https://api.example.com/api`

Authentication: JWT tokens in `Authorization: Bearer <token>` header.

### Public (no auth)

- POST `/register` — `AuthController@register` — Create a new user (email, name, password)
- POST `/login` — `AuthController@login` — Authenticate and return JWT token
- GET `/showcase` — `ShowcaseController@index` — List showcase designs
- POST `/oauth/google` — `OAuthController@google` — Google OAuth endpoint
- GET `/ping` — health-check (returns `{ status: 'ok' }`)

### Authenticated user endpoints (middleware `auth:api`)

- GET `/me` — `AuthController@me` — Current authenticated user
- POST `/logout` — `AuthController@logout` — Invalidate current token
- POST `/refresh` — `AuthController@refresh` — Refresh JWT
- GET `/user/designs-quota` — `UserController@designsQuota` — Return remaining quota info
- GET `/user/designs` — `UserController@myDesigns` — List saved designs

Designs:

- POST `/designs/enhance-prompt` — `DesignController@enhancePrompt` — Enhance a prompt (AI helper)
- POST `/designs/preview` — `DesignController@preview` — Generate a preview image (calls AI service)
- POST `/designs/save` — `DesignController@save` — Save a design (stores base64 image via Spatie Media Library)
- GET `/designs` — `DesignController@index` — List user's designs
- PUT `/designs/{id}/favorite` — `DesignController@toggleFavorite` — Toggle favorite design
- DELETE `/designs/{id}` — `DesignController@destroy` — Delete a design

Coupons & Orders:

- POST `/coupons/validate` — `CouponController@validateCode` — Validate a coupon code
- POST `/orders/create` — `OrderController@store` — Create an order (accepts `design_id`, optional `coupon_code`)
- GET `/orders/my-orders` — `OrderController@index` — List user's orders

Notifications:

- GET `/notifications` — list notifications
- GET `/notifications/unread-count` — unread count
- PUT `/notifications/mark-all-read` — mark all as read
- PUT `/notifications/{id}/read` — mark single notification as read
- DELETE `/notifications/{id}` — delete notification

### Admin endpoints (`/admin` prefix, middlewares `auth:api` + `is_admin`)

- GET `/admin/stats` — `DashboardController@stats` — Site statistics
- Users: GET `/admin/users`, PUT `/admin/users/{id}/designs-limit`, DELETE `/admin/users/{id}`
- Orders: GET `/admin/orders`, PUT `/admin/orders/{id}/status`
- Designs: GET `/admin/designs`, DELETE `/admin/designs/{id}`
- Showcase designs: GET/POST/PUT/DELETE `/admin/showcase-designs` and PUT `/admin/showcase-designs/{id}/toggle-featured`
- Coupons: resource routes under `/admin/coupons` plus custom `/admin/coupons-stats` and `/admin/coupons/{id}/usage`

## Service integrations

- `AiService` ([app/Services/AiService.php](app/Services/AiService.php))
    - Sends `POST` to `{AI_BASE_URL}/generate` with JSON `{ prompt: string }`.
    - Expects a JSON response with key `image_base64` containing the generated image as a base64 string.
    - Default base URL (if not configured) is `http://127.0.0.1:8002`.

- `DesignService` ([app/Services/DesignService.php](app/Services/DesignService.php))
    - Uses `AiService` to generate previews, enforces user quotas, and persists designs using Spatie Media Library.

- `OrderService` ([app/Services/OrderService.php](app/Services/OrderService.php))
    - Handles order creation in a DB transaction, coupon validation, discount calc, and user notification (`OrderCreatedNotification`).

- `CouponService` ([app/Services/CouponService.php](app/Services/CouponService.php))
    - Validates coupon code logic: active flag, expiry, usage limits, and per-user usage.

- `CustomerNotificationService` ([app/Services/CustomerNotificationService.php](app/Services/CustomerNotificationService.php))
    - Lists, counts, marks, and deletes notifications in a pageable manner.

## Models & important fields

- `User` (`app/Models/User.php`): `name`, `email`, `password`, `is_admin`, `designs_limit`, `designs_used`, `is_unlimited`.
- `Design` (`app/Models/Design.php`): `id (UUID)`, `user_id`, `prompt`, `image_url`, `ai_settings`, `is_completed`.
- `Order` (`app/Models/Order.php`): `user_id`, `design_id`, `coupon_id`, `total_amount`, `final_amount`, `status`, `payment_id`.
- `Coupon` (`app/Models/Coupon.php`): `code`, `discount_percentage`, `is_active`, `max_uses`, `current_uses`, `valid_until`.
- `CouponUsage` (`app/Models/CouponUsage.php`): mapping between `user_id`, `coupon_id`, `order_id`.
- `ShowcaseDesign` (`app/Models/ShowcaseDesign.php`): `design_id`, `title`, `display_order`, `is_active`.

## Examples

### Authentication (login)

```bash
curl -X POST https://api.example.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret"}'
```

Response contains `access_token` (JWT). Use it for subsequent requests:

```
Authorization: Bearer <token>
```

### Generate design preview (authenticated)

```bash
curl -X POST https://api.example.com/api/designs/preview \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"prompt":"Minimalist logo, blue and white, vector-style"}'
```

Response: JSON with base64 image (field depends on controller; service returns `image_base64`).

### Save a design

```bash
curl -X POST https://api.example.com/api/designs/save \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"prompt":"...","image_base64":"<BASE64_STRING>"}'
```

### Create order

```bash
curl -X POST https://api.example.com/api/orders/create \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"design_id":"<DESIGN_ID>", "coupon_code":"SAVE10"}'
```

## Testing

Run the test suite with:

```bash
composer run test
# or
php artisan test
```

## Developer notes & next steps

- AI service: ensure the AI server is reachable at `AI_SERVICE_URL` or update `config/services.php` to include `ai.url` and deploy config.
- To generate API docs programmatically, `knuckleswtf/scribe` is installed; check project usage if automated docs are desired.
- Consider adding `ai` configuration to `config/services.php` for clarity:

```php
'ai' => [
  'url' => env('AI_SERVICE_URL', 'http://127.0.0.1:8002'),
],
```

## Where to look in the code

- Routes and API surface: [routes/api.php](routes/api.php)
- Services: [app/Services](app/Services)
- Models: [app/Models](app/Models)
- Controllers: [app/Http/Controllers](app/Http/Controllers)

---

If you want, I can:

- Generate a shorter `README.md` in the repo root replacing the generic Laravel README
- Produce a machine-readable OpenAPI spec (using `scribe` or by hand)
- Add example Postman collection or Postman export

Tell me which of the above you'd like next.
