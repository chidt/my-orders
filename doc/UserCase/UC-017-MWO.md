````markdown
# UC017: Manage Warehouse Out (Quản lý xuất kho)

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-017-MWO                                 |
| Tên Use Case   | Quản lý phiếu xuất kho                     |
| Actor          | SiteAdmin (người dùng có quyền hạn manage_inventory) |
| Mô tả          | Người dùng có thể tạo phiếu xuất kho trực tiếp hoặc từ đơn hàng chi tiết, tự động xác định vị trí xuất dựa trên tồn kho, cập nhật inventory real-time |
| Độ ưu tiên     | Cao                                        |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có quyền hạn **manage_inventory**<br>- Đã có warehouse, location và sản phẩm trong hệ thống<br>- WarehouseInventory đã có tồn kho |
| Post-condition | Phiếu xuất kho được tạo thành công, tồn kho được trừ chính xác, đơn hàng được cập nhật trạng thái |

---

## Luồng chính (Main Flow)

### Luồng xem danh sách phiếu xuất kho

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | SiteAdmin  | Đăng nhập vào hệ thống |
| 2    | Hệ thống   | Kiểm tra vai trò và quyền sở hữu trang web |
| 3    | Hệ thống   | Hiển thị **"Quản lý xuất kho"** trong menu sidebar |
| 4    | SiteAdmin  | Nhấn vào **"Quản lý xuất kho"** |
| 5    | Hệ thống   | Hiển thị danh sách WarehouseOuts thuộc trang web hiện tại |
| 6    | Hệ thống   | Hiển thị các cột: ID, Out Date, Customer, Total Items, Total Value, Status, Delivery Status, Actions |
| 7    | Hệ thống   | Hiển thị bộ lọc theo ngày xuất, customer, status, delivery_status |
| 8    | Hệ thống   | Hiển thị nút **"Tạo phiếu xuất mới"** |

### Luồng tạo phiếu xuất kho trực tiếp

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 9    | SiteAdmin  | Nhấn nút **"Tạo phiếu xuất mới"** |
| 10   | Hệ thống   | Hiển thị form tạo phiếu xuất kho |
| 11   | SiteAdmin  | Chọn ngày xuất kho (mặc định hôm nay) |
| 12   | SiteAdmin  | Nhập thông tin người nhận: tên, địa chỉ, số điện thoại |
| 13   | SiteAdmin  | Chọn customer liên quan (tùy chọn) |
| 14   | SiteAdmin  | Nhập thông tin vận chuyển: loại vận chuyển, người thanh toán VC, COD |
| 15   | SiteAdmin  | Nhấn **"Thêm sản phẩm"** |
| 16   | Hệ thống   | Hiển thị modal chọn ProductItems có tồn kho > 0 |
| 17   | SiteAdmin  | Tìm kiếm và chọn ProductItems cần xuất |
| 18   | SiteAdmin  | Nhập số lượng cần xuất |
| 19   | Hệ thống   | **Tự động xác định location xuất** dựa trên current_qty tại các location |
| 20   | Hệ thống   | Hiển thị location được chọn và số lượng available |
| 21   | Hệ thống   | Kiểm tra available_qty >= requested_qty |
| 22   | SiteAdmin  | Điều chỉnh unit_price nếu khác giá bán mặc định |
| 23   | Hệ thống   | Tự động tính total_price = qty × unit_price |
| 24   | SiteAdmin  | Nhập ghi chú cho từng item (tùy chọn) |
| 25   | SiteAdmin  | Xem preview tổng số lượng và tổng giá trị |
| 26   | SiteAdmin  | Nhấn **"Tạo phiếu xuất"** |
| 27   | Hệ thống   | Validate dữ liệu và kiểm tra available stock |
| 28   | Hệ thống   | **Begin Database Transaction** |
| 29   | Hệ thống   | Tạo WarehouseOut với thông tin cơ bản |
| 30   | Hệ thống   | Tạo WarehouseOutDetails cho từng ProductItem |
| 31   | Hệ thống   | **Cập nhật WarehouseInventory.current_qty** -= qty |
| 32   | Hệ thống   | **Commit Transaction** |
| 33   | Hệ thống   | Ghi audit log cho warehouse operation |
| 34   | Hệ thống   | Thông báo tạo thành công |

### Luồng xuất kho từ Order Details (UC-014)

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 35   | SiteAdmin  | Truy cập **UC-014: Manage Order Details** |
| 36   | SiteAdmin  | Áp dụng bộ lọc: Customer, Status = Invoiced (9), Payment_Status = Paid |
| 37   | Hệ thống   | Hiển thị danh sách OrderDetails đủ điều kiện xuất kho |
| 38   | SiteAdmin  | Chọn multiple OrderDetails bằng checkbox |
| 39   | SiteAdmin  | Nhấn **"Tạo phiếu xuất kho"** từ bulk actions |
| 40   | Hệ thống   | **Group OrderDetails theo Customer** |
| 41   | Hệ thống   | Tạo preview phiếu xuất kho cho từng Customer |
| 42   | Hệ thống   | **Tự động điền thông tin** từ Customer và Orders: |
|      |            | - Receiver: Customer name |
|      |            | - Address: Shipping address từ Order |
|      |            | - Phone: Customer phone |
| 43   | Hệ thống   | **Tự động xác định location xuất** cho từng ProductItem |
| 44   | Hệ thống   | Kiểm tra available stock cho tất cả items |
| 45   | SiteAdmin  | Review và điều chỉnh thông tin nếu cần |
| 46   | SiteAdmin  | Xác nhận tạo phiếu xuất |
| 47   | Hệ thống   | **Begin Database Transaction** |
| 48   | Hệ thống   | Tạo WarehouseOut cho từng Customer |
| 49   | Hệ thống   | Tạo WarehouseOutDetails với order_detail_id liên kết |
| 50   | Hệ thống   | **Cập nhật WarehouseInventory** |
| 51   | Hệ thống   | **Cập nhật OrderDetails status**: Invoiced → Delivering |
| 52   | Hệ thống   | **Cập nhật Order status** nếu tất cả OrderDetails đã Delivering |
| 53   | Hệ thống   | **Commit Transaction** |
| 54   | Hệ thống   | **Thông báo customers** về việc hàng đã xuất kho |
| 55   | Hệ thống   | Thông báo tạo thành công với summary |

### Luồng auto-select location xuất

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 56   | Hệ thống   | Khi SiteAdmin chọn ProductItem và quantity |
| 57   | Hệ thống   | **Query WarehouseInventory** theo product_item_id và site_id |
| 58   | Hệ thống   | **Filter locations có current_qty > 0** |
| 59   | Hệ thống   | **Sắp xếp theo current_qty DESC** (ưu tiên location có nhiều hàng nhất) |
| 60   | Hệ thống   | **Algorithm phân bổ**: |
|      |            | - Nếu 1 location đủ hàng: chọn location đó |
|      |            | - Nếu cần nhiều locations: chọn locations theo thứ tự current_qty |
| 61   | Hệ thống   | **Tự động tạo multiple WarehouseOutDetails** nếu cần nhiều locations |
| 62   | Hệ thống   | Hiển thị location allocation cho SiteAdmin xem |
| 63   | SiteAdmin  | **Manual override**: Có thể thay đổi location allocation nếu cần |

### Luồng xem chi tiết phiếu xuất kho

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 64   | SiteAdmin  | Nhấn vào một WarehouseOut để xem chi tiết |
| 65   | Hệ thống   | Hiển thị thông tin WarehouseOut: |
|      |            | - Out Date, Receiver, Address, Phone |
|      |            | - Customer Info, Total Items, Total Value |
|      |            | - Status, Delivery Status, Tracking Info |
| 66   | Hệ thống   | Hiển thị bảng WarehouseOutDetails với các cột: |
|      |            | - ProductItem (name + SKU) |
|      |            | - Location |
|      |            | - Quantity |
|      |            | - Unit Price |
|      |            | - Total Price |
|      |            | - Linked Order Detail |
|      |            | - Notes |
| 67   | Hệ thống   | Hiển thị liên kết đến Orders và Customers |
| 68   | Hệ thống   | Hiển thị delivery tracking information |

### Luồng cập nhật trạng thái giao hàng

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 69   | SiteAdmin  | Nhấn **"Cập nhật giao hàng"** |
| 70   | Hệ thống   | Hiển thị form cập nhật delivery status |
| 71   | SiteAdmin  | Cập nhật delivery_status (Pending, InTransit, Delivered, Cancelled) |
| 72   | SiteAdmin  | Nhập tracking_number nếu có |
| 73   | SiteAdmin  | Cập nhật carrier_status và carrier_note |
| 74   | SiteAdmin  | Nhập estimated_time hoặc actual delivery time |
| 75   | SiteAdmin  | Nhấn **"Cập nhật"** |
| 76   | Hệ thống   | Cập nhật WarehouseOut với thông tin mới |
| 77   | Hệ thống   | **Cập nhật OrderDetails status** nếu delivery_status = Delivered → Completed |
| 78   | Hệ thống   | **Cập nhật Order status** nếu tất cả OrderDetails = Completed |
| 79   | Hệ thống   | **Gửi notification** cho customer về trạng thái giao hàng |
| 80   | Hệ thống   | Ghi audit log |

### Luồng hủy phiếu xuất kho

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 81   | SiteAdmin  | Nhấn **"Hủy phiếu xuất"** |
| 82   | Hệ thống   | Kiểm tra điều kiện hủy (chỉ status = Pending) |
| 83   | Hệ thống   | Hiển thị warning về inventory impact |
| 84   | SiteAdmin  | Nhập lý do hủy (bắt buộc) |
| 85   | SiteAdmin  | Nhấn **"Xác nhận hủy"** |
| 86   | Hệ thống   | **Begin Database Transaction** |
| 87   | Hệ thống   | **Revert WarehouseInventory changes** (current_qty += qty) |
| 88   | Hệ thống   | Cập nhật WarehouseOut.status = Cancelled |
| 89   | Hệ thống   | **Revert OrderDetails status** nếu có liên kết |
| 90   | Hệ thống   | **Commit Transaction** |
| 91   | Hệ thống   | Audit log cancellation với lý do |
| 92   | Hệ thống   | Thông báo hủy thành công |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Không có quyền **manage_inventory** | Không hiển thị menu quản lý xuất kho |
| AF-02| Không đủ available stock    | Hiển thị lỗi và available quantity |
| AF-03| Location không có hàng      | Auto-select location khác có hàng |
| AF-04| Customer không thuộc site   | Không cho phép chọn Customer |
| AF-05| OrderDetail không đủ điều kiện | Không hiển thị trong danh sách filter |
| AF-06| Multiple customers selected | Tạo nhiều phiếu xuất riêng biệt |
| AF-07| Transaction rollback        | Không thay đổi inventory, thông báo lỗi |
| AF-08| Hủy phiếu đã giao hàng      | Không cho phép hủy |
| AF-09| Product discontinued        | Cảnh báo và yêu cầu xác nhận |
| AF-10| Location access restricted  | Auto-select location khác |
| AF-11| Delivery service unavailable | Cảnh báo và manual input required |
| AF-12| COD amount invalid          | Validation error và correction |
| AF-13| Concurrent stock update     | Conflict detection và refresh required |
| AF-14| Partial allocation failure  | Hiển thị partial success với details |

---

## Dữ liệu hiển thị và tìm kiếm

### Dữ liệu hiển thị trong danh sách WarehouseOuts
- **ID**: WarehouseOut ID
- **Out Date**: Ngày xuất kho
- **Customer**: Tên khách hàng (nếu có)
- **Receiver**: Tên người nhận
- **Address**: Địa chỉ giao hàng (rút gọn)
- **Phone**: Số điện thoại người nhận
- **Total Items**: Tổng số lượng ProductItems
- **Total Quantity**: Tổng số lượng xuất
- **Total Value**: Tổng giá trị xuất kho
- **Status**: Trạng thái phiếu xuất
- **Delivery Status**: Trạng thái giao hàng
- **Tracking Number**: Mã vận đơn
- **Created By**: Người tạo phiếu
- **Actions**: View, Edit, Cancel, Track

### Dữ liệu hiển thị trong WarehouseOutDetails
- **ProductItem**: Tên và SKU sản phẩm
- **Product Info**: Category, Type, Supplier
- **Location**: Warehouse + Location name
- **Quantity**: Số lượng xuất
- **Available Before**: Số lượng available trước khi xuất
- **Available After**: Số lượng available sau khi xuất
- **Unit Price**: Giá bán đơn vị
- **Total Price**: Thành tiền
- **Order Detail**: Link đến OrderDetail nếu có
- **Order**: Link đến Order nếu có
- **Notes**: Ghi chú cho item

### Tiêu chí tìm kiếm và lọc
#### **Tìm kiếm text (global search)**
- WarehouseOut ID
- Customer Name
- Receiver Name
- Phone Number
- Product Name
- ProductItem SKU
- Tracking Number
- Address

#### **Bộ lọc (Filters)**
- **Out Date Range**: Date picker (from - to)
- **Customer**: Dropdown với search autocomplete
- **Status**: Multi-select (Pending, Completed, Cancelled)
- **Delivery Status**: Multi-select (Pending, InTransit, Delivered, Cancelled)
- **Warehouse**: Dropdown list warehouses
- **Location**: Cascade dropdown theo warehouse
- **Product Category**: Multi-select dropdown
- **Product Type**: Multi-select với color indicator
- **Order Status**: Filter theo trạng thái đơn hàng liên kết
- **Payment Status**: Filter theo trạng thái thanh toán
- **Has Tracking**: Checkbox (có tracking number)
- **Delivery Service**: Dropdown các dịch vụ vận chuyển
- **Value Range**: Number input (min - max total value)

### Export và báo cáo
- **Export Excel**: Danh sách warehouse outs với filtering
- **Export PDF**: Phiếu xuất kho để in
- **Delivery Report**: Báo cáo tình hình giao hàng
- **Inventory Impact Report**: Báo cáo ảnh hưởng tồn kho
- **Customer Shipping Report**: Báo cáo giao hàng theo khách hàng
- **Performance Analytics**: Phân tích hiệu suất xuất kho

---

## Payment Status Integration (UC-008-MO & UC-014-MOD)

### ENUM Payment Status Values (1-5)
| Value | Status | Vietnamese | Description |
|-------|--------|------------|-------------|
| 1 | Unpaid | Chưa thanh toán | Chưa có yêu cầu thanh toán |
| 2 | PaymentRequested | Yêu cầu thanh toán | Đã gửi yêu cầu thanh toán cho khách |
| 3 | Paid | Đã thanh toán | Khách hàng đã thanh toán |
| 4 | Processing | Đang xử lý | Đang xử lý thanh toán |
| 5 | PendingConfirmation | Chờ xác nhận | Chờ xác nhận thanh toán |

### Business Rules cho Payment Status
- **Xuất kho từ UC-014**: Chỉ OrderDetails có Payment_Status = Paid (3)
- **Status progression**: Unpaid → PaymentRequested → Processing/PendingConfirmation → Paid
- **Warehouse Out eligibility**: OrderDetails phải có Status = Invoiced AND Payment_Status = Paid
- **Auto-update**: Khi tạo WarehouseOut từ OrderDetails, có thể auto-update status → Delivering

---

## Quy tắc nghiệp vụ

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Chỉ hiển thị WarehouseOuts thuộc site hiện tại (site_id isolation)                 |
| BR-02 | Kiểm tra quyền **manage_inventory** trước khi cho phép tạo/cập nhật                  |
| BR-03 | **Kiểm tra available stock** (current_qty - reserved_qty) >= requested_qty          |
| BR-04 | **Tự động chọn location** dựa trên current_qty DESC                                  |
| BR-05 | **Multiple location allocation** nếu không đủ hàng tại 1 location                   |
| BR-06 | Out_date không được vượt quá ngày hiện tại                                          |
| BR-07 | Quantity phải là số nguyên dương (> 0)                                               |
| BR-08 | Unit_price phải >= 0                                                                 |
| BR-09 | **Cập nhật WarehouseInventory phải trong database transaction**                     |
| BR-10 | **current_qty -= qty khi tạo warehouse out**                                        |
| BR-11 | **current_qty += qty khi hủy warehouse out**                                        |
| BR-12 | **Chỉ hủy được phiếu xuất có status = Pending**                                     |
| BR-13 | **OrderDetails eligibility**: Status = Invoiced (9) AND Payment_Status = Paid          |
| BR-14 | **Auto-update OrderDetails**: Invoiced → Delivering khi xuất kho                    |
| BR-15 | **Auto-update Order status** khi tất cả OrderDetails → Delivering/Completed        |
| BR-16 | **Group by Customer** khi xuất kho từ multiple OrderDetails                         |
| BR-17 | **Site isolation**: chỉ select Customers, ProductItems thuộc site hiện tại         |
| BR-18 | **Audit trail**: Log đầy đủ mọi thay đổi inventory và order status                  |
| BR-19 | **Real-time inventory**: WarehouseInventory cập nhật tức thì                        |
| BR-20 | **Customer notification**: Thông báo khi hàng xuất kho và delivery status changes  |
| BR-21 | **Delivery tracking**: Tự động sync với delivery services nếu có integration       |
| BR-22 | **COD validation**: COD amount không vượt quá total order value                     |
| BR-23 | **Location capacity**: Cảnh báo nếu location inventory thấp sau khi xuất           |
| BR-24 | **Performance**: Batch operations cho multiple OrderDetails export                  |

---

## UI/UX Requirements

### Danh sách WarehouseOuts
- **Responsive table**: Mobile-friendly với collapse columns
- **Status badges**: Color-coded status và delivery status indicators
- **Quick actions**: Inline buttons cho View, Edit, Cancel, Track
- **Advanced filters**: Collapsible filter panel với real-time filtering
- **Bulk actions**: Multiple selection và bulk export
- **Sorting**: Multi-column sorting với saved preferences
- **Real-time updates**: WebSocket updates cho delivery status changes

### Tạo WarehouseOut
- **Smart wizard**: Context-aware steps tùy theo source (direct/from orders)
- **Product selection**: Advanced search với stock level indicators
- **Location visualization**: Visual representation of warehouse layout
- **Auto-allocation display**: Clear presentation of location allocation logic
- **Stock validation**: Real-time stock checking với availability indicators
- **Customer integration**: Auto-fill customer information từ orders
- **Preview mode**: Complete preview trước khi confirm

### Location Auto-Selection
- **Algorithm transparency**: Hiển thị logic chọn location
- **Manual override**: Dễ dàng thay đổi location allocation
- **Stock indicators**: Visual representation of stock levels at locations
- **Optimization suggestions**: Gợi ý tối ưu hóa việc chọn location
- **Multi-location handling**: Clear display when using multiple locations

### UC-014 Integration
- **Seamless transition**: Smooth workflow từ OrderDetails list
- **Bulk selection UI**: Clear indication of selected items
- **Customer grouping**: Visual grouping by customer
- **Eligibility indicators**: Clear marking của items eligible for export
- **Conflict resolution**: Handle conflicts in item selection

### Performance & UX
- **Loading states**: Skeleton screens và progress indicators
- **Real-time validation**: Immediate feedback on stock availability
- **Error recovery**: Graceful handling of concurrent stock updates
- **Offline mode**: Basic functionality khi network unstable
- **Mobile optimization**: Touch-friendly interface for warehouse staff
- **Barcode integration**: Support for barcode scanning devices
- **Print support**: Print-friendly formats cho warehouse documents

---

## Integration Points

### Với các Use Cases khác
- **UC-014-MOD**: Order Details filtering và bulk export to warehouse
- **UC-008-MO**: Order status updates và payment status integration
- **UC-010-MI**: Inventory management và stock level monitoring
- **UC-006-MP**: Product và ProductItem information
- **UC-004-MSW, UC-005-MWL**: Warehouse và Location management
- **UC-007-MC**: Customer information và shipping addresses

### Với External Systems
- **Delivery services**: Integration với Giao Hàng Nhanh, Giao Hàng Tiết Kiệm, etc.
- **Barcode scanners**: Mobile scanning cho warehouse operations
- **SMS/Email services**: Customer notifications về delivery status
- **ERP integration**: Sync với external accounting systems
- **Tracking APIs**: Real-time tracking updates từ carriers
- **Payment gateways**: Payment status verification

````
