# UC013 Implementation Plan - Manage Product Types

## 🎯 Objective
Implement comprehensive product types management system with multi-tenant support and site isolation. SiteAdmin users can create, update, delete, and organize product types with color coding and display preferences for their site.

## 📋 Current State Analysis

### ✅ Already Exists:
- Database schema for ProductTypes defined in ER diagram (updated with site_id)
- Multi-tenant Site model structure
- User permission system (Laravel Spatie Permissions)
- Site-scoped data isolation pattern established

### ❌ Missing Components:
- ProductType model
- Database migration to add site_id to product_types table
- Permission definitions for product type management
- Controllers and API endpoints
- Frontend pages and components
- Form validation classes
- Comprehensive test coverage

### 🏗️ Database Schema Updates Needed:

**ProductTypes Table Updates:**
- Add `site_id` (bigint, FK, required - multi-tenant)
- Add unique constraint on [name, site_id]
- Add foreign key constraint to sites table
- Update existing records to have site_id (if any exist)

## 🚀 Implementation Plan

### Phase 1: Database Foundation

#### 1.1 Create Migration to Add site_id to ProductTypes

**Create Migration:**
```bash
php artisan make:migration add_site_id_to_product_types_table --no-interaction
```

**Migration Structure:**
- Add site_id column (unsignedBigInteger, required)
- Add foreign key constraint to sites table
- Add unique constraint on [name, site_id]
- Add index on site_id for performance

**Migration Code:**
```php
Schema::table('product_types', function (Blueprint $table) {
    $table->unsignedBigInteger('site_id')->after('id');
    $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
    $table->unique(['name', 'site_id']);
    $table->index('site_id');
});
```

#### 1.2 Handle Existing Data (if any)
- Backup existing product_types data
- Assign default site_id for existing records
- Validate data integrity after migration

### Phase 2: Model Development

#### 2.1 Create ProductType Model
```bash
php artisan make:model ProductType --factory --no-interaction
```

**Model Features:**
- Multi-tenant site scoping
- Automatic slug generation from name
- Color validation and management
- Validation rules
- Query scopes for filtering
- Relationship methods

**Key Methods:**
- `site()` - belongsTo relationship
- `products()` - hasMany relationship (when Product model exists)
- `scopeForSite()` - query scope for current site
- `scopeOrdered()` - ordered by 'order' field
- `scopeShowOnFront()` - filter frontend display types
- `getProductsCountAttribute()` - count products using this type

**Model Properties:**
- Fillable: name, order, show_on_front, color, site_id
- Casts: show_on_front (boolean)
- Rules: name validation, color hex validation, order >= 0

#### 2.2 Update Site Model (if needed)
Add relationship to ProductType:
```php
public function productTypes(): HasMany
{
    return $this->hasMany(ProductType::class);
}
```

### Phase 3: Permission System

#### 3.1 Define Permissions
Create permission seeder or add to existing:
- `manage_product_types` - Full product type management
- `view_product_types` - View product types (if needed for staff)

#### 3.2 Create Policy
```bash
php artisan make:policy ProductTypePolicy --model=ProductType --no-interaction
```

**Policy Methods:**
- `viewAny()` - Can list product types
- `view()` - Can view specific product type
- `create()` - Can create new product types
- `update()` - Can update product types
- `delete()` - Can delete product types
- Site ownership verification in all methods

### Phase 4: Backend API Development

#### 4.1 Create Controller
```bash
php artisan make:controller ProductTypeController --resource --no-interaction
```

#### 4.2 Controller Methods
- `index()` - List product types for current site
- `show()` - Single product type with products count
- `store()` - Create new product type
- `update()` - Update product type
- `destroy()` - Delete product type (with usage checks)
- `reorder()` - Update display order via drag & drop

**Special Features:**
- Site-scoped queries in all methods
- Products count for each product type
- Drag & drop reordering support
- Bulk operations (show/hide on front)
- Color preview in responses

#### 4.3 Form Request Validation
```bash
php artisan make:request StoreProductTypeRequest --no-interaction
php artisan make:request UpdateProductTypeRequest --no-interaction
```

**Validation Rules:**
- name: required, string, max:100, unique per site
- order: integer, min:0
- show_on_front: boolean
- color: nullable, regex hex color pattern

**Custom Rules:**
- UniqueName rule for site-scoped validation
- HexColor rule for color validation
- ProductTypeUsageCheck for delete operations

### Phase 5: Frontend Development

#### 5.1 Create Vue Pages
**Product Types Management Page:**
`resources/js/pages/Products/ProductTypes/Index.vue`

**Features:**
- Table view with sortable columns
- Color preview badges
- Inline editing capabilities
- Add product type modal/form
- Delete confirmations with usage warnings
- Search functionality
- Drag & drop reordering
- Bulk operations toolbar

**Create/Edit Product Type Page:**
`resources/js/pages/Products/ProductTypes/Create.vue`
`resources/js/pages/Products/ProductTypes/Edit.vue`

**Features:**
- Form with validation
- Color picker component
- Real-time preview
- Order number input
- Show on front toggle
- Submit handling with proper error display

#### 5.2 Create Vue Components
**ProductTypeCard Component:**
- Display product type with color
- Show products count
- Quick actions (edit, delete)
- Drag handle for reordering

**ProductTypeForm Component:**
- Reusable form for create/edit
- Color picker integration
- Validation display
- Preview section

**ProductTypeSelector Component:**
- Dropdown selector for product forms
- Color-coded options
- Search within types
- Used when creating/editing products

#### 5.3 Navigation Integration
Add to site admin navigation:
```
Products
├── Categories (route: categories.index)
├── Tags (route: tags.index)
├── Product Types (route: product-types.index)
└── Products (existing)
```

### Phase 6: Advanced Features

#### 6.1 Product Type Management Features
- **Display Management:**
  - Drag & drop reordering
  - Bulk show/hide on frontend
  - Preview frontend layout
  - Copy types between sites (admin only)

- **Search & Filter:**
  - Search by name
  - Filter by frontend display status
  - Filter by color categories
  - Sort by name, order, product count

#### 6.2 Color Management Features
- **Color System:**
  - Predefined color palette
  - Custom hex color input
  - Color contrast validation
  - Color accessibility checking

- **Visual Features:**
  - Color preview in all lists
  - Color-coded badges
  - Theme integration
  - Dark mode support

#### 6.3 Analytics & Reporting
- **Usage Statistics:**
  - Most used product types
  - Frontend display performance
  - Product distribution by type
  - Color usage analytics

### Phase 7: Testing Strategy

#### 7.1 Model Tests
- ProductType creation and validation
- Site scoping verification
- Relationship integrity
- Color validation
- Order management

#### 7.2 Feature Tests
- ProductType CRUD operations
- Permission enforcement
- Site isolation verification
- Validation rules
- Bulk operations

#### 7.3 Browser Tests
- Product type management interface
- Drag & drop functionality
- Form submissions and validation
- Color picker interaction
- Search and filtering

### Phase 8: Performance & Optimization

#### 8.1 Database Optimization
- Proper indexing strategy
- Query optimization for site-scoped data
- Eager loading for relationships
- Caching for frequently accessed types

#### 8.2 Frontend Optimization
- Lazy loading for large lists
- Debounced search
- Optimistic updates
- Color picker performance

## 📁 File Structure

### Backend Files
```
app/
├── Models/
│   └── ProductType.php
├── Http/
│   ├── Controllers/
│   │   └── ProductTypeController.php
│   └── Requests/
│       ├── StoreProductTypeRequest.php
│       └── UpdateProductTypeRequest.php
└── Policies/
    └── ProductTypePolicy.php

database/
├── migrations/
│   └── add_site_id_to_product_types_table.php
├── factories/
│   └── ProductTypeFactory.php
└── seeders/
    └── ProductTypePermissionSeeder.php

routes/
└── web.php (add product type routes)
```

### Frontend Files
```
resources/js/
├── pages/
│   └── Products/
│       └── ProductTypes/
│           ├── Index.vue
│           ├── Create.vue
│           └── Edit.vue
├── components/
│   ├── ProductTypeCard.vue
│   ├── ProductTypeForm.vue
│   ├── ProductTypeSelector.vue
│   └── ColorPicker.vue
└── types/
    └── product-type.ts
```

### Test Files
```
tests/
├── Feature/
│   ├── ProductTypeManagementTest.php
│   └── ProductTypePermissionTest.php
├── Unit/
│   └── ProductTypeModelTest.php
└── Browser/
    └── ProductTypeManagementTest.php
```

## ⏱️ Implementation Timeline

### Week 1: Foundation (Days 1-2)
- [ ] Create migration for site_id
- [ ] Run migration and handle existing data
- [ ] Create ProductType model with relationships

### Week 1: Backend API (Days 3-4)
- [ ] Create controller with CRUD methods
- [ ] Implement form validation classes
- [ ] Set up policy and permissions
- [ ] Define API routes

### Week 1: Frontend Core (Day 5)
- [ ] Create basic Vue pages
- [ ] Implement product type table view
- [ ] Add navigation integration

### Week 2: Advanced Features (Days 1-3)
- [ ] Implement color picker component
- [ ] Add drag & drop reordering
- [ ] Create search and filtering
- [ ] Implement bulk operations

### Week 2: Testing & Polish (Days 4-5)
- [ ] Write comprehensive test suite
- [ ] Browser testing and UI polish
- [ ] Performance optimization
- [ ] Bug fixes and refinements

## 🎯 Success Criteria

### Functional Requirements ✅
- [x] Create, read, update, delete product types
- [x] Site-scoped data isolation
- [x] Permission-based access control
- [x] Color management with preview
- [x] Order management with drag & drop
- [x] Frontend display configuration

### Technical Requirements ✅
- [x] Multi-tenant architecture
- [x] Proper relationship modeling
- [x] Comprehensive validation
- [x] RESTful API design
- [x] Test coverage > 90%
- [x] Mobile-responsive UI

### Business Rules ✅
- [x] Unique names per site
- [x] Cannot delete types in use by products
- [x] Default product type for new sites
- [x] Color coding system
- [x] Frontend display management
- [x] Usage analytics

## 🔧 Technical Notes

### Site Isolation Strategy
All queries must include site_id filtering:
```php
ProductType::where('site_id', auth()->user()->currentSite->id)
```

### Color Management
Use hex color validation:
```php
'color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/']
```

### Permission Integration
Leverage existing Spatie permission system:
```php
$user->can('manage-product-types')
Gate::authorize('update', $productType)
```

### Frontend State Management
Use Inertia.js patterns:
- Server-side rendering with Vue
- Form handling with useForm
- Route-based navigation
- Shared data through props

This implementation plan provides a comprehensive roadmap for building a robust product types management system that integrates seamlessly with the existing Laravel + Vue.js + Inertia.js architecture while maintaining multi-tenant data isolation and proper permission controls.
