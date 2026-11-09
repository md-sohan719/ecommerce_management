# Small E-commerce Management

A small e-commerce management system built with **Laravel 12** (REST API) and **Vue 3** (single-page app).

- **Admins** manage shops, the product catalog and shop owner accounts.
- **Shop owners** pick products from a **shared product catalog** for their shops. They set their own price, details and thumbnail per shop, and can add new products to the catalog.
- **Customers** browse the storefront. They only see products that are assigned to an (active) shop, with each shop's own price, details and thumbnail.

---

## Tech stack

| Layer    | Technology                                                       |
| -------- | ---------------------------------------------------------------- |
| Backend  | PHP 8.2+, Laravel 12, Laravel Sanctum (API token auth)           |
| Database | MySQL 8 (MySQL 5.7+ / MariaDB 10.3+ also work)                    |
| Frontend | Vue 3 (Composition API), Vue Router 4, Axios, Tailwind CSS 4     |
| Build    | Vite 7 (via `laravel-vite-plugin`)                               |
| Tests    | PHPUnit feature tests (30 tests)                                 |

---

## Requirements

- **PHP 8.2 or newer** with these extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`. Tests also need `pdo_sqlite` and `gd`.
- **Composer 2**
- **Node.js 20.19+ (or 22.12+)** and **npm**
- **MySQL** server

> Laragon, XAMPP, Herd or any LAMP/WAMP stack works. On **Laragon**, MySQL already runs with user `root` and an empty password, which matches `.env.example`.

---

## Setup (step by step)

### 1. Clone the repository

```bash
git clone <repository-url> ecommerce_management
cd ecommerce_management
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Create the environment file and app key

```bash
# Linux / macOS / Git Bash
cp .env.example .env

# Windows (cmd / PowerShell)
copy .env.example .env
```

```bash
php artisan key:generate
```

### 4. Create the MySQL database

Create an empty database, for example with the MySQL CLI, HeidiSQL or phpMyAdmin:

```sql
CREATE DATABASE ecommerce_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then check the database settings in `.env`. These are the defaults:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce_management
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Run migrations and seed demo data

```bash
php artisan migrate --seed
```

This creates all tables and loads the demo data: users, shops, products, shop listings and generated product thumbnails.

### 6. Install and build the frontend

```bash
npm install
npm run build
```

### 7. Start the application

```bash
php artisan serve
```

Open **http://127.0.0.1:8000**.

> **No `php artisan storage:link` is needed.** Uploaded thumbnails are served through the `/media/...` route, so the project also works on Windows without symlink permissions.

#### One-command alternative

After you create the database (step 4), `composer setup` runs steps 2–6 in one go: install, `.env`, key, `migrate:fresh --seed`, `npm install` and build. Then run `php artisan serve`.

#### Development mode (hot reload)

Instead of `npm run build`, run the Vite dev server next to `php artisan serve`:

```bash
php artisan serve      # terminal 1
npm run dev            # terminal 2
```

`composer dev` starts both together.

---

## Demo accounts

All accounts use the password **`password`**. The login page also has quick-fill buttons for them.

| Role        | Email                | Notes                                                       |
| ----------- | -------------------- | ----------------------------------------------------------- |
| Admin       | `admin@example.com`  | Full admin panel                                            |
| Shop owner  | `rahim@example.com`  | Owns **Gadget House** and **Urban Threads** (shop switcher) |
| Shop owner  | `nusrat@example.com` | Owns **Green Leaf Grocery**                                 |
| Shop owner  | `karim@example.com`  | Owns **Daily Needs Store** (sells products created by the admin *and* by other owners) |
| Shop owner  | `sadia@example.com`  | No shop assigned yet                                        |

The demo data covers every rule in the task:

- Products created by the **admin** and by **shop owners** (Gaming Mouse by Rahim, Mango Pickle by Nusrat, LED Bulb by Karim), all in the shared catalog.
- The **same product sold by several shops at different prices**, for example the Power Bank at Gadget House (৳1,750) and at Daily Needs Store (৳1,850).
- **Shop-specific title, details and thumbnail**, for example *Wireless Earbuds Pro* at Gadget House and *Sundarbans Raw Honey* at Green Leaf Grocery.
- **Products not assigned to any shop** (*Running Sneakers*, *Ceramic Coffee Mug*). Customers never see them.
- A **hidden listing** (Bluetooth Speaker at Daily Needs Store) and an **inactive shop** (Old Town Bazaar). Customers don't see these either.

---

## Features

### Admin panel (`/admin`)

- Login with an API token (Laravel Sanctum).
- **Dashboard** with counts (shops, products, unlisted products, listings, owners).
- **Shops CRUD**: name, owner, address, phone, description, active/inactive. The slug is generated automatically.
- **Products CRUD** for the shared catalog: name, SKU, description, base price and thumbnail upload, replace or remove. You can filter by creator (admin or shop owners) and by status (in a shop / not in any shop).
- **Shop owner accounts**: create, edit (leave the password empty to keep it) and delete. A deleted owner's shops are kept without an owner so they can be reassigned.

### Shop owner panel (`/owner`)

- **My shop products**: the products listed in the selected shop. You can:
  - edit the **price**, **title**, **details** and **thumbnail** for this shop. An empty field falls back to the catalog value.
  - show or hide a product for customers, or remove it from the shop. The catalog product stays.
- **Product catalog**: browse the shared catalog (all products from the admin and every shop owner), with search and these tabs: *All*, *Not in my shop*, *Created by me*.
  - **Add to my shop**: choose your own price, title, details and thumbnail.
  - **Create new product**: it joins the shared catalog, so other owners can sell it, and can be added to your shop straight away.
  - Edit or delete **your own** products. You can't delete a product that other shops are still selling.
- **Shop switcher** for owners with more than one shop.

### Customer storefront (`/`)

- No login needed.
- Home page with all active shops and a searchable, sortable, paginated product grid across shops.
- Shop page with that shop's products (search, sort by newest or price).
- Product page with the **shop-specific** name, price, details and thumbnail, plus **"Also available at"** listing other shops and their prices.
- Only products assigned to an **active shop**, with an **active listing**, are ever returned. A product without a shop is not reachable through any public endpoint.

---

## How it works (data model)

```
users ──< shops ──< shop_products >── products
 (role)   (owner_id)   (the listing)    (shared catalog, created_by → users)
```

| Table           | Purpose |
| --------------- | ------- |
| `users`         | Admins and shop owners (`role` = `admin` \| `shop_owner`). |
| `shops`         | A shop, optionally owned by a shop owner (`owner_id`), with `is_active`. |
| `products`      | The **shared catalog**. `created_by` records whether the admin or a shop owner created it. `base_price` is the suggested price. |
| `shop_products` | A **listing**: a product assigned to a shop, with the shop's `price` and optional overrides `title`, `description` and `thumbnail`, plus `is_active`. `UNIQUE(shop_id, product_id)`. |

**Customer visibility rule:** every storefront query starts from `shop_products`, filtered with `ShopProduct::visible()` (active listing in an active shop). A product without a listing therefore can't appear (see `app/Http/Controllers/Api/Storefront`).

**Authorization**

- `role:admin` / `role:shop_owner` middleware protects each panel (`EnsureUserHasRole`).
- `ShopPolicy::manage`: an owner can only manage listings of shops they own. Nested routes use **scoped bindings**, so `/owner/shops/{shop}/listings/{listing}` returns 404 if the listing belongs to another shop.
- `ProductPolicy`: admins can edit or delete any product. Owners can only edit or delete products they created, and can't delete them while other shops still sell them.

---

## API overview

All endpoints are under `/api` and return JSON. Authenticated endpoints need `Authorization: Bearer <token>` (from `POST /api/login`).

| Method | Endpoint | Access | Description |
| ------ | -------- | ------ | ----------- |
| POST | `/login` | public | Get an API token |
| GET  | `/me`, POST `/logout` | auth | Current user / revoke the token |
| GET  | `/storefront/shops` | public | Active shops |
| GET  | `/storefront/shops/{slug}` | public | Shop details |
| GET  | `/storefront/shops/{slug}/products` | public | Visible products of a shop (`search`, `sort`, `page`) |
| GET  | `/storefront/shops/{slug}/products/{productId}` | public | Product in a shop + other shops selling it |
| GET  | `/storefront/products` | public | Visible products across all shops |
| GET  | `/admin/stats` | admin | Dashboard counts |
| CRUD | `/admin/shops` | admin | Shops |
| CRUD | `/admin/products` | admin | Catalog products (multipart for thumbnails) |
| CRUD | `/admin/owners` | admin | Shop owner accounts |
| GET  | `/admin/owner-options` | admin | Owners for select inputs |
| GET  | `/owner/shops` | shop owner | My shops |
| GET  | `/owner/catalog` | shop owner | Shared catalog (`search`, `shop_id`, `availability=not_listed`, `mine=1`) |
| POST/PUT/DELETE | `/owner/products[/{id}]` | shop owner | Create a catalog product (optionally list it in my shop) / edit / delete my own |
| GET/POST | `/owner/shops/{shop}/listings` | shop owner | List / add products in my shop |
| PUT/DELETE | `/owner/shops/{shop}/listings/{listing}` | shop owner | Update price, details, thumbnail, visibility / remove |

File uploads use `multipart/form-data`. For updates, send `POST` with `_method=PUT` (Laravel method spoofing).

---

## Running the tests

```bash
php artisan test
```

The feature tests use an in-memory **SQLite** database (see `phpunit.xml`), so your MySQL data is never touched. This needs the `pdo_sqlite` PHP extension. To run the tests on MySQL instead, change `DB_CONNECTION` / `DB_DATABASE` in `phpunit.xml` to a separate, empty test database.

The tests cover authentication and roles, admin CRUD, the shared catalog, owner authorization (including cross-shop access attempts) and the customer visibility rules.

---

## Project structure (main parts)

```
app/
  Http/Controllers/Api/
    AuthController.php
    Admin/       DashboardController, ShopController, ProductController, ShopOwnerController
    Owner/       ShopController, CatalogController, ProductController, ListingController
    Storefront/  ShopController, ProductController
  Http/Controllers/MediaController.php   serves uploaded thumbnails (/media/...)
  Http/Middleware/EnsureUserHasRole.php
  Http/Requests/   ShopRequest, ProductRequest, ListingRequest, ShopOwnerRequest
  Http/Resources/  User/Shop/Product/Listing/StorefrontProduct resources
  Models/          User, Shop, Product, ShopProduct
  Policies/        ShopPolicy, ProductPolicy
  Services/ProductService.php
  Support/Media.php
database/
  migrations/  factories/  seeders/DatabaseSeeder.php
resources/js/          Vue 3 SPA
  router.js  lib/ (api, auth, ownerShops, useList, ...)
  layouts/   StoreLayout, DashboardLayout
  pages/     store/*, admin/*, owner/*, Login.vue
  components/ ProductForm, ListingForm, ImageInput, Modal, Pagination, ...
routes/api.php  routes/web.php
tests/Feature/
```

---

## Troubleshooting

| Problem | Fix |
| ------- | --- |
| `Vite manifest not found` | Run `npm install && npm run build`, or keep `npm run dev` running. |
| `SQLSTATE[HY000] [2002]` / `[1049] Unknown database` | Start MySQL, create the database (step 4) and check the `DB_*` values in `.env`. |
| `No application encryption key` | `php artisan key:generate` |
| Thumbnails missing after a reset | Re-run the seeder: `php artisan migrate:fresh --seed` (this regenerates demo images). |
| Changed `.env` but nothing happens | `php artisan config:clear` |
| Reset everything to the demo state | `php artisan migrate:fresh --seed` |
