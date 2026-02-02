# UC002: User Login

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-002-LOG                                 |
| Tên Use Case   | Đăng nhập người dùng                        |
| Actor          | Người dùng                                  |
| Mô tả          | Người dùng đăng nhập vào hệ thống           |
| Độ ưu tiên     | Cao                                        |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | Người dùng chưa đăng nhập  |
| Post-condition | Người dùng đăng nhập thành công và được chuyển hướng phù hợp theo vai trò |

---

## Luồng chính (Main Flow)

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | Người dùng | Truy cập trang đăng nhập |
| 2    | Hệ thống   | Hiển thị form đăng nhập |
| 3    | Người dùng | Nhập thông tin đăng nhập |
| 4    | Người dùng | Nhấn nút **Đăng nhập** |
| 5    | Hệ thống   | Kiểm tra thông tin đăng nhập |
| 6    | Hệ thống   | Xác thực người dùng |
| 7    | Hệ thống   | Kiểm tra vai trò người dùng |
| 8    | Hệ thống   | Nếu vai trò là **admin**: chuyển hướng đến /admin/dashboard |
| 9    | Hệ thống   | Nếu vai trò là **SiteAdmin**: chuyển hướng đến /site-slug/dashboard |
| 10   | Hệ thống   | Thông báo đăng nhập thành công |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                | Kết quả           |
|------|-------------------------|-------------------|
| AF-01| Thông tin không hợp lệ  | Hiển thị lỗi      |
| AF-02| Tài khoản bị khóa       | Hiển thị lỗi      |
| AF-03| Thiếu dữ liệu           | Yêu cầu nhập lại  |

---

## Dữ liệu vào / ra

| Loại  | Trường              | Bắt buộc |
|-------|---------------------|---------|
| Input | email               | R       |
| Input | password            | R       |
| Output| role                |         |
| Output| redirect_url        |         |

---

## Quy tắc nghiệp vụ

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Email phải tồn tại trong hệ thống                                                     |
| BR-02 | Mật khẩu phải đúng với email đã nhập                                                  |
| BR-03 | Nếu vai trò là admin, chuyển hướng đến /admin/dashboard                               |
| BR-04 | Nếu vai trò là SiteAdmin, chuyển hướng đến /site-slug/dashboard                       |
| BR-05 | Nếu tài khoản bị khóa, không cho phép đăng nhập                                      |
