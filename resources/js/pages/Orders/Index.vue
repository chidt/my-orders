<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useOrderCustomerSearchFilter, type OrderSearchCustomer } from '@/composables/useOrderCustomerSearchFilter';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatVnd } from '@/lib/utils';

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
    status: number;
    status_label: string;
    details_count: number;
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
    links: PaginationLink[];
}

const props = defineProps<{
    site: Site;
    filters: {
        status: string;
        customer_id: string;
        date_from: string;
        date_to: string;
    };
    filterCustomer: OrderSearchCustomer | null;
    orders: OrdersPagination;
    statusOptions: StatusOption[];
}>();

const page = usePage<{ flash?: { success?: string; error?: string; message?: string } }>();

const status = ref(props.filters.status || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const {
    customerId,
    customerSearch,
    customerOptions,
    isSearchingCustomers,
    isCustomerSuggestionsOpen,
    selectCustomer,
    clearCustomerSelection,
    orderSearchCustomerLabel,
    openSuggestions,
    closeSuggestionsBlur,
} = useOrderCustomerSearchFilter({
    siteSlug: () => props.site.slug,
    getCustomerId: () => props.filters.customer_id || '',
    getFilterCustomer: () => props.filterCustomer,
});

const breadcrumbs = computed(() => [
    { title: props.site.name, href: `/${props.site.slug}/dashboard`, current: false },
    { title: 'Quản lý đơn hàng', href: `/${props.site.slug}/orders`, current: true },
]);

const applyFilters = () => {
    router.get(
        `/${props.site.slug}/orders`,
        {
            status: status.value || undefined,
            customer_id: customerId.value.trim() || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const clearFilters = () => {
    status.value = '';
    clearCustomerSelection();
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

const deleteOrder = (orderId: number) => {
    if (!window.confirm('Bạn có chắc muốn xóa đơn hàng này?')) {
        return;
    }

    router.delete(`/${props.site.slug}/orders/${orderId}`);
};
</script>

<template>
    <Head :title="`Quản lý đơn hàng - ${site.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Quản lý đơn hàng</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Danh sách đơn hàng thuộc website {{ site.name }}
                    </p>
                </div>
                <Button :as="Link" :href="`/${site.slug}/orders/create`">
                    Tạo đơn hàng mới
                </Button>
            </div>

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

            <div class="grid grid-cols-1 gap-3 rounded-lg border bg-white p-4 md:grid-cols-5">
                <div>
                    <label class="mb-1 block text-xs text-gray-600" for="orders-index-filter-status">Trạng thái</label>
                    <select id="orders-index-filter-status" v-model="status" class="h-9 w-full rounded-md border bg-white px-3 text-sm">
                        <option value="">Tất cả</option>
                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs text-gray-600" for="orders-index-customer-search">Khách hàng</label>
                    <div class="relative">
                        <Input
                            id="orders-index-customer-search"
                            v-model="customerSearch"
                            type="text"
                            class="w-full text-sm md:text-sm"
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
                    <label class="mb-1 block text-xs text-gray-600" for="orders-index-filter-date-from">Từ ngày</label>
                    <Input id="orders-index-filter-date-from" v-model="dateFrom" type="date" class="w-full text-sm md:text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs text-gray-600" for="orders-index-filter-date-to">Đến ngày</label>
                    <Input id="orders-index-filter-date-to" v-model="dateTo" type="date" class="w-full text-sm md:text-sm" />
                </div>

                <div>
                    <p class="mb-1 block text-xs text-gray-600">Thao tác</p>
                    <div class="flex gap-2">
                        <Button class="flex-1" @click="applyFilters">Lọc</Button>
                        <Button class="flex-1" variant="outline" @click="clearFilters">Xóa lọc</Button>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border bg-white">
                <div v-if="orders.data.length === 0" class="py-12 text-center text-sm text-gray-500">
                    Chưa có đơn hàng nào
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[900px] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Số đơn</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Khách hàng</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Ngày tạo</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Trạng thái</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Số dòng</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Tổng tiền</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium">
                                    <Link :href="`/${site.slug}/orders/${order.id}`" class="text-blue-600 hover:underline">
                                        {{ order.order_number }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ order.customer?.name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm">{{ order.order_date }}</td>
                                <td class="px-4 py-3 text-sm">{{ order.status_label }}</td>
                                <td class="px-4 py-3 text-sm">{{ order.details_count }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    {{ formatVnd(order.total_amount) }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <Button size="sm" variant="outline" :as="Link" :href="`/${site.slug}/orders/${order.id}/edit`">
                                            Sửa
                                        </Button>
                                        <Button size="sm" variant="destructive" @click="deleteOrder(order.id)">
                                            Xóa
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="orders.last_page > 1" class="border-t px-4 py-3">
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="(link, index) in orders.links"
                            :key="index"
                            :as="link.url ? Link : 'button'"
                            :href="link.url || undefined"
                            :disabled="!link.url"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                        >
                            <span v-html="link.label" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
