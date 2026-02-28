# My Orders - Project Specification

## Project Overview

**My Orders** is a comprehensive multi-tenant order management system built with Laravel 12 and Vue.js. The application enables site administrators to manage their own e-commerce sites with complete control over products, orders, warehouses, and inventory through a modern, responsive web interface.

### Key Features
- Multi-tenant architecture with site isolation
- Role-based access control with permissions
- Complete order lifecycle management
- Warehouse and inventory management
- Product catalog with variants and attributes
- Customer relationship management
- Real-time notifications and updates

## Technology Stack

### Backend Framework
- **PHP**: 8.5.2
- **Laravel Framework**: v12
- **Laravel Fortify**: v1 (Headless authentication)
- **Laravel Wayfinder**: v0 (Type-safe route generation)
- **Spatie Laravel Permission**: Permission and role management

### Frontend Framework
- **Vue.js**: v3 with Composition API
- **TypeScript**: Type-safe JavaScript
- **Inertia.js**: v2 (Modern monolith SPA)
- **Tailwind CSS**: v4 (Utility-first CSS)

### Development & Testing
- **Laravel Sail**: v1 (Docker development environment)
- **Pest**: v4 (PHP testing framework)
- **Laravel Pint**: v1 (Code formatting)
- **ESLint**: v9 & **Prettier**: v3 (Frontend code quality)

### Database & Storage
- **SQLite** (Development) / **MySQL/PostgreSQL** (Production)
- **Laravel Media Library** (File management)
- **Activity Log** (Audit trail)

## Architecture & Design Patterns

### Multi-Tenant Architecture
- **Site-based isolation**: Each user owns and manages their own site
- **Shared database**: Single database with `site_id` foreign keys
- **Dynamic routing**: Site-specific routes with slug prefixes
- **Permission inheritance**: Role-based permissions with site context

### Key Design Patterns
- **Repository Pattern**: Clean data access layer
- **Action Pattern**: Business logic encapsulation
- **Form Request Pattern**: Validation and authorization
- **Resource Pattern**: API response transformation
- **Observer Pattern**: Model events and logging

### Security Architecture
- **Laravel Fortify**: Headless authentication system
- **Spatie Permissions**: Role and permission management
- **CSRF Protection**: Cross-site request forgery prevention
- **Input Validation**: Comprehensive form request validation
- **Site Isolation**: Strict data access control by site ownership

## Database Design

### Core Entities

#### User Management
- **Users**: System users with authentication data
- **Roles**: Permission groups (Admin, SiteAdmin, etc.)
- **Permissions**: Granular access controls
- **Sites**: Multi-tenant site containers

#### Product Management
- **Products**: Base product information
- **ProductItems**: Product variants with SKUs
- **ProductTypes**: Product categorization
- **Categories**: Hierarchical product organization
- **Attributes**: Product variation attributes (color, size, etc.)
- **ProductAttributeValues**: Attribute values for products
- **Tags**: Product tagging system

#### Order Management
- **Orders**: Order headers with customer and shipping info
- **OrderDetails**: Individual line items with pricing
- **PaymentRequests**: Payment tracking and status
- **Customers**: Customer information and addresses

#### Inventory Management
- **Warehouses**: Storage facility information
- **Locations**: Specific positions within warehouses
- **WarehouseReceipts**: Inventory receiving transactions
- **WarehouseOuts**: Inventory shipping transactions
- **ShoppingCarts**: User shopping cart persistence

#### Geographic & Media
- **Provinces/Wards**: Geographic location hierarchy
- **Media**: File attachments using Spatie Media Library
- **ActivityLog**: System audit trail

### Key Relationships
- Users belong to Sites (multi-tenant isolation)
- Orders contain OrderDetails (order line items)
- Products have ProductItems (variants/SKUs)
- Warehouses contain Locations (inventory positions)
- All core entities linked to Sites for data isolation

## Use Case Implementation

### UC001: User Registration (High Priority)
**Objective**: Enable new users to create accounts and automatically set up their own site

**Features**:
- User account creation with validation
- Automatic SiteAdmin role assignment
- Site creation with custom slug
- Address management using HasAddress trait
- Email uniqueness and validation
- Password security requirements

**Business Rules**:
- Unique email and phone number
- Password minimum 8 characters
- Site slug uniqueness and format validation
- Auto-assign SiteAdmin role for new users

### UC002: User Login (High Priority)
**Objective**: Authenticate users and provide role-based dashboard access

**Features**:
- Laravel Fortify authentication
- Role-based dashboard routing
- Session management
- Secure password handling

**Dashboard Routing**:
- Admin users → `/admin/dashboard`
- SiteAdmin users → `/{site-slug}/dashboard`

### UC003: Manage Own Site (Medium Priority)
**Objective**: Allow SiteAdmins to update their site information and settings

**Features**:
- Site information management (name, slug, description)
- Product prefix configuration for auto-generated codes
- Site settings JSON storage
- Owner-only access control

**Permissions**: `manage-own-site` role required

### UC004: Manage Site Warehouse (High Priority)
**Objective**: Enable warehouse CRUD operations for site inventory management

**Features**:
- Create/Read/Update/Delete warehouses
- Site-specific warehouse isolation
- Warehouse code and address management
- Integration with location management

**Permissions**: `manage_warehouses` permission required

### UC005: Manage Warehouse Locations (High Priority)
**Objective**: Manage specific storage positions within warehouses

**Features**:
- Location CRUD within warehouses
- Default location designation
- Stock quantity tracking per location
- Warehouse ownership validation

**Permissions**: `manage-warehouse-locations` role required

## Technical Requirements

### Frontend Architecture
- **Single Page Application** using Inertia.js v2
- **Type-safe routing** with Wayfinder integration
- **Responsive design** with Tailwind CSS v4
- **Component reusability** with Vue 3 Composition API
- **Form handling** with Inertia Form helpers
- **Real-time updates** using polling and deferred props

### Backend Architecture
- **RESTful API design** with resource controllers
- **Form Request validation** for all inputs
- **Eloquent relationships** with proper type hints
- **Query optimization** with eager loading
- **Event-driven logging** with Activity Log
- **Job queues** for time-intensive operations

### Testing Strategy
- **Pest 4 framework** for all testing
- **Feature tests** for user workflows
- **Unit tests** for business logic
- **Browser tests** for critical user paths
- **Factory-based** test data generation
- **Minimum 80% code coverage** target

### Performance Requirements
- **Response time** < 200ms for standard requests
- **Database query optimization** with N+1 prevention
- **Efficient pagination** for large datasets
- **Image optimization** with media library
- **Caching strategy** for frequently accessed data

### Security Requirements
- **Input validation** on all user inputs
- **CSRF protection** on state-changing requests
- **SQL injection prevention** using Eloquent ORM
- **XSS protection** with proper output escaping
- **Access control** with permission-based authorization
- **Audit logging** for all significant actions

## Development Standards

### Code Quality Standards
- **Laravel Pint formatting** for PHP code
- **ESLint + Prettier** for TypeScript/Vue code
- **Type declarations** for all PHP methods
- **PHPDoc blocks** for complex functionality
- **Descriptive naming** for variables and methods

### File Organization
- **Laravel 12 structure** with streamlined directories
- **Feature-based grouping** for related functionality
- **Consistent naming** following Laravel conventions
- **Reusable components** in dedicated directories

### Git Workflow
- **Feature branches** for new development
- **Pull request reviews** before merging
- **Automated testing** in CI/CD pipeline
- **Conventional commits** for clear history

## Deployment & Infrastructure

### Environment Requirements
- **PHP 8.5.2+** with required extensions
- **Node.js 18+** for frontend build process
- **Database server** (MySQL 8.0+ or PostgreSQL 13+)
- **Web server** (Nginx recommended)
- **SSL certificate** for HTTPS

### Production Considerations
- **Environment configuration** via .env files
- **Database migrations** for schema management
- **Asset compilation** with Vite
- **Cache optimization** (Redis recommended)
- **Background job processing** with queue workers
- **Log rotation** and monitoring
- **Backup strategy** for database and uploads

## Success Criteria

### Functional Requirements Met
- ✅ Multi-tenant user registration and authentication
- ✅ Site-specific dashboard and management
- ✅ Warehouse and location management
- ✅ Role-based permission system
- ✅ Responsive user interface

### Quality Metrics
- **Test Coverage**: ≥80% code coverage
- **Performance**: <200ms average response time
- **Security**: Zero critical vulnerabilities
- **Code Quality**: PSR-12 compliance with Pint
- **User Experience**: Mobile-responsive design

### Business Value
- **Scalable multi-tenant architecture** for growth
- **Comprehensive order management** system
- **Modern technology stack** for maintainability
- **Role-based access control** for security
- **Audit trail** for compliance and debugging

---

*This specification serves as the foundation for development planning and implementation tracking. All features should be implemented following Laravel best practices and the established coding standards outlined in this document.*
