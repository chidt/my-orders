# UC012 Implementation Plan - Manage Attributes

## 🎯 Objective
Implement comprehensive product attributes management system with multi-tenant support and site isolation. SiteAdmin users can create, update, delete, and organize product attributes (like Size, Color, Material) and their values within their site scope, serving as critical prerequisite for UC006-MP (Product Management).

## 📋 Current State Analysis

### ✅ Already Exists:
- Database schema for Attributes defined in ER diagram with site_id
- ProductAttributeValues and ProductItemAttributeValues tables defined
- Multi-tenant Site model structure  
- User permission system (Laravel Spatie Permissions)
- Site-scoped data isolation pattern established
- Authentication system (UC001, UC002 completed)

### ❌ Missing Components:
- Attribute model with site isolation
- ProductAttributeValue model for attribute values
- Permission definitions for attribute management  
- Controllers and API endpoints for CRUD operations
- Frontend pages and components for attribute management
- Form validation classes with Vietnamese messages
- Comprehensive test coverage
- Business logic for attribute value management

### 🏗️ Database Schema Analysis:

**Attributes Table (Already Defined):**
```sql
Attributes {
    bigint id PK ""
    string name "required"           -- e.g., "Kích Thước", "Màu Sắc"
    string code "required"           -- e.g., "size", "color" (kebab-case)
    text description ""              -- Optional description
    int order "default 0"           -- Display order
    bigint site_id FK "required"    -- Multi-tenant isolation
}
```

**ProductAttributeValues Table (Already Defined):**
```sql
ProductAttributeValues {
    bigint id PK ""
    string code "required"                    -- e.g., "s", "m", "l" or "red", "blue"
    string value "required"                   -- e.g., "Size S", "Màu Đỏ"
    int order "required"                      -- Display order
    decimal purchase_addition_value ""        -- Additional cost for purchase
    decimal partner_addition_value ""         -- Additional cost for partners
    decimal addition_value ""                 -- Additional cost for customers
    bigint product_id FK "required"           -- Link to specific product
    bigint attribute_id FK "required"         -- Link to attribute
}
```

**Constraints Needed:**
- Unique constraint on [name, site_id] for Attributes
- Unique constraint on [code, site_id] for Attributes
- Foreign key constraints to sites table
- Indexes on site_id for performance

## 🚀 Implementation Plan

### Phase 1: Database Foundation

#### 1.1 Verify Database Schema
**Check Current Migrations:**
```bash
vendor/bin/sail artisan migrate:status
```

**Verify Attributes Table Structure:**
- Ensure attributes table has site_id column
- Check if indexes and constraints are properly set
- Validate ProductAttributeValues table structure

#### 1.2 Create Missing Constraints (if needed)
If site_id constraints are missing, create migration:
```bash
vendor/bin/sail artisan make:migration add_constraints_to_attributes_table --no-interaction
```

**Migration Content:**
```php
Schema::table('attributes', function (Blueprint $table) {
    $table->unique(['name', 'site_id']);
    $table->unique(['code', 'site_id']);
    $table->index('site_id');
});
```

### Phase 2: Model Development

#### 2.1 Create Attribute Model
```bash
vendor/bin/sail artisan make:model Attribute --factory --no-interaction
```

**Model Features:**
- Multi-tenant site scoping
- Automatic code generation from name (kebab-case)
- Validation rules for name, code, site_id
- Query scopes for filtering and ordering
- Relationship methods

**Key Methods:**
- `site()` - belongsTo relationship with Site
- `productAttributeValues()` - hasMany relationship
- `scopeForSite()` - query scope for current site
- `scopeOrdered()` - ordered by 'order' field
- `getUsageCountAttribute()` - count products using this attribute
- `generateCodeFromName()` - auto-generate kebab-case code

**Model Properties:**
- Fillable: name, code, description, order, site_id
- Rules: name validation, code format validation, uniqueness within site
- Casts: order (integer)

#### 2.2 Create ProductAttributeValue Model
```bash
vendor/bin/sail artisan make:model ProductAttributeValue --factory --no-interaction
```

**Model Features:**
- Link between Products and Attributes
- Support for additional pricing values
- Validation for code and value fields
- Query scopes for filtering

**Key Methods:**
- `product()` - belongsTo relationship
- `attribute()` - belongsTo relationship  
- `productItems()` - hasManyThrough via ProductItemAttributeValues
- `scopeForAttribute()` - filter by attribute
- `scopeOrdered()` - ordered by 'order' field

#### 2.3 Update Site Model
Add relationship to Attribute:
```php
public function attributes(): HasMany
{
    return $this->hasMany(Attribute::class);
}
```

### Phase 3: Permission System

#### 3.1 Define Permissions
Create permissions in database seeder:
```php
// Attribute Management Permissions
'manage_attributes',           // Full CRUD access
'attributes_view',            // View attributes list
'attributes_create',          // Create new attributes
'attributes_update',          // Update existing attributes
'attributes_delete',          // Delete attributes
'attribute_values_manage',    // Manage attribute values
```

#### 3.2 Create Policy Classes
```bash
vendor/bin/sail artisan make:policy AttributePolicy --model=Attribute --no-interaction
```

**Policy Methods:**
- `viewAny()` - Check site ownership for index
- `view()` - Check attribute belongs to user's site
- `create()` - Check manage_attributes permission
- `update()` - Check ownership and permission
- `delete()` - Check ownership, permission, and usage constraints

#### 3.3 Role Assignment
Update role assignments in seeder:
- **Admin**: All attribute permissions
- **SiteAdmin**: All attribute permissions for their site

### Phase 4: Backend Development

#### 4.1 Create Action Classes
Following Action Design Pattern:

```bash
vendor/bin/sail artisan make:class Actions/Attributes/CreateAttributeAction --no-interaction
vendor/bin/sail artisan make:class Actions/Attributes/UpdateAttributeAction --no-interaction
vendor/bin/sail artisan make:class Actions/Attributes/DeleteAttributeAction --no-interaction
vendor/bin/sail artisan make:class Actions/Attributes/ManageAttributeValuesAction --no-interaction
```

**Action Responsibilities:**
- Business logic separation from controllers
- Data validation and processing
- Relationship management
- Site isolation enforcement

#### 4.2 Create Form Request Classes
```bash
vendor/bin/sail artisan make:request Attributes/StoreAttributeRequest --no-interaction
vendor/bin/sail artisan make:request Attributes/UpdateAttributeRequest --no-interaction
vendor/bin/sail artisan make:request Attributes/StoreAttributeValueRequest --no-interaction
```

**Validation Rules:**
- Attribute name: required, max:255, unique within site
- Attribute code: required, kebab-case format, unique within site
- Site isolation validation
- Vietnamese error messages

#### 4.3 Create Controllers
```bash
vendor/bin/sail artisan make:controller Admin/AttributeController --resource --no-interaction
vendor/bin/sail artisan make:controller Admin/AttributeValueController --no-interaction
```

**Controller Features:**
- RESTful resource methods (index, create, store, show, edit, update, destroy)
- Inertia.js integration for Vue.js frontend
- Policy authorization checks
- Proper error handling and responses

#### 4.4 Define Routes
Add to `routes/admin.php`:
```php
Route::resource('attributes', AttributeController::class);
Route::post('attributes/{attribute}/values', [AttributeValueController::class, 'store'])
    ->name('attributes.values.store');
Route::put('attributes/{attribute}/values/{value}', [AttributeValueController::class, 'update'])
    ->name('attributes.values.update');
Route::delete('attributes/{attribute}/values/{value}', [AttributeValueController::class, 'destroy'])
    ->name('attributes.values.destroy');
```

### Phase 5: Frontend Development

#### 5.1 Create Vue.js Pages
**Pages to create:**
```
resources/js/pages/Admin/Attributes/Index.vue          -- List view
resources/js/pages/Admin/Attributes/Create.vue        -- Create form
resources/js/pages/Admin/Attributes/Edit.vue          -- Edit form
resources/js/pages/Admin/Attributes/Values.vue        -- Manage values
```

#### 5.2 Frontend Features
**Attribute Index Page:**
- Responsive table with search and filtering
- Pagination support
- Bulk actions (delete multiple)
- Quick actions (edit, delete, manage values)
- Vietnamese localization

**Attribute Form Pages:**
- Clean form design with validation
- Auto-generate code from name
- Real-time validation feedback
- Success/error notifications
- Mobile-responsive layout

**Attribute Values Management:**
- Inline editing of values
- Drag-and-drop ordering
- Bulk value creation
- Price adjustments per value
- Preview of product impact

#### 5.3 Create Reusable Components
```
resources/js/components/Attributes/AttributeSelector.vue       -- Multi-select for products
resources/js/components/Attributes/AttributeValueManager.vue   -- Value CRUD component
resources/js/components/Attributes/AttributePreview.vue       -- Preview component
```

### Phase 6: Testing Implementation

#### 6.1 Model Tests
```bash
vendor/bin/sail artisan make:test --unit Models/AttributeTest --pest --no-interaction
vendor/bin/sail artisan make:test --unit Models/ProductAttributeValueTest --pest --no-interaction
```

**Test Coverage:**
- Site isolation validation
- Relationship methods
- Scope methods
- Business logic validation
- Edge cases and error conditions

#### 6.2 Feature Tests
```bash
vendor/bin/sail artisan make:test Feature/AttributeManagementTest --pest --no-interaction
vendor/bin/sail artisan make:test Feature/AttributeValueManagementTest --pest --no-interaction
```

**Test Scenarios:**
- CRUD operations for attributes
- Site isolation enforcement
- Permission-based access control
- Attribute value management
- Integration with product system

#### 6.3 Browser Tests
```bash
vendor/bin/sail artisan make:test --browser AttributeManagementBrowserTest --pest --no-interaction
```

**Browser Test Scenarios:**
- Complete attribute creation workflow
- Attribute value management interface
- Search and filtering functionality
- Mobile responsiveness
- Error handling and validation

### Phase 7: Advanced Features

#### 7.1 Attribute Templates
**Common Templates:**
- Fashion attributes: Size, Color, Material, Style
- Electronics: Brand, Model, Warranty, Color
- Food: Weight, Expiry Date, Flavor, Package Type

#### 7.2 Import/Export Functionality
- Excel/CSV import of attributes and values
- Bulk attribute creation from templates
- Export current attributes for backup

#### 7.3 Attribute Grouping
- Group related attributes (Physical, Style, Technical)
- Conditional attribute display
- Attribute dependencies validation

## 🧪 Testing Strategy

### Unit Testing (Target: 100% Model Coverage)
- Attribute model methods and scopes
- ProductAttributeValue model relationships
- Business logic validation
- Site isolation enforcement

### Feature Testing (Target: All Endpoints)
- Authentication and authorization
- CRUD operations for attributes
- Attribute value management
- Policy enforcement
- Error handling

### Browser Testing (Target: Critical Paths)
- Complete user workflows
- Form validation and submission
- Search and filtering
- Mobile responsiveness

## 📈 Success Metrics

### Technical Metrics
- All tests passing (100% success rate)
- Code coverage > 90%
- Performance: Page load time < 2s
- Database queries optimized (N+1 prevention)

### Business Metrics
- Attribute creation time < 30 seconds
- Zero site data leakage
- Support for 50+ attributes per site
- Seamless integration with UC006-MP

## 🔄 Dependencies & Integration

### Prerequisites (MUST be completed):
- ✅ UC001-REG (User Registration & Sites)
- ✅ UC002-LOG (Authentication System)

### Integration Points:
- **UC006-MP (Manage Products)**: Primary consumer of attributes
- **UC008-MO (Order Management)**: Display attributes in orders
- **UC010-MI (Inventory Management)**: Track by attribute combinations

### Database Relationships:
- `Attributes` → `Sites` (belongs to)
- `Attributes` → `ProductAttributeValues` (has many)
- `ProductAttributeValues` → `Products` (belongs to)
- `ProductAttributeValues` → `ProductItemAttributeValues` (has many through)

## 🚀 Deployment Checklist

### Database
- [ ] All migrations executed successfully
- [ ] Indexes created for performance
- [ ] Foreign key constraints validated
- [ ] Sample data seeded for testing

### Backend
- [ ] Models tested and working
- [ ] Controllers returning correct responses
- [ ] Policies enforcing site isolation
- [ ] API endpoints documented

### Frontend  
- [ ] All pages rendering correctly
- [ ] Form validation working
- [ ] Mobile responsive design
- [ ] Vietnamese translations complete

### Integration
- [ ] Wayfinder routes generated
- [ ] Laravel Pint formatting applied
- [ ] All tests passing
- [ ] Ready for UC006-MP integration

## ⚠️ Critical Notes

1. **PREREQUISITE STATUS**: UC012-MA must be 100% complete before UC006-MP can begin
2. **SITE ISOLATION**: Every query must include site_id filtering
3. **PERFORMANCE**: Implement eager loading to prevent N+1 queries
4. **VALIDATION**: Code must be kebab-case, unique within site
5. **INTEGRATION**: Design with UC006-MP requirements in mind

---

**Implementation Priority**: 🔴 **CRITICAL HIGH** - Blocking dependency for product management
**Estimated Timeline**: 5-7 days for complete implementation
**Next Steps**: Begin with Phase 1 database validation and proceed sequentially through phases
