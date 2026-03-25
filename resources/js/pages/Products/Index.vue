<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, PackageSearch, Edit, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import ProductThumbnailPreview from '@/components/products/ProductThumbnailPreview.vue';
import { usePermissions } from '@/composables/usePermissions';
import { formatVnd } from '@/lib/utils';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    create as ProductsCreate,
    destroy as ProductsDestroy,
    edit as ProductsEdit,
    index as ProductsIndex,
} from '@/routes/products';

interface Site {
    id: number;
    name: string;
    slug: string;
}

interface RelatedEntity {
    id: number;
    name: string;
}

interface Product {
    id: number;
    name: string;
    code: string;
    supplier_code: string | null;
    price: string;
    partner_price: string | null;
    purchase_price: string;
    thumbnail_url: string | null;
    qty_in_stock: number;
    product_items_count: number;
    category: RelatedEntity | null;
    supplier: RelatedEntity | null;
    unit: RelatedEntity | null;
    product_type: RelatedEntity | null;
}

interface PaginatedProducts {
    data: Product[];
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
}

interface Props {
    site?: Site;
    products: PaginatedProducts;
    filters: Filters;
}

const props = defineProps<Props>();

const { can } = usePermissions();
const canManageProducts = computed(() => can('manage_products'));

const searchQuery = ref(props.filters.search || '');

const performSearch = () => {
    if (!props.site?.slug) return;

    const params = new URLSearchParams();
    if (searchQuery.value) {
        params.set('search', searchQuery.value);
    }

    router.get(
        ProductsIndex.url({ site: props.site.slug }) + '?' + params.toString(),
        {},
        {
            preserveState: true,
            replace: true,
        },
    );
};

const showDeleteDialog = ref(false);
const productToDelete = ref<Product | null>(null);

const confirmDelete = (product: Product) => {
    productToDelete.value = product;
    showDeleteDialog.value = true;
};

const deleteProduct = () => {
    if (productToDelete.value && props.site?.slug) {
        router.delete(
            ProductsDestroy.url({
                site: props.site.slug,
                product: productToDelete.value.id,
            }),
            {
                onSuccess: () => {
                    showDeleteDialog.value = false;
                    productToDelete.value = null;
                },
            },
        );
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Quản lý sản phẩm" />

        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-8 sm:flex sm:items-center sm:justify-between">
                <div class="mb-4 sm:mb-0">
                    <h1 class="text-2xl font-bold text-gray-900">
                        Quản lý sản phẩm
                    </h1>
                    <p class="mt-2 text-sm text-gray-700">
                        Danh sách sản phẩm thuộc trang web hiện tại
                    </p>
                </div>

                <Button
                    v-if="canManageProducts && props.site?.slug"
                    :as="Link"
                    :href="ProductsCreate.url({ site: props.site.slug })"
                    class="flex w-full items-center justify-center gap-2 sm:w-auto"
                >
                    <Plus class="h-4 w-4" />
                    Thêm sản phẩm mới
                </Button>
            </div>

            <div class="mb-8 rounded-lg border border-gray-200 bg-white p-4">
                <div class="flex flex-col gap-4 sm:flex-row">
                    <div class="flex-1">
                        <Input
                            v-model="searchQuery"
                            placeholder="Tìm theo tên, mã sản phẩm, mã NCC..."
                            @keyup.enter="performSearch"
                        />
                    </div>
                    <Button
                        @click="performSearch"
                        variant="outline"
                        class="w-full sm:w-auto"
                    >
                        Tìm kiếm
                    </Button>
                </div>
            </div>

            <div class="mb-6">
                <p class="text-sm text-gray-600">
                    Tổng cộng
                    <span class="font-medium">{{ products.total }}</span>
                    sản phẩm
                </p>
            </div>

            <div
                v-if="products.data.length === 0"
                class="rounded-lg border border-gray-200 bg-white py-12 text-center"
            >
                <PackageSearch class="mx-auto h-16 w-16 text-gray-300" />
                <h3 class="mt-4 text-lg font-medium text-gray-900">
                    Chưa có sản phẩm
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                    Bắt đầu bằng cách tạo sản phẩm đầu tiên.
                </p>
                <div class="mt-6">
                    <Button
                        v-if="canManageProducts && props.site?.slug"
                        :as="Link"
                        :href="ProductsCreate.url({ site: props.site.slug })"
                        class="flex items-center gap-2"
                    >
                        <Plus class="h-4 w-4" />
                        Thêm sản phẩm mới
                    </Button>
                </div>
            </div>

            <div
                v-else
                class="hidden overflow-hidden rounded-lg border border-gray-200 bg-white md:block"
            >
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Ảnh
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Sản phẩm
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Danh mục
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Nhà cung cấp
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Giá
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Tồn
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Biến thể
                            </th>
                            <th
                                v-if="canManageProducts"
                                scope="col"
                                class="px-6 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Thao tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr
                            v-for="product in products.data"
                            :key="product.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <ProductThumbnailPreview
                                    :src="product.thumbnail_url"
                                    :alt="product.name"
                                />
                            </td>
                            <td class="px-6 py-4">
                                <div class="min-w-0">
                                    <div
                                        class="truncate text-sm font-medium text-gray-900"
                                    >
                                        {{ product.name }}
                                    </div>
                                    <div
                                        class="mt-1 flex flex-wrap items-center gap-2"
                                    >
                                        <Badge variant="outline" class="text-xs">
                                            {{ product.code }}
                                        </Badge>
                                        <Badge
                                            v-if="product.supplier_code"
                                            variant="secondary"
                                            class="text-xs"
                                        >
                                            {{ product.supplier_code }}
                                        </Badge>
                                        <Badge
                                            v-if="product.product_type?.name"
                                            variant="secondary"
                                            class="text-xs"
                                        >
                                            {{ product.product_type.name }}
                                        </Badge>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ product.category?.name || '—' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ product.supplier?.name || '—' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="space-y-1 text-xs text-gray-600">
                                    <div>
                                        Giá nhập:
                                        <span class="font-medium text-gray-900">
                                            {{ formatVnd(product.purchase_price) }}
                                        </span>
                                    </div>
                                    <div>
                                        Giá đối tác:
                                        <span class="font-medium text-gray-900">
                                            {{ formatVnd(product.partner_price) }}
                                        </span>
                                    </div>
                                    <div>
                                        Giá bán:
                                        <span class="font-medium text-gray-900">
                                            {{ formatVnd(product.price) }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <Badge variant="outline">
                                    {{ product.qty_in_stock }}
                                </Badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <Badge variant="outline">
                                    {{ product.product_items_count }}
                                </Badge>
                            </td>
                            <td
                                v-if="canManageProducts"
                                class="px-6 py-4 whitespace-nowrap text-center"
                            >
                                <div class="flex justify-center gap-2">
                                    <Button
                                        v-if="props.site?.slug"
                                        :as="Link"
                                        :href="
                                            ProductsEdit.url({
                                                site: props.site.slug,
                                                product: product.id,
                                            })
                                        "
                                        variant="ghost"
                                        size="sm"
                                        class="p-2"
                                    >
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        @click="confirmDelete(product)"
                                        variant="ghost"
                                        size="sm"
                                        class="p-2 text-red-600 hover:text-red-700"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="products.data.length > 0" class="space-y-3 md:hidden">
                <div
                    v-for="product in products.data"
                    :key="product.id"
                    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <div class="mb-2 flex items-start justify-between gap-3">
                        <div class="flex min-w-0 flex-1 gap-3">
                            <ProductThumbnailPreview
                                :src="product.thumbnail_url"
                                :alt="product.name"
                                size-class="h-14 w-14"
                            />
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-lg font-medium text-gray-900">
                                    {{ product.name }}
                                </h3>
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    <Badge variant="outline" class="text-xs">
                                        {{ product.code }}
                                    </Badge>
                                    <Badge
                                        v-if="product.supplier_code"
                                        variant="secondary"
                                        class="text-xs"
                                    >
                                        {{ product.supplier_code }}
                                    </Badge>
                                    <Badge
                                        v-if="product.product_type?.name"
                                        variant="secondary"
                                        class="text-xs"
                                    >
                                        {{ product.product_type.name }}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="canManageProducts && props.site?.slug"
                            class="flex shrink-0 gap-1"
                        >
                            <Button
                                :as="Link"
                                :href="
                                    ProductsEdit.url({
                                        site: props.site.slug,
                                        product: product.id,
                                    })
                                "
                                variant="ghost"
                                size="sm"
                                class="h-8 w-8 p-2"
                            >
                                <Edit class="h-4 w-4" />
                            </Button>
                            <Button
                                @click="confirmDelete(product)"
                                variant="ghost"
                                size="sm"
                                class="h-8 w-8 p-2 text-red-600 hover:text-red-700"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Danh mục:</span
                                >
                                <div class="font-medium text-gray-900">
                                    {{ product.category?.name || '—' }}
                                </div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Nhà cung cấp:</span
                                >
                                <div class="font-medium text-gray-900">
                                    {{ product.supplier?.name || '—' }}
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-gray-500">
                                    Giá nhập / đối tác / bán:
                                </span>
                                <div class="space-y-1 text-sm text-gray-900">
                                    <div>{{ formatVnd(product.purchase_price) }}</div>
                                    <div>{{ formatVnd(product.partner_price) }}</div>
                                    <div class="font-medium">{{ formatVnd(product.price) }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex-1">
                                    <span class="text-sm text-gray-500"
                                        >Tồn:</span
                                    >
                                    <div>
                                        <Badge variant="outline" class="text-sm">
                                            {{ product.qty_in_stock }}
                                        </Badge>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <span class="text-sm text-gray-500"
                                        >Variants:</span
                                    >
                                    <div>
                                        <Badge variant="outline" class="text-sm">
                                            {{ product.product_items_count }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="products.last_page > 1" class="mt-8 flex justify-center">
                <div class="flex flex-wrap justify-center gap-1">
                    <template v-for="link in products.links" :key="link.label">
                        <Button
                            v-if="link.url"
                            :as="Link"
                            :href="link.url"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            class="h-9 min-w-10"
                        >
                            <span v-html="link.label"></span>
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            size="sm"
                            disabled
                            class="h-9 min-w-10"
                        >
                            <span v-html="link.label"></span>
                        </Button>
                    </template>
                </div>
            </div>
        </div>

        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Xác nhận xóa</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc chắn muốn xóa sản phẩm "{{
                            productToDelete?.name
                        }}" ({{ productToDelete?.code }})? Hành động này không
                        thể hoàn tác và sẽ xóa toàn bộ variants.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="showDeleteDialog = false"
                    >
                        Hủy
                    </Button>
                    <Button variant="destructive" @click="deleteProduct">
                        Xóa
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

