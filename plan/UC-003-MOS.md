# UC003 Implementation Plan - Manage Own Site (MOS)

## 🎯 Objective
Implement site management functionality allowing users with `manage-own-site` permission to update their owned site information including name, slug, description, and product prefix settings through a secure web interface with proper authorization.

## 📋 Current State Analysis

### ✅ Already Implemented:
- Site model with proper fillable fields (name, slug, description, settings, user_id)
- Sites database table with JSON settings column
- User-Site relationship (belongsTo/hasMany)
- Permission system with `manage-own-site` permission
- CheckRole middleware for role verification
- Spatie Laravel Permission package configured
- Settings routes structure in place

### ❌ Missing Components:
- Site Policy for ownership authorization
- Site management controllers and form requests
- Routes for site management functionality
- Frontend site management page/form
- Sidebar navigation conditional display
- Site settings validation for product_prefix
- Frontend type definitions for site permissions

## 🚀 Implementation Plan

### Phase 1: Backend Authorization & Policy

#### Backend Changes

**1. Create Site Policy** (`app/Policies/SitePolicy.php`):
```php
// Policy methods to implement:
// - update(User $user, Site $site): bool - Check if user owns the site
// - viewAny(User $user): bool - Check if user has manage-own-site permission
```
- Verify user ownership (site->user_id === auth->id)
- Check `manage-own-site` permission
- Register policy in AppServiceProvider

**2. Update AppServiceProvider** (`app/Providers/AppServiceProvider.php`):
- Register SitePolicy in boot() method
- Ensure proper policy binding

**Files to create/modify**:
- `app/Policies/SitePolicy.php` (new)
- `app/Providers/AppServiceProvider.php` (register policy)

### Phase 2: Backend Controllers & Validation

#### Backend Changes

**1. Create Site Management Controller** (`app/Http/Controllers/Settings/SiteController.php`):
```php
// Controller methods:
// - edit(): Show site management form  
// - update(UpdateSiteRequest $request, Site $site): Redirect with success
```
- Use Site Policy for authorization
- Delegate business logic to Actions
- Return proper Inertia responses with minimal logic

**2. Create Site Management Actions** (`app/Actions/Site/`):
```php
// Actions to implement:
// - UpdateSiteInformation.php: Handle site data updates
// - ValidateProductPrefix.php: Validate and format product prefix
// - GenerateSlugFromName.php: Auto-generate slug from site name
```
- Follow single responsibility principle
- Encapsulate business logic separate from controllers
- Make actions testable and reusable

**2. Create Form Request** (`app/Http/Requests/UpdateSiteRequest.php`):
```php
// Validation rules:
// - name: required|string|max:255
// - slug: required|string|unique:sites,slug,{id}|regex:/^[a-z0-9-]+$/
// - description: nullable|string|max:2000  
// - settings.product_prefix: nullable|string|max:5|regex:/^[A-Z0-9]+$/
```
- Include proper authorization check
- Custom error messages in Vietnamese
- Handle settings JSON structure validation
- Prepare validated data for Actions

**3. Add Routes** (`routes/settings.php`):
```php
// New routes:
// GET /settings/site - Show site management form
// PUT /settings/site - Update site information
```
- Apply proper middleware (auth, permission)
- Use route model binding for site ownership
- Group under settings middleware

**Files to create/modify**:
- `app/Http/Controllers/Settings/SiteController.php` (new)
- `app/Http/Requests/UpdateSiteRequest.php` (new)
- `app/Actions/Site/UpdateSiteInformation.php` (new)
- `app/Actions/Site/ValidateProductPrefix.php` (new) 
- `app/Actions/Site/GenerateSlugFromName.php` (new)
- `routes/settings.php` (add routes)

### Phase 3: Frontend Implementation

#### Frontend Changes - Activating `inertia-vue-development` skill

**1. Update AppSidebar Component** (`resources/js/components/AppSidebar.vue`):
```typescript
// Add to mainNavItems conditionally:
// - Check if user has manage-own-site permission
// - Check if current site belongs to user
// - Add "Quản lý trang web" menu item with Settings icon
```
- Import proper icons (Settings from lucide-vue-next)
- Add conditional rendering logic
- Use proper TypeScript types

**2. Create Site Management Page** (`resources/js/Pages/settings/Site.vue`):
```vue
// Components to include:
// - Site name input field
// - Site slug input with format validation
// - Site description textarea
// - Product prefix input with example
// - Save/Cancel buttons with proper form handling
```
- Use Inertia `useForm` for form management
- Implement proper validation display
- Add loading states and success messages
- Use Tailwind CSS for consistent styling

**3. Update Type Definitions** (`resources/js/types/index.ts`):
```typescript
// Add interface definitions:
// - Site interface with all properties
// - User interface updated with site and permissions
// - Settings interface for product_prefix
```
- Proper TypeScript support for site management
- Include permissions array in User type
- Add proper type checking

**Files to create/modify**:
- `resources/js/components/AppSidebar.vue` (update mainNavItems logic)
- `resources/js/Pages/settings/Site.vue` (new)
- `resources/js/types/index.ts` (add Site interface)

### Phase 4: Testing Implementation

#### Backend Tests - Activating `pest-testing` skill

**1. Create Site Policy Test** (`tests/Unit/SitePolicyTest.php`):
```php
// Test scenarios:
// - User can update own site
// - User cannot update other's site  
// - User with permission can access site management
// - User without permission cannot access
```

**2. Create Site Management Feature Test** (`tests/Feature/SiteManagementTest.php`):
```php  
// Test scenarios:
// - Authorized user can view site edit form
// - Authorized user can update site information
// - Validation rules work correctly for all fields
// - Product prefix validation works
// - Unauthorized access is blocked
// - Site slug uniqueness validation
```

**3. Create Actions Unit Tests** (`tests/Unit/Actions/Site/`):
```php
// Test files:
// - UpdateSiteInformationTest.php: Test site data updates
// - ValidateProductPrefixTest.php: Test product prefix validation
// - GenerateSlugFromNameTest.php: Test slug generation logic
```

**4. Create Frontend Component Tests** (`tests/Browser/SiteManagementTest.php`):
```php
// Test scenarios using Laravel Dusk:
// - Sidebar shows site management link conditionally
// - Site management form displays correctly
// - Form submission works with validation
// - Success messages display properly
```

**Files to create**:
- `tests/Unit/SitePolicyTest.php` (new)
- `tests/Feature/SiteManagementTest.php` (new)
- `tests/Unit/Actions/Site/UpdateSiteInformationTest.php` (new)
- `tests/Unit/Actions/Site/ValidateProductPrefixTest.php` (new)
- `tests/Unit/Actions/Site/GenerateSlugFromNameTest.php` (new)
- `tests/Browser/SiteManagementTest.php` (new)

### Phase 5: Security & Performance

#### Security Implementation

**1. Enhanced Authorization**:
- Implement CSRF protection (automatic with Laravel forms)
- Add rate limiting for site updates
- Sanitize all input data properly
- Validate JSON settings structure

**2. Performance Optimization**:
- Eager load relationships where needed
- Cache user permissions for sidebar logic
- Optimize database queries

**Files to review/modify**:
- Middleware configuration
- Route caching considerations
- Database indexing verification

## 📅 Implementation Timeline

| Phase | Duration | Dependencies |
|-------|----------|--------------|
| Phase 1 | 4 hours | None |
| Phase 2 | 6 hours | Phase 1 complete |
| Phase 3 | 8 hours | Phase 2 complete |
| Phase 4 | 6 hours | Phase 3 complete |
| Phase 5 | 2 hours | All phases complete |
| **Total** | **26 hours** | Sequential completion |

## 🧪 Testing Strategy

### Backend Testing
1. **Unit Tests**: Policy authorization logic and Actions business logic
2. **Feature Tests**: Controller endpoints and validation
3. **Integration Tests**: Complete site management workflow

### Frontend Testing  
1. **Component Tests**: Sidebar conditional rendering
2. **Feature Tests**: Site management form functionality
3. **End-to-End Tests**: Complete user workflow

### Manual Testing Checklist
- [ ] User with `manage-own-site` permission sees sidebar item
- [ ] User without permission doesn't see sidebar item  
- [ ] Site owner can access and update site information
- [ ] Non-owner cannot access other sites
- [ ] All validation rules work correctly
- [ ] Product prefix setting saves properly
- [ ] Slug uniqueness validation prevents conflicts
- [ ] Form displays proper error messages
- [ ] Success messages show after updates

## 🚨 Risk Assessment

### High Risk
- **Site ownership validation**: Critical security requirement
- **Permission checking**: Must be bulletproof
- **Slug uniqueness**: Database integrity essential

### Medium Risk  
- **Frontend state management**: Form complexity with nested settings
- **Route authorization**: Multiple middleware layers

### Low Risk
- **UI/UX implementation**: Standard form patterns
- **Product prefix validation**: Simple string validation

## 📋 Definition of Done

### Backend Requirements
- [ ] Site Policy implemented with proper authorization
- [ ] Site management controller with CRUD operations
- [ ] Actions classes for business logic separation
- [ ] Form request validation with all rules
- [ ] Routes properly secured with middleware
- [ ] All backend tests passing (Unit + Feature)

### Frontend Requirements  
- [ ] Sidebar conditionally shows management link
- [ ] Site management page fully functional
- [ ] Form validation working client-side
- [ ] TypeScript types properly defined
- [ ] All frontend tests passing

### Quality Assurance
- [ ] Code formatted with Laravel Pint
- [ ] All tests passing (`vendor/bin/sail artisan test`)
- [ ] No security vulnerabilities
- [ ] Performance meets requirements
- [ ] Documentation updated

## 📚 Technical Notes

### Database Considerations
- Site `settings` column stores JSON with `product_prefix` key
- Ensure proper JSON validation in requests
- Consider indexing on `user_id` for performance

### Frontend Architecture  
- Follow existing Vue/Inertia patterns
- Use composition API consistently
- Implement proper TypeScript throughout

### Security Implementation
- Always verify site ownership in policy
- Use Laravel's built-in CSRF protection
- Sanitize all user inputs
- Implement proper error handling

### Performance Considerations
- Cache permission checks where appropriate
- Optimize database queries with eager loading
- Consider implementing frontend caching for user permissions

---

*This implementation plan follows the established Laravel Boost Guidelines and maintains consistency with existing UC-001-REG and UC-002-LOG implementations.*
