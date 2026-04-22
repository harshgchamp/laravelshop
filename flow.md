
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