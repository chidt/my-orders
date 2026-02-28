# UC004: Manage Site Warehouse

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-004-MSW                                 |
| Tên Use Case   | Quản lý kho của trang web                  |
| Actor          | SiteAdmin (người dùng có quyền hạn manage_warehouses) |
| Mô tả          | Người dùng có thể tạo, xem, cập nhật, xóa kho thuộc trang web mà họ sở hữu |
| Độ ưu tiên     | Cao                                        |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có quyền hạn **manage_warehouses**<br>- Trang web thuộc về người dùng hiện tại (user_id trong bảng sites = current auth user) |
| Post-condition | Kho được tạo/cập nhật/xóa thành công và thuộc về trang web hiện tại |

---

## Luồng chính (Main Flow)

### Luồng xem danh sách kho

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | SiteAdmin  | Đăng nhập vào hệ thống |
| 2    | Hệ thống   | Kiểm tra vai trò và quyền sở hữu trang web |
| 3    | Hệ thống   | Hiển thị **"Quản lý kho"** trong menu sidebar nếu người dùng có quyền |
| 4    | SiteAdmin  | Nhấn vào **"Quản lý kho"** |
| 5    | Hệ thống   | Hiển thị danh sách kho thuộc trang web hiện tại |
| 6    | Hệ thống   | Hiển thị nút **"Thêm kho mới"** |

### Luồng tạo kho mới

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 7    | SiteAdmin  | Nhấn nút **"Thêm kho mới"** |
| 8    | Hệ thống   | Hiển thị form tạo kho mới |
| 9    | SiteAdmin  | Nhập thông tin kho |
| 10   | SiteAdmin  | Nhấn nút **"Lưu"** |
| 11   | Hệ thống   | Kiểm tra dữ liệu |
| 12   | Hệ thống   | Tạo kho mới và gán vào site_id hiện tại |
| 13   | Hệ thống   | Thông báo thành công và quay về danh sách |

### Luồng cập nhật kho

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 14   | SiteAdmin  | Nhấn nút **"Chỉnh sửa"** tại một kho |
| 15   | Hệ thống   | Kiểm tra kho thuộc trang web hiện tại |
| 16   | Hệ thống   | Hiển thị form chỉnh sửa với dữ liệu hiện tại |
| 17   | SiteAdmin  | Cập nhật thông tin kho |
| 18   | SiteAdmin  | Nhấn nút **"Lưu"** |
| 19   | Hệ thống   | Kiểm tra dữ liệu |
| 20   | Hệ thống   | Cập nhật thông tin kho |
| 21   | Hệ thống   | Thông báo thành công |

### Luồng xóa kho

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 22   | SiteAdmin  | Nhấn nút **"Xóa"** tại một kho |
| 23   | Hệ thống   | Kiểm tra kho thuộc trang web hiện tại |
| 24   | Hệ thống   | Kiểm tra kho có vị trí (locations) hay không |
| 25   | Hệ thống   | Hiển thị popup xác nhận xóa |
| 26   | SiteAdmin  | Xác nhận xóa |
| 27   | Hệ thống   | Xóa kho khỏi database |
| 28   | Hệ thống   | Thông báo thành công |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Người dùng không có quyền hạn **manage_warehouses** | Không hiển thị menu "Quản lý kho" |
| AF-02| Kho không thuộc về trang web hiện tại | Không cho phép truy cập/chỉnh sửa/xóa |
| AF-03| Mã kho đã tồn tại trong trang web | Hiển thị lỗi                  |
| AF-04| Dữ liệu không hợp lệ         | Hiển thị lỗi validation       |
| AF-05| Thiếu dữ liệu bắt buộc       | Yêu cầu nhập lại              |
| AF-06| Kho đang có vị trí (locations) | Không cho phép xóa, yêu cầu xóa vị trí trước |
| AF-07| Kho đang được sử dụng trong giao dịch | Không cho phép xóa |

---

## Dữ liệu vào / ra

### Dữ liệu vào (Input)
- **Mã kho** (code): Chuỗi, bắt buộc, unique trong phạm vi site, tối đa 50 ký tự
- **Tên kho** (name): Chuỗi, bắt buộc, tối đa 255 ký tự
- **Địa chỉ kho** (address): Text, bắt buộc

### Dữ liệu ra (Output)
- Danh sách kho thuộc trang web
- Thông báo thành công/thất bại
- Dữ liệu kho đã được tạo/cập nhật

---

## Giao diện người dùng (UI Requirements)

### Sidebar Navigation
- Menu **"Quản lý kho"** chỉ hiển thị khi:
  - Người dùng có quyền hạn **manage_warehouses**
  - Trang web hiện tại thuộc về người dùng

### Trang danh sách kho
- Bảng hiển thị danh sách kho với các cột:
  - Mã kho
  - Tên kho
  - Địa chỉ
  - Số lượng vị trí
  - Hành động (Chỉnh sửa, Xóa, Quản lý vị trí)
- Nút **"Thêm kho mới"**
- Tìm kiếm và phân trang

### Form tạo/chỉnh sửa kho
- **Mã kho**: Input text, required, với gợi ý format
- **Tên kho**: Input text, required
- **Địa chỉ**: Textarea, required
- Nút **"Lưu"** và **"Hủy"**

---

## Validation Rules

| Trường | Quy tắc |
|--------|---------|
| code   | required, string, max:50, unique trong phạm vi site_id |
| name   | required, string, max:255 |
| address | required, string |

---

## Database Schema

### Bảng warehouses
- `id`: bigint, primary key
- `code`: varchar(50), not null
- `name`: varchar(255), not null
- `address`: text, not null
- `site_id`: bigint, foreign key to sites.id, nullable
- `created_at`: timestamp
- `updated_at`: timestamp

### Unique Index
- `unique(site_id, code)` - Mã kho unique trong phạm vi site

---

## Security Requirements

1. **Authorization**: Chỉ cho phép người dùng quản lý kho thuộc trang web mà họ sở hữu
2. **Permission-based Access**: Kiểm tra quyền hạn **manage_warehouses**
3. **Input Validation**: Validate tất cả input từ client
4. **CSRF Protection**: Sử dụng CSRF token cho form
5. **Soft Delete**: Có thể sử dụng soft delete thay vì hard delete

---

## Quy tắc nghiệp vụ

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Mã kho phải là duy nhất trong phạm vi trang web                                       |
| BR-02 | Kho chỉ thuộc về một trang web duy nhất                                               |
| BR-03 | Không được xóa kho nếu đang có vị trí (locations)                                     |
| BR-04 | Không được xóa kho nếu đang được sử dụng trong giao dịch kho                          |
| BR-05 | Chỉ SiteAdmin của trang web mới có quyền quản lý kho thuộc trang web đó              |

---

## Technical Notes

- Sử dụng Laravel Policy để kiểm tra quyền sở hữu kho
- Implement middleware để check permission **manage_warehouses**
- Khi tạo kho mới, tự động gán `site_id` = current user's site
- Implement cascading rules khi xóa kho (kiểm tra dependencies)
- Có thể tạo location mặc định khi tạo kho mới

