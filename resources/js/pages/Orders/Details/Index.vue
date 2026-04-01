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
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from '@/components/ui/drawer';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { useOrderCustomerSearchFilter, type OrderSearchCustomer } from '@/composables/useOrderCustomerSearchFilter';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatVnd } from '@/lib/utils';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Pencil, TableOfContents, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

interface Site {
    id: number;
    slug: string;
    name: string;
}

interface Option {
    id?: number;
    value?: string;
    name?: string;
    label?: string;
}

interface StatusTransition {
    value: number;
    label: string;
}

const props = defineProps<{
    site: Site;
    filters: Record<string, any>;
    activeFilterStatus: { value: number; label: string } | null;
    filterStatusTransitions: StatusTransition[];
    orderDetails: any;
    statusOptions: Option[];
    paymentStatusOptions: Option[];
    filterCustomer: OrderSearchCustomer | null;
    products: Option[];
    productItems: Option[];
    productTypes: Option[];
}>();

const page = usePage<{ flash?: { success?: string; error?: string; message?: string } }>();

const filters = reactive({
    search: props.filters.search ?? '',
    product_id: props.filters.product_id ?? '',
    product_item_id: props.filters.product_item_id ?? '',
    product_type_id: props.filters.product_type_id ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    filter_status: props.filters.filter_status ?? '',
});

const {
    customerId: filterCustomerId,
    customerSearch,
    customerOptions,
    isSearchingCustomers,
    isCustomerSuggestionsOpen,
    selectCustomer,
    orderSearchCustomerLabel,
    openSuggestions,
    closeSuggestionsBlur,
} = useOrderCustomerSearchFilter({
    siteSlug: () => props.site.slug,
    getCustomerId: () => props.filters.customer_id || '',
    getFilterCustomer: () => props.filterCustomer,
});

const selectedIds = ref<number[]>([]);
const showBulkConfirmDialog = ref(false);
const pendingBulkTarget = ref<{ value: number; label: string } | null>(null);
const isBulkUpdating = ref(false);
const showFilterModal = ref(false);

const hasStatusFilter = computed(() => String(filters.filter_status ?? '').trim() !== '');

const visibleRowIds = computed(() => (props.orderDetails.data ?? []).map((d: { id: number }) => d.id));

const allVisibleSelected = computed(
    () => visibleRowIds.value.length > 0 && visibleRowIds.value.every((id: number) => selectedIds.value.includes(id)),
);

const toggleAllVisible = () => {
    const ids = visibleRowIds.value;
    if (allVisibleSelected.value) {
        selectedIds.value = selectedIds.value.filter((id) => !ids.includes(id));
    } else {
        selectedIds.value = Array.from(new Set([...selectedIds.value, ...ids]));
    }
};

const toggleSelected = (id: number) => {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter((item) => item !== id);
    } else {
        selectedIds.value = [...selectedIds.value, id];
    }
};

/** Màu nút theo giá trị OrderStatus (đồng bộ với backend enum). */
const transitionButtonClass = (value: number): string => {
    const map: Record<number, string> = {
        1: 'bg-slate-600 hover:bg-slate-700 text-white focus-visible:ring-slate-500',
        2: 'bg-blue-600 hover:bg-blue-700 text-white focus-visible:ring-blue-500',
        3: 'bg-indigo-600 hover:bg-indigo-700 text-white focus-visible:ring-indigo-500',
        4: 'bg-violet-600 hover:bg-violet-700 text-white focus-visible:ring-violet-500',
        5: 'bg-purple-600 hover:bg-purple-700 text-white focus-visible:ring-purple-500',
        6: 'bg-orange-600 hover:bg-orange-700 text-white focus-visible:ring-orange-500',
        7: 'bg-amber-600 hover:bg-amber-700 text-white focus-visible:ring-amber-500',
        8: 'bg-teal-600 hover:bg-teal-700 text-white focus-visible:ring-teal-500',
        9: 'bg-cyan-600 hover:bg-cyan-700 text-white focus-visible:ring-cyan-500',
        10: 'bg-sky-600 hover:bg-sky-700 text-white focus-visible:ring-sky-500',
        11: 'bg-emerald-600 hover:bg-emerald-700 text-white focus-visible:ring-emerald-500',
        12: 'bg-red-600 hover:bg-red-700 text-white focus-visible:ring-red-500',
    };
    return map[value] ?? 'bg-gray-600 hover:bg-gray-700 text-white focus-visible:ring-gray-500';
};

const getBadgeClass = (color: string): string => {
    const colorMap: Record<string, string> = {
        blue: 'bg-blue-100 text-blue-800',
        yellow: 'bg-yellow-100 text-yellow-800',
        orange: 'bg-orange-100 text-orange-800',
        purple: 'bg-purple-100 text-purple-800',
        cyan: 'bg-cyan-100 text-cyan-800',
        teal: 'bg-teal-100 text-teal-800',
        gray: 'bg-gray-100 text-gray-800',
        indigo: 'bg-indigo-100 text-indigo-800',
        pink: 'bg-pink-100 text-pink-800',
        lime: 'bg-lime-100 text-lime-800',
        green: 'bg-green-100 text-green-800',
        red: 'bg-red-100 text-red-800',
        slate: 'bg-slate-100 text-slate-800',
        violet: 'bg-violet-100 text-violet-800',
        amber: 'bg-amber-100 text-amber-800',
        sky: 'bg-sky-100 text-sky-800',
        emerald: 'bg-emerald-100 text-emerald-800',
    };
    return colorMap[color] || 'bg-gray-100 text-gray-800';
};

const fetchList = () => {
    selectedIds.value = [];
    router.get(
        `/${props.site.slug}/order-details`,
        {
            ...filters,
            customer_id: filterCustomerId.value.trim() || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const onFilterStatusChange = () => {
    fetchList();
};

const clearStatusFilter = () => {
    filters.filter_status = '';
    fetchList();
};

const applyFilters = () => {
    fetchList();
};

const openBulkConfirm = (targetStatus: number) => {
    if (selectedIds.value.length === 0) {
        return;
    }
    const label = props.filterStatusTransitions.find((t) => t.value === targetStatus)?.label ?? String(targetStatus);
    pendingBulkTarget.value = { value: targetStatus, label };
    showBulkConfirmDialog.value = true;
};

const closeBulkConfirm = () => {
    showBulkConfirmDialog.value = false;
    pendingBulkTarget.value = null;
    isBulkUpdating.value = false;
};

const openFilterModal = () => {
    showFilterModal.value = true;
};

const closeFilterModal = () => {
    showFilterModal.value = false;
};

const clearAllFilters = () => {
    filters.date_from = '';
    filters.date_to = '';
    filterCustomerId.value = '';
    customerSearch.value = '';
};

const applyAdvancedFilters = () => {
    fetchList();
    closeFilterModal();
};

const viewDetail = (id: number) => {
    router.get(`/${props.site.slug}/order-details/${id}`);
};

const confirmBulkUpdate = () => {
    if (!pendingBulkTarget.value || selectedIds.value.length === 0) {
        return;
    }
    isBulkUpdating.value = true;
    const targetValue = pendingBulkTarget.value.value;
    router.patch(
        `/${props.site.slug}/order-details/bulk/status`,
        {
            order_detail_ids: selectedIds.value,
            status: targetValue,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
                closeBulkConfirm();
            },
            onError: () => {
                isBulkUpdating.value = false;
            },
            onFinish: () => {
                isBulkUpdating.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="`Chi tiết đơn hàng - ${site.name}`" />

    <AppLayout
        :breadcrumbs="[
            { title: site.name, href: `/${site.slug}/dashboard`, current: false },
            { title: 'Chi tiết đơn hàng', href: `/${site.slug}/order-details`, current: true },
        ]"
    >
        <div class="space-y-4 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <!-- Header with Title and Search -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h1 class="text-xl sm:text-2xl font-bold">Chi tiết đơn hàng</h1>
                <div class="w-full sm:w-80">
                    <div class="relative">
                        <Input
                            v-model="filters.search"
                            placeholder="Tìm kiếm mã đơn, khách hàng, SKU..."
                            class="w-full h-11 sm:h-10 pr-10"
                            @input="applyFilters"
                            @keyup.enter="applyFilters"
                        />
                        <button
                            type="button"
                            @click="applyFilters"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="page.props.flash?.success || page.props.flash?.message" class="rounded-md border border-green-200 bg-green-50 p-3 sm:px-4 sm:py-3">
                <p class="text-sm font-medium text-green-800">
                    {{ page.props.flash?.success || page.props.flash?.message }}
                </p>
            </div>
            <div v-if="page.props.flash?.error" class="rounded-md border border-red-200 bg-red-50 p-3 sm:px-4 sm:py-3">
                <p class="text-sm font-medium text-red-800">
                    {{ page.props.flash.error }}
                </p>
            </div>

            <div class="rounded-lg border bg-white p-3 sm:p-4 space-y-3">
                <p class="text-sm font-medium text-gray-700">Lọc theo trạng thái chi tiết</p>
                <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                    <div class="w-full sm:min-w-[200px] sm:flex-1">
                        <select
                            v-model="filters.filter_status"
                            class="h-11 sm:h-10 w-full rounded-md border px-3 text-sm"
                            @change="onFilterStatusChange"
                        >
                            <option value="">— Chọn trạng thái —</option>
                            <option v-for="statusOption in statusOptions" :key="statusOption.value" :value="String(statusOption.value)">
                                {{ statusOption.label }}
                            </option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <Button v-if="hasStatusFilter" variant="outline" type="button" class="h-11 sm:h-auto" @click="clearStatusFilter">
                            Bỏ lọc trạng thái
                        </Button>
                        <Button variant="outline" type="button" class="h-11 sm:h-auto gap-2" @click="openFilterModal">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707v4.586a1 1 0 01-.293.707L9 19.414A1 1 0 018 18.707V14.121a1 1 0 00-.293-.707L1.293 6.707A1 1 0 011 6V4z"/>
                            </svg>
                            Bộ lọc
                        </Button>
                    </div>
                </div>
            </div>

            <div
                v-if="hasStatusFilter && activeFilterStatus && filterStatusTransitions.length > 0 && orderDetails.total > 0"
                class="space-y-3"
            >
                <span class="text-sm text-gray-600">Đã chọn {{ selectedIds.length }} dòng.</span>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="t in filterStatusTransitions"
                        :key="t.value"
                        type="button"
                        :disabled="selectedIds.length === 0"
                        :class="[
                            'min-h-11 sm:min-h-10 rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40',
                            transitionButtonClass(t.value),
                        ]"
                        @click="openBulkConfirm(t.value)"
                    >
                        {{ t.label }}
                    </button>
                </div>
            </div>


            <!-- Luôn hiển thị bảng danh sách, không yêu cầu chọn trạng thái -->
            <div>
                <p v-if="activeFilterStatus" class="rounded-t-lg border border-b-0 bg-gray-50 px-3 sm:px-4 py-2 text-sm text-gray-700">
                    Đang lọc theo trạng thái: <span class="font-semibold">{{ activeFilterStatus.label }}</span>
                    <span class="text-gray-500">({{ orderDetails.total }} bản ghi)</span>
                </p>
                <p v-else-if="filters.filter_status && !activeFilterStatus" class="rounded-t-lg border border-b-0 bg-amber-50 px-3 sm:px-4 py-2 text-sm text-amber-900">
                    Giá trị lọc trạng thái không hợp lệ. Chọn lại trạng thái khác.
                </p>
                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-x-auto rounded-b-lg border bg-white">
                    <table class="w-full min-w-[1200px] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="w-10 px-4 py-2 text-left text-xs uppercase">
                                    <input type="checkbox" :checked="allVisibleSelected" @change="toggleAllVisible" />
                                </th>
                                <th class="px-4 py-2 text-left text-xs uppercase">Sản phẩm</th>
                                <th class="px-4 py-2 text-left text-xs uppercase">Khách hàng</th>
                                <th class="px-4 py-2 text-right text-xs uppercase">SL</th>
                                <th class="px-4 py-2 text-right text-xs uppercase">Giá</th>
                                <th class="px-4 py-2 text-right text-xs uppercase">Tổng</th>
                                <th class="px-4 py-2 text-left text-xs uppercase">Trạng thái</th>
                                <th class="px-4 py-2 text-center text-xs uppercase">Chức năng</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="!orderDetails.data?.length">
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">Không có chi tiết đơn hàng nào.</td>
                            </tr>
                            <tr
                                v-for="detail in orderDetails.data"
                                :key="detail.id"
                                class="cursor-pointer hover:bg-gray-50"
                                @click="viewDetail(detail.id)"
                            >
                                <td class="px-4 py-2 text-sm" @click.stop>
                                    <input
                                        type="checkbox"
                                        :checked="selectedIds.includes(detail.id)"
                                        @change="toggleSelected(detail.id)"
                                    />
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    <div class="flex items-center gap-3">
                                        <ProductThumbnailPreview
                                            :src="detail.product_item.image"
                                            :alt="detail.product.name"
                                            size-class="h-12 w-12"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate font-medium">{{ detail.product.name }}</div>
                                            <div class="text-xs text-gray-500">SKU: {{ detail.product_item.sku }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-sm">{{ detail.customer.name }}</td>
                                <td class="px-4 py-2 text-right text-sm">{{ detail.qty }}</td>
                                <td class="px-4 py-2 text-right text-sm">{{ formatVnd(detail.price) }}</td>
                                <td class="px-4 py-2 text-right text-sm">{{ formatVnd(detail.total) }}</td>
                                <td class="px-4 py-2 text-sm">
                                    <div class="flex flex-col gap-1">
                                        <Badge :class="getBadgeClass(detail.status_color)">{{ detail.status_label }}</Badge>
                                        <Badge :class="getBadgeClass(detail.payment_status_color)">{{ detail.payment_status_label }}</Badge>
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-center" @click.stop>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="h-8 w-8 p-0"
                                            >
                                                <TableOfContents
                                                    class="h-4 w-4"
                                                />
                                                <span class="sr-only"
                                                    >Thao tác</span
                                                >
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem
                                                @click="viewDetail(detail.id)"
                                            >
                                                <Eye class="mr-2 h-4 w-4" />
                                                <span>Xem chi tiết</span>
                                            </DropdownMenuItem>
                                            <!-- Chỗ này có thể thêm Sửa/Xóa về sau nếu cần -->
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="lg:hidden rounded-b-lg border bg-white">
                    <div v-if="!orderDetails.data?.length" class="px-4 py-8 text-center text-sm text-gray-500">
                        Không có chi tiết đơn hàng nào với bộ lọc hiện tại.
                    </div>
                    <div v-else>
                        <!-- Mobile Select All Header -->
                        <div class="border-b border-gray-100 p-3 sm:p-4">
                            <label class="flex items-center gap-3 text-sm">
                                <input
                                    type="checkbox"
                                    :checked="allVisibleSelected"
                                    @change="toggleAllVisible"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="font-medium text-gray-700">
                                    Chọn tất cả ({{ orderDetails.data.length }} mục)
                                </span>
                                <span v-if="selectedIds.length > 0" class="text-xs text-gray-500">
                                    - {{ selectedIds.length }} đã chọn
                                </span>
                            </label>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="divide-y divide-gray-100">
                            <div
                                v-for="detail in orderDetails.data"
                                :key="detail.id"
                                class="cursor-pointer p-3 hover:bg-gray-50 sm:p-4"
                                @click="viewDetail(detail.id)"
                            >
                                <div
                                    class="mb-3 flex items-center justify-between gap-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            :checked="
                                                selectedIds.includes(detail.id)
                                            "
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            @change="toggleSelected(detail.id)"
                                            @click.stop
                                        />
                                        <div class="flex flex-wrap gap-1">
                                            <Badge
                                                :class="
                                                    getBadgeClass(
                                                        detail.status_color,
                                                    )
                                                "
                                                class="text-[10px] sm:text-xs"
                                                >{{ detail.status_label }}</Badge
                                            >
                                            <Badge
                                                :class="
                                                    getBadgeClass(
                                                        detail.payment_status_color,
                                                    )
                                                "
                                                class="text-[10px] sm:text-xs"
                                                >{{
                                                    detail.payment_status_label
                                                }}</Badge
                                            >
                                        </div>
                                    </div>
                                    <div @click.stop>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-8 w-8 p-0"
                                                >
                                                    <TableOfContents
                                                        class="h-4 w-4"
                                                    />
                                                    <span class="sr-only"
                                                        >Thao tác</span
                                                    >
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem
                                                    @click="
                                                        viewDetail(detail.id)
                                                    "
                                                >
                                                    <Eye class="mr-2 h-4 w-4" />
                                                    <span>Xem chi tiết</span>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <!-- Product Info with Image -->
                                    <div class="flex items-center gap-3">
                                        <ProductThumbnailPreview
                                            :src="detail.product_item.image"
                                            :alt="detail.product.name"
                                            size-class="h-16 w-16 sm:h-20 sm:w-20"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="text-sm font-medium sm:text-base"
                                            >
                                                {{ detail.product.name }}
                                            </div>
                                            <div
                                                class="mt-1 text-xs text-gray-500"
                                            >
                                                SKU: {{ detail.product_item.sku }}
                                            </div>
                                            <div
                                                class="mt-1 text-xs text-gray-600"
                                            >
                                                Khách: {{ detail.customer.name }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Order Details -->
                                    <div class="grid grid-cols-3 gap-3 text-sm">
                                        <div class="text-center">
                                            <div class="text-xs text-gray-500">
                                                Số lượng
                                            </div>
                                            <div class="font-medium">
                                                {{ detail.qty }}
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-xs text-gray-500">
                                                Đơn giá
                                            </div>
                                            <div class="font-medium">
                                                {{ formatVnd(detail.price) }}
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-xs text-gray-500">
                                                Tổng
                                            </div>
                                            <div class="font-medium">
                                                {{ formatVnd(detail.total) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="orderDetails.total > orderDetails.per_page" class="mt-4 sm:mt-6">
                <nav class="flex items-center justify-between">
                    <!-- Mobile Pagination -->
                    <div class="flex flex-col gap-3 w-full sm:hidden">
                        <!-- Page Info -->
                        <div class="text-center">
                            <p class="text-xs text-gray-700">
                                Trang {{ orderDetails.current_page }} / {{ orderDetails.last_page }}
                                ({{ orderDetails.total }} bản ghi)
                            </p>
                        </div>
                        <!-- Navigation Controls -->
                        <div class="flex items-center justify-center gap-1">
                            <!-- Previous Button -->
                            <Link
                                v-if="orderDetails.current_page > 1 && orderDetails.links[0].url"
                                :href="orderDetails.links[0].url"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 min-h-11"
                            >
                                ‹ Trước
                            </Link>
                            <span
                                v-else
                                class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-400 min-h-11"
                            >
                                ‹ Trước
                            </span>

                            <!-- Page Numbers (show current and adjacent pages) -->
                            <template v-for="link in orderDetails.links.slice(1, -1)" :key="link.label">
                                <Link
                                    v-if="link.url && (parseInt(link.label) === orderDetails.current_page || Math.abs(parseInt(link.label) - orderDetails.current_page) <= 1)"
                                    :href="link.url"
                                    :class="[
                                        'inline-flex items-center border px-3 py-2 text-sm font-medium min-h-11',
                                        link.active
                                            ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                    ]"
                                >
                                    {{ link.label }}
                                </Link>
                                <span
                                    v-else-if="!link.url && (parseInt(link.label) === orderDetails.current_page || Math.abs(parseInt(link.label) - orderDetails.current_page) <= 1)"
                                    :class="[
                                        'inline-flex items-center border px-3 py-2 text-sm font-medium min-h-11',
                                        link.active
                                            ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                            : 'bg-white border-gray-300 text-gray-500',
                                    ]"
                                >
                                    {{ link.label }}
                                </span>
                                <!-- Show ellipsis for gaps -->
                                <span
                                    v-else-if="parseInt(link.label) === orderDetails.current_page + 2 && orderDetails.current_page < orderDetails.last_page - 2"
                                    class="inline-flex items-center px-2 py-2 text-sm text-gray-500 min-h-11"
                                >
                                    ...
                                </span>
                            </template>

                            <!-- Next Button -->
                            <Link
                                v-if="orderDetails.current_page < orderDetails.last_page && orderDetails.links[orderDetails.links.length - 1].url"
                                :href="orderDetails.links[orderDetails.links.length - 1].url"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 min-h-11"
                            >
                                Sau ›
                            </Link>
                            <span
                                v-else
                                class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-400 min-h-11"
                            >
                                Sau ›
                            </span>
                        </div>
                    </div>

                    <!-- Desktop Pagination -->
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs sm:text-sm text-gray-700">
                                Hiển thị từ
                                <span class="font-medium">{{ (orderDetails.current_page - 1) * orderDetails.per_page + 1 }}</span>
                                đến
                                <span class="font-medium">{{ Math.min(orderDetails.current_page * orderDetails.per_page, orderDetails.total) }}</span>
                                trong tổng số
                                <span class="font-medium">{{ orderDetails.total }}</span>
                                chi tiết đơn hàng
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm">
                                <template v-for="link in orderDetails.links" :key="link.label">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        :class="[
                                            'relative inline-flex items-center border px-3 sm:px-4 py-2 text-sm font-medium min-h-10',
                                            link.active
                                                ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                            link.label === 'Previous' || link.label.includes('Previous') || link.label.includes('Trước')
                                                ? 'rounded-l-md'
                                                : link.label === 'Next' || link.label.includes('Next') || link.label.includes('Sau')
                                                ? 'rounded-r-md'
                                                : '',
                                        ]"
                                    >
                                        <span v-if="link.label === 'Previous' || link.label.includes('Previous')" class="sr-only">Trang trước</span>
                                        <span v-else-if="link.label === 'Next' || link.label.includes('Next')" class="sr-only">Trang sau</span>
                                        <span v-html="link.label.includes('Previous') ? '‹' : link.label.includes('Next') ? '›' : link.label"></span>
                                    </Link>
                                    <span
                                        v-else
                                        :class="[
                                            'relative inline-flex items-center border px-3 sm:px-4 py-2 text-sm font-medium min-h-10',
                                            link.active
                                                ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                : 'bg-white border-gray-300 text-gray-500',
                                        ]"
                                    >
                                        <span v-html="link.label"></span>
                                    </span>
                                </template>
                            </nav>
                        </div>
                    </div>
                </nav>
            </div>
        </div>

        <!-- Filter Drawer -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showFilterModal" class="fixed inset-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent z-50">
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-gray-500/75 cursor-pointer"
                    @click="closeFilterModal"
                ></div>

                <div class="absolute inset-0 pl-0 sm:pl-10 focus:outline-none lg:pl-16 pointer-events-none">
                    <!-- Panel with Right-to-Left Animation -->
                    <Transition
                        enter-active-class="transition-transform duration-500 ease-in-out sm:duration-700"
                        enter-from-class="translate-x-full"
                        enter-to-class="translate-x-0"
                        leave-active-class="transition-transform duration-500 ease-in-out sm:duration-700"
                        leave-from-class="translate-x-0"
                        leave-to-class="translate-x-full"
                    >
                        <div v-if="showFilterModal" class="relative ml-auto block size-full w-full sm:max-w-md pointer-events-auto">
                            <!-- Close button -->
                            <Transition
                                enter-active-class="transition-opacity duration-500 ease-in-out delay-150"
                                enter-from-class="opacity-0"
                                enter-to-class="opacity-100"
                                leave-active-class="transition-opacity duration-300 ease-in-out"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                            >
                                <div v-if="showFilterModal" class="absolute top-0 left-0 -ml-0 sm:-ml-8 lg:-ml-10 flex pt-4 pr-2 sm:pr-4">
                                    <button
                                        type="button"
                                        @click="closeFilterModal"
                                        class="relative rounded-md text-gray-300 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors sm:block hidden"
                                    >
                                        <span class="absolute -inset-2.5"></span>
                                        <span class="sr-only">Đóng panel</span>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6">
                                            <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                </div>
                            </Transition>

                            <div class="relative flex h-full flex-col overflow-y-auto bg-white py-6 shadow-xl">
                                <!-- Header with mobile close button -->
                                <div class="px-4 sm:px-6">
                                    <div class="flex items-center justify-between mb-2 sm:mb-0">
                                        <div class="flex-1">
                                            <h2 class="text-lg font-semibold text-gray-900">Bộ lọc nâng cao</h2>
                                            <p class="text-sm text-gray-600 mt-1">Thiết lập các bộ lọc theo khách hàng và ngày tháng</p>
                                        </div>
                                        <!-- Mobile close button -->
                                        <button
                                            type="button"
                                            @click="closeFilterModal"
                                            class="sm:hidden rounded-md p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                                        >
                                            <span class="sr-only">Đóng panel</span>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6">
                                                <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="relative mt-6 flex-1 px-4 sm:px-6 space-y-4">
                                    <!-- Customer Filter -->
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-gray-700">Khách hàng</label>
                                        <div class="relative">
                                            <Input
                                                v-model="customerSearch"
                                                type="text"
                                                class="h-11 w-full"
                                                placeholder="Tên / SĐT / email..."
                                                @focus="openSuggestions"
                                                @blur="closeSuggestionsBlur"
                                            />
                                            <div
                                                v-if="isCustomerSuggestionsOpen && customerSearch.trim().length >= 2"
                                                class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-white shadow-lg"
                                            >
                                                <div v-if="isSearchingCustomers" class="px-3 py-2 text-sm text-gray-500">Đang tìm...</div>
                                                <button
                                                    v-for="c in customerOptions"
                                                    :key="c.id"
                                                    type="button"
                                                    class="flex w-full items-center px-3 py-2 text-left text-sm hover:bg-gray-50 min-h-11"
                                                    @mousedown.prevent="selectCustomer(c)"
                                                >
                                                    {{ orderSearchCustomerLabel(c) }}
                                                </button>
                                                <div v-if="!isSearchingCustomers && customerOptions.length === 0" class="px-3 py-2 text-sm text-gray-500">
                                                    Không tìm thấy khách hàng.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Date Range -->
                                    <div class="space-y-4">
                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-gray-700">Từ ngày</label>
                                            <Input v-model="filters.date_from" type="date" class="h-11 w-full" />
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-gray-700">Đến ngày</label>
                                            <Input v-model="filters.date_to" type="date" class="h-11 w-full" />
                                        </div>
                                    </div>

                                    <!-- Current Filter Status -->
                                    <div v-if="filters.date_from || filters.date_to || filterCustomerId.trim()" class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                                        <h4 class="text-sm font-medium text-blue-900 mb-2">Bộ lọc hiện tại:</h4>
                                        <ul class="text-xs text-blue-800 space-y-1">
                                            <li v-if="filterCustomerId.trim()">
                                                <strong>Khách hàng:</strong> {{ customerSearch || 'Đã chọn' }}
                                            </li>
                                            <li v-if="filters.date_from">
                                                <strong>Từ ngày:</strong> {{ filters.date_from }}
                                            </li>
                                            <li v-if="filters.date_to">
                                                <strong>Đến ngày:</strong> {{ filters.date_to }}
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Clear Filters -->
                                    <div v-if="filters.date_from || filters.date_to || filterCustomerId.trim()" class="pt-4 border-t">
                                        <Button
                                            variant="ghost"
                                            type="button"
                                            @click="clearAllFilters"
                                            class="w-full text-red-600 hover:text-red-700 hover:bg-red-50"
                                        >
                                            Xóa tất cả bộ lọc
                                        </Button>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="mt-6 border-t pt-4 px-4 sm:px-6">
                                    <div class="grid grid-cols-2 gap-3">
                                        <Button variant="outline" class="w-full min-h-11" @click="closeFilterModal">
                                            Hủy
                                        </Button>
                                        <Button @click="applyAdvancedFilters" class="w-full min-h-11">
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

        <Dialog :open="showBulkConfirmDialog" @update:open="(open: boolean) => !open && closeBulkConfirm()">
            <DialogContent class="mx-4 sm:mx-0 sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle class="text-lg sm:text-xl">Xác nhận cập nhật trạng thái</DialogTitle>
                    <DialogDescription class="text-sm sm:text-base">
                        Bạn có chắc muốn cập nhật
                        <span class="font-semibold">{{ selectedIds.length }}</span>
                        chi tiết đã chọn sang trạng thái
                        <span class="font-semibold">«{{ pendingBulkTarget?.label }}»</span>?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="flex-col gap-2 sm:flex-row sm:gap-0">
                    <Button variant="outline" type="button" :disabled="isBulkUpdating" @click="closeBulkConfirm" class="w-full sm:w-auto min-h-11 sm:min-h-auto">
                        Hủy
                    </Button>
                    <Button type="button" :disabled="isBulkUpdating" @click="confirmBulkUpdate" class="w-full sm:w-auto min-h-11 sm:min-h-auto">
                        <span v-if="isBulkUpdating">Đang cập nhật...</span>
                        <span v-else>Cập nhật</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
