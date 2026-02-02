# UC002 Implementation Plan - User Login with Role-Based Redirection

## 🎯 Objective
Implement user login flow with role-based authentication and automatic redirection. After successful login, users are redirected based on their role: Admin users go to `/dashboard`, SiteAdmin users go to `/site-slug/dashboard`.

## 📋 Current State Analysis

### ✅ Already Implemented:
- Laravel Fortify authentication system configured
- User model with authentication capabilities
- Two-factor authentication support
- Database schema with users table
- Spatie Laravel Permission package installed with migrations
- Permission configuration files in place

### ❌ Missing Components:
- HasRoles trait not added to User model
- Role and permission seeders not created
- Role-based redirection logic not implemented
- Login success redirection customization
- Dashboard controllers and routes not created
- Role-based middleware not configured

## 🚀 Implementation Plan

### Phase 1: Setup Roles and Permissions System

#### Backend Changes

**1. Update User Model** (`app/Models/User.php`):
- Add HasRoles trait from Spatie Permission package
- Import necessary classes
- Ensure proper trait usage

**2. Create Roles and Permissions Seeders** (`database/seeders/RoleSeeder.php` and `database/seeders/UserSeeder.php`):
- Create `admin` and `SiteAdmin` roles
- Define permissions for each role
- Separate seeder for creating test users with roles
- Flexible seeding for production environments

**3. Update DatabaseSeeder** (`database/seeders/DatabaseSeeder.php`):
- Call RoleSeeder (always runs)
- Call UserSeeder conditionally (development/testing only)
- Ensure proper seeding order

### Phase 2: Implement Role-Based Redirection

#### Backend Changes

**1. Create Custom Fortify Response** (`app/Providers/FortifyServiceProvider.php`):
- Override login response in boot() method
- Implement role-checking logic
- Add redirection based on user role
- Handle site slug resolution for SiteAdmin

**2. Create Dashboard Controllers**:
- `app/Http/Controllers/AdminDashboardController.php` (admin dashboard)
- `app/Http/Controllers/SiteDashboardController.php` (site-specific dashboard)
- Implement proper authorization and role checking

**3. Create Role-Based Middleware** (`app/Http/Middleware/CheckRole.php`):
- Middleware to verify user roles
- Handle unauthorized access
- Support multiple role checking

**4. Update Routes** (`routes/web.php`):
- Add dashboard routes with proper middleware
- Implement site-slug based routing
- Add role-based route protection

### Phase 3: Frontend Implementation

#### Frontend Changes

**1. Create Dashboard Pages**:
- `resources/js/Pages/Admin/Dashboard.vue` (admin dashboard)
- `resources/js/Pages/Site/Dashboard.vue` (site dashboard)
- Implement role-specific content and navigation

**2. Update Login Flow**:
- Ensure login form works with role redirection
- Handle loading states during authentication
- Add proper error handling

### Phase 4: Testing and Validation

#### Backend Tests

**1. Create RolePermissionTest.php**:
- Test role creation and assignment
- Test permission assignment
- Test user role checking methods

**2. Create LoginRedirectionTest.php**:
- Test admin login redirects to `/dashboard`
- Test SiteAdmin login redirects to `/site-slug/dashboard`
- Test unauthorized access handling
- Test role-based middleware

**3. Create DashboardAccessTest.php**:
- Test dashboard access with proper roles
- Test unauthorized dashboard access
- Test site-specific dashboard access

#### Frontend Tests

**1. LoginFlowTest.vue**:
- Test successful login with different roles
- Test proper redirection after login
- Test dashboard page loading

## 🔧 Technical Implementation Details

### User Model with Roles
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;
    
    // ...existing code...
}
```

### Role Seeder (Production-Safe)
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles (using firstOrCreate to prevent duplicates)
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $siteAdminRole = Role::firstOrCreate(['name' => 'SiteAdmin']);
        
        // Create permissions (using firstOrCreate to prevent duplicates)
        $permissions = [
            'manage-users',
            'manage-sites',
            'view-admin-dashboard',
            'manage-own-site',
            'view-site-dashboard',
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        
        // Assign permissions to roles
        $adminRole->givePermissionTo([
            'manage-users',
            'manage-sites', 
            'view-admin-dashboard'
        ]);
        
        $siteAdminRole->givePermissionTo([
            'manage-own-site',
            'view-site-dashboard'
        ]);
    }
}
```

### User Seeder (Development/Testing Only)
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Site;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $adminUser = User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@system.com',
            'phone_number' => '0123456789',
            'password' => 'password123'
        ]);
        $adminUser->assignRole('admin');
        
        // Create site and site admin user
        $site = Site::factory()->create([
            'name' => 'Demo Store',
            'slug' => 'demo-store',
            'description' => 'Demo store for testing'
        ]);
        
        $siteAdmin = User::factory()->create([
            'name' => 'Site Admin',
            'email' => 'siteadmin@demo-store.com',
            'phone_number' => '0987654321',
            'password' => 'password123',
            'site_id' => $site->id
        ]);
        $siteAdmin->assignRole('SiteAdmin');
        
        $site->update(['user_id' => $siteAdmin->id]);
    }
}
```

### Database Seeder (Environment-Aware)
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Always seed roles and permissions
        $this->call([
            RoleSeeder::class,
        ]);
        
        // Seed users only in development/testing
        if (app()->environment('local', 'testing')) {
            $this->call([
                UserSeeder::class,
            ]);
        }
    }
}
```

### Custom Fortify Login Response
```php
// In FortifyServiceProvider boot() method
use Laravel\Fortify\Contracts\LoginResponse;

Fortify::loginView(function () {
    return Inertia::render('Auth/Login');
});

$this->app->instance(LoginResponse::class, new class implements LoginResponse {
    public function toResponse($request)
    {
        $user = auth()->user();
        
        if ($user->hasRole('admin')) {
            return redirect()->intended('/dashboard');
        }
        
        if ($user->hasRole('SiteAdmin')) {
            $site = $user->site;
            return redirect()->intended("/{$site->slug}/dashboard");
        }
        
        // Fallback for other roles
        return redirect()->intended('/');
    }
});
```

### Dashboard Controllers
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }
    
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users' => \App\Models\User::count(),
                'total_sites' => \App\Models\Site::count(),
            ]
        ]);
    }
}

class SiteDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:SiteAdmin']);
    }
    
    public function index(Request $request, string $siteSlug): Response
    {
        $site = \App\Models\Site::where('slug', $siteSlug)->firstOrFail();
        
        // Ensure user can only access their own site dashboard
        if (auth()->user()->site_id !== $site->id) {
            abort(403, 'Unauthorized access to site dashboard');
        }
        
        return Inertia::render('Site/Dashboard', [
            'site' => $site,
            'stats' => [
                // Site-specific statistics
            ]
        ]);
    }
}
```

### Role Middleware
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        foreach ($roles as $role) {
            if (auth()->user()->hasRole($role)) {
                return $next($request);
            }
        }
        
        abort(403, 'Insufficient permissions');
    }
}
```

### Routes Configuration
```php
// In routes/web.php
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\SiteDashboardController;

// Admin routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');
});

// Site admin routes
Route::middleware(['auth', 'role:SiteAdmin'])->group(function () {
    Route::get('/{site:slug}/dashboard', [SiteDashboardController::class, 'index'])
        ->name('site.dashboard');
});
```

## 📱 User Experience Features

### Role-Based Redirection
- Automatic redirection after login based on user role
- Admin users: immediate access to system dashboard
- Site admins: direct access to their site's management dashboard
- Proper error handling for unauthorized access

### Dashboard Features
- Admin Dashboard: System-wide statistics and management
- Site Dashboard: Site-specific management and analytics
- Role-appropriate navigation and features
- Responsive design for all devices

### Security Features
- Role-based access control using Spatie Permission
- Middleware protection for sensitive routes
- Site ownership verification for site admins
- Proper authentication checks

## ⏱️ Implementation Timeline

### Phase 1: Roles and Permissions Setup (45 minutes)
- Add HasRoles trait to User model
- Create RoleSeeder and UserSeeder
- Update DatabaseSeeder
- Run seeders to create roles and test users

### Phase 2: Authentication and Redirection (1.5 hours)
- Implement custom Fortify login response
- Create dashboard controllers with proper authorization
- Create role middleware
- Setup protected routes

### Phase 3: Frontend Dashboard Pages (1 hour)
- Create Admin/Dashboard.vue component
- Create Site/Dashboard.vue component
- Implement role-specific content and navigation

### Phase 4: Testing and Quality Assurance (1 hour)
- Create comprehensive test suite
- Test role assignment and permissions
- Test login redirection logic
- Test dashboard access controls
- Run all tests and fix issues

## 🎯 Success Criteria

### Functional Requirements
✅ User login with email/password authentication
✅ Role-based redirection after successful login
✅ Admin users redirect to `/dashboard`
✅ SiteAdmin users redirect to `/site-slug/dashboard`
✅ Proper access control for dashboard routes
✅ Role and permission management system

### Technical Requirements
✅ Spatie Laravel Permission package properly configured
✅ HasRoles trait added to User model
✅ Comprehensive role and permission seeder
✅ Custom Fortify login response implementation
✅ Role-based middleware protection
✅ All existing tests pass
✅ New tests for authentication and authorization

### Security Requirements
✅ Proper role-based access control
✅ Site ownership verification for SiteAdmin
✅ Unauthorized access prevention
✅ Secure password handling
✅ Protected dashboard routes

### User Experience Requirements
✅ Seamless login flow with automatic redirection
✅ Role-appropriate dashboard content
✅ Clear navigation and interface
✅ Proper error handling and feedback
✅ Mobile-responsive dashboard design

## 📁 Files to Create/Modify

### Backend
- `app/Models/User.php` (add HasRoles trait)
- `database/seeders/RoleSeeder.php` (new)
- `database/seeders/UserSeeder.php` (new)
- `database/seeders/DatabaseSeeder.php` (update)
- `app/Providers/FortifyServiceProvider.php` (add custom login response)
- `app/Http/Controllers/AdminDashboardController.php` (new)
- `app/Http/Controllers/SiteDashboardController.php` (new)
- `app/Http/Middleware/CheckRole.php` (new)
- `routes/web.php` (add dashboard routes)
- `bootstrap/app.php` (register middleware)

### Frontend
- `resources/js/Pages/Admin/Dashboard.vue` (new)
- `resources/js/Pages/Site/Dashboard.vue` (new)

### Tests
- `tests/Feature/RolePermissionTest.php` (new)
- `tests/Feature/LoginRedirectionTest.php` (new)
- `tests/Feature/DashboardAccessTest.php` (new)
- `tests/Feature/AuthenticationTest.php` (extend existing)

## 🔍 Dependencies and Risks

### Dependencies
- Spatie Laravel Permission package (already installed)
- Laravel Fortify (already configured)
- Inertia.js for frontend rendering
- User and Site models properly configured
- Database migrations for permissions already run

### Risks and Mitigations
- **Role seeding conflicts** → Check existing data before seeding
- **Permission middleware conflicts** → Use descriptive middleware names
- **Site slug resolution issues** → Add proper validation and error handling
- **Frontend route conflicts** → Use namespaced routes for dashboards
- **Authorization bypass** → Comprehensive testing of all access paths

### Migration Strategy
- Create seeder that can run multiple times safely
- Use `firstOrCreate()` for roles and permissions
- Provide rollback capabilities
- Test with existing data

## 🚀 Production Usage

### Running Seeders in Production

**To seed only roles and permissions in production:**
```bash
php artisan db:seed --class=RoleSeeder
```

**To seed all (roles + users) in development:**
```bash
php artisan db:seed
```

**To seed only users (after roles exist):**
```bash
php artisan db:seed --class=UserSeeder
```

### Environment-Specific Behavior
- **Production**: Only roles and permissions are seeded by default
- **Local/Testing**: Both roles and test users are seeded automatically
- **Manual Control**: You can run specific seeders as needed

### Flexible Seeding Commands
```bash
# Production setup - roles only
php artisan db:seed --class=RoleSeeder

# Add test users if needed
php artisan db:seed --class=UserSeeder

# Fresh database with all data
php artisan migrate:fresh --seed
```

This comprehensive plan ensures UC002 is implemented with robust authentication, proper role-based access control, and secure dashboard functionality following Laravel best practices and the existing application architecture.
