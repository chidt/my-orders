# UC006: Manage Products

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-006-MP                                  |
| Tên Use Case   | Quản lý sản phẩm                           |
| Actor          | SiteAdmin (người dùng có quyền hạn manage-products) |
| Mô tả          | Người dùng có thể tạo, xem, cập nhật, xóa sản phẩm thuộc trang web mà họ sở hữu |
| Độ ưu tiên     | Cao                                        |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có quyền hạn **manage-products**<br>- Trang web thuộc về người dùng hiện tại<br>- **⚠️ BẮT BUỘC**: UC011-MCT (Categories và Tags) đã được triển khai<br>- **⚠️ BẮT BUỘC**: UC012-MA (Attributes) đã được triển khai<br>- **⚠️ BẮT BUỘC**: UC009-MS (Suppliers) đã được triển khai<br>- **⚠️ BẮT BUỘC**: UC004-MSW & UC005-MWL (Warehouses & Locations) đã được triển khai |
| Post-condition | Sản phẩm được tạo/cập nhật/xóa thành công và thuộc về trang web hiện tại |

---

## Luồng chính (Main Flow)

### Luồng xem danh sách sản phẩm

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 1    | SiteAdmin  | Đăng nhập vào hệ thống |
| 2    | Hệ thống   | Kiểm tra vai trò và quyền sở hữu trang web |
| 3    | Hệ thống   | Hiển thị **"Quản lý sản phẩm"** trong menu sidebar |
| 4    | SiteAdmin  | Nhấn vào **"Quản lý sản phẩm"** |
| 5    | Hệ thống   | Hiển thị danh sách sản phẩm thuộc trang web hiện tại |
| 6    | Hệ thống   | Hiển thị nút **"Thêm sản phẩm mới"** |

### Luồng tạo sản phẩm mới

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 7    | SiteAdmin  | Nhấn nút **"Thêm sản phẩm mới"** |
| 8    | Hệ thống   | Hiển thị form tạo sản phẩm mới |
| 9    | SiteAdmin  | Nhập thông tin sản phẩm cơ bản (tên, mô tả, code) |
| 10   | SiteAdmin  | Chọn danh mục sản phẩm từ site hiện tại |
| 11   | SiteAdmin  | Chọn nhà cung cấp từ site hiện tại |
| 12   | SiteAdmin  | Chọn đơn vị tính và location mặc định |
| 13   | SiteAdmin  | **Chọn Attributes** (ví dụ: Size, Color) từ site hiện tại |
| 14   | SiteAdmin  | **Nhập Values cho mỗi Attribute** (ví dụ: Size: S,M,L / Color: Xanh,Đỏ,Vàng) |
| 15   | Hệ thống   | **Tự động tạo ProductAttributeValues** cho Product |
| 16   | Hệ thống   | **Tạo tất cả combinations ProductItems** từ attribute values |
| 17   | Hệ thống   | **Tự động generate SKU** theo thứ tự order của Attributes |
| 18   | Hệ thống   | **Tạo ProductItemAttributeValues** liên kết ProductItem với Values |
| 19   | SiteAdmin  | Upload hình ảnh và media cho Product |
| 20   | SiteAdmin  | Nhập giá cho từng ProductItem hoặc áp dụng giá chung |
| 21   | SiteAdmin  | Thêm tags và thuộc tính mở rộng |
| 22   | SiteAdmin  | Nhấn nút **"Lưu"** |
| 23   | Hệ thống   | Kiểm tra dữ liệu và validate attribute combinations |
| 24   | Hệ thống   | Lưu Product, ProductAttributeValues, ProductItems với site_id |
| 25   | Hệ thống   | Thông báo thành công và hiển thị tất cả variants đã tạo |

### Ví dụ tạo SKU tự động

**Input:**
- Product code: `ABC`
- Attributes được chọn:
  - Size (order=1): S (code: S), M (code: M)
  - Color (order=2): Xanh (code: XANH), Đỏ (code: DO)

**Output - ProductItems được tạo:**
1. SKU: `ABC-S-XANH` (ProductItem 1)
2. SKU: `ABC-S-DO` (ProductItem 2) 
3. SKU: `ABC-M-XANH` (ProductItem 3)
4. SKU: `ABC-M-DO` (ProductItem 4)

**Logic SKU Generation:**
- Thứ tự SKU dựa trên `Attributes.order` (tăng dần)
- Format: `{product_code}-{attr1_code}-{attr2_code}-...`
- Tạo tất cả combinations có thể từ attribute values

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Không có quyền **manage-products** | Không hiển thị menu quản lý sản phẩm |
| AF-02| Mã sản phẩm đã tồn tại       | Hiển thị lỗi và tạo mã mới    |
| AF-03| Thiếu thông tin bắt buộc     | Hiển thị lỗi validation       |
| AF-04| Upload file quá dung lượng   | Hiển thị lỗi và yêu cầu resize |
| AF-05| Không có danh mục nào        | Yêu cầu tạo danh mục trước    |
| AF-06| Không chọn attributes        | Tạo product đơn giản không có variants |
| AF-07| Attribute values trống       | Hiển thị lỗi validation và yêu cầu nhập values |
| AF-08| Quá nhiều combinations       | Cảnh báo nếu > 100 ProductItems, yêu cầu xác nhận |
| AF-09| SKU đã tồn tại              | Tự động thêm suffix số để tránh trùng |
| AF-10| Attributes không thuộc site  | Không cho phép chọn attributes của site khác |

---

## Dữ liệu vào / ra

### Dữ liệu vào (Input)
- **Tên sản phẩm** (name): Chuỗi, bắt buộc, tối đa 255 ký tự
- **Mã sản phẩm** (code): Chuỗi, unique trong site, tự động generate
- **Mô tả sản phẩm** (description): Text, tùy chọn
- **Danh mục** (category_id): ID danh mục, bắt buộc
- **Nhà cung cấp** (supplier_id): ID nhà cung cấp, bắt buộc
- **Đơn vị tính** (unit_id): ID đơn vị, bắt buộc
- **Giá bán** (price): Decimal, bắt buộc, > 0
- **Giá đối tác** (partner_price): Decimal, tùy chọn
- **Giá nhập** (purchase_price): Decimal, bắt buộc, > 0
- **Trọng lượng** (weight): Decimal, tùy chọn
- **Số lượng tồn kho** (qty_in_stock): Integer, mặc định 0
- **Ngày đóng đặt hàng** (order_closing_date): Date, tùy chọn
- **Vị trí mặc định** (default_location_id): ID location, bắt buộc
- **Thuộc tính mở rộng** (extra_attributes): JSON, tùy chọn

### Dữ liệu Attributes và Variants
- **Selected Attributes** (attributes): Array, tùy chọn
  - **attribute_id**: ID attribute từ site hiện tại
  - **values**: Array các giá trị attribute
    - **code**: Mã giá trị (dùng cho SKU)
    - **value**: Tên hiển thị
    - **order**: Thứ tự trong attribute
    - **addition_price**: Giá phụ phí (nếu có)

### Ví dụ dữ liệu Attributes Input:
```json
{
  "attributes": [
    {
      "attribute_id": 1, // Size attribute
      "values": [
        {"code": "S", "value": "Small", "order": 1, "addition_price": 0},
        {"code": "M", "value": "Medium", "order": 2, "addition_price": 0},
        {"code": "L", "value": "Large", "order": 3, "addition_price": 5000}
      ]
    },
    {
      "attribute_id": 2, // Color attribute  
      "values": [
        {"code": "XANH", "value": "Xanh", "order": 1, "addition_price": 0},
        {"code": "DO", "value": "Đỏ", "order": 2, "addition_price": 2000},
        {"code": "VANG", "value": "Vàng", "order": 3, "addition_price": 3000}
      ]
    }
  ]
}
```

### Dữ liệu ra (Output)
- Thông báo thành công/thất bại
- Thông tin sản phẩm đã tạo/cập nhật
- Mã sản phẩm được generate tự động

---

## Quy tắc nghiệp vụ

| ID    | Quy tắc                                                                               |
|-------|---------------------------------------------------------------------------------------|
| BR-01 | Mã sản phẩm phải là duy nhất trong phạm vi site                                      |
| BR-02 | Mã sản phẩm tự động generate theo format: {site_prefix}{auto_number}                 |
| BR-03 | Giá bán phải lớn hơn 0                                                                |
| BR-04 | Giá nhập phải lớn hơn 0                                                               |
| BR-05 | Giá đối tác (nếu có) phải nhỏ hơn hoặc bằng giá bán                                  |
| BR-06 | Sản phẩm phải thuộc về một danh mục hợp lệ của site                                   |
| BR-07 | Nhà cung cấp phải thuộc về site hiện tại                                             |
| BR-08 | Vị trí mặc định phải thuộc về warehouse của site                                      |
| BR-09 | File upload không được vượt quá 10MB                                                 |
| BR-10 | Chỉ cho phép upload file ảnh: jpg, jpeg, png, gif, webp                              |
| BR-11 | **SKU tự động generate** theo format: {product_code}-{attr1_code}-{attr2_code}-...   |
| BR-12 | **Thứ tự attributes trong SKU** dựa trên `Attributes.order` (tăng dần)               |
| BR-13 | **ProductItems được tạo** cho tất cả combinations của attribute values                |
| BR-14 | **Attributes phải thuộc site hiện tại**, không cho phép cross-site                   |
| BR-15 | **ProductAttributeValues.code** phải unique trong Product                            |
| BR-16 | **Nếu không chọn attributes**, tạo 1 ProductItem đơn giản với SKU = product_code    |
| BR-17 | **Giới hạn tối đa 100 ProductItems** cho 1 Product để tránh quá tải                 |
| BR-18 | **SKU phải unique** trong toàn hệ thống, tự động thêm suffix nếu trùng               |
