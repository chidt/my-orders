# UC009 Implementation Plan - Manage Suppliers (MS)

## 🎯 Objective
Implement supplier management functionality allowing users with `manage_suppliers` permission to create, view, update, and (when allowed) delete suppliers belonging to their own sites. This includes CRUD operations with site isolation, basic search, and enforcement of core business rules from UC-009.

## 📋 Current State Analysis

### ✅ Already Defined in Documentation:
- ERD specifies `Suppliers` entity with fields: `id`, `name`, `person_in_charge`, `phone`, `address`, `description`, `site_id`.
- Use case UC-009-MS defines:
  - Actor: `SiteAdmin` with `manage-suppliers` capability.
  - Flows: list, create, update, delete suppliers for the current site.
  - Business rules:
    - BR-01: Supplier names may be duplicated within the same site.
    - BR-02: Phone number must be valid format (if provided).
    - BR-03: Suppliers with products cannot be deleted.
    - BR-05: System should log supplier changes (to be aligned with global logging approach later).

### ❌ Missing in Codebase:
- Some test scenarios still need expanding for current supplier behavior (without status/deactivation).
- Planning doc contains outdated assumptions (needs alignment with implemented code).

## 🚀 Implementation Plan

### Phase 1: Database & Model

#### Backend Changes

1. **Create Suppliers Migration** (`database/migrations/2026_02_XX_xxxxxx_create_suppliers_table.php`):
   - Columns:
     - `id` (bigIncrements)
     - `name` (string, required, max 255)
     - `person_in_charge` (string, nullable, max 255)
     - `phone` (string, nullable, max 50)
     - `address` (string, nullable)
     - `description` (text, nullable)
     - `site_id` (foreignId to `sites`, required)
     - Timestamps
   - Constraints & indexes:
     - Foreign key `site_id` → `sites(id)` with `onDelete('cascade')`.
     - Index on `site_id` for multi-tenant queries.
     - **Important**: Do **not** enforce uniqueness on `name` to satisfy BR-01.

2. **Create Supplier Model** (`app/Models/Supplier.php`):
   - Traits: `HasFactory`.
   - `$fillable`: `['name', 'person_in_charge', 'phone', 'address', 'description', 'site_id']`.
   - Relationships:
     - `site(): BelongsTo` → `Site`.
     - `products(): HasMany` → `Product` (by `supplier_id`).
     - `purchaseRequests(): HasMany` → `PurchaseRequest` (by `supplier_id`).
   - Scopes:
     - `scopeForSite($query, $siteId)` — filter by `site_id`.
     - `scopeActive($query)` — only active suppliers.
   - Helpers:
     - `hasProducts(): bool` — whether supplier has related products.
     - `canBeDeleted(): bool` — `true` only when no related products (BR-03).

3. **Create Supplier Factory** (`database/factories/SupplierFactory.php`):
   - Generate realistic supplier data:
     - `name`, `person_in_charge`, `phone`, `address`, `description`.
   - Allow passing/associating a `Site` for `site_id`.

### Phase 2: Permissions & Authorization

#### Backend Changes

1. **Extend Role & Permission Seeder** (`database/seeders/RolePermissionSeeder.php`):
   - Add supplier permissions:
     - `manage_suppliers`
     - `view_suppliers`
     - `create_suppliers`
     - `edit_suppliers`
     - `delete_suppliers`
   - Grant to roles:
     - `Admin`: all supplier permissions.
     - `SiteAdmin`: `manage_suppliers`, `view_suppliers`, `create_suppliers`, `edit_suppliers`, `delete_suppliers`.

2. **Create Supplier Policy** (`app/Policies/SupplierPolicy.php`):
   - Methods:
     - `viewAny(User $user, Site $site)` — requires `view_suppliers` and ownership of the site.
     - `view(User $user, Supplier $supplier)` — supplier belongs to a site owned by user and `view_suppliers`.
     - `create(User $user, Site $site)` — `create_suppliers` and owns site.
     - `update(User $user, Supplier $supplier)` — `edit_suppliers` and same site.
     - `delete(User $user, Supplier $supplier)` — `delete_suppliers`, same site, and `canBeDeleted() === true`.
   - Register in `AppServiceProvider` (or dedicated policy provider) following existing pattern.

### Phase 3: Actions, Validation & Controller

#### Backend Changes

1. **Create Form Requests** (`app/Http/Requests/Supplier/`):
   - `StoreSupplierRequest`:
     - `name`: `required|string|max:255`
     - `person_in_charge`: `nullable|string|max:255`
     - `phone`: `nullable|string|max:50|regex:/^(\+?\d{7,15})$/` (or simplified phone regex)
     - `address`: `nullable|string`
     - `description`: `nullable|string`
     - Custom error messages in Vietnamese for required and format errors (AF-03, BR-02).
   - `UpdateSupplierRequest`:
     - Same rules as store, plus authorize supplier ownership in `authorize()` method.

2. **Create Supplier Actions** (`app/Actions/Supplier/`):
   - `StoreSupplier`:
     - `execute(array $data, Site $site): Supplier`
     - Attach `site_id` from `$site`.
   - `UpdateSupplier`:
     - `execute(Supplier $supplier, array $data): Supplier`
     - Handle supplier field updates.
   - `DeleteSupplier`:
     - `execute(Supplier $supplier): void`
     - Enforce BR-03: throw domain exception if `hasProducts()`; otherwise delete.

3. **Create Supplier Controller** (`app/Http/Controllers/Site/SupplierController.php`):
   - Use `AuthorizesRequests`.
   - Methods:
     - `index(Site $site)`:
       - Authorize `viewAny`.
       - Query suppliers by `site_id` with optional search by `name`/`phone`.
       - Paginate and return Inertia page `site/suppliers/Index`.
     - `create(Site $site)`:
       - Authorize `create`.
       - Render `site/suppliers/Create`.
     - `store(StoreSupplierRequest $request, Site $site, StoreSupplier $action)`:
       - Authorize `create`.
       - Persist supplier and redirect to index with success message.
     - `edit(Site $site, Supplier $supplier)`:
       - Authorize `update`.
       - Render `site/suppliers/Edit` with supplier data.
     - `update(UpdateSupplierRequest $request, Site $site, Supplier $supplier, UpdateSupplier $action)`:
       - Authorize `update`.
       - Update supplier fields.
     - `destroy(Site $site, Supplier $supplier, DeleteSupplier $action)`:
       - Authorize `delete`.
       - Execute delete or catch exception and flash appropriate error (AF-04).

4. **Add Routes** (`routes/site.php`):
   - Within existing `{site:slug}` group:
   - Add `Route::resource('suppliers', SupplierController::class)->names([ ... 'site.suppliers.*' ... ]);`
   - URLs: `/{site:slug}/suppliers/*`.

### Phase 4: Frontend Implementation (Inertia + Vue + Tailwind)

#### Frontend Changes

1. **Type Definitions** (`resources/js/types/supplier.d.ts`):
   - `Supplier` interface:
     - `id`, `name`, `person_in_charge`, `phone`, `address`, `description`, `site_id`, `products_count?`.
   - `SupplierFormData` interface.
   - `SupplierFilters` (e.g. `search` string).

2. **Supplier List Page** (`resources/js/pages/site/suppliers/Index.vue`):
   - Props:
     - `site` (id, name, slug), `suppliers` (paginated), `filters` (current search).
   - Features:
     - Table with columns: name, person_in_charge, phone, actions.
     - Search input (by name/phone) submitting via Inertia with query parameter.
     - Button **"Thêm nhà cung cấp mới"** (if `can('create_suppliers')`).
     - Actions per row: Edit, Delete (only when allowed).
     - Display warning tooltip/message when delete is not allowed due to products.

3. **Supplier Form Pages**:
   - `Create.vue` (`resources/js/pages/site/suppliers/Create.vue`):
     - Form fields corresponding to UC inputs.
     - Submit to `site.suppliers.store` via Wayfinder actions `.form()` or `useForm`.
     - Show validation errors inline.
   - `Edit.vue` (`resources/js/pages/site/suppliers/Edit.vue`):
     - Same form with existing data pre-filled.

4. **Sidebar Navigation** (`resources/js/components/AppSidebar.vue`):
   - Add supplier menu item:
     - Title: `Quản lý nhà cung cấp`.
     - Icon: choose relevant Lucide icon (e.g., `Handshake` or `Building2`).
     - Link built via Wayfinder site routes (e.g. `site.suppliers.index`).
     - Show only if user has `manage_suppliers` and site context is available.

### Phase 5: Testing & Validation

#### Backend Tests (Pest)

1. **Model Tests** (`tests/Unit/SupplierTest.php`):
   - Relationships (`site`, `products`, `purchaseRequests`).
   - Scopes `forSite`, `active`.
   - Helpers `hasProducts`, `canBeDeleted`.

2. **Policy Tests** (`tests/Unit/SupplierPolicyTest.php`):
   - Permissions for viewAny/view/create/update/delete.
   - Site ownership enforcement.

3. **Feature Tests** (`tests/Feature/SupplierManagementTest.php`):
   - Listing suppliers for a site with correct permissions.
   - Creating, updating suppliers.
   - Preventing deletion when products exist.
   - Validation error scenarios (missing name, invalid phone).

4. **Action Tests** (`tests/Unit/Actions/Supplier/`):
   - `StoreSupplierTest`, `UpdateSupplierTest`, `DeleteSupplierTest`.

#### Frontend Tests (optional initial pass)

- Smoke tests for supplier pages can be added later following existing frontend testing approach.

### Phase 6: Integration & Logging Considerations

1. **Integration with Products**:
   - Ensure `Product` model (when implemented) defines `belongsTo(Supplier::class)` and respects `site_id`.
   - Enforce BR-03 via `DeleteSupplier` action and controller.

2. **Activity Logging Alignment (BR-05)**:
   - Once global activity logging approach is finalized (e.g., via a dedicated package or `ActivityLog` model), integrate supplier create/update/delete events with that mechanism.

## 📊 Success Criteria

- **Functional**:
  - Site admins can list, search, create, edit, and (when allowed) delete suppliers belonging to their site.
  - Suppliers with products cannot be deleted; users are informed with an appropriate message.
  - Supplier data fully respects multi-tenant isolation via `site_id`.

- **Technical**:
  - All new PHP code follows Laravel Boost and project conventions.
  - Supplier logic is covered by unit and feature tests.
  - Inertia pages are type-safe and use Wayfinder for route calls.
  - Permissions and policies correctly restrict access based on role and site ownership.

