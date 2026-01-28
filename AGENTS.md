# AGENTS.md

This file contains guidelines and commands for agentic coding agents working in this Laravel + Vue.js application.

## Development Commands

### Environment

- **Start services**: `vendor/bin/sail up -d`
- **Stop services**: `vendor/bin/sail stop`
- **Open in browser**: `vendor/bin/sail open`

### PHP/Composer Commands (via Sail)

- **Install dependencies**: `vendor/bin/sail composer install`
- **Run migrations**: `vendor/bin/sail artisan migrate`
- **Create new files**: `vendor/bin/sail artisan make:model`, `vendor/bin/sail artisan make:controller`, etc.
- **Tinker (debug)**: `vendor/bin/sail artisan tinker`

### Frontend Commands (via Sail)

- **Install dependencies**: `vendor/bin/sail npm install`
- **Development server**: `vendor/bin/sail npm run dev`
- **Build for production**: `vendor/bin/sail npm run build`
- **Format code**: `vendor/bin/sail npm run format`
- **Lint code**: `vendor/bin/sail npm run lint`

### Testing Commands

- **Run all tests**: `vendor/bin/sail artisan test --compact`
- **Run single test file**: `vendor/bin/sail artisan test --compact tests/Feature/ExampleTest.php`
- **Run specific test**: `vendor/bin/sail artisan test --compact --filter=testName`
- **Browser tests**: `vendor/bin/sail artisan test --compact tests/Browser/`

### Code Quality

- **PHP formatting**: `vendor/bin/sail pint --dirty`
- **PHP linting**: `vendor/bin/sail pint --parallel`
- **Full test suite**: `composer run test` (includes linting)

## Code Style Guidelines

### PHP

- **Framework**: Laravel 12 with PHP 8.5.2
- **Formatter**: Laravel Pint (preset: laravel)
- **Type declarations**: Always use explicit return types and parameter hints
- **Constructors**: Use PHP 8 constructor property promotion
- **Control structures**: Always use curly braces, even for single lines
- **Comments**: Prefer PHPDoc blocks over inline comments
- **Naming**: Use descriptive names (e.g., `isRegisteredForDiscounts`, not `discount()`)

### Vue.js/TypeScript

- **Framework**: Vue 3 with TypeScript, Inertia.js v2
- **Formatter**: Prettier with organize-imports and tailwindcss plugins
- **Imports**: Use named imports for tree-shaking (`import { show } from '@/actions/...'`)
- **Components**: Single root element required
- **Props**: Use TypeScript interfaces with default values via `withDefaults()`
- **Navigation**: Use `router.visit()` or `<Link>` component

### Testing

- **Framework**: Pest 4 (all tests must use Pest)
- **Test types**: Feature tests (default), Unit tests (`--unit`), Browser tests
- **Assertions**: Use specific assertion methods (`assertForbidden`, not `assertStatus(403)`)
- **Factories**: Use model factories for test data
- **Mocking**: Import `use function Pest\Laravel\mock;` for mocking

### Styling

- **CSS Framework**: Tailwind CSS v4
- **Configuration**: CSS-first with `@theme` directive
- **Dark mode**: Support with `dark:` prefixes where appropriate
- **Spacing**: Use gap utilities for lists, not margins
- **Imports**: Use `@import "tailwindcss";` not `@tailwind` directives

## Architecture Patterns

### Laravel

- **Models**: Use Eloquent relationships with return type hints
- **Controllers**: Create Form Request classes for validation
- **Queries**: Prevent N+1 problems with eager loading
- **Configuration**: Use `config()` not `env()` outside config files
- **Middleware**: Registered in `bootstrap/app.php` (Laravel 12)

### Inertia.js

- **Pages**: Located in `resources/js/Pages`
- **Routing**: Use `Inertia::render()` in controllers
- **Forms**: Use `<Form>` component or `useForm` helper
- **Features**: Deferred props, infinite scrolling, polling available

### Wayfinder

- **Type safety**: Generates TypeScript functions for Laravel routes
- **Usage**: Import from `@/actions/` or `@/routes/`
- **Forms**: Use `.form()` method with Inertia `<Form>` component
- **Regeneration**: `vendor/bin/sail artisan wayfinder:generate` after route changes

## File Structure Conventions

### PHP

- **Models**: `app/Models/`
- **Controllers**: `app/Http/Controllers/`
- **Requests**: `app/Http/Requests/`
- **Actions**: `app/Actions/` (Action Design Pattern)
- **Factories**: `database/factories/`
- **Tests**: `tests/Feature/`, `tests/Unit/`, `tests/Browser/`

### Vue.js

- **Pages**: `resources/js/pages/`
- **Components**: `resources/js/components/`
- **Layouts**: `resources/js/layouts/`
- **UI Components**: `resources/js/components/ui/`

## Important Notes

- **All commands must be run via Sail**: Prefix with `vendor/bin/sail`
- **Test every change**: Write or update tests before finalizing
- **Check existing conventions**: Review sibling files for patterns
- **Use search-docs tool**: For Laravel ecosystem documentation
- **No new base folders**: Don't create top-level directories without approval
- **Frontend changes**: May require `npm run build` to see updates
