<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Edit, Trash2, Palette, Eye, EyeOff } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import siteRoute from '@/routes/site';

interface Site {
    id: number;
    name: string;
    slug: string;
}

interface ProductType {
    id: number;
    name: string;
    order: number;
    show_on_front: boolean;
    color: string;
    products_count: number;
    created_at: string;
    updated_at: string;
}

interface PaginatedProductTypes {
    data: ProductType[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Filters {
    search?: string;
    show_on_front?: boolean;
    sort_by?: string;
    sort_direction?: string;
}

interface Props {
    site: Site;
    productTypes: PaginatedProductTypes;
    filters: Filters;
}

const props = defineProps<Props>();

const { can } = usePermissions();

// State
const showDeleteDialog = ref(false);
const productTypeToDelete = ref<ProductType | null>(null);
const searchQuery = ref(props.filters.search || '');

// Computed
const canManageProductTypes = computed(() => can('manage_product_types'));

// Methods
const confirmDelete = (productType: ProductType) => {
    productTypeToDelete.value = productType;
    showDeleteDialog.value = true;
};

const deleteProductType = () => {
    if (productTypeToDelete.value) {
        router.delete(siteRoute('product-types.destroy', {
            site: props.site.slug,
            product_type: productTypeToDelete.value.id
        }), {
            onSuccess: () => {
                showDeleteDialog.value = false;
                productTypeToDelete.value = null;
            },
        });
    }
};

const performSearch = () => {
    const params = new URLSearchParams();
    if (searchQuery.value) {
        params.set('search', searchQuery.value);
    }

    router.get(siteRoute('product-types.index', { site: props.site.slug }) + '?' + params.toString(), {}, {
        preserveState: true,
        replace: true,
    });
};

const toggleShowOnFront = (productType: ProductType) => {
    router.put(siteRoute('product-types.update', {
        site: props.site.slug,
        product_type: productType.id
    }), {
        name: productType.name,
        order: productType.order,
        show_on_front: !productType.show_on_front,
        color: productType.color,
    }, {
        preserveScroll: true,
    });
};

const getColorBadgeStyle = (color: string) => ({
    backgroundColor: color,
    color: getContrastColor(color),
});

const getContrastColor = (hexColor: string) => {
    // Remove # if present
    const hex = hexColor.replace('#', '');

    // Convert to RGB
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16);
    const b = parseInt(hex.substr(4, 2), 16);

    // Calculate luminance
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    return luminance > 0.5 ? '#000000' : '#ffffff';
};
</script>

<template>
    <AppLayout>
        <Head title="Quản lý loại sản phẩm" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Quản lý loại sản phẩm</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Quản lý các loại sản phẩm trong cửa hàng của bạn
                    </p>
                </div>

                <Button
                    v-if="canManageProductTypes"
                    :as="Link"
                    :href="siteRoute('product-types.create', { site: props.site.slug })"
                    class="flex items-center gap-2"
                >
                    <Plus class="h-4 w-4" />
                    Thêm loại sản phẩm
                </Button>
            </div>

            <!-- Search and Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Tìm kiếm và lọc</CardTitle>
                    <CardDescription>
                        Tìm kiếm loại sản phẩm theo tên hoặc lọc theo trạng thái
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <Input
                                v-model="searchQuery"
                                placeholder="Tìm kiếm theo tên loại sản phẩm..."
                                @keyup.enter="performSearch"
                                class="max-w-md"
                            />
                        </div>
                        <Button @click="performSearch" variant="outline">
                            Tìm kiếm
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Product Types Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Danh sách loại sản phẩm</CardTitle>
                    <CardDescription>
                        Tổng cộng {{ productTypes.total }} loại sản phẩm
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="productTypes.data.length === 0" class="text-center py-8">
                        <Palette class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Chưa có loại sản phẩm</h3>
                        <p class="mt-1 text-sm text-gray-500">Bắt đầu bằng cách tạo loại sản phẩm đầu tiên.</p>
                        <div class="mt-6">
                            <Button
                                v-if="canManageProductTypes"
                                :as="Link"
                                :href="siteRoute('product-types.create', { site: props.site.slug })"
                                class="flex items-center gap-2"
                            >
                                <Plus class="h-4 w-4" />
                                Thêm loại sản phẩm
                            </Button>
                        </div>
                    </div>

                    <div v-else class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tên loại sản phẩm
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Màu sắc
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Thứ tự
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Hiển thị trang chủ
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Số sản phẩm
                                    </th>
                                    <th v-if="canManageProductTypes" scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Thao tác
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="productType in productTypes.data" :key="productType.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ productType.name }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-4 h-4 rounded-full border"
                                                :style="{ backgroundColor: productType.color }"
                                            ></div>
                                            <Badge
                                                variant="outline"
                                                :style="getColorBadgeStyle(productType.color)"
                                            >
                                                {{ productType.color }}
                                            </Badge>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="text-sm text-gray-900">
                                            {{ productType.order }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <Button
                                            v-if="canManageProductTypes"
                                            variant="ghost"
                                            size="sm"
                                            @click="toggleShowOnFront(productType)"
                                            class="p-2"
                                        >
                                            <Eye v-if="productType.show_on_front" class="h-4 w-4 text-green-600" />
                                            <EyeOff v-else class="h-4 w-4 text-gray-400" />
                                        </Button>
                                        <Badge v-else :variant="productType.show_on_front ? 'default' : 'secondary'">
                                            {{ productType.show_on_front ? 'Có' : 'Không' }}
                                        </Badge>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <Badge variant="outline">
                                            {{ productType.products_count }}
                                        </Badge>
                                    </td>

                                    <td v-if="canManageProductTypes" class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex justify-center gap-2">
                                            <Button
                                                :as="Link"
                                                :href="siteRoute('product-types.edit', {
                                                    site: props.site.slug,
                                                    product_type: productType.id
                                                })"
                                                variant="ghost"
                                                size="sm"
                                                class="p-2"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Button>

                                            <Button
                                                @click="confirmDelete(productType)"
                                                variant="ghost"
                                                size="sm"
                                                class="p-2 text-red-600 hover:text-red-700"
                                                :disabled="productType.products_count > 0"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    <!-- Pagination -->
                    <div v-if="productTypes.last_page > 1" class="mt-6 flex justify-center">
                        <div class="flex gap-2">
                            <template v-for="link in productTypes.links" :key="link.label">
                                <Button
                                    v-if="link.url"
                                    :as="Link"
                                    :href="link.url"
                                    :variant="link.active ? 'default' : 'outline'"
                                    size="sm"
                                    v-html="link.label"
                                />
                                <Button
                                    v-else
                                    variant="outline"
                                    size="sm"
                                    disabled
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Xác nhận xóa</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc chắn muốn xóa loại sản phẩm "{{ productTypeToDelete?.name }}"?
                        <br>
                        Hành động này không thể hoàn tác.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">
                        Hủy
                    </Button>
                    <Button variant="destructive" @click="deleteProductType">
                        Xóa
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
