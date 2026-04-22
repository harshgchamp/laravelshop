# Laravel + Inertia.js + Vue 3 — Complete Theoretical Guide
### Based on: E-Commerce Admin Panel Project

---

## TABLE OF CONTENTS

1. [The Big Picture — What Are We Building?](#1-the-big-picture)
2. [Technology Stack Explained](#2-technology-stack-explained)
3. [How Laravel, Inertia & Vue Work Together](#3-how-they-work-together)
4. [Project Folder Structure](#4-project-folder-structure)
5. [Database Design — Migrations & Models](#5-database-design)
6. [Routing — How URLs Are Handled](#6-routing)
7. [Controllers — The Brain of the Application](#7-controllers)
8. [Inertia — The Bridge Between PHP and Vue](#8-inertia-the-bridge)
9. [Vue Pages & Components — The Frontend](#9-vue-pages--components)
10. [Forms & Data Submission with Inertia](#10-forms--data-submission)
11. [Layouts — Consistent UI Shell](#11-layouts)
12. [Authentication & Roles](#12-authentication--roles)
13. [File Uploads](#13-file-uploads)
14. [The Full Request Lifecycle (Step by Step)](#14-full-request-lifecycle)
15. [Frontend vs Admin Panel Architecture](#15-frontend-vs-admin-panel)
16. [PrimeVue Components Used](#16-primevue-components-used)
17. [Key Concepts Cheat Sheet](#17-key-concepts-cheat-sheet)

---

## 1. THE BIG PICTURE

### What is this project?

This is a **full-stack e-commerce application** with two separate parts:

| Part | URL | Purpose |
|------|-----|---------|
| Admin Panel | `/dashboard`, `/admin/*` | Manage categories, products, users |
| Customer Storefront | `/`, `/product/*`, `/cart/*` | Browse & buy products |

Both parts are built as a **Single Page Application (SPA)** using Vue.js, but powered by Laravel on the backend.

### The Problem This Stack Solves

Traditional approach has two options:
- **Full server-side rendering (Blade):** Every click reloads the entire page. Slow, clunky.
- **Full SPA (Vue + separate API):** You build a REST API in Laravel AND a separate Vue app. Double the work. You manage authentication tokens (JWT) separately.

**Inertia.js is the middle ground:**
- Laravel handles routing, authentication, authorization, database — everything server side.
- Vue handles the UI — reactive, fast, no page reloads.
- No need to build a separate REST API.
- No need to manage tokens — sessions work as normal.

---

## 2. TECHNOLOGY STACK EXPLAINED

### Laravel 12 (Backend)
- PHP framework following MVC (Model–View–Controller) pattern
- Handles routing, database queries, authentication, file storage, validation
- Returns **Inertia responses** instead of Blade HTML views
- Version 12 uses PHP 8.3+

### Inertia.js (The Bridge / Adapter)
- A protocol/library that sits between Laravel and Vue
- On the PHP side: `inertiajs/inertia-laravel` package
- On the JS side: `@inertiajs/vue3` package
- Think of it as a "modern replacement for Blade templates" — instead of rendering HTML, Laravel returns JSON that Inertia renders using Vue components

### Vue 3 (Frontend)
- JavaScript framework for building user interfaces
- Uses **Composition API** with `<script setup>` syntax
- Reactive: when data changes, the UI updates automatically
- Components are `.vue` files containing template, script, and style

### PrimeVue 4 with Aura Theme
- A UI component library for Vue
- Provides ready-made components: DataTable, Button, InputText, FileUpload, Toast, ConfirmDialog, etc.
- Configured with the **Aura preset** (unstyled + design tokens)

### Tailwind CSS
- Utility-first CSS framework
- Instead of writing `.btn { ... }` you write class names directly: `class="bg-violet-900 text-white py-2 px-4"`

### MySQL
- Relational database
- Tables are created via **migrations** (PHP files that describe table structure)

---

## 3. HOW THEY WORK TOGETHER

### Traditional Laravel (without Inertia)
```
Browser → Laravel Route → Controller → Blade Template (HTML) → Browser renders HTML
```

### With Inertia + Vue
```
Browser → Laravel Route → Controller → Inertia::render('PageName', [data])
       → Inertia sends JSON → Vue component renders the UI
```

### On First Page Load
1. Browser requests `/dashboard`
2. Laravel returns a full HTML page with one `<div id="app">` and the Vue app bootstrapped
3. Inertia initialises Vue and renders the correct Vue component

### On Subsequent Navigations (clicking a link)
1. User clicks an Inertia `<Link>` component
2. Inertia intercepts the click — NO full page reload
3. It makes an XHR (AJAX) request to the server with `X-Inertia: true` header
4. Laravel controller returns JSON: `{ component: 'Admin/Categories/Index', props: { categories: [...] } }`
5. Vue swaps out the current component for the new one
6. URL changes in the browser — feels like a real navigation

This is why it feels like an SPA but you still write server-side code!

---

## 4. PROJECT FOLDER STRUCTURE

```
ecommerce-admin/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/               ← Admin CRUD controllers
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   └── UserController.php
│   │   │   └── Front/               ← Customer-facing controllers
│   │   │       ├── HomeController.php
│   │   │       ├── CartController.php
│   │   │       └── CheckoutController.php
│   │   ├── Requests/Admin/          ← Form validation (FormRequest classes)
│   │   └── Resources/Admin/         ← API Resources (shape data for Vue)
│   └── Models/                      ← Eloquent Models (DB tables as PHP objects)
│       ├── Category.php
│       ├── Product.php
│       ├── User.php
│       ├── CartItem.php
│       ├── Order.php
│       ├── OrderItem.php
│       ├── Payment.php
│       └── UserAddress.php
│
├── database/
│   └── migrations/                  ← Table structure definitions
│
├── resources/
│   └── js/
│       ├── app.js                   ← Vue + Inertia bootstrap (entry point)
│       ├── Pages/                   ← Vue page components (match Inertia::render names)
│       │   ├── Admin/
│       │   │   ├── Layouts/
│       │   │   │   └── AuthenticatedLayout.vue   ← Admin shell (sidebar + topbar)
│       │   │   ├── Categories/
│       │   │   │   ├── Index.vue
│       │   │   │   ├── Create.vue
│       │   │   │   ├── Edit.vue
│       │   │   │   └── Partials/CategoryForm.vue
│       │   │   └── Products/
│       │   │       ├── Index.vue
│       │   │       ├── Create.vue
│       │   │       ├── Edit.vue
│       │   │       └── Partials/ProductForm.vue
│       │   ├── Front/               ← Customer-facing pages
│       │   │   ├── Layouts/FrontLayout.vue
│       │   │   ├── Home.vue
│       │   │   ├── ProductDetail.vue
│       │   │   ├── Cart.vue
│       │   │   └── Components/      ← Reusable UI pieces
│       │   ├── Auth/                ← Login, Register pages
│       │   └── Dashboard.vue
│       └── Components/
│           └── layout/
│               ├── AppSidebar.vue
│               └── AppTopbar.vue
│
└── routes/
    └── web.php                      ← All application routes
```

### Key Rule
> The string you pass to `Inertia::render('Admin/Categories/Index')` must exactly match
> the path `resources/js/Pages/Admin/Categories/Index.vue`
> This is how Inertia knows which Vue component to render.

---

## 5. DATABASE DESIGN

### All Tables & Their Purpose

#### `users`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| name | string | Full name |
| email | string | Unique login |
| password | string | Hashed automatically |
| email_verified_at | timestamp | Email verification |
| remember_token | string | "Remember me" sessions |
| timestamps | created_at, updated_at | Auto-managed |

#### `categories`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| name | string | Category name |
| slug | string | URL-friendly name e.g. `mens-wear` |
| description | text | nullable |
| image | string | File path e.g. `categories/abc.jpg` |
| status | boolean | Active = true, Inactive = false |
| deleted_at | timestamp | Soft delete (row not removed from DB) |
| timestamps | - | Auto |

**Soft Deletes** means when you "delete" a category, Laravel just sets `deleted_at` to current time. The row stays in the database. All queries automatically exclude soft-deleted rows unless you ask for them.

#### `products`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| title | string(200) | Product name |
| slug | string | Unique URL slug |
| quantity | integer | Stock count |
| description | longText | Rich HTML content |
| image | string | Main image path |
| published | boolean | Visible on storefront? |
| in_stock | boolean | Available for purchase? |
| price | decimal(10,2) | Base price |
| discount_price | decimal(10,2) | Sale price (nullable) |
| category_id | FK → categories | Which category it belongs to |
| created_by | FK → users | Audit: who created it |
| updated_by | FK → users | Audit: who last updated |
| deleted_by | FK → users | Audit: who deleted it |
| deleted_at | timestamp | Soft delete |
| timestamps | - | Auto |

**Foreign Key Behaviours:**
- `category_id` → `restrictOnDelete` = cannot delete a category if it has products
- `created_by` → `cascadeOnDelete` = if user is deleted, their products are also deleted
- `updated_by/deleted_by` → `nullOnDelete` = if user is deleted, these become NULL (not cascade)

#### `product_images`
| Column | Type | Notes |
|--------|------|-------|
| product_id | FK → products | cascadeOnDelete (delete product = delete all its images) |
| image | string | Image file path |

#### `cart_items`
| Column | Type | Notes |
|--------|------|-------|
| user_id | FK → users | Whose cart |
| product_id | FK → products | What product |
| quantity | integer | How many |
| unique(user_id, product_id) | constraint | Same user can't add same product twice, it updates instead |

#### `user_addresses`
| Column | Type | Notes |
|--------|------|-------|
| user_id | FK → users | Owner |
| type | string(25) | e.g. "home", "work" |
| address1 | string(255) | Street address |
| address2 | string | optional |
| city | string(100) | |
| state | string | nullable |
| postcode | string(10) | |
| country_code | string(10) | e.g. "US", "GB" |
| is_main | boolean | Is this the default address? |

#### `orders`
| Column | Type | Notes |
|--------|------|-------|
| total_price | decimal(20,2) | Full order value |
| status | string(45) | "pending", "paid", "shipped" etc. |
| session_id | string(255) | Stripe checkout session ID |
| user_address_id | FK → user_addresses | Shipping address snapshot |
| created_by / updated_by | FK → users | Audit fields |

#### `order_items`
| Column | Type | Notes |
|--------|------|-------|
| order_id | FK → orders | Which order |
| product_id | FK → products | Which product (restrictOnDelete — can't delete product with orders) |
| quantity | integer | Items ordered |
| unit_price | decimal | Price AT TIME OF ORDER (important — product price may change later) |

#### `payments`
| Column | Type | Notes |
|--------|------|-------|
| order_id | FK → orders | Which order this payment belongs to |
| amount | decimal | Amount charged |
| status | string(45) | "pending", "paid", "failed" |
| type | string(45) | Payment method e.g. "stripe" |

### Entity Relationship Diagram (simplified)
```
users ─────────────┬─── cart_items ────── products ──── categories
                   │                          │
                   ├─── user_addresses         └─── product_images
                   │         │
                   └─── orders (via user_address_id)
                             │
                             ├─── order_items ─── products
                             └─── payments
```

### What is a Migration?

A migration is a PHP class that describes **how to create or modify a database table**. Instead of writing raw SQL, you use Laravel's Schema builder.

```php
// database/migrations/2026_02_16_233656_create_categories_table.php

Schema::create('categories', function (Blueprint $table) {
    $table->id();                       // AUTO_INCREMENT primary key
    $table->string('name');             // VARCHAR(255)
    $table->string('slug');             // VARCHAR(255)
    $table->text('description')->nullable();  // TEXT, can be NULL
    $table->string('image')->nullable();
    $table->boolean('status')->default(true); // TINYINT, default 1
    $table->softDeletes();              // Adds deleted_at column
    $table->timestamps();               // Adds created_at + updated_at
});
```

Run all migrations: `php artisan migrate`
Reset and re-run: `php artisan migrate:fresh --seed`

### What is a Model?

A Model is a PHP class that represents a **database table**. Each instance = one row. It handles reading/writing to that table.

```php
// app/Models/Category.php
class Category extends Model
{
    use HasFactory, SoftDeletes;   // enables soft delete behaviour

    protected $fillable = [        // columns allowed for mass assignment
        'name', 'slug', 'description', 'image', 'status'
    ];

    public function products()     // Relationship: one category has many products
    {
        return $this->hasMany(Product::class);
    }
}
```

**Eloquent Relationships in this project:**
- `Category` hasMany `Product` (one category → many products)
- `Product` belongsTo `Category` (many products → one category)
- `User` hasMany `CartItem`
- `CartItem` belongsTo `Product` and `User`
- `Order` hasMany `OrderItem`
- `Order` belongsTo `UserAddress`
- `Order` hasOne `Payment`
- `OrderItem` belongsTo `Product`

---

## 6. ROUTING

All routes are in `routes/web.php`.

### Route Groups
```php
// PUBLIC — No login required
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/category/{category:slug?}', ...)->name('category');
Route::get('/product/{product:slug}', ...)->name('product');

// CART — Public but uses sessions
Route::prefix('cart')->controller(CartController::class)->group(function () {
    Route::get('view', 'view')->name('cart.view');
    Route::post('store/{product}', 'store')->name('cart.store');
    Route::patch('update/{product}', 'update')->name('cart.update');
    Route::delete('delete/{product}', 'delete')->name('cart.delete');
});

// AUTHENTICATED — Must be logged in
Route::middleware('auth')->group(function () {
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('orders', [AccountOrderController::class, 'index'])->name('orders.index');
    });
    Route::prefix('checkout')->controller(CheckoutController::class)->group(function () {
        Route::post('order', 'store')->name('checkout.order');
    });
});

// ADMIN — Must be logged in AND have 'admin' role
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::resource('products', ProductController::class);
        Route::resource('users', UserController::class);
    });
```

### Resource Routes Explained
`Route::resource('categories', CategoryController::class)` automatically creates **7 routes**:

| Verb | URL | Controller Method | Route Name | Purpose |
|------|-----|-------------------|------------|---------|
| GET | /admin/categories | index() | admin.categories.index | List all |
| GET | /admin/categories/create | create() | admin.categories.create | Show create form |
| POST | /admin/categories | store() | admin.categories.store | Save new record |
| GET | /admin/categories/{id} | show() | admin.categories.show | Show one record |
| GET | /admin/categories/{id}/edit | edit() | admin.categories.edit | Show edit form |
| PUT/PATCH | /admin/categories/{id} | update() | admin.categories.update | Save updated record |
| DELETE | /admin/categories/{id} | destroy() | admin.categories.destroy | Delete record |

### Named Routes & Ziggy
In PHP you use: `route('admin.categories.index')` to generate the URL.
In Vue you use the same: `route('admin.categories.index')` — this works because of the **Ziggy** package which exports all named routes to JavaScript.

---

## 7. CONTROLLERS

A controller receives the HTTP request, does the work, and returns a response.

### CategoryController — Full CRUD Walkthrough

```php
// app/Http/Controllers/Admin/CategoryController.php

class CategoryController extends Controller
{
    // LIST — GET /admin/categories
    public function index(Request $request)
    {
        $categories = Category::query()
            ->latest()                 // ORDER BY created_at DESC
            ->paginate(10)             // 10 per page
            ->withQueryString();       // preserve ?page= in URL

        // Pass data to Vue via Inertia
        return Inertia::render('Admin/Categories/Index', [
            'categories' => CategoryResource::collection($categories),
        ]);
    }

    // SHOW CREATE FORM — GET /admin/categories/create
    public function create()
    {
        return Inertia::render('Admin/Categories/Create');
        // No data needed — blank form
    }

    // SAVE NEW RECORD — POST /admin/categories
    public function store(CategoryRequest $request)
    {
        $data = $request->validated(); // Only use validated data (from FormRequest)

        // Auto-generate slug from name
        $data['slug'] = Str::slug($data['name']); // "My Category" → "my-category"

        // Handle duplicate slugs
        $count = Category::where('slug', 'like', "{$data['slug']}%")->count();
        $data['slug'] = $count ? "{$data['slug']}-{$count}" : $data['slug'];

        // Handle file upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')
                ->store('categories', 'public'); // saves to storage/app/public/categories/
        }

        Category::create($data); // INSERT INTO categories

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully'); // flash message
    }

    // SHOW EDIT FORM — GET /admin/categories/{id}/edit
    public function edit(Category $category) // Route Model Binding — auto-fetches record
    {
        return Inertia::render('Admin/Categories/Edit', [
            'category' => (new CategoryResource($category))->resolve()
        ]);
    }

    // SAVE UPDATED RECORD — PUT /admin/categories/{id}
    public function update(CategoryRequest $request, Category $category)
    {
        $data = $request->validated();
        // ... slug logic, image handling ...
        $category->update($data); // UPDATE categories SET ...

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully');
    }

    // DELETE — DELETE /admin/categories/{id}
    public function destroy(Category $category)
    {
        $category->delete(); // Soft delete — sets deleted_at

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully');
    }
}
```

### Route Model Binding
When a controller method has a typed parameter matching a route segment:
```php
public function edit(Category $category)
// Laravel automatically does: Category::findOrFail($id)
// If not found → 404 error automatically
```

### FormRequest (Validation)
Instead of validating inside the controller, we use a dedicated class:
```php
// app/Http/Requests/Admin/CategoryRequest.php
public function rules(): array
{
    return [
        'name'        => 'required|string|max:255',
        'description' => 'nullable|string',
        'image'       => 'nullable|image|max:2048',
        'status'      => 'boolean',
    ];
}
```
If validation fails, Laravel automatically redirects back with errors in `form.errors` on the Vue side.

### API Resources
Resources transform model data before sending to Vue:
```php
// app/Http/Resources/Admin/CategoryResource.php
// Controls exactly what fields Vue receives
// e.g., formats image URL as full path, hides internal fields
```

---

## 8. INERTIA — THE BRIDGE

### How `Inertia::render()` Works

```php
return Inertia::render('Admin/Categories/Index', [
    'categories' => CategoryResource::collection($categories),
]);
```

This tells Inertia:
1. "The Vue component to render is at `Pages/Admin/Categories/Index.vue`"
2. "Pass this data as **props** to that component"

On the first request → full HTML page
On subsequent requests → JSON response:
```json
{
  "component": "Admin/Categories/Index",
  "props": {
    "categories": { "data": [...], "links": {...}, "meta": {...} }
  },
  "url": "/admin/categories",
  "version": "abc123"
}
```

### Shared Props (Flash Messages)
In `HandleInertiaRequests` middleware, you can share data with every page:
```php
public function share(Request $request): array
{
    return [
        'auth' => ['user' => $request->user()],
        'flash' => [
            'success' => $request->session()->get('success'),
            'error'   => $request->session()->get('error'),
        ],
    ];
}
```
This is how `->with('success', '...')` in the controller shows up as a Toast notification in Vue.

### `usePage()` in Vue — Reading Shared Props
```js
// In AuthenticatedLayout.vue
const page = usePage()

watch(() => page.props.flash?.success, (message) => {
    if (message) {
        toast.add({ severity: 'success', detail: message })
    }
})
```

---

## 9. VUE PAGES & COMPONENTS

### What is a `.vue` file?

Every `.vue` file has three sections:

```vue
<script setup>
// JavaScript logic — reactive data, functions, imports
</script>

<template>
<!-- HTML structure — what is displayed -->
</template>

<style scoped>
/* CSS — optional, scoped = only applies to this component */
</style>
```

### `<script setup>` Syntax
This is Vue 3's Composition API shorthand. Everything declared here is automatically available in the template.

### Props — Receiving Data from Laravel

```vue
<!-- Admin/Categories/Index.vue -->
<script setup>
defineProps({
    categories: Object   // This data comes from Inertia::render() props
})
</script>

<template>
    <DataTable :value="categories.data">
        <!-- categories.data is the array of categories -->
    </DataTable>
</template>
```

**TypeScript-style props** (used in Front pages):
```vue
<!-- Front/Home.vue -->
<script setup lang="ts">
const props = defineProps<{
    categories: { data: any[] },
    products:   { data: any[] }
}>()
</script>
```

### Reactive Data with `ref()`

```vue
<script setup>
import { ref } from 'vue'

const collapsed = ref(false)   // reactive variable, starts as false

const toggle = () => collapsed.value = !collapsed.value
// Note: to READ or WRITE a ref, use .value in script
// In template, Vue automatically unwraps: {{ collapsed }} not {{ collapsed.value }}
</script>

<template>
    <!-- In template, no .value needed -->
    <div :class="collapsed ? 'w-20' : 'w-64'">
        <button @click="toggle">Toggle</button>
    </div>
</template>
```

### Computed Properties with `computed()`

```vue
<!-- Front/Cart.vue -->
const total = computed(() => props.cartItems.data.total)
// total automatically recalculates whenever cartItems changes
```

### Watchers with `watch()`

```vue
<!-- Categories/Partials/CategoryForm.vue -->
watch(
    () => props.category,
    (category) => {
        if (category) {
            form.name = category.name    // populate form when editing
            form.status = Boolean(category.status)
        }
    },
    { immediate: true }  // run immediately on mount, not just when it changes
)
```

---

## 10. FORMS & DATA SUBMISSION

### `useForm()` — Inertia's Form Helper

This is the most important concept for forms in this project.

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    name: '',          // initial values
    description: '',
    image: null,
    status: true,
    _method: 'post',   // HTTP method override for PUT/PATCH
})
</script>
```

`useForm` gives you:
- `form.name`, `form.description` — the field values
- `form.errors.name` — validation error for that field (from Laravel)
- `form.processing` — true while request is in flight (for loading button state)
- `form.post(url, options)` — submit as POST
- `form.put(url, options)` — submit as PUT
- `form.reset()` — clear the form

### Submitting the Form

```vue
<script setup>
const submit = () => {
    form.post(props.submitUrl, {
        forceFormData: true,    // Required when uploading files
        preserveScroll: true,   // Don't jump to top of page after redirect
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Saved!' })
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Fix errors' })
        }
    })
}
</script>

<template>
    <InputText v-model="form.name" />
    <small v-if="form.errors.name" class="text-red-500">
        {{ form.errors.name }}
    </small>

    <Button label="Save" :loading="form.processing" @click="submit" />
</template>
```

### v-model — Two-Way Data Binding

`v-model="form.name"` means:
- When the input changes → `form.name` updates automatically
- When `form.name` changes in code → the input field shows the new value

### Method Spoofing for PUT/PATCH/DELETE

HTML forms only support GET and POST. For PUT/DELETE, Inertia uses a hidden `_method` field:

```js
const form = useForm({
    _method: 'put',     // tells Laravel to treat this as PUT
    ...
})
// then submit as POST:
form.post(url, { forceFormData: true })
```

### Inertia `router` — Navigation Without Forms

For DELETE operations (no form needed):
```vue
<script setup>
import { router } from '@inertiajs/vue3'

const destroy = (row) => {
    confirm.require({
        message: 'Delete this category?',
        accept: () => {
            router.delete(route('admin.categories.destroy', row.id))
        }
    })
}
</script>
```

For Cart quantity updates:
```js
router.patch(`/cart/update/${cart.id}`, { quantity: cart.quantity + 1 })
```

### `<Link>` Component vs `<a>` Tag

Always use `<Link>` from Inertia instead of `<a>`:
```vue
<!-- Regular anchor — causes FULL PAGE RELOAD -->
<a href="/admin/categories">Categories</a>

<!-- Inertia Link — SPA navigation, no reload -->
<Link :href="route('admin.categories.index')">Categories</Link>
```

---

## 11. LAYOUTS

A **layout** is a wrapper component that provides the consistent shell (sidebar, topbar, footer) around every page.

### Admin Layout (`AuthenticatedLayout.vue`)

```vue
<template>
<div class="layout-wrapper flex h-screen">

    <AppSidebar />           <!-- Left navigation sidebar -->

    <div class="flex flex-col flex-1 overflow-hidden">
        <Toast />            <!-- Notification popups -->
        <ConfirmDialog />    <!-- "Are you sure?" dialogs -->
        <AppTopbar />        <!-- Top navigation bar -->

        <main class="flex-1 overflow-y-auto p-6">
            <slot />         <!-- PAGE CONTENT GOES HERE -->
        </main>
    </div>

</div>
</template>
```

The `<slot />` is where child content goes. Each page wraps itself:

```vue
<!-- Admin/Categories/Index.vue -->
<template>
    <AdminLayout>             <!-- uses the layout -->
        <div class="card">
            <!-- actual page content here -->
        </div>
    </AdminLayout>
</template>
```

### Front Layout (`FrontLayout.vue`)

```vue
<template>
  <div>
    <Header />     <!-- Nav bar with cart icon, account link -->
    <main>
      <slot />     <!-- Page content -->
    </main>
    <Footer />
  </div>
</template>
```

### Flash Message Handling in Layout

The Admin layout watches for flash messages from Laravel:
```js
// AuthenticatedLayout.vue
const page = usePage()

watch(() => page.props.flash?.success, (message) => {
    if (message) toast.add({ severity: 'success', detail: message, life: 3000 })
})
watch(() => page.props.flash?.error, (message) => {
    if (message) toast.add({ severity: 'error', detail: message, life: 3000 })
})
```
When a controller does `->with('success', 'Category created!')`, the Toast automatically appears.

---

## 12. AUTHENTICATION & ROLES

### Authentication
Laravel Breeze (or Jetstream) pre-generates auth routes + Vue pages:
- `Pages/Auth/Login.vue` — Login form
- `Pages/Auth/Register.vue` — Registration form
- Session-based auth — no JWT tokens needed

### Login Form Example
```vue
<!-- Pages/Auth/Login.vue -->
const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'), // clear password after attempt
    })
}
```

### Role-Based Access with Spatie Permissions
The project uses the `spatie/laravel-permission` package.

```php
// User model
use HasRoles;  // from Spatie

// Route middleware
Route::middleware(['auth', 'role:admin'])   // only users with 'admin' role
```

Users can be assigned roles like "admin" which controls what routes they can access.

---

## 13. FILE UPLOADS

### Storage Configuration

Files are stored in `storage/app/public/` and accessed via `/storage/` URL.
This requires running: `php artisan storage:link` (creates a symlink from `public/storage` to `storage/app/public`).

### Upload Flow

**Vue side — pick file:**
```vue
<!-- FileUpload component from PrimeVue -->
<FileUpload
    mode="basic"
    accept="image/*"
    @select="onImageSelect"
/>

<script setup>
const preview = ref(null)

const onImageSelect = (event) => {
    const file = event.files[0]
    form.image = file                          // attach to form
    preview.value = URL.createObjectURL(file)  // show preview immediately
}
</script>
```

**PHP side — save file:**
```php
if ($request->hasFile('image')) {
    $data['image'] = $request->file('image')
        ->store('categories', 'public');
    // saves to: storage/app/public/categories/randomname.jpg
    // accessible at: /storage/categories/randomname.jpg
}
```

**When updating — delete old file first:**
```php
if ($request->hasFile('image')) {
    if ($category->image) {
        Storage::disk('public')->delete($category->image); // delete old image
    }
    $data['image'] = $request->file('image')->store('categories', 'public');
}
```

---

## 14. FULL REQUEST LIFECYCLE

### Example: Admin creates a new Category

**Step 1 — User visits Create page**
```
Browser: GET /admin/categories/create
Laravel: CategoryController@create()
→ Inertia::render('Admin/Categories/Create')
→ Vue renders Categories/Create.vue inside AdminLayout
```

**Step 2 — User fills the form and clicks Save**
```
Vue: form.post('/admin/categories', { forceFormData: true })
→ Inertia sends POST request with multipart form data
```

**Step 3 — Laravel processes the request**
```
Middleware: ['auth', 'role:admin'] — is user logged in and is admin?
Route: POST /admin/categories → CategoryController@store()
FormRequest: CategoryRequest validates all fields
Controller:
  1. $data = $request->validated()
  2. Generate slug
  3. Store uploaded image
  4. Category::create($data)
  5. return redirect()->route('admin.categories.index')->with('success', '...')
```

**Step 4 — Inertia handles the redirect**
```
Laravel sends HTTP 302 redirect to /admin/categories
Inertia follows the redirect — GET /admin/categories
→ CategoryController@index() runs
→ Returns categories list as Inertia response
→ Vue re-renders Index.vue with fresh data
→ AuthenticatedLayout detects flash message → shows Toast
```

### Example: Customer adds product to cart

```
Vue: router.post(route('cart.store', product))
Laravel: CartController@store()
  - Check if user is logged in (session)
  - CartItem::updateOrCreate(['user_id' => $user->id, 'product_id' => $product->id], ['quantity' => $qty + 1])
  - Return redirect or Inertia response
Vue: Shows success alert
```

---

## 15. FRONTEND VS ADMIN PANEL ARCHITECTURE

### Two Separate Worlds

| Feature | Admin Panel | Customer Storefront |
|---------|------------|-------------------|
| Layout | `AuthenticatedLayout.vue` (sidebar) | `FrontLayout.vue` (header/footer) |
| UI Library | PrimeVue DataTable, Buttons | Plain HTML + Tailwind |
| Routes prefix | `/admin/*` | `/`, `/product/*`, `/cart/*` |
| Middleware | `auth + role:admin` | mostly public |
| Controllers folder | `Controllers/Admin/` | `Controllers/Front/` |
| Pages folder | `Pages/Admin/` | `Pages/Front/` |

### Dashboard — Stats Overview
```php
// routes/web.php
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'users'      => (int) User::count(),
        'categories' => (int) Category::count(),
        'products'   => (int) Product::count(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');
```

```vue
<!-- Pages/Dashboard.vue -->
<script setup>
const props = defineProps({ users: Number, categories: Number, products: Number })
</script>

<template>
    <AdminLayout>
        <div class="grid grid-cols-12 gap-8">
            <div>Users: {{ props.users }}</div>
            <div>Categories: {{ props.categories }}</div>
            <div>Products: {{ props.products }}</div>
        </div>
    </AdminLayout>
</template>
```

---

## 16. PRIMEVUE COMPONENTS USED

### Admin Panel Components

| Component | Import | Usage |
|-----------|--------|-------|
| `DataTable` | `primevue/datatable` | Listing records with pagination |
| `Column` | `primevue/column` | Defining table columns |
| `Button` | `primevue/button` | Action buttons (New, Edit, Delete, Save) |
| `InputText` | `primevue/inputtext` | Text input fields |
| `Textarea` | `primevue/textarea` | Multi-line text input |
| `InputSwitch` | `primevue/inputswitch` | Toggle for Active/Inactive |
| `FileUpload` | `primevue/fileupload` | Image selection with preview |
| `Select` | `primevue/select` | Dropdown for category selection |
| `InputNumber` | `primevue/inputnumber` | Numeric fields (price, quantity) |
| `InputGroup` | `primevue/inputgroup` | Combines label + input |
| `Editor` | `primevue/editor` | Rich text editor for product description |
| `Toast` | `primevue/toast` | Notification popups |
| `ConfirmDialog` | `primevue/confirmdialog` | "Are you sure?" delete confirmation |

### Toast & ConfirmDialog Setup

These are registered globally in `AuthenticatedLayout.vue`:
```vue
<Toast />         <!-- renders toast notifications -->
<ConfirmDialog /> <!-- renders confirm modals -->
```

Used in pages:
```js
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

const toast = useToast()
const confirm = useConfirm()

// Show toast
toast.add({ severity: 'success', summary: 'Done', detail: 'Category saved', life: 3000 })

// Show confirm dialog
confirm.require({
    message: 'Delete this category?',
    accept: () => { router.delete(route('admin.categories.destroy', id)) }
})
```

---

## 17. KEY CONCEPTS CHEAT SHEET

### Inertia Core
| Concept | Explanation |
|---------|-------------|
| `Inertia::render('Page', [data])` | Returns a Vue page with props from PHP |
| `<Link :href="route('name')">` | SPA navigation (no page reload) |
| `useForm({})` | Tracks form state, errors, loading |
| `router.delete(url)` | Programmatic navigation/deletion |
| `usePage()` | Access shared props (auth, flash) from any component |
| `form.errors.fieldName` | Validation errors from Laravel |
| `form.processing` | True while request is pending |

### Vue 3 Core
| Concept | Explanation |
|---------|-------------|
| `ref(value)` | Reactive variable, access with `.value` in script |
| `computed(() => ...)` | Auto-recalculates when dependencies change |
| `watch(source, callback)` | Runs code when reactive data changes |
| `defineProps({})` | Declare what data this component accepts |
| `v-model` | Two-way binding between input and data |
| `v-if` / `v-else` | Conditional rendering |
| `v-for` | Loop over arrays to render lists |
| `:prop="value"` | Dynamic prop binding |
| `@event="handler"` | Event listener |
| `<slot />` | Placeholder for child content in layouts |

### Laravel Core
| Concept | Explanation |
|---------|-------------|
| Migration | PHP class that creates/modifies DB tables |
| Model | PHP class representing a DB table (Eloquent ORM) |
| Controller | Handles requests, calls models, returns responses |
| FormRequest | Dedicated validation class |
| Route Model Binding | Auto-fetches model by ID from route parameter |
| Soft Deletes | Sets `deleted_at` instead of removing the row |
| `->with('success', '...')` | Flash message sent after redirect |
| Named routes | `route('admin.categories.index')` generates URL |
| Middleware | Code that runs before/after controller (auth checks) |
| Storage | `storage/app/public/` for uploaded files |

### The Golden Rule of Inertia
> **Server side:** Focus on data. Laravel fetches from DB, shapes data, sends via Inertia.
> **Client side:** Focus on display. Vue receives props, renders UI, handles user interactions.
> **No REST API needed.** Authentication is just PHP sessions. Routing is just Laravel routes.

---

## PROJECT SETUP COMMANDS

```bash
# 1. Install PHP dependencies
composer install

# 2. Install JavaScript dependencies
npm install

# 3. Copy environment file and configure DB
cp .env.example .env
php artisan key:generate

# 4. Create database, run migrations, seed demo data
php artisan migrate:fresh --seed

# 5. Link storage for file uploads
php artisan storage:link

# 6. Start development servers (run both simultaneously)
php artisan serve        # Laravel on http://localhost:8000
npm run dev              # Vite on http://localhost:5173 (hot reload)
```

---

*This document is based on the actual code in this project. Every example is from a real file.*
