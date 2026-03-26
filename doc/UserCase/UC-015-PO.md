````markdown
# UC015: Manage Purchase Order (Quản lý đặt hàng nhà cung cấp)

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-015-PO                                  |
| Tên Use Case   | Quản lý đặt hàng nhà cung cấp              |
| Actor          | SiteAdmin (người dùng có quyền hạn manage_purchases) |
| Mô tả          | Người dùng có thể tạo, xem, cập nhật và quản lý chi tiết đơn đặt hàng (PurchaseRequestDetails) gửi cho supplier, bao gồm tạo từ pre-order hoặc tạo trực tiếp cho bất kỳ sản phẩm nào |
| Độ ưu tiên     | Cao                                        |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có quyền hạn **manage_purchases**<br>- Đã có suppliers và products trong hệ thống<br>- Có thể có pre-order từ OrderDetails cần mua hàng |
| Post-condition | Purchase Request và PurchaseRequestDetails được tạo/cập nhật thành công và thuộc về trang web hiện tại |

---

## Luồng chính (Main Flow)

### Luồng xem danh sách Purchase Requests

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | SiteAdmin  | Đăng nhập vào hệ thống |
| 2    | Hệ thống   | Kiểm tra vai trò và quyền sở hữu trang web |
| 3    | Hệ thống   | Hiển thị **"Đơn đặt hàng"** trong menu sidebar |
| 4    | SiteAdmin  | Nhấn vào **"Đơn đặt hàng"** |
| 5    | Hệ thống   | Hiển thị danh sách Purchase Requests thuộc trang web hiện tại |
| 6    | Hệ thống   | Hiển thị các cột: Purchase Number, Supplier, Request Date, Expected Delivery, Status, Total Amount |
| 7    | Hệ thống   | Hiển thị bộ lọc và tìm kiếm theo supplier, status, date range |
| 8    | Hệ thống   | Hiển thị nút **"Tạo đơn đặt hàng mới"** |

### Luồng tạo Purchase Request từ Pre-order

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 9    | SiteAdmin  | Nhấn vào **"Tạo từ Pre-order"** |
| 10   | Hệ thống   | Hiển thị danh sách OrderDetails có status = PreOrder (6) |
| 11   | Hệ thống   | Group OrderDetails theo **Product và Supplier** |
| 12   | SiteAdmin  | Chọn supplier cần tạo Purchase Request |
| 13   | Hệ thống   | Hiển thị danh sách ProductItems theo supplier được chọn |
| 14   | SiteAdmin  | Xem tổng số lượng cần order cho từng ProductItem (Sum qty from OrderDetails) |
| 15   | SiteAdmin  | Điều chỉnh số lượng đặt hàng thực tế nếu cần |
| 16   | SiteAdmin  | Nhập giá đơn vị cho từng ProductItem |
| 17   | SiteAdmin  | Chọn ngày giao hàng dự kiến |
| 18   | SiteAdmin  | Nhập ghi chú cho Purchase Request |
| 19   | SiteAdmin  | Nhấn **"Tạo Purchase Request"** |
| 20   | Hệ thống   | Tạo PurchaseRequest với purchase_number tự động |
| 21   | Hệ thống   | Tạo PurchaseRequestDetails cho từng ProductItem |
| 22   | Hệ thống   | **Liên kết OrderDetails với PurchaseRequestDetails** (purchase_request_detail_id) |
| 23   | Hệ thống   | Tính tổng tiền Purchase Request |
| 24   | Hệ thống   | Thông báo tạo thành công |

### Luồng tạo Purchase Request trực tiếp

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 25   | SiteAdmin  | Nhấn nút **"Tạo đơn đặt hàng mới"** |
| 26   | Hệ thống   | Hiển thị form tạo Purchase Request |
| 27   | SiteAdmin  | Chọn supplier từ dropdown |
| 28   | SiteAdmin  | Nhập thông tin cơ bản: Expected delivery date, notes |
| 29   | SiteAdmin  | Nhấn **"Thêm sản phẩm"** |
| 30   | Hệ thống   | Hiển thị modal chọn ProductItems của supplier |
| 31   | SiteAdmin  | Tìm kiếm và chọn ProductItems cần đặt hàng |
| 32   | SiteAdmin  | Nhập số lượng yêu cầu cho từng ProductItem |
| 33   | SiteAdmin  | Nhập giá đơn vị (lấy từ purchase_price làm mặc định) |
| 34   | Hệ thống   | Tính tự động total_price cho từng item |
| 35   | SiteAdmin  | Xem preview tổng tiền của Purchase Request |
| 36   | SiteAdmin  | Nhấn **"Lưu Purchase Request"** |
| 37   | Hệ thống   | Validate dữ liệu đầu vào |
| 38   | Hệ thống   | Tạo PurchaseRequest và PurchaseRequestDetails |
| 39   | Hệ thống   | Gán status = Draft (1) |
| 40   | Hệ thống   | Thông báo tạo thành công |

### Luồng xem và cập nhật Purchase Request Details

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 41   | SiteAdmin  | Nhấn vào một Purchase Request để xem chi tiết |
| 42   | Hệ thống   | Hiển thị thông tin Purchase Request: Supplier, dates, status, notes |
| 43   | Hệ thống   | Hiển thị bảng PurchaseRequestDetails với các cột: |
|      |            | - ProductItem (name + SKU) |
|      |            | - Requested Qty |
|      |            | - Received Qty |
|      |            | - Unit Price |
|      |            | - Total Price |
|      |            | - Linked OrderDetails (số lượng) |
|      |            | - Notes |
| 44   | Hệ thống   | Hiển thị action buttons dựa trên status hiện tại |
| 45   | SiteAdmin  | **Chỉnh sửa quantity/price** nếu status = Draft |
| 46   | SiteAdmin  | **Thêm/xóa items** nếu status = Draft |
| 47   | SiteAdmin  | **Gửi cho supplier** (chuyển status = Sent) |
| 48   | Hệ thống   | Export Purchase Order PDF để gửi supplier |
| 49   | SiteAdmin  | **Cập nhật status** khi nhận response từ supplier |

### Luồng cập nhật trạng thái Purchase Request

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 50   | SiteAdmin  | Chọn **"Cập nhật trạng thái"** |
| 51   | Hệ thống   | Hiển thị dropdown status hợp lệ theo transition rules |
| 52   | SiteAdmin  | Chọn status mới (Sent, Confirmed, Cancelled) |
| 53   | SiteAdmin  | Nhập supplier response nếu có |
| 54   | SiteAdmin  | Cập nhật expected/actual delivery date |
| 55   | SiteAdmin  | Nhấn **"Xác nhận cập nhật"** |
| 56   | Hệ thống   | Validate status transition |
| 57   | Hệ thống   | Cập nhật Purchase Request status |
| 58   | Hệ thống   | **Tự động cập nhật OrderDetails status** nếu có liên kết |
| 59   | Hệ thống   | Ghi log thay đổi |
| 60   | Hệ thống   | Thông báo cập nhật thành công |

### Luồng nhập hàng từ Purchase Request

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 61   | SiteAdmin  | Supplier giao hàng (có thể giao từng phần) |
| 62   | SiteAdmin  | Truy cập **"Nhập hàng"** từ Purchase Request |
| 63   | Hệ thống   | Hiển thị form nhập kho với danh sách PurchaseRequestDetails |
| 64   | SiteAdmin  | Nhập số lượng thực tế nhận được cho từng item |
| 65   | SiteAdmin  | Chọn location để nhập hàng |
| 66   | SiteAdmin  | Nhập purchase price thực tế nếu khác dự kiến |
| 67   | SiteAdmin  | Nhấn **"Tạo phiếu nhập kho"** |
| 68   | Hệ thống   | Tạo WarehouseReceipt và WarehouseReceiptDetails |
| 69   | Hệ thống   | **Liên kết với PurchaseRequestDetails** |
| 70   | Hệ thống   | Cập nhật **received_qty** trong PurchaseRequestDetails |
| 71   | Hệ thống   | Cập nhật WarehouseInventory (current_qty) |
| 72   | Hệ thống   | Kiểm tra received_qty vs requested_qty |
| 73   | Hệ thống   | Nếu đủ hàng: **Auto-fulfill pre-orders** liên kết |
| 74   | Hệ thống   | Cập nhật OrderDetails status từ WaitingForStock → Arrived |
| 75   | Hệ thống   | Cập nhật Purchase Request status (PartiallyDelivered/Delivered) |
| 76   | Hệ thống   | Thông báo khách hàng có pre-order rằng hàng đã về |

### Luồng cập nhật trạng thái khi hàng về từ supplier

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 117  | SiteAdmin  | Nhận thông báo hàng về từ supplier |
| 118  | SiteAdmin  | Truy cập **"Quản lý Purchase Requests"** |
| 119  | SiteAdmin  | Tìm Purchase Request tương ứng |
| 120  | SiteAdmin  | Xem danh sách PurchaseRequestDetails |
| 121  | SiteAdmin  | Kiểm tra hàng về có đúng theo OrderDetail không |
| 122  | SiteAdmin  | **Cập nhật OrderDetails status**: PreOrder (6) → WaitingForStock (7) |
| 123  | Hệ thống   | **Validate**: Chỉ OrderDetails có liên kết PurchaseRequestDetail mới được cập nhật |
| 124  | Hệ thống   | Cập nhật trạng thái và ghi log |
| 125  | Hệ thống   | **Tự động cập nhật Order status** nếu tất cả OrderDetails thay đổi |
| 126  | Hệ thống   | **Enable tạo phiếu nhập kho** từ OrderDetails có status = WaitingForStock |

### Luồng bulk operations

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 77   | SiteAdmin  | Chọn multiple Purchase Requests bằng checkbox |
| 78   | SiteAdmin  | Chọn bulk action: **"Gửi hàng loạt"**, **"Hủy hàng loạt"** |
| 79   | Hệ thống   | Hiển thị confirmation modal với danh sách được chọn |
| 80   | SiteAdmin  | Xác nhận bulk action |
| 81   | Hệ thống   | Thực hiện action cho từng Purchase Request hợp lệ |
| 82   | Hệ thống   | Hiển thị báo cáo kết quả: Success/Failed với lý do |
| 83   | Hệ thống   | Export bulk PDF nếu action = "Gửi hàng loạt" |

### Luồng xóa Purchase Request

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 84   | SiteAdmin  | Nhấn nút **"Xóa"** trên Purchase Request |
| 85   | Hệ thống   | Kiểm tra điều kiện cho phép xóa (chỉ Draft status) |
| 86   | Hệ thống   | Hiển thị warning modal với thông tin impact |
| 87   | Hệ thống   | Hiển thị danh sách OrderDetails sẽ bị ảnh hưởng |
| 88   | Hệ thống   | Hiển thị số lượng PurchaseRequestDetails sẽ bị xóa |
| 89   | SiteAdmin  | Nhập lý do xóa (bắt buộc) |
| 90   | SiteAdmin  | Nhấn **"Xác nhận xóa"** |
| 91   | Hệ thống   | **Begin Database Transaction** |
| 92   | Hệ thống   | Xóa tất cả PurchaseRequestDetails |
| 93   | Hệ thống   | **Cập nhật OrderDetails.purchase_request_detail_id = NULL** |
| 94   | Hệ thống   | **Cập nhật OrderDetails status từ WaitingForStock → Processing** |
| 95   | Hệ thống   | **Tính toán lại Order status** dựa trên OrderDetails còn lại |
| 96   | Hệ thống   | **Cập nhật Order status** nếu tất cả OrderDetails không còn WaitingForStock |
| 97   | Hệ thống   | Xóa Purchase Request |
| 98   | Hệ thống   | Ghi log deletion với lý do và user |
| 99   | Hệ thống   | **Commit Transaction** |
| 100  | Hệ thống   | Thông báo xóa thành công với summary |
| 101  | Hệ thống   | **Gửi notification** cho customers có pre-order bị ảnh hưởng |

### Luồng hủy Purchase Request (soft delete)

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 102  | SiteAdmin  | Nhấn nút **"Hủy"** trên Purchase Request có status ≥ Sent |
| 103  | Hệ thống   | Hiển thị confirmation modal |
| 104  | Hệ thống   | Hiển thị warning về impact đến OrderDetails |
| 105  | SiteAdmin  | Nhập lý do hủy và ghi chú gửi supplier |
| 106  | SiteAdmin  | Nhấn **"Xác nhận hủy"** |
| 107  | Hệ thống   | **Begin Database Transaction** |
| 108  | Hệ thống   | Cập nhật Purchase Request status = Cancelled (6) |
| 109  | Hệ thống   | **Cập nhật OrderDetails status về Processing** (từ WaitingForStock) |
| 110  | Hệ thống   | **Giữ nguyên liên kết** OrderDetails.purchase_request_detail_id (for audit) |
| 111  | Hệ thống   | **Tính toán lại Order status** |
| 112  | Hệ thống   | Ghi log cancellation với lý do |
| 113  | Hệ thống   | **Commit Transaction** |
| 114  | Hệ thống   | **Gửi email thông báo hủy** cho supplier |
| 115  | Hệ thống   | **Thông báo customers** có pre-order về việc delay |
| 116  | Hệ thống   | Hiển thị thông báo hủy thành công |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Không có quyền **manage_purchases** | Không hiển thị menu Purchase Orders |
| AF-02| Không có supplier nào | Hiển thị thông báo tạo supplier trước (link UC-009-MS) |
| AF-03| Không có OrderDetails pre-order | "Tạo từ Pre-order" bị disable |
| AF-04| ProductItem không có supplier | Không thể tạo Purchase Request, yêu cầu gán supplier |
| AF-05| Status = Sent/Confirmed/Delivered | Chỉ cho phép xem, không chỉnh sửa |
| AF-06| Status transition không hợp lệ | Hiển thị lỗi và danh sách trạng thái được phép |
| AF-07| Supplier từ chối Purchase Request | Cập nhật status = Cancelled, tìm supplier khác |
| AF-08| Hàng về không đúng số lượng | Cập nhật received_qty khác requested_qty, ghi chú lý do |
| AF-09| Giao hàng từng phần nhiều lần | Cho phép nhập kho nhiều lần với received_qty tích lũy |
| AF-10| Hủy Purchase Request đã gửi | Revert OrderDetails status về PreOrder (6) |
| AF-11| Xóa ProductItem có Purchase Request | Không cho phép xóa, hiển thị warning |
| AF-12| Concurrent update Purchase Request | Hiển thị conflict warning, yêu cầu refresh |
| AF-13| Nhập kho không liên kết Purchase Request | Cho phép tạo WarehouseReceipt độc lập |
| AF-14| Auto-fulfill pre-order thất bại | Ghi log và thông báo admin, yêu cầu xử lý thủ công |
| AF-15| Supplier giao thừa hàng | Cho phép received_qty > requested_qty, tạo tồn kho dự phòng |
| AF-16| Xóa Purchase Request status ≠ Draft | Chỉ cho phép "Hủy" (soft delete), không cho phép xóa vĩnh viễn |
| AF-17| Xóa Purchase Request có received_qty > 0 | Không cho phép xóa, yêu cầu rollback nhập kho trước |
| AF-18| Xóa Purchase Request không có lý do | Validation error: "Lý do xóa là bắt buộc" |
| AF-19| Transaction rollback khi xóa PR | Hiển thị lỗi và giữ nguyên dữ liệu, yêu cầu thử lại |
| AF-20| OrderDetails orphaned sau khi xóa PR | Auto-revert về status trước WaitingForStock |
| AF-21| Order status conflict sau xóa PR | Tính toán lại Order status từ tất cả OrderDetails |
| AF-22| Bulk delete với mixed status | Chỉ xóa/hủy những PR hợp lệ, báo cáo chi tiết |

---

## Dữ liệu hiển thị và tìm kiếm

### Dữ liệu hiển thị trong danh sách Purchase Requests
- **ID**: Purchase Request ID
- **Purchase Number**: Mã đơn đặt hàng (PR-{timestamp})
- **Supplier**: Tên supplier (link đến supplier detail)
- **Request Date**: Ngày tạo yêu cầu
- **Expected Delivery**: Ngày giao hàng dự kiến
- **Actual Delivery**: Ngày giao hàng thực tế
- **Status**: Trạng thái với badge màu
- **Total Amount**: Tổng tiền (tự động tính từ details)
- **Items Count**: Số lượng ProductItems
- **Linked Orders**: Số đơn hàng liên kết (pre-order)
- **Received Progress**: % hàng đã nhận (received_qty/requested_qty)
- **Last Updated**: Thời gian cập nhật cuối

### Dữ liệu hiển thị trong Purchase Request Details
- **ProductItem**: Tên và SKU sản phẩm
- **Product Info**: Tên Product, ProductType, Category
- **Requested Qty**: Số lượng yêu cầu
- **Received Qty**: Số lượng đã nhận
- **Remaining Qty**: Số lượng còn thiếu (requested - received)
- **Unit Price**: Giá đơn vị
- **Total Price**: Thành tiền (requested_qty × unit_price)
- **Linked OrderDetails**: Danh sách OrderDetails liên kết
- **Pre-order Count**: Số khách hàng đang pre-order
- **Current Stock**: Tồn kho hiện tại
- **Notes**: Ghi chú cho item

### Tiêu chí tìm kiếm và lọc
#### **Tìm kiếm text (global search)**
- Purchase Number
- Supplier Name
- Product Name
- ProductItem SKU/Name
- Notes

#### **Bộ lọc (Filters)**
- **Supplier**: Dropdown với search autocomplete
- **Status**: Multi-select với badge preview
- **Request Date Range**: Date picker (from - to)
- **Expected Delivery Range**: Date picker
- **Has Pre-orders**: Checkbox (chỉ PR có liên kết OrderDetails)
- **Completion Status**: Dropdown (Not Started, In Progress, Completed, Overdue)
- **Total Amount Range**: Number input (min - max)
- **Product Category**: Multi-select dropdown
- **Product Type**: Multi-select với color indicator

### Export và báo cáo
- **Export Purchase Orders PDF**: Xuất Purchase Orders gửi supplier
- **Export Excel**: Danh sách Purchase Requests với filtering
- **Pre-order Report**: Báo cáo OrderDetails đang chờ hàng
- **Supplier Performance**: Báo cáo hiệu suất giao hàng theo supplier
- **Cost Analysis**: Phân tích chi phí đặt hàng theo sản phẩm/supplier

---

## Trạng thái và quy tắc chuyển đổi

### ENUM Status Values cho Purchase Request (1-6)
| Value | Status | Vietnamese | Transition Rules |
|-------|--------|------------|------------------|
| 1 | Draft | Bản thảo | → 2, 6 |
| 2 | Sent | Đã gửi | → 3, 6 |
| 3 | Confirmed | Đã xác nhận | → 4, 5, 6 |
| 4 | PartiallyDelivered | Giao từng phần | → 5 |
| 5 | Delivered | Đã giao hàng | No transition (final) |
| 6 | Cancelled | Đã hủy | No transition (final) |

### Business Rules cho Status Transition
- **Draft**: Cho phép chỉnh sửa toàn bộ thông tin
- **Sent**: Chỉ cho phép cập nhật supplier response và delivery dates
- **Confirmed**: Cho phép nhập hàng, cập nhật received_qty
- **Auto transition**: PartiallyDelivered ↔ Delivered dựa trên received_qty
- **Final status**: Delivered và Cancelled không thể thay đổi

---

## Quy tắc nghiệp vụ

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Chỉ hiển thị Purchase Requests thuộc site hiện tại (site_id isolation)              |
| BR-02 | Kiểm tra quyền **manage_purchases** trước khi cho phép tạo/cập nhật                  |
| BR-03 | Purchase Number tự động generate theo format: PR-{YYYYMMDD}-{sequence}              |
| BR-04 | Một PurchaseRequestDetail có thể liên kết với nhiều OrderDetails                     |
| BR-05 | OrderDetails.purchase_request_detail_id chỉ set khi có purchase request             |
| BR-06 | Tự động tính total_amount từ tổng các PurchaseRequestDetails                         |
| BR-07 | Expected_fulfillment_date estimate dựa trên supplier lead time                      |
| BR-08 | Khi received_qty = requested_qty: auto-update status = Delivered                    |
| BR-09 | Khi received_qty > 0 và < requested_qty: auto-update status = PartiallyDelivered   |
| BR-10 | Auto-fulfill pre-orders khi hàng về đủ số lượng                                     |
| BR-11 | Cập nhật OrderDetails status từ WaitingForStock → Arrived khi hàng về               |
| BR-12 | Ghi log chi tiết mọi thay đổi trạng thái và received_qty                            |
| BR-13 | Validation: requested_qty > 0, unit_price >= 0                                      |
| BR-14 | Không cho phép xóa Supplier có Purchase Requests                                    |
| BR-15 | Không cho phép xóa ProductItem có PurchaseRequestDetails                            |
| BR-16 | Purchase Request chỉ chứa ProductItems của cùng một supplier                        |
| BR-17 | Site isolation: chỉ select suppliers và products thuộc site hiện tại               |
| BR-18 | Real-time notification khi Purchase Request status thay đổi                         |
| BR-19 | Backup và audit trail cho tất cả purchase operations                               |
| BR-20 | Integration với WarehouseInventory để cập nhật stock levels                         |
| BR-21 | **Chỉ cho phép XÓA Purchase Request có status = Draft (1)**                        |
| BR-22 | **Purchase Request có status ≥ Sent chỉ có thể HỦY (status = Cancelled)**          |
| BR-23 | **Không cho phép xóa PR có PurchaseRequestDetails với received_qty > 0**           |
| BR-24 | **Khi xóa PR: Auto-revert OrderDetails.purchase_request_detail_id = NULL**         |
| BR-25 | **Khi xóa PR: Auto-update OrderDetails status WaitingForStock → Processing**       |
| BR-26 | **Khi xóa/hủy PR: Tự động tính lại Order status từ tất cả OrderDetails**           |
| BR-27 | **Xóa PR phải có lý do (required field), ghi vào audit log**                       |
| BR-28 | **Hủy PR đã gửi phải thông báo supplier qua email**                                |
| BR-29 | **Database transaction bắt buộc cho mọi delete/cancel operations**                 |
| BR-30 | **Thông báo customers có pre-order khi PR bị xóa/hủy**                             |

---

## UI/UX Requirements

### Danh sách Purchase Requests
- **Responsive table**: Mobile-friendly với collapse columns
- **Status badges**: Color-coded với progress indicators
- **Quick actions**: Inline buttons cho Send, Receive, Cancel
- **Bulk selection**: Checkbox với select all/none
- **Advanced filters**: Collapsible filter panel
- **Sorting**: Multi-column sorting
- **Progress bars**: Visual progress cho received_qty/requested_qty

### Chi tiết Purchase Request
- **Master-detail layout**: Purchase Request info ở trên, Details ở dưới
- **Editable grid**: Inline editing cho Draft status
- **Status timeline**: Visual workflow với timestamps
- **Linked data**: Quick links đến OrderDetails, Products, Supplier
- **Action panels**: Context-sensitive actions based on status
- **PDF preview**: In-browser preview trước khi gửi supplier

### Tạo Purchase Request
- **Wizard interface**: Multi-step với progress indicator
- **Smart suggestions**: Gợi ý products dựa trên pre-orders
- **Real-time calculation**: Auto-update total amounts
- **Validation feedback**: Immediate validation với helpful messages
- **Draft auto-save**: Tự động lưu draft khi nhập dữ liệu

### Performance & UX
- **Loading states**: Skeleton screens cho data loading
- **Optimistic updates**: UI updates trước khi server response
- **Keyboard shortcuts**: Efficiency shortcuts cho power users
- **Search highlighting**: Highlight search terms trong results
- **Responsive design**: Mobile-first approach
- **Accessibility**: WCAG 2.1 compliance với screen reader support

### Delete & Cancel Operations
- **Conditional actions**: Hiển thị "Xóa" chỉ cho Draft status, "Hủy" cho status ≥ Sent
- **Impact preview**: Modal hiển thị OrderDetails và Orders sẽ bị ảnh hưởng
- **Confirmation dialogs**: Multi-step confirmation cho destructive operations
- **Reason input**: Required reason field với character counter
- **Progress tracking**: Real-time progress cho bulk delete/cancel operations
- **Rollback capability**: Undo functionality trong thời gian ngắn sau xóa/hủy
- **Audit trail display**: Hiển thị lịch sử xóa/hủy với timestamps và reasons

---

## Integration Points

### Với các Use Cases khác
- **UC-009-MS**: Supplier management - supplier selection và info
- **UC-006-MP**: Product management - product selection và pricing
- **UC-008-MO**: Order management - pre-order identification
- **UC-014-MOD**: Order Details - pre-order tracking và fulfillment
- **UC-010-MI**: Inventory - stock updates và warehouse operations

### Với External Systems
- **Email system**: Gửi Purchase Orders cho suppliers
- **PDF generation**: Tạo Purchase Order documents
- **Notification system**: Real-time updates cho status changes
- **Audit system**: Activity logging cho compliance
- **Reporting system**: Business intelligence và analytics

````
