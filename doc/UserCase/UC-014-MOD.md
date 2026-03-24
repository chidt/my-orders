````markdown
# UC014: Manage Order Details

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-014-MOD                                 |
| Tên Use Case   | Quản lý chi tiết đơn hàng                  |
| Actor          | SiteAdmin (người dùng có quyền hạn manage_orders) |
| Mô tả          | Người dùng có thể xem, tìm kiếm, lọc và cập nhật trạng thái chi tiết đơn hàng (OrderDetails) thuộc trang web mà họ sở hữu |
| Độ ưu tiên     | Cao                                        |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có quyền hạn **manage_orders**<br>- Đã có đơn hàng và chi tiết đơn hàng trong hệ thống |
| Post-condition | Chi tiết đơn hàng được xem/cập nhật thành công và thuộc về trang web hiện tại |

---

## Luồng chính (Main Flow)

### Luồng xem danh sách OrderDetails

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | SiteAdmin  | Đăng nhập vào hệ thống |
| 2    | Hệ thống   | Kiểm tra vai trò và quyền sở hữu trang web |
| 3    | Hệ thống   | Hiển thị **"Chi tiết đơn hàng"** trong menu sidebar |
| 4    | SiteAdmin  | Nhấn vào **"Chi tiết đơn hàng"** |
| 5    | Hệ thống   | Hiển thị danh sách OrderDetails thuộc trang web hiện tại |
| 6    | Hệ thống   | Hiển thị các cột: Order Number, Customer, Product, ProductItem, ProductType, Quantity, Price, Status, Order Date |
| 7    | Hệ thống   | Hiển thị bộ lọc và tìm kiếm |

### Luồng tìm kiếm và lọc OrderDetails

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 8    | SiteAdmin  | Nhập từ khóa tìm kiếm (Order Number, Customer Name, Product Name) |
| 9    | Hệ thống   | Tìm kiếm theo từ khóa trong các trường liên quan |
| 10   | SiteAdmin  | Chọn lọc theo **Customer** (dropdown list) |
| 11   | SiteAdmin  | Chọn lọc theo **Product** (dropdown với search) |
| 12   | SiteAdmin  | Chọn lọc theo **ProductItem** (dropdown phụ thuộc Product) |
| 13   | SiteAdmin  | Chọn lọc theo **ProductType** (dropdown list) |
| 14   | SiteAdmin  | Chọn lọc theo **Status** (checkbox multiple selection) |
| 15   | SiteAdmin  | Chọn lọc theo **Order Date Range** (date picker) |
| 16   | SiteAdmin  | Nhấn nút **"Áp dụng bộ lọc"** |
| 17   | Hệ thống   | Hiển thị kết quả lọc với pagination |
| 18   | Hệ thống   | Hiển thị tổng số lượng kết quả và thống kê theo trạng thái |

### Luồng xem chi tiết OrderDetail

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 19   | SiteAdmin  | Nhấn vào một OrderDetail để xem chi tiết |
| 20   | Hệ thống   | Hiển thị modal/page chi tiết OrderDetail |
| 21   | Hệ thống   | Hiển thị thông tin: Order info, Customer info, Product details, Pricing, Status history, Notes |
| 22   | Hệ thống   | Hiển thị nút **"Cập nhật trạng thái"** nếu có quyền |

### Luồng cập nhật trạng thái OrderDetail

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 23   | SiteAdmin  | Nhấn nút **"Cập nhật trạng thái"** |
| 24   | Hệ thống   | Hiển thị dropdown trạng thái (chỉ các trạng thái hợp lệ) |
| 25   | SiteAdmin  | Chọn trạng thái mới |
| 26   | SiteAdmin  | Nhập ghi chú thay đổi (tùy chọn) |
| 27   | SiteAdmin  | Nhấn nút **"Xác nhận cập nhật"** |
| 28   | Hệ thống   | Validate trạng thái transition hợp lệ |
| 29   | Hệ thống   | Cập nhật trạng thái OrderDetail |
| 30   | Hệ thống   | **Tự động tính toán và cập nhật Order status** theo quy tắc đồng bộ |
| 31   | Hệ thống   | Cập nhật WarehouseInventory nếu cần thiết |
| 32   | Hệ thống   | Ghi log thay đổi trạng thái |
| 33   | Hệ thống   | Thông báo cập nhật thành công |
| 34   | Hệ thống   | Refresh danh sách để hiển thị thay đổi |

### Luồng bulk update trạng thái

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 35   | SiteAdmin  | Chọn multiple OrderDetails bằng checkbox |
| 36   | SiteAdmin  | Nhấn nút **"Cập nhật hàng loạt"** |
| 37   | Hệ thống   | Hiển thị modal bulk update |
| 38   | SiteAdmin  | Chọn trạng thái mới cho tất cả items được chọn |
| 39   | SiteAdmin  | Nhập ghi chú chung |
| 40   | SiteAdmin  | Nhấn nút **"Xác nhận cập nhật hàng loạt"** |
| 41   | Hệ thống   | Validate từng OrderDetail có thể chuyển trạng thái |
| 42   | Hệ thống   | Cập nhật từng OrderDetail hợp lệ |
| 43   | Hệ thống   | **Tự động cập nhật Order status** cho các đơn hàng bị ảnh hưởng |
| 44   | Hệ thống   | Hiển thị báo cáo kết quả: Success/Failed với lý do |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Không có quyền **manage_orders** | Không hiển thị menu Chi tiết đơn hàng |
| AF-02| OrderDetail status = Completed (10) hoặc Cancelled (11) | Chỉ cho phép xem, không cập nhật |
| AF-03| Chuyển trạng thái không hợp lệ | Hiển thị lỗi và danh sách trạng thái được phép |
| AF-04| Không có kết quả tìm kiếm/lọc | Hiển thị thông báo "Không có dữ liệu" |
| AF-05| Lỗi cập nhật trạng thái | Hiển thị lỗi chi tiết và rollback |
| AF-06| Bulk update một phần thất bại | Hiển thị báo cáo chi tiết success/failed |
| AF-07| OrderDetail thuộc Order đã Completed/Cancelled | Không cho phép cập nhật |
| AF-08| Cập nhật đồng thời (concurrent update) | Hiển thị cảnh báo conflict và yêu cầu refresh |

---

## Dữ liệu hiển thị và tìm kiếm

### Dữ liệu hiển thị trong danh sách
- **ID**: OrderDetail ID
- **Order Number**: Số đơn hàng (link đến Order detail)
- **Order Date**: Ngày đơn hàng
- **Customer**: Tên khách hàng
- **Product**: Tên sản phẩm
- **ProductItem**: SKU/Tên variant
- **ProductType**: Loại sản phẩm (với color coding)
- **Quantity**: Số lượng
- **Price**: Giá bán
- **Discount**: Chiết khấu
- **Total**: Thành tiền
- **Status**: Trạng thái hiện tại (với badge màu)
- **Last Updated**: Thời gian cập nhật cuối

### Tiêu chí tìm kiếm và lọc
#### **Tìm kiếm text (global search)**
- Order Number
- Customer Name
- Product Name
- ProductItem SKU/Name
- Order Note

#### **Bộ lọc (Filters)**
- **Customer**: Dropdown với search autocomplete
- **Product**: Dropdown với search autocomplete
- **ProductItem**: Cascade dropdown phụ thuộc Product
- **ProductType**: Multi-select dropdown với color indicator
- **Status**: Multi-select với badge preview
- **Order Date Range**: Date picker (from - to)
- **Price Range**: Number input (min - max)
- **Quantity Range**: Number input (min - max)

### Export và báo cáo
- **Export Excel**: Xuất kết quả lọc ra Excel
- **Export PDF**: Báo cáo PDF với charts
- **Quick Stats**: Thống kê nhanh theo trạng thái, khách hàng, sản phẩm

---

## Trạng thái và quy tắc chuyển đổi

### ENUM Status Values (1-11) - Giống UC-008-MO
| Value | Status | Vietnamese | Transition Rules |
|-------|--------|------------|------------------|
| 1 | New | Tạo mới | → 2, 3, 11 |
| 2 | Processing | Đang xử lý | → 3, 4, 11 |
| 3 | ClosingOrder | Chốt đơn | → 4, 11 |
| 4 | AddToCart | Thêm giỏ hàng | → 5, 11 |
| 5 | Ordered | Đã order | → 6, 7, 11 |
| 6 | WaitingForStock | Chờ nhập kho | → 7, 11 |
| 7 | Arrived | Hàng về | → 8, 11 |
| 8 | Invoiced | Đã báo đơn | → 9, 11 |
| 9 | Delivering | Đang giao hàng | → 10, 11 |
| 10 | Completed | Hoàn thành | No transition (final) |
| 11 | Cancelled | Huỷ | No transition (final) |

### Business Rules cho Status Transition
- **Forward only**: Chỉ cho phép chuyển tiến (trừ Cancelled)
- **Skip allowed**: Có thể bỏ qua một số trạng thái trung gian
- **Inventory impact**: Status 5, 9, 11 ảnh hưởng đến WarehouseInventory
- **Final status**: Status 10, 11 không thể thay đổi

---

## Quy tắc nghiệp vụ

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Chỉ hiển thị OrderDetails thuộc site hiện tại (site_id isolation)                   |
| BR-02 | Kiểm tra quyền **manage_orders** trước khi cho phép cập nhật                         |
| BR-03 | Status transition phải tuân theo quy tắc chuyển đổi hợp lệ                          |
| BR-04 | Tự động cập nhật Order status khi OrderDetail status thay đổi                       |
| BR-05 | Ghi log chi tiết mọi thay đổi trạng thái (ai, khi nào, từ trạng thái nào sang trạng thái nào) |
| BR-06 | Cập nhật WarehouseInventory khi OrderDetail chuyển sang trạng thái ảnh hưởng stock  |
| BR-07 | Không cho phép cập nhật OrderDetail khi status = 10 (Completed) hoặc 11 (Cancelled)|
| BR-08 | Bulk update chỉ áp dụng cho các OrderDetail có thể chuyển trạng thái hợp lệ         |
| BR-09 | Hiển thị warning khi cập nhật OrderDetail có thể ảnh hưởng đến Order status        |
| BR-10 | Pagination và lazy loading cho danh sách lớn (>1000 items)                         |
| BR-11 | Cache kết quả lọc trong session để improve performance                               |
| BR-12 | Real-time update khi có thay đổi từ user khác (WebSocket/Server-Sent Events)        |

---

## UI/UX Requirements

### Danh sách OrderDetails
- **Responsive table**: Mobile-friendly với collapse columns
- **Sticky header**: Header cố định khi scroll
- **Status badges**: Color-coded status indicators
- **Quick actions**: Inline buttons cho common actions
- **Bulk selection**: Checkbox với select all/none
- **Sorting**: Click column header để sort
- **Advanced filters**: Collapsible filter panel

### Chi tiết OrderDetail
- **Modal/Side panel**: Không reload trang
- **Status timeline**: Visual timeline của status changes
- **Related info**: Links đến Order, Customer, Product
- **Audit log**: Lịch sử thay đổi với user và timestamp
- **Quick update**: Dropdown status với shortcut keys

### Performance & UX
- **Loading states**: Skeleton screens, progress bars
- **Error handling**: Friendly error messages với actions
- **Keyboard shortcuts**: Alt+N (New), Ctrl+F (Filter), etc.
- **Auto-save**: Tự động lưu filter preferences
- **Breadcrumbs**: Navigation trail
- **Help tooltips**: Context-sensitive help

````
