# UC008: Manage Orders

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-008-MO                                  |
| Tên Use Case   | Quản lý đơn hàng                           |
| Actor          | SiteAdmin (người dùng có quyền hạn manage_orders) |
| Mô tả          | Người dùng có thể tạo, xem, cập nhật trạng thái đơn hàng thuộc trang web mà họ sở hữu |
| Độ ưu tiên     | Cao                                        |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có quyền hạn **manage_orders**<br>- Đã có khách hàng và sản phẩm trong hệ thống |
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
| 10   | SiteAdmin  | Chọn khách hàng hoặc nhấn **"Tạo khách hàng mới"** |
| 10a  | Hệ thống   | **Nếu chọn "Tạo khách hàng mới"**: Hiển thị popup form tạo customer |
| 10b  | SiteAdmin  | **Trong popup**: Nhập thông tin khách hàng (tên, email, phone, địa chỉ) |
| 10c  | SiteAdmin  | **Trong popup**: Nhấn **"Lưu khách hàng"** |
| 10d  | Hệ thống   | Validate thông tin khách hàng và tạo Customer record mới |
| 10e  | Hệ thống   | Đóng popup và **tự động điền thông tin KH** vào form Order |
| 10f  | Hệ thống   | **Tự động tạo địa chỉ mặc định** cho khách hàng và chọn làm shipping address |
| 11   | SiteAdmin  | Chọn địa chỉ giao hàng (hoặc dùng địa chỉ mặc định nếu vừa tạo KH mới) |
| 12   | SiteAdmin  | Thêm sản phẩm vào đơn hàng |
| 13   | SiteAdmin  | Nhập số lượng cho từng sản phẩm |
| 14   | Hệ thống   | Tự động tính giá và tổng tiền |
| 15   | SiteAdmin  | Chọn phương thức thanh toán |
| 16   | SiteAdmin  | Nhập ghi chú đơn hàng |
| 17   | SiteAdmin  | Nhấn nút **"Tạo đơn hàng"** |
| 18   | Hệ thống   | Tạo số đơn hàng tự động |
| 19   | Hệ thống   | Tạo đơn hàng với **status = New (1)** |
| 20   | Hệ thống   | Tạo OrderDetails cơ bản với **status = New (1)** cho từng sản phẩm |
| 21   | Hệ thống   | Gán site_id cho đơn hàng và OrderDetails |
| 22   | Hệ thống   | Thông báo thành công và **redirect đến trang chi tiết đơn hàng** vừa tạo |

**Note**: 
- Stock checking, status transitions, và inventory management được xử lý tự động khi "chốt đơn" tại UC-014-MOD
- OrderDetails được tạo với status = New (1) và chờ xử lý chi tiết tại UC-014-MOD

### Luồng cập nhật Order và OrderDetails

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 23   | SiteAdmin  | Nhấn vào một đơn hàng để xem chi tiết |
| 24   | Hệ thống   | Hiển thị form chỉnh sửa Order với **OrderDetails editable table** |
| 25   | Hệ thống   | **Order-level fields**: customer, shipping address, order notes, sale channel |
| 26   | Hệ thống   | **OrderDetails table**: cho phép chỉnh sửa qty, price, discount, addition_price |
| 27   | SiteAdmin  | **Có thể chỉnh sửa Order info**: customer, shipping address, notes |
| 28   | SiteAdmin  | **Có thể chỉnh sửa OrderDetails**: thay đổi qty, price, discount cho từng item |
| 29   | SiteAdmin  | **Có thể thêm OrderDetails**: thêm sản phẩm mới vào Order |
| 30   | SiteAdmin  | **Có thể xóa OrderDetails**: nhấn nút xóa cho OrderDetail |
| 30a  | Hệ thống   | **Kiểm tra trạng thái OrderDetail**: chỉ cho phép xóa nếu status = New (1) |
| 30b  | Hệ thống   | **Kiểm tra dependencies**: OrderDetail có đang được sử dụng ở UC khác không |
| 30c  | Hệ thống   | **Nếu có dependencies**: Hiển thị warning và danh sách dependencies |
| 30d  | Hệ thống   | **Nếu không thể xóa**: Disable nút xóa và hiển thị lý do |
| 30e  | Hệ thống   | **Nếu có thể xóa**: Hiển thị confirm dialog "Xác nhận xóa OrderDetail?" |
| 30f  | SiteAdmin  | **Xác nhận xóa** hoặc **Hủy bỏ** |
| 30g  | Hệ thống   | **Nếu xác nhận**: Xóa OrderDetail và cập nhật Order total |
| 31   | Hệ thống   | **Tự động tính lại total** khi OrderDetails thay đổi |
| 32   | SiteAdmin  | Nhập ghi chú thay đổi |
| 33   | SiteAdmin  | Nhấn nút **"Cập nhật Order"** |
| 34   | Hệ thống   | **Validate tất cả thay đổi**: Order info + OrderDetails |
| 35   | Hệ thống   | **Cập nhật Order và OrderDetails** trong cùng transaction |
| 36   | Hệ thống   | Lưu thay đổi và ghi log cho cả Order và OrderDetails |
| 37   | Hệ thống   | Gửi thông báo cho khách hàng (nếu có) |
| 38   | Hệ thống   | **Link "Quản lý OrderDetails nâng cao"** → UC-014-MOD (cho advanced features) |

**Note**: 
- **Delete Validation**: Chỉ có thể xóa OrderDetails ở trạng thái New (1)
- **Dependency Check**: Kiểm tra OrderDetail có được reference ở UC-014-MOD, UC-015-PO, UC-016-MWI, UC-017-MWO, UC-018-MPR
- **Safe Delete**: Prevent accidental deletion of OrderDetails đã có business impact
- **Advanced Management**: UC-014-MOD cung cấp tính năng nâng cao cho complex operations

### Integration với UC-014-MOD

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 38   | SiteAdmin  | Nhấn **"Quản lý OrderDetails nâng cao"** từ Order view |
| 39   | Hệ thống   | **Redirect đến UC-014-MOD** với filter Order ID |
| 40   | UC-014-MOD | Hiển thị OrderDetails của Order được chọn |
| 41   | UC-014-MOD | Xử lý: status transitions, stock management, payment tracking |
| 42   | UC-014-MOD | **Auto-sync Order status** khi OrderDetails thay đổi |

**Note**: UC-014-MOD chịu trách nhiệm cho tất cả advanced operations liên quan đến OrderDetails

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Không có quyền **manage_orders** | Không hiển thị menu quản lý đơn hàng |
| AF-02| Khách hàng chưa có địa chỉ   | Yêu cầu tạo địa chỉ trước     |
| AF-03| Order đã = Completed (11) hoặc Cancelled (12) | Chỉ cho phép xem Order info, không chỉnh sửa |
| AF-04| Sản phẩm không tồn tại hoặc không thuộc site | Không cho phép thêm vào đơn hàng |
| AF-05| Cập nhật Order info không hợp lệ | Hiển thị lỗi validation |
| AF-06| Redirect UC-014-MOD thất bại | Hiển thị thông báo lỗi và link manual |
| AF-07| **Tạo KH mới**: Email đã tồn tại | Hiển thị lỗi "Email đã được sử dụng", đề xuất chọn KH có sẵn |
| AF-08| **Tạo KH mới**: Thông tin không hợp lệ | Hiển thị lỗi validation trong popup, giữ popup mở |
| AF-09| **Tạo KH mới**: Hủy tạo khách hàng | Đóng popup, quay về form Order với customer chưa chọn |
| AF-10| **Tạo KH mới**: Lỗi hệ thống khi tạo | Hiển thị lỗi "Không thể tạo khách hàng", giữ popup mở để thử lại |
| AF-11| **ActivityLog**: Không có quyền xem lịch sử | Không hiển thị nút "Xem lịch sử" |
| AF-12| **ActivityLog**: Không có lịch sử thay đổi | Hiển thị thông báo "Chưa có thay đổi nào được ghi nhận" |
| AF-13| **ActivityLog**: Lỗi khi load activity log | Hiển thị lỗi và cho phép refresh lại |
| AF-14| **ActivityLog Export**: Quá nhiều records | Giới hạn export 1000 records, cho phép filter để giảm |
| AF-15| **ActivityLog**: Filter không trả về kết quả | Hiển thị "Không tìm thấy activity nào phù hợp với bộ lọc" |
| AF-16| **Xóa OrderDetail**: Status != New (1) | Không cho phép xóa, hiển thị "Chỉ có thể xóa OrderDetail ở trạng thái Tạo mới" |
| AF-17| **Xóa OrderDetail**: Có dependencies tại UC-014-MOD | Hiển thị warning "OrderDetail đang được quản lý tại UC-014-MOD, không thể xóa" |
| AF-18| **Xóa OrderDetail**: Có PurchaseRequestDetail liên kết | Hiển thị "OrderDetail đã tạo Purchase Request, không thể xóa" (UC-015-PO) |
| AF-19| **Xóa OrderDetail**: Có WarehouseReceiptDetail liên kết | Hiển thị "OrderDetail đã nhập kho, không thể xóa" (UC-016-MWI) |
| AF-20| **Xóa OrderDetail**: Có PaymentRequestDetail liên kết | Hiển thị "OrderDetail đã tạo Payment Request, không thể xóa" (UC-018-MPR) |
| AF-21| **Xóa OrderDetail**: Có WarehouseOutDetail liên kết | Hiển thị "OrderDetail đã xuất kho, không thể xóa" (UC-017-MWO) |
| AF-22| **Xóa OrderDetail**: Là OrderDetail cuối cùng trong Order | Hiển thị warning "Không thể xóa OrderDetail cuối cùng, Order phải có ít nhất 1 item" |
| AF-23| **Xóa OrderDetail**: User hủy confirm dialog | Quay về form edit, không xóa OrderDetail |

**Note**: Các luồng thay thế liên quan đến OrderDetails status, stock checking được xử lý tại UC-014-MOD

---

## Dữ liệu vào / ra

### Dữ liệu Order (Order level - managed by UC-008)
- **Khách hàng** (customer_id): ID khách hàng, bắt buộc
- **Địa chỉ giao hàng** (shipping_address_id): ID địa chỉ, bắt buộc
- **Ngày đơn hàng** (order_date): DateTime, mặc định hiện tại
- **Loại khách hàng** (customer_type): Enum, tự động từ khách hàng
- **Kênh bán hàng** (sale_channel): Enum (Online, Offline, Phone), bắt buộc
- **Người thanh toán vận chuyển** (shipping_payer): Enum (Người bán, Người mua)
- **Ghi chú giao hàng** (shipping_note): Text, tùy chọn
- **Ghi chú đơn hàng** (order_note): Text, tùy chọn

### Dữ liệu tạo Khách hàng mới (New Customer via Popup)
- **Tên khách hàng** (name): String, bắt buộc, max 255 chars
- **Email** (email): String, bắt buộc, unique trong site, format email hợp lệ
- **Số điện thoại** (phone): String, bắt buộc, format phone hợp lệ
- **Địa chỉ mặc định** (default_address): Text, bắt buộc cho shipping
- **Loại khách hàng** (customer_type): Enum, mặc định "Individual"
- **Ghi chú** (notes): Text, tùy chọn

### Dữ liệu OrderDetails (Basic editing by UC-008, Advanced by UC-014-MOD)
- **Basic editing trong UC-008**: qty, price, discount, addition_price
- **Product selection**: Chọn sản phẩm và thêm/xóa OrderDetails
- **Auto-calculation**: Tự động tính total khi thay đổi
- **Advanced management tại UC-014-MOD**: 
  - Trạng thái OrderDetail và payment status
  - Stock checking và inventory management
  - Status transitions và business logic

### Dữ liệu ActivityLog (Activity History)
- **Activity ID**: Unique identifier cho từng activity record
- **Subject**: Model được thay đổi (Order, OrderDetail, Customer)
- **Causer**: User thực hiện thay đổi (User ID, name, email)
- **Action**: Loại hành động (created, updated, deleted, restored)
- **Old Values**: Giá trị trước khi thay đổi (JSON format)
- **New Values**: Giá trị sau khi thay đổi (JSON format) 
- **Attributes**: Metadata bổ sung (IP address, user agent, etc.)
- **Created At**: Timestamp của activity
- **Description**: Mô tả chi tiết về thay đổi
- **Batch UUID**: Group related activities trong cùng transaction

### Dữ liệu ra (Output)
- **Số đơn hàng** (order_number): Tự động generate
- **Tổng tiền đơn hàng**: Tổng của tất cả OrderDetails
- **Trạng thái đơn hàng**: Mặc định "Chờ xác nhận"

---

## Trạng thái đơn hàng (Order Status)

### ENUM Status Values (1-12) - Shared với UC-014-MOD
| Value | Status | Vietnamese | Mô tả | UC-008 Management |
|-------|--------|------------|-------|------------------|
| 1 | New | Tạo mới | Đơn hàng mới được tạo | ✅ Tạo và view |
| 2 | Processing | Đang xử lý | Đang xử lý đơn hàng | ✅ View và update order info |
| 3 | ClosingOrder | Chốt đơn | Đơn hàng đã được chốt | 📋 View only (managed by UC-014-MOD) |
| 4 | AddToCart | Thêm giỏ hàng | Sản phẩm đã được thêm vào giỏ | 📋 View only |
| 5 | Ordered | Đã order | Đã đặt hàng chính thức | 📋 View only |
| 6 | PreOrder | Pre-order | Không đủ hàng, cần đặt hàng với supplier | 📋 View only |
| 7 | WaitingForStock | Chờ nhập kho | Hàng đã về từ supplier, chờ nhập kho | 📋 View only |
| 8 | Arrived | Hàng về | Hàng đã nhập kho thành công | 📋 View only |
| 9 | Invoiced | Đã báo đơn | Đã tạo hóa đơn | 📋 View only |
| 10 | Delivering | Đang giao hàng | Đang trong quá trình giao hàng | 📋 View only |
| 11 | Completed | Hoàn thành | Đã giao hàng thành công | 📋 View only |
| 12 | Cancelled | Huỷ | Đơn hàng bị hủy | ✅ Can cancel toàn bộ Order |

### ENUM Payment Status Values (1-5) - Managed by UC-014-MOD
**Note**: Payment status của OrderDetails được quản lý hoàn toàn bởi UC-014-MOD và UC-018-MPR

| Value | Status | Vietnamese | Description |
|-------|--------|------------|-------------|
| 1 | Unpaid | Chưa thanh toán | Chưa có yêu cầu thanh toán |
| 2 | PaymentRequested | Yêu cầu thanh toán | Đã gửi yêu cầu thanh toán cho khách |
| 3 | Paid | Đã thanh toán | Khách hàng đã thanh toán |
| 4 | Processing | Đang xử lý | Đang xử lý thanh toán |
| 5 | PendingConfirmation | Chờ xác nhận | Chờ xác nhận thanh toán |


### Order Status Auto-Sync (Handled by UC-014-MOD)

**⚠️ Important**: Order status được tự động cập nhật bởi UC-014-MOD khi OrderDetails status thay đổi.

#### **Quy tắc cập nhật tự động:**
1. **Khi tất cả OrderDetails cùng trạng thái**: Order sẽ có trạng thái đó
2. **Khi OrderDetails có trạng thái khác nhau** (trừ Cancelled): Order = Processing (2)
3. **Khi tất cả OrderDetails = Cancelled**: Order = Cancelled (11)

#### **UC-008 không trực tiếp thay đổi Order status**, trừ:
- **Manual cancellation**: SiteAdmin có thể cancel toàn bộ Order
- **Order info updates**: Chỉ cập nhật customer, shipping, notes - không ảnh hưởng status

#### **Ví dụ auto-sync từ UC-014-MOD:**
```
Scenario: OrderDetails status changes trong UC-014-MOD
OrderDetail 1: New → ClosingOrder → Arrived
OrderDetail 2: New → ClosingOrder → PreOrder  
OrderDetail 3: New → ClosingOrder → Arrived
=> Order Status auto-updated to: Processing (2) vì mixed status
```

---

## Quy tắc nghiệp vụ (UC-008 Focus)

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Số đơn hàng tự động generate theo format: ORD-{timestamp} (ví dụ: ORD-1313372764)     |
| BR-02 | Order creation chỉ tạo skeleton OrderDetails, chi tiết xử lý tại UC-014-MOD          |
| BR-03 | Chỉ cho phép chọn sản phẩm thuộc site hiện tại                                       |
| BR-04 | **Order level info**: customer, shipping address, order notes managed by UC-008      |
| BR-05 | **OrderDetails management**: status, stock, payment handled by UC-014-MOD            |
| BR-06 | Không cho phép chỉnh sửa Order khi status = Completed (11) hoặc Cancelled (12)       |
| BR-07 | Ghi log mọi thay đổi Order-level information                                         |
| BR-08 | Tổng tiền đơn hàng được tính tại UC-014-MOD từ OrderDetails                          |
| BR-09 | **Order status auto-sync** từ UC-014-MOD - UC-008 không trực tiếp thay đổi          |
| BR-10 | **Manual Order cancellation**: UC-008 có thể cancel toàn bộ Order                    |
| BR-11 | **Status values từ 1-12** theo ENUM đã định nghĩa, shared với UC-014-MOD             |
| BR-12 | **Integration**: Seamless redirect UC-008 → UC-014-MOD cho OrderDetails management   |
| BR-13 | **Customer creation**: Email phải unique trong phạm vi site                           |
| BR-14 | **Auto-fill**: Tự động điền thông tin KH mới vào form Order sau khi tạo thành công  |
| BR-15 | **Default address**: Tự động tạo địa chỉ mặc định cho KH mới và set làm shipping address |
| BR-16 | **Popup validation**: Validate realtime trong popup, không đóng popup nếu có lỗi     |
| BR-17 | **Site isolation**: KH mới được tạo chỉ thuộc về site hiện tại                       |
| BR-18 | **OrderDetails basic edit**: UC-008 cho phép chỉnh sửa qty, price, discount, addition_price |
| BR-19 | **Add/Remove OrderDetails**: Có thể thêm sản phẩm mới hoặc xóa item khỏi Order        |
| BR-20 | **Auto-calculate total**: Tự động tính lại tổng tiền khi OrderDetails thay đổi        |
| BR-21 | **Transaction integrity**: Cập nhật Order và OrderDetails trong cùng transaction      |
| BR-22 | **Validation**: Validate đồng thời Order info và OrderDetails trước khi lưu          |
| BR-23 | **ActivityLog**: Ghi log tất cả thay đổi Order và OrderDetails bằng spatie/laravel-activitylog |
| BR-24 | **Activity retention**: Lưu trữ activity log tối thiểu 12 tháng cho audit          |
| BR-25 | **Activity permission**: Chỉ user có quyền manage_orders mới xem được activity log |
| BR-26 | **Activity batching**: Group related changes trong cùng transaction với batch_uuid  |
| BR-27 | **Activity export**: Giới hạn export activity log tối đa 1000 records            |
| BR-28 | **Sensitive data**: Không log sensitive information (passwords, payment details)   |
| BR-29 | **OrderDetail delete validation**: Chỉ cho phép xóa OrderDetails với status = New (1) |
| BR-30 | **Dependency check**: Kiểm tra OrderDetail có được reference tại UC-014-MOD, UC-015-PO, UC-016-MWI, UC-017-MWO, UC-018-MPR trước khi xóa |
| BR-31 | **Minimum OrderDetails**: Order phải có ít nhất 1 OrderDetail, không cho phép xóa item cuối cùng |
| BR-32 | **Safe delete confirmation**: Luôn yêu cầu user confirm trước khi xóa OrderDetail |
| BR-33 | **Delete impact warning**: Hiển thị rõ lý do không thể xóa và suggest alternative actions |
| BR-34 | **UI state management**: Disable delete button và show tooltip khi OrderDetail không thể xóa |

### Phân chia trách nhiệm rõ ràng:

#### **UC-008-MO Responsibilities:**
- ✅ Order creation và basic info management
- ✅ Customer selection và shipping address
- ✅ **Customer creation** via popup khi không có KH phù hợp
- ✅ **Auto-fill customer info** sau khi tạo KH thành công
- ✅ **Basic OrderDetails editing**: qty, price, discount, addition_price
- ✅ **Add/Remove OrderDetails**: thêm/xóa sản phẩm trong Order
- ✅ **ActivityLog tracking**: Ghi log tất cả thay đổi và hiển thị lịch sử
- ✅ **Activity filtering**: Filter và export activity logs cho audit
- ✅ Order-level notes và comments
- ✅ Order listing và filtering
- ✅ Manual Order cancellation
- ✅ Integration với UC-014-MOD (for advanced features)

#### **UC-014-MOD Responsibilities (Advanced Features):**
- 📋 **Advanced OrderDetails management**: Status transitions, bulk operations
- 📋 **Stock management**: Stock checking, inventory integration, reservation
- 📋 **Payment processing**: Payment status tracking, payment request creation
- 📋 **PreOrder workflow**: PreOrder logic, WaitingForStock handling
- 📋 **Order status auto-sync**: Tự động cập nhật Order status từ OrderDetails
- 📋 **Advanced filtering**: Complex filters, reporting, analytics
- 📋 **Integration**: UC-015-PO, UC-016-MWI, UC-017-MWO, UC-018-MPR coordination

### Luồng xem lịch sử thay đổi Order (ActivityLog)

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 43   | SiteAdmin  | Nhấn **"Xem lịch sử"** từ Order detail view |
| 44   | Hệ thống   | **Query ActivityLog** cho Order và OrderDetails liên quan |
| 45   | Hệ thống   | **Hiển thị timeline** thay đổi theo thời gian (mới nhất trước) |
| 46   | Hệ thống   | **Show activity details**: user, action, old_values, new_values, timestamp |
| 47   | Hệ thống   | **Group activities**: Order level changes vs OrderDetails changes |
| 48   | SiteAdmin  | **Có thể filter** theo: user, action type, date range, field changed |
| 49   | SiteAdmin  | **Có thể expand** từng activity để xem chi tiết thay đổi |
| 50   | Hệ thống   | **Highlight significant changes**: status transitions, price changes, quantity adjustments |
| 51   | SiteAdmin  | **Có thể export** activity log thành CSV/PDF cho audit |

**Note**: 
- **ActivityLog integration**: Sử dụng spatie/laravel-activitylog để track tất cả thay đổi
- **Comprehensive logging**: Ghi log cả Order-level và OrderDetails-level changes
- **User attribution**: Mỗi thay đổi đều có thông tin user thực hiện
