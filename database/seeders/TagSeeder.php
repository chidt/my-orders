<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            // Promotional tags
            'Khuyến mãi',
            'Giảm giá',
            'Flash Sale',
            'Hàng mới',
            'Bán chạy',
            'Xu hướng',

            // Quality tags
            'Cao cấp',
            'Chất lượng',
            'Hàng hiệu',
            'Độc quyền',
            'Limited Edition',
            'VIP',

            // Origin tags
            'Made in Vietnam',
            'Hàng nhập',
            'Quảng Châu',
            'Hàng Thái',
            'Hàng Hàn',
            'Hàng Nhật',

            // Style tags
            'Vintage',
            'Hiện đại',
            'Truyền thống',
            'Thể thao',
            'Công sở',
            'Dạo phố',
            'Dự tiệc',

            // Material tags
            'Cotton',
            'Lụa',
            'Vải thô',
            'Jean',
            'Kaki',
            'Thun co giãn',

            // Season tags
            'Mùa hè',
            'Mùa đông',
            'Thu đông',
            'Xuân hè',
            'Tết Nguyên Đán',
            '4 mùa',

            // Special features
            'Thêu tay',
            'Họa tiết',
            'Trơn màu',
            'In hình',
            'Ren',
            'Sequin',
            'Đá',
            'Thổ cẩm',

            // Size related
            'Freesize',
            'Plus size',
            'Size nhỏ',
            'Unisex',

            // Care tags
            'Dễ giặt',
            'Không nhăn',
            'Chống thấm',
            'Thoáng khí',
        ];

        // Get all sites to create tags for each site
        $sites = Site::all();

        if ($sites->isEmpty()) {
            // If no sites exist, create tags without site_id (will be null)
            foreach ($tags as $tagName) {
                Tag::create([
                    'name' => $tagName,
                    'site_id' => null,
                ]);
            }
        } else {
            // Create tags for each site
            foreach ($sites as $site) {
                foreach ($tags as $tagName) {
                    Tag::create([
                        'name' => $tagName,
                        'site_id' => $site->id,
                    ]);
                }
            }
        }
    }
}
