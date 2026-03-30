<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { useOrderCustomerSearchFilter, type OrderSearchCustomer } from '@/composables/useOrderCustomerSearchFilter';
import { formatVnd } from '@/lib/utils';

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

const updateStatus = (detailId: number, status: string) => {
    router.patch(`/${props.site.slug}/order-details/${detailId}/status`, { status });
};

const updatePaymentStatus = (detailId: number, paymentStatus: string) => {
    router.patch(`/${props.site.slug}/order-details/${detailId}/payment-status`, { payment_status: paymentStatus });
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
        <div class="space-y-4 px-4 py-8 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold">Chi tiết đơn hàng</h1>

            <div v-if="page.props.flash?.success || page.props.flash?.message" class="rounded-md border border-green-200 bg-green-50 px-4 py-3">
                <p class="text-sm font-medium text-green-800">
                    {{ page.props.flash?.success || page.props.flash?.message }}
                </p>
            </div>
            <div v-if="page.props.flash?.error" class="rounded-md border border-red-200 bg-red-50 px-4 py-3">
                <p class="text-sm font-medium text-red-800">
                    {{ page.props.flash.error }}
                </p>
            </div>

            <div class="rounded-lg border bg-white p-4 space-y-3">
                <p class="text-sm font-medium text-gray-700">Lọc theo trạng thái chi tiết</p>
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[200px] flex-1">
                        <label class="mb-1 block text-xs text-gray-600">Trạng thái</label>
                        <select
                            v-model="filters.filter_status"
                            class="h-10 w-full rounded-md border px-3 text-sm"
                            @change="onFilterStatusChange"
                        >
                            <option value="">— Chọn trạng thái —</option>
                            <option v-for="statusOption in statusOptions" :key="statusOption.value" :value="String(statusOption.value)">
                                {{ statusOption.label }}
                            </option>
                        </select>
                    </div>
                    <Button v-if="hasStatusFilter" variant="outline" type="button" @click="clearStatusFilter">Bỏ lọc trạng thái</Button>
                </div>
            </div>

            <div
                v-if="hasStatusFilter && activeFilterStatus && filterStatusTransitions.length > 0 && orderDetails.total > 0"
                class="flex flex-wrap items-center gap-2"
            >
                <span class="text-sm text-gray-600">Đã chọn {{ selectedIds.length }} dòng.</span>
                <button
                    v-for="t in filterStatusTransitions"
                    :key="t.value"
                    type="button"
                    :disabled="selectedIds.length === 0"
                    :class="[
                        'rounded-lg px-3 py-2 text-sm font-medium shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40',
                        transitionButtonClass(t.value),
                    ]"
                    @click="openBulkConfirm(t.value)"
                >
                    {{ t.label }}
                </button>
            </div>

            <div class="grid grid-cols-1 gap-3 rounded-lg border bg-white p-4 md:grid-cols-4">
                <div>
                    <label class="mb-1 block text-xs text-gray-600" for="order-details-filter-search">Tìm kiếm</label>
                    <Input id="order-details-filter-search" v-model="filters.search" placeholder="Mã đơn, khách, SKU..." class="w-full" />
                </div>
                <div>
                    <label class="mb-1 block text-xs text-gray-600" for="order-details-filter-customer">Khách hàng</label>
                    <div class="relative">
                        <Input
                            id="order-details-filter-customer"
                            v-model="customerSearch"
                            type="text"
                            class="h-10 w-full"
                            placeholder="Tên / SĐT / email (ít nhất 2 ký tự)..."
                            @focus="openSuggestions"
                            @blur="closeSuggestionsBlur"
                        />
                        <div
                            v-if="isCustomerSuggestionsOpen && customerSearch.trim().length >= 2"
                            class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-white shadow"
                        >
                            <div v-if="isSearchingCustomers" class="px-3 py-2 text-sm text-gray-500">Đang tìm...</div>
                            <button
                                v-for="c in customerOptions"
                                :key="c.id"
                                type="button"
                                class="flex w-full items-center px-3 py-2 text-left text-sm hover:bg-gray-50"
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
                <div>
                    <label class="mb-1 block text-xs text-gray-600" for="order-details-filter-date-from">Từ ngày</label>
                    <Input id="order-details-filter-date-from" v-model="filters.date_from" type="date" class="w-full" />
                </div>
                <div>
                    <label class="mb-1 block text-xs text-gray-600" for="order-details-filter-date-to">Đến ngày</label>
                    <Input id="order-details-filter-date-to" v-model="filters.date_to" type="date" class="w-full" />
                </div>
                <Button class="md:col-span-4" :disabled="!hasStatusFilter" type="button" @click="applyFilters">
                    Áp dụng bộ lọc (tìm kiếm, khách, ngày)
                </Button>
                <p v-if="!hasStatusFilter" class="text-xs text-gray-500 md:col-span-4">Chọn trạng thái trước khi dùng bộ lọc phụ.</p>
            </div>

            <div v-if="!hasStatusFilter" class="rounded-lg border border-dashed bg-gray-50 px-4 py-8 text-center text-sm text-gray-600">
                Chọn trạng thái ở trên để xem danh sách.
            </div>

            <div v-else class="overflow-x-auto rounded-lg border bg-white">
                <p v-if="activeFilterStatus" class="border-b bg-gray-50 px-4 py-2 text-sm text-gray-700">
                    Đang lọc theo trạng thái: <span class="font-semibold">{{ activeFilterStatus.label }}</span>
                    <span class="text-gray-500">({{ orderDetails.total }} bản ghi)</span>
                </p>
                <p v-else class="border-b bg-amber-50 px-4 py-2 text-sm text-amber-900">Giá trị lọc trạng thái không hợp lệ. Chọn lại trạng thái khác.</p>
                <table class="w-full min-w-[1200px] divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-10 px-4 py-2 text-left text-xs uppercase">
                                <input type="checkbox" :checked="allVisibleSelected" @change="toggleAllVisible" />
                            </th>
                            <th class="px-4 py-2 text-left text-xs uppercase">Order</th>
                            <th class="px-4 py-2 text-left text-xs uppercase">Chi tiết</th>
                            <th class="px-4 py-2 text-left text-xs uppercase">Khách hàng</th>
                            <th class="px-4 py-2 text-left text-xs uppercase">Sản phẩm</th>
                            <th class="px-4 py-2 text-right text-xs uppercase">SL</th>
                            <th class="px-4 py-2 text-right text-xs uppercase">Giá</th>
                            <th class="px-4 py-2 text-right text-xs uppercase">Tổng</th>
                            <th class="px-4 py-2 text-left text-xs uppercase">Status</th>
                            <th class="px-4 py-2 text-left text-xs uppercase">Payment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="!orderDetails.data?.length">
                            <td colspan="10" class="px-4 py-8 text-center text-sm text-gray-500">Không có chi tiết đơn hàng nào với bộ lọc hiện tại.</td>
                        </tr>
                        <tr v-for="detail in orderDetails.data" :key="detail.id">
                            <td class="px-4 py-2 text-sm">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(detail.id)"
                                    @change="toggleSelected(detail.id)"
                                />
                            </td>
                            <td class="px-4 py-2 text-sm">{{ detail.order.order_number }}</td>
                            <td class="px-4 py-2 text-sm">
                                <Link :href="`/${site.slug}/order-details/${detail.id}`" class="text-blue-600 hover:underline">
                                    Xem
                                </Link>
                            </td>
                            <td class="px-4 py-2 text-sm">{{ detail.customer.name }}</td>
                            <td class="px-4 py-2 text-sm">{{ detail.product.name }} - {{ detail.product_item.sku }}</td>
                            <td class="px-4 py-2 text-right text-sm">{{ detail.qty }}</td>
                            <td class="px-4 py-2 text-right text-sm">{{ formatVnd(detail.price) }}</td>
                            <td class="px-4 py-2 text-right text-sm">{{ formatVnd(detail.total) }}</td>
                            <td class="px-4 py-2 text-sm">
                                <select
                                    :value="String(detail.status)"
                                    class="h-9 rounded-md border px-2 text-sm"
                                    :disabled="!detail.can_update_status"
                                    @change="updateStatus(detail.id, ($event.target as HTMLSelectElement).value)"
                                >
                                    <option
                                        v-for="statusOption in statusOptions.filter((status) => detail.allowed_status_values.includes(Number(status.value)))"
                                        :key="statusOption.value"
                                        :value="statusOption.value"
                                    >
                                        {{ statusOption.label }}
                                    </option>
                                </select>
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <select
                                    :value="String(detail.payment_status)"
                                    class="h-9 rounded-md border px-2 text-sm"
                                    @change="updatePaymentStatus(detail.id, ($event.target as HTMLSelectElement).value)"
                                >
                                    <option v-for="paymentOption in paymentStatusOptions" :key="paymentOption.value" :value="paymentOption.value">
                                        {{ paymentOption.label }}
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Dialog :open="showBulkConfirmDialog" @update:open="(open: boolean) => !open && closeBulkConfirm()">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Xác nhận cập nhật trạng thái</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc muốn cập nhật
                        <span class="font-semibold">{{ selectedIds.length }}</span>
                        chi tiết đã chọn sang trạng thái
                        <span class="font-semibold">«{{ pendingBulkTarget?.label }}»</span>?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" type="button" :disabled="isBulkUpdating" @click="closeBulkConfirm">Hủy</Button>
                    <Button type="button" :disabled="isBulkUpdating" @click="confirmBulkUpdate">
                        <span v-if="isBulkUpdating">Đang cập nhật...</span>
                        <span v-else>Cập nhật</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
