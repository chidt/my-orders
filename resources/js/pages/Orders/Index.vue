<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Filter, Pencil, Plus, Search, Trash2, X } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
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

interface StatusOption {
    value: string;
    label: string;
}

interface OrderRow {
    id: number;
    order_number: string;
    order_date: string;
    status_label: string;
    status_color: string;
    payment_status: number;
    payment_status_label: string;
    payment_status_color: string;
    total_qty: number;
    total_amount: number;
    customer: { id: number; name: string } | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface OrdersPagination {
    data: OrderRow[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    links: PaginationLink[];
    from: number;
    to: number;
}

const props = defineProps<{
    site: Site;
    filters: {
        status: string;
        customer_id: string;
        date_from: string;
        date_to: string;
        search: string;
    };
    filterCustomer: OrderSearchCustomer | null;
    orders: OrdersPagination;
    statusOptions: StatusOption[];
    paymentStatusOptions: StatusOption[];
}>();

const page = usePage<
    AppPageProps<{
        flash?: { success?: string; error?: string; message?: string };
    }>
>();

// Selection logic
const selectedIds = ref<number[]>([]);
const allVisibleSelected = computed(() => {
    return (
        props.orders.data.length > 0 &&
        props.orders.data.every((o) => selectedIds.value.includes(o.id))
    );
});

const toggleSelected = (id: number) => {
    const index = selectedIds.value.indexOf(id);
    if (index === -1) {
        selectedIds.value.push(id);
    } else {
        selectedIds.value.splice(index, 1);
    }
};

const toggleAllVisible = () => {
    if (allVisibleSelected.value) {
        const visibleIds = props.orders.data.map((o) => o.id);
        selectedIds.value = selectedIds.value.filter(
            (id) => !visibleIds.includes(id),
        );
    } else {
        const newIds = props.orders.data
            .map((o) => o.id)
            .filter((id) => !selectedIds.value.includes(id));
        selectedIds.value.push(...newIds);
    }
};

// Filter logic
const filters = reactive({
    status: props.filters.status || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    search: props.filters.search || '',
});

const isFilterModalOpen = ref(false);

const {
    customerId,
    customerSearch,
    customerOptions,
    isSearchingCustomers,
    selectCustomer,
    clearCustomerSelection,
} = useOrderCustomerSearchFilter({
    siteSlug: () => props.site.slug,
    getCustomerId: () => props.filters.customer_id || '',
    getFilterCustomer: () => props.filterCustomer,
});

const breadcrumbs = computed(() => [
    {
        title: props.site.name,
        href: `/${props.site.slug}/dashboard`,
        current: false,
    },
    {
        title: 'Quản lý đơn hàng',
        href: `/${props.site.slug}/orders`,
        current: true,
    },
]);

const applyFilters = () => {
    isFilterModalOpen.value = false;
    router.get(
        `/${props.site.slug}/orders`,
        {
            status: filters.status || undefined,
            customer_id: customerId.value.trim() || undefined,
            date_from: filters.date_from || undefined,
            date_to: filters.date_to || undefined,
            search: filters.search || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const clearFilters = () => {
    filters.status = '';
    filters.date_from = '';
    filters.date_to = '';
    filters.search = '';
    clearCustomerSelection();
    applyFilters();
};

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filters.status) count++;
    if (customerId.value) count++;
    if (filters.date_from) count++;
    if (filters.date_to) count++;
    return count;
});

const hasActiveFilters = computed(() => activeFiltersCount.value > 0);

const getBadgeClass = (color: string) => {
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

const viewOrder = (orderId: number) => {
    router.get(`/${props.site.slug}/orders/${orderId}`);
};

const editOrder = (orderId: number) => {
    router.get(`/${props.site.slug}/orders/${orderId}/edit`);
};

const showDeleteDialog = ref(false);
const showBulkDeleteDialog = ref(false);
const orderToDelete = ref<OrderRow | null>(null);
const isDeleting = ref(false);

const openDeleteDialog = (order: OrderRow) => {
    orderToDelete.value = order;
    showDeleteDialog.value = true;
};

const confirmDeleteOrder = () => {
    if (!orderToDelete.value) return;

    isDeleting.value = true;
    router.delete(`/${props.site.slug}/orders/${orderToDelete.value.id}`, {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteDialog.value = false;
            orderToDelete.value = null;
        },
    });
};

const confirmBulkDelete = () => {
    if (selectedIds.value.length === 0) return;

    isDeleting.value = true;
    router.post(
        `/${props.site.slug}/orders/bulk-delete`,
        { ids: selectedIds.value },
        {
            onFinish: () => {
                isDeleting.value = false;
                showBulkDeleteDialog.value = false;
                selectedIds.value = [];
            },
        },
    );
};
</script>

<template>
    <Head :title="`Quản lý đơn hàng - ${site.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header with Title and Search -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">
                        Quản lý đơn hàng
                    </h1>
                </div>
                <div class="flex w-full gap-2 sm:w-auto">
                    <div class="relative flex-1 sm:w-80">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                        />
                        <Input
                            v-model="filters.search"
                            placeholder="Tìm kiếm mã đơn, khách hàng..."
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
                        @click="isFilterModalOpen = true"
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
                    :as="Link"
                    :href="`/${site.slug}/orders/create`"
                    class="h-11 w-full sm:h-10 sm:w-auto"
                >
                    <Plus class="mr-2 h-4 w-4" />
                    Thêm mới
                </Button>
            </div>

            <div
                v-if="page.props.flash?.success || page.props.flash?.message"
                class="rounded-md border border-green-200 bg-green-50 px-4 py-3"
            >
                <p class="text-sm font-medium text-green-800">
                    {{ page.props.flash?.success || page.props.flash?.message }}
                </p>
            </div>
            <div
                v-if="page.props.flash?.error"
                class="rounded-md border border-red-200 bg-red-50 px-4 py-3"
            >
                <p class="text-sm font-medium text-red-800">
                    {{ page.props.flash.error }}
                </p>
            </div>

            <div class="mb-6 flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Tổng cộng
                    <span class="font-medium text-gray-900">{{
                        orders.total
                    }}</span>
                    đơn hàng
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

            <!-- Selection Bar -->
            <div
                v-if="selectedIds.length > 0"
                class="flex animate-in items-center justify-between gap-3 rounded-lg border border-indigo-100 bg-indigo-50 p-3 shadow-sm fade-in slide-in-from-top-1"
            >
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-indigo-700"
                        >Đã chọn {{ selectedIds.length }} đơn hàng</span
                    >
                    <Button
                        variant="outline"
                        size="sm"
                        @click="selectedIds = []"
                        class="h-8 bg-white text-xs"
                        >Bỏ chọn</Button
                    >
                </div>
                <Button
                    variant="destructive"
                    size="sm"
                    @click="showBulkDeleteDialog = true"
                    class="h-8 gap-2 text-xs"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                    Xóa hàng loạt
                </Button>
            </div>

            <div class="overflow-hidden rounded-lg border bg-white shadow-sm">
                <!-- Desktop Table -->
                <div class="hidden overflow-x-auto md:block">
                    <table
                        class="w-full min-w-[1000px] divide-y divide-gray-200"
                    >
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="w-10 px-4 py-3 text-left">
                                    <input
                                        type="checkbox"
                                        :checked="allVisibleSelected"
                                        @change="toggleAllVisible"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                >
                                    Số đơn
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                >
                                    Khách hàng
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                >
                                    Ngày tạo
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                >
                                    Trạng thái
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                >
                                    Tổng SP
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-bold font-semibold tracking-wider text-gray-500 uppercase"
                                >
                                    Tổng tiền
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                >
                                    Thao tác
                                </th>
                            </tr>
                        </thead>
                        <tbody class="italic-none divide-y divide-gray-100">
                            <tr v-if="orders.data.length === 0">
                                <td
                                    colspan="8"
                                    class="py-12 text-center text-sm text-gray-500"
                                >
                                    Chưa có đơn hàng nào khớp với bộ lọc
                                </td>
                            </tr>
                            <tr
                                v-for="order in orders.data"
                                :key="order.id"
                                class="group cursor-pointer transition-colors hover:bg-gray-50"
                                @click="viewOrder(order.id)"
                            >
                                <td class="px-4 py-3" @click.stop>
                                    <input
                                        type="checkbox"
                                        :checked="
                                            selectedIds.includes(order.id)
                                        "
                                        @change="toggleSelected(order.id)"
                                        class="h-4 w-4 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                </td>
                                <td
                                    class="px-4 py-3 text-sm font-bold text-gray-900"
                                >
                                    <span
                                        class="text-blue-600 uppercase transition-colors hover:text-blue-800"
                                    >
                                        {{ order.order_number }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ order.customer?.name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    {{ order.order_date }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex flex-col gap-1">
                                        <Badge
                                            :class="
                                                getBadgeClass(
                                                    order.status_color,
                                                )
                                            "
                                            >{{ order.status_label }}</Badge
                                        >
                                        <Badge
                                            :class="
                                                getBadgeClass(
                                                    order.payment_status_color,
                                                )
                                            "
                                            >{{
                                                order.payment_status_label
                                            }}</Badge
                                        >
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-3 text-center text-sm text-gray-600"
                                >
                                    {{ order.total_qty }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right text-sm font-bold text-gray-900"
                                >
                                    {{ formatVnd(order.total_amount) }}
                                </td>
                                <td class="px-4 py-3 text-right" @click.stop>
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            @click="viewOrder(order.id)"
                                            variant="ghost"
                                            size="sm"
                                            class="p-2"
                                            title="Xem chi tiết"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            @click="editOrder(order.id)"
                                            variant="ghost"
                                            size="sm"
                                            class="p-2"
                                            title="Sửa"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            @click="openDeleteDialog(order)"
                                            variant="ghost"
                                            size="sm"
                                            class="p-2 text-red-600 hover:text-red-700"
                                            title="Xóa"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden">
                    <div
                        v-if="orders.data.length === 0"
                        class="py-12 text-center text-sm text-gray-500"
                    >
                        Chưa có đơn hàng nào
                    </div>
                    <template v-else>
                        <!-- Mobile Select All Header -->
                        <div class="border-b border-gray-100 bg-gray-50/50 p-4">
                            <label class="flex items-center gap-3 text-sm">
                                <input
                                    type="checkbox"
                                    :checked="allVisibleSelected"
                                    @change="toggleAllVisible"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="font-bold text-gray-700"
                                    >Chọn tất cả ({{
                                        orders.data.length
                                    }})</span
                                >
                            </label>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div
                                v-for="order in orders.data"
                                :key="order.id"
                                class="cursor-pointer space-y-3 p-4 transition-colors hover:bg-gray-50 active:bg-gray-100"
                                @click="viewOrder(order.id)"
                            >
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            :checked="
                                                selectedIds.includes(order.id)
                                            "
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            @change="toggleSelected(order.id)"
                                            @click.stop
                                        />
                                        <div>
                                            <Link
                                                :href="`/${site.slug}/orders/${order.id}`"
                                                class="text-base font-bold text-blue-600 uppercase"
                                                @click.stop
                                            >
                                                {{ order.order_number }}
                                            </Link>
                                            <p
                                                class="text-[10px] tracking-tight text-gray-500 uppercase"
                                            >
                                                {{ order.order_date }}
                                            </p>
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <Badge
                                                :class="[
                                                    getBadgeClass(
                                                        order.status_color,
                                                    ),
                                                    'h-5 px-2 py-0 text-[10px] font-normal',
                                                ]"
                                                >{{ order.status_label }}</Badge
                                            >
                                            <Badge
                                                :class="[
                                                    getBadgeClass(
                                                        order.payment_status_color,
                                                    ),
                                                    'h-5 px-2 py-0 text-[10px] font-normal',
                                                ]"
                                                >{{
                                                    order.payment_status_label
                                                }}</Badge
                                            >
                                        </div>
                                    </div>
                                    <div
                                        class="flex shrink-0 gap-1"
                                        @click.stop
                                    >
                                        <Button
                                            @click="viewOrder(order.id)"
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-2"
                                            title="Xem chi tiết"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            @click="editOrder(order.id)"
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-2"
                                            title="Sửa"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            @click="openDeleteDialog(order)"
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-2 text-red-600 hover:text-red-700"
                                            title="Xóa"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                                <div
                                    class="grid grid-cols-2 gap-4 rounded-lg border border-gray-100 bg-gray-50 p-3 text-sm"
                                >
                                    <div>
                                        <p
                                            class="mb-0.5 text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                        >
                                            Khách hàng
                                        </p>
                                        <p
                                            class="truncate font-medium text-gray-900"
                                        >
                                            {{ order.customer?.name ?? '-' }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p
                                            class="mb-0.5 text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                        >
                                            Tổng SP
                                        </p>
                                        <p class="font-medium text-gray-900">
                                            {{ order.total_qty }}
                                        </p>
                                    </div>
                                    <div class="col-span-2">
                                        <p
                                            class="mb-0.5 text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                        >
                                            Tổng thanh toán
                                        </p>
                                        <p
                                            class="text-base font-bold text-indigo-600"
                                        >
                                            {{ formatVnd(order.total_amount) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Shared Enhanced Pagination -->
                <AppPagination :meta="orders" label="đơn hàng" />
            </div>
        </div>

        <!-- Filter Drawer Sidebar -->
        <Transition
            enter-active-class="transition opacity-0 duration-300"
            enter-to-class="opacity-100"
            leave-active-class="transition opacity-100 duration-300"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isFilterModalOpen"
                class="fixed inset-0 z-50 overflow-hidden bg-black/40 backdrop-blur-sm"
                @click="isFilterModalOpen = false"
            ></div>
        </Transition>

        <Transition
            enter-active-class="transform transition ease-in-out duration-500"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transform transition ease-in-out duration-500"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div
                v-if="isFilterModalOpen"
                class="pointer-events-auto fixed inset-y-0 right-0 z-[60] flex w-full max-w-md flex-col bg-white shadow-2xl"
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
                        @click="isFilterModalOpen = false"
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
                            Trạng thái đơn hàng
                        </label>
                        <select
                            v-model="filters.status"
                            class="h-12 w-full rounded-md border border-gray-200 bg-gray-50 px-3 text-sm transition-all outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Tất cả trạng thái</option>
                            <option
                                v-for="option in statusOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Customer Select -->
                    <div class="space-y-3">
                        <label
                            class="flex items-center gap-2 text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                        >
                            Khách hàng
                        </label>
                        <AppMultiselect
                            :model-value="
                                customerId
                                    ? customerOptions.find(
                                          (c) =>
                                              String(c.id) ===
                                              String(customerId),
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

                    <!-- Date Selection -->
                    <div class="space-y-4">
                        <label
                            class="flex items-center gap-2 text-[10px] font-black font-bold tracking-wider text-gray-400 uppercase"
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
                </div>

                <div class="grid grid-cols-2 gap-4 border-t bg-white p-6">
                    <Button
                        variant="ghost"
                        class="h-12 text-base font-semibold text-gray-600 hover:bg-gray-100"
                        @click="clearFilters"
                        >Xóa trắng</Button
                    >
                    <Button
                        class="h-12 text-base font-bold shadow-lg shadow-indigo-200"
                        @click="applyFilters"
                        >Áp dụng lọc</Button
                    >
                </div>
            </div>
        </Transition>
        <!-- Single Delete Dialog -->
        <Dialog
            :open="showDeleteDialog"
            @update:open="showDeleteDialog = $event"
        >
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Xác nhận xóa đơn hàng</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc chắn muốn xóa đơn hàng
                        <span class="font-bold text-gray-900">{{
                            orderToDelete?.order_number
                        }}</span
                        >?
                        <br />
                        Hành động này không thể hoàn tác. Các tồn kho liên quan
                        sẽ được tự động hoàn lại.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="showDeleteDialog = false"
                        :disabled="isDeleting"
                    >
                        Hủy
                    </Button>
                    <Button
                        variant="destructive"
                        @click="confirmDeleteOrder"
                        :disabled="isDeleting"
                    >
                        <span v-if="isDeleting">Đang xóa...</span>
                        <span v-else>Xóa đơn hàng</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Bulk Delete Dialog -->
        <Dialog
            :open="showBulkDeleteDialog"
            @update:open="showBulkDeleteDialog = $event"
        >
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Xác nhận xóa hàng loạt</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc chắn muốn xóa
                        <span class="font-bold text-gray-900">{{
                            selectedIds.length
                        }}</span>
                        đơn hàng đã chọn?
                        <br />
                        Hành động này không thể hoàn tác. Tồn kho của các đơn
                        hàng hợp lệ sẽ được hoàn lại.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="showBulkDeleteDialog = false"
                        :disabled="isDeleting"
                    >
                        Hủy
                    </Button>
                    <Button
                        variant="destructive"
                        @click="confirmBulkDelete"
                        :disabled="isDeleting"
                    >
                        <span v-if="isDeleting">Đang xử lý...</span>
                        <span v-else
                            >Xóa {{ selectedIds.length }} đơn hàng</span
                        >
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
