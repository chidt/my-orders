# UC001: User registration

## Thông tin Use Case

| Thuộc tính | Nội dung |
|-----------|----------|
| Use Case ID | UC-REG-01 |
| Tên Use Case | Đăng ký người dùng |
| Actor | Người dùng |
| Mô tả | Người dùng tạo tài khoản mới trên hệ thống |
| Độ ưu tiên | Cao |

---

## Điều kiện

| Loại | Mô tả |
|------|------|
| Pre-condition | Người dùng chưa đăng nhập |
| Post-condition | Tài khoản được tạo thành công |

---

## Luồng chính (Main Flow)

| Bước | Actor | Hành động |
|------|-------|----------|
| 1 | Người dùng | Truy cập trang đăng ký |
| 2 | Hệ thống | Hiển thị form đăng ký |
| 3 | Người dùng | Nhập thông tin đăng ký |
| 4 | Người dùng | Nhấn nút **Đăng ký** |
| 5 | Hệ thống | Kiểm tra dữ liệu |
| 6 | Hệ thống | Tạo tài khoản |
| 7 | Hệ thống | Thông báo thành công |

---

## Luồng thay thế / Ngoại lệ

| Mã | Điều kiện | Kết quả |
|----|----------|--------|
| AF-01 | Email đã tồn tại | Hiển thị lỗi |
| AF-02 | Mật khẩu không hợp lệ | Hiển thị lỗi |
| AF-03 | Thiếu dữ liệu | Yêu cầu nhập lại |

---

## Dữ liệu vào / ra

| Loại  | Trường              | Bắt buộc |
|-------|---------------------|---------|
| Input | name                | R       |
| Input | email               | R       |
| Input | phone_number        | R       |
| Input | password            | R       |
| Input | password_confirmation | R      |
| Input | address             | R       |
| Input | site_name           | R       |
| Input | site_slug           | R       |
| Input | site_description    |         |


---

## Quy tắc nghiệp vụ

- Tạo và sử dụng HasAddress trait ở backend để gán địa chỉ cho model

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Email phải là duy nhất                                                                |
| BR-02 | Mật khẩu ≥ 8 ký tự                                                                    |
| BR-03 | Mật khẩu được mã hóa                                                                  |
| BR-04 | SĐT phải là duy nhất                                                                  |
| BR-05 | Đúng định dạng của SĐT                                                                |
| BR-06 | Site slug phải là duy nhất                                                            |
| BR-07 | Site slug định dạng của slug (là ký tự la tinh, tiếng việt không dấu, cách nhau bởi - |
