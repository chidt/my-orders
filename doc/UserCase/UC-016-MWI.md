````markdown
# UC016: Manage Warehouse In (Quản lý nhập kho)

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-016-MWI                                 |
| Tên Use Case   | Quản lý phiếu nhập kho                     |
| Actor          | SiteAdmin (người dùng có quyền hạn manage_inventory) |
| Mô tả          | Người dùng có thể tạo, xem, cập nhật phiếu nhập kho (WarehouseReceipt) và chi tiết nhập kho (WarehouseReceiptDetails), tự động cập nhật tồn kho theo thời gian thực |
| Độ ưu tiên     | Cao                                        |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có quyền hạn **manage_inventory**<br>- Đã có warehouse, location và sản phẩm trong hệ thống<br>- WarehouseInventory table đã được thiết lập |
| Post-condition | Phiếu nhập kho được tạo thành công, tồn kho được cập nhật chính xác trong WarehouseInventory |

---

## Luồng chính (Main Flow)

### Luồng xem danh sách phiếu nhập kho

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | SiteAdmin  | Đăng nhập vào hệ thống |
| 2    | Hệ thống   | Kiểm tra vai trò và quyền sở hữu trang web |
| 3    | Hệ thống   | Hiển thị **"Quản lý nhập kho"** trong menu sidebar |
| 4    | SiteAdmin  | Nhấn vào **"Quản lý nhập kho"** |
| 5    | Hệ thống   | Hiển thị danh sách WarehouseReceipts thuộc trang web hiện tại |
| 6    | Hệ thống   | Hiển thị các cột: ID, Receipt Date, Total Items, Total Value, Note, Actions |
| 7    | Hệ thống   | Hiển thị bộ lọc theo ngày nhập, warehouse, supplier |
| 8    | Hệ thống   | Hiển thị nút **"Tạo phiếu nhập mới"** |

### Luồng tạo phiếu nhập kho mới

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 9    | SiteAdmin  | Nhấn nút **"Tạo phiếu nhập mới"** |
| 10   | Hệ thống   | Hiển thị form tạo phiếu nhập kho |
| 11   | SiteAdmin  | Chọn ngày nhập kho (mặc định hôm nay) |
| 12   | SiteAdmin  | Nhập ghi chú cho phiếu nhập (tùy chọn) |
| 13   | SiteAdmin  | Nhấn **"Thêm sản phẩm"** |
| 14   | Hệ thống   | Hiển thị modal chọn ProductItems thuộc site |
| 15   | SiteAdmin  | Tìm kiếm và chọn ProductItems cần nhập |
| 16   | SiteAdmin  | Chọn location để lưu trữ cho từng ProductItem |
| 17   | SiteAdmin  | Nhập số lượng nhập cho từng ProductItem |
| 18   | SiteAdmin  | Nhập giá nhập (purchase_price) |
| 19   | SiteAdmin  | Nhập phí phát sinh nếu có (fee_price) |
| 20   | SiteAdmin  | Nhập ghi chú cho từng item (tùy chọn) |
| 21   | Hệ thống   | Tự động tính tổng giá trị nhập = qty × (purchase_price + fee_price) |
| 22   | SiteAdmin  | Xem preview tổng số lượng và tổng giá trị |
| 23   | SiteAdmin  | Nhấn **"Lưu phiếu nhập"** |
| 24   | Hệ thống   | Validate dữ liệu đầu vào |
| 25   | Hệ thống   | **Begin Database Transaction** |
| 26   | Hệ thống   | Tạo WarehouseReceipt với thông tin cơ bản |
| 27   | Hệ thống   | Tạo WarehouseReceiptDetails cho từng ProductItem |
| 28   | Hệ thống   | **Cập nhật WarehouseInventory.current_qty** += qty |
| 29   | Hệ thống   | **Tính toán lại avg_cost** cho từng ProductItem-Location |
| 30   | Hệ thống   | **Commit Transaction** |
| 31   | Hệ thống   | Ghi audit log cho warehouse operation |
| 32   | Hệ thống   | Thông báo tạo thành công |

### Luồng nhập kho từ Purchase Request

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 33   | SiteAdmin  | Chọn **"Nhập từ Purchase Request"** |
| 34   | Hệ thống   | Hiển thị danh sách Purchase Requests có status = Confirmed |
| 35   | SiteAdmin  | Chọn Purchase Request cần nhập hàng |
| 36   | Hệ thống   | Hiển thị danh sách PurchaseRequestDetails chưa nhập đủ |
| 37   | Hệ thống   | Pre-fill thông tin: ProductItem, requested_qty, unit_price |
| 38   | SiteAdmin  | Nhập số lượng thực tế nhận được (có thể < requested_qty) |
| 39   | SiteAdmin  | Chọn location để nhập hàng |
| 40   | SiteAdmin  | Điều chỉnh purchase_price thực tế nếu khác dự kiến |
| 41   | SiteAdmin  | Nhấn **"Tạo phiếu nhập"** |
| 42   | Hệ thống   | Tạo WarehouseReceipt và WarehouseReceiptDetails |
| 43   | Hệ thống   | **Liên kết với PurchaseRequestDetails** (purchase_request_detail_id) |
| 44   | Hệ thống   | **Cập nhật received_qty** trong PurchaseRequestDetails |
| 45   | Hệ thống   | **Cập nhật WarehouseInventory** |
| 46   | Hệ thống   | Kiểm tra received_qty vs requested_qty |
| 47   | Hệ thống   | **Auto-fulfill pre-orders** nếu có hàng đủ |
| 48   | Hệ thống   | **Cập nhật Purchase Request status** nếu cần |
| 49   | Hệ thống   | **Thông báo customers** có pre-order rằng hàng đã về |

### Luồng nhập kho từ OrderDetails có status = WaitingForStock

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 50   | SiteAdmin  | Chọn **"Nhập từ OrderDetails"** |
| 51   | Hệ thống   | Hiển thị danh sách OrderDetails có status = WaitingForStock (7) |
| 52   | Hệ thống   | Group OrderDetails theo ProductItem |
| 53   | SiteAdmin  | Chọn OrderDetails cần nhập kho |
| 54   | Hệ thống   | Pre-fill thông tin: ProductItem, quantity từ OrderDetails |
| 55   | SiteAdmin  | Chọn location để nhập hàng |
| 56   | SiteAdmin  | Nhập purchase_price thực tế |
| 57   | SiteAdmin  | Nhấn **"Tạo phiếu nhập"** |
| 58   | Hệ thống   | Tạo WarehouseReceipt và WarehouseReceiptDetails |
| 59   | Hệ thống   | **Liên kết với OrderDetails** (order_detail_id) |
| 60   | Hệ thống   | **Cập nhật WarehouseInventory** |
| 61   | Hệ thống   | **Cập nhật OrderDetails status**: WaitingForStock → Arrived (8) |
| 62   | Hệ thống   | **Cập nhật Order status** nếu cần |
| 63   | Hệ thống   | **Thông báo customers** rằng hàng đã nhập kho |

### Luồng xem chi tiết phiếu nhập kho

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 50   | SiteAdmin  | Nhấn vào một WarehouseReceipt để xem chi tiết |
| 51   | Hệ thống   | Hiển thị thông tin WarehouseReceipt: Receipt Date, Note, Total Items, Total Value |
| 52   | Hệ thống   | Hiển thị bảng WarehouseReceiptDetails với các cột: |
|      |            | - ProductItem (name + SKU) |
|      |            | - Location |
|      |            | - Quantity |
|      |            | - Purchase Price |
|      |            | - Fee Price |
|      |            | - Total Price |
|      |            | - Linked Purchase Request (nếu có) |
|      |            | - Linked Order Detail (nếu có) |
|      |            | - Notes |
| 53   | Hệ thống   | Hiển thị liên kết đến Purchase Request nếu có |
| 54   | Hệ thống   | Hiển thị current inventory levels sau khi nhập |

### Luồng chỉnh sửa phiếu nhập kho

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 55   | SiteAdmin  | Nhấn **"Chỉnh sửa"** trên WarehouseReceipt |
| 56   | Hệ thống   | Kiểm tra permission và khả năng chỉnh sửa |
| 57   | Hệ thống   | Hiển thị form chỉnh sửa với dữ liệu hiện tại |
| 58   | SiteAdmin  | Cập nhật receipt_date, note |
| 59   | SiteAdmin  | Thêm/xóa/sửa WarehouseReceiptDetails |
| 60   | SiteAdmin  | Nhấn **"Cập nhật"** |
| 61   | Hệ thống   | **Begin Database Transaction** |
| 62   | Hệ thống   | **Revert cũ WarehouseInventory changes** |
| 63   | Hệ thống   | Cập nhật WarehouseReceipt và Details |
| 64   | Hệ thống   | **Apply mới WarehouseInventory changes** |
| 65   | Hệ thống   | **Recalculate avg_cost** |
| 66   | Hệ thống   | **Commit Transaction** |
| 67   | Hệ thống   | Audit log thay đổi |
| 68   | Hệ thống   | Thông báo cập nhật thành công |

### Luồng xóa phiếu nhập kho

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 69   | SiteAdmin  | Nhấn **"Xóa"** trên WarehouseReceipt |
| 70   | Hệ thống   | Kiểm tra điều kiện cho phép xóa |
| 71   | Hệ thống   | Hiển thị warning về impact đến inventory |
| 72   | SiteAdmin  | Nhập lý do xóa (bắt buộc) |
| 73   | SiteAdmin  | Nhấn **"Xác nhận xóa"** |
| 74   | Hệ thống   | **Begin Database Transaction** |
| 75   | Hệ thống   | **Revert WarehouseInventory changes** (current_qty -= qty) |
| 76   | Hệ thống   | Xóa WarehouseReceiptDetails |
| 77   | Hệ thống   | Xóa WarehouseReceipt |
| 78   | Hệ thống   | **Recalculate avg_cost** |
| 79   | Hệ thống   | **Commit Transaction** |
| 80   | Hệ thống   | Audit log deletion với lý do |
| 81   | Hệ thống   | Thông báo xóa thành công |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Không có quyền **manage_inventory** | Không hiển thị menu quản lý nhập kho |
| AF-02| ProductItem không thuộc site | Không cho phép chọn ProductItem |
| AF-03| Location không thuộc site   | Không cho phép chọn Location |
| AF-04| Số lượng nhập <= 0          | Validation error, không cho phép lưu |
| AF-05| Purchase price < 0          | Validation error, không cho phép lưu |
| AF-06| Transaction rollback        | Hiển thị lỗi và không thay đổi inventory |
| AF-07| Chỉnh sửa phiếu đã liên kết Order | Cảnh báo và yêu cầu xác nhận |
| AF-08| Xóa phiếu nhập làm inventory âm | Không cho phép xóa |
| AF-09| Purchase Request đã hoàn thành | Không cho phép nhập thêm |
| AF-10| Location hết chỗ (nếu có giới hạn) | Cảnh báo và yêu cầu chọn location khác |
| AF-11| Duplicate receipt trong ngày | Cảnh báo duplicate và yêu cầu xác nhận |
| AF-12| Concurrent update inventory | Conflict detection và yêu cầu refresh |

---

## Dữ liệu hiển thị và tìm kiếm

### Dữ liệu hiển thị trong danh sách WarehouseReceipts
- **ID**: WarehouseReceipt ID
- **Receipt Date**: Ngày nhập kho
- **Total Items**: Tổng số lượng ProductItems
- **Total Quantity**: Tổng số lượng nhập
- **Total Value**: Tổng giá trị nhập kho
- **Supplier**: Supplier nếu nhập từ Purchase Request
- **Purchase Request**: Link đến PR nếu có
- **Note**: Ghi chú
- **Created By**: Người tạo
- **Created At**: Thời gian tạo

### Dữ liệu hiển thị trong WarehouseReceiptDetails
- **ProductItem**: Tên và SKU sản phẩm
- **Product Info**: Category, Type, Supplier
- **Location**: Warehouse + Location name
- **Quantity**: Số lượng nhập
- **Purchase Price**: Giá nhập đơn vị
- **Fee Price**: Phí phát sinh
- **Total Price**: Thành tiền
- **Purchase Request Detail**: Link nếu có
- **Order Detail**: Link nếu có
- **Current Stock**: Tồn kho hiện tại tại location
- **Notes**: Ghi chú cho item

### Tiêu chí tìm kiếm và lọc
#### **Tìm kiếm text (global search)**
- Receipt ID
- Product Name
- ProductItem SKU
- Supplier Name
- Purchase Request Number
- Notes

#### **Bộ lọc (Filters)**
- **Receipt Date Range**: Date picker (from - to)
- **Warehouse**: Dropdown list warehouses
- **Location**: Cascade dropdown theo warehouse
- **Supplier**: Dropdown với search autocomplete
- **Purchase Request**: Dropdown active PRs
- **Product Category**: Multi-select dropdown
- **Product Type**: Multi-select với color indicator
- **Value Range**: Number input (min - max total value)
- **Has Purchase Request**: Checkbox filter

### Export và báo cáo
- **Export Excel**: Danh sách receipts với filtering
- **Export PDF**: Chi tiết receipt để in phiếu
- **Inventory Impact Report**: Báo cáo ảnh hưởng tồn kho
- **Supplier Performance**: Báo cáo nhập hàng theo supplier
- **Cost Analysis**: Phân tích giá nhập theo thời gian

---

## Quy tắc nghiệp vụ

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Chỉ hiển thị WarehouseReceipts thuộc site hiện tại (site_id isolation)             |
| BR-02 | Kiểm tra quyền **manage_inventory** trước khi cho phép tạo/cập nhật                  |
| BR-03 | Receipt_date không được vượt quá ngày hiện tại                                      |
| BR-04 | Quantity phải là số nguyên dương (> 0)                                               |
| BR-05 | Purchase_price phải >= 0                                                             |
| BR-06 | Fee_price phải >= 0 nếu có                                                           |
| BR-07 | **Cập nhật WarehouseInventory phải trong database transaction**                     |
| BR-08 | **current_qty += qty khi tạo receipt**                                               |
| BR-09 | **current_qty -= qty khi xóa receipt**                                               |
| BR-10 | **Recalculate avg_cost** mỗi khi có thay đổi receipt                                 |
| BR-11 | **Unique constraint**: Không duplicate receipt cho cùng PR detail                   |
| BR-12 | **Auto-fulfill pre-orders** khi nhập hàng từ Purchase Request                       |
| BR-13 | **Auto-update Purchase Request status** dựa trên received_qty                       |
| BR-14 | Chỉ cho phép chỉnh sửa receipt trong 24h sau tạo (hoặc theo config)                |
| BR-15 | Không cho phép xóa receipt nếu làm inventory âm                                     |
| BR-16 | **Site isolation**: chỉ select ProductItems, Locations thuộc site hiện tại         |
| BR-17 | **Audit trail**: Log đầy đủ mọi thay đổi inventory                                  |
| BR-18 | **Real-time inventory**: WarehouseInventory cập nhật tức thì                        |
| BR-19 | **Data integrity**: Rollback toàn bộ nếu có lỗi                                     |
| BR-20 | **Performance**: Batch update cho multiple items                                     |

---

## UI/UX Requirements

### Danh sách WarehouseReceipts
- **Responsive table**: Mobile-friendly với collapse columns
- **Quick actions**: Inline buttons cho View, Edit, Delete
- **Advanced filters**: Collapsible filter panel
- **Sorting**: Multi-column sorting
- **Pagination**: Server-side pagination cho large datasets
- **Real-time updates**: WebSocket updates cho concurrent users

### Tạo/Sửa WarehouseReceipt
- **Wizard interface**: Step-by-step process
- **Product selection**: Smart search với autocomplete
- **Location picker**: Visual warehouse/location selector
- **Real-time calculation**: Auto-update totals
- **Validation feedback**: Immediate validation messages
- **Draft auto-save**: Auto-save draft during input

### Chi tiết WarehouseReceipt
- **Master-detail layout**: Receipt info ở trên, details ở dưới
- **Related data links**: Quick navigation đến PR, Orders
- **Inventory impact**: Show before/after inventory levels
- **Action history**: Timeline của các thay đổi
- **Print layout**: Print-friendly receipt format

### Performance & UX
- **Loading states**: Skeleton screens và progress indicators
- **Error handling**: Friendly error messages với actions
- **Keyboard shortcuts**: Power user efficiency
- **Responsive design**: Mobile-first approach
- **Accessibility**: Screen reader support
- **Offline capability**: Basic offline mode cho remote warehouses

---

## Integration Points

### Với các Use Cases khác
- **UC-015-PO**: Purchase Request fulfillment và goods receiving
- **UC-010-MI**: Inventory management và pre-order auto-fulfillment
- **UC-006-MP**: Product và ProductItem information
- **UC-004-MSW, UC-005-MWL**: Warehouse và Location selection
- **UC-008-MO, UC-014-MOD**: Order fulfillment tracking

### Với External Systems
- **Barcode scanners**: Mobile barcode scanning support
- **ERP integration**: Data sync với external ERP systems
- **Audit systems**: Compliance logging
- **Notification system**: Real-time alerts và notifications
- **Reporting system**: Business intelligence integration

````
