# ER Diagram

```mermaid
---
config:
  theme: mc
  layout: dagre
---
erDiagram
    direction TB
    Customers {
        bigint id PK ""
        string name  "required"
        string phone  "required"
        string email UK ""
        tinyInt type  "required"
        text description  ""
        bigint site_id FK ""
    }

    Addresses {
        bigint id PK ""
        string address  "required"
        bigint customer_id FK ""
        bigint ward_id FK ""
    }

    Wards {
        bigint id PK ""
        string name  "required"
        bigint province_id FK ""
        string gso_id  ""
    }

    Provinces {
        bigint id PK ""
        string name  "required"
        string gso_id  ""
    }

    OrderDetails {
        bigint id PK ""
        tinyInt payment_status  "required"
        bigint payment_request_detail_id FK ""
        %% UC-018-MPR: Links OrderDetails to PaymentRequestDetails for payment tracking
        %% Set when OrderDetail is included in a PaymentRequest
        %% Used to prevent OrderDetail from being included in multiple PaymentRequests
        tinyInt status  "required"
        tinyInt fulfillment_status  "default 0"
        int qty  "required"
        decimal price  "required"
        decimal discount  ""
        decimal addition_price  ""
        decimal total  "required"
        text note  ""
        bigint product_item_id FK "required"
        bigint order_id FK "required"
        bigint site_id FK "required"
        bigint purchase_request_detail_id FK ""
        %% UC-015-PO: Links OrderDetails to PurchaseRequestDetails for pre-order tracking
        %% Set when OrderDetail status = WaitingForStock and Purchase Request is created
        %% Reset to NULL when Purchase Request is deleted (hard delete only)
        datetime order_date  ""
        datetime expected_fulfillment_date  ""
        json extra_attributes  ""
    }

    ProductItems {
        bigint id PK "required"
        string name  "required"
        string sku UK "required"
        boolean is_parent_image  "required"
        boolean is_parent_slider_image  "required"
        int qty_in_stock  "required"
        decimal price  "required"
        decimal partner_price  ""
        decimal purchase_price  "required"
        bigint product_id FK "required"
        bigint site_id FK "required"
    }

    Attributes {
        bigint id PK ""
        string name  "required"
        string code  "required"
        text description  ""
        int order "default 0"
        bigint site_id FK "required"
    }

    ProductAttributeValues {
        bigint id PK ""
        string code  "required"
        string value  "required"
        int order  "required"
        decimal purchase_addition_value  ""
        decimal partner_addition_value  ""
        decimal addition_value  ""
        bigint product_id FK "required"
        bigint attribute_id FK "required"
    }

    ProductItemAttributeValues {
        bigint id PK "required"
        bigint product_item_id FK "required"
        bigint product_attribute_value_id FK "required"
    }

    Categories {
        bigint id PK ""
        string name  "required"
        string slug  "required"
        text description  ""
        int order  "required default 0"
        boolean is_active  "required default true"
        bigint parent_id FK ""
        bigint site_id FK "required"
    }

    Tags {
        bigint id PK "required"
        string name  "required"
        string slug  "required"
        bigint site_id FK "required"
    }

    ProductTags {
        bigint id PK "required"
        bigint product_id FK "required"
        bigint tag_id FK "required"
    }

    Orders {
        bigint id PK ""
        tinyInt payment_status  "required"
        string order_number UK "required"
        datetime order_date  "required"
        tinyInt customer_type  "required"
        tinyInt status  "required"
        tinyInt shipping_payer  "required"
        string phone  "required"
        text shipping_note  ""
        text order_note  ""
        tinyInt sale_channel  "required"
        bigint shipping_address_id FK ""
        bigint customer_id FK "required"
        bigint site_id FK ""
    }

    PaymentRequests {
        bigint id PK "required"
        string payment_request_number UK "required"
        bigint customer_id FK "required"
        datetime request_date "required"
        datetime due_date "required"
        datetime paid_date ""
        decimal total  "required"
        tinyInt payment_status  "required"
        text note  ""
        bigint site_id FK "required"
    }

    %% UC-018-MPR: Payment Request Management
    %% Payment Status Values: 1=Unpaid, 2=PaymentRequested, 3=Paid, 4=Processing, 5=PendingConfirmation, 6=Cancelled
    %% Business Rules:
    %% - Only OrderDetails with status=Arrived(7) eligible for payment requests
    %% - One OrderDetail can only belong to one PaymentRequest at a time
    %% - PaymentRequest contains OrderDetails from single Customer only
    %% - Auto-calculate total from PaymentRequestDetails.amount
    %% - Auto-generate payment_request_number: PR-{YYYYMMDD}-{sequence}

    PaymentRequestDetails {
        bigint id PK "required"
        bigint payment_request_id FK "required"
        bigint order_detail_id FK "required"
        decimal amount "required"
        text note ""
    }

    PurchaseRequests {
        bigint id PK "required"
        string purchase_number UK "required"
        bigint supplier_id FK "required"
        bigint site_id FK "required"
        tinyInt status "required default 1"
        datetime request_date "required"
        datetime expected_delivery_date ""
        datetime actual_delivery_date ""
        decimal total_amount "default 0"
        text notes ""
        text supplier_response ""
    }

    %% UC-015-POD: Purchase Order Details Management
    %% Status Values: 1=Draft, 2=Sent, 3=Confirmed, 4=PartiallyDelivered, 5=Delivered, 6=Cancelled
    %% Business Rules:
    %% - Only Draft status can be deleted (hard delete)
    %% - Sent+ status can only be cancelled (soft delete)  
    %% - Auto-transition to PartiallyDelivered/Delivered based on received_qty
    %% - Integration with WarehouseReceiptDetails for goods receiving
    %% - Auto-fulfillment of pre-orders when goods arrive

    PurchaseRequestDetails {
        bigint id PK "required"
        bigint purchase_request_id FK "required"
        bigint product_item_id FK "required"
        int requested_qty "required"
        int received_qty "default 0"
        decimal unit_price "required"
        decimal total_price "required"
        text notes ""
    }

    Suppliers {
        bigint id PK "required"
        string name  "required"
        string person_in_charge  ""
        string phone  ""
        string address  ""
        text description  ""
        bigint site_id FK "required"
    }

    Units {
        bigint id PK "required"
        string name  "required"
        string unit  "required"
    }

    ProductTypes {
        bigint id PK "required"
        string name  "required"
        int order  ""
        boolean show_on_front  ""
        string color  ""
        bigint site_id FK "required"
    }

    Warehouses {
        bigint id PK "required"
        string code  "required"
        string name  "required"
        string address  "required"
        bigint site_id FK ""
    }

    ShoppingCarts {
        bigint id PK "required"
        bigint user_id FK "required"
    }

    ShoppingCartItems {
        bigint id PK "required"
        bigint shopping_cart_id FK "required"
        bigint product_item_id FK "required"
        int quantity  "required"
        json extra_attributes  ""
    }

    WarehouseOuts {
        bigint id PK "required"
        datetime out_date  "required"
        decimal weight  ""
        text note  ""
        string receiver  "required"
        string address  "required"
        string phone  "required"
        string product_name  ""
        int qty  "required"
        decimal total_price  "required"
        tinyInt status  "required"
        tinyInt delivery_status  "required"
        tinyInt type_of_transport  "required"
        tinyInt shipping_payer  "required"
        decimal cod  ""
        string delivery_service  ""
        decimal estimated_time  ""
        string carrier_status  ""
        text carrier_note  ""
        string tracking_number  ""
        json delivery_attributes  ""
        decimal shipping_charges  ""
        bigint customer_id FK "required"
        bigint site_id FK "required"
    }

    WarehouseOutDetails {
        bigint id PK "required"
        bigint warehouse_out_id FK "required"
        bigint product_item_id FK "required"
        bigint location_id FK "required"
        bigint order_detail_id FK ""
        int qty  "required"
        decimal unit_price  "required"
        decimal total_price  "required"
        text note  ""
    }

    WarehouseReceipts {
        bigint id PK "required"
        string note  ""
        datetime receipt_date  "required"
        bigint site_id FK "required"
    }

    WarehouseReceiptDetails {
        bigint id PK "required"
        bigint warehouse_receipt_id FK "required"
        bigint product_item_id FK "required"
        bigint location_id FK "required"
        bigint order_detail_id FK ""
        bigint purchase_request_detail_id FK ""
        %% UC-015-POD: Links received goods to Purchase Request Details
        %% Used to track which Purchase Request items have been received
        %% Updates received_qty in PurchaseRequestDetails when goods arrive
        int qty  "required"
        decimal purchase_price  "required"
        decimal fee_price  ""
        text note  ""
    }

    Products {
        bigint id PK ""
        string name  "required"
        string code UK "required"
        string supplier_code  ""
        tinyInt product_type_id FK "required"
        text description  ""
        int qty_in_stock  "required"
        decimal weight  ""
        decimal price  "required"
        decimal partner_price  ""
        decimal purchase_price  "required"
        bigint supplier_id FK "required"
        bigint unit_id FK "required"
        bigint category_id FK "required"
        datetime order_closing_date  ""
        bigint default_location_id FK "required"
        bigint site_id FK ""
        json extra_attributes  ""
    }

    Media {
        bigint id PK "required"
        bigint model_id  "required"
        string model_type  "required"
        uuid uuid  ""
        string collection_name  "required"
        string name  "required"
        string file_name  "required"
        string mime_type  ""
        string disk  "required"
        string conversions_disk  ""
        int size  "required"
        json manipulations  "required"
        json custom_properties  "required"
        json generated_conversions  "required"
        json responsive_images  "required"
        int order_column  ""
        bigint site_id FK "required"
    }

    Permissions {
        bigint id PK "required"
        string name  "required"
        string guard_name  "required"
    }

    Roles {
        bigint id PK "required"
        string name  "required"
        string guard_name  "required"
    }

    RolesHasPermissions {
        int permission_id FK "required"
        int role_id FK "required"
    }

    ModelHasRoles {
        int role_id FK "required"
        string model_type  "required"
        int model_id  "required"
    }

    ModelHasPermissions {
        int permission_id FK "required"
        string model_type  "required"
        int model_id  "required"
    }

    ActivityLog {
        bigint id PK "required"
        string log_name  ""
        text description  "required"
        string subject_type  ""
        bigint subject_id  ""
        string event  ""
        string causer_type  ""
        bigint causer_id  ""
        json properties  ""
        uuid batch_uuid  ""
    }

    Sites {
        bigint id PK ""
        string name  "required"
        string slug UK "required"
        text description  ""
        json settings  ""
        int user_id FK ""
    }

    Users {
        bigint id PK ""
        string name  "required"
        string email UK "required"
        string phone_number UK "required"
        bigint site_id FK ""
        bigint customer_id FK ""
    }

    Locations {
        bigint id PK "required"
        string code  "required"
        string name  "required"
        boolean is_default  "required"
        bigint warehouse_id FK "required"
    }

    WarehouseInventory {
        bigint id PK "required"
        bigint product_item_id FK "required"
        bigint location_id FK "required"
        int current_qty "required default 0"
        int reserved_qty "default 0"
        int pre_order_qty "default 0"
        decimal avg_cost "nullable"
        bigint site_id FK "required"
        json metadata "nullable"
        timestamp last_updated
    }

    Customers||--o{Orders:"  "
    Orders||--|{OrderDetails:"includes"
    Customers}|--||Sites:"  "
    Products}|--||Sites:"  "
    Warehouses}|--||Sites:"  "
    Orders}|--||Sites:"  "
    Customers||--o{Addresses:"  "
    Addresses}|--||Wards:"  "
    Wards}|--||Provinces:"  "
    Customers|o..||Users:"  "
    Users|o..o|Sites:"  "
    Products||--|{ProductItems:"  "
    OrderDetails}|--||ProductItems:"  "
    Products||--|{ProductAttributeValues:"  "
    ProductItems||--|{ProductItemAttributeValues:"  "
    ProductItemAttributeValues}|--||ProductAttributeValues:"  "
    Attributes||--|{ProductItemAttributeValues:"  "
    Products}o--||Categories:"  "
    Categories||--o{Categories:"parent_of"
    Products||--o{ProductTags:"has"
    Tags||--o{ProductTags:"  "
    Orders}o--||Addresses:"  "
    Orders}o--||Customers:"  "
    %% UC-018-MPR: Payment Request Management Relationships
    PaymentRequests}o--||Customers:"  "
    PaymentRequests||--|{PaymentRequestDetails:"  "
    PaymentRequestDetails}|--||OrderDetails:"  "
    %% OrderDetails link to PaymentRequestDetails for payment tracking
    OrderDetails}o--||PaymentRequestDetails:"  "
    %% UC-015-POD Key Relationships:
    %% Purchase Request Management
    PurchaseRequests}|--||Suppliers:"  "
    PurchaseRequests}|--||Sites:"  "
    PurchaseRequests||--|{PurchaseRequestDetails:"  "
    PurchaseRequestDetails}|--||ProductItems:"  "
    %% Pre-order Integration - OrderDetails can be linked to PurchaseRequestDetails
    OrderDetails}o--||PurchaseRequestDetails:"  "
    %% Goods Receiving Integration - WarehouseReceiptDetails tracks received goods
    WarehouseReceiptDetails}o--||PurchaseRequestDetails:"  "
    Products}o--||Suppliers:"  "
    Products}o--||Units:"  "
    Products}o--||Locations:"  "
    Products}o--||ProductTypes:"  "
    ProductTypes}|--||Sites:"  "
    Locations}o--||Warehouses:"  "
    ShoppingCarts||--|{ShoppingCartItems:"  "
    ShoppingCartItems||--||ProductItems:"  "
    Users||--||ShoppingCarts:"  "
    WarehouseOuts}o--o{Customers:"  "
    WarehouseOuts||--|{WarehouseOutDetails:"  "
    WarehouseOutDetails||--||ProductItems:"  "
    WarehouseOutDetails||--||Locations:"  "
    WarehouseOutDetails||--||OrderDetails:"  "
    WarehouseReceipts||--|{WarehouseReceiptDetails:"  "
    WarehouseReceiptDetails||--||ProductItems:"  "
    WarehouseReceiptDetails||--||Locations:"  "
    WarehouseReceiptDetails||--||OrderDetails:"  "
    WarehouseReceiptDetails}o--||PurchaseRequestDetails:"  "
    Products||--|{Media:"  "
    ProductItems||--|{Media:"  "
    Categories||--|{Media:"  "
    PaymentRequests||--|{Media:"  "
    Permissions||--o{RolesHasPermissions:"  "
    Roles||--o{RolesHasPermissions:"  "
    Roles||--o{ModelHasRoles:"  "
    Permissions||--o{ModelHasPermissions:"  "
    Users||--o{ModelHasRoles:"  "
    Users||--o{ModelHasPermissions:"  "
    ActivityLog}o--||Users:"causer"
    ActivityLog}o--||Products:"subject"
    ActivityLog}o--||ProductItems:"subject"
    ActivityLog}o--||Orders:"subject"
    ActivityLog}o--||OrderDetails:"subject"
    ActivityLog}o--||WarehouseOuts:"subject"
    ActivityLog}o--||WarehouseOutDetails:"subject"
    ActivityLog}o--||WarehouseReceipts:"subject"
    ActivityLog}o--||WarehouseReceiptDetails:"subject"
    Orders}|--|{Sites:"  "
    OrderDetails}|--||Sites:"  "
    WarehouseOuts}|--||Sites:"  "
    WarehouseReceipts}|--||Sites:"  "
    Categories}|--||Sites:"  "
    Tags}|--||Sites:"  "
    Suppliers}|--||Sites:"  "
    ProductItems}|--||Sites:"  "
    PaymentRequests}|--||Sites:"  "
    Media}|--||Sites:"  "
    PurchaseRequests}|--||Sites:"  "
    WarehouseInventory}|--||Sites:"  "
    Attributes}|--||Sites:"  "
    WarehouseInventory}|--||ProductItems:"  "
    WarehouseInventory}|--||Locations:"  "
    ProductItemAttributeValues||--||ProductItems:"  "
    ProductItemAttributeValues||--||ProductAttributeValues:"  "
    ProductAttributeValues||--||Products:"  "
    ProductAttributeValues||--||Attributes:"  "
    Users}|--|{Sites:"  "
```

---

## UC-015-POD: Purchase Order Details Management

### Data Flow Overview

1. **Pre-order Creation**: When OrderDetails have status = WaitingForStock (6), they become eligible for Purchase Request creation

2. **Purchase Request Creation**: 
   - From Pre-orders: Group OrderDetails by Supplier, create PurchaseRequest and PurchaseRequestDetails
   - Direct: Manually select ProductItems and create PurchaseRequest
   - Link: Set OrderDetails.purchase_request_detail_id → PurchaseRequestDetails.id

3. **Purchase Request Management**:
   - Status progression: Draft → Sent → Confirmed → PartiallyDelivered/Delivered
   - Supplier coordination via PDF export and email notifications
   - Bulk operations for multiple Purchase Requests

4. **Goods Receiving**:
   - Create WarehouseReceipt and WarehouseReceiptDetails
   - Link: WarehouseReceiptDetails.purchase_request_detail_id → PurchaseRequestDetails.id  
   - Update: PurchaseRequestDetails.received_qty += received quantity
   - Auto-transition: PurchaseRequest status based on received_qty vs requested_qty

5. **Pre-order Fulfillment**:
   - When goods arrive: Auto-fulfill linked OrderDetails
   - Update: OrderDetails.status from WaitingForStock → Arrived
   - Update: Order.status recalculated from all OrderDetails
   - Notification: Customers informed about stock availability

### Delete/Cancel Operations

**Hard Delete (Draft status only)**:
- Delete PurchaseRequest and all PurchaseRequestDetails
- Reset OrderDetails.purchase_request_detail_id = NULL
- Revert OrderDetails.status: WaitingForStock → Processing  
- Recalculate Order.status from remaining OrderDetails
- Notify affected customers

**Soft Delete (Sent+ status)**:
- Update PurchaseRequest.status = Cancelled (6)
- Keep OrderDetails.purchase_request_detail_id for audit trail
- Revert OrderDetails.status: WaitingForStock → Processing
- Recalculate Order.status
- Email notification to supplier
- Notify affected customers about delays

### Key Business Rules

- **Site Isolation**: All entities filtered by site_id
- **Supplier Constraint**: PurchaseRequest contains ProductItems from single supplier only
- **Status Validation**: Only valid status transitions allowed
- **Referential Integrity**: Cannot delete Suppliers/ProductItems with active PurchaseRequests
- **Transaction Safety**: All delete/cancel operations wrapped in database transactions
- **Audit Trail**: Complete logging of all status changes and delete/cancel operations

---

## UC-016-MWI & UC-017-MWO: Warehouse In/Out Management

### Data Flow Overview

**UC-016-MWI: Warehouse In Management**
1. **Receipt Creation**: Create WarehouseReceipt with multiple WarehouseReceiptDetails
2. **Purchase Request Integration**: Link WarehouseReceiptDetails to PurchaseRequestDetails
3. **Inventory Updates**: Auto-update WarehouseInventory.current_qty += received quantity
4. **Cost Calculation**: Recalculate avg_cost for ProductItem-Location combinations
5. **Pre-order Fulfillment**: Auto-fulfill OrderDetails when sufficient stock arrives

**UC-017-MWO: Warehouse Out Management**  
1. **Direct Creation**: Create warehouse out slips for any customer/purpose
2. **Order Integration**: Create warehouse outs from OrderDetails (Status=Invoiced, Payment_Status=Paid)
3. **Auto-Location Selection**: Automatically select locations based on available stock
4. **Inventory Deduction**: Auto-update WarehouseInventory.current_qty -= shipped quantity
5. **Order Fulfillment**: Update OrderDetails status from Invoiced → Delivering

### Payment Status Integration (UC-008-MO & UC-014-MOD)

**Payment Status Values (1-5)**:
- 1=Unpaid, 2=PaymentRequested, 3=Paid, 4=Processing, 5=PendingConfirmation

**Warehouse Out Eligibility**:
- OrderDetails must have: Status = Invoiced (8) AND Payment_Status = Paid (3)
- UC-017-MWO filters and groups OrderDetails by Customer for batch processing
- Auto-fills shipping information from Customer and Order data

### Key Integration Points

**UC-016-MWI Integrations**:
- **UC-015-PO**: Purchase Request fulfillment and received_qty updates
- **UC-010-MI**: WarehouseInventory real-time updates and pre-order auto-fulfillment
- **UC-008-MO/UC-014-MOD**: OrderDetails status updates when pre-orders are fulfilled

**UC-017-MWO Integrations**:
- **UC-014-MOD**: OrderDetails filtering and bulk warehouse out creation
- **UC-008-MO**: Payment status validation and order status progression
- **UC-007-MC**: Customer information and shipping address auto-population
- **UC-010-MI**: Real-time inventory deduction and stock validation

### Business Rules

- **Transaction Safety**: All warehouse operations wrapped in database transactions
- **Real-time Inventory**: WarehouseInventory updated immediately on receipt/shipment
- **Location Auto-selection**: UC-017 automatically selects optimal locations based on stock levels
- **Customer Grouping**: Multiple OrderDetails for same customer grouped into single warehouse out
- **Status Synchronization**: OrderDetails and Order status automatically updated on warehouse operations

---

## UC-018-MPR: Payment Request Management

### Data Flow Overview

1. **Payment Request Creation**: Create PaymentRequest from OrderDetails with status = Arrived (7)
2. **Customer Grouping**: Automatically group OrderDetails by Customer for efficient payment processing
3. **PaymentRequestDetail Creation**: Create individual PaymentRequestDetails linking each OrderDetail
4. **Status Management**: Manage payment status progression from PaymentRequested to Paid
5. **Payment Tracking**: Complete audit trail of payment status changes and customer communications

### Payment Request Structure

**PaymentRequest (Master)**:
- Contains header information: customer, dates, total amount, payment status
- Auto-generated payment_request_number with format: PR-{YYYYMMDD}-{sequence}
- Groups multiple OrderDetails for single customer into one payment request

**PaymentRequestDetail (Detail)**:
- Links individual OrderDetails to PaymentRequest
- Stores amount and notes specific to each OrderDetail
- Enables granular tracking and audit trail for each item

### Key Integration Points

**UC-014-MOD Integration**:
- OrderDetails with status = Arrived (7) become eligible for payment requests
- OrderDetails.payment_request_detail_id links to PaymentRequestDetails
- Payment status synchronization between PaymentRequest and OrderDetails

**UC-017-MWO Integration**:
- OrderDetails with status = Invoiced (8) AND payment_status = Paid (3) eligible for warehouse out
- Payment verification required before goods can be shipped

**UC-008-MO Integration**:
- Payment status affects overall Order management workflow
- Customer information used for payment request generation

### Payment Status Flow (1-6)

1. **Unpaid (1)**: OrderDetail has no payment request
2. **PaymentRequested (2)**: OrderDetail included in PaymentRequest 
3. **Paid (3)**: Customer has paid, enables warehouse out eligibility
4. **Processing (4)**: Payment being processed by admin/system
5. **PendingConfirmation (5)**: Waiting for payment confirmation
6. **Cancelled (6)**: Payment request cancelled, OrderDetail reverts to Unpaid

### Business Rules

- **Eligibility**: Only OrderDetails with status = Arrived (7) can be included in PaymentRequest
- **Customer Isolation**: PaymentRequest contains OrderDetails from single Customer only
- **Site Isolation**: All entities filtered by site_id for multi-tenant support
- **Unique Assignment**: OrderDetail can only belong to one PaymentRequest at a time
- **Auto-calculation**: PaymentRequest.total auto-calculated from PaymentRequestDetails
- **Status Synchronization**: PaymentRequest status changes propagate to all linked OrderDetails
- **Audit Trail**: Complete logging of payment status changes with timestamps and reasons

