# UC004 Implementation Plan - Manage Site Warehouse (MSW)

## 🎯 Objective
Implement warehouse management functionality allowing users with `manage_warehouses` permission to create, view, update, and delete warehouses within their owned sites. Includes comprehensive CRUD operations with proper authorization and validation.

## 📋 Current State Analysis

### ✅ Already Implemented:
- Database schema with warehouses table (id, code, name, address, site_id)
- Permission system with warehouse-related permissions (`manage_warehouses`, `create_warehouses`, etc.)
- Site-Warehouse relationship (hasMany/belongsTo)
- Spatie Laravel Permission package configured
- User-Site ownership validation system
- Unique constraint on (site_id, code) combination

### ❌ Missing Components:
- Warehouse model and factory
- Warehouse Policy for site-based authorization
- Warehouse management controllers and form requests
- Routes for warehouse CRUD operations
- Frontend warehouse management pages/forms
- Sidebar navigation conditional display
- Cascade deletion rules for warehouse-location dependencies
- Frontend type definitions for warehouse permissions

## 🚀 Implementation Plan

### Phase 1: Backend Models & Authorization

#### Backend Changes

**1. Create Warehouse Model** (`app/Models/Warehouse.php`):
```php
// Model features:
// - Fillable: code, name, address
// - Relationships: belongsTo(Site), hasMany(Location)
// - Scopes: forSite(), withLocationsCount()
// - Validation: unique code within site scope
```
- Define fillable attributes (code, name, address)
- Setup Site relationship (belongsTo site)
- Setup Location relationship (hasMany locations)
- Add query scopes for site filtering
- Include soft deletes for data integrity

**2. Create Warehouse Factory** (`database/factories/WarehouseFactory.php`):
```php
// Factory definitions:
// - Generate unique warehouse codes (W001, W002, etc.)
// - Create realistic warehouse names and addresses
// - Associate with existing sites via site_id
```
- Generate sequential warehouse codes
- Create realistic names and addresses using Faker
- Support site association for testing

**3. Create Warehouse Policy** (`app/Policies/WarehousePolicy.php`):
```php
// Policy methods:
// - viewAny(User $user): Check manage_warehouses permission
// - view(User $user, Warehouse $warehouse): Check site ownership
// - create(User $user): Check create_warehouses permission + site ownership
// - update(User $user, Warehouse $warehouse): Check edit_warehouses + site ownership
// - delete(User $user, Warehouse $warehouse): Check delete_warehouses + no locations
```
- Verify site ownership through warehouse->site->user_id
- Check specific warehouse permissions
- Validate deletion constraints (no existing locations)
- Register policy in AppServiceProvider

**Files to create/modify**:
- `app/Models/Warehouse.php` (new)
- `database/factories/WarehouseFactory.php` (new)  
- `app/Policies/WarehousePolicy.php` (new)
- `app/Providers/AppServiceProvider.php` (register policy)

### Phase 2: Backend Controllers & Validation

#### Backend Changes

**1. Create Warehouse Management Controller** (`app/Http/Controllers/Site/WarehouseController.php`):
```php
// Controller methods:
// - index(): List warehouses for current site with locations count
// - create(): Show warehouse creation form
// - store(StoreWarehouseRequest $request): Create new warehouse
// - edit(Warehouse $warehouse): Show edit form with authorization
// - update(UpdateWarehouseRequest $request, Warehouse $warehouse): Update warehouse
// - destroy(Warehouse $warehouse): Delete warehouse with dependency check
```
- Use Warehouse Policy for all authorization
- Delegate business logic to Actions
- Return Inertia responses with proper data structure
- Include locations count for each warehouse

**2. Create Warehouse Management Actions** (`app/Actions/Warehouse/`):
```php
// Actions to implement:
// - StoreWarehouse.php: Handle warehouse creation with site assignment
// - UpdateWarehouse.php: Handle warehouse updates with validation
// - DeleteWarehouse.php: Handle safe deletion with dependency checks
// - GenerateWarehouseCode.php: Auto-generate unique codes within site
```
- Follow single responsibility principle
- Encapsulate business logic separate from controllers
- Handle automatic site_id assignment for new warehouses
- Validate uniqueness within site scope

**3. Create Form Requests** (`app/Http/Requests/Warehouse/`):
```php
// StoreWarehouseRequest.php validation:
// - code: required|string|max:50|unique within current user's site
// - name: required|string|max:255
// - address: required|string

// UpdateWarehouseRequest.php validation:
// - Same as store but with unique rule excluding current warehouse
// - Include authorization check for warehouse ownership
```
- Include proper authorization checks
- Custom error messages in Vietnamese
- Validate unique code within site scope
- Handle site_id automatic assignment

**4. Add Routes** (`routes/site.php`):
```php
// Update existing site routes file:
Route::prefix('{site:slug}')->group(function () {
    // Dashboard route (already exists)
    Route::get('/dashboard', [SiteDashboardController::class, 'index'])
        ->name('site.dashboard');

    // New warehouse routes within site context
    Route::resource('warehouses', WarehouseController::class)->names([
        'index' => 'site.warehouses.index',
        'create' => 'site.warehouses.create', 
        'store' => 'site.warehouses.store',
        'show' => 'site.warehouses.show',
        'edit' => 'site.warehouses.edit',
        'update' => 'site.warehouses.update',
        'destroy' => 'site.warehouses.destroy',
    ]);
});
```
- URLs will be: `/{site:slug}/warehouses/*`
- Automatic site context through route model binding
- Named routes with `site.warehouses.*` prefix
- Site ownership validation through route binding

**Files to create/modify**:
- `app/Http/Controllers/Site/WarehouseController.php` (new)
- `app/Http/Requests/Warehouse/StoreWarehouseRequest.php` (new)
- `app/Http/Requests/Warehouse/UpdateWarehouseRequest.php` (new)
- `app/Actions/Warehouse/StoreWarehouse.php` (new)
- `app/Actions/Warehouse/UpdateWarehouse.php` (new)
- `app/Actions/Warehouse/DeleteWarehouse.php` (new)
- `routes/site.php` (add warehouse routes)

### Phase 3: Frontend Implementation

#### Frontend Changes

**1. Create Warehouse List Page** (`resources/js/pages/site/warehouses/Index.vue`):
```vue
<!-- Page features:
- Data table with columns: code, name, address, locations_count, actions
- Search and filter functionality
- Pagination for large warehouse lists
- Create warehouse button (if has create_warehouses permission)
- Edit/Delete actions per row (with proper permission checks)
- Breadcrumb navigation: [Site Name] > Warehouses
-->
```
- Display warehouses in paginated table format
- Show locations count for each warehouse
- Conditional action buttons based on permissions
- Implement search/filter functionality
- Handle loading states and error messages

**2. Create Warehouse Form Components** (`resources/js/pages/site/warehouses/`):
```vue
<!-- Create.vue - Warehouse creation form -->
<!-- Edit.vue - Warehouse editing form -->
<!-- Shared form features:
- Code input with validation and uniqueness check
- Name input with required validation
- Address textarea with proper sizing
- Form submission with loading states
- Error handling and success messages
- Cancel/Save action buttons
-->
```
- Reusable form validation logic
- Real-time validation feedback
- Proper error message display
- Form state management with useForm()
- Navigate back to list after successful operations

**3. Update Site Navigation** (`resources/js/layouts/SiteLayout.vue`):
```vue
<!-- Add warehouse management link:
- Show only if user has manage_warehouses permission
- Highlight active state when on warehouse pages  
- Use proper Vietnamese label "Quản lý kho"
- Icon: warehouse or building icon
-->
```
- Conditional display based on manage_warehouses permission
- Proper active state highlighting
- Consistent with other navigation items

**4. Create TypeScript Definitions** (`resources/js/types/warehouse.d.ts`):
```typescript
// Type definitions:
// - Warehouse interface (id, code, name, address, site_id, locations_count)
// - WarehouseFormData interface for form handling
// - WarehousePermissions interface for permission checks
```
- Complete type safety for warehouse operations
- Form data interfaces for validation
- Permission-based type guards

**Files to create/modify**:
- `resources/js/pages/site/warehouses/Index.vue` (new)
- `resources/js/pages/site/warehouses/Create.vue` (new)  
- `resources/js/pages/site/warehouses/Edit.vue` (new)
- `resources/js/layouts/SiteLayout.vue` (update navigation)
- `resources/js/types/warehouse.d.ts` (new)

### Phase 4: Testing & Validation

#### Backend Tests

**1. Create Warehouse Model Tests** (`tests/Unit/WarehouseTest.php`):
- Test model relationships (site, locations)
- Test validation rules and constraints
- Test query scopes (forSite, withLocationsCount)
- Test soft delete functionality

**2. Create Warehouse Policy Tests** (`tests/Unit/WarehousePolicyTest.php`):
- Test permission-based authorization
- Test site ownership validation
- Test deletion constraints (locations check)
- Test policy registration

**3. Create Warehouse Controller Tests** (`tests/Feature/WarehouseManagementTest.php`):
```php
// Test scenarios:
// - List warehouses (authorized users only)
// - Create warehouse (with proper site assignment)
// - Update warehouse (owner only)
// - Delete warehouse (with dependency checks)
// - Permission-based access control
// - Validation error handling
```

**4. Create Warehouse Action Tests** (`tests/Unit/Actions/Warehouse/`):
- Test each action independently
- Test business logic validation
- Test error handling and edge cases
- Test site_id automatic assignment

#### Frontend Tests

**1. Create Warehouse Component Tests**:
- Test form validation and submission
- Test permission-based UI elements
- Test navigation and routing
- Test error state handling

**Files to create**:
- `tests/Unit/WarehouseTest.php`
- `tests/Unit/WarehousePolicyTest.php`
- `tests/Feature/WarehouseManagementTest.php`
- `tests/Unit/Actions/Warehouse/StoreWarehouseTest.php`
- `tests/Unit/Actions/Warehouse/UpdateWarehouseTest.php`
- `tests/Unit/Actions/Warehouse/DeleteWarehouseTest.php`

### Phase 5: Integration & Dependencies

#### Integration Points

**1. Site Integration**:
- Ensure warehouse count displayed in site management
- Handle cascade operations when site is deleted
- Validate site ownership in all warehouse operations

**2. Location Integration (Preparation for UC005)**:
- Setup hasMany relationship to locations
- Implement deletion constraints (cannot delete warehouse with locations)
- Prepare for location management integration

**3. Permission Integration**:
- Verify all warehouse permissions work correctly
- Test permission inheritance for site admins
- Ensure proper middleware application

#### Database Considerations

**1. Add Database Constraints**:
```sql
-- Ensure referential integrity
ALTER TABLE warehouses ADD CONSTRAINT fk_warehouses_site_id 
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE;

-- Ensure unique codes within site
CREATE UNIQUE INDEX idx_warehouses_site_code ON warehouses(site_id, code);
```

**2. Create Migration for Constraints** (`database/migrations/add_warehouse_constraints.php`):
- Add foreign key constraints
- Add unique index for (site_id, code)
- Add proper indexing for performance

## 📊 Success Metrics

### Functional Requirements Met:
- ✅ Create warehouses within user's site
- ✅ View list of warehouses with locations count  
- ✅ Update warehouse information
- ✅ Delete warehouses (with dependency validation)
- ✅ Unique warehouse codes within site scope
- ✅ Proper authorization and permission checks

### Technical Requirements Met:
- ✅ Follows Laravel best practices and conventions
- ✅ Comprehensive test coverage (>90%)
- ✅ Type-safe frontend implementation
- ✅ Proper error handling and validation
- ✅ Performance optimized queries
- ✅ Security through policies and middleware

## 🔄 Next Steps

1. **Complete UC005 (Manage Warehouse Location)** - Build upon warehouse foundation
2. **Integrate with Inventory System** - Connect warehouses to stock management
3. **Add Warehouse Analytics** - Usage statistics and reporting
4. **Implement Warehouse Settings** - Configuration and preferences per warehouse

## ⚠️ Important Notes

- **Site Ownership**: All warehouse operations must respect site ownership boundaries
- **Permission Granularity**: Use specific permissions (create_warehouses, edit_warehouses) not just manage_warehouses
- **Data Integrity**: Implement proper constraints and cascade rules
- **User Experience**: Provide clear feedback for all operations and error states
- **Performance**: Use eager loading for relationships and proper indexing
