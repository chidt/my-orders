# UC008: Manage Orders

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-008-MO                                  |
| Tên Use Case   | Quản lý đơn hàng                           |
| Actor          | SiteAdmin (người dùng có quyền hạn manage-orders) |
| Mô tả          | Người dùng có thể tạo, xem, cập nhật trạng thái đơn hàng thuộc trang web mà họ sở hữu |
| Độ ưu tiên     | Cao                                        |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có quyền hạn **manage-orders**<br>- Đã có khách hàng và sản phẩm trong hệ thống |
| Post-condition | Đơn hàng được tạo/cập nhật thành công và thuộc về trang web hiện tại |

---

## Luồng chính (Main Flow)

### Luồng xem danh sách đơn hàng

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | SiteAdmin  | Đăng nhập vào hệ thống |
| 2    | Hệ thống   | Kiểm tra vai trò và quyền sở hữu trang web |
| 3    | Hệ thống   | Hiển thị **"Quản lý đơn hàng"** trong menu sidebar |
| 4    | SiteAdmin  | Nhấn vào **"Quản lý đơn hàng"** |
| 5    | Hệ thống   | Hiển thị danh sách đơn hàng thuộc trang web hiện tại |
| 6    | Hệ thống   | Hiển thị bộ lọc theo trạng thái, ngày, khách hàng |
| 7    | Hệ thống   | Hiển thị nút **"Tạo đơn hàng mới"** |

### Luồng tạo đơn hàng mới

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 8    | SiteAdmin  | Nhấn nút **"Tạo đơn hàng mới"** |
| 9    | Hệ thống   | Hiển thị form tạo đơn hàng |
| 10   | SiteAdmin  | Chọn khách hàng |
| 11   | SiteAdmin  | Chọn địa chỉ giao hàng |
| 12   | SiteAdmin  | Thêm sản phẩm vào đơn hàng |
| 13   | SiteAdmin  | Nhập số lượng và kiểm tra tồn kho |
| 14   | Hệ thống   | Tự động tính giá và tổng tiền |
| 15   | SiteAdmin  | Chọn phương thức thanh toán |
| 16   | SiteAdmin  | Nhập ghi chú đơn hàng |
| 17   | SiteAdmin  | Nhấn nút **"Tạo đơn hàng"** |
| 18   | Hệ thống   | Tạo số đơn hàng tự động |
| 19   | Hệ thống   | **Kiểm tra WarehouseInventory.available_qty** cho từng sản phẩm |
| 20   | Hệ thống   | Tạo đơn hàng với **status = New (1)** |
| 21   | Hệ thống   | Tạo OrderDetails với **status = New (1)** cho từng sản phẩm |
| 22   | Hệ thống   | **Nếu đủ hàng**: Reserve stock (cập nhật reserved_qty) |
| 23   | Hệ thống   | **Nếu không đủ hàng**: Tạo pre-order (cập nhật pre_order_qty) |
| 24   | Hệ thống   | Gán site_id cho đơn hàng và OrderDetails |
| 25   | Hệ thống   | **Tự động cập nhật Order status** dựa trên OrderDetails status |
| 26   | Hệ thống   | Thông báo thành công |

### Luồng cập nhật trạng thái đơn hàng

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 27   | SiteAdmin  | Nhấn vào một đơn hàng để xem chi tiết |
| 28   | Hệ thống   | Hiển thị thông tin chi tiết đơn hàng và OrderDetails |
| 29   | SiteAdmin  | **Cập nhật trạng thái OrderDetail** (có thể cập nhật từng item riêng lẻ) |
| 30   | SiteAdmin  | Nhập ghi chú thay đổi |
| 31   | SiteAdmin  | Nhấn nút **"Cập nhật"** |
| 32   | Hệ thống   | Cập nhật trạng thái OrderDetail |
| 33   | Hệ thống   | **Tự động tính toán và cập nhật Order status** dựa trên quy tắc: |
| 34   | Hệ thống   | - Nếu tất cả OrderDetails cùng status → Order = status đó |
| 35   | Hệ thống   | - Nếu OrderDetails khác status (trừ Cancelled) → Order = Processing |
| 36   | Hệ thống   | - Nếu tất cả OrderDetails = Cancelled → Order = Cancelled |
| 37   | Hệ thống   | Lưu thay đổi và ghi log |
| 38   | Hệ thống   | Gửi thông báo cho khách hàng (nếu có) |

### Luồng xử lý tồn kho theo trạng thái

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 39   | Hệ thống   | **Khi OrderDetail = Ordered (5)**: Reserve stock nếu chưa reserve |
| 40   | Hệ thống   | **Khi OrderDetail = WaitingForStock (6)**: Tạo pre-order nếu không đủ hàng |
| 41   | Hệ thống   | **Khi OrderDetail = Delivering (9)**: Trừ stock thực tế từ WarehouseInventory |
| 42   | Hệ thống   | **Khi OrderDetail = Cancelled (11)**: Release reserved stock |
| 43   | Hệ thống   | Cập nhật WarehouseInventory tương ứng |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Không có quyền **manage-orders** | Không hiển thị menu quản lý đơn hàng |
| AF-02| Sản phẩm không đủ hàng (available_qty < qty) | Tự động tạo pre-order, OrderDetail = WaitingForStock (6) |
| AF-03| Khách hàng chưa có địa chỉ   | Yêu cầu tạo địa chỉ trước     |
| AF-04| OrderDetail status = Completed (10) hoặc Cancelled (11) | Không cho phép chỉnh sửa |
| AF-05| Cập nhật trạng thái không hợp lệ | Hiển thị lỗi và rollback |
| AF-06| Order đã = Completed (10) hoặc Cancelled (11) | Chỉ cho phép xem, không chỉnh sửa |
| AF-07| Hủy OrderDetail khi đã Delivering (9) | Cần hoàn trả stock và xử lý đặc biệt |
| AF-08| Tất cả OrderDetails = Cancelled (11) | Order tự động = Cancelled (11) |

---

## Dữ liệu vào / ra

### Dữ liệu vào (Input)
- **Khách hàng** (customer_id): ID khách hàng, bắt buộc
- **Địa chỉ giao hàng** (shipping_address_id): ID địa chỉ, bắt buộc
- **Ngày đơn hàng** (order_date): DateTime, mặc định hiện tại
- **Loại khách hàng** (customer_type): Enum, tự động từ khách hàng
- **Kênh bán hàng** (sale_channel): Enum (Online, Offline, Phone), bắt buộc
- **Người thanh toán vận chuyển** (shipping_payer): Enum (Người bán, Người mua)
- **Ghi chú giao hàng** (shipping_note): Text, tùy chọn
- **Ghi chú đơn hàng** (order_note): Text, tùy chọn

### Chi tiết đơn hàng (OrderDetails)
- **Sản phẩm** (product_item_id): ID sản phẩm, bắt buộc
- **Số lượng** (qty): Integer, > 0
- **Giá bán** (price): Decimal, tự động từ sản phẩm
- **Chiết khấu** (discount): Decimal, tùy chọn
- **Phí bổ sung** (addition_price): Decimal, tùy chọn
- **Tổng tiền** (total): Decimal, tự động tính

### Dữ liệu ra (Output)
- **Số đơn hàng** (order_number): Tự động generate
- **Tổng tiền đơn hàng**: Tổng của tất cả OrderDetails
- **Trạng thái đơn hàng**: Mặc định "Chờ xác nhận"

---

## Trạng thái đơn hàng và chi tiết đơn hàng

### ENUM Status Values (1-11)
| Value | Status | Vietnamese | Mô tả | Hành động cho phép |
|-------|--------|------------|-------|-------------------|
| 1 | New | Tạo mới | Đơn hàng mới được tạo | Chỉnh sửa, hủy |
| 2 | Processing | Đang xử lý | Đang xử lý đơn hàng | Chuyển sang các trạng thái khác |
| 3 | ClosingOrder | Chốt đơn | Đơn hàng đã được chốt | Chuyển sang AddToCart |
| 4 | AddToCart | Thêm giỏ hàng | Sản phẩm đã được thêm vào giỏ | Chuyển sang Ordered |
| 5 | Ordered | Đã order | Đã đặt hàng chính thức | Chuyển sang WaitingForStock |
| 6 | WaitingForStock | Chờ nhập kho | Chờ hàng nhập về kho | Chuyển sang Arrived |
| 7 | Arrived | Hàng về | Hàng đã về kho | Chuyển sang Invoiced |
| 8 | Invoiced | Đã báo đơn | Đã tạo hóa đơn | Chuyển sang Delivering |
| 9 | Delivering | Đang giao hàng | Đang trong quá trình giao hàng | Chuyển sang Completed |
| 10 | Completed | Hoàn thành | Đã giao hàng thành công | Không thể thay đổi |
| 11 | Cancelled | Huỷ | Đơn hàng bị hủy | Không thể thay đổi |

### Quy tắc đồng bộ trạng thái Order và OrderDetail

#### **Quy tắc cập nhật trạng thái Order:**
1. **Khi tất cả OrderDetails cùng trạng thái**: Order sẽ có trạng thái đó
2. **Khi OrderDetails có trạng thái khác nhau** (trừ Cancelled): Order = Processing (2)
3. **Khi tất cả OrderDetails = Cancelled**: Order = Cancelled (11)

#### **Ví dụ cập nhật trạng thái:**
```
Scenario 1: Tất cả OrderDetails cùng trạng thái
OrderDetail 1: Completed (10)
OrderDetail 2: Completed (10) 
OrderDetail 3: Completed (10)
=> Order Status: Completed (10)

Scenario 2: OrderDetails khác trạng thái
OrderDetail 1: Ordered (5)
OrderDetail 2: Arrived (7)
OrderDetail 3: Invoiced (8)
=> Order Status: Processing (2)

Scenario 3: Tất cả OrderDetails bị hủy
OrderDetail 1: Cancelled (11)
OrderDetail 2: Cancelled (11)
OrderDetail 3: Cancelled (11)
=> Order Status: Cancelled (11)

Scenario 4: Mix với Cancelled
OrderDetail 1: Completed (10)
OrderDetail 2: Cancelled (11)
OrderDetail 3: Ordered (5)
=> Order Status: Processing (2)
```

---

## Quy tắc nghiệp vụ

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Số đơn hàng tự động generate theo format: ORD-{timestamp} (ví dụ: ORD-1313372764)     |
| BR-02 | Kiểm tra WarehouseInventory.available_qty trước khi tạo đơn hàng                     |
| BR-03 | Tự động reserve stock (reserved_qty) khi OrderDetail = Ordered (5)                   |
| BR-04 | Tự động release stock khi OrderDetail = Cancelled (11)                               |
| BR-05 | Không cho phép chỉnh sửa OrderDetail khi status = Completed (10) hoặc Cancelled (11)|
| BR-06 | Ghi log mọi thay đổi trạng thái OrderDetail và Order                                 |
| BR-07 | Tổng tiền đơn hàng = Σ(qty × price - discount + addition_price)                      |
| BR-08 | Chỉ cho phép chọn sản phẩm thuộc site hiện tại                                       |
| BR-09 | **Order status tự động cập nhật** dựa trên OrderDetails status theo quy tắc đồng bộ  |
| BR-10 | Khi không đủ hàng, tự động tạo pre-order và set OrderDetail = WaitingForStock (6)    |
| BR-11 | **Status values từ 1-11** theo ENUM đã định nghĩa                                    |
| BR-12 | OrderDetail có thể cập nhật độc lập, Order status sẽ tự động sync                     |
