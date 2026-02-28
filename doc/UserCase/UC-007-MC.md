# UC007: Manage Customers

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-007-MC                                  |
| Tên Use Case   | Quản lý khách hàng                         |
| Actor          | SiteAdmin (người dùng có quyền hạn manage-customers) |
| Mô tả          | Người dùng có thể tạo, xem, cập nhật, xóa thông tin khách hàng thuộc trang web mà họ sở hữu |
| Độ ưu tiên     | Cao                                        |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có quyền hạn **manage-customers**<br>- Trang web thuộc về người dùng hiện tại |
| Post-condition | Thông tin khách hàng được tạo/cập nhật/xóa thành công và thuộc về trang web hiện tại |

---

## Luồng chính (Main Flow)

### Luồng xem danh sách khách hàng

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | SiteAdmin  | Đăng nhập vào hệ thống |
| 2    | Hệ thống   | Kiểm tra vai trò và quyền sở hữu trang web |
| 3    | Hệ thống   | Hiển thị **"Quản lý khách hàng"** trong menu sidebar |
| 4    | SiteAdmin  | Nhấn vào **"Quản lý khách hàng"** |
| 5    | Hệ thống   | Hiển thị danh sách khách hàng thuộc trang web hiện tại |
| 6    | Hệ thống   | Hiển thị tính năng tìm kiếm và lọc |
| 7    | Hệ thống   | Hiển thị nút **"Thêm khách hàng mới"** |

### Luồng tạo khách hàng mới

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 8    | SiteAdmin  | Nhấn nút **"Thêm khách hàng mới"** |
| 9    | Hệ thống   | Hiển thị form tạo khách hàng mới |
| 10   | SiteAdmin  | Nhập thông tin khách hàng |
| 11   | SiteAdmin  | Chọn loại khách hàng |
| 12   | SiteAdmin  | Nhập địa chỉ khách hàng |
| 13   | SiteAdmin  | Nhấn nút **"Lưu"** |
| 14   | Hệ thống   | Kiểm tra dữ liệu và validate |
| 15   | Hệ thống   | Tạo khách hàng và gán vào site_id hiện tại |
| 16   | Hệ thống   | Tạo địa chỉ khách hàng |
| 17   | Hệ thống   | Thông báo thành công |

### Luồng cập nhật thông tin khách hàng

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 18   | SiteAdmin  | Nhấn nút **"Chỉnh sửa"** tại một khách hàng |
| 19   | Hệ thống   | Kiểm tra khách hàng thuộc site hiện tại |
| 20   | Hệ thống   | Hiển thị form chỉnh sửa với dữ liệu hiện tại |
| 21   | SiteAdmin  | Cập nhật thông tin khách hàng |
| 22   | SiteAdmin  | Nhấn nút **"Lưu"** |
| 23   | Hệ thống   | Kiểm tra và cập nhật dữ liệu |
| 24   | Hệ thống   | Thông báo thành công |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Không có quyền **manage-customers** | Không hiển thị menu quản lý khách hàng |
| AF-02| Email đã tồn tại trong site  | Hiển thị lỗi                  |
| AF-03| Số điện thoại đã tồn tại     | Hiển thị cảnh báo (cho phép trùng) |
| AF-04| Thiếu thông tin bắt buộc     | Hiển thị lỗi validation       |
| AF-05| Khách hàng có đơn hàng       | Không cho phép xóa, chỉ cho phép vô hiệu hóa |

---

## Dữ liệu vào / ra

### Dữ liệu vào (Input)
- **Tên khách hàng** (name): Chuỗi, bắt buộc, tối đa 255 ký tự
- **Số điện thoại** (phone): Chuỗi, bắt buộc, định dạng số điện thoại hợp lệ
- **Email** (email): Email, unique trong site, tùy chọn
- **Loại khách hàng** (type): Enum (Cá nhân, Doanh nghiệp), bắt buộc
- **Mô tả** (description): Text, tùy chọn
- **Địa chỉ** (address): Chuỗi, bắt buộc
- **Phường/Xã** (ward_id): ID ward, bắt buộc

### Dữ liệu ra (Output)
- Thông báo thành công/thất bại
- Thông tin khách hàng đã tạo/cập nhật
- Danh sách khách hàng với pagination

---

## Tính năng nâng cao

### Tìm kiếm và lọc
- Tìm kiếm theo tên, email, số điện thoại
- Lọc theo loại khách hàng
- Lọc theo khu vực (province/ward)
- Sắp xếp theo tên, ngày tạo, số đơn hàng

### Báo cáo khách hàng
- Thống kê số lượng khách hàng theo loại
- Top khách hàng theo doanh thu
- Khách hàng mới trong tháng
- Khách hàng lâu không mua hàng

---

## Quy tắc nghiệp vụ

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Email phải là duy nhất trong phạm vi site (nếu có)                                   |
| BR-02 | Số điện thoại phải đúng định dạng (bắt đầu bằng 0, 10-11 số)                        |
| BR-03 | Khách hàng doanh nghiệp có thể có nhiều địa chỉ                                      |
| BR-04 | Khách hàng cá nhân chỉ có một địa chỉ chính                                          |
| BR-05 | Không thể xóa khách hàng đã có đơn hàng                                              |
| BR-06 | Địa chỉ phải thuộc ward hợp lệ trong hệ thống                                        |
| BR-07 | Hệ thống tự động log mọi thay đổi thông tin khách hàng                               |
