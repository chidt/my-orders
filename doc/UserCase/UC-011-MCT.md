# UC011: Manage Categories and Tags

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-011-MCT                                 |
| Tên Use Case   | Quản lý danh mục và thẻ tag sản phẩm      |
| Actor          | SiteAdmin (người dùng có quyền hạn manage-categories) |
| Mô tả          | Người dùng có thể tạo, xem, cập nhật, xóa danh mục và thẻ tag sản phẩm thuộc trang web mà họ sở hữu |
| Độ ưu tiên     | Trung bình                                 |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có quyền hạn **manage-categories**<br>- Trang web thuộc về người dùng hiện tại |
| Post-condition | Danh mục/tag được tạo/cập nhật/xóa thành công và thuộc về trang web hiện tại |

---

## Luồng chính (Main Flow)

### Luồng quản lý danh mục (Categories)

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | SiteAdmin  | Đăng nhập vào hệ thống |
| 2    | Hệ thống   | Kiểm tra vai trò và quyền sở hữu trang web |
| 3    | SiteAdmin  | Truy cập **"Quản lý sản phẩm"** → **"Danh mục"** |
| 4    | Hệ thống   | Hiển thị cây danh mục theo cấu trúc phân cấp |
| 5    | Hệ thống   | Hiển thị nút **"Thêm danh mục"** |

### Luồng tạo danh mục mới

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 6    | SiteAdmin  | Nhấn nút **"Thêm danh mục"** |
| 7    | Hệ thống   | Hiển thị form tạo danh mục |
| 8    | SiteAdmin  | Nhập tên danh mục |
| 9    | SiteAdmin  | Chọn danh mục cha (nếu là danh mục con) |
| 10   | SiteAdmin  | Nhập mô tả |
| 11   | SiteAdmin  | Thiết lập thứ tự hiển thị |
| 12   | SiteAdmin  | Upload ảnh danh mục (tùy chọn) |
| 13   | SiteAdmin  | Nhấn nút **"Lưu"** |
| 14   | Hệ thống   | Tạo danh mục và gán vào site_id hiện tại |
| 15   | Hệ thống   | Thông báo thành công |

### Luồng quản lý thẻ tag

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 16   | SiteAdmin  | Truy cập **"Quản lý sản phẩm"** → **"Thẻ tag"** |
| 17   | Hệ thống   | Hiển thị danh sách tag thuộc site |
| 18   | SiteAdmin  | Nhấn **"Thêm tag mới"** |
| 19   | Hệ thống   | Hiển thị form tạo tag |
| 20   | SiteAdmin  | Nhập tên tag |
| 21   | SiteAdmin  | Nhấn **"Lưu"** |
| 22   | Hệ thống   | Tạo tag và gán vào site_id hiện tại |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Không có quyền **manage-categories** | Không hiển thị menu quản lý danh mục |
| AF-02| Tên danh mục đã tồn tại      | Hiển thị cảnh báo (cho phép trùng) |
| AF-03| Danh mục có sản phẩm         | Không cho phép xóa, chỉ vô hiệu hóa |
| AF-04| Tag đã tồn tại               | Hiển thị cảnh báo |
| AF-05| Tạo danh mục con vô tận      | Giới hạn tối đa 3 cấp |

---

## Dữ liệu vào / ra

### Danh mục (Categories)
- **Tên danh mục** (name): Chuỗi, bắt buộc, tối đa 255 ký tự
- **Mô tả** (description): Text, tùy chọn
- **Thứ tự** (order): Integer, mặc định 0
- **Danh mục cha** (parent_id): ID danh mục cha, tùy chọn
- **Ảnh danh mục**: File ảnh, tùy chọn

### Thẻ tag (Tags)
- **Tên tag** (name): Chuỗi, bắt buộc, tối đa 100 ký tự
- **Màu tag**: Hex color, tùy chọn, mặc định blue

---

## Tính năng nâng cao

### Quản lý cây danh mục
- Hiển thị danh mục dạng cây phân cấp
- Drag & drop để thay đổi cấu trúc
- Sắp xếp thứ tự hiển thị
- Ẩn/hiện danh mục

### Quản lý tag thông minh
- Tự động gợi ý tag khi nhập
- Tag phổ biến nhất
- Gộp tag trùng lặp
- Thống kê sử dụng tag

---

## Giao diện người dùng (UI Requirements)

### Trang quản lý danh mục
- **Cây danh mục**: Hiển thị cấu trúc phân cấp với expand/collapse
- **Form tạo/sửa**: Modal popup hoặc sidebar form
- **Thống kê**: Số sản phẩm trong mỗi danh mục
- **Tìm kiếm**: Tìm kiếm danh mục theo tên

### Trang quản lý tag
- **Danh sách tag**: Grid view với tên, màu, số lượng sử dụng
- **Tạo tag nhanh**: Inline form để tạo tag nhanh
- **Bộ lọc**: Lọc tag theo tên, số lượng sử dụng
- **Tag cloud**: Hiển thị tag phổ biến dạng word cloud

---

## Quy tắc nghiệp vụ

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Tên danh mục có thể trùng trong cùng site                                            |
| BR-02 | Danh mục không được tạo vòng lặp (A → B → A)                                         |
| BR-03 | Giới hạn tối đa 3 cấp danh mục                                                        |
| BR-04 | Không thể xóa danh mục có sản phẩm                                                   |
| BR-05 | Tên tag phải unique trong cùng site                                                   |
| BR-06 | Tag có thể được sử dụng cho nhiều sản phẩm                                           |
| BR-07 | Tự động xóa tag không được sử dụng sau 30 ngày                                       |
| BR-08 | Hệ thống tự động tạo slug từ tên danh mục                                            |
