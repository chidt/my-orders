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

## Phase 6: Advanced Features (MEDIUM - Tuần 9-10)
### [UC010-MI: Manage Inventory](UserCase/UC-010-MI.md)
- **Priority**: MEDIUM - Advanced inventory management
- **Dependencies**:
  - **REQUIRED**: UC006-MP (cần Products)
  - **REQUIRED**: UC004-MSW, UC005-MWL (cần Warehouses & Locations)
- **Output**: Stock management, warehouse transactions

### [UC003-MOS: Manage Own Site](UserCase/UC-003-MOS.md)
- **Priority**: MEDIUM - Site customization
- **Dependencies**: UC001, UC002 (cần authentication)
- **Output**: Site configuration, product prefix settings

---

## Dependencies Flow Chart

```
UC001-REG (Users & Sites)
    ↓
UC002-LOG (Authentication)
    ↓
┌─────────────────┬─────────────────┐
│   UC004-MSW     │   UC011-MCT     │   UC009-MS
│ (Warehouses)    │ (Categories)    │ (Suppliers)
│       ↓         │       ↓         │       ↓
│   UC005-MWL     │       └─────────┼───────┘
│ (Locations)     │                 │
│       ↓         │                 │
└─────────────────┴─────────────────┘
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
             UC003-MOS (Site Config)
```

## Critical Implementation Rules

### ⚠️ BLOCKING DEPENDENCIES
- **UC006 CANNOT START** until UC011, UC012 & UC009 are 100% complete
- **UC008 CANNOT START** until UC006 & UC007 are complete
- **UC010 CANNOT START** until UC006 is complete

### ✅ PARALLEL IMPLEMENTATION ALLOWED
- UC004 & UC011 & UC012 & UC009 can be developed in parallel (Week 3-5)
- UC005 can start after UC004 is complete
- UC007 can be developed in parallel with UC006 completion
- UC003 can be developed anytime after UC002

### 🎯 SPRINT PLANNING RECOMMENDATION
- **Sprint 1**: UC001, UC002 (Authentication Foundation)
- **Sprint 2**: UC004, UC011, UC012, UC009 (Infrastructure & Prerequisites)
- **Sprint 3**: UC005, UC006 (Locations & Products)
- **Sprint 4**: UC007, UC008 (Customers & Orders)
- **Sprint 5**: UC010, UC003 (Advanced Features)

---

## Business Value Priority

### Revenue Impact (HIGH)
1. UC008-MO (Orders) - Direct revenue
2. UC006-MP (Products) - Product catalog
3. UC007-MC (Customers) - Customer base

### Operational Impact (HIGH)  
1. UC001-REG, UC002-LOG (Authentication)
2. UC004-MSW, UC005-MWL (Warehouse operations)
3. UC010-MI (Inventory management)

### Setup & Configuration (MEDIUM)
1. UC011-MCT (Categories & Tags)
2. UC009-MS (Suppliers) 
3. UC003-MOS (Site customization)

