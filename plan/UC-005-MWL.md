# UC005 Implementation Plan - Manage Warehouse Location (MWL)

## 🎯 Objective
Implement warehouse location management functionality allowing users with `manage_warehouse_locations` permission to create, view, update, and delete storage locations within warehouses of their owned sites. Includes default location management and inventory tracking integration.

## 📋 Current State Analysis

### ✅ Already Implemented:
- Database schema with locations table (id, code, name, is_default, warehouse_id, qty_in_stock)
- Permission system with location-related permissions (`manage_warehouse_locations`, etc.)
- Warehouse-Location relationship (hasMany/belongsTo)
- Unique constraint on (warehouse_id, code) combination
- UC004 warehouse management foundation
- Site-Warehouse-Location hierarchical ownership model

### ❌ Missing Components:
- Location model and factory
- Location Policy with nested authorization (Site->Warehouse->Location)
- Location management controllers and form requests
- Routes for location CRUD operations within warehouse context
- Frontend location management pages with warehouse navigation
- Default location business logic and constraints
- Integration with inventory system (qty_in_stock tracking)
- Frontend type definitions for location permissions

## 🚀 Implementation Plan

### Phase 1: Backend Models & Authorization

#### Backend Changes

**1. Create Location Model** (`app/Models/Location.php`):
```php
// Model features:
// - Fillable: code, name, is_default
// - Relationships: belongsTo(Warehouse), hasMany(InventoryTransaction)
// - Scopes: forWarehouse(), defaults(), withStock()
// - Business logic: ensureOnlyOneDefault(), validateDeletion()
// - Attributes: qty_in_stock (calculated or cached)
```
- Define fillable attributes (code, name, is_default)
- Setup Warehouse relationship (belongsTo warehouse)
- Setup inventory relationships for future use
- Add query scopes for warehouse filtering
- Implement default location business logic
- Include soft deletes for audit trail

**2. Create Location Factory** (`database/factories/LocationFactory.php`):
```php
// Factory definitions:
// - Generate unique location codes (A1, B2, C3, etc.)
// - Create realistic location names (Shelf A1, Zone B, etc.)
// - Associate with existing warehouses via warehouse_id
// - Handle default location creation (only one per warehouse)
```
- Generate sequential/logical location codes
- Create realistic storage location names
- Support warehouse association for testing
- Handle default location constraints

**3. Create Location Policy** (`app/Policies/LocationPolicy.php`):
```php
// Policy methods (with nested authorization):
// - viewAny(User $user, Warehouse $warehouse): Check manage_warehouse_locations + warehouse ownership
// - view(User $user, Location $location): Check site ownership through warehouse
// - create(User $user, Warehouse $warehouse): Check create_warehouse_locations + warehouse ownership
// - update(User $user, Location $location): Check edit_warehouse_locations + nested ownership
// - delete(User $user, Location $location): Check delete_warehouse_locations + no inventory + not last default
```
- Verify nested ownership (Site->Warehouse->Location)
- Check specific location permissions
- Validate deletion constraints (inventory check, default location rules)
- Handle complex authorization scenarios
- Register policy in AppServiceProvider

**Files to create/modify**:
- `app/Models/Location.php` (new)
- `database/factories/LocationFactory.php` (new)
- `app/Policies/LocationPolicy.php` (new)
- `app/Providers/AppServiceProvider.php` (register policy)

### Phase 2: Backend Controllers & Business Logic

#### Backend Changes

**1. Create Location Management Controller** (`app/Http/Controllers/Site/LocationController.php`):
```php
// Controller methods (nested under warehouse):
// - index(Warehouse $warehouse): List locations for specific warehouse
// - create(Warehouse $warehouse): Show location creation form
// - store(StoreLocationRequest $request, Warehouse $warehouse): Create location
// - edit(Warehouse $warehouse, Location $location): Show edit form
// - update(UpdateLocationRequest $request, Warehouse $warehouse, Location $location): Update
// - destroy(Warehouse $warehouse, Location $location): Delete with business rule validation
```
- Use nested resource routing (warehouses/{warehouse}/locations)
- Apply Location Policy for all authorization
- Delegate complex business logic to Actions
- Return Inertia responses with proper nested data structure
- Include inventory information for each location

**2. Create Location Management Actions** (`app/Actions/Location/`):
```php
// Actions to implement:
// - StoreLocation.php: Handle creation with default location logic
// - UpdateLocation.php: Handle updates with default management
// - DeleteLocation.php: Validate deletion constraints and reassign default
// - SetDefaultLocation.php: Manage default location switching
// - ValidateLocationInventory.php: Check inventory before deletion
```
- Handle default location business rules (only one default per warehouse)
- Manage default switching when updating is_default flag
- Validate inventory constraints before deletion
- Auto-create default location when warehouse is empty
- Encapsulate complex default location management

**3. Create Form Requests** (`app/Http/Requests/Location/`):
```php
// StoreLocationRequest.php validation:
// - code: required|string|max:50|unique within warehouse scope
// - name: required|string|max:255
// - is_default: boolean|nullable (handle default logic in action)

// UpdateLocationRequest.php validation:
// - Same as store but exclude current location from unique check
// - Include nested authorization (warehouse ownership)
// - Handle default location switching validation
```
- Include nested authorization checks (warehouse->site ownership)
- Custom error messages in Vietnamese
- Validate unique code within warehouse scope
- Handle default location switching rules

**4. Add Nested Routes** (`routes/site.php`):
```php
// Update existing site routes file:
Route::prefix('{site:slug}')->group(function () {
    // Dashboard and warehouse routes (already exists)
    // ...existing routes...

    // New nested location routes within warehouse context
    Route::resource('warehouses.locations', LocationController::class)->names([
        'index' => 'site.warehouses.locations.index',
        'create' => 'site.warehouses.locations.create',
        'store' => 'site.warehouses.locations.store', 
        'show' => 'site.warehouses.locations.show',
        'edit' => 'site.warehouses.locations.edit',
        'update' => 'site.warehouses.locations.update',
        'destroy' => 'site.warehouses.locations.destroy',
    ]);
});
```
- URLs will be: `/{site:slug}/warehouses/{warehouse}/locations/*`
- Automatic nested route model binding (site -> warehouse -> location)
- Named routes with `site.warehouses.locations.*` prefix
- Hierarchical ownership validation through route binding

**Files to create/modify**:
- `app/Http/Controllers/Site/LocationController.php` (new)
- `app/Http/Requests/Location/StoreLocationRequest.php` (new)
- `app/Http/Requests/Location/UpdateLocationRequest.php` (new)
- `app/Actions/Location/StoreLocation.php` (new)
- `app/Actions/Location/UpdateLocation.php` (new)
- `app/Actions/Location/DeleteLocation.php` (new)
- `app/Actions/Location/SetDefaultLocation.php` (new)
- `routes/site.php` (add nested location routes)

### Phase 3: Frontend Implementation

#### Frontend Changes

**1. Create Location List Page** (`resources/js/pages/site/warehouses/locations/Index.vue`):
```vue
<!-- Page features:
- Display warehouse information header (name, code, address)
- Data table: code, name, is_default (badge), qty_in_stock, actions
- Breadcrumb: [Site Name] > Warehouses > [Warehouse Name] > Locations
- Create location button (if has create_warehouse_locations permission)
- Edit/Delete actions with proper permission checks
- Back to warehouse list navigation
- Default location visual indicators (badge/highlight)
-->
```
- Display parent warehouse context information
- Show locations in organized table format
- Visual indicators for default location
- Inventory information display (qty_in_stock)
- Conditional action buttons based on permissions
- Clear navigation back to warehouse management

**2. Create Location Form Components** (`resources/js/pages/site/warehouses/locations/`):
```vue
<!-- Create.vue - Location creation form -->
<!-- Edit.vue - Location editing form -->
<!-- Shared form features:
- Code input with warehouse-scoped validation
- Name input with descriptive placeholders
- Default location checkbox with business rule explanation
- Warehouse context display (read-only)
- Form submission with loading states
- Nested navigation (warehouse -> locations -> form)
-->
```
- Warehouse context display and navigation
- Default location toggle with clear explanation
- Real-time validation with warehouse scope
- Proper error handling for business rule violations
- Form state management with nested routing

**3. Update Warehouse Management Integration** (`resources/js/pages/site/warehouses/Index.vue`):
```vue
<!-- Add location management integration:
- "Manage Locations" action button per warehouse row
- Display locations count in warehouse list
- Quick access to location management from warehouse list
- Navigation flow: Warehouses -> Select Warehouse -> Manage Locations
-->
```
- Add location count display to warehouse table
- "Manage Locations" action button with proper permission check
- Seamless navigation between warehouse and location management
- Update warehouse actions to include location management

**4. Create Location Type Definitions** (`resources/js/types/location.d.ts`):
```typescript
// Type definitions:
// - Location interface (id, code, name, is_default, warehouse_id, qty_in_stock)
// - LocationFormData interface for form handling
// - LocationListProps interface with warehouse context
// - LocationPermissions interface for nested permission checks
// - WarehouseContext interface for parent data
```
- Complete type safety for nested location operations
- Form data interfaces with warehouse context
- Permission-based type guards for nested authorization
- Warehouse context types for proper data flow

**5. Update Navigation Components**:
```vue
<!-- Update breadcrumb component for nested navigation -->
<!-- Update sidebar to show active states for nested routes -->
<!-- Ensure proper active state highlighting for warehouse->location flow -->
```

**Files to create/modify**:
- `resources/js/pages/site/warehouses/locations/Index.vue` (new)
- `resources/js/pages/site/warehouses/locations/Create.vue` (new)
- `resources/js/pages/site/warehouses/locations/Edit.vue` (new)
- `resources/js/pages/site/warehouses/Index.vue` (update with location management)
- `resources/js/types/location.d.ts` (new)
- `resources/js/components/Breadcrumb.vue` (update for nested navigation)

### Phase 4: Default Location Business Logic

#### Backend Business Rules Implementation

**1. Create Default Location Manager** (`app/Services/DefaultLocationManager.php`):
```php
// Service responsibilities:
// - ensureWarehouseHasDefaultLocation(Warehouse $warehouse): void
// - switchDefaultLocation(Location $newDefault): void  
// - validateDefaultLocationDeletion(Location $location): bool
// - getDefaultLocationForWarehouse(Warehouse $warehouse): ?Location
// - createDefaultLocationForWarehouse(Warehouse $warehouse): Location
```
- Encapsulate all default location business logic
- Handle default location creation and switching
- Validate business rules before operations
- Ensure data consistency across operations

**2. Update Location Actions to Use Service**:
- Integrate DefaultLocationManager into all location actions
- Handle automatic default creation when warehouse is empty
- Manage default switching when is_default flag changes
- Validate default deletion constraints

**3. Create Database Observers** (`app/Observers/LocationObserver.php`):
```php
// Observer responsibilities:
// - creating(): Ensure only one default per warehouse
// - updating(): Handle default location switching
// - deleting(): Validate deletion constraints and reassign default
// - deleted(): Auto-assign new default if deleted location was default
```
- Automatic enforcement of default location business rules
- Prevent invalid states at database level
- Handle complex default switching logic
- Register observer in AppServiceProvider

**Files to create/modify**:
- `app/Services/DefaultLocationManager.php` (new)
- `app/Observers/LocationObserver.php` (new)
- `app/Actions/Location/*.php` (integrate service)
- `app/Providers/AppServiceProvider.php` (register observer)

### Phase 5: Testing & Validation

#### Backend Tests

**1. Create Location Model Tests** (`tests/Unit/LocationTest.php`):
- Test model relationships (warehouse, inventory)
- Test validation rules and constraints
- Test query scopes (forWarehouse, defaults, withStock)
- Test default location business logic

**2. Create Location Policy Tests** (`tests/Unit/LocationPolicyTest.php`):
- Test nested authorization (Site->Warehouse->Location)
- Test permission-based access control
- Test deletion constraint validation
- Test policy registration and binding

**3. Create Location Controller Tests** (`tests/Feature/LocationManagementTest.php`):
```php
// Test scenarios:
// - List locations for warehouse (authorized users only)
// - Create location with default logic
// - Update location with default switching
// - Delete location with inventory/default constraints
// - Nested authorization (warehouse ownership)
// - Default location business rules enforcement
```

**4. Create Default Location Manager Tests** (`tests/Unit/DefaultLocationManagerTest.php`):
- Test default location creation and switching
- Test business rule validation
- Test edge cases (empty warehouse, last location deletion)
- Test data consistency scenarios

**5. Create Location Action Tests** (`tests/Unit/Actions/Location/`):
- Test each action independently with business rules
- Test nested authorization scenarios
- Test default location management
- Test inventory constraint validation

#### Frontend Tests

**1. Create Location Component Tests**:
- Test nested form validation and submission
- Test warehouse context display
- Test permission-based UI elements
- Test default location visual indicators
- Test navigation between warehouse and location management

**Files to create**:
- `tests/Unit/LocationTest.php`
- `tests/Unit/LocationPolicyTest.php`  
- `tests/Feature/LocationManagementTest.php`
- `tests/Unit/DefaultLocationManagerTest.php`
- `tests/Unit/Actions/Location/StoreLocationTest.php`
- `tests/Unit/Actions/Location/UpdateLocationTest.php`
- `tests/Unit/Actions/Location/DeleteLocationTest.php`

### Phase 6: Integration & Inventory Preparation

#### Integration Points

**1. Warehouse Integration**:
- Update warehouse deletion to handle location cascade
- Display location count in warehouse management
- Ensure warehouse-location relationship integrity
- Handle warehouse deletion with location cleanup

**2. Inventory System Preparation**:
- Setup foundation for qty_in_stock tracking
- Create inventory transaction relationship structure
- Prepare for future inventory management integration
- Implement stock display in location management

**3. Site Management Integration**:
- Ensure proper site ownership validation throughout chain
- Handle site deletion cascade (Site->Warehouse->Location)
- Maintain data integrity across nested relationships

#### Database Enhancements

**1. Add Database Constraints and Indexes**:
```sql
-- Ensure referential integrity
ALTER TABLE locations ADD CONSTRAINT fk_locations_warehouse_id 
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE;

-- Ensure unique codes within warehouse
CREATE UNIQUE INDEX idx_locations_warehouse_code ON locations(warehouse_id, code);

-- Optimize default location queries
CREATE INDEX idx_locations_default ON locations(warehouse_id, is_default) WHERE is_default = true;
```

**2. Create Migration for Constraints** (`database/migrations/add_location_constraints.php`):
- Add foreign key constraints for referential integrity
- Add unique index for (warehouse_id, code)
- Add conditional index for default location queries
- Add proper indexing for performance optimization

## 📊 Success Metrics

### Functional Requirements Met:
- ✅ Create locations within warehouse scope
- ✅ View locations with inventory information
- ✅ Update location information with default management
- ✅ Delete locations with proper constraint validation
- ✅ Enforce single default location per warehouse
- ✅ Nested authorization (Site->Warehouse->Location)
- ✅ Automatic default location management

### Technical Requirements Met:
- ✅ Follows Laravel best practices with nested resources
- ✅ Comprehensive test coverage including business rules
- ✅ Type-safe frontend with nested routing
- ✅ Proper error handling for complex business logic
- ✅ Performance optimized with proper indexing
- ✅ Security through nested policy authorization

## 🔄 Next Steps

1. **Integrate with Inventory Management** - Connect locations to stock tracking system
2. **Add Location Analytics** - Usage statistics and inventory reporting
3. **Implement Location Settings** - Configuration per location (capacity, type, etc.)
4. **Add Barcode/QR Code Support** - Physical location identification
5. **Create Location Import/Export** - Bulk location management tools

## ⚠️ Important Notes

- **Nested Authorization**: All location operations must validate Site->Warehouse->Location ownership chain
- **Default Location Rules**: Always maintain exactly one default location per warehouse
- **Inventory Constraints**: Prevent location deletion when inventory exists (future feature)
- **Data Integrity**: Implement proper cascade rules and constraint validation
- **User Experience**: Provide clear navigation and context for nested resource management
- **Performance**: Use eager loading for nested relationships and optimize queries with proper indexing
- **Business Logic**: Encapsulate complex default location management in dedicated service classes
