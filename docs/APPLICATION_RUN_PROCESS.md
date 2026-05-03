# Application Run Process

**Stack:** Laravel 12 · Inertia.js 2 · Vue 3 (Composition API) · PrimeVue 4 · Tailwind CSS 3

This document walks through the complete lifecycle of a request — from the browser hitting the server for the first time, through login, to a full admin CRUD operation. It is written as a mental model reference, not an API reference.

---

## Table of Contents

1. [How the App Boots (Cold Start)](#1-how-the-app-boots-cold-start)
2. [What Inertia.js Actually Does](#2-what-inertiajs-actually-does)
3. [Login Flow — End to End](#3-login-flow--end-to-end)
4. [Authenticated Page Request](#4-authenticated-page-request)
5. [Full CRUD Example — Categories](#5-full-crud-example--categories)
   - [Index (list)](#51-index--list)
   - [Create (form)](#52-create--blank-form)
   - [Store (submit)](#53-store--form-submission)
   - [Edit (prefilled form)](#54-edit--prefilled-form)
   - [Update (submit)](#55-update--form-submission)
   - [Delete (confirm + destroy)](#56-delete--confirm--destroy)
6. [Shared Data and Flash Messages](#6-shared-data-and-flash-messages)
7. [File Upload in a Form](#7-file-upload-in-a-form)
8. [Key Files Quick Reference](#8-key-files-quick-reference)

---

## 1. How the App Boots (Cold Start)

When a browser visits the app for the **very first time**, this happens:

```
Browser GET /
    │
    ▼
public/index.php          ← PHP entry point, every request comes here
    │  loads vendor/autoload.php
    │  bootstraps Laravel (bootstrap/app.php)
    ▼
bootstrap/app.php         ← Registers service providers, middleware, routes
    │
    ▼
routes/web.php            ← Laravel matches the URL to a route
    │
    ▼
Controller or closure     ← Runs the action and returns a response
    │
    ▼  (when the action calls Inertia::render())
resources/views/app.blade.php   ← The ONE and ONLY HTML file ever sent
    │
    │  Contains: <div id="app"></div>
    │            @inertiaHead    ← page title/meta from components
    │            @vite(...)      ← loads app.js (compiled by Vite)
    ▼
resources/js/app.js       ← Vue + Inertia bootstrap
    │  createApp(InertiaApp)
    │  PrimeVue.use(...)
    │  Pinia store
    │  ZiggyVue (named routes in JS)
    ▼
Vue mounts on <div id="app"> and renders the Inertia page component
```

**The key insight:** Laravel only ever sends `app.blade.php` once. After that, all navigation is handled by Inertia as XHR — no more full HTML pages.

---

## 2. What Inertia.js Actually Does

Inertia is the **bridge** between Laravel and Vue. It has no router of its own — it uses Laravel's router on the backend and Vue on the frontend.

### First visit (full page load)

```
Browser GET /admin/products
    │
    ▼
Laravel returns app.blade.php with this embedded JSON:
    {
      "component": "Admin/Products/Index",
      "props": { "products": {...}, "filters": {} },
      "url": "/admin/products",
      "version": "abc123"
    }
    │
    ▼
app.js reads this JSON from the #app div's data-page attribute
Inertia resolves "Admin/Products/Index"
    → maps to resources/js/Pages/Admin/Products/Index.vue
Vue renders that component with the given props
```

### Subsequent navigation (Inertia XHR)

When you click an `<Link href="/admin/categories">` (Inertia's `<Link>`):

```
User clicks Inertia <Link>
    │
    │  Inertia intercepts the click (no page reload)
    ▼
XHR GET /admin/categories
  Headers:
    X-Inertia: true
    X-Inertia-Version: abc123   ← asset version check
    │
    ▼
Laravel sees X-Inertia: true header
    │  Runs middleware + controller as normal
    │  Inertia::render() returns JSON instead of HTML:
    {
      "component": "Admin/Categories/Index",
      "props": { "categories": {...} },
      "url": "/admin/categories"
    }
    ▼
Inertia client receives JSON
    │  Swaps the Vue component (Categories/Index replaces Products/Index)
    │  Updates browser URL via History API (no reload)
    │  Merges shared props (auth, flash, ziggy) automatically
    ▼
Vue re-renders — feels instant, like an SPA
```

### What `Inertia::render()` returns

```php
// In a controller:
return Inertia::render('Admin/Categories/Index', [
    'categories' => CategoryResource::collection($categories),
]);

// This resolves to the file:
// resources/js/Pages/Admin/Categories/Index.vue
//
// The array becomes the Vue component's `props`.
```

---

## 3. Login Flow — End to End

### Step 1 — Browser visits `/login`

```
GET /login
    │
routes/auth.php           ← Breeze registers this route
    │  Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ▼
AuthenticatedSessionController::create()
    │  return Inertia::render('Auth/Login');
    ▼
resources/js/Pages/Auth/Login.vue
    └─ Renders the login form in the browser
```

### Step 2 — User submits credentials

```
User types email + password, clicks "Log in"
    │
    │  Login.vue uses Inertia useForm():
    │    const form = useForm({ email: '', password: '', remember: false })
    │    form.post(route('login'))
    ▼
POST /login
    │
    ▼
LoginRequest (FormRequest)
    │  validates: email required|email, password required
    │  authenticates: Auth::attempt(['email', 'password'])
    │  if fails → back with validation errors
    ▼
AuthenticatedSessionController::store()
    │  regenerates session
    │  redirects to /dashboard (or intended URL)
    ▼
Inertia follows the redirect automatically
    │  GET /dashboard  (X-Inertia: true header)
    ▼
DashboardController / route closure
    │  return Inertia::render('Dashboard', [...])
    ▼
resources/js/Pages/Dashboard.vue renders
```

### Step 3 — Validation errors come back

If the password is wrong, Inertia handles the 422 response transparently:

```
POST /login → 422 Unprocessable Entity
    │  Body: { errors: { email: ['These credentials do not match...'] } }
    ▼
Inertia intercepts the 422
    │  Does NOT navigate — stays on Login.vue
    │  Populates form.errors.email
    ▼
Login.vue shows: <small>{{ form.errors.email }}</small>
```

---

## 4. Authenticated Page Request

After login, every admin request passes through the **middleware stack**:

```
GET /admin/products
    │
routes/web.php
    │  Route::middleware(['auth', 'role:admin'])
    │         ->prefix('admin')
    │         ->name('admin.')
    │         ->group(...)
    │
    ├─ auth middleware
    │    checks Auth::check() — redirects to /login if not authenticated
    │
    ├─ role:admin middleware (Spatie Permission)
    │    checks $user->hasRole('admin') — 403 if missing role
    │
    ├─ HandleInertiaRequests middleware  ← runs on EVERY Inertia request
    │    share(): injects into every page's props:
    │    {
    │      auth:  { user: { id, name, email } }
    │      flash: { success: '...', error: '...' }  ← from session
    │      cart:  { count: 3, items: [...] }
    │      ziggy: { routes: {...} }   ← makes route() work in Vue
    │    }
    ▼
ProductController::index()
    │  calls ProductService::list()
    │  returns Inertia::render('Admin/Products/Index', [...])
    ▼
resources/js/Pages/Admin/Products/Index.vue renders
```

**HandleInertiaRequests** is defined in:
`app/Http/Middleware/HandleInertiaRequests.php`

Everything in its `share()` method is merged into every page's props automatically — you never pass `auth` or `flash` manually from a controller.

---

## 5. Full CRUD Example — Categories

The Categories module demonstrates the complete CRUD loop. All other modules (Products, Brands, Roles, Users) follow the same pattern.

### 5.1 Index — List

```
User clicks "Categories" in sidebar
    │
    │  Sidebar uses: <Link :href="route('admin.categories.index')">
    │  Inertia fires XHR GET /admin/categories
    ▼
routes/web.php
    Route::resource('categories', CategoryController::class)
    ↳ GET /admin/categories  →  CategoryController@index
    │
    ▼
CategoryController::index(CategoryIndexRequest $request)
    │
    │  1. FormRequest validates optional sort params (field, order, perPage)
    │  2. Delegates to CategoryService::list()
    ▼
CategoryService::list()
    │  Category::query()
    │    ->orderBy($sortField, $sortOrder)
    │    ->paginate($perPage)
    │    ->withQueryString()   ← bakes current ?params into pagination URLs
    ▼
CategoryResource::collection($categories)
    │  Transforms each Category model into:
    │  { id, name, slug, status, created_at }
    │  Wraps in: { data: [...], meta: { current_page, per_page, total,
    │                                   last_page, links: [...] } }
    ▼
Inertia::render('Admin/Categories/Index', [
    'categories' => CategoryResource::collection($categories)
])
    │
    ▼  (JSON over XHR)
resources/js/Pages/Admin/Categories/Index.vue
    │
    │  defineProps({ categories: Object })
    │
    │  Template:
    │    <DataTable :value="categories.data">       ← rows
    │      <Column> ... {{ categories.meta.current_page }} ...
    │    </DataTable>
    │    <button v-for="link in categories.meta.links">  ← page nav
    │      @click="router.get(link.url)"
    │    </button>
    ▼
User sees the paginated category list
```

**The data path in summary:**
```
MySQL → Eloquent Model → Service (query) → Resource (transform)
     → Inertia prop (JSON) → Vue defineProps → template render
```

---

### 5.2 Create — Blank Form

```
User clicks "New" button
    │
    │  <Link :href="route('admin.categories.create')">
    ▼
GET /admin/categories/create  →  CategoryController@create()
    │
    │  return Inertia::render('Admin/Categories/Create')
    │  (no extra data needed — the form starts empty)
    ▼
resources/js/Pages/Admin/Categories/Create.vue
    │
    │  <CategoryForm :category="null" method="post"
    │                :submit-url="route('admin.categories.store')" />
    ▼
resources/js/Pages/Admin/Categories/Partials/CategoryForm.vue
    │
    │  const form = useForm({ name: '', status: true, image: null, _method: 'post' })
    │
    │  Template renders: InputText, InputSwitch, FileUpload
    ▼
User sees blank form
```

---

### 5.3 Store — Form Submission

```
User fills name, toggles status, picks an image → clicks "Save"
    │
CategoryForm.vue::submit()
    │  form.post(submitUrl, { forceFormData: true })
    │
    │  forceFormData: true → sends as multipart/form-data
    │  so the image File object is transmitted correctly
    ▼
POST /admin/categories
    │
    ▼
CategoryStoreRequest (FormRequest)
    │  Validates:
    │    name:   required, string, max:255, unique:categories
    │    status: boolean
    │    image:  nullable, image file, max 2048 KB
    │
    │  If validation FAILS:
    │    Laravel returns 422
    │    Inertia stays on Create.vue
    │    form.errors.name / form.errors.image are populated
    │    Template shows: <small v-if="form.errors.name">...</small>
    │
    │  If validation PASSES → continues:
    ▼
CategoryController::store(CategoryStoreRequest $request)
    │  $data = $request->validated()
    │  $this->categoryService->store($data, $request->file('image'))
    ▼
CategoryService::store(array $data, ?UploadedFile $image)
    │  if empty slug → unset so Spatie generates from name
    │  if $image → $image->store('categories', 'public')
    │               saves path to $data['image']
    │  Category::create($data)   ← INSERT into DB
    ▼
CategoryController::store() returns:
    redirect()->route('admin.categories.index')
              ->with('success', 'Category created successfully.')
    │
    │  Inertia follows the redirect (GET /admin/categories)
    │  Flash 'success' is read by HandleInertiaRequests
    │  Injected into shared props: flash.success
    ▼
AuthenticatedLayout.vue watches flash.success
    │  watch(flash, () => toast.add({ severity: 'success', ... }))
    ▼
User sees: Category list page + green "Category created" toast
```

---

### 5.4 Edit — Prefilled Form

```
User clicks "Edit" on a category row
    │
    │  <Link :href="route('admin.categories.edit', category.id)">
    ▼
GET /admin/categories/{category}/edit
    │  Laravel route model binding: resolves {category} to Category model by id
    ▼
CategoryController::edit(Category $category)
    │
    │  return Inertia::render('Admin/Categories/Edit', [
    │      'category' => (new CategoryResource($category))->resolve()
    │  ])
    │
    │  .resolve() forces the resource to an array immediately
    │  (instead of lazy-resolving during serialisation)
    ▼
resources/js/Pages/Admin/Categories/Edit.vue
    │
    │  defineProps({ category: Object })
    │  <CategoryForm :category="category" method="put"
    │                :submit-url="route('admin.categories.update', category.id)" />
    ▼
CategoryForm.vue — watch() prefills the form
    │
    │  watch(() => props.category, (cat) => {
    │      if (cat) {
    │          form.name   = cat.name
    │          form.status = cat.status
    │          // image not prefilled — file inputs can't be prefilled by JS
    │          // the existing image URL is shown as a preview instead
    │      }
    │  }, { immediate: true })
    ▼
User sees form pre-populated with current name and status
    Existing image shown as thumbnail (cat.image URL from CategoryResource)
```

---

### 5.5 Update — Form Submission

```
User changes the name → clicks "Save"
    │
CategoryForm.vue::submit()
    │
    │  _method: 'put' is in the form data (HTML forms can't do PUT natively)
    │  form.post(submitUrl, { forceFormData: true })
    │
    │  Inertia sends:  POST /admin/categories/{id}
    │                  with body field: _method=put
    ▼
Laravel sees _method=put via method spoofing
    │  Routes to: CategoryController::update()
    ▼
CategoryUpdateRequest
    │  name unique rule: Rule::unique('categories')->ignore($category->id)
    │  (so the current category's own name doesn't fail uniqueness)
    ▼
CategoryController::update(CategoryUpdateRequest $request, Category $category)
    │  $this->categoryService->update($category, $data, $request->file('image'))
    ▼
CategoryService::update()
    │  if new image → delete old from disk → store new
    │  else          → carry over existing path ($data['image'] = $category->image)
    │  $category->update($data)   ← UPDATE in DB
    ▼
redirect()->route('admin.categories.index')
          ->with('success', 'Category updated successfully.')
    ▼
Same flash toast flow as Store (section 5.3)
```

---

### 5.6 Delete — Confirm + Destroy

```
User clicks "Delete" button on a row
    │
Categories/Index.vue::destroy(row)
    │
    │  confirm.require({        ← PrimeVue ConfirmationService
    │      message: 'Delete "Electronics"?',
    │      accept: () => router.delete(route('admin.categories.destroy', row.id))
    │  })
    ▼
PrimeVue <ConfirmDialog> appears (mounted in AuthenticatedLayout)
User clicks "Yes"
    │
    ▼
Inertia router.delete()
    │  Sends:  DELETE /admin/categories/{id}
    │  (Inertia automatically includes the CSRF token)
    ▼
CategoryController::destroy(Category $category)
    │  $this->categoryService->destroy($category)
    ▼
CategoryService::destroy()
    │  $category->delete()   ← SoftDeletes: sets deleted_at, record stays in DB
    │                            (does NOT physically delete — can restore via tinker)
    ▼
redirect()->route('admin.categories.index')
          ->with('success', 'Category deleted successfully.')
    ▼
Inertia re-fetches the index → table refreshes without the deleted row
Flash toast appears
```

---

## 6. Shared Data and Flash Messages

### How shared props flow

```
Every request (Inertia or full-page)
    │
    ▼
HandleInertiaRequests::share()    ← app/Http/Middleware/HandleInertiaRequests.php
    │
    │  Returns array merged into every page's props:
    │  [
    │    'auth'  => ['user' => [...current user...]]
    │    'flash' => ['success' => session('success'), 'error' => session('error')]
    │    'cart'  => ['count' => ..., 'items' => [...]]
    │    'ziggy' => Ziggy::generate()   ← all named routes as JSON
    │  ]
    ▼
Every Vue page automatically receives these as props
    They are accessible via: usePage().props.auth.user
                                        .props.flash.success
```

### How flash toasts work

```
Controller:  redirect()->with('success', 'Created!')
    │
    │  Laravel stores 'success' in the session (one-time flash)
    ▼
HandleInertiaRequests::share()
    │  reads session('success') → puts in shared flash prop
    ▼
AuthenticatedLayout.vue
    │
    │  const flash = computed(() => usePage().props.flash)
    │
    │  watch(flash, (newFlash) => {
    │      if (newFlash.success) toast.add({ severity: 'success', ... })
    │      if (newFlash.error)   toast.add({ severity: 'error',   ... })
    │  })
    ▼
PrimeVue <Toast> component (mounted once in AuthenticatedLayout) shows the message
```

---

## 7. File Upload in a Form

File uploads require `forceFormData: true` in Inertia because standard JSON cannot carry binary data.

```
Vue form with image:
    │
    │  const form = useForm({
    │      name: '',
    │      image: null,    ← will hold a File object
    │      _method: 'post'
    │  })
    │
    │  // When user picks a file:
    │  form.image = event.files[0]   ← File object from FileUpload component
    │
    │  // Submit:
    │  form.post(url, { forceFormData: true })
    │
    │  forceFormData: true
    │  → Inertia serialises the form as multipart/form-data
    │  → File is sent as a binary stream
    │  → _method: 'put' travels as a form field (method spoofing)
    ▼
Laravel receives a standard multipart request
    │  $request->file('image')   → UploadedFile instance
    │  $request->validated()     → other fields (name, _method stripped by FormRequest)
    ▼
Service stores the file:
    │  $image->store('categories', 'public')
    │    → saves to:  storage/app/public/categories/uuid.jpg
    │    → symlinked: public/storage/categories/uuid.jpg
    │    → URL:       asset('storage/categories/uuid.jpg')
    │
    │  Path stored in DB: 'categories/uuid.jpg'  (relative, not full URL)
    │  Resource generates full URL: asset('storage/' . $this->image)
    ▼
Vue receives the full URL in the resource → displays with <img :src="category.image">
```

---

## 8. Key Files Quick Reference

### Backend

| Purpose | File |
|---------|------|
| PHP entry point | `public/index.php` |
| App bootstrap | `bootstrap/app.php` |
| All web routes | `routes/web.php` |
| Auth routes (Breeze) | `routes/auth.php` |
| Shared Inertia props | `app/Http/Middleware/HandleInertiaRequests.php` |
| Admin controllers | `app/Http/Controllers/Admin/` |
| Form validation | `app/Http/Requests/Admin/` |
| Response transformation | `app/Http/Resources/Admin/` |
| Business logic | `app/Services/` |
| Eloquent models + scopes | `app/Models/` |

### Frontend

| Purpose | File |
|---------|------|
| Vue + Inertia bootstrap | `resources/js/app.js` |
| HTML shell (only file sent) | `resources/views/app.blade.php` |
| Admin layout (sidebar + toast) | `resources/js/Pages/Admin/Layouts/AuthenticatedLayout.vue` |
| Sidebar navigation | `resources/js/Components/layout/AppSidebar.vue` |
| Admin pages | `resources/js/Pages/Admin/` |
| Storefront pages | `resources/js/Pages/Front/` |

### The request lifecycle in one line per step

```
Browser → public/index.php → routes/web.php → Middleware chain
→ FormRequest (validate) → Controller (orchestrate) → Service (logic)
→ Model (query DB) → Resource (transform) → Inertia::render()
→ JSON over XHR → Vue component (props) → DOM update
```

---

## Summary: Why This Architecture

| Concern | Handled by |
|---------|-----------|
| Routing | Laravel (`routes/web.php`) |
| Auth / middleware | Laravel middleware stack |
| Input validation | FormRequest classes |
| Business logic | Service classes |
| DB queries | Eloquent models + scopes |
| API response shape | JsonResource classes |
| SPA navigation (no reload) | Inertia.js client |
| Reactivity + UI | Vue 3 Composition API |
| UI components | PrimeVue 4 |
| Shared global state | HandleInertiaRequests (server) + usePage() (client) |
| File uploads | multipart via forceFormData + Laravel Storage |
| Flash messages | Laravel session flash → shared props → PrimeVue Toast |

The key mental model: **Laravel owns the URL, the data, and the session. Vue owns the DOM. Inertia is the messenger between them.**
