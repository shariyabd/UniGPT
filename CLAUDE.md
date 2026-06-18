Yes — I merged your original `CLAUDE.md` with the improvements, while keeping it **compact and high-signal** (still roughly within your 200–300 line target).

I intentionally **did not bloat it** with the project audit workflow. This version focuses only on instructions that should apply to **every Claude Code request**.

# CLAUDE.md

## Project

**UniGPT** — AI-powered university academic copilot with role-based dashboards (**Student / Faculty / Admin**).

Stack:

* Laravel 11
* Inertia 2
* Vue 3
* Vite
* Tailwind
* MySQL

⚠️ Root docs (`README.md`, `PROJECT_STRUCTURE.md`, etc.) are partially outdated.

* Docs mention **Livewire** → incorrect (installed but unused)
* Actual frontend → **Inertia + Vue**
* AI/RAG/VectorDB mostly unimplemented scaffolding in `app/Domain/*` and `app/Infrastructure/*`

**Trust code over docs.**

---

## Response Style

* Be concise and high-signal
* Avoid repeating obvious context
* Prefer precise analysis over long explanations
* Do not overengineer
* Prioritize maintainability and correctness

---

## Commands

```bash
composer dev
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

Uses **MySQL** database (`uni_gpt`), not SQLite.

`phpunit.xml` SQLite config is commented out.

Always ensure:

* migrations valid
* foreign keys correct
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

* Ziggy for route helper
* vue-toastification for toasts

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

* Public
* Authenticated:

  * `role:student`
  * `role:faculty`
  * `role:admin`

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

### Role Slug Mismatch

Enum may return uppercase (`ADMIN`) but DB stores lowercase (`admin`).

Source of truth = lowercase DB slug.

Never rely on uppercase role values.

---

### Shared Auth User Broken

`HandleInertiaRequests` currently shares:

```php
auth.user => null
```

Vue cannot access current user until replaced with:

```php
$request->user()
```

---

## Task Mode

Before making code changes, determine task type:

* Bug Fix
* Refactor
* New Feature
* Architecture Audit
* Database Change

Workflow:

1. Understand existing implementation
2. Identify dependencies
3. Detect blast radius
4. Propose plan
5. Execute safely

Never code before understanding context.

---

## Search First Policy

Before modifying logic, search for all related code.

Search:

* routes
* controllers
* services
* actions
* models
* relationships
* Vue pages
* migrations
* tests
* configs

Never assume a file is isolated.

Large systems often hide indirect dependencies.

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

Use verification proportionally.

Small change:

```bash
./vendor/bin/pint
php artisan test --filter=RelevantTest
```

Large change:

```bash
php artisan route:list
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan test
npm run build
```

Never leave partial refactors.

---

## Legacy Preservation Rule

This project is partially migrated from legacy code.

Before deleting or rewriting logic, verify whether code exists for:

* backward compatibility
* unfinished migration
* hidden business rules

Prefer refactor over rewrite.

Do not remove old code unless confirmed unused.

---

## Implementation Awareness

Many modules are scaffolding only.

Presence of:

* class
* interface
* migration
* Vue page
* service

DOES NOT mean feature is complete.

Always verify end-to-end:

Database
→ Backend logic
→ API / Controller
→ Frontend integration
→ User workflow

---

## Feature Status Labels

Use during audits:

* COMPLETE
* PARTIAL
* NOT_STARTED
* BLOCKED

A feature is COMPLETE only if user can use it end-to-end.

---

## Naming

Use meaningful names only.

Variables:

```php
camelCase
```

Methods:

```php
verbFirstCamelCase
```

Classes:

```php
PascalCase
```

Bad:

```php
$a
$x
data()
handle()
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
* collections/models when possible

Prefer:

```php
declare(strict_types=1);
```

Example:

```php
public function assignRole(User $user, string $role): bool
```

---

## Named Arguments

Use named args for multi-parameter calls.

Good:

```php
calculateTotal(
    price: $price,
    quantity: $quantity
);
```

---

## Separation of Concerns

Controllers:

* validation
* orchestration
* response only

Business logic belongs in:

* Services
* Domain layer
* Actions

Vue:

* UI only
* local state only

Avoid business logic inside Vue.

---

## Database Access

Prefer:

* Eloquent
* relationships
* scopes
* query builder

Avoid:

* raw SQL
* excessive `DB::raw`
* manual joins if relationships suffice

Always prevent N+1.

Example:

```php
User::with(['roles', 'permissions'])->get();
```

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

1. Database design
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

Avoid unnecessary abstraction.

Use DTOs / repositories only when complexity justifies them.

---

## Workflow (Every Task)

1. Analyze domain + dependencies
2. Explain problem and risks
3. Refactor safely
4. Propagate all changes (Rule 0)
5. Verify changes
6. Confirm behavior unchanged

