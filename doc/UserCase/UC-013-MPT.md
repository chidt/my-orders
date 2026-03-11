# UC013: Manage Product Types

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-013-MPT                                 |
| Tên Use Case   | Quản lý loại sản phẩm                      |
| Actor          | SiteAdmin (người dùng có quyền hạn manage_product_types) |
| Mô tả          | Người dùng có thể tạo, xem, cập nhật, xóa thông tin loại sản phẩm thuộc trang web mà họ sở hữu |
| Độ ưu tiên     | Cao (Prerequisites cho UC006-MP)           |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có quyền hạn **manage_product_types**<br>- Trang web thuộc về người dùng hiện tại |
| Post-condition | Thông tin loại sản phẩm được tạo/cập nhật/xóa thành công và thuộc về trang web hiện tại |

---

## Luồng chính (Main Flow)

### Luồng xem danh sách loại sản phẩm

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | SiteAdmin  | Đăng nhập vào hệ thống |
| 2    | Hệ thống   | Kiểm tra vai trò và quyền sở hữu trang web |
| 3    | Hệ thống   | Hiển thị **"Quản lý loại sản phẩm"** trong menu sidebar |
| 4    | SiteAdmin  | Nhấn vào **"Quản lý loại sản phẩm"** |
| 5    | Hệ thống   | Hiển thị danh sách loại sản phẩm thuộc trang web hiện tại |
| 6    | Hệ thống   | Hiển thị tính năng tìm kiếm và sắp xếp |
| 7    | Hệ thống   | Hiển thị nút **"Thêm loại sản phẩm mới"** |

### Luồng tạo loại sản phẩm mới

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 8    | SiteAdmin  | Nhấn nút **"Thêm loại sản phẩm mới"** |
| 9    | Hệ thống   | Hiển thị form tạo loại sản phẩm mới |
| 10   | SiteAdmin  | Nhập tên loại sản phẩm |
| 11   | SiteAdmin  | Chọn màu sắc hiển thị |
| 12   | SiteAdmin  | Thiết lập thứ tự sắp xếp |
| 13   | SiteAdmin  | Chọn hiển thị trên trang chủ (nếu muốn) |
| 14   | SiteAdmin  | Nhấn nút **"Lưu"** |
| 15   | Hệ thống   | Kiểm tra dữ liệu và validate |
| 16   | Hệ thống   | Tạo loại sản phẩm và gán vào site_id hiện tại |
| 17   | Hệ thống   | Thông báo thành công và redirect về danh sách |

### Luồng cập nhật loại sản phẩm

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 18   | SiteAdmin  | Từ danh sách, nhấn nút **"Chỉnh sửa"** |
| 19   | Hệ thống   | Hiển thị form chỉnh sửa với dữ liệu hiện tại |
| 20   | SiteAdmin  | Cập nhật thông tin cần thiết |
| 21   | SiteAdmin  | Nhấn nút **"Cập nhật"** |
| 22   | Hệ thống   | Validate và lưu thay đổi |
| 23   | Hệ thống   | Thông báo thành công |

### Luồng xóa loại sản phẩm

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 24   | SiteAdmin  | Từ danh sách, nhấn nút **"Xóa"** |
| 25   | Hệ thống   | Kiểm tra xem có sản phẩm nào đang sử dụng loại này không |
| 26   | Hệ thống   | Hiển thị dialog xác nhận xóa |
| 27   | SiteAdmin  | Xác nhận xóa |
| 28   | Hệ thống   | Xóa loại sản phẩm và thông báo thành công |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Không có quyền **manage_product_types** | Không hiển thị menu quản lý loại sản phẩm |
| AF-02| Tên loại sản phẩm đã tồn tại trong site | Hiển thị lỗi validation |
| AF-03| Thiếu thông tin bắt buộc     | Hiển thị lỗi validation       |
| AF-04| Loại sản phẩm có sản phẩm đang sử dụng | Không cho phép xóa, hiển thị cảnh báo |
| AF-05| Màu sắc không hợp lệ         | Hiển thị lỗi validation       |
| AF-06| Thứ tự sắp xếp âm            | Hiển thị lỗi validation       |

---

## Dữ liệu vào / ra

### Dữ liệu vào (Input)
- **Tên loại sản phẩm** (name): Chuỗi, bắt buộc, tối đa 100 ký tự, unique trong site
- **Thứ tự** (order): Số nguyên, tùy chọn, mặc định 0, >= 0
- **Hiển thị trang chủ** (show_on_front): Boolean, tùy chọn, mặc định false
- **Màu sắc** (color): Chuỗi hex color, tùy chọn, mặc định '#3b82f6'

### Dữ liệu ra (Output)
- Thông báo thành công/thất bại
- Thông tin loại sản phẩm đã tạo/cập nhật
- Danh sách loại sản phẩm với số lượng sản phẩm theo loại
- Preview màu sắc trong danh sách

---

## Tính năng nâng cao

### Quản lý hiển thị
- Sắp xếp thứ tự hiển thị bằng drag & drop
- Bulk activate/deactivate cho hiển thị trang chủ
- Preview giao diện trang chủ với các loại sản phẩm được chọn

### Thống kê loại sản phẩm
- Số lượng sản phẩm theo từng loại
- Loại sản phẩm phổ biến nhất
- Biểu đồ phân bổ sản phẩm theo loại
- Export danh sách loại sản phẩm

### Tìm kiếm & Lọc
- Tìm kiếm theo tên loại sản phẩm
- Lọc theo trạng thái hiển thị trang chủ
- Sắp xếp theo tên, thứ tự, số lượng sản phẩm
- Lọc theo màu sắc

---

## Quy tắc nghiệp vụ

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Tên loại sản phẩm phải unique trong cùng site                                        |
| BR-02 | Thứ tự sắp xếp phải là số nguyên không âm                                            |
| BR-03 | Không thể xóa loại sản phẩm đã có sản phẩm sử dụng                                   |
| BR-04 | Màu sắc phải là mã hex hợp lệ (#RRGGBB)                                              |
| BR-05 | Mặc định có 1 loại sản phẩm "General" khi tạo site mới                              |
| BR-06 | Tối đa 20 loại sản phẩm có thể hiển thị trên trang chủ                              |
| BR-07 | Hệ thống tự động log mọi thay đổi thông tin loại sản phẩm                           |

---

## Giao diện người dùng

### Danh sách loại sản phẩm
- Table view với columns: Tên, Màu sắc (preview), Thứ tự, Hiển thị trang chủ, Số sản phẩm, Actions
- Pagination khi > 20 items
- Search bar ở đầu trang
- Filter toggles cho "Hiển thị trang chủ"

### Form tạo/chỉnh sửa
- **Tên loại sản phẩm**: Text input với validation realtime
- **Màu sắc**: Color picker với preview
- **Thứ tự**: Number input với spinner
- **Hiển thị trang chủ**: Toggle switch
- **Preview**: Hiển thị badge với màu và tên đã chọn

### Actions
- **Thêm mới**: Primary button màu xanh
- **Chỉnh sửa**: Icon button với tooltip
- **Xóa**: Icon button màu đỏ với confirm dialog
- **Sắp xếp**: Drag & drop handles

---

## Kiến trúc kỹ thuật

### Database Schema
```sql
-- Cập nhật bảng product_types
ALTER TABLE product_types ADD COLUMN site_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE product_types ADD FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE;
ALTER TABLE product_types ADD UNIQUE KEY unique_name_per_site (name, site_id);
```

### API Endpoints
- `GET /api/product-types` - Danh sách loại sản phẩm
- `POST /api/product-types` - Tạo loại sản phẩm mới  
- `GET /api/product-types/{id}` - Chi tiết loại sản phẩm
- `PUT /api/product-types/{id}` - Cập nhật loại sản phẩm
- `DELETE /api/product-types/{id}` - Xóa loại sản phẩm
- `POST /api/product-types/reorder` - Sắp xếp lại thứ tự

### Models & Relationships
```php
// ProductType Model
class ProductType extends Model
{
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
    
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_type');
    }
}
```

---

## Test Cases

### Functional Tests
1. **Tạo loại sản phẩm mới thành công**
2. **Validation tên trống**
3. **Validation tên trùng lặp trong site**
4. **Validation màu sắc không hợp lệ**
5. **Cập nhật thông tin thành công**
6. **Xóa loại sản phẩm không có sản phẩm**
7. **Không thể xóa loại sản phẩm có sản phẩm**
8. **Site isolation - không thấy loại sản phẩm của site khác**

### Permission Tests
1. **User không có quyền không thấy menu**
2. **User có quyền thấy đầy đủ chức năng**
3. **Site owner chỉ thấy data thuộc site mình**

### UI/UX Tests
1. **Drag & drop sắp xếp thứ tự**
2. **Color picker hoạt động chính xác**
3. **Search và filter hoạt động**
4. **Responsive trên mobile**

---

## Dependencies & Prerequisites

### Required Before Implementation
- ✅ UC001-REG: User Registration (cần Users & Sites)
- ✅ UC002-LOG: Login (cần Authentication system)
- ✅ Database migration cho bảng product_types với site_id

### Blocks/Enables
- **Enables**: UC006-MP (Manage Products) - Products cần ProductType
- **Dependency for**: Product creation, Product categorization

### Implementation Priority
- **Phase**: 3 (Product Prerequisites)
- **Priority**: HIGH
- **Timeline**: Week 5 (parallel với UC011-MCT, UC012-MA, UC009-MS)

---

## Success Criteria

### Functional Requirements ✅
- [x] CRUD operations cho loại sản phẩm
- [x] Site isolation và multi-tenancy
- [x] Permission-based access control
- [x] Drag & drop reordering
- [x] Color management với preview
- [x] Search và filtering

### Technical Requirements ✅
- [x] RESTful API endpoints
- [x] Proper validation và error handling
- [x] Database constraints và foreign keys
- [x] Activity logging
- [x] Test coverage > 90%
- [x] Mobile-responsive UI

### Business Requirements ✅
- [x] Unique names per site
- [x] Cannot delete types in use
- [x] Default "General" type for new sites
- [x] Frontend display configuration
- [x] Usage statistics
