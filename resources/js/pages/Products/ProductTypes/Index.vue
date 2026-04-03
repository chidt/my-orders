<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Edit,
    Eye,
    EyeOff,
    Palette,
    Plus,
    Search,
    Trash2,
    X,
} from 'lucide-vue-next';
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
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    create as ProductTypesCreate,
    destroy as ProductTypesDestroy,
    edit as ProductTypesEdit,
    index as ProductTypesIndex,
    update as ProductTypesUpdate,
} from '@/routes/product-types';

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
    site?: Site;
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
    if (productTypeToDelete.value && props.site?.slug) {
        router.delete(
            ProductTypesDestroy.url({
                site: props.site.slug,
                product_type: productTypeToDelete.value.id,
            }),
            {
                onSuccess: () => {
                    showDeleteDialog.value = false;
                    productTypeToDelete.value = null;
                },
            },
        );
    }
};

const performSearch = () => {
    if (!props.site?.slug) return;

    const params = new URLSearchParams();
    if (searchQuery.value) {
        params.set('search', searchQuery.value);
    }

    router.get(
        ProductTypesIndex.url({ site: props.site.slug }) +
            '?' +
            params.toString(),
        {},
        {
            preserveState: true,
            replace: true,
        },
    );
};

const toggleShowOnFront = (productType: ProductType) => {
    if (!props.site?.slug) return;

    router.put(
        ProductTypesUpdate.url({
            site: props.site.slug,
            product_type: productType.id,
        }),
        {
            name: productType.name,
            order: productType.order,
            show_on_front: !productType.show_on_front,
            color: productType.color,
        },
        {
            preserveScroll: true,
        },
    );
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

const breadcrumbs = computed(() => {
    if (!props.site) return [];
    return [
        {
            title: props.site.name,
            href: `/${props.site.slug}/dashboard`,
            current: false,
        },
        {
            title: 'Quản lý loại sản phẩm',
            href: ProductTypesIndex.url({ site: props.site.slug }),
            current: true,
        },
    ];
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Quản lý loại sản phẩm" />

        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header with Title and Search -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">
                        Quản lý loại sản phẩm
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Quản lý các loại sản phẩm trong cửa hàng của bạn
                    </p>
                </div>
                <div class="w-full sm:w-80">
                    <div class="relative">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                        />
                        <Input
                            v-model="searchQuery"
                            placeholder="Tìm kiếm theo tên loại sản phẩm..."
                            class="h-11 pl-9 text-sm sm:h-10"
                            @keyup.enter="performSearch"
                        />
                        <button
                            v-if="searchQuery"
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            type="button"
                            @click="
                                searchQuery = '';
                                performSearch();
                            "
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-6 mb-4 flex w-full justify-end">
                <Button
                    v-if="canManageProductTypes && props.site?.slug"
                    :as="Link"
                    :href="ProductTypesCreate.url({ site: props.site.slug })"
                    class="h-11 w-full sm:h-10 sm:w-auto"
                >
                    <Plus class="mr-2 h-4 w-4" />
                    Thêm loại sản phẩm
                </Button>
            </div>

            <!-- Summary -->
            <div class="mb-6">
                <p class="text-sm text-gray-600">
                    Tổng cộng
                    <span class="font-medium">{{ productTypes.total }}</span>
                    loại sản phẩm
                </p>
            </div>

            <!-- Content -->
            <div
                v-if="productTypes.data.length === 0"
                class="rounded-lg border border-gray-200 bg-white py-12 text-center"
            >
                <Palette class="mx-auto h-16 w-16 text-gray-300" />
                <h3 class="mt-4 text-lg font-medium text-gray-900">
                    Chưa có loại sản phẩm
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                    Bắt đầu bằng cách tạo loại sản phẩm đầu tiên.
                </p>
                <div class="mt-6">
                    <Button
                        v-if="canManageProductTypes && props.site?.slug"
                        :as="Link"
                        :href="
                            ProductTypesCreate.url({ site: props.site.slug })
                        "
                        class="flex items-center gap-2"
                    >
                        <Plus class="h-4 w-4" />
                        Thêm loại sản phẩm
                    </Button>
                </div>
            </div>

            <!-- Desktop Table -->
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
                                Tên loại sản phẩm
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Màu sắc
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Thứ tự
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Hiển thị trang chủ
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Số sản phẩm
                            </th>
                            <th
                                v-if="canManageProductTypes"
                                scope="col"
                                class="px-6 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Thao tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr
                            v-for="productType in productTypes.data"
                            :key="productType.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ productType.name }}
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-4 w-4 rounded-full border"
                                        :style="{
                                            backgroundColor: productType.color,
                                        }"
                                    ></div>
                                    <Badge
                                        variant="outline"
                                        :style="
                                            getColorBadgeStyle(
                                                productType.color,
                                            )
                                        "
                                    >
                                        {{ productType.color }}
                                    </Badge>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ productType.order }}
                                </div>
                            </td>

                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <Button
                                    v-if="canManageProductTypes"
                                    variant="ghost"
                                    size="sm"
                                    @click="toggleShowOnFront(productType)"
                                    class="p-2"
                                >
                                    <Eye
                                        v-if="productType.show_on_front"
                                        class="h-4 w-4 text-green-600"
                                    />
                                    <EyeOff
                                        v-else
                                        class="h-4 w-4 text-gray-400"
                                    />
                                </Button>
                                <Badge
                                    v-else
                                    :variant="
                                        productType.show_on_front
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        productType.show_on_front
                                            ? 'Có'
                                            : 'Không'
                                    }}
                                </Badge>
                            </td>

                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <Badge variant="outline">
                                    {{ productType.products_count }}
                                </Badge>
                            </td>

                            <td
                                v-if="canManageProductTypes"
                                class="px-6 py-4 text-center whitespace-nowrap"
                            >
                                <div class="flex justify-center gap-2">
                                    <Button
                                        v-if="props.site?.slug"
                                        :as="Link"
                                        :href="
                                            ProductTypesEdit.url({
                                                site: props.site.slug,
                                                product_type: productType.id,
                                            })
                                        "
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
                                        :disabled="
                                            productType.products_count > 0
                                        "
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile List -->
            <div
                v-if="productTypes.data.length > 0"
                class="space-y-3 md:hidden"
            >
                <div
                    v-for="productType in productTypes.data"
                    :key="productType.id"
                    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <!-- Header -->
                    <div class="mb-3 flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <h3
                                class="truncate text-lg font-medium text-gray-900"
                            >
                                {{ productType.name }}
                            </h3>
                            <div class="mt-1 flex items-center gap-2">
                                <div
                                    class="h-4 w-4 rounded-full border"
                                    :style="{
                                        backgroundColor: productType.color,
                                    }"
                                ></div>
                                <Badge
                                    variant="outline"
                                    :style="
                                        getColorBadgeStyle(productType.color)
                                    "
                                    class="text-xs"
                                >
                                    {{ productType.color }}
                                </Badge>
                            </div>
                        </div>
                        <div
                            v-if="canManageProductTypes"
                            class="ml-3 flex gap-1"
                        >
                            <Button
                                v-if="props.site?.slug"
                                :as="Link"
                                :href="
                                    ProductTypesEdit.url({
                                        site: props.site.slug,
                                        product_type: productType.id,
                                    })
                                "
                                variant="ghost"
                                size="sm"
                                class="h-8 w-8 p-2"
                            >
                                <Edit class="h-4 w-4" />
                            </Button>

                            <Button
                                @click="confirmDelete(productType)"
                                variant="ghost"
                                size="sm"
                                class="h-8 w-8 p-2 text-red-600 hover:text-red-700"
                                :disabled="productType.products_count > 0"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Thứ tự:</span
                                >
                                <div class="font-medium text-gray-900">
                                    {{ productType.order }}
                                </div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Số sản phẩm:</span
                                >
                                <div>
                                    <Badge variant="outline" class="text-sm">
                                        {{ productType.products_count }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500"
                                >Hiển thị trang chủ:</span
                            >
                            <div class="mt-1 flex items-center">
                                <Button
                                    v-if="canManageProductTypes"
                                    variant="ghost"
                                    size="sm"
                                    @click="toggleShowOnFront(productType)"
                                    class="-ml-1 h-7 w-7 p-1"
                                >
                                    <Eye
                                        v-if="productType.show_on_front"
                                        class="h-4 w-4 text-green-600"
                                    />
                                    <EyeOff
                                        v-else
                                        class="h-4 w-4 text-gray-400"
                                    />
                                </Button>
                                <Badge
                                    v-else
                                    :variant="
                                        productType.show_on_front
                                            ? 'default'
                                            : 'secondary'
                                    "
                                    class="text-sm"
                                >
                                    {{
                                        productType.show_on_front
                                            ? 'Có'
                                            : 'Không'
                                    }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="productTypes.last_page > 1"
                class="mt-8 flex justify-center"
            >
                <div class="flex flex-wrap justify-center gap-1">
                    <template
                        v-for="link in productTypes.links"
                        :key="link.label"
                    >
                        <Button
                            v-if="link.url"
                            :as="Link"
                            :href="link.url"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            class="h-9 min-w-10"
                        >
                            {{ link.label }}
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            size="sm"
                            disabled
                            class="h-9 min-w-10"
                        >
                            {{ link.label }}
                        </Button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Xác nhận xóa</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc chắn muốn xóa loại sản phẩm "{{
                            productTypeToDelete?.name
                        }}"?
                        <br />
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
