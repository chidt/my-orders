# UC005: Manage Warehouse Location

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-005-MWL                                 |
| Tên Use Case   | Quản lý vị trí trong kho                   |
| Actor          | SiteAdmin (người dùng có vai trò manage-warehouse-locations) |
| Mô tả          | Người dùng có thể tạo, xem, cập nhật, xóa vị trí trong kho thuộc trang web mà họ sở hữu |
| Độ ưu tiên     | Cao                                        |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có vai trò **manage-warehouse-locations**<br>- Kho thuộc về trang web của người dùng hiện tại<br>- Kho đã được tạo trước đó |
| Post-condition | Vị trí trong kho được tạo/cập nhật/xóa thành công và thuộc về kho hợp lệ |

---

## Luồng chính (Main Flow)

### Luồng xem danh sách vị trí trong kho

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | SiteAdmin  | Đăng nhập vào hệ thống |
| 2    | Hệ thống   | Kiểm tra vai trò và quyền sở hữu kho |
| 3    | SiteAdmin  | Truy cập **"Quản lý kho"** → Chọn kho → **"Quản lý vị trí"** |
| 4    | Hệ thống   | Kiểm tra kho thuộc trang web hiện tại |
| 5    | Hệ thống   | Hiển thị danh sách vị trí thuộc kho được chọn |
| 6    | Hệ thống   | Hiển thị nút **"Thêm vị trí mới"** |

### Luồng tạo vị trí mới

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 7    | SiteAdmin  | Nhấn nút **"Thêm vị trí mới"** |
| 8    | Hệ thống   | Hiển thị form tạo vị trí mới |
| 9    | SiteAdmin  | Nhập thông tin vị trí |
| 10   | SiteAdmin  | Chọn có phải vị trí mặc định không |
| 11   | SiteAdmin  | Nhấn nút **"Lưu"** |
| 12   | Hệ thống   | Kiểm tra dữ liệu |
| 13   | Hệ thống   | Tạo vị trí mới và gán vào warehouse_id hiện tại |
| 14   | Hệ thống   | Nếu được chọn làm mặc định, cập nhật các vị trí khác thành không mặc định |
| 15   | Hệ thống   | Thông báo thành công và quay về danh sách |

### Luồng cập nhật vị trí

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 16   | SiteAdmin  | Nhấn nút **"Chỉnh sửa"** tại một vị trí |
| 17   | Hệ thống   | Kiểm tra vị trí thuộc kho hợp lệ |
| 18   | Hệ thống   | Hiển thị form chỉnh sửa với dữ liệu hiện tại |
| 19   | SiteAdmin  | Cập nhật thông tin vị trí |
| 20   | SiteAdmin  | Nhấn nút **"Lưu"** |
| 21   | Hệ thống   | Kiểm tra dữ liệu |
| 22   | Hệ thống   | Cập nhật thông tin vị trí |
| 23   | Hệ thống   | Nếu thay đổi trạng thái mặc định, cập nhật các vị trí khác |
| 24   | Hệ thống   | Thông báo thành công |

### Luồng xóa vị trí

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 25   | SiteAdmin  | Nhấn nút **"Xóa"** tại một vị trí |
| 26   | Hệ thống   | Kiểm tra vị trí thuộc kho hợp lệ |
| 27   | Hệ thống   | Kiểm tra vị trí có đang được sử dụng không |
| 28   | Hệ thống   | Hiển thị popup xác nhận xóa |
| 29   | SiteAdmin  | Xác nhận xóa |
| 30   | Hệ thống   | Xóa vị trí khỏi database |
| 31   | Hệ thống   | Nếu vị trí bị xóa là mặc định, tự động chọn vị trí khác làm mặc định |
| 32   | Hệ thống   | Thông báo thành công |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Người dùng không có vai trò **manage-warehouse-locations** | Không hiển thị chức năng quản lý vị trí |
| AF-02| Kho không thuộc về trang web hiện tại | Không cho phép truy cập |
| AF-03| Mã vị trí đã tồn tại trong kho | Hiển thị lỗi                  |
| AF-04| Dữ liệu không hợp lệ         | Hiển thị lỗi validation       |
| AF-05| Thiếu dữ liệu bắt buộc       | Yêu cầu nhập lại              |
| AF-06| Vị trí đang có tồn kho       | Không cho phép xóa            |
| AF-07| Vị trí đang được sử dụng trong giao dịch | Không cho phép xóa |
| AF-08| Xóa vị trí mặc định duy nhất | Yêu cầu chọn vị trí khác làm mặc định trước |
| AF-09| Không có vị trí nào trong kho | Tự động tạo vị trí mặc định   |

---

## Dữ liệu vào / ra

### Dữ liệu vào (Input)
- **Mã vị trí** (code): Chuỗi, bắt buộc, unique trong phạm vi warehouse, tối đa 50 ký tự
- **Tên vị trí** (name): Chuỗi, bắt buộc, tối đa 255 ký tự
- **Là vị trí mặc định** (is_default): Boolean, mặc định false
- **ID kho** (warehouse_id): Được tự động gán từ context

### Dữ liệu ra (Output)
- Danh sách vị trí thuộc kho
- Thông báo thành công/thất bại
- Dữ liệu vị trí đã được tạo/cập nhật
- Số lượng tồn kho tại vị trí (qty_in_stock)

---

## Giao diện người dùng (UI Requirements)

### Navigation Flow
- **Quản lý kho** → Chọn kho → **"Quản lý vị trí"**
- Breadcrumb: Quản lý kho > [Tên kho] > Quản lý vị trí

### Trang danh sách vị trí
- Hiển thị thông tin kho hiện tại (tên, mã)
- Bảng hiển thị danh sách vị trí với các cột:
  - Mã vị trí
  - Tên vị trí
  - Là mặc định (badge)
  - Số lượng tồn kho
  - Hành động (Chỉnh sửa, Xóa)
- Nút **"Thêm vị trí mới"**
- Nút **"Quay lại danh sách kho"**

### Form tạo/chỉnh sửa vị trí
- **Mã vị trí**: Input text, required, với gợi ý format (A1, B2, etc.)
- **Tên vị trí**: Input text, required
- **Là vị trí mặc định**: Checkbox với cảnh báo về việc thay đổi
- Hiển thị thông tin kho hiện tại (read-only)
- Nút **"Lưu"** và **"Hủy"**

---

## Validation Rules

| Trường | Quy tắc |
|--------|---------|
| code   | required, string, max:50, unique trong phạm vi warehouse_id |
| name   | required, string, max:255 |
| is_default | boolean |
| warehouse_id | required, exists:warehouses,id, thuộc site hiện tại |

---

## Database Schema

### Bảng locations
- `id`: bigint, primary key
- `code`: varchar(50), not null
- `name`: varchar(255), not null
- `is_default`: boolean, default false
- `warehouse_id`: bigint, foreign key to warehouses.id, not null
- `qty_in_stock`: int, default 0
- `created_at`: timestamp
- `updated_at`: timestamp

### Unique Index
- `unique(warehouse_id, code)` - Mã vị trí unique trong phạm vi warehouse

### Business Rules Index
- Index trên `warehouse_id, is_default` để query vị trí mặc định nhanh

---

## Security Requirements

1. **Authorization**: Chỉ cho phép người dùng quản lý vị trí thuộc kho của trang web mà họ sở hữu
2. **Permission-based Access**: Kiểm tra quyền hạn **manage_warehouse_locations**
3. **Input Validation**: Validate tất cả input từ client
4. **CSRF Protection**: Sử dụng CSRF token cho form
5. **Cascading Authorization**: Kiểm tra quyền sở hữu kho trước khi cho phép thao tác vị trí

---

## Quy tắc nghiệp vụ

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Mã vị trí phải là duy nhất trong phạm vi kho                                          |
| BR-02 | Mỗi kho phải có ít nhất một vị trí mặc định                                           |
| BR-03 | Chỉ được có một vị trí mặc định trong mỗi kho                                         |
| BR-04 | Không được xóa vị trí nếu đang có tồn kho (qty_in_stock > 0)                         |
| BR-05 | Không được xóa vị trí nếu đang được sử dụng trong giao dịch kho                       |
| BR-06 | Khi tạo kho mới, tự động tạo vị trí mặc định                                          |
| BR-07 | Khi xóa vị trí mặc định, phải chọn vị trí khác làm mặc định                          |
| BR-08 | Chỉ SiteAdmin của trang web mới có quyền quản lý vị trí thuộc kho của trang web đó   |

---

## Technical Notes

- Sử dụng Laravel Policy với nested authorization (Site → Warehouse → Location)
- Implement middleware để check permission **manage_warehouse_locations**
- Khi tạo vị trí mới, kiểm tra và cập nhật trạng thái is_default
- Implement database transaction khi thay đổi vị trí mặc định
- Sử dụng database constraints để đảm bảo business rules
- Có thể implement soft delete cho locations
- Cache thông tin vị trí mặc định để tăng performance

---

## Integration Points

### Với UC004 (Manage Site Warehouse)
- Truy cập từ trang quản lý kho
- Kiểm tra quyền sở hữu kho trước khi cho phép quản lý vị trí

### Với hệ thống quản lý tồn kho
- Hiển thị số lượng tồn kho tại mỗi vị trí
- Kiểm tra ràng buộc trước khi xóa vị trí

### Với hệ thống xuất/nhập kho
- Sử dụng vị trí mặc định khi không chỉ định vị trí cụ thể
- Validate vị trí thuộc kho đúng khi tạo phiếu xuất/nhập

