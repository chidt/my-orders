# UC011 Implementation Plan - Manage Categories and Tags

## 🎯 Objective
Implement comprehensive categories and tags management system for products with multi-tenant support. SiteAdmin users can create, update, delete, and organize categories in hierarchical structure and manage tags within their site scope.

## 📋 Current State Analysis

### ✅ Already Exists:
- Database schema for Categories and Tags defined in ER diagram
- Multi-tenant Site model structure
- User permission system (Laravel Spatie Permissions)
- Site-scoped data isolation pattern established

### ❌ Missing Components:
- Category and Tag models
- Database migrations for categories and tags tables
- Permission definitions for category management
- Controllers and API endpoints
- Frontend pages and components
- Form validation classes
- Comprehensive test coverage

### 🏗️ Database Schema (from ER Diagram):

**Categories Table:**
- `id` (bigint, PK)
- `name` (string, required)
- `description` (text, optional)
- `order` (int, sorting)
- `parent_id` (bigint, FK, nullable - self-reference)
- `site_id` (bigint, FK, required - multi-tenant)
- `timestamps`

**Tags Table:**
- `id` (bigint, PK)
- `name` (string, required)
- `site_id` (bigint, FK, required - multi-tenant)
- `timestamps`

**ProductTags Table:** (Many-to-Many)
- `id` (bigint, PK)
- `product_id` (bigint, FK)
- `tag_id` (bigint, FK)

## 🚀 Implementation Plan

### Phase 1: Database Foundation

#### 1.1 Create Database Migrations

**Create Categories Migration:**
```bash
php artisan make:migration create_categories_table --no-interaction
```

**Migration Structure:**
- Primary key (id)
- name (string, 255, required)
- slug (string, 255, unique per site, indexed)
- description (text, nullable)
- order (integer, default 0)
- parent_id (unsignedBigInteger, nullable, foreign key to categories.id)
- site_id (unsignedBigInteger, required, foreign key to sites.id)
- is_active (boolean, default true)
- timestamps

**Create Tags Migration:**
```bash
php artisan make:migration create_tags_table --no-interaction
```

**Migration Structure:**
- Primary key (id)
- name (string, 100, required)
- slug (string, 100, unique per site, indexed)
- color (string, 7, default '#3b82f6' - hex color)
- site_id (unsignedBigInteger, required, foreign key to sites.id)
- timestamps

**Create Product Tags Pivot Migration:**
```bash
php artisan make:migration create_product_tags_table --no-interaction
```

**Migration Structure:**
- Primary key (id)
- product_id (unsignedBigInteger, foreign key to products.id)
- tag_id (unsignedBigInteger, foreign key to tags.id)
- Unique constraint on [product_id, tag_id]

#### 1.2 Create Indexes and Constraints
- Site-scoped uniqueness for category names
- Site-scoped uniqueness for tag names
- Proper foreign key constraints with cascade options
- Indexes for performance (site_id, parent_id, order)

### Phase 2: Model Development

#### 2.1 Create Category Model
```bash
php artisan make:model Category --factory --no-interaction
```

**Model Features:**
- Multi-tenant site scoping
- Self-referencing parent-child relationships
- Nested set or adjacency list for hierarchy
- Automatic slug generation from name
- Validation rules
- Query scopes for filtering
- Relationship methods

**Key Methods:**
- `children()` - hasMany relationship
- `parent()` - belongsTo relationship  
- `descendants()` - recursive children
- `ancestors()` - recursive parents
- `products()` - hasMany relationship
- `site()` - belongsTo relationship
- `scopeForSite()` - query scope
- `scopeRoots()` - top-level categories
- `scopeOrdered()` - ordered by 'order' field

#### 2.2 Create Tag Model
```bash
php artisan make:model Tag --factory --no-interaction
```

**Model Features:**
- Multi-tenant site scoping
- Many-to-many with products
- Automatic slug generation
- Color management
- Usage statistics

**Key Methods:**
- `products()` - belongsToMany relationship
- `site()` - belongsTo relationship
- `scopeForSite()` - query scope
- `scopePopular()` - by usage count
- `getUsageCountAttribute()` - count products

#### 2.3 Update Product Model (if exists)
- Add category relationship: `belongsTo(Category::class)`
- Add tags relationship: `belongsToMany(Tag::class)`

### Phase 3: Permission System

#### 3.1 Define Permissions
Create permission seeder or add to existing:
- `manage-categories` - Full category management
- `manage-tags` - Full tag management
- `view-categories` - View categories (if needed for staff)
- `view-tags` - View tags (if needed for staff)

#### 3.2 Middleware and Policies
Create policies for authorization:
```bash
php artisan make:policy CategoryPolicy --model=Category --no-interaction
php artisan make:policy TagPolicy --model=Tag --no-interaction
```

**Policy Methods:**
- `viewAny()` - Can list categories/tags
- `view()` - Can view specific category/tag
- `create()` - Can create new categories/tags
- `update()` - Can update categories/tags
- `delete()` - Can delete categories/tags
- Site ownership verification in all methods

### Phase 4: Backend API Development

#### 4.1 Create Controllers
```bash
php artisan make:controller CategoryController --resource --no-interaction
php artisan make:controller TagController --resource --no-interaction
```

#### 4.2 Category Controller Methods
- `index()` - List categories with tree structure
- `show()` - Single category with products count
- `store()` - Create new category
- `update()` - Update category
- `destroy()` - Delete category (with checks)
- `reorder()` - Update category order
- `move()` - Move category to different parent

**Special Features:**
- Tree structure response with nested children
- Product counts for each category
- Drag & drop reordering support
- Bulk operations (activate/deactivate)

#### 4.3 Tag Controller Methods
- `index()` - List tags with usage statistics
- `show()` - Single tag with products
- `store()` - Create new tag
- `update()` - Update tag
- `destroy()` - Delete tag
- `popular()` - Most used tags
- `merge()` - Merge duplicate tags

#### 4.4 Form Request Validation
```bash
php artisan make:request StoreCategoryRequest --no-interaction
php artisan make:request UpdateCategoryRequest --no-interaction
php artisan make:request StoreTagRequest --no-interaction
php artisan make:request UpdateTagRequest --no-interaction
```

**Category Validation Rules:**
- name: required, string, max:255, unique per site
- description: nullable, string, max:2000
- parent_id: nullable, exists:categories,id, same site
- order: integer, min:0
- No circular reference validation

**Tag Validation Rules:**
- name: required, string, max:100, unique per site
- color: nullable, regex hex color

### Phase 5: Frontend Development

#### 5.1 Create Vue Pages
**Categories Management Page:**
`resources/js/pages/Products/Categories/Index.vue`

**Features:**
- Tree view with expand/collapse
- Drag & drop reordering
- Inline editing
- Add category modal/form
- Delete confirmations
- Search functionality
- Product counts display

**Tags Management Page:**
`resources/js/pages/Products/Tags/Index.vue`

**Features:**
- Grid/list view of tags
- Tag cloud visualization
- Inline tag creation
- Color picker for tags
- Usage statistics
- Bulk operations
- Search and filter

#### 5.2 Create Vue Components
**CategoryTree Component:**
- Recursive tree rendering
- Drag & drop support
- Inline editing
- Actions menu (edit, delete, add child)

**TagManager Component:**
- Tag input with autocomplete
- Tag cloud display
- Color management
- Usage statistics

**CategorySelector Component:**
- Dropdown tree selector for forms
- Search within categories
- Breadcrumb display

#### 5.3 Navigation Integration
Add to site admin navigation:
```
Products
├── Categories (route: categories.index)
├── Tags (route: tags.index)
└── Products (existing)
```

### Phase 6: Advanced Features

#### 6.1 Category Management Features
- **Tree Operations:**
  - Drag & drop reordering
  - Move categories between parents
  - Bulk activate/deactivate
  - Copy category structure

- **Search & Filter:**
  - Search by name/description
  - Filter by parent category
  - Filter by product count
  - Filter active/inactive

#### 6.2 Tag Management Features
- **Smart Tags:**
  - Auto-suggestion based on existing tags
  - Tag merging for duplicates
  - Popular tags widget
  - Tag usage analytics

- **Visual Features:**
  - Tag cloud with size based on usage
  - Color coding system
  - Tag popularity metrics
  - Recently used tags

#### 6.3 Bulk Operations
- **Categories:**
  - Bulk delete with product reassignment
  - Bulk activate/deactivate
  - Export category structure
  - Import categories from CSV

- **Tags:**
  - Bulk delete unused tags
  - Tag cleanup (merge similar)
  - Export tag list
  - Tag usage reports

### Phase 7: Testing Strategy

#### 7.1 Model Tests
- Category hierarchy validation
- Tag uniqueness within site
- Relationship integrity
- Site scoping verification

#### 7.2 Feature Tests
- Category CRUD operations
- Tag CRUD operations
- Permission enforcement
- Site isolation verification
- Tree structure manipulation

#### 7.3 Browser Tests
- Category tree interaction
- Drag & drop functionality
- Form submissions
- Search and filtering
- Bulk operations

### Phase 8: Performance & Optimization

#### 8.1 Database Optimization
- Proper indexing strategy
- Query optimization for tree structures
- Eager loading for relationships
- Caching for category trees

#### 8.2 Frontend Optimization
- Virtual scrolling for large lists
- Lazy loading of tree branches
- Debounced search
- Optimistic updates

## 📁 File Structure

### Backend Files
```
app/
├── Models/
│   ├── Category.php
│   └── Tag.php
├── Http/
│   ├── Controllers/
│   │   ├── CategoryController.php
│   │   └── TagController.php
│   └── Requests/
│       ├── StoreCategoryRequest.php
│       ├── UpdateCategoryRequest.php
│       ├── StoreTagRequest.php
│       └── UpdateTagRequest.php
└── Policies/
    ├── CategoryPolicy.php
    └── TagPolicy.php

database/
├── migrations/
│   ├── create_categories_table.php
│   ├── create_tags_table.php
│   └── create_product_tags_table.php
├── factories/
│   ├── CategoryFactory.php
│   └── TagFactory.php
└── seeders/
    └── CategoryTagPermissionSeeder.php

routes/
└── web.php (add category/tag routes)
```

### Frontend Files
```
resources/js/
├── pages/
│   └── Products/
│       ├── Categories/
│       │   ├── Index.vue
│       │   ├── Create.vue
│       │   └── Edit.vue
│       └── Tags/
│           ├── Index.vue
│           ├── Create.vue
│           └── Edit.vue
├── components/
│   ├── CategoryTree.vue
│   ├── CategorySelector.vue
│   ├── TagManager.vue
│   ├── TagCloud.vue
│   └── BulkActions.vue
└── types/
    ├── category.ts
    └── tag.ts
```

### Test Files
```
tests/
├── Feature/
│   ├── CategoryManagementTest.php
│   ├── TagManagementTest.php
│   └── CategoryTagPermissionTest.php
├── Unit/
│   ├── CategoryModelTest.php
│   └── TagModelTest.php
└── Browser/
    ├── CategoryManagementTest.php
    └── TagManagementTest.php
```

## ⏱️ Implementation Timeline

### Week 1: Foundation
- [ ] Create database migrations
- [ ] Create models with relationships
- [ ] Set up permissions
- [ ] Basic controller structure

### Week 2: Backend API
- [ ] Complete controller methods
- [ ] Form validation classes
- [ ] Policy implementation
- [ ] Route definitions

### Week 3: Frontend Core
- [ ] Basic Vue pages
- [ ] Category tree component
- [ ] Tag management interface
- [ ] Navigation integration

### Week 4: Advanced Features
- [ ] Drag & drop functionality
- [ ] Search and filtering
- [ ] Bulk operations
- [ ] Performance optimizations

### Week 5: Testing & Polish
- [ ] Comprehensive test suite
- [ ] Browser testing
- [ ] Performance testing
- [ ] Bug fixes and refinements

## 🎯 Success Criteria

### Functional Requirements ✅
- [x] Create hierarchical categories (max 3 levels)
- [x] Manage tags with colors
- [x] Site-scoped data isolation
- [x] Permission-based access control
- [x] Tree structure manipulation
- [x] Search and filtering

### Technical Requirements ✅
- [x] Multi-tenant architecture
- [x] Proper relationship modeling
- [x] Comprehensive validation
- [x] Performance optimization
- [x] Test coverage > 90%
- [x] Mobile-responsive UI

### Business Rules ✅
- [x] Categories can have max 3 levels deep
- [x] Category names can be duplicated within site
- [x] Tag names must be unique within site
- [x] Cannot delete categories with products
- [x] Auto-cleanup unused tags after 30 days
- [x] Prevent circular category references

## 🔧 Technical Notes

### Site Isolation Strategy
All queries must include site_id filtering:
```php
Category::where('site_id', auth()->user()->currentSite->id)
Tag::where('site_id', auth()->user()->currentSite->id)
```

### Tree Structure Implementation
Use adjacency list pattern with:
- Recursive CTEs for modern databases
- Path enumeration for performance
- Nested set model for complex queries

### Permission Integration
Leverage existing Spatie permission system:
```php
$user->can('manage-categories')
Gate::authorize('update', $category)
```

### Frontend State Management
Use Inertia.js patterns:
- Server-side rendering with Vue
- Form handling with useForm
- Route-based navigation
- Shared data through props

This implementation plan provides a comprehensive roadmap for building a robust categories and tags management system that integrates seamlessly with the existing Laravel + Vue.js + Inertia.js architecture while maintaining multi-tenant data isolation and proper permission controls.

