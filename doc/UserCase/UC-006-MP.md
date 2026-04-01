# UC006: Manage Products

## Thông tin Use Case

| Thuộc tính      | Nội dung                                   |
|----------------|--------------------------------------------|
| Use Case ID    | UC-006-MP                                  |
| Tên Use Case   | Quản lý sản phẩm                           |
| Actor          | SiteAdmin (người dùng có quyền hạn manage_products) |
| Mô tả          | Người dùng có thể tạo, xem, cập nhật, xóa sản phẩm thuộc trang web mà họ sở hữu |
| Độ ưu tiên     | Cao                                        |

---

## Điều kiện

| Loại           | Mô tả                       |
|----------------|----------------------------|
| Pre-condition  | - Người dùng đã đăng nhập<br>- Người dùng có quyền hạn **manage_products**<br>- Trang web thuộc về người dùng hiện tại<br>- **⚠️ BẮT BUỘC**: UC011-MCT (Categories và Tags) đã được triển khai<br>- **⚠️ BẮT BUỘC**: UC012-MA (Attributes) đã được triển khai<br>- **⚠️ BẮT BUỘC**: UC009-MS (Suppliers) đã được triển khai<br>- **⚠️ BẮT BUỘC**: UC004-MSW & UC005-MWL (Warehouses & Locations) đã được triển khai |
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

### Luồng chỉnh sửa sản phẩm cơ bản

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 26   | SiteAdmin  | Từ danh sách sản phẩm, nhấn nút **"Chỉnh sửa"** hoặc nhấn vào tên sản phẩm |
| 27   | Hệ thống   | Kiểm tra quyền **update** cho sản phẩm |
| 28   | Hệ thống   | Load thông tin sản phẩm với tất cả relationships |
| 29   | Hệ thống   | Hiển thị form chỉnh sửa với dữ liệu hiện tại được điền sẵn |
| 30   | Hệ thống   | Hiển thị **"ProductAttributes"** hiện tại (attributes đã chọn với values) |
| 31   | Hệ thống   | Hiển thị **"Child Products"** (ProductItems) đã được tạo từ attribute combinations |
| 32   | SiteAdmin  | Cập nhật thông tin sản phẩm cơ bản (tên, mô tả, giá, etc.) |
| 33   | SiteAdmin  | **Chỉnh sửa Attributes và Values** (thêm, xóa, sửa attributes/values) |
| 34   | SiteAdmin  | Upload/xóa hình ảnh chính và slider images |
| 35   | SiteAdmin  | Nhấn nút **"Cập nhật"** (cập nhật thông tin chính) |
| 36   | Hệ thống   | Validate dữ liệu đầu vào |
| 37   | Hệ thống   | Update thông tin Product cơ bản |
| 38   | Hệ thống   | **Sync ProductAttributeValues** (cập nhật, thêm mới, xóa) |
| 39   | Hệ thống   | Sync tags và media |
| 40   | Hệ thống   | **⚠️ KHÔNG tự động sync ProductItems** - cần nhấn nút riêng |
| 41   | Hệ thống   | Lưu thay đổi và thông báo thành công |
| 42   | Hệ thống   | Redirect về trang edit với thông báo "Sản phẩm đã được cập nhật thành công" |

### Luồng cập nhật sản phẩm con (Child Products)

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 43   | SiteAdmin  | Sau khi đã cập nhật attributes, nhấn nút **"Cập nhật sản phẩm con"** |
| 44   | Hệ thống   | Validate quyền update cho sản phẩm |
| 45   | Hệ thống   | **Recalculate ProductItem combinations** từ attributes hiện tại |
| 46   | Hệ thống   | **Update existing ProductItems** hoặc tạo mới nếu cần |
| 47   | Hệ thống   | **Xóa ProductItems không còn cần thiết** (orphaned items) |
| 48   | Hệ thống   | **Regenerate SKU** cho ProductItems mới hoặc đã thay đổi |
| 49   | Hệ thống   | **Update ProductItemAttributeValues** mapping |
| 50   | Hệ thống   | **Update prices** cho tất cả ProductItems dựa trên addition values |
| 51   | Hệ thống   | **Sync variant images** cho ProductItems |
| 52   | Hệ thống   | Thông báo "Đã đồng bộ sản phẩm con thành công" |

### Chi tiết luồng xử lý ProductItem khi cập nhật sản phẩm con

**⚠️ Lưu ý quan trọng**: ProductItems chỉ được sync khi nhấn nút **"Cập nhật sản phẩm con"**, không phải khi "Cập nhật" thông tin chính.

#### Trường hợp 1: Thêm Attribute Values mới
| Bước | Actor      | Hành động |
|------|------------|-----------|
| 53   | SiteAdmin  | **Bước 1**: Cập nhật attributes để thêm values mới (VD: Size thêm "XL") |
| 54   | SiteAdmin  | **Bước 2**: Nhấn **"Cập nhật"** để lưu ProductAttributeValues |
| 55   | SiteAdmin  | **Bước 3**: Nhấn **"Cập nhật sản phẩm con"** để sync ProductItems |
| 56   | Hệ thống   | Tạo **ProductAttributeValue** mới (đã có từ bước 54) |
| 57   | Hệ thống   | **Tính toán combinations mới** từ attribute values |
| 58   | Hệ thống   | **Tạo ProductItems mới** cho combinations chưa tồn tại |
| 59   | Hệ thống   | **Generate SKU** cho ProductItems mới |
| 60   | Hệ thống   | **Tạo ProductItemAttributeValues** mapping cho items mới |
| 61   | Hệ thống   | **Giữ nguyên existing ProductItems** không thay đổi |

#### Trường hợp 2: Xóa Attribute Values
| Bước | Actor      | Hành động |
|------|------------|-----------|
| 62   | SiteAdmin  | **Bước 1**: Xóa values khỏi existing attribute (VD: xóa size "S") |
| 63   | SiteAdmin  | **Bước 2**: Nhấn **"Cập nhật"** để xóa ProductAttributeValues |
| 64   | SiteAdmin  | **Bước 3**: Nhấn **"Cập nhật sản phẩm con"** để sync ProductItems |
| 65   | Hệ thống   | **Identify ProductItems** sử dụng values đã bị xóa |
| 66   | Hệ thống   | **Kiểm tra ràng buộc** (OrderDetails, etc.) cho items sẽ xóa |
| 67   | Hệ thống   | **Xóa ProductItemAttributeValues** liên quan |
| 68   | Hệ thống   | **Xóa ProductItems** không còn valid combinations |

#### Trường hợp 3: Cập nhật Attribute Values
| Bước | Actor      | Hành động |
|------|------------|-----------|
| 69   | SiteAdmin  | **Bước 1**: Thay đổi thông tin values (code, value, addition_price) |
| 70   | SiteAdmin  | **Bước 2**: Nhấn **"Cập nhật"** để lưu ProductAttributeValues |
| 71   | SiteAdmin  | **Bước 3**: Nhấn **"Cập nhật sản phẩm con"** để sync ProductItems |
| 72   | Hệ thống   | **Update ProductAttributeValue** đã có từ bước 70 |
| 73   | Hệ thống   | **Nếu thay đổi code**: Rebuild SKU cho affected ProductItems |
| 74   | Hệ thống   | **Nếu thay đổi addition prices**: Recalculate giá cho ProductItems |
| 75   | Hệ thống   | **Update ProductItem** prices, names, SKUs |
| 76   | Hệ thống   | **Giữ nguyên ProductItem ID** để maintain data integrity |

#### Trường hợp 4: Thêm/Xóa Attributes hoàn toàn
| Bước | Actor      | Hành động |
|------|------------|-----------|
| 77   | SiteAdmin  | **Bước 1**: Thêm/xóa attribute hoàn toàn (VD: thêm "Material") |
| 78   | SiteAdmin  | **Bước 2**: Nhấn **"Cập nhật"** để lưu attribute changes |
| 79   | SiteAdmin  | **Bước 3**: Nhấn **"Cập nhật sản phẩm con"** để rebuild tất cả |
| 80   | Hệ thống   | **Xóa tất cả existing ProductItems** |
| 81   | Hệ thống   | **Tính lại combinations** với attributes mới |
| 82   | Hệ thống   | **Tạo lại tất cả ProductItems** với combinations mới |
| 83   | Hệ thống   | **Generate SKU mới** theo thứ tự attributes |

### Luồng xem chi tiết sản phẩm

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 84   | SiteAdmin  | Từ danh sách, nhấn **"Xem chi tiết"** |
| 85   | Hệ thống   | Hiển thị thông tin đầy đủ sản phẩm |
| 86   | Hệ thống   | Hiển thị danh sách **Child Products** với SKU và giá |
| 87   | Hệ thống   | Hiển thị **Attribute combinations** |
| 88   | Hệ thống   | Hiển thị **Media gallery** |

### Luồng xóa sản phẩm

| Bước | Actor      | Hành động |
|------|------------|-----------|
| 89   | SiteAdmin  | Từ danh sách hoặc trang edit, nhấn **"Xóa"** |
| 90   | Hệ thống   | Kiểm tra **ràng buộc dữ liệu** (OrderDetails, etc.) |
| 91   | Hệ thống   | Nếu có ràng buộc: Hiển thị cảnh báo không thể xóa |
| 92   | Hệ thống   | Nếu không có ràng buộc: Hiển thị dialog xác nhận |
| 93   | SiteAdmin  | Xác nhận xóa |
| 94   | Hệ thống   | **Xóa ProductItemAttributeValues** |
| 95   | Hệ thống   | **Xóa ProductItems** |
| 96   | Hệ thống   | **Xóa ProductAttributeValues** |
| 97   | Hệ thống   | **Xóa Product media** |
| 98   | Hệ thống   | **Xóa Product** |
| 99   | Hệ thống   | Thông báo thành công và redirect về danh sách |

---

## Luồng thay thế / Ngoại lệ

| Mã   | Điều kiện                    | Kết quả                       |
|------|------------------------------|-------------------------------|
| AF-01| Không có quyền **manage_products** | Không hiển thị menu quản lý sản phẩm |
| AF-02| Mã sản phẩm đã tồn tại       | Hiển thị lỗi và tạo mã mới    |
| AF-03| Thiếu thông tin bắt buộc     | Hiển thị lỗi validation       |
| AF-04| Upload file quá dung lượng   | Hiển thị lỗi và yêu cầu resize |
| AF-05| Không có danh mục nào        | Yêu cầu tạo danh mục trước    |
| AF-06| Không chọn attributes        | Tạo product đơn giản không có variants |
| AF-07| Attribute values trống       | Hiển thị lỗi validation và yêu cầu nhập values |
| AF-08| Quá nhiều combinations       | Cảnh báo nếu > 100 ProductItems, yêu cầu xác nhận |
| AF-09| SKU đã tồn tại              | Tự động thêm suffix số để tránh trùng |
| AF-10| Attributes không thuộc site  | Không cho phép chọn attributes của site khác |
| AF-11| **Chỉnh sửa attribute làm mất ProductItems** | Cảnh báo user về số ProductItems sẽ bị xóa |
| AF-12| **ProductItems đang được sử dụng trong Orders** | Không cho phép xóa, hiển thị cảnh báo |
| AF-13| **Thay đổi attribute code** | Tự động update SKU của affected ProductItems |
| AF-14| **Attribute values trùng code** | Validation error, yêu cầu sửa duplicate |
| AF-15| **Xóa tất cả attributes** | Tạo 1 ProductItem đơn giản với SKU = product_code |
| AF-16| **Upload variant image không thành công** | Giữ existing variant image, thông báo lỗi upload |
| AF-17| **Cập nhật product khi có quá nhiều variants** | Cảnh báo performance và yêu cầu xác nhận |
| AF-18| **Network error khi save** | Hiển thị lỗi, giữ dữ liệu form, cho phép retry |
| AF-19| **Concurrent editing** | Detect version conflict, yêu cầu refresh và merge changes |
| AF-20| **Database constraint violations** | Rollback transaction, hiển thị user-friendly error |

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

## Dữ liệu vào cho Editing (Input)
- **Tất cả fields từ Create** (xem phần trước)
- **ProductAttributeValues Updates**:
  - **id**: ID của existing value (để update)
  - **code**: Code mới (sẽ trigger SKU regeneration nếu thay đổi)
  - **value**: Display value mới
  - **order**: Thứ tự mới trong attribute
  - **addition_value**: Phụ phí mới cho regular price
  - **partner_addition_value**: Phụ phí cho partner price
  - **purchase_addition_value**: Phụ phí cho purchase price

### Ví dụ dữ liệu Editing với ID:
```json
{
  "attributes": [
    {
      "attribute_id": 1,
      "values": [
        {
          "id": 101,  // Update existing value
          "code": "S", 
          "value": "Small Updated", 
          "order": 1, 
          "addition_value": 1000
        },
        {
          "id": null,  // Create new value
          "code": "XL", 
          "value": "Extra Large", 
          "order": 4, 
          "addition_value": 5000
        }
      ]
    }
  ]
}
```

### Dữ liệu Media và Variant Images
- **Main Image Updates**:
  - **remove_main_image**: Boolean để xóa ảnh chính
  - **main_image**: File mới để upload
- **Slider Images Updates**:
  - **remove_slide_media_ids**: Array IDs ảnh slider cần xóa
  - **slide_images**: Array files mới để thêm vào slider
- **Variant Images Updates**:
  - **variant_images**: Array mapping ProductItem với media
  - **variant_image_files**: Files upload cho variants
  - **variant_image_file_keys**: Keys mapping files với ProductItems

### Dữ liệu ra cho Editing (Output)
- **Product Updated**: Thông tin sản phẩm sau khi update
- **ProductItems Summary**: Danh sách ProductItems mới/updated/deleted
- **SKU Changes**: Báo cáo SKUs đã thay đổi
- **Media Changes**: Báo cáo media uploaded/deleted
- **Validation Errors**: Chi tiết lỗi nếu có

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
| BR-19 | **Khi chỉnh sửa attributes**, phải maintain data integrity cho existing ProductItems |
| BR-20 | **ProductItems có OrderDetails** không được phép xóa                                 |
| BR-21 | **Thay đổi attribute code** sẽ trigger SKU regeneration cho affected ProductItems    |
| BR-22 | **Addition values thay đổi** sẽ tự động update giá của ProductItems                  |
| BR-23 | **Xóa tất cả attributes** sẽ tạo 1 ProductItem đơn giản                              |
| BR-24 | **Variant images** chỉ có thể upload 1 file cho mỗi ProductItem                      |
| BR-25 | **ProductItem ID được giữ nguyên** khi possible để maintain references               |
| BR-26 | **Transaction rollback** nếu có bất kỳ error nào trong quá trình update             |
| BR-27 | **Media optimization** được thực hiện tự động sau khi upload                         |
| BR-28 | **Concurrent editing detection** để tránh data conflicts                             |
| BR-29 | **Attribute order thay đổi** sẽ trigger SKU regeneration theo thứ tự mới             |
| BR-30 | **Maximum 10 slider images** cho mỗi Product                                         |
| BR-31 | **⚠️ Cập nhật ProductItems riêng biệt**: Nhấn "Cập nhật" chỉ sync ProductAttributeValues, cần nhấn "Cập nhật sản phẩm con" để sync ProductItems |

---

## Luồng kỹ thuật chi tiết (Technical Implementation Flows)

### Sync ProductAttributeValues Process

| Bước | Action | Logic Implementation |
|------|--------|---------------------|
| T1   | **Load existing values** | `$product->productAttributeValues()->get()` |
| T2   | **Index by ID** | `$existingById = $values->keyBy('id')` |
| T3   | **Index by attribute+code** | `$existingByKey = $values->keyBy(fn($v) => $v->attribute_id.'|'.$v->code)` |
| T4   | **Process input attributes** | Loop through `$validated['attributes']` |
| T5   | **For each attribute value** | Check if `id` exists in request |
| T6   | **If ID provided** | Find existing by ID and update |
| T7   | **If no ID** | Find by attribute_id+code combination |
| T8   | **If not found** | Create new ProductAttributeValue |
| T9   | **Track kept IDs** | `$keptAttributeValueIds[]` |
| T10  | **Find orphaned values** | `whereNotIn('id', $keptAttributeValueIds)` |
| T11  | **Delete orphaned relations** | `ProductItemAttributeValue::whereIn('product_attribute_value_id', $orphanIds)->delete()` |
| T12  | **Delete orphaned values** | `$product->productAttributeValues()->whereIn('id', $orphanIds)->delete()` |

### Sync ProductItems Process

| Bước | Action | Logic Implementation |
|------|--------|---------------------|
| T13  | **Load updated attributes** | `$product->load('productAttributeValues.attribute')` |
| T14  | **Update existing item prices** | Loop through existing ProductItems |
| T15  | **Group values by attribute** | `$valuesByAttribute = $values->groupBy('attribute_id')` |
| T16  | **Get attribute order** | Sort by `attribute.order` ASC |
| T17  | **Generate combinations** | Cartesian product of all attribute values |
| T18  | **Build existing item map** | `$existingItemsByKey[variant_key] = $item` |
| T19  | **For each combination** | Generate variant key from codes |
| T20  | **Build SKU** | `{product_code}-{attr1_code}-{attr2_code}` |
| T21  | **Calculate prices** | Base price + sum of addition_values |
| T22  | **If item exists** | Update existing item with new data |
| T23  | **If item not exists** | Create new ProductItem |
| T24  | **Sync item relations** | Delete old + create new ProductItemAttributeValues |

### Media Management Process

| Bước | Action | Logic Implementation |
|------|--------|---------------------|
| T25  | **Process main image removal** | `$product->clearMediaCollection('main_image')` if `remove_main_image = true` |
| T26  | **Process slider removals** | Delete media by IDs in `remove_slide_media_ids` |
| T27  | **Upload new main image** | `$product->addMediaFromRequest('main_image')->toMediaCollection('main_image')` |
| T28  | **Upload new slider images** | `addMultipleMediaFromRequest(['slide_images'])` |
| T29  | **Enforce slider limit** | Keep only first 10 images, delete excess |
| T30  | **Process variant images** | Map uploaded files to ProductItems by keys |
| T31  | **Optimize images** | `OptimizerChainFactory::create()->optimize($media->getPath())` |

### SKU Generation Algorithm

```php
private function generateSKU(Product $product, array $combination): string
{
    // 1. Get base SKU from product code
    $baseSku = $product->code;
    
    // 2. Sort combination by attribute order
    $sortedValues = collect($combination)
        ->sortBy(fn($value) => $value->attribute->order);
    
    // 3. Build SKU with attribute codes
    $attributeCodes = $sortedValues
        ->pluck('code')
        ->map(fn($code) => strtoupper(trim($code)))
        ->implode('-');
    
    // 4. Combine product code with attribute codes
    $sku = $attributeCodes ? "{$baseSku}-{$attributeCodes}" : $baseSku;
    
    // 5. Ensure uniqueness
    return $this->ensureUniqueSku($sku, $productItemId = null);
}
```

### Price Calculation Logic

```php
private function calculateProductItemPrices(Product $product, array $combination): array
{
    $basePrice = (float) $product->price;
    $basePartnerPrice = (float) ($product->partner_price ?? 0);
    $basePurchasePrice = (float) $product->purchase_price;
    
    // Sum all addition values from attribute values
    $addition = collect($combination)->sum(fn($v) => (float) ($v->addition_value ?? 0));
    $partnerAddition = collect($combination)->sum(fn($v) => (float) ($v->partner_addition_value ?? 0));
    $purchaseAddition = collect($combination)->sum(fn($v) => (float) ($v->purchase_addition_value ?? 0));
    
    return [
        'price' => $basePrice + $addition,
        'partner_price' => $basePartnerPrice > 0 ? $basePartnerPrice + $partnerAddition : null,
        'purchase_price' => $basePurchasePrice + $purchaseAddition,
    ];
}
```

---

## Transaction & Error Handling

### Database Transaction Flow

**Luồng cập nhật sản phẩm cơ bản (UpdateProduct::handle):**
```php
DB::transaction(function () use ($product, $validated, $request, $siteId) {
    // 1. Update basic product information
    $product->update($basicFields);
    
    // 2. Sync tags
    $product->tags()->sync($validated['tags'] ?? []);
    
    // 3. Sync media (main image, slider images)
    $this->syncProductMedia($product, $request);
    
    // 4. Sync ONLY product attribute values
    $this->syncProductAttributeValues($product, $validated['attributes']);
    
    // ⚠️ NO ProductItems sync here - done separately
});
```

**Luồng cập nhật sản phẩm con (UpdateProduct::syncChildProductsOnly):**
```php
DB::transaction(function () use ($product, $siteId, $request) {
    // 1. Sync child products (ProductItems)
    $this->syncChildProducts($product, $siteId, $product->code);
    
    // 2. Sync variant images
    $this->syncChildProductImages($product, $request);
});
```

### Error Scenarios & Handling

| Error Type | Detection | Response | Recovery |
|------------|-----------|----------|----------|
| **Validation Error** | Form validation rules | Return to form with errors | User fixes input |
| **Duplicate SKU** | SKU uniqueness check | Auto-append suffix number | System auto-resolves |
| **File Upload Error** | File size/type validation | Show error, keep existing media | User uploads valid file |
| **Database Constraint** | Foreign key violations | Transaction rollback | Show user-friendly message |
| **Media Processing Error** | Image optimization failure | Log error, continue flow | Image saved without optimization |
| **Concurrent Edit** | Version/timestamp check | Show conflict warning | User must refresh and retry |
| **Storage Error** | Disk space/permissions | Transaction rollback | Admin resolves storage issue |
| **Memory Limit** | Too many combinations | Limit check before processing | User reduces attribute values |

---

## Performance Considerations

### Optimization Strategies

| Aspect | Implementation | Benefit |
|--------|----------------|---------|
| **Eager Loading** | Load relationships in single query | Reduce N+1 query problems |
| **Bulk Operations** | Use `whereIn()` for deletions | Faster than individual deletes |
| **Image Optimization** | Background image processing | Faster user response |
| **Combination Limit** | Max 100 ProductItems warning | Prevent system overload |
| **Transaction Scope** | Minimize transaction time | Reduce deadlock risk |
| **Selective Updates** | Only update changed fields | Better performance |
| **Media Collections** | Spatie Media Library | Efficient file management |
| **Index Usage** | Proper database indexes | Fast lookups |

### Database Indexes Required
```sql
-- ProductAttributeValues
INDEX idx_pav_product_attribute (product_id, attribute_id)
INDEX idx_pav_code (product_id, code)

-- ProductItems  
INDEX idx_pi_product (product_id)
INDEX idx_pi_sku (sku) UNIQUE

-- ProductItemAttributeValues
INDEX idx_piav_item (product_item_id)
INDEX idx_piav_value (product_attribute_value_id)
```

---

