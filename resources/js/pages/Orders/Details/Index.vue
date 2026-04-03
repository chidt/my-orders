<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Check, Eye, Filter, Search, X } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';
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
import AppPagination from '@/components/ui/pagination/AppPagination.vue';
import {
    useOrderCustomerSearchFilter,
    type OrderSearchCustomer,
} from '@/composables/useOrderCustomerSearchFilter';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatVnd } from '@/lib/utils';
import type { AppPageProps } from '@/types';

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
    suppliers: Option[];
}>();

const page = usePage<
    AppPageProps<{
        flash?: { success?: string; error?: string; message?: string };
    }>
>();

const filters = reactive({
    search: props.filters.search ?? '',
    product_id: props.filters.product_id ?? '',
    product_item_id: props.filters.product_item_id ?? '',
    product_type_id: props.filters.product_type_id ?? '',
    supplier_id: props.filters.supplier_id ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    filter_status: props.filters.filter_status ?? '',
    per_page: props.filters.per_page ?? 50,
});

const {
    customerId: filterCustomerId,
    customerSearch,
    customerOptions,
    isSearchingCustomers,
    selectCustomer,
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

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filters.filter_status) count++;
    if (filterCustomerId.value) count++;
    if (filters.product_id) count++;
    if (filters.product_item_id) count++;
    if (filters.product_type_id) count++;
    if (filters.supplier_id) count++;
    if (filters.date_from) count++;
    if (filters.date_to) count++;
    return count;
});

const hasActiveFilters = computed(() => activeFiltersCount.value > 0);

const visibleRowIds = computed(() =>
    (props.orderDetails.data ?? []).map((d: { id: number }) => d.id),
);

const allVisibleSelected = computed(
    () =>
        visibleRowIds.value.length > 0 &&
        visibleRowIds.value.every((id: number) =>
            selectedIds.value.includes(id),
        ),
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
    return (
        map[value] ??
        'bg-gray-600 hover:bg-gray-700 text-white focus-visible:ring-gray-500'
    );
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
            per_page: filters.per_page,
            page: 1, // Always reset to page 1 when applying filters
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const handlePagination = (url: string) => {
    // Parse the page number from the URL
    const urlObj = new URL(url, window.location.origin);
    const page = urlObj.searchParams.get('page') || 1;
    router.get(
        `/${props.site.slug}/order-details`,
        {
            ...filters,
            customer_id: filterCustomerId.value.trim() || undefined,
            page,
            per_page: filters.per_page,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const applyFilters = () => {
    fetchList();
};

const openBulkConfirm = (targetStatus: number) => {
    if (selectedIds.value.length === 0) {
        return;
    }
    const label =
        props.filterStatusTransitions.find((t) => t.value === targetStatus)
            ?.label ?? String(targetStatus);
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
    filters.filter_status = '';
    filters.product_id = '';
    filters.product_item_id = '';
    filters.product_type_id = '';
    filters.supplier_id = '';
    filters.date_from = '';
    filters.date_to = '';
    filterCustomerId.value = '';
    customerSearch.value = '';
    fetchList();
};

const applyAdvancedFilters = () => {
    fetchList();
    closeFilterModal();
};

const clearCustomerSelection = () => {
    filterCustomerId.value = '';
    customerSearch.value = '';
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

// Watch for per_page changes and automatically refetch data
watch(
    () => filters.per_page,
    (newPerPage, oldPerPage) => {
        if (newPerPage !== oldPerPage && oldPerPage !== undefined) {
            fetchList();
        }
    },
);
</script>

<template>
    <Head :title="`Chi tiết đơn hàng - ${site.name}`" />

    <AppLayout
        :breadcrumbs="[
            { title: props.site.name, href: `/${props.site.slug}/dashboard` },
            {
                title: 'Chi tiết đơn hàng',
                href: `/${props.site.slug}/order-details`,
            },
        ]"
    >
        <div class="space-y-4 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <!-- Header with Title and Search -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">
                        Chi tiết đơn hàng
                    </h1>
                </div>
                <div class="flex w-full gap-3 sm:w-auto">
                    <div class="relative flex-1 sm:w-80">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                        />
                        <Input
                            v-model="filters.search"
                            placeholder="Mã đơn, khách hàng, SKU..."
                            class="h-11 pl-9 text-sm sm:h-10"
                            @keyup.enter="applyFilters"
                        />
                        <button
                            v-if="filters.search"
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            type="button"
                            @click="
                                filters.search = '';
                                applyFilters();
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
                        @click="openFilterModal"
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

            <!-- Flash Messages -->
            <TransitionGroup
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform -translate-y-2 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
            >
                <div
                    v-if="
                        page.props.flash?.success || page.props.flash?.message
                    "
                    key="success"
                    class="rounded-2xl border border-green-100 bg-green-50 p-4 shadow-sm shadow-green-100/50"
                >
                    <p
                        class="flex items-center gap-2 text-sm font-semibold text-green-800"
                    >
                        <span class="rounded-full bg-green-100 p-1"
                            ><Check class="h-3 w-3"
                        /></span>
                        {{
                            page.props.flash?.success ||
                            page.props.flash?.message
                        }}
                    </p>
                </div>
                <div
                    v-if="page.props.flash?.error"
                    key="error"
                    class="rounded-2xl border border-red-100 bg-red-50 p-4 shadow-sm shadow-red-100/50"
                >
                    <p
                        class="flex items-center gap-2 text-sm font-semibold text-red-800"
                    >
                        <span class="rounded-full bg-red-100 p-1"
                            ><X class="h-3 w-3"
                        /></span>
                        {{ page.props.flash.error }}
                    </p>
                </div>
            </TransitionGroup>

            <!-- Stats Bar -->
            <div
                class="flex flex-col gap-2 pt-2 sm:flex-row sm:items-center sm:justify-between"
            >
                <p
                    class="text-[11px] font-bold tracking-widest text-gray-500 uppercase"
                >
                    Tổng kết:
                    <span class="font-bold text-gray-900">{{
                        orderDetails.total
                    }}</span>
                    chi tiết đơn hàng
                </p>
                <div v-if="hasActiveFilters" class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 italic"
                        >Đang áp dụng bộ lọc</span
                    >
                    <button
                        @click="clearAllFilters"
                        class="text-xs font-medium text-indigo-600 underline hover:text-indigo-800"
                    >
                        Xóa tất cả bộ lọc
                    </button>
                </div>
            </div>

            <!-- Bulk Actions Bar -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform -translate-y-4 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
            >
                <div
                    v-if="
                        filters.filter_status &&
                        activeFilterStatus &&
                        filterStatusTransitions.length > 0 &&
                        orderDetails.total > 0
                    "
                    class="rounded-2xl border border-indigo-100 bg-white p-4 shadow-lg shadow-indigo-100/50 lg:rounded-3xl lg:p-5"
                >
                    <div
                        class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <span class="relative flex h-3 w-3">
                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-indigo-400 opacity-75"
                                ></span>
                                <span
                                    class="relative inline-flex h-3 w-3 rounded-full bg-indigo-500"
                                ></span>
                            </span>
                            <span class="text-sm font-bold text-gray-900">
                                Cập nhật hàng loạt ({{ selectedIds.length }} mục
                                đang chọn)
                            </span>
                        </div>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="toggleAllVisible"
                            class="h-8 rounded-2xl px-3 text-[10px] font-semibold tracking-wider text-indigo-600 uppercase hover:bg-indigo-50 sm:px-4 lg:rounded-3xl lg:text-[11px]"
                        >
                            {{
                                allVisibleSelected
                                    ? 'Bỏ chọn tất cả'
                                    : 'Chọn tất cả trang này'
                            }}
                        </Button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="t in filterStatusTransitions"
                            :key="t.value"
                            type="button"
                            :disabled="selectedIds.length === 0"
                            :class="[
                                'min-h-10 rounded-xl px-4 py-2 text-[9px] font-bold tracking-widest uppercase shadow transition-all focus:ring-2 focus:ring-offset-2 focus:outline-none active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 sm:min-h-11 sm:rounded-2xl sm:px-6 sm:text-[10px] lg:shadow-lg',
                                transitionButtonClass(t.value),
                            ]"
                            @click="openBulkConfirm(t.value)"
                        >
                            {{ t.label }}
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Desktop Table View -->
            <div
                class="hidden overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-lg shadow-gray-100/25 lg:block lg:rounded-4xl lg:shadow-2xl lg:shadow-gray-100/50"
            >
                <table
                    class="w-full min-w-[800px] border-separate border-spacing-0 divide-y divide-gray-50"
                >
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="w-10 px-4 py-4 lg:px-6 lg:py-5">
                                <input
                                    type="checkbox"
                                    :checked="allVisibleSelected"
                                    @change="toggleAllVisible"
                                    class="h-4 w-4 cursor-pointer rounded-lg border-gray-200 text-indigo-600 transition-all focus:ring-indigo-500"
                                />
                            </th>
                            <th
                                class="px-3 py-4 text-left text-[9px] font-bold tracking-wider text-gray-400 uppercase lg:px-4 lg:py-5 lg:text-[10px]"
                            >
                                Sản phẩm
                            </th>
                            <th
                                class="px-3 py-4 text-left text-[9px] font-bold tracking-wider text-gray-400 uppercase lg:px-4 lg:py-5 lg:text-[10px]"
                            >
                                Khách hàng
                            </th>
                            <th
                                class="px-3 py-4 text-right text-[9px] font-bold tracking-wider text-gray-400 uppercase lg:px-4 lg:py-5 lg:text-[10px]"
                            >
                                SL
                            </th>
                            <th
                                class="px-3 py-4 text-right text-[9px] font-bold tracking-wider text-gray-400 uppercase lg:px-4 lg:py-5 lg:text-[10px]"
                            >
                                Đơn giá
                            </th>
                            <th
                                class="px-3 py-4 text-right text-[9px] font-bold tracking-wider text-gray-400 uppercase lg:px-4 lg:py-5 lg:text-[10px]"
                            >
                                Thành tiền
                            </th>
                            <th
                                class="px-3 py-4 text-left text-[9px] font-bold tracking-wider text-gray-400 uppercase lg:px-4 lg:py-5 lg:text-[10px]"
                            >
                                Trạng thái
                            </th>
                            <th
                                class="px-4 py-4 text-center text-[9px] font-bold tracking-wider text-gray-400 uppercase lg:px-6 lg:py-5 lg:text-[10px]"
                            >
                                Thao tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50/50">
                        <tr v-if="!orderDetails.data?.length">
                            <td
                                colspan="8"
                                class="px-6 py-16 text-center lg:py-24"
                            >
                                <div
                                    class="flex flex-col items-center gap-3 lg:gap-4"
                                >
                                    <div
                                        class="rounded-full bg-gray-50 p-4 text-gray-300 lg:p-6"
                                    >
                                        <Search class="h-6 w-6 lg:h-8 lg:w-8" />
                                    </div>
                                    <p
                                        class="text-xs font-semibold tracking-wider text-gray-400 uppercase lg:text-sm"
                                    >
                                        Không có dữ liệu phù hợp
                                    </p>
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-for="detail in orderDetails.data"
                            :key="detail.id"
                            class="group transition-all duration-300 hover:bg-indigo-50/30"
                        >
                            <td class="px-4 py-4 lg:px-6 lg:py-5" @click.stop>
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(detail.id)"
                                    @change="toggleSelected(detail.id)"
                                    class="h-4 w-4 cursor-pointer rounded-lg border-gray-200 text-indigo-600 transition-all focus:ring-indigo-500"
                                />
                            </td>
                            <td
                                class="px-3 py-4 font-medium text-gray-900 lg:px-4 lg:py-5"
                            >
                                <div class="flex items-center gap-3 lg:gap-5">
                                    <ProductThumbnailPreview
                                        :src="detail.product_item.image"
                                        :alt="detail.product.name"
                                        size-class="h-12 w-12"
                                    />
                                    <div
                                        @click="viewDetail(detail.id)"
                                        class="min-w-0 flex-1 cursor-pointer"
                                    >
                                        <div
                                            class="truncate text-xs font-bold text-gray-900 transition-colors group-hover:text-indigo-600 lg:text-sm"
                                        >
                                            {{ detail.product.name }}
                                        </div>
                                        <div
                                            class="mt-1 text-[9px] font-bold tracking-widest text-gray-400 uppercase lg:text-[10px]"
                                        >
                                            SKU: {{ detail.product_item.sku }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td
                                class="px-3 py-4 text-xs font-medium text-gray-700 lg:px-4 lg:py-5 lg:text-sm"
                            >
                                {{ detail.customer.name }}
                            </td>
                            <td
                                class="px-3 py-4 text-right text-xs font-bold text-gray-900 lg:px-4 lg:py-5 lg:text-sm"
                            >
                                {{ detail.qty }}
                            </td>
                            <td
                                class="px-3 py-4 text-right text-xs font-medium text-gray-400 lg:px-4 lg:py-5 lg:text-sm"
                            >
                                {{ formatVnd(detail.price) }}
                            </td>
                            <td
                                class="px-3 py-4 text-right text-xs font-bold text-indigo-600 lg:px-4 lg:py-5 lg:text-sm"
                            >
                                {{ formatVnd(detail.total) }}
                            </td>
                            <td class="px-3 py-4 lg:px-4 lg:py-5">
                                <div
                                    class="flex flex-col items-start gap-1.5 lg:gap-2"
                                >
                                    <Badge
                                        :class="
                                            getBadgeClass(detail.status_color)
                                        "
                                        class="rounded px-2 py-0.5 text-[8px] font-bold tracking-wider uppercase lg:rounded-lg lg:px-3 lg:py-1 lg:text-[9px]"
                                        >{{ detail.status_label }}</Badge
                                    >
                                    <Badge
                                        :class="
                                            getBadgeClass(
                                                detail.payment_status_color,
                                            )
                                        "
                                        class="rounded px-2 py-0.5 text-[8px] font-bold tracking-wider uppercase lg:rounded-lg lg:px-3 lg:py-1 lg:text-[9px]"
                                        >{{
                                            detail.payment_status_label
                                        }}</Badge
                                    >
                                </div>
                            </td>
                            <td
                                class="px-4 py-4 text-center lg:px-6 lg:py-5"
                                @click.stop
                            >
                                <div
                                    class="flex justify-center gap-1 transition-all duration-300"
                                >
                                    <Button
                                        @click="viewDetail(detail.id)"
                                        variant="ghost"
                                        size="sm"
                                        class="p-1.5 lg:p-2"
                                        title="Xem chi tiết"
                                    >
                                        <Eye
                                            class="h-3.5 w-3.5 lg:h-4 lg:w-4"
                                        />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="space-y-4 lg:hidden">
                <div
                    v-if="!orderDetails.data?.length"
                    class="rounded-3xl border border-dashed border-gray-200 bg-white px-6 py-24 text-center shadow-inner"
                >
                    <div
                        class="mx-auto mb-4 w-fit rounded-full bg-gray-50 p-6 text-gray-200"
                    >
                        <Search class="h-10 w-10" />
                    </div>
                    <p
                        class="text-xs font-semibold tracking-wider text-gray-400 uppercase"
                    >
                        Không tìm thấy kết quả
                    </p>
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="detail in orderDetails.data"
                        :key="detail.id"
                        class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-all hover:shadow-md active:scale-[0.99]"
                        @click="viewDetail(detail.id)"
                    >
                        <!-- Mobile Card Header -->
                        <div class="mb-4 flex items-start justify-between">
                            <div class="flex flex-1 items-start gap-3">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(detail.id)"
                                    class="mt-1 h-4 w-4 rounded border-gray-200 text-indigo-600 transition-all focus:ring-indigo-500"
                                    @change.stop="toggleSelected(detail.id)"
                                    @click.stop
                                />
                                <div class="flex flex-wrap gap-1.5">
                                    <Badge
                                        :class="
                                            getBadgeClass(detail.status_color)
                                        "
                                        class="rounded px-2 py-1 text-[8px] font-bold tracking-wider uppercase"
                                        >{{ detail.status_label }}</Badge
                                    >
                                    <Badge
                                        :class="
                                            getBadgeClass(
                                                detail.payment_status_color,
                                            )
                                        "
                                        class="rounded px-2 py-1 text-[8px] font-bold tracking-wider uppercase"
                                        >{{
                                            detail.payment_status_label
                                        }}</Badge
                                    >
                                </div>
                            </div>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="h-8 w-8 rounded-full p-0 text-gray-400 hover:bg-gray-50 hover:text-indigo-600"
                                @click.stop="viewDetail(detail.id)"
                            >
                                <Eye class="h-4 w-4" />
                            </Button>
                        </div>

                        <!-- Mobile Card Content -->
                        <div class="flex gap-4">
                            <ProductThumbnailPreview
                                :src="detail.product_item.image"
                                :alt="detail.product.name"
                                size-class="h-16 w-16 rounded-xl shrink-0 object-cover ring-1 ring-gray-100 shadow-sm"
                            />
                            <div class="min-w-0 flex-1 space-y-2">
                                <h4
                                    class="line-clamp-2 text-sm leading-tight font-bold text-gray-900"
                                >
                                    {{ detail.product.name }}
                                </h4>
                                <p
                                    class="text-[9px] font-bold tracking-widest text-gray-400 uppercase"
                                >
                                    SKU: {{ detail.product_item.sku }}
                                </p>
                                <p class="text-xs font-medium text-indigo-600">
                                    {{ detail.customer.name }}
                                </p>
                            </div>
                        </div>

                        <!-- Mobile Card Stats -->
                        <div
                            class="mt-4 grid grid-cols-3 gap-2 border-t border-gray-50 pt-4"
                        >
                            <div class="text-center">
                                <div
                                    class="text-[9px] font-medium tracking-wide text-gray-400 uppercase"
                                >
                                    Số lượng
                                </div>
                                <div
                                    class="mt-1 text-sm font-bold text-gray-900"
                                >
                                    {{ detail.qty }}
                                </div>
                            </div>
                            <div class="text-center">
                                <div
                                    class="text-[9px] font-medium tracking-wide text-gray-400 uppercase"
                                >
                                    Đơn giá
                                </div>
                                <div
                                    class="mt-1 text-[10px] font-medium text-gray-500"
                                >
                                    {{ formatVnd(detail.price) }}
                                </div>
                            </div>
                            <div class="text-center">
                                <div
                                    class="text-[9px] font-medium tracking-wide text-indigo-400 uppercase"
                                >
                                    Thành tiền
                                </div>
                                <div
                                    class="mt-1 text-sm font-bold text-indigo-600"
                                >
                                    {{ formatVnd(detail.total) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <AppPagination
                :meta="orderDetails"
                label="chi tiết đơn hàng"
                :onNavigate="handlePagination"
            />
        </div>

        <!-- Filter Drawer Backdrop -->
        <Transition
            enter-active-class="transition opacity-0 duration-300"
            enter-to-class="opacity-100"
            leave-active-class="transition opacity-100 duration-300"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showFilterModal"
                class="fixed inset-0 z-50 overflow-hidden bg-black/40 backdrop-blur-sm"
                @click="closeFilterModal"
            ></div>
        </Transition>

        <!-- Filter Drawer Panel -->
        <Transition
            enter-active-class="transform transition ease-in-out duration-500"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transform transition ease-in-out duration-500"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div
                v-if="showFilterModal"
                class="pointer-events-auto fixed inset-y-0 right-0 z-60 flex w-full max-w-md flex-col bg-white shadow-2xl"
            >
                <div
                    class="flex items-center justify-between border-b bg-gray-50 px-6 py-4"
                >
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">
                            Bộ lọc nâng cao
                        </h2>
                        <p class="mt-0.5 text-xs text-gray-500">
                            Thiết lập tiêu chí tìm kiếm chi tiết
                        </p>
                    </div>
                    <Button
                        variant="ghost"
                        size="icon"
                        @click="closeFilterModal"
                        class="rounded-full hover:bg-gray-200"
                    >
                        <X class="h-6 w-6 text-gray-500" />
                    </Button>
                </div>
                <div class="flex-1 space-y-8 overflow-y-auto p-6">
                    <!-- Status Filter -->
                    <div class="space-y-3">
                        <label
                            class="flex items-center gap-2 text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                        >
                            Trạng thái chi tiết
                        </label>
                        <select
                            v-model="filters.filter_status"
                            class="h-12 w-full rounded-md border border-gray-200 bg-gray-50 px-3 text-sm transition-all outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Tất cả trạng thái</option>
                            <option
                                v-for="statusOption in statusOptions"
                                :key="statusOption.value"
                                :value="String(statusOption.value)"
                            >
                                {{ statusOption.label }}
                            </option>
                        </select>
                    </div>
                    <!-- Customer Filter -->
                    <div class="space-y-3">
                        <label
                            class="flex items-center gap-2 text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                        >
                            Khách hàng
                        </label>
                        <AppMultiselect
                            :model-value="
                                filterCustomerId
                                    ? customerOptions.find(
                                          (c) =>
                                              String(c.id) ===
                                              String(filterCustomerId),
                                      )
                                    : null
                            "
                            @update:model-value="
                                (c: any) =>
                                    c
                                        ? selectCustomer(c)
                                        : clearCustomerSelection()
                            "
                            :options="customerOptions"
                            placeholder="Tìm tên / SĐT / Email..."
                            label="name"
                            track-by="id"
                            :loading="isSearchingCustomers"
                            :internal-search="false"
                            @search-change="
                                (query: string) => (customerSearch = query)
                            "
                            deselect-label="Xóa"
                        >
                            <template #option="{ option }">
                                <div class="flex flex-col">
                                    <span class="font-bold text-gray-900">{{
                                        (option as any).name
                                    }}</span>
                                    <span class="text-xs text-gray-500">{{
                                        (option as any).phone ||
                                        (option as any).email ||
                                        'N/A'
                                    }}</span>
                                </div>
                            </template>
                        </AppMultiselect>
                    </div>
                    <!-- Date Filter -->
                    <div class="space-y-4">
                        <label
                            class="flex items-center gap-2 text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                        >
                            Khoảng thời gian
                        </label>
                        <div class="grid grid-cols-1 gap-4">
                            <div class="space-y-1.5">
                                <span class="ml-1 text-xs text-gray-500"
                                    >Từ ngày</span
                                >
                                <Input
                                    v-model="filters.date_from"
                                    type="date"
                                    class="h-12 bg-gray-50 shadow-none transition-all focus:bg-white"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <span class="ml-1 text-xs text-gray-500"
                                    >Đến ngày</span
                                >
                                <Input
                                    v-model="filters.date_to"
                                    type="date"
                                    class="h-12 bg-gray-50 shadow-none transition-all focus:bg-white"
                                />
                            </div>
                        </div>
                    </div>
                    <!-- Per Page Filter -->
                    <div class="space-y-3">
                        <label
                            class="flex items-center gap-2 text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                        >
                            Số bản ghi mỗi trang
                        </label>
                        <select
                            v-model.number="filters.per_page"
                            class="h-12 w-full rounded-md border border-gray-200 bg-gray-50 px-3 text-sm transition-all outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500"
                        >
                            <option
                                v-for="n in [50, 100, 200, 300, 400, 500]"
                                :key="n"
                                :value="n"
                            >
                                {{ n }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 border-t bg-white p-6">
                    <Button
                        variant="ghost"
                        class="h-12 text-base font-semibold text-gray-600 hover:bg-gray-100"
                        @click="clearAllFilters"
                        >Xóa trắng</Button
                    >
                    <Button
                        class="h-12 text-base font-bold shadow-lg shadow-indigo-200"
                        @click="applyAdvancedFilters"
                        >Áp dụng lọc</Button
                    >
                </div>
            </div>
        </Transition>

        <!-- Bulk Change status modal -->
        <Dialog
            :open="showBulkConfirmDialog"
            @update:open="(open: boolean) => !open && closeBulkConfirm()"
        >
            <DialogContent
                class="relative mx-4 overflow-hidden rounded-[2.5rem] border-none p-10 shadow-2xl sm:mx-0 sm:max-w-lg"
            >
                <div
                    class="absolute top-0 left-0 h-2 w-full bg-linear-to-r from-indigo-500 via-purple-500 to-pink-500"
                ></div>

                <DialogHeader class="mb-8">
                    <DialogTitle
                        class="text-2xl font-bold tracking-tight text-gray-900"
                        >Xác nhận chuyển trạng thái</DialogTitle
                    >
                    <DialogDescription
                        class="pt-4 text-base leading-relaxed font-medium text-gray-500 italic"
                    >
                        Hệ thống sẽ thực hiện chuyển
                        <span
                            class="rounded-lg px-1 font-bold text-indigo-600 underline decoration-2 underline-offset-4 ring-2 ring-indigo-50"
                            >{{ selectedIds.length }} mục</span
                        >
                        sang trạng thái mới:
                        <div class="mt-4 flex justify-center">
                            <span
                                class="inline-flex items-center rounded-2xl bg-indigo-50 px-6 py-3 text-xs font-bold tracking-wider text-indigo-700 uppercase shadow-inner ring-1 ring-indigo-100"
                            >
                                {{ pendingBulkTarget?.label }}
                            </span>
                        </div>
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter
                    class="mt-4 flex flex-col-reverse gap-4 sm:flex-row"
                >
                    <Button
                        variant="outline"
                        type="button"
                        :disabled="isBulkUpdating"
                        @click="closeBulkConfirm"
                        class="h-14 w-full rounded-2xl border-gray-100 bg-gray-50/50 text-[10px] font-bold tracking-wider uppercase transition-all hover:bg-white active:scale-95 sm:w-auto"
                    >
                        Không, quay lại
                    </Button>
                    <Button
                        type="button"
                        :disabled="isBulkUpdating"
                        @click="confirmBulkUpdate"
                        class="h-14 w-full flex-1 rounded-2xl bg-indigo-600 text-[10px] font-bold tracking-wider uppercase shadow-2xl shadow-indigo-200 transition-all hover:bg-indigo-700 active:scale-95 sm:w-auto"
                    >
                        <span v-if="isBulkUpdating">Đang xử lý...</span>
                        <span v-else>Đồng ý cập nhật</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
