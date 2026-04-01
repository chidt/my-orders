# Product Seeder README

## Mô tả

ProductSeeder tạo dữ liệu mẫu cho các sản phẩm với đầy đủ variants (ProductItems) và media (hình ảnh).

## Cấu trúc thư mục ảnh

```
database/data/product/
├── 1/
│   ├── main.jpg        # Ảnh chính của sản phẩm
│   ├── 1.jpg          # Ảnh slider 1
│   ├── 2.jpg          # Ảnh slider 2
│   ├── 3.jpg          # Ảnh slider 3
│   └── 4.jpg          # Ảnh slider 4
├── 2/
│   ├── main.jpg
│   ├── 1.jpg
│   └── 2.jpg
├── 3/
│   ├── main.jpg
│   ├── 1.jpg
│   ├── 2.jpg
│   └── 3.jpg
├── 4/
    ├── main.jpg
    ├── 1.jpg
    └── 2.jpg
```

## Sản phẩm được tạo

Seeder sẽ tạo 4 loại sản phẩm mẫu cho mỗi site:

1. **Giày bé gái A001** (folder 1/)
   - Mã sản phẩm: A001
   - Loại sản phẩm: Quảng Châu
   - Attributes: Kích Thước, Màu Sắc
   - Giá cơ bản: 150,000 VNĐ
   - Variants: S/M/L/XL × Black/White/Blue/Red = 16 variants

2. **Áo thêu lá cờ A002** (folder 2/)
   - Mã sản phẩm: A002
   - Loại sản phẩm: Hàng Thêu
   - Attributes: Kích Thước, Màu Sắc
   - Giá cơ bản: 350,000 VNĐ
   - Variants: S/M/L/XL × Black/White/Blue/Red = 16 variants

3. **Váy premium A003** (folder 3/)
   - Mã sản phẩm: A003
   - Loại sản phẩm: VNTK
   - Attributes: Kích Thước, Màu Sắc
   - Giá cơ bản: 800,000 VNĐ
   - Variants: S/M/L/XL × Black/White/Blue/Red = 16 variants

4. **Váy thêu ngựa A004** (folder 4/)
   - Mã sản phẩm: A004
   - Loại sản phẩm: Quảng Châu
   - Attributes: Màu Sắc only
   - Giá cơ bản: 450,000 VNĐ
   - Variants: Black/White/Blue/Red = 4 variants

## Chạy seeder

### Chạy toàn bộ database seeder (bao gồm ProductSeeder)
```bash
vendor/bin/sail artisan db:seed
```

### Chỉ chạy ProductSeeder
```bash
vendor/bin/sail artisan db:seed --class=ProductSeeder
```

### Refresh database và chạy lại seeder
```bash
vendor/bin/sail artisan migrate:fresh --seed
```

## Dữ liệu được tạo

### Product
- Thông tin cơ bản: name, code, description, price, etc.
- Media: main_image và product_slider_images từ thư mục tương ứng
- Liên kết với Site, Category, Supplier, Unit, Location

### ProductAttributeValue
- Kích Thước: S, M, L, XL với addition_value khác nhau
- Màu Sắc: Đen, Trắng, Xanh dương, Đỏ với addition_value khác nhau

### ProductItem (Variants)
- Tất cả combinations của attributes
- SKU format: {PRODUCT_CODE}-{SIZE}-{COLOR}
- Tên format: {PRODUCT_NAME} - {SIZE} / {COLOR}
- Giá = giá cơ bản + addition_value của attributes

### ProductItemAttributeValue
- Mapping giữa ProductItem và ProductAttributeValue

## Yêu cầu trước khi chạy

Đảm bảo các seeder sau đã chạy trước ProductSeeder:
- RolePermissionSeeder
- UserSeeder (tạo Sites)
- CategorySeeder
- SupplierSeeder
- ProductTypeSeeder
- WarehouseSeeder & LocationSeeder
- AttributeSeeder (tạo Kích Thước và Màu Sắc attributes)
- UnitsSeeder

## Lưu ý

- Seeder chỉ chạy trong môi trường: local, testing, staging
- Mỗi site sẽ có 4 sản phẩm với tổng cộng khoảng 52 ProductItems
- **Hình ảnh được copy từ database/data/product/ và lưu vào storage (không làm mất file gốc)**
- Nếu không có hình ảnh trong thư mục, seeder vẫn chạy nhưng không có media
- Mã sản phẩm sử dụng format tùy chỉnh: A001, A002, A003, A004
- ProductType phải tồn tại với tên: "Quảng Châu", "Hàng Thêu", "VNTK"

## Troubleshooting

### Lỗi thiếu dependencies
```
⚠️ Missing dependencies for site {site_name}. Skipping...
```
**Giải pháp**: Chạy các seeder dependency trước ProductSeeder.

### Lỗi không tìm thấy hình ảnh
```
⚠️ Image folder not found: {path}
```
**Giải pháp**: Đảm bảo thư mục database/data/product/{1-4}/ tồn tại với các file ảnh.

### Lỗi không có Sites
```
⚠️ No sites found. Please run SiteSeeder first.
```
**Giải pháp**: Chạy UserSeeder để tạo Sites.

### Cảnh báo ProductType không tìm thấy
```
⚠️ Product type 'Quảng Châu' not found, using 'Default Type'
```
**Giải pháp**: Đảm bảo ProductTypeSeeder đã tạo các loại sản phẩm cần thiết:
- "Quảng Châu"
- "Hàng Thêu"  
- "VNTK"

