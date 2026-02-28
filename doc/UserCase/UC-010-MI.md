# UC010: Manage Inventory & Pre-orders

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-010-MI                                  |
| Tên Use Case   | Quản lý kho hàng, xuất nhập tồn và đặt hàng trước |
| Actor          | SiteAdmin (người dùng có quyền hạn manage-inventory) |
| Mô tả          | Người dùng có thể quản lý việc nhập kho, xuất kho, theo dõi tồn kho và xử lý đặt hàng trước (pre-order) thuộc trang web mà họ sở hữu |
| Độ ưu tiên     | Cao                                        |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có quyền hạn **manage-inventory**<br>- Đã có warehouse, location và sản phẩm trong hệ thống<br>- WarehouseInventory table đã được thiết lập |
| Post-condition | Giao dịch nhập/xuất kho được ghi nhận, tồn kho được cập nhật chính xác, và pre-order được xử lý tự động |

---

## Luồng chính (Main Flow)

### Luồng nhập kho (Warehouse Receipt)

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | SiteAdmin  | Đăng nhập vào hệ thống |
| 2    | Hệ thống   | Kiểm tra vai trò và quyền sở hữu trang web |
| 3    | SiteAdmin  | Truy cập **"Quản lý kho hàng"** → **"Nhập kho"** |
| 4    | Hệ thống   | Hiển thị form tạo phiếu nhập kho |
| 5    | SiteAdmin  | Chọn ngày nhập kho |
| 6    | SiteAdmin  | Thêm sản phẩm vào phiếu nhập |
| 7    | SiteAdmin  | Chọn vị trí lưu trữ cho từng sản phẩm |
| 8    | SiteAdmin  | Nhập số lượng và giá nhập |
| 9    | SiteAdmin  | Nhập ghi chú (nếu có) |
| 10   | SiteAdmin  | Nhấn nút **"Lưu phiếu nhập"** |
| 11   | Hệ thống   | Tạo WarehouseReceipt và WarehouseReceiptDetails |
| 12   | Hệ thống   | **Liên kết với PurchaseRequestDetail** nếu nhập từ Purchase Request |
| 13   | Hệ thống   | **Cập nhật WarehouseInventory table** với số lượng nhập |
| 14   | Hệ thống   | Tính toán lại avg_cost cho product tại location |
| 15   | Hệ thống   | **Cập nhật received_qty** trong PurchaseRequestDetail (nếu có) |
| 16   | Hệ thống   | Ghi log activity |
| 17   | Hệ thống   | Thông báo thành công |

### Luồng xuất kho (Warehouse Out)

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 16   | SiteAdmin  | Truy cập **"Quản lý kho hàng"** → **"Xuất kho"** |
| 17   | Hệ thống   | Hiển thị form tạo phiếu xuất kho |
| 18   | SiteAdmin  | Chọn loại xuất kho (Bán hàng, Chuyển kho, Khác) |
| 19   | SiteAdmin  | Nhập thông tin người nhận |
| 20   | SiteAdmin  | Chọn đơn hàng liên kết (nếu là bán hàng) |
| 21   | SiteAdmin  | Thêm sản phẩm và chọn vị trí xuất |
| 22   | SiteAdmin  | Nhập số lượng xuất |
| 23   | Hệ thống   | Kiểm tra số lượng tồn kho |
| 24   | SiteAdmin  | Nhập thông tin vận chuyển |
| 25   | SiteAdmin  | Nhấn nút **"Tạo phiếu xuất"** |
| 26   | Hệ thống   | Tạo WarehouseOut và WarehouseOutDetails |
| 27   | Hệ thống   | **Cập nhật WarehouseInventory table** - trừ current_qty |
| 28   | Hệ thống   | Cập nhật reserved_qty nếu liên quan đến đơn hàng |
| 29   | Hệ thống   | Cập nhật trạng thái đơn hàng (nếu có) |
| 30   | Hệ thống   | Ghi log activity |

### Luồng kiểm tra tồn kho

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 31   | SiteAdmin  | Truy cập **"Báo cáo tồn kho"** |
| 32   | Hệ thống   | **Query WarehouseInventory table** để hiển thị tồn kho real-time |
| 33   | SiteAdmin  | Lọc theo warehouse, location hoặc sản phẩm |
| 34   | Hệ thống   | Hiển thị báo cáo với current_qty, reserved_qty, pre_order_qty, available_qty |
| 35   | SiteAdmin  | Xuất báo cáo Excel (nếu cần) |

### Luồng xử lý đặt hàng trước (Pre-order Management)

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 36   | Hệ thống   | Khách hàng đặt hàng sản phẩm chưa có trong kho |
| 37   | Hệ thống   | Kiểm tra **WarehouseInventory.available_qty** |
| 38   | Hệ thống   | Nếu không đủ hàng, tạo pre-order: cập nhật **pre_order_qty** |
| 39   | Hệ thống   | Cập nhật **OrderDetails.fulfillment_status = PRE_ORDER** |
| 40   | Hệ thống   | Ghi nhận **expected_fulfillment_date** dựa trên estimation |
| 41   | SiteAdmin  | **Thủ công** xem báo cáo pre-order và lập kế hoạch mua hàng |
| 42   | SiteAdmin  | **Thủ công** tạo Purchase Request dựa trên Order và OrderDetail |
| 43   | SiteAdmin  | Xử lý mua hàng từ supplier |
| 44   | SiteAdmin  | Nhập hàng vào kho (WarehouseReceipt) |
| 45   | Hệ thống   | **Auto-fulfill pre-orders**: chuyển từ pre_order_qty sang reserved_qty |
| 46   | Hệ thống   | Cập nhật **OrderDetails.fulfillment_status = READY_TO_SHIP** |
| 47   | Hệ thống   | Gửi thông báo cho khách hàng về việc hàng đã sẵn sàng |

### Luồng báo cáo Pre-order

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 48   | SiteAdmin  | Truy cập **"Báo cáo Pre-order"** |
| 49   | Hệ thống   | Hiển thị danh sách sản phẩm có pre-order |
| 50   | Hệ thống   | Hiển thị tổng pre_order_qty theo sản phẩm |
| 51   | SiteAdmin  | Xem chi tiết customers đã pre-order |
| 52   | SiteAdmin  | Xem danh sách Orders và OrderDetails có trạng thái PRE_ORDER |
| 53   | SiteAdmin  | **Thủ công** lập kế hoạch mua hàng dựa trên pre-order demand |
| 54   | SiteAdmin  | **Thủ công** tạo Purchase Request cho từng supplier |
| 55   | SiteAdmin  | Theo dõi trạng thái Purchase Request và delivery timeline |

### Luồng tạo Purchase Request

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 56   | SiteAdmin  | Truy cập **"Tạo Purchase Request"** |
| 57   | SiteAdmin  | Chọn supplier từ danh sách |
| 58   | SiteAdmin  | Thêm OrderDetails cần mua hàng vào Purchase Request |
| 59   | SiteAdmin  | Nhập số lượng cần mua cho từng ProductItem |
| 60   | SiteAdmin  | Nhập giá và ngày giao hàng dự kiến |
| 61   | SiteAdmin  | Tạo PurchaseRequestDetails cho từng sản phẩm |
| 62   | Hệ thống   | Liên kết OrderDetails với PurchaseRequestDetails (purchase_request_detail_id) |
| 63   | SiteAdmin  | Gửi Purchase Request cho supplier |
| 64   | Hệ thống   | Cập nhật trạng thái Purchase Request = "Đã gửi" |

### Luồng theo dõi Purchase Request

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 65   | SiteAdmin  | Truy cập **"Quản lý Purchase Requests"** |
| 66   | Hệ thống   | Hiển thị danh sách Purchase Requests theo trạng thái |
| 67   | SiteAdmin  | Cập nhật trạng thái khi nhận response từ supplier |
| 68   | SiteAdmin  | Cập nhật ngày giao hàng thực tế khi hàng về |
| 69   | Hệ thống   | Tự động cập nhật OrderDetails status khi hàng về |

### Luồng nhập kho từ Purchase Request

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 70   | SiteAdmin  | Supplier giao hàng (có thể giao từng phần) |
| 71   | SiteAdmin  | Tạo phiếu nhập kho cho số lượng thực tế nhận được |
| 72   | SiteAdmin  | **Liên kết WarehouseReceiptDetail với PurchaseRequestDetail** |
| 73   | Hệ thống   | Cập nhật **received_qty** trong PurchaseRequestDetail |
| 74   | Hệ thống   | Kiểm tra **received_qty vs requested_qty** |
| 75   | Hệ thống   | Nếu đủ hàng: **Auto-fulfill pre-orders** từ WarehouseInventory |
| 76   | Hệ thống   | Cập nhật **PurchaseRequest status** (Partially Delivered/Delivered) |
| 77   | Hệ thống   | Thông báo khách hàng có pre-order rằng hàng đã về |

### Luồng theo dõi tiến độ giao hàng

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 78   | SiteAdmin  | Xem **"Purchase Request Progress"** |
| 79   | Hệ thống   | Hiển thị **requested_qty vs received_qty** cho từng PurchaseRequestDetail |
| 80   | Hệ thống   | Hiển thị danh sách **WarehouseReceiptDetails** liên quan |
| 81   | SiteAdmin  | Theo dõi những đơn hàng nào đã được fulfill |
| 82   | SiteAdmin  | Xem những khách hàng nào còn đang chờ hàng |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Không có quyền **manage-inventory** | Không hiển thị menu quản lý kho hàng |
| AF-02| Số lượng xuất > available_qty | Hiển thị lỗi và không cho phép |
| AF-03| Location không thuộc site    | Không cho phép chọn location |
| AF-04| Sản phẩm không thuộc site    | Không cho phép chọn sản phẩm |
| AF-05| Lỗi cập nhật tồn kho         | Rollback transaction và thông báo lỗi |
| AF-06| Đặt hàng nhưng không đủ stock | Tự động chuyển sang pre-order mode |
| AF-07| Pre-order nhưng không có supplier | Thông báo cần thiết lập supplier trước |
| AF-08| Hủy pre-order               | Giảm pre_order_qty, cập nhật OrderDetails status |
| AF-09| Nhập hàng nhưng không có pre-order | Chỉ cập nhật current_qty |
| AF-10| Auto-fulfill pre-order thất bại | Ghi log và thông báo admin |
| AF-11| SiteAdmin quên tạo Purchase Request | Pre-order vẫn ở trạng thái chờ, hiển thị trong báo cáo |
| AF-12| Supplier từ chối Purchase Request | Cập nhật trạng thái = "Từ chối", tìm supplier khác |
| AF-13| Hàng về không đúng số lượng | Cập nhật received_qty khác requested_qty |
| AF-14| Một PurchaseRequestDetail cho nhiều OrderDetail | Phân bổ hàng về theo tỷ lệ hoặc FIFO |
| AF-15| Hủy Purchase Request đã gửi | Thông báo supplier và cập nhật OrderDetails |
| AF-16| Giao hàng từng phần nhiều lần | Tạo nhiều WarehouseReceiptDetails cho 1 PurchaseRequestDetail |
| AF-17| Nhập kho không liên kết Purchase Request | WarehouseReceiptDetail.purchase_request_detail_id = NULL |
| AF-18| Supplier giao thừa hàng | Cập nhật received_qty > requested_qty, tạo tồn kho dự phòng |

---

## Dữ liệu vào / ra

### Phiếu nhập kho (WarehouseReceipt)
- **Ngày nhập** (receipt_date): DateTime, bắt buộc
- **Ghi chú** (note): Text, tùy chọn

### Chi tiết nhập kho (WarehouseReceiptDetails)
- **Sản phẩm** (product_item_id): ID sản phẩm, bắt buộc
- **Vị trí** (location_id): ID location, bắt buộc
- **Order Detail ID** (order_detail_id): ID chi tiết đơn hàng, tùy chọn
- **Purchase Request Detail ID** (purchase_request_detail_id): ID yêu cầu mua hàng, tùy chọn
- **Số lượng** (qty): Integer, > 0
- **Giá nhập** (purchase_price): Decimal, > 0
- **Phí phát sinh** (fee_price): Decimal, tùy chọn
- **Ghi chú** (note): Text, tùy chọn

### Phiếu xuất kho (WarehouseOut)
- **Ngày xuất** (out_date): DateTime, bắt buộc
- **Người nhận** (receiver): Chuỗi, bắt buộc
- **Địa chỉ nhận** (address): Chuỗi, bắt buộc
- **SĐT người nhận** (phone): Chuỗi, bắt buộc
- **Tổng số lượng** (qty): Integer, tự động tính
- **Tổng giá trị** (total_price): Decimal, tự động tính
- **Trạng thái** (status): Enum (Chờ xử lý, Đã xuất, Đã giao)
- **Loại vận chuyển** (type_of_transport): Enum
- **Người thanh toán VC** (shipping_payer): Enum

### Purchase Request (Yêu cầu mua hàng)
- **Số Purchase Request** (purchase_number): String, tự động generate, unique
- **Supplier** (supplier_id): ID supplier, bắt buộc
- **Ngày tạo** (request_date): DateTime, mặc định hiện tại
- **Ngày giao hàng dự kiến** (expected_delivery_date): Date, tùy chọn
- **Ngày giao hàng thực tế** (actual_delivery_date): Date, tùy chọn
- **Tổng tiền** (total_amount): Decimal, tự động tính từ details
- **Trạng thái** (status): Enum (Draft, Sent, Confirmed, Delivered, Cancelled)
- **Ghi chú** (notes): Text, tùy chọn
- **Phản hồi từ supplier** (supplier_response): Text, tùy chọn

### Purchase Request Details (Chi tiết yêu cầu mua hàng)
- **Purchase Request ID** (purchase_request_id): ID yêu cầu, bắt buộc
- **Product Item** (product_item_id): ID sản phẩm, bắt buộc
- **Số lượng yêu cầu** (requested_qty): Integer, bắt buộc, > 0
- **Số lượng nhận được** (received_qty): Integer, mặc định 0
- **Giá đơn vị** (unit_price): Decimal, bắt buộc, > 0
- **Tổng tiền** (total_price): Decimal, tự động tính = requested_qty × unit_price
- **Ghi chú** (notes): Text, tùy chọn

---

## Báo cáo và thống kê

### Báo cáo tồn kho
- Tồn kho theo sản phẩm
- Tồn kho theo location
- Tồn kho theo warehouse
- Sản phẩm sắp hết hàng
- Sản phẩm tồn kho lâu

### Báo cáo xuất nhập
- Lịch sử nhập kho theo thời gian
- Lịch sử xuất kho theo thời gian
- Thống kê nhập/xuất theo sản phẩm
- Giá trị nhập/xuất theo tháng

---

## Tính năng WarehouseInventory (Nâng cao)

### Quản lý Inventory theo Location-Product
- **Real-time Inventory**: Tồn kho real-time theo từng location và product
- **Reserved Quantity**: Quản lý số lượng đã được đặt hàng và có sẵn hàng
- **Pre-order Quantity**: Quản lý số lượng đặt trước cho hàng chưa có sẵn
- **Available Quantity**: Số lượng có thể bán ngay = current_qty - reserved_qty
- **Average Cost**: Tự động tính giá vốn trung bình

### Pre-order Management System
- **Auto Pre-order Creation**: Tự động tạo pre-order khi không đủ hàng
- **Manual Purchase Planning**: SiteAdmin thủ công tạo Purchase Request dựa trên Orders và OrderDetails
- **Auto-fulfillment**: Tự động fulfill pre-order khi hàng về
- **Customer Notifications**: Thông báo khách hàng khi hàng sẵn sàng
- **Demand Forecasting**: Dự báo nhu cầu dựa trên pre-order
- **Purchase Tracking**: Theo dõi các Purchase Request đã tạo thủ công

### Tính năng nâng cao
- **Low Stock Alerts**: Cảnh báo tồn kho thấp theo location
- **High Demand Alerts**: Cảnh báo sản phẩm có nhiều pre-order
- **Multi-Location Reports**: Báo cáo tồn kho across multiple locations
- **Inventory Transfers**: Chuyển kho giữa các location
- **Stock Reservations**: Đặt trước hàng cho đơn hàng
- **Purchase Planning**: Lập kế hoạch mua hàng dựa trên pre-order demand

### Performance Benefits
- **Fast Queries**: Query trực tiếp từ WarehouseInventory table
- **No Complex Calculations**: Không cần SUM transaction tables
- **Location-Based Filtering**: Lọc nhanh theo warehouse/location
- **Real-time Updates**: Cập nhật tức thì khi có giao dịch
- **Automated Workflows**: Tự động hóa quy trình pre-order

---

## Quy tắc nghiệp vụ

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Không được xuất số lượng lớn hơn available_qty (current_qty - reserved_qty)          |
| BR-02 | Mọi giao dịch nhập/xuất phải có ghi log đầy đủ                                       |
| BR-03 | Cập nhật WarehouseInventory phải được thực hiện trong database transaction           |
| BR-04 | Chỉ cho phép nhập/xuất sản phẩm thuộc site hiện tại                                  |
| BR-05 | Chỉ cho phép chọn location thuộc warehouse của site                                   |
| BR-06 | Số lượng nhập/xuất phải là số nguyên dương                                           |
| BR-07 | Giá nhập phải lớn hơn 0                                                               |
| BR-08 | **WarehouseInventory.current_qty** là single source of truth cho tồn kho             |
| BR-09 | **Reserved_qty** chỉ được cập nhật khi tạo/hủy đơn hàng có hàng sẵn                  |
| BR-10 | **Pre_order_qty** chỉ được cập nhật khi tạo/hủy pre-order                            |
| BR-11 | **Avg_cost** được tính lại mỗi khi có nhập kho                                       |
| BR-12 | **Unique constraint**: (product_item_id, location_id, site_id) phải unique           |
| BR-13 | Khi nhập hàng, tự động fulfill pre-order theo thứ tự FIFO                           |
| BR-14 | Pre-order chỉ được tạo khi available_qty < requested_qty                             |
| BR-15 | Customer phải được thông báo khi pre-order status thay đổi                           |
| BR-16 | **Purchase Request phải được tạo thủ công** dựa trên Orders và OrderDetails         |
| BR-17 | SiteAdmin có trách nhiệm theo dõi pre-order reports và lập kế hoạch mua hàng        |
| BR-18 | Expected_fulfillment_date được estimate dựa trên supplier lead time trung bình      |
| BR-19 | **Purchase Request Number** tự động generate theo format: PR-{timestamp}            |
| BR-20 | Một PurchaseRequestDetail có thể liên kết với nhiều OrderDetails                    |
| BR-21 | OrderDetails.purchase_request_detail_id là optional, chỉ set khi có purchase request |
| BR-22 | Khi hàng về, tự động cập nhật received_qty và OrderDetails status                   |
| BR-23 | Total_amount của PurchaseRequest = SUM(PurchaseRequestDetails.total_price)          |
| BR-24 | Chỉ có thể xóa PurchaseRequest ở trạng thái Draft                                   |
| BR-25 | **WarehouseReceiptDetails có thể liên kết với PurchaseRequestDetail** (tùy chọn)    |
| BR-26 | Một PurchaseRequestDetail có thể có **nhiều WarehouseReceiptDetails** (giao từng phần) |
| BR-27 | **received_qty** = SUM(WarehouseReceiptDetails.qty) cho PurchaseRequestDetail đó     |
| BR-28 | Khi received_qty >= requested_qty, PurchaseRequestDetail = "Hoàn thành"              |
| BR-29 | WarehouseReceiptDetail.purchase_request_detail_id **không bắt buộc**                |
