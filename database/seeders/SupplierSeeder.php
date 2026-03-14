<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Công ty TNHH Thương mại Quảng Châu',
                'person_in_charge' => 'Nguyễn Văn An',
                'phone' => '0901234567',
                'address' => 'Số 123, Đường Lê Lợi, Quận 1, TP. Hồ Chí Minh',
                'description' => 'Chuyên cung cấp hàng hóa nhập khẩu từ Trung Quốc, đặc biệt là hàng thời trang và phụ kiện.',
            ],
            [
                'name' => 'Công ty Cổ phần Dệt may VNTK',
                'person_in_charge' => 'Trần Thị Bình',
                'phone' => '0987654321',
                'address' => 'KCN Tân Bình, Quận Tân Bình, TP. Hồ Chí Minh',
                'description' => 'Nhà sản xuất và cung cấp sản phẩm dệt may chất lượng cao tại Việt Nam.',
            ],
            [
                'name' => 'Xưởng Thêu Truyền Thống Đông Dương',
                'person_in_charge' => 'Lê Văn Cường',
                'phone' => '0912345678',
                'address' => 'Làng nghề Vạn Phúc, Hà Đông, Hà Nội',
                'description' => 'Chuyên sản xuất các sản phẩm thêu truyền thống Việt Nam với kỹ thuật thủ công tinh xảo.',
            ],
            [
                'name' => 'Công ty TNHH XNK Minh Tâm',
                'person_in_charge' => 'Phạm Minh Tuấn',
                'phone' => '0965432198',
                'address' => 'Số 45, Phố Hàng Bài, Hoàn Kiếm, Hà Nội',
                'description' => 'Nhập khẩu và phân phối các mặt hàng tiêu dùng chất lượng cao từ nhiều quốc gia.',
            ],
            [
                'name' => 'Xí nghiệp May mặc Hồng Linh',
                'person_in_charge' => 'Đỗ Thị Hà',
                'phone' => '0934567890',
                'address' => 'Thị trấn Hồng Lĩnh, Huyện Hồng Lĩnh, Hà Tĩnh',
                'description' => 'Sản xuất và cung cấp các sản phẩm may mặc từ nguyên liệu tự nhiên chất lượng.',
            ],
            [
                'name' => 'Công ty CP Thương mại Dịch vụ Tân Phú',
                'person_in_charge' => 'Võ Thanh Nam',
                'phone' => '0923456789',
                'address' => 'Quận Tân Phú, TP. Hồ Chí Minh',
                'description' => 'Chuyên cung cấp hàng hóa đa dạng phục vụ nhu cầu thị trường bán lẻ.',
            ],
        ];

        // Get all sites to create suppliers for each site
        $sites = Site::all();

        if ($sites->isEmpty()) {
            // If no sites exist, create suppliers without site_id (will be null)
            foreach ($suppliers as $supplierData) {
                Supplier::create(array_merge($supplierData, ['site_id' => null]));
            }
        } else {
            // Create suppliers for each site
            foreach ($sites as $site) {
                foreach ($suppliers as $supplierData) {
                    Supplier::create(array_merge($supplierData, ['site_id' => $site->id]));
                }
            }
        }
    }
}
