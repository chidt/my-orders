# UC009: Manage Suppliers

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-009-MS                                  |
| Tên Use Case   | Quản lý nhà cung cấp                       |
| Actor          | SiteAdmin (người dùng có quyền hạn manage-suppliers) |
| Mô tả          | Người dùng có thể tạo, xem, cập nhật, xóa thông tin nhà cung cấp thuộc trang web mà họ sở hữu |
| Độ ưu tiên     | Trung bình                                 |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có quyền hạn **manage-suppliers**<br>- Trang web thuộc về người dùng hiện tại |
| Post-condition | Thông tin nhà cung cấp được tạo/cập nhật/xóa thành công và thuộc về trang web hiện tại |

---

## Luồng chính (Main Flow)

### Luồng xem danh sách nhà cung cấp

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | SiteAdmin  | Đăng nhập vào hệ thống |
| 2    | Hệ thống   | Kiểm tra vai trò và quyền sở hữu trang web |
| 3    | Hệ thống   | Hiển thị **"Quản lý nhà cung cấp"** trong menu sidebar |
| 4    | SiteAdmin  | Nhấn vào **"Quản lý nhà cung cấp"** |
| 5    | Hệ thống   | Hiển thị danh sách nhà cung cấp thuộc trang web hiện tại |
| 6    | Hệ thống   | Hiển thị tính năng tìm kiếm |
| 7    | Hệ thống   | Hiển thị nút **"Thêm nhà cung cấp mới"** |

### Luồng tạo nhà cung cấp mới

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 8    | SiteAdmin  | Nhấn nút **"Thêm nhà cung cấp mới"** |
| 9    | Hệ thống   | Hiển thị form tạo nhà cung cấp mới |
| 10   | SiteAdmin  | Nhập thông tin nhà cung cấp |
| 11   | SiteAdmin  | Nhập thông tin liên hệ |
| 12   | SiteAdmin  | Nhấn nút **"Lưu"** |
| 13   | Hệ thống   | Kiểm tra dữ liệu và validate |
| 14   | Hệ thống   | Tạo nhà cung cấp và gán vào site_id hiện tại |
| 15   | Hệ thống   | Thông báo thành công |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Không có quyền **manage-suppliers** | Không hiển thị menu quản lý nhà cung cấp |
| AF-02| Tên nhà cung cấp đã tồn tại  | Hiển thị cảnh báo (cho phép trùng) |
| AF-03| Thiếu thông tin bắt buộc     | Hiển thị lỗi validation       |
| AF-04| Nhà cung cấp có sản phẩm     | Không cho phép xóa, chỉ vô hiệu hóa |

---

## Dữ liệu vào / ra

### Dữ liệu vào (Input)
- **Tên nhà cung cấp** (name): Chuỗi, bắt buộc, tối đa 255 ký tự
- **Người phụ trách** (person_in_charge): Chuỗi, tùy chọn, tối đa 255 ký tự
- **Số điện thoại** (phone): Chuỗi, tùy chọn, định dạng số điện thoại
- **Địa chỉ** (address): Chuỗi, tùy chọn
- **Mô tả** (description): Text, tùy chọn

### Dữ liệu ra (Output)
- Thông báo thành công/thất bại
- Thông tin nhà cung cấp đã tạo/cập nhật
- Danh sách nhà cung cấp với thống kê số sản phẩm

---

## Tính năng nâng cao

### Thống kê nhà cung cấp
- Số lượng sản phẩm theo nhà cung cấp
- Tổng giá trị nhập hàng
- Nhà cung cấp hoạt động/không hoạt động
- Lịch sử giao dịch với nhà cung cấp

---

## Quy tắc nghiệp vụ

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Tên nhà cung cấp có thể trùng trong cùng site                                        |
| BR-02 | Số điện thoại phải đúng định dạng (nếu có)                                           |
| BR-03 | Không thể xóa nhà cung cấp đã có sản phẩm                                            |
| BR-04 | Chỉ có thể vô hiệu hóa nhà cung cấp đã có sản phẩm                                   |
| BR-05 | Hệ thống tự động log mọi thay đổi thông tin nhà cung cấp                             |
