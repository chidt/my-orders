# UC001 Implementation Plan - User Registration with Unique Email/Phone and Site Creation

## 🎯 Objective
Implement user registration flow that creates both a user account and their associated site with unique validation for email, phone number, and site slug. Features auto-generated site slug with manual override capability.

## 📋 Current State Analysis

### ✅ Already Implemented:
- Database schema with unique constraints for email, phone_number, and site slug
- User and Site models with proper relationships  
- Validation rules for unique email and phone number
- Site validation rules with unique slug
- Comprehensive test coverage for validations
- Fortify registration system configured

### ❌ Critical Issues:
- Frontend registration form missing required `phone_number` field (current form is broken)
- Site creation not integrated into registration flow
- No site management UI components

## 🚀 Implementation Plan

### Phase 1: Fix Critical Registration Issue (URGENT)
**Problem**: Current registration form fails validation due to missing phone_number field

**Tasks**:
1. Add phone_number input field to Register.vue
2. Add Vietnamese label "Số điện thoại" 
3. Add proper InputError component for validation
4. Test registration flow works

**Files to modify**: `resources/js/pages/auth/Register.vue`

### Phase 2: Integrate Site Creation into Registration

#### Backend Changes

**1. Extend CreateNewUser Action** (`app/Actions/Fortify/CreateNewUser.php`):
- Import Site model and DB facade
- Add SiteValidationRules trait usage
- Implement database transaction for safety
- Create site first, then user with site_id
- Assign default SiteAdmin role to newly created user
- Link site back to user

**2. Create SiteValidationRules Trait** (`app/Concerns/SiteValidationRules.php`):
- Site name validation (required, string, max:255)
- Site slug validation (required, unique, regex `/^[a-z0-9-]+$/`, max:255)
- Site description validation (optional, string, max:2000)
- Auto-generation method for slug from site name
- Uniqueness checker with counter fallback

**3. Update ProfileValidationRules** (`app/Concerns/ProfileValidationRules.php`):
- Import new SiteValidationRules trait
- Add siteRules() method call to validation

#### Frontend Changes

**1. Extend Register.vue Form**:
- Add phone_number field immediately (Phase 1)
- Add site creation section with visual grouping
- Add site_name, site_slug, site_description fields
- Implement client-side slug auto-generation
- Add proper validation error handling for all new fields
- Use Vietnamese labels and placeholders

**2. Form Structure**:
```vue
<!-- Personal Information Section -->
<div class="space-y-4">
    <h2 class="text-lg font-semibold text-gray-900">Thông tin cá nhân</h2>
    <!-- name, email, phone_number fields -->
</div>

<!-- Site Information Section -->  
<div class="space-y-4 border-t border-gray-200 pt-6 mt-6">
    <h2 class="text-lg font-semibold text-gray-900">Thông tin cửa hàng</h2>
    <!-- site_name, site_slug, site_description fields -->
</div>
```

**3. Slug Generation Logic**:
- Auto-generate slug from site name on input
- Convert to lowercase and replace special characters with hyphens
- Real-time preview of generated slug
- Allow manual override
- Format: `/cua-hang/slug` display

**4. TypeScript Implementation**:
- Add form fields to useForm()
- Implement generateSlug() function
- Handle reactive slug updates
- Proper type definitions for new fields

### Phase 3: Testing and Validation

#### Backend Tests
**1. Extend UserRegistrationTest.php**:
- Add phone_number field to existing registration test
- Test unique phone number validation
- Test complete registration with site data
- Test default SiteAdmin role assignment after registration

**2. Create CombinedRegistrationTest.php**:
- Test user + site creation in transaction
- Test rollback on validation failures
- Test slug generation and uniqueness
- Test site-user relationship creation
- Test user role assignment within transaction
- Test user can access site dashboard after registration

**3. Update SiteValidationTest.php**:
- Test site validation rules work correctly
- Test slug format validation
- Test database constraints

#### Frontend Tests  
**1. FormValidationTest.vue**:
- Test all field validations display correctly
- Test slug auto-generation on input
- Test manual slug override
- Test error handling and display

**2. EndToEndRegistrationTest.vue**:
- Test complete registration flow
- Test success redirect and authentication
- Test site creation verification

## 🔧 Technical Implementation Details

### Database Transaction Safety
```php
DB::transaction(function () use ($input) {
    $site = Site::create([
        'name' => $input['site_name'],
        'slug' => $input['site_slug'] ?? $this->generateSlug($input['site_name']),
        'description' => $input['site_description'] ?? null,
    ]);
    
    $user = User::create([
        'name' => $input['name'],
        'email' => $input['email'], 
        'phone_number' => $input['phone_number'],
        'site_id' => $site->id,
        'password' => $input['password'],
    ]);
    
    // Assign default SiteAdmin role to newly registered user
    $user->assignRole('SiteAdmin');
    
    $site->update(['user_id' => $user->id]);
    return $user;
});
```

### Slug Generation Algorithm
```php
protected function generateSlug(string $name): string
{
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    
    // Ensure uniqueness with counter
    $originalSlug = $slug;
    $counter = 1;
    
    while (Site::where('slug', $slug)->exists()) {
        $slug = $originalSlug . '-' . $counter++;
    }
    
    return $slug;
}
```

### Frontend Slug Generation
```typescript
const generateSlug = (): void => {
    if (!form.site_name) {
        form.site_slug = '';
        return;
    }
    
    const slug = form.site_name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
    
    form.site_slug = slug;
};
```

## 📱 User Experience Features

### Auto-Generated Slug
- Real-time generation from site name
- Visual preview with `/cua-hang/` prefix
- Manual override capability
- Format validation feedback
- Uniqueness checking on blur

### Form Organization  
- Visual separation between personal and site information
- Progressive disclosure (user info first, then site info)
- Clear Vietnamese labels and placeholders
- Inline validation feedback
- Accessible form structure

### Error Handling
- Clear Vietnamese error messages
- Field-specific validation feedback
- Transaction rollback protection
- Graceful failure recovery

## ⏱️ Implementation Timeline

### Phase 1: Critical Fix (30 minutes)
- Add phone_number field to Register.vue
- Test registration works
- Fix immediate validation issue

### Phase 2: Full Integration (2 hours)
- Backend: Extend CreateNewUser with site creation
- Backend: Create SiteValidationRules trait
- Frontend: Add site creation section
- Frontend: Implement slug generation
- Update tests

### Phase 3: Quality Assurance (1 hour)
- Run comprehensive tests
- Fix any validation issues
- Polish user experience
- Documentation updates

## 🎯 Success Criteria

### Functional Requirements
✅ User registration with unique email and phone number
✅ Site creation with unique slug during registration
✅ Auto-generated slug from site name with manual override
✅ Default SiteAdmin role assignment for new users
✅ Database transaction safety
✅ Comprehensive validation and error handling

### Technical Requirements  
✅ All existing tests pass
✅ New tests for combined registration flow
✅ Tests verify default SiteAdmin role assignment
✅ Code follows project conventions (Pest, Pint, TypeScript)
✅ No database constraint violations
✅ Frontend form validates correctly

### User Experience Requirements
✅ Intuitive two-section form layout
✅ Real-time slug generation
✅ Clear Vietnamese interface
✅ Proper error feedback
✅ Mobile-responsive design

## 📁 Files to Modify/Create

### Backend
- `app/Actions/Fortify/CreateNewUser.php` (extend with site creation)
- `app/Concerns/SiteValidationRules.php` (new trait)
- `app/Concerns/ProfileValidationRules.php` (add site rules)

### Frontend
- `resources/js/pages/auth/Register.vue` (add phone + site fields)

### Tests
- `tests/Feature/UserRegistrationTest.php` (add phone_number)
- `tests/Feature/CombinedRegistrationTest.php` (new)
- `tests/Feature/SiteValidationTest.php` (if needed)

## 🔍 Dependencies and Risks

### Dependencies
- Database migrations already exist and run
- User and Site models are properly configured
- Validation infrastructure is in place
- Frontend components (Input, Label, InputError) exist

### Risks and Mitigations
- **Registration form currently broken** → Fix immediately in Phase 1
- **Transaction complexity** → Keep simple and well-tested
- **Slug uniqueness race condition** → Use database unique constraint as fallback
- **Form validation complexity** → Follow existing patterns and test thoroughly

This comprehensive plan ensures UC001 is implemented with high quality, following existing patterns while adding robust site creation functionality to registration flow.
