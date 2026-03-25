# UC007 Implementation Plan - Manage Customers (MC)

## 🎯 Objective
Implement customer management functionality for users with `manage_customers` permission to create, view, update, and delete/deactivate customers in their owned site scope. Ensure business rules BR-01..BR-07 are enforced consistently at UI, request validation, service/action, and database levels.

## 📋 Current State Analysis

### ✅ Already Implemented
- Customer CRUD flow (list/create/edit/delete) is available.
- Site scoping exists via `site_id` in queries and request authorization.
- Request validation for core fields is present (`name`, `phone`, `email`, `type`, `address`, `ward_id`).
- Customer address handling exists via `HasAddress` relation.
- Sidebar/menu has customer management entry with permission check.

### ⚠️ Needs Hardening / Standardization
- BR-01 needs DB-level support for unique email per site (not global unique email).
- BR-05 requires strict backend block when customer has linked orders.
- UI delete action should reflect BR-05 (disable/hide delete when not allowed).
- BR-07 audit logging should be explicit and testable for customer change events.
- Business constraints for address behavior by customer type should be clarified and enforced consistently.

## 🚀 Implementation Plan

### Phase 1: Data Integrity (Database + Validation)

#### Backend Changes

1. **Email uniqueness in site scope (BR-01)**
- Update customers unique index from global `email` to composite `(site_id, email)`.
- Keep request validation `Rule::unique('customers', 'email')->where(site_id)`.

2. **Phone format normalization (BR-02)**
- Keep regex `^(0\d{9,10})$` in Store/Update requests.
- Ensure frontend input restricts non-digit and max length 11.

3. **Address validation (BR-06)**
- Keep `ward_id` exists check (`exists:wards,id`).
- Ensure create/update always has required address fields.

**Files to modify**:
- `database/migrations/*` (new migration for email unique index)
- `app/Http/Requests/Customer/StoreCustomerRequest.php`
- `app/Http/Requests/Customer/UpdateCustomerRequest.php`

### Phase 2: Business Rules in Actions/Services

#### Backend Changes

1. **Store customer flow**
- Create customer in current `site_id`.
- Create default customer address.

2. **Update customer flow**
- Update core customer fields.
- Update default address safely (or create if absent).

3. **Delete/deactivate logic (BR-05)**
- Check whether customer has linked orders.
- If linked orders exist:
  - Block hard delete.
  - Return business error response (or deactivate flow if model supports status).
- If no linked orders:
  - Allow delete.

4. **Address constraints by type (BR-03/BR-04)**
- Keep one main/default address for individual customers.
- For business/corporate customers, allow extending to multiple addresses in future without breaking current flow.

**Files to modify**:
- `app/Actions/Customer/StoreCustomer.php`
- `app/Actions/Customer/UpdateCustomer.php`
- `app/Actions/Customer/DeleteCustomer.php`
- `app/Models/Customer.php`

### Phase 3: UI/UX and Access Feedback

#### Frontend Changes

1. **Customer form UX**
- Keep required field indicators and placeholder hints.
- Keep inline validation error rendering for each field.

2. **Delete action UX (BR-05)**
- Show delete button only when user has permission and record is deletable.
- Disable action or show tooltip message if customer has linked orders.

3. **List clarity**
- Keep address display from default address accessor.
- Keep customer type and contact fields visible for fast scanning.

**Files to modify**:
- `resources/js/pages/site/customers/Index.vue`
- `resources/js/pages/site/customers/Create.vue`
- `resources/js/pages/site/customers/Edit.vue`
- `resources/js/types/customer.d.ts`

### Phase 4: Audit Logging (BR-07)

#### Backend Changes

1. **Track customer changes**
- Log create/update/delete/deactivate events with actor, customer id, site id, and changed fields.
- Use centralized logging strategy (activity log package or action-level logging).

2. **Audit completeness**
- Ensure address updates are logged together with customer changes.

**Files to modify** (depending on current logging stack):
- `app/Actions/Customer/*.php`
- Possibly `app/Models/Customer.php` or dedicated observer/service.

### Phase 5: Testing & Verification

#### Test Coverage

1. **Feature tests**
- Create customer success/fail validation.
- Update customer success/fail validation.
- Delete customer without orders success.
- Delete customer with orders blocked (BR-05).

2. **Rule tests**
- Email unique in same site, allowed duplicate across different sites (BR-01).
- Phone regex enforcement (BR-02).
- Ward existence enforcement (BR-06).

3. **Authorization tests**
- User cannot access/modify customers outside current site.

4. **Audit tests**
- Verify logs are created for key operations (BR-07).

**Files to create/modify**:
- `tests/Feature/*Customer*Test.php`
- `tests/Unit/*Customer*Test.php` (if needed)

## 📊 Success Criteria (Definition of Done)

- Customer CRUD works reliably in site scope.
- BR-01 to BR-07 are enforced and verifiable by tests.
- Delete behavior complies with order-link constraint.
- UI provides clear feedback and prevents invalid operations.
- Audit logs exist for critical customer data changes.

## ⚠️ Important Notes

- Keep all queries site-scoped to avoid cross-site data leakage.
- Do not rely only on UI rules; enforce business rules in backend actions.
- Database constraints must align with request validation to prevent runtime inconsistency.
- If deactivation is used instead of hard delete, define status lifecycle clearly and apply consistently in list filters.
