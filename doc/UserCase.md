# Usercase

## Phase 1: Authentication & Site Foundation (CRITICAL - Tuần 1-2)
### [UC001-REG: User Registration](UserCase/UC-001-REG.md)
- **Priority**: CRITICAL FIRST - Foundation cho toàn bộ hệ thống
- **Dependencies**: None
- **Output**: Users, Sites, Role assignments

### [UC002-LOG: Login](UserCase/UC-002-LOG.md) 
- **Priority**: CRITICAL - Authentication system
- **Dependencies**: UC001-REG (cần Users và Sites)
- **Output**: Session management, role-based routing

### [UC003-MOS: Manage Own Site](UserCase/UC-003-MOS.md)
- **Priority**: CRITICAL - Site configuration foundation
- **Dependencies**: UC001-REG, UC002-LOG (cần authentication và site access)
- **Output**: Site configuration, product prefix settings, delivery rules, business rules
- **⚠️ CRITICAL**: Product code prefixes needed for UC006-MP, delivery info needed for warehouse operations

## Phase 2: Infrastructure Setup (HIGH - Tuần 3-4)
### [UC004-MSW: Manage Site Warehouse](UserCase/UC-004-MSW.md)
- **Priority**: HIGH - Infrastructure cho inventory
- **Dependencies**: UC001, UC002 (cần authentication)
- **Output**: Warehouses với site isolation

### [UC005-MWL: Manage Warehouse Location](UserCase/UC-005-MWL.md)
- **Priority**: HIGH - Vị trí lưu trữ
- **Dependencies**: UC004-MSW (cần Warehouses)
- **Output**: Locations trong warehouses

## Phase 3: Product Prerequisites (HIGH - Tuần 5)
### [UC011-MCT: Manage Categories and Tags](UserCase/UC-011-MCT.md)
- **Priority**: HIGH - **PREREQUISITE** cho Products
- **Dependencies**: UC001, UC002 (cần authentication)
- **Output**: Categories hierarchy, Tags với site isolation
- **⚠️ CRITICAL**: Phải hoàn thành trước UC006

### [UC012-MA: Manage Attributes](UserCase/UC-012-MA.md)
- **Priority**: HIGH - **PREREQUISITE** cho Product Variants
- **Dependencies**: UC001, UC002 (cần authentication)
- **Output**: Product attributes với site isolation
- **⚠️ CRITICAL**: Phải hoàn thành trước UC006

### [UC013-MPT: Manage Product Types](UserCase/UC-013-MPT.md)
- **Priority**: HIGH - **PREREQUISITE** cho Products
- **Dependencies**: UC001, UC002 (cần authentication)
- **Output**: Product types với site isolation, color coding
- **⚠️ CRITICAL**: Phải hoàn thành trước UC006

### [UC009-MS: Manage Suppliers](UserCase/UC-009-MS.md)
- **Priority**: HIGH - **PREREQUISITE** cho Products  
- **Dependencies**: UC001, UC002 (cần authentication)
- **Output**: Suppliers với site isolation
- **⚠️ CRITICAL**: Phải hoàn thành trước UC006

## Phase 4: Product Management (HIGH - Tuần 6)
### [UC006-MP: Manage Products](UserCase/UC-006-MP.md)
- **Priority**: HIGH - Core business functionality
- **Dependencies**: 
  - **⚠️ REQUIRED**: UC011-MCT (Categories & Tags)
  - **⚠️ REQUIRED**: UC012-MA (Attributes)
  - **⚠️ REQUIRED**: UC013-MPT (Product Types)
  - **⚠️ REQUIRED**: UC009-MS (Suppliers)
  - **⚠️ REQUIRED**: UC004-MSW, UC005-MWL (Warehouses & Locations)
- **Output**: Products với variants, SKUs, media integration

## Phase 5: Customer & Sales Operations (HIGH - Tuần 7-8)
### [UC007-MC: Manage Customers](UserCase/UC-007-MC.md)
- **Priority**: HIGH - Customer relationship management
- **Dependencies**: UC001, UC002 (cần authentication)
- **Output**: Customer database với site isolation

### [UC008-MO: Manage Orders](UserCase/UC-008-MO.md)
- **Priority**: HIGH - Revenue generating functionality
- **Dependencies**: 
  - **REQUIRED**: UC006-MP (cần Products)
  - **REQUIRED**: UC007-MC (cần Customers)
- **Output**: Order processing, inventory allocation

### [UC014-MOD: Manage Order Details](UserCase/UC-014-MOD.md)
- **Priority**: HIGH - Detailed order item management
- **Dependencies**: 
  - **REQUIRED**: UC008-MO (cần Orders và OrderDetails)
  - **REQUIRED**: UC006-MP (cần Products, ProductItems, ProductTypes)
  - **REQUIRED**: UC007-MC (cần Customers)
- **Output**: OrderDetails management, status tracking, advanced filtering

## Phase 6: Advanced Features (MEDIUM - Tuần 9-10)
### [UC010-MI: Manage Inventory](UserCase/UC-010-MI.md)
- **Priority**: MEDIUM - Advanced inventory management
- **Dependencies**:
  - **REQUIRED**: UC006-MP (cần Products)
  - **REQUIRED**: UC004-MSW, UC005-MWL (cần Warehouses & Locations)
- **Output**: Stock management, warehouse transactions

### [UC015-PO: Manage Purchase Order](UserCase/UC-015-PO.md)
- **Priority**: HIGH - Purchase order management and supplier coordination
- **Dependencies**: 
  - **REQUIRED**: UC009-MS (cần Suppliers)
  - **REQUIRED**: UC006-MP (cần Products và ProductItems)
  - **REQUIRED**: UC008-MO, UC014-MOD (cần pre-orders từ OrderDetails)
  - **REQUIRED**: UC010-MI (cần WarehouseInventory integration)
- **Output**: Purchase Request management, supplier coordination, pre-order fulfillment

### [UC016-MWI: Manage Warehouse In](UserCase/UC-016-MWI.md)
- **Priority**: HIGH - Warehouse receipt management and inventory updates
- **Dependencies**: 
  - **REQUIRED**: UC004-MSW, UC005-MWL (cần Warehouses & Locations)
  - **REQUIRED**: UC006-MP (cần Products và ProductItems)
  - **REQUIRED**: UC015-PO (cần Purchase Requests integration)
  - **REQUIRED**: UC010-MI (cần WarehouseInventory)
- **Output**: Warehouse receipt processing, inventory updates, purchase request fulfillment

### [UC017-MWO: Manage Warehouse Out](UserCase/UC-017-MWO.md)
- **Priority**: HIGH - Warehouse out management and order fulfillment
- **Dependencies**: 
  - **REQUIRED**: UC004-MSW, UC005-MWL (cần Warehouses & Locations)
  - **REQUIRED**: UC006-MP (cần Products và ProductItems)
  - **REQUIRED**: UC008-MO, UC014-MOD (cần Orders với payment_status integration)
  - **REQUIRED**: UC010-MI (cần WarehouseInventory)
  - **REQUIRED**: UC007-MC (cần Customers cho shipping info)
- **Output**: Warehouse out processing, order fulfillment, delivery management

### [UC018-MPR: Manage Payment Request](UserCase/UC-018-MPR.md)
- **Priority**: HIGH - Payment request management and customer billing
- **Dependencies**: 
  - **REQUIRED**: UC014-MOD (cần OrderDetails với status = Arrived)
  - **REQUIRED**: UC008-MO (cần Orders và Order information)
  - **REQUIRED**: UC007-MC (cần Customers cho payment info)
  - **REQUIRED**: UC017-MWO (payment status validation for warehouse eligibility)
- **Output**: Payment request processing, payment status tracking, customer billing management


---

## Dependencies Flow Chart

```
UC001-REG (Users & Sites)
    ↓
UC002-LOG (Authentication)
    ↓
UC003-MOS (Site Config)
    ↓
┌─────────────────┬─────────────────┬─────────────────┬─────────────────┐
│   UC004-MSW     │   UC011-MCT     │   UC012-MA      │   UC013-MPT     │   UC009-MS
│ (Warehouses)    │ (Categories)    │ (Attributes)    │ (ProductTypes)  │ (Suppliers)
│       ↓         │       ↓         │       ↓         │       ↓         │       ↓
│   UC005-MWL     │       └─────────┼───────┼─────────┼───────┴─────────┘
│ (Locations)     │                 │       │         │
│       ↓         │                 │       │         │
└─────────────────┴─────────────────┴───────┴─────────┘
                  ↓
              UC006-MP (Products)
                  ↓
        ┌─────────┴─────────┐
        │                   │
    UC007-MC            UC010-MI
  (Customers)         (Inventory)
        │                   │
        └─────────┬─────────┘
                  ↓
             UC008-MO (Orders)
                  ↓
           UC014-MOD (OrderDetails)
                  ↓
           UC015-PO (Purchase Orders)
                  ↓
           UC016-MWI (WarehouseIn)
                  ↓
         ┌─────────┴─────────┐
         │                   │
    UC018-MPR              UC017-MWO
   (Payment)            (WarehouseOut)
```

## Critical Implementation Rules

### ⚠️ BLOCKING DEPENDENCIES
- **UC003 CANNOT START** until UC001, UC002 are complete (needs authentication and site access)
- **UC006 CANNOT START** until UC011, UC012, UC013, UC009 & UC003 are 100% complete (needs product code prefixes from site config)
- **UC008 CANNOT START** until UC006 & UC007 are complete
- **UC014 CANNOT START** until UC008 is complete (requires OrderDetails data)
- **UC015 CANNOT START** until UC009, UC006, UC014 & UC010 are complete (requires Suppliers, Products, OrderDetails, Inventory)
- **UC016 CANNOT START** until UC004, UC005, UC006, UC015 & UC010 are complete (requires Warehouses, Locations, Products, Purchase Requests, Inventory)
- **UC018 CANNOT START** until UC016 is complete (requires OrderDetails with status=Arrived from warehouse receipts)
- **UC017 CANNOT START** until UC004, UC005, UC006, UC008, UC014, UC018, UC010 & UC007 are complete (requires Warehouses, Locations, Products, Orders with payment_status=Paid, OrderDetails, Payment validation, Inventory, Customers)
- **UC010 CANNOT START** until UC006 is complete
- **UC004, UC005 MAY NEED** UC003 for delivery/warehouse configuration settings

### ✅ PARALLEL IMPLEMENTATION ALLOWED
- UC003 should complete early as it provides foundational site configuration
- UC004 & UC011 & UC012 & UC013 & UC009 can be developed in parallel after UC003 (Week 3-5)  
- UC005 can start after UC004 is complete
- UC007 can be developed in parallel with UC006 completion
- UC015 can start after UC014 is complete
- UC018 & UC017 can be developed in parallel after UC016 is complete

### 🎯 SPRINT PLANNING RECOMMENDATION
- **Sprint 1**: UC001, UC002, UC003 (Authentication & Site Configuration Foundation)
- **Sprint 2**: UC004, UC011, UC012, UC013, UC009 (Infrastructure & Prerequisites)
- **Sprint 3**: UC005, UC006 (Locations & Products)
- **Sprint 4**: UC007, UC008 (Customers & Orders)
- **Sprint 5**: UC014, UC010 (Order Details & Inventory)
- **Sprint 6**: UC015 (Purchase Order Management)
- **Sprint 7**: UC016, UC017, UC018 (Warehouse & Payment Management)

---

## Business Value Priority

### Revenue Impact (HIGH)
1. UC008-MO (Orders) - Direct revenue
2. UC014-MOD (Order Details) - Order fulfillment tracking
3. UC018-MPR (Payment Requests) - Customer billing & payment tracking
4. UC006-MP (Products) - Product catalog
5. UC007-MC (Customers) - Customer base

### Operational Impact (HIGH)  
1. UC001-REG, UC002-LOG (Authentication)
2. UC004-MSW, UC005-MWL (Warehouse operations)
3. UC015-PO (Purchase order management & supplier coordination)
4. UC016-MWI (Warehouse receipt processing & inventory updates)
5. UC017-MWO (Warehouse out processing & order fulfillment)
6. UC010-MI (Inventory management)

### Setup & Configuration (MEDIUM)
1. UC011-MCT (Categories & Tags)
2. UC012-MA (Attributes)
3. UC013-MPT (Product Types)
4. UC009-MS (Suppliers) 
5. UC003-MOS (Site customization)

