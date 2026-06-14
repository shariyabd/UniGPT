# CLAUDE.md

## Project

**UniGPT** — university AI academic copilot with role-based dashboards (**Student / Faculty / Admin**).
Stack: **Laravel 11 + Inertia 2 + Vue 3 + Vite + Tailwind + MySQL**.

⚠️ Root docs (`README.md`, `PROJECT_STRUCTURE.md`, etc.) are partially outdated.

* Docs mention **Livewire** → incorrect (installed but unused)
* Actual frontend → **Inertia + Vue**
* AI/RAG/VectorDB layers are mostly unimplemented scaffolding in `app/Domain/*` and `app/Infrastructure/*`

Trust the code over docs.

---

## Commands

```bash
composer dev                    # full dev environment
php artisan serve
npm run dev
npm run build

php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed --class=RBACSeeder

php artisan test
./vendor/bin/phpunit tests/Feature/

./vendor/bin/pint

php artisan route:list
php artisan optimize:clear
```

---

## Database

Uses **MySQL** (`uni_gpt`), not SQLite.
`phpunit.xml` SQLite config is commented out. Tests use configured DB.

Always ensure:

* migrations valid
* FKs correct
* indexes added where needed
* schema matches code

---

## Architecture

### Frontend

Root view:

```php
resources/views/app.blade.php
```

Boot file:

```js
resources/js/app.js
```

Controllers return:

```php
Inertia::render('PageName', [...props]);
```

Frontend structure:

* Pages → `resources/js/pages`
* Components → `resources/js/components`
* Layouts → `resources/js/Layouts`

Uses:

* **Ziggy** for route helper
* **vue-toastification** for toasts

⚠️ Inertia page names and route names are string-based and fail silently.

---

## Routing

Only active route file:

```php
routes/web.php
```

These are NOT registered:

* `routes/student.php`
* `routes/faculty.php`
* `routes/admin.php`

Editing them does nothing unless registered in `bootstrap/app.php`.

Route groups:

* Public → login/register/password reset/terms/privacy
* Authenticated:

  * `role:student`
  * `role:faculty`
  * `role:admin`

---

## Authentication / RBAC

Custom auth system.

Middleware aliases in:

```php
bootstrap/app.php
```

Uses:

* `RoleMiddleware`
* `PermissionMiddleware`

Rules:

* guests → `/login`
* inactive users → logout

Login requires:

* credentials
* selected role (`student|faculty|admin`)
* role validation
* rate limiting

RBAC:

* User ↔ Role (many-to-many)
* Role ↔ Permission (many-to-many)

Pivot supports `expires_at`.

---

## User Model

NOT in `app/Models`.

Actual location:

```php
app/Domain/User/Models/User.php
```

Namespace:

```php
App\Domain\User\Models\User
```

Important methods:

* `hasRole`
* `hasPermission`
* `assignRole`
* `syncRoles`
* `isStudent`
* `isFaculty`
* `isAdmin`
* `getPrimaryRole`
* `getDashboardRoute`

---

## Known Gotchas

### Role slug mismatch

Enum returns uppercase (`ADMIN`) but DB stores lowercase (`admin`).

Source of truth = **lowercase DB slug**.

Do not write logic relying on uppercase role slugs.

### Shared auth user broken

`HandleInertiaRequests` shares:

```php
auth.user => null
```

Vue cannot access current user until replaced with:

```php
$request->user()
```

---

## Conventions

### Naming

* Variables → `camelCase`
* Methods → verb-first `camelCase`
* Classes → `PascalCase`
* Use meaningful names only

Bad:

```php
$a, $x, data(), handle()
```

Good:

```php
$totalPrice
calculateTotalAmount()
```

---

## Typing

Always type:

* params
* returns
* nullable values
* collections/models where possible

Prefer in new files:

```php
declare(strict_types=1);
```

Example:

```php
public function assignRole(User $user, string $role): bool
```

---

## Named Arguments

Use named args for multi-param calls.

Good:

```php
calculateTotal(
    price: $price,
    quantity: $qty
);
```

---

# Rule 0 — Propagate Every Change (MOST IMPORTANT)

A refactor is incomplete until **all usage sites** are updated.

Changing any:

* variable
* method
* class
* route
* enum
* DB column
* prop
* page name
* config key
* relationship

You MUST:

### 1. Search

```bash
grep -rn "oldName" app/ resources/ routes/ database/ config/ tests/
```

### 2. Update ALL usages

Backend:

* controllers
* services
* repositories
* actions
* DTOs
* requests
* policies
* models
* middleware

Frontend:

* Vue props
* route()
* composables
* stores
* forms
* emits
* watchers
* Inertia page names

Database:

* migrations
* factories
* seeders
* validation rules

Tests:

* unit
* feature
* mocks

### 3. Re-search

Old symbol must be gone.

### 4. Verify

```bash
php artisan route:list
php artisan migrate:fresh --seed
php artisan optimize:clear
./vendor/bin/pint
php artisan test
npm run build
```

Never leave partial refactors.

---

## Separation of Concerns

Controllers:

* validation
* orchestration
* response only

Keep business logic in:

* Services
* Domain layer
* Actions
* Repositories

Vue:

* UI only
* local state only

Avoid business logic in Vue.

---

## Database Access

Prefer:

* Eloquent
* relationships
* scopes
* query builder

Avoid:

* raw SQL
* `DB::raw`
* manual joins when relationships suffice

Always prevent N+1 with eager loading.

Example:

```php
User::with(['roles', 'permissions'])->get();
```

---

## Routes

Routes must be:

* RESTful
* grouped
* named
* consistent

Avoid:

```php
/getUsers
/deleteCourse
```

Prefer:

```php
/admin/users
/admin/courses
```

Any route change must update:

* backend
* Vue route()
* redirects
* tests

---

## Models

Models should define:

* `$fillable` / `$guarded`
* `$casts`
* relationships
* scopes

Example:

```php
protected $casts = [
    'is_active' => 'boolean',
];
```

---

## Refactor Priority

1. DB design
2. Raw SQL removal
3. Naming
4. Type safety
5. Fat controllers
6. Vue logic leakage
7. Routes
8. Relationships
9. Migrations
10. Docs

---

## Principles

Always follow:

* SOLID
* DRY
* KISS
* Clean Architecture
* Separation of Concerns

Use only when justified:

* Services
* DTOs
* Form Requests
* Actions
* Policies
* Resources
* Repositories

No overengineering.

---

## Workflow (Every Task)

1. Analyze domain + dependencies
2. Explain problem & risk
3. Refactor safely
4. Propagate all changes (Rule 0)
5. Verify commands
6. Confirm behavior unchanged
