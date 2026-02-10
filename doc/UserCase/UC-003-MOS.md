# UC003: Manage Own Site

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-003-MOS                                 |
| Tên Use Case   | Quản lý trang web của tôi                  |
| Actor          | SiteAdmin (người dùng có vai trò manage-own-site) |
| Mô tả          | Người dùng có thể cập nhật thông tin về trang web mà họ sở hữu |
| Độ ưu tiên     | Trung bình                                 |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có vai trò **manage-own-site**<br>- Trang web thuộc về người dùng hiện tại (user_id trong bảng sites = current auth user) |
| Post-condition | Thông tin trang web được cập nhật thành công |

---

## Luồng chính (Main Flow)

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | SiteAdmin  | Đăng nhập vào hệ thống |
| 2    | Hệ thống   | Kiểm tra vai trò và quyền sở hữu trang web |
| 3    | Hệ thống   | Hiển thị **"Quản lý trang web"** trong menu sidebar nếu người dùng có quyền |
| 4    | SiteAdmin  | Nhấn vào **"Quản lý trang web"** |
| 5    | Hệ thống   | Hiển thị form chỉnh sửa thông tin trang web |
| 6    | SiteAdmin  | Cập nhật thông tin trang web |
| 7    | SiteAdmin  | Nhấn nút **Lưu** |
| 8    | Hệ thống   | Kiểm tra dữ liệu |
| 9    | Hệ thống   | Cập nhật thông tin vào database |
| 10   | Hệ thống   | Thông báo thành công |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Người dùng không có vai trò **manage-own-site** | Không hiển thị menu "Quản lý trang web" |
| AF-02| Trang web không thuộc về người dùng | Không cho phép truy cập/chỉnh sửa |
| AF-03| Slug đã tồn tại              | Hiển thị lỗi                  |
| AF-04| Dữ liệu không hợp lệ         | Hiển thị lỗi validation       |
| AF-05| Thiếu dữ liệu bắt buộc       | Yêu cầu nhập lại              |

---

## Dữ liệu vào / ra

### Dữ liệu vào (Input)
- **Tên trang web** (name): Chuỗi, bắt buộc, tối đa 255 ký tự
- **Slug trang web** (slug): Chuỗi, bắt buộc, unique, chỉ chứa ký tự a-z, 0-9, dấu gạch ngang
- **Mô tả trang web** (description): Text, tùy chọn
- **Cài đặt trang web** (settings):
  - **Tiền tố mã sản phẩm** (product_prefix): Chuỗi, tùy chọn, 1-5 ký tự
    - Ví dụ: "A" → tạo mã sản phẩm "A001", "A002"...

### Dữ liệu ra (Output)
- Thông báo thành công/thất bại
- Dữ liệu trang web đã được cập nhật

---

## Giao diện người dùng (UI Requirements)

### Sidebar Navigation
- Menu **"Quản lý trang web"** chỉ hiển thị khi:
  - Người dùng có vai trò **manage-own-site**
  - Trang web hiện tại thuộc về người dùng (user_id = current user)

### Form chỉnh sửa trang web
- **Tên trang web**: Input text, required
- **Slug trang web**: Input text, required, với gợi ý format
- **Mô tả**: Textarea, optional
- **Cài đặt sản phẩm**:
  - **Tiền tố mã sản phẩm**: Input text, với ví dụ "A → A001"
- Nút **Lưu** và **Hủy**

---

## Validation Rules

| Trường | Quy tắc |
|--------|---------|
| name   | required, string, max:255 |
| slug   | required, string, unique:sites,slug,{current_id}, regex:/^[a-z0-9-]+$/ |
| description | nullable, string |
| settings.product_prefix | nullable, string, max:5, regex:/^[A-Z0-9]+$/ |

---

## Database Schema

### Bảng sites
- `id`: bigint, primary key
- `name`: varchar(255), not null
- `slug`: varchar(255), unique, not null  
- `description`: text, nullable
- `settings`: json, nullable
- `user_id`: bigint, foreign key to users.id, nullable
- `created_at`: timestamp
- `updated_at`: timestamp

### Settings JSON Structure
```json
{
  "product_prefix": "A"
}
```

---

## Security Requirements

1. **Authorization**: Chỉ cho phép người dùng chỉnh sửa trang web mà họ sở hữu
2. **Role-based Access**: Kiểm tra vai trò **manage-own-site**
3. **Input Validation**: Validate tất cả input từ client
4. **CSRF Protection**: Sử dụng CSRF token cho form

---

## Technical Notes

- Sử dụng Laravel Policy để kiểm tra quyền sở hữu trang web
- Implement middleware để check role **manage-own-site**
- Settings được lưu dưới dạng JSON trong database
- Slug phải unique và follow URL-friendly format
