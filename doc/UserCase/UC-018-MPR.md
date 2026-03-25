````markdown
# UC018: Manage Payment Request (Quản lý yêu cầu thanh toán)

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-018-MPR                                 |
| Tên Use Case   | Quản lý yêu cầu thanh toán                 |
| Actor          | SiteAdmin (người dùng có quyền hạn manage_payments) |
| Mô tả          | Người dùng có thể tạo, xem, cập nhật yêu cầu thanh toán (PaymentRequest) từ danh sách OrderDetails có trạng thái Arrived, quản lý chi tiết thanh toán theo từng OrderDetail |
| Độ ưu tiên     | Cao                                        |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có quyền hạn **manage_payments**<br>- Đã có OrderDetails với status = Arrived (7) trong hệ thống<br>- PaymentRequestDetail table đã được thiết lập |
| Post-condition | PaymentRequest và PaymentRequestDetails được tạo/cập nhật thành công, liên kết với OrderDetails tương ứng |

---

## Luồng chính (Main Flow)

### Luồng xem danh sách Payment Requests

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | SiteAdmin  | Đăng nhập vào hệ thống |
| 2    | Hệ thống   | Kiểm tra vai trò và quyền sở hữu trang web |
| 3    | Hệ thống   | Hiển thị **"Yêu cầu thanh toán"** trong menu sidebar |
| 4    | SiteAdmin  | Nhấn vào **"Yêu cầu thanh toán"** |
| 5    | Hệ thống   | Hiển thị danh sách PaymentRequests thuộc trang web hiện tại |
| 6    | Hệ thống   | Hiển thị các cột: ID, Customer, Request Date, Total Amount, Payment Status, Items Count, Actions |
| 7    | Hệ thống   | Hiển thị bộ lọc theo customer, payment status, date range |
| 8    | Hệ thống   | Hiển thị nút **"Tạo yêu cầu thanh toán"** |

### Luồng tạo Payment Request từ OrderDetails có status = Arrived

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 9    | SiteAdmin  | Nhấn nút **"Tạo yêu cầu thanh toán"** |
| 10   | Hệ thống   | **Redirect đến UC-014-MOD** với filter Status = Arrived (7) |
| 11   | Hệ thống   | Hiển thị danh sách OrderDetails có status = Arrived (8) chưa có payment_request_detail_id |
| 12   | Hệ thống   | **Group OrderDetails theo Customer** để hiển thị rõ ràng |
| 13   | SiteAdmin  | Chọn multiple OrderDetails bằng checkbox (có thể chọn nhiều customers) |
| 14   | SiteAdmin  | Nhấn **"Tạo yêu cầu thanh toán"** từ bulk actions |
| 15   | Hệ thống   | **Validate tất cả OrderDetails** có status = Arrived (7) |
| 16   | Hệ thống   | **Group OrderDetails theo Customer** |
| 17   | Hệ thống   | Hiển thị preview PaymentRequest cho từng Customer: |
|      |            | - Customer info |
|      |            | - Danh sách OrderDetails và tổng tiền |
|      |            | - Payment due date (mặc định +30 ngày) |
| 18   | SiteAdmin  | Review thông tin và điều chỉnh nếu cần: |
|      |            | - Payment due date |
|      |            | - Note cho PaymentRequest |
|      |            | - Remove OrderDetails không muốn include |
| 19   | SiteAdmin  | Nhấn **"Xác nhận tạo yêu cầu thanh toán"** |
| 20   | Hệ thống   | **Begin Database Transaction** |
| 21   | Hệ thống   | Tạo PaymentRequest cho từng Customer: |
|      |            | - customer_id |
|      |            | - total = sum of selected OrderDetails |
|      |            | - payment_status = PaymentRequested (2) |
|      |            | - due_date |
|      |            | - note |
|      |            | - site_id |
| 22   | Hệ thống   | Tạo PaymentRequestDetails cho từng OrderDetail: |
|      |            | - payment_request_id |
|      |            | - order_detail_id |
|      |            | - amount = OrderDetail.total |
|      |            | - note |
| 23   | Hệ thống   | **Cập nhật OrderDetails.payment_status**: Unpaid → PaymentRequested |
| 24   | Hệ thống   | **Generate payment_request_number** tự động theo format PR-{YYYYMMDD}-{sequence} |
| 25   | Hệ thống   | **Commit Transaction** |
| 26   | Hệ thống   | **Gửi notification** đến customers về payment request |
| 27   | Hệ thống   | Thông báo tạo thành công với summary |
| 28   | Hệ thống   | **Return to UC-018-MPR** payment request list |

### Luồng xem chi tiết Payment Request

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 29   | SiteAdmin  | Nhấn vào một PaymentRequest để xem chi tiết |
| 30   | Hệ thống   | Hiển thị thông tin PaymentRequest: |
|      |            | - Payment Request Number |
|      |            | - Customer info (name, email, phone) |
|      |            | - Request Date, Due Date |
|      |            | - Total Amount |
|      |            | - Payment Status với badge |
|      |            | - Notes |
| 31   | Hệ thống   | Hiển thị bảng PaymentRequestDetails với các cột: |
|      |            | - OrderDetail ID (link to order detail) |
|      |            | - Order Number (link to order) |
|      |            | - Product Info (ProductItem name + SKU) |
|      |            | - Quantity |
|      |            | - Unit Price |
|      |            | - Amount |
|      |            | - OrderDetail Status |
|      |            | - Notes |
| 32   | Hệ thống   | Hiển thị action buttons: |
|      |            | - **"Cập nhật trạng thái thanh toán"** |
|      |            | - **"Gửi reminder"** |
|      |            | - **"Export PDF"** (payment invoice) |
|      |            | - **"Edit"** (nếu chưa thanh toán) |

### Luồng cập nhật payment status

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 33   | SiteAdmin  | Nhấn **"Cập nhật trạng thái thanh toán"** |
| 34   | Hệ thống   | Hiển thị modal cập nhật với payment status options |
| 35   | SiteAdmin  | Chọn payment status mới: |
|      |            | - Processing (4): Đang xử lý thanh toán |
|      |            | - PendingConfirmation (5): Chờ xác nhận |
|      |            | - Paid (3): Đã thanh toán |
|      |            | - Cancelled (6): Hủy yêu cầu thanh toán |
| 36   | SiteAdmin  | Nhập payment note và thông tin thanh toán nếu có |
| 37   | SiteAdmin  | Nhập actual payment date nếu status = Paid |
| 38   | SiteAdmin  | Nhấn **"Cập nhật"** |
| 39   | Hệ thống   | **Begin Database Transaction** |
| 40   | Hệ thống   | Cập nhật PaymentRequest.payment_status |
| 41   | Hệ thống   | **Cập nhật tất cả OrderDetails.payment_status** liên kết |
| 42   | Hệ thống   | Nếu status = Paid: |
|      |            | - Cập nhật PaymentRequest.paid_date |
|      |            | - OrderDetails eligible for warehouse out (if status = Invoiced) |
| 43   | Hệ thống   | **Commit Transaction** |
| 44   | Hệ thống   | **Gửi notification** cho customer về payment status change |
| 45   | Hệ thống   | Ghi audit log |
| 46   | Hệ thống   | Refresh payment request details |

### Luồng chỉnh sửa Payment Request

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 47   | SiteAdmin  | Nhấn **"Edit"** trên PaymentRequest (chỉ nếu status != Paid) |
| 48   | Hệ thống   | Hiển thị form chỉnh sửa với thông tin hiện tại |
| 49   | SiteAdmin  | Cập nhật thông tin: |
|      |            | - Due date |
|      |            | - Notes |
|      |            | - Add/remove PaymentRequestDetails |
| 50   | SiteAdmin  | Thêm OrderDetails mới (status = Arrived, chưa có payment request) |
| 51   | SiteAdmin  | Remove PaymentRequestDetails không cần thiết |
| 52   | SiteAdmin  | Nhấn **"Cập nhật"** |
| 53   | Hệ thống   | **Begin Database Transaction** |
| 54   | Hệ thống   | Cập nhật PaymentRequest information |
| 55   | Hệ thống   | **Recalculate total** từ PaymentRequestDetails |
| 56   | Hệ thống   | **Update OrderDetails.payment_status** cho items được thêm/xóa |
| 57   | Hệ thống   | **Commit Transaction** |
| 58   | Hệ thống   | Audit log changes |
| 59   | Hệ thống   | Thông báo cập nhật thành công |

### Luồng hủy Payment Request

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 60   | SiteAdmin  | Nhấn **"Cancel"** trên PaymentRequest |
| 61   | Hệ thống   | Hiển thị confirmation dialog với warning về impact |
| 62   | SiteAdmin  | Nhập lý do hủy (bắt buộc) |
| 63   | SiteAdmin  | Nhấn **"Xác nhận hủy"** |
| 64   | Hệ thống   | **Begin Database Transaction** |
| 65   | Hệ thống   | Cập nhật PaymentRequest.payment_status = Cancelled (6) |
| 66   | Hệ thống   | **Revert OrderDetails.payment_status**: PaymentRequested → Unpaid |
| 67   | Hệ thống   | Keep PaymentRequestDetails for audit trail |
| 68   | Hệ thống   | **Commit Transaction** |
| 69   | Hệ thống   | **Gửi notification** cho customer về cancellation |
| 70   | Hệ thống   | Audit log cancellation với lý do |
| 71   | Hệ thống   | Thông báo hủy thành công |

### Luồng gửi payment reminder

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 72   | SiteAdmin  | Nhấn **"Gửi reminder"** |
| 73   | Hệ thống   | Kiểm tra điều kiện gửi reminder (status = PaymentRequested/Processing) |
| 74   | Hệ thống   | Hiển thị preview email/SMS reminder |
| 75   | SiteAdmin  | Tùy chỉnh nội dung reminder message |
| 76   | SiteAdmin  | Chọn notification channels (Email, SMS, hoặc cả hai) |
| 77   | SiteAdmin  | Nhấn **"Gửi reminder"** |
| 78   | Hệ thống   | **Gửi reminder** đến customer qua channels được chọn |
| 79   | Hệ thống   | Log reminder activity |
| 80   | Hệ thống   | Thông báo gửi reminder thành công |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Không có quyền **manage_payments** | Không hiển thị menu Payment Requests |
| AF-02| Không có OrderDetails status = Arrived | Thông báo "Không có OrderDetails đủ điều kiện" |
| AF-03| OrderDetails đã có payment_request_detail_id | Không cho phép chọn (disabled checkbox) |
| AF-04| Customer không có thông tin liên lạc | Yêu cầu cập nhật customer info trước khi tạo |
| AF-05| PaymentRequest đã Paid | Chỉ cho phép xem, không chỉnh sửa/hủy |
| AF-06| PaymentRequest đã Cancelled | Chỉ cho phép xem và recreate |
| AF-07| Transaction rollback | Hiển thị lỗi chi tiết và không thay đổi dữ liệu |
| AF-08| Customer không tồn tại | Thông báo lỗi và yêu cầu kiểm tra OrderDetails |
| AF-09| Concurrent update payment request | Conflict detection và yêu cầu refresh |
| AF-10| Email/SMS service unavailable | Log error và thông báo gửi thất bại |
| AF-11| Invalid due date (quá khứ) | Validation error và correction |
| AF-12| No OrderDetails selected | Validation error "Phải chọn ít nhất một OrderDetail" |
| AF-13| Mixed customers but same request | Warning và tự động tách thành nhiều requests |
| AF-14| OrderDetails status changed | Refresh và re-validate eligibility |

---

## Dữ liệu hiển thị và tìm kiếm

### Dữ liệu hiển thị trong danh sách PaymentRequests
- **ID**: PaymentRequest ID
- **Payment Request Number**: Mã yêu cầu thanh toán (PR-{YYYYMMDD}-{sequence})
- **Customer**: Tên khách hàng (link to customer profile)
- **Request Date**: Ngày tạo yêu cầu
- **Due Date**: Ngày hạn thanh toán
- **Paid Date**: Ngày thanh toán thực tế (nếu đã thanh toán)
- **Total Amount**: Tổng số tiền
- **Items Count**: Số lượng OrderDetails
- **Payment Status**: Trạng thái thanh toán với badge màu
- **Days Overdue**: Số ngày quá hạn (nếu có)
- **Last Updated**: Thời gian cập nhật cuối
- **Actions**: View, Edit, Cancel, Send Reminder

### Dữ liệu hiển thị trong PaymentRequestDetails
- **OrderDetail ID**: ID chi tiết đơn hàng (link to UC-014-MOD)
- **Order Number**: Số đơn hàng (link to UC-008-MO)
- **Order Date**: Ngày đặt hàng
- **Product Info**: ProductItem name + SKU
- **Product Type**: Loại sản phẩm với color coding
- **Quantity**: Số lượng
- **Unit Price**: Đơn giá
- **Discount**: Chiết khấu
- **Amount**: Thành tiền
- **OrderDetail Status**: Trạng thái OrderDetail hiện tại
- **Notes**: Ghi chú cho item

### Tiêu chí tìm kiếm và lọc
#### **Tìm kiếm text (global search)**
- Payment Request Number
- Customer Name
- Customer Email
- Customer Phone
- Notes

#### **Bộ lọc (Filters)**
- **Customer**: Dropdown với search autocomplete
- **Payment Status**: Multi-select với badge preview
- **Request Date Range**: Date picker (from - to)
- **Due Date Range**: Date picker để tìm requests sắp/đã hết hạn
- **Amount Range**: Number input (min - max total amount)
- **Overdue Status**: Dropdown (All, Not Overdue, Overdue, Critically Overdue)
- **Has Paid Date**: Checkbox filter cho paid requests
- **Items Count Range**: Number input (min - max số OrderDetails)

### Export và báo cáo
- **Export Excel**: Danh sách payment requests với filtering
- **Export PDF**: Payment invoice/statement để gửi customer
- **Overdue Report**: Báo cáo các payment requests quá hạn
- **Payment Analytics**: Thống kê payment performance theo customer/time
- **Cash Flow Report**: Báo cáo dự báo cash flow dựa trên due dates

---

## Trạng thái và quy tắc chuyển đổi

### ENUM Payment Status Values (1-6) - Enhanced
| Value | Status | Vietnamese | Description | Transition Rules |
|-------|--------|------------|-------------|------------------|
| 1 | Unpaid | Chưa thanh toán | OrderDetail chưa có payment request | → 2 |
| 2 | PaymentRequested | Yêu cầu thanh toán | Đã tạo payment request | → 3, 4, 5, 6 |
| 3 | Paid | Đã thanh toán | Khách hàng đã thanh toán | Final status |
| 4 | Processing | Đang xử lý | Đang xử lý thanh toán | → 3, 5, 6 |
| 5 | PendingConfirmation | Chờ xác nhận | Chờ xác nhận thanh toán | → 3, 4, 6 |
| 6 | Cancelled | Đã hủy | Hủy yêu cầu thanh toán | → 2 (recreate) |

### Business Rules cho Payment Status Transition
- **PaymentRequested**: Default status khi tạo PaymentRequest
- **Processing/PendingConfirmation**: Có thể chuyển đổi qua lại
- **Paid**: Final status, không thể chuyển sang status khác
- **Cancelled**: Có thể tạo PaymentRequest mới cho OrderDetails đó
- **Automatic sync**: PaymentRequest status → tất cả PaymentRequestDetails → OrderDetails

---

## Quy tắc nghiệp vụ

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Chỉ hiển thị PaymentRequests thuộc site hiện tại (site_id isolation)               |
| BR-02 | Kiểm tra quyền **manage_payments** trước khi cho phép tạo/cập nhật                   |
| BR-03 | **Chỉ OrderDetails có status = Arrived (8)** mới eligible cho payment request      |
| BR-04 | **Một OrderDetail chỉ có thể thuộc một PaymentRequest** tại một thời điểm          |
| BR-05 | **Group by Customer**: Một PaymentRequest chỉ chứa OrderDetails của một Customer   |
| BR-06 | **Auto-calculate total**: PaymentRequest.total = SUM(PaymentRequestDetails.amount) |
| BR-07 | **Auto-generate payment_request_number**: Format PR-{YYYYMMDD}-{sequence}          |
| BR-08 | **Due date validation**: due_date >= request_date                                   |
| BR-09 | **Payment status sync**: PaymentRequest status → OrderDetails payment_status      |
| BR-10 | **Không cho phép xóa PaymentRequest** có status = Paid (chỉ cancel)                |
| BR-11 | **Audit trail đầy đủ**: Log mọi thay đổi payment status với timestamp             |
| BR-12 | **Customer notification**: Auto-send notifications khi tạo/update payment status   |
| BR-13 | **Overdue calculation**: Tự động tính số ngày quá hạn dựa trên due_date           |
| BR-14 | **Reminder throttling**: Chỉ cho phép gửi reminder mỗi customer 1 lần/24h          |
| BR-15 | **Transaction safety**: Tất cả operations wrapped trong database transaction       |
| BR-16 | **Site isolation**: Chỉ select Customers, OrderDetails thuộc site hiện tại        |
| BR-17 | **Payment eligibility**: Paid status enable warehouse out eligibility (UC-017)     |
| BR-18 | **Cascade updates**: PaymentRequest changes cascade to all PaymentRequestDetails  |
| BR-19 | **Data integrity**: Không cho phép orphaned PaymentRequestDetails                  |
| BR-20 | **Performance**: Pagination và indexing cho large payment request lists            |

---

## UI/UX Requirements

### Danh sách PaymentRequests
- **Responsive table**: Mobile-friendly với collapse columns
- **Status badges**: Color-coded payment status indicators
- **Overdue indicators**: Visual warnings cho requests quá hạn
- **Quick actions**: Inline buttons cho View, Edit, Send Reminder
- **Bulk actions**: Multiple selection cho bulk reminder sending
- **Advanced filters**: Collapsible filter panel với saved presets
- **Sorting**: Multi-column sorting với overdue priority
- **Real-time updates**: WebSocket updates cho payment status changes

### Tạo Payment Request từ OrderDetails
- **Seamless integration**: Smooth transition từ UC-014-MOD
- **Customer grouping**: Visual grouping with clear customer separation
- **Calculation display**: Real-time total calculation per customer
- **Preview mode**: Comprehensive preview before creation
- **Validation feedback**: Immediate feedback on selection eligibility
- **Bulk selection helpers**: Select all per customer, clear selections

### Chi tiết PaymentRequest
- **Master-detail layout**: PaymentRequest info ở trên, details ở dưới
- **Customer info panel**: Quick access to customer details
- **Status timeline**: Visual payment status history
- **Linked data navigation**: Easy navigation to Orders, OrderDetails
- **Payment actions**: Context-sensitive action buttons
- **Document generation**: In-browser PDF preview

### Reminder và Notification
- **Template customization**: Editable reminder templates
- **Multi-channel support**: Email, SMS, push notifications
- **Delivery tracking**: Track reminder delivery status
- **Response tracking**: Track customer interactions with reminders

### Performance & UX
- **Loading states**: Progressive loading cho large datasets
- **Error handling**: Comprehensive error recovery mechanisms
- **Offline indicators**: Show connectivity status
- **Keyboard shortcuts**: Efficiency shortcuts cho power users
- **Accessibility**: Full WCAG compliance
- **Mobile optimization**: Touch-friendly interface design

---

## Integration Points

### Với các Use Cases khác
- **UC-014-MOD**: OrderDetails filtering và payment request creation
- **UC-008-MO**: Order information và customer data
- **UC-007-MC**: Customer management và contact information
- **UC-017-MWO**: Payment status validation for warehouse out eligibility
- **UC-001-REG, UC-002-LOG**: User authentication và site isolation

### Với External Systems
- **Email services**: Payment request notifications và reminders
- **SMS gateways**: SMS reminder delivery
- **Payment gateways**: Payment processing integration (future enhancement)
- **Accounting systems**: Financial data synchronization
- **CRM systems**: Customer payment history integration
- **Reporting systems**: Payment analytics và business intelligence

````
