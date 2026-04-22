# Project: Laravel Sakai E-Commerce Admin

## Overview
Modern e-commerce admin panel using:
- Laravel 12 (PHP 8.3+)
- Inertia 2 + Vue 3 (Composition API + `<script setup>`)
- PrimeVue 4 with Sakai template (Aura theme)
- Tailwind CSS 3
- MySQL (local: XAMPP, production: AWS RDS)
- Stripe for payments
- Spatie Laravel Permission (RBAC)
- Spatie Laravel Sluggable

Main modules (in order of priority): Categories → Brands → Products → Cart → Stripe → Orders → Dashboard Analytics

## Architecture

### Backend Layer
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          → Admin CRUD controllers (ProductController, CategoryController, UserController, PermissionController)
│   │   ├── Auth/           → Laravel Breeze auth controllers
│   │   └── Front/          → Storefront controllers (Home, Product, Cart, Checkout, AccountOrder)
│   ├── Middleware/
│   │   └── HandleInertiaRequests.php  → Shared props: auth, flash, cart, ziggy
│   ├── Requests/
│   │   ├── Admin/          → FormRequest validation per resource
│   │   └── Auth/
│   └── Resources/
│       ├── Admin/          → ProductResource, CategoryResource, UserResource
│       └── Front/          → CartResource
├── Helper/
│   └── Cart.php            → Dual storage: DB (auth) + cookie (guest), migration on login
├── Models/                 → 11 models: User, Product, Category, Brand, Order, OrderItem, CartItem, Payment, UserAddress, Permission
└── Providers/
    └── AppServiceProvider.php
```

### Frontend Layer
```
resources/js/
├── Pages/
│   ├── Admin/              → Index, Create, Edit + Partials/Form per module
│   ├── Front/              → Home, ProductDetail, Cart, Checkout, Category, Account/Orders
│   └── Auth/               → Login, Register, Password Reset, Email Verification
├── Components/             → Reusable UI + layout: AppSidebar, AppTopbar, ProductCard, CategoryCard
├── Layouts/                → AdminLayout, FrontLayout, GuestLayout
└── app.js                  → Inertia + Pinia + PrimeVue + Ziggy bootstrap
```

### Database (17 migrations)
- Standard: users, cache, jobs, personal_access_tokens
- Spatie RBAC: roles, permissions, model_has_roles, model_has_permissions, role_has_permissions
- Domain: categories, products, brands, cart_items, user_addresses, orders, order_items, payments
- Audit columns: created_by, updated_by, deleted_by on orders and products

## Core Rules
- Always use Inertia/Vue — never return Blade views
- PHP 8.3+ strict typing — typed properties, return types, parameter types
- Controllers: resource-style (7 methods), use FormRequest for all validation
- Vue: `<script setup>`, `defineProps()`, `defineEmits()`, `useForm()` from @inertiajs/vue3
- Pinia: use for cross-component or persistent state (cart, auth extras)
- PrimeVue: prefer unstyled + Aura preset, Toast for notifications, ConfirmDialog for destructive actions
- Routes: named routes, prefix-grouped, middleware groups (auth, guest, role:admin)
- File uploads: store in `storage/app/public/{categories,brands,products}`, delete old on update
- Soft deletes on all domain models (Category, Product, Brand, Order)
- Slug auto-generation via Spatie Sluggable + unique counter fallback
- Audit fields: fill created_by/updated_by/deleted_by using Auth::id()
- Never commit .env — use .env.example with documented variables
- Never use real Stripe keys in code or comments

## Folder Conventions
- `app/Http/Controllers/Admin/`     → admin resource controllers
- `app/Http/Controllers/Front/`     → storefront controllers
- `app/Http/Requests/Admin/`        → FormRequest classes per resource action
- `app/Http/Resources/Admin/`       → API resource transformers
- `app/Helper/`                     → standalone utility classes (Cart, etc.)
- `resources/js/Pages/Admin/`       → Inertia admin pages
- `resources/js/Pages/Front/`       → Inertia storefront pages
- `resources/js/Components/Admin/`  → reusable PrimeVue admin components
- `resources/js/Components/Front/`  → storefront-specific components
- `resources/js/composables/`       → Vue 3 composables (useCart, useToast, useAuth, etc.)
- `resources/js/stores/`            → Pinia stores (cartStore, authStore)

## Key Models & Relationships
- `User` → hasMany(CartItem), hasMany(UserAddress), hasRoles (Spatie)
- `Category` → hasMany(Product), SoftDeletes, HasSlug
- `Brand` → hasMany(Product), SoftDeletes, HasSlug
- `Product` → belongsTo(Category), belongsTo(Brand), hasMany(OrderItem), hasMany(CartItem), SoftDeletes, HasSlug
- `Order` → hasMany(OrderItem), belongsTo(UserAddress), hasOne(Payment)
- `CartItem` → belongsTo(User), belongsTo(Product)

## Cart System
- `app/Helper/Cart.php` — dual-storage pattern
- Guest: JSON in cookie `cart_items_hghg` (30 days, 2048 bytes max)
- Auth: DB via `cart_items` table
- Login event: `moveCartItemsIntoDb()` migrates cookie → DB
- Count + items shared via `HandleInertiaRequests` on every request

## Payment Flow (Stripe)
1. `POST /checkout` → validate cart → create `Order` + `OrderItem` records (status: pending)
2. Create Stripe Checkout Session → redirect to Stripe hosted page
3. `GET /checkout/success` → update Order + Payment (status: paid) → clear cart
4. `GET /checkout/cancel` → soft-cancel order

## Commands Cheat Sheet
```bash
# Dev
npm run dev
npm run build

# Database
php artisan migrate:fresh --seed
php artisan db:seed --class=RoleSeeder

# Storage
php artisan storage:link

# Testing
php artisan test
php artisan test --filter=ProductCrudTest
php artisan test --coverage

# Code Style
./vendor/bin/pint

# Roles (manual)
php artisan tinker --execute="App\Models\User::first()->assignRole('admin')"
```

## Testing Strategy
- `tests/Feature/Admin/` → CRUD tests per resource (Products 640 lines, Categories)
- Use `RefreshDatabase` trait on all feature tests
- `Storage::fake('public')` for file upload tests
- `actingAs($adminUser)` for authorization tests
- Assert Inertia component + props with `assertInertia()`
- Target: 80%+ coverage on Controllers and Models

## Environment Variables to Document
```
APP_NAME=
APP_ENV=
APP_KEY=
APP_DEBUG=
APP_URL=

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
AWS_URL=

MAIL_MAILER=
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
```

## AWS Deployment Target
- **Compute**: EC2 (t3.small minimum, Auto Scaling Group)
- **Database**: RDS MySQL 8.0 (Multi-AZ for production)
- **Storage**: S3 (replace `public` disk for images)
- **CDN**: CloudFront in front of S3 and ALB
- **Load Balancer**: ALB with HTTPS (ACM certificate)
- **IAM**: Instance role for EC2 → S3 access (no static keys in .env)
- **Secrets**: AWS Secrets Manager or Parameter Store for .env values
- **CI/CD**: GitHub Actions → CodeDeploy or direct EC2 deploy script
- **Process Manager**: Supervisor for queues, PHP-FPM for web
- **Web Server**: Nginx + PHP-FPM on EC2

## Known Gaps (To Build)
- [ ] BrandController + Brand CRUD UI (stub exists)
- [ ] Admin Order management (view, status update)
- [ ] Dashboard analytics (sales chart, KPIs)
- [ ] Product search + advanced filters
- [ ] Email notifications (order confirmation, password reset)
- [ ] Queue jobs (send emails, image processing)
- [ ] Service layer (ProductService, OrderService, CartService)
- [ ] Pinia stores (cartStore, authStore)
- [ ] Vue composables (useCart, useToast, useConfirm)
- [ ] S3 file storage driver (replace local disk)
- [ ] Image optimization (resize on upload)
- [ ] API layer (Laravel Sanctum token auth)
- [ ] Observer pattern (audit trail automation)
- [ ] Caching (categories, products per page)
