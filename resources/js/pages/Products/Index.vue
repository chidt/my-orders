<script setup lang="ts">
import ProductThumbnailPreview from '@/components/products/ProductThumbnailPreview.vue';
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
import AppMultiselect from '@/components/ui/multiselect/AppMultiselect.vue';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatVnd } from '@/lib/utils';
import {
    create as ProductsCreate,
    destroy as ProductsDestroy,
    edit as ProductsEdit,
    index as ProductsIndex,
} from '@/routes/products';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Edit,
    Filter,
    PackageSearch,
    Plus,
    Search,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

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
    category_id?: string | number;
    product_type_id?: string | number;
    supplier_id?: string | number;
    tag_id?: string | number;
}

interface Props {
    site?: Site;
    products: PaginatedProducts;
    filters: Filters;
    categories: RelatedEntity[];
    suppliers: RelatedEntity[];
    productTypes: RelatedEntity[];
    tags: RelatedEntity[];
}

const props = defineProps<Props>();

const { can } = usePermissions();
const canManageProducts = computed(() => can('manage_products'));

const filters = reactive({
    search: props.filters.search || '',
    category_id: props.filters.category_id || '',
    product_type_id: props.filters.product_type_id || '',
    supplier_id: props.filters.supplier_id || '',
    tag_id: props.filters.tag_id || '',
});

const showFilterDrawer = ref(false);

const performSearch = () => {
    if (!props.site?.slug) return;

    router.get(
        ProductsIndex.url({ site: props.site.slug }),
        { ...filters },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const applyFilters = () => {
    performSearch();
    showFilterDrawer.value = false;
};

const clearFilters = () => {
    filters.category_id = '';
    filters.product_type_id = '';
    filters.supplier_id = '';
    filters.tag_id = '';
    performSearch();
};

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filters.category_id) count++;
    if (filters.product_type_id) count++;
    if (filters.supplier_id) count++;
    if (filters.tag_id) count++;
    return count;
});

const hasActiveFilters = computed(() => activeFiltersCount.value > 0);

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

const breadcrumbs = computed(() => {
    if (!props.site) return [];
    return [
        {
            title: props.site.name,
            href: `/${props.site.slug}/dashboard`,
            current: false,
        },
        {
            title: 'Quản lý sản phẩm',
            href: ProductsIndex.url({ site: props.site.slug }),
            current: true,
        },
    ];
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Quản lý sản phẩm" />

        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header with Title and Search -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">
                        Quản lý sản phẩm
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Danh sách sản phẩm thuộc trang web hiện tại
                    </p>
                </div>
                <div class="flex w-full gap-2 sm:w-auto">
                    <div class="relative flex-1 sm:w-80">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                        />
                        <Input
                            v-model="filters.search"
                            placeholder="Tìm theo tên, mã sản phẩm..."
                            class="h-11 pl-9 text-sm sm:h-10"
                            @keyup.enter="performSearch"
                        />
                        <button
                            v-if="filters.search"
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            type="button"
                            @click="
                                filters.search = '';
                                performSearch();
                            "
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <Button
                        variant="outline"
                        type="button"
                        class="relative h-11 gap-2 px-3 transition-colors sm:h-10"
                        :class="{
                            'border-indigo-600 bg-indigo-50 text-indigo-600 shadow-sm':
                                hasActiveFilters,
                        }"
                        @click="showFilterDrawer = true"
                    >
                        <Filter class="h-4 w-4" />
                        <span class="hidden sm:inline">Bộ lọc</span>
                        <div
                            v-if="activeFiltersCount > 0"
                            class="absolute -top-2 -right-2 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-[10px] font-bold text-white shadow-sm ring-2 ring-white"
                        >
                            {{ activeFiltersCount }}
                        </div>
                    </Button>
                </div>
            </div>

            <div class="mt-6 mb-4 flex w-full justify-end">
                <Button
                    v-if="canManageProducts && props.site?.slug"
                    :as="Link"
                    :href="ProductsCreate.url({ site: props.site.slug })"
                    class="h-11 w-full sm:h-10 sm:w-auto"
                >
                    <Plus class="mr-2 h-4 w-4" />
                    Thêm sản phẩm mới
                </Button>
            </div>

            <div class="mb-6 flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Tổng cộng
                    <span class="font-medium text-gray-900">{{
                        products.total
                    }}</span>
                    sản phẩm
                </p>

                <div v-if="hasActiveFilters" class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 italic"
                        >Đang áp dụng bộ lọc</span
                    >
                    <button
                        @click="clearFilters"
                        class="text-xs font-medium text-indigo-600 underline hover:text-indigo-800"
                    >
                        Xóa tất cả bộ lọc
                    </button>
                </div>
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
                class="hidden overflow-x-auto rounded-xl border border-gray-200 bg-white md:block"
            >
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="border-b border-gray-100 bg-gray-50">
                        <tr>
                            <th
                                scope="col"
                                class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-600 uppercase"
                            >
                                Ảnh
                            </th>
                            <th
                                scope="col"
                                class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-600 uppercase"
                            >
                                Sản phẩm
                            </th>
                            <th
                                scope="col"
                                class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-600 uppercase"
                            >
                                Danh mục
                            </th>
                            <th
                                scope="col"
                                class="px-3 py-3 text-left text-[10px] font-black tracking-wider text-gray-600 uppercase"
                            >
                                Nhà cung cấp
                            </th>
                            <th
                                scope="col"
                                class="px-3 py-3 text-right text-[10px] font-black tracking-wider text-gray-600 uppercase"
                            >
                                Giá
                            </th>
                            <th
                                scope="col"
                                class="px-3 py-3 text-center text-[10px] font-black tracking-wider text-gray-600 uppercase"
                            >
                                Tồn
                            </th>
                            <th
                                scope="col"
                                class="px-3 py-3 text-center text-[10px] font-black tracking-wider text-gray-600 uppercase"
                            >
                                Biến thể
                            </th>
                            <th
                                v-if="canManageProducts"
                                scope="col"
                                class="px-3 py-3 text-center text-[10px] font-black tracking-wider text-gray-600 uppercase"
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
                            <td class="px-3 py-4 whitespace-nowrap">
                                <ProductThumbnailPreview
                                    :src="product.thumbnail_url"
                                    :alt="product.name"
                                />
                            </td>
                            <td class="max-w-[200px] px-3 py-4 lg:max-w-xs">
                                <div class="min-w-0">
                                    <div
                                        class="truncate text-sm font-black text-gray-900"
                                    >
                                        {{ product.name }}
                                    </div>
                                    <div
                                        class="mt-1 flex flex-wrap items-center gap-2"
                                    >
                                        <Badge
                                            variant="outline"
                                            class="text-[10px] font-bold uppercase"
                                        >
                                            {{ product.code }}
                                        </Badge>
                                        <Badge
                                            v-if="product.product_type?.name"
                                            variant="secondary"
                                            class="text-[10px] font-bold uppercase"
                                        >
                                            {{ product.product_type.name }}
                                        </Badge>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ product.category?.name || '—' }}
                                </div>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ product.supplier?.name || '—' }}
                                </div>
                            </td>
                            <td class="px-3 py-4 text-right whitespace-nowrap">
                                <div class="flex flex-col items-end gap-1">
                                    <div
                                        class="flex items-center gap-2 text-[10px]"
                                    >
                                        <span
                                            class="font-black tracking-tight text-gray-400 uppercase"
                                            >Nhập:</span
                                        >
                                        <span
                                            class="font-medium text-gray-700"
                                            >{{
                                                formatVnd(
                                                    product.purchase_price,
                                                )
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center gap-2 text-[10px]"
                                    >
                                        <span
                                            class="font-black tracking-tight text-gray-400 uppercase"
                                            >Đối tác:</span
                                        >
                                        <span
                                            class="font-medium text-gray-700"
                                            >{{
                                                formatVnd(product.partner_price)
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="mt-1 rounded border border-indigo-100 bg-indigo-50 px-2 py-0.5 text-xs font-black text-indigo-700"
                                    >
                                        {{ formatVnd(product.price) }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-4 text-center whitespace-nowrap">
                                <Badge variant="outline">
                                    {{ product.qty_in_stock }}
                                </Badge>
                            </td>
                            <td class="px-3 py-4 text-center whitespace-nowrap">
                                <Badge variant="outline">
                                    {{ product.product_items_count }}
                                </Badge>
                            </td>
                            <td
                                v-if="canManageProducts"
                                class="px-3 py-4 text-center whitespace-nowrap"
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

            <div v-if="products.data.length > 0" class="space-y-4 md:hidden">
                <div
                    v-for="product in products.data"
                    :key="product.id"
                    class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm"
                >
                    <div
                        class="mb-4 flex items-start gap-3 border-b border-gray-50 pb-4"
                    >
                        <ProductThumbnailPreview
                            :src="product.thumbnail_url"
                            :alt="product.name"
                            size-class="h-16 w-16 sm:h-20 sm:w-20 rounded-lg flex-shrink-0"
                        />
                        <div class="min-w-0 flex-1 space-y-1">
                            <h3
                                class="truncate text-base leading-tight font-bold text-gray-900"
                            >
                                {{ product.name }}
                            </h3>
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge
                                    variant="outline"
                                    class="text-[10px] font-bold tracking-wider uppercase"
                                >
                                    {{ product.code }}
                                </Badge>
                                <Badge
                                    v-if="product.product_type?.name"
                                    variant="secondary"
                                    class="text-[10px] font-bold uppercase"
                                >
                                    {{ product.product_type.name }}
                                </Badge>
                            </div>
                        </div>

                        <div
                            v-if="canManageProducts && props.site?.slug"
                            class="flex flex-col items-end gap-1"
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
                                class="h-9 w-9 rounded-full p-0 hover:bg-gray-100"
                            >
                                <Edit class="h-4 w-4" />
                            </Button>
                            <Button
                                @click="confirmDelete(product)"
                                variant="ghost"
                                size="sm"
                                class="h-9 w-9 rounded-full p-0 text-red-500 hover:bg-red-50"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <div>
                                <span
                                    class="block text-[10px] font-black tracking-wider text-gray-400 uppercase"
                                    >Danh mục</span
                                >
                                <div
                                    class="mt-0.5 text-sm font-medium text-gray-900"
                                >
                                    {{ product.category?.name || '—' }}
                                </div>
                            </div>
                            <div>
                                <span
                                    class="block text-[10px] font-black tracking-wider text-gray-400 uppercase"
                                    >Nhà cung cấp</span
                                >
                                <div
                                    class="mt-0.5 text-sm font-medium text-gray-900"
                                >
                                    {{ product.supplier?.name || '—' }}
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <span
                                        class="block text-[10px] font-black tracking-wider text-gray-400 uppercase"
                                        >Tồn kho</span
                                    >
                                    <div class="mt-0.5">
                                        <Badge
                                            variant="outline"
                                            class="text-xs font-black"
                                        >
                                            {{ product.qty_in_stock }}
                                        </Badge>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <span
                                        class="block text-[10px] font-black tracking-wider text-gray-400 uppercase"
                                        >Biến thể</span
                                    >
                                    <div class="mt-0.5">
                                        <Badge
                                            variant="outline"
                                            class="text-xs font-black"
                                        >
                                            {{ product.product_items_count }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2 border-t border-gray-50 pt-4">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500">Giá nhập</span>
                            <span
                                class="text-[10px] font-medium text-gray-900"
                                >{{ formatVnd(product.purchase_price) }}</span
                            >
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500">Giá đối tác</span>
                            <span
                                class="text-[10px] font-medium text-gray-900"
                                >{{ formatVnd(product.partner_price) }}</span
                            >
                        </div>
                        <div
                            class="flex items-center justify-between rounded-lg border border-indigo-100 bg-indigo-50 p-2 text-sm"
                        >
                            <span class="font-bold text-indigo-600"
                                >Giá bán lẻ</span
                            >
                            <span class="font-black text-indigo-700">{{
                                formatVnd(product.price)
                            }}</span>
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
        <!-- Advanced Filter Drawer -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showFilterDrawer"
                class="fixed inset-0 z-50 overflow-hidden"
            >
                <div
                    class="absolute inset-0 cursor-pointer bg-gray-500/75 transition-opacity"
                    @click="showFilterDrawer = false"
                ></div>

                <div
                    class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10"
                >
                    <Transition
                        enter-active-class="transform transition ease-in-out duration-500 sm:duration-700"
                        enter-from-class="translate-x-full"
                        enter-to-class="translate-x-0"
                        leave-active-class="transform transition ease-in-out duration-500 sm:duration-700"
                        leave-from-class="translate-x-0"
                        leave-to-class="translate-x-full"
                    >
                        <div
                            v-if="showFilterDrawer"
                            class="pointer-events-auto w-screen max-w-md"
                        >
                            <div
                                class="flex h-full flex-col overflow-y-auto bg-white py-6 shadow-xl"
                            >
                                <div class="px-4 sm:px-6">
                                    <div
                                        class="flex items-center justify-between border-b border-gray-100 pb-4"
                                    >
                                        <div>
                                            <h2
                                                class="text-lg font-bold text-gray-900"
                                            >
                                                Bộ lọc sản phẩm
                                            </h2>
                                            <p class="text-xs text-gray-500">
                                                Lọc nâng cao theo nhiều tiêu chí
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none"
                                            @click="showFilterDrawer = false"
                                        >
                                            <X class="h-6 w-6" />
                                        </button>
                                    </div>
                                </div>
                                <div
                                    class="relative mt-6 flex-1 space-y-6 px-4 sm:px-6"
                                >
                                    <!-- Category Filter -->
                                    <div>
                                        <label
                                            class="mb-2 block text-[10px] font-black tracking-wider text-gray-400 uppercase"
                                            >Danh mục</label
                                        >
                                        <AppMultiselect
                                            v-model="filters.category_id"
                                            :options="categories"
                                            placeholder="Chọn danh mục"
                                            label="name"
                                            track-by="id"
                                            :reduce="
                                                (opt: RelatedEntity) => opt.id
                                            "
                                            :allow-empty="true"
                                            deselect-label="Xóa"
                                        />
                                    </div>

                                    <!-- Product Type Filter -->
                                    <div>
                                        <label
                                            class="mb-2 block text-[10px] font-black tracking-wider text-gray-400 uppercase"
                                            >Loại sản phẩm</label
                                        >
                                        <AppMultiselect
                                            v-model="filters.product_type_id"
                                            :options="productTypes"
                                            placeholder="Chọn loại sản phẩm"
                                            label="name"
                                            track-by="id"
                                            :reduce="
                                                (opt: RelatedEntity) => opt.id
                                            "
                                            :allow-empty="true"
                                            deselect-label="Xóa"
                                        />
                                    </div>

                                    <!-- Supplier Filter -->
                                    <div>
                                        <label
                                            class="mb-2 block text-[10px] font-black tracking-wider text-gray-400 uppercase"
                                            >Nhà cung cấp</label
                                        >
                                        <AppMultiselect
                                            v-model="filters.supplier_id"
                                            :options="suppliers"
                                            placeholder="Chọn nhà cung cấp"
                                            label="name"
                                            track-by="id"
                                            :reduce="
                                                (opt: RelatedEntity) => opt.id
                                            "
                                            :allow-empty="true"
                                            deselect-label="Xóa"
                                        />
                                    </div>

                                    <!-- Tag Filter -->
                                    <div>
                                        <label
                                            class="mb-2 block text-[10px] font-black tracking-wider text-gray-400 uppercase"
                                            >Tags</label
                                        >
                                        <AppMultiselect
                                            v-model="filters.tag_id"
                                            :options="tags"
                                            placeholder="Chọn tags"
                                            label="name"
                                            track-by="id"
                                            :reduce="
                                                (opt: RelatedEntity) => opt.id
                                            "
                                        />
                                    </div>
                                </div>

                                <div
                                    class="flex flex-shrink-0 items-center justify-between border-t border-gray-100 bg-gray-50 px-4 py-4"
                                >
                                    <button
                                        type="button"
                                        class="text-sm font-medium text-gray-500 underline hover:text-gray-700"
                                        @click="clearFilters"
                                    >
                                        Xóa tất cả
                                    </button>
                                    <div class="flex gap-2">
                                        <Button
                                            variant="outline"
                                            @click="showFilterDrawer = false"
                                            >Hủy</Button
                                        >
                                        <Button
                                            @click="applyFilters"
                                            class="px-8 shadow-sm"
                                        >
                                            Áp dụng
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>

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
                    <Button variant="outline" @click="showDeleteDialog = false">
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
