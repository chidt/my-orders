# UC012: Manage Attributes

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-012-MA                                  |
| Tên Use Case   | Quản lý thuộc tính sản phẩm (Attributes)  |
| Actor          | SiteAdmin (người dùng có quyền hạn manage-attributes) |
| Mô tả          | Người dùng có thể quản lý các thuộc tính sản phẩm như Kích thước, Màu sắc, Loại sản phẩm cho site của mình |
| Độ ưu tiên     | Cao - **PREREQUISITE** cho UC006-MP (Manage Products) |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có quyền hạn **manage-attributes**<br>- Đã có site trong hệ thống |
| Post-condition | Các thuộc tính sản phẩm được tạo/cập nhật/xóa thành công với site isolation |

---

## Luồng chính (Main Flow)

### Luồng xem danh sách Attributes

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | SiteAdmin  | Đăng nhập vào hệ thống |
| 2    | Hệ thống   | Kiểm tra vai trò và quyền sở hữu trang web |
| 3    | SiteAdmin  | Truy cập **"Quản lý sản phẩm"** → **"Thuộc tính sản phẩm"** |
| 4    | Hệ thống   | Hiển thị danh sách Attributes thuộc site hiện tại |
| 5    | Hệ thống   | Hiển thị các cột: Name, Code, Description, Số lượng values |
| 6    | SiteAdmin  | Có thể tìm kiếm, lọc theo tên hoặc code |

### Luồng tạo Attribute mới

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 7    | SiteAdmin  | Nhấn nút **"Tạo Attribute mới"** |
| 8    | Hệ thống   | Hiển thị form tạo Attribute |
| 9    | SiteAdmin  | Nhập **Tên Attribute** (ví dụ: "Kích Thước", "Màu Sắc", "Loại Sản Phẩm") |
| 10   | SiteAdmin  | Nhập **Code** (ví dụ: "size", "color", "product-type") |
| 11   | SiteAdmin  | Nhập **Mô tả** (tùy chọn) |
| 12   | SiteAdmin  | Nhấn nút **"Lưu"** |
| 13   | Hệ thống   | Validate dữ liệu (name, code required và unique trong site) |
| 14   | Hệ thống   | Tạo Attribute với **site_id** của SiteAdmin |
| 15   | Hệ thống   | Thông báo thành công và redirect về danh sách |

### Luồng chỉnh sửa Attribute

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 16   | SiteAdmin  | Nhấn nút **"Chỉnh sửa"** trên một Attribute |
| 17   | Hệ thống   | Kiểm tra quyền sở hữu (attribute.site_id = user.site_id) |
| 18   | Hệ thống   | Hiển thị form chỉnh sửa với dữ liệu hiện tại |
| 19   | SiteAdmin  | Cập nhật thông tin Attribute |
| 20   | SiteAdmin  | Nhấn nút **"Cập nhật"** |
| 21   | Hệ thống   | Validate và lưu thay đổi |
| 22   | Hệ thống   | Thông báo thành công |

### Luồng xóa Attribute

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 23   | SiteAdmin  | Nhấn nút **"Xóa"** trên một Attribute |
| 24   | Hệ thống   | Kiểm tra quyền sở hữu và ràng buộc dữ liệu |
| 25   | Hệ thống   | Hiển thị dialog xác nhận xóa |
| 26   | SiteAdmin  | Xác nhận xóa |
| 27   | Hệ thống   | Xóa Attribute và các ProductAttributeValues liên quan |
| 28   | Hệ thống   | Thông báo thành công |

### Luồng quản lý Attribute Values

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 29   | SiteAdmin  | Nhấn vào **"Quản lý Values"** của một Attribute |
| 30   | Hệ thống   | Hiển thị danh sách ProductAttributeValues cho Attribute đó |
| 31   | SiteAdmin  | Có thể thêm/sửa/xóa các values (ví dụ: S, M, L, XL cho size) |
| 32   | SiteAdmin  | Nhập **Code, Value, Order, Addition Values** cho mỗi value |
| 33   | Hệ thống   | Lưu các ProductAttributeValues với liên kết đến Product |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Không có quyền **manage-attributes** | Không hiển thị menu quản lý attributes |
| AF-02| Code đã tồn tại trong site   | Hiển thị lỗi và yêu cầu nhập code khác |
| AF-03| Attribute không thuộc site   | Không cho phép chỉnh sửa/xóa |
| AF-04| Attribute đang được sử dụng bởi Products | Cảnh báo và không cho phép xóa |
| AF-05| Tên Attribute trống          | Hiển thị lỗi validation |

---

## Dữ liệu vào / ra

### Attribute (Thuộc tính sản phẩm)
- **Tên Attribute** (name): String, bắt buộc (ví dụ: "Kích Thước", "Màu Sắc")
- **Code** (code): String, bắt buộc, unique trong site (ví dụ: "size", "color")
- **Mô tả** (description): Text, tùy chọn
- **Site ID** (site_id): ID site, tự động gán

### Ví dụ Attributes phổ biến
- **Kích Thước (Size)**: Code "size", Values: S, M, L, XL, XXL
- **Màu Sắc (Color)**: Code "color", Values: Đỏ, Xanh, Vàng, Đen, Trắng
- **Loại Sản Phẩm**: Code "product-type", Values: Áo, Quần, Giày, Phụ kiện
- **Chất Liệu**: Code "material", Values: Cotton, Polyester, Jean, Da
- **Thương Hiệu**: Code "brand", Values: Nike, Adidas, Zara, H&M

---

## Quy tắc nghiệp vụ

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | **Attribute Code** phải unique trong cùng một site                                    |
| BR-02 | **Attribute Name** phải unique trong cùng một site                                    |
| BR-03 | Chỉ SiteAdmin của site đó mới có thể CRUD Attributes của site                       |
| BR-04 | **Site Isolation**: Mỗi site chỉ thấy và quản lý Attributes của mình                |
| BR-05 | Không thể xóa Attribute nếu đang được sử dụng bởi ProductAttributeValues             |
| BR-06 | **Code format**: lowercase, kebab-case, không có ký tự đặc biệt                      |
| BR-07 | **Name format**: có thể có dấu tiếng Việt, viết hoa chữ cái đầu                      |
| BR-08 | Attribute phải được tạo trước khi có thể gán cho Product                              |

---

## Báo cáo và phân tích

### Thống kê Attributes
- Tổng số Attributes theo site
- Attributes được sử dụng nhiều nhất
- Attributes chưa có ProductAttributeValues
- Số lượng Products sử dụng mỗi Attribute

### Báo cáo sử dụng
- Danh sách Products theo Attribute
- Phân tích độ phổ biến của các Attribute Values
- Attributes cần được cập nhật hoặc tối ưu

---

## Mối quan hệ với Use Cases khác

### **Dependencies (Phụ thuộc)**
- **UC001-REG**: Cần có User và Site trước
- **UC002-LOG**: Cần có authentication system

### **Dependents (Use Cases phụ thuộc vào UC012)**
- **UC006-MP**: Manage Products (cần Attributes để tạo variants)
- **UC008-MO**: Order Management (hiển thị attributes trong orders)
- **UC010-MI**: Inventory (tracking theo attributes)

### **Integration Points**
- **Product Creation**: Chọn Attributes và tạo ProductAttributeValues
- **Product Variants**: Tạo ProductItems dựa trên attribute combinations
- **Order Processing**: Hiển thị product attributes trong order details
- **Inventory Management**: Track inventory theo attribute combinations

---

## Tính năng nâng cao

### Attribute Templates
- **Predefined Templates**: Có sẵn templates cho các loại sản phẩm phổ biến
- **Import/Export**: Import attributes từ file Excel hoặc CSV
- **Bulk Operations**: Tạo nhiều attributes cùng lúc

### Attribute Validation
- **Data Type Validation**: String, Number, Boolean, Date cho values
- **Pattern Validation**: Regex validation cho attribute codes
- **Business Rules**: Custom validation rules cho specific attributes

### Advanced Features
- **Attribute Groups**: Nhóm các attributes liên quan (ví dụ: Physical, Style, Technical)
- **Conditional Attributes**: Attributes chỉ hiện khi có điều kiện nhất định
- **Attribute Dependencies**: Một attribute phụ thuộc vào value của attribute khác

---

## UI/UX Requirements

### Attribute Management Interface
- **List View**: Table view với search, filter, pagination
- **Form Interface**: Clean, intuitive forms với validation
- **Bulk Actions**: Select multiple attributes cho bulk operations
- **Quick Actions**: Inline editing, quick delete confirmations

### Integration với Product Management
- **Attribute Selection**: Multi-select với search trong product forms
- **Value Management**: Inline editing của attribute values
- **Preview**: Preview product với selected attributes
- **Validation**: Real-time validation của attribute combinations

This use case ensures that each site can manage their own product attributes independently, supporting the multi-tenant architecture while providing flexibility for different business needs.
