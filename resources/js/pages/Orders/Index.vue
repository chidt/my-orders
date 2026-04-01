<script setup lang="ts">
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
import {
    useOrderCustomerSearchFilter,
    type OrderSearchCustomer,
} from '@/composables/useOrderCustomerSearchFilter';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatVnd } from '@/lib/utils';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Eye,
    Pencil,
    Search,
    TableOfContents,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

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

const page = usePage<{
    flash?: { success?: string; error?: string; message?: string };
}>();

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

const hasAdvancedFilters = computed(() => {
    return (
        !!filters.date_from ||
        !!filters.date_to ||
        !!customerId.value
    );
});

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

const deleteOrder = (orderId: number) => {
    if (!window.confirm('Bạn có chắc muốn xóa đơn hàng này?')) {
        return;
    }

    router.delete(`/${props.site.slug}/orders/${orderId}`);
};

const onStatusChange = () => {
    applyFilters();
};
</script>

<template>
    <Head :title="`Quản lý đơn hàng - ${site.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header with Title and Search -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Quản lý đơn hàng</h1>
                </div>
                <div class="w-full sm:w-80">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="filters.search"
                            placeholder="Tìm kiếm mã đơn, khách hàng..."
                            class="pl-9 h-11 sm:h-10 text-sm"
                            @keyup.enter="applyFilters"
                        />
                        <button
                            v-if="filters.search"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            type="button"
                            @click="filters.search = ''; applyFilters()"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>
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

            <!-- Modern Filter Section -->
            <div class="rounded-lg border bg-white p-3 sm:p-4 space-y-3">
                <p class="text-sm font-medium text-gray-700">Lọc theo trạng thái đơn hàng</p>
                <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                    <div class="w-full sm:min-w-[200px] sm:flex-1">
                        <select
                            v-model="filters.status"
                            class="h-11 sm:h-10 w-full rounded-md border bg-white px-3 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                            @change="onStatusChange"
                        >
                            <option value="">— Tất cả trạng thái —</option>
                            <option
                                v-for="option in statusOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            type="button"
                            class="h-11 sm:h-10 gap-2 flex-1 sm:flex-none"
                            @click="isFilterModalOpen = true"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707v4.586a1 1 0 01-.293.707L9 19.414A1 1 0 018 18.707V14.121a1 1 0 00-.293-.707L1.293 6.707A1 1 0 011 6V4z"/>
                            </svg>
                            <span class="relative">
                                Bộ lọc
                                <span v-if="hasAdvancedFilters" class="absolute -top-1 -right-2 flex h-2 w-2 rounded-full bg-indigo-500"></span>
                            </span>
                        </Button>
                        <Button :as="Link" :href="`/${site.slug}/orders/create`" class="h-11 sm:h-10 flex-1 sm:flex-none">
                            Tạo đơn hàng mới
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Selection Bar -->
            <div v-if="selectedIds.length > 0" class="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg border border-indigo-100 shadow-sm animate-in fade-in slide-in-from-top-1">
                <span class="text-sm font-medium text-indigo-700">Đã chọn {{ selectedIds.length }} đơn hàng</span>
                <Button variant="outline" size="sm" @click="selectedIds = []" class="h-8 text-xs">Bỏ chọn</Button>
            </div>

            <div class="rounded-lg border bg-white shadow-sm overflow-hidden">
                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full min-w-[1000px] divide-y divide-gray-200">
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
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Số đơn</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Khách hàng</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Ngày tạo</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">Trạng thái</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider text-center">Tổng SP</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500 tracking-wider font-bold">Tổng tiền</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500 tracking-wider">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 italic-none">
                            <tr v-if="orders.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-sm text-gray-500">Chưa có đơn hàng nào khớp với bộ lọc</td>
                            </tr>
                            <tr
                                v-for="order in orders.data"
                                :key="order.id"
                                class="cursor-pointer hover:bg-gray-50 group transition-colors"
                                @click="viewOrder(order.id)"
                            >
                                <td class="px-4 py-3" @click.stop>
                                    <input
                                        type="checkbox"
                                        :checked="selectedIds.includes(order.id)"
                                        @change="toggleSelected(order.id)"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                    />
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900">
                                    <span class="text-blue-600 hover:text-blue-800 transition-colors uppercase">
                                        {{ order.order_number }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ order.customer?.name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ order.order_date }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex flex-col gap-1">
                                        <Badge :class="getBadgeClass(order.status_color)">{{ order.status_label }}</Badge>
                                        <Badge :class="getBadgeClass(order.payment_status_color)">{{ order.payment_status_label }}</Badge>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-center text-gray-600">{{ order.total_qty }}</td>
                                <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">{{ formatVnd(order.total_amount) }}</td>
                                <td class="px-4 py-3 text-right" @click.stop>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <TableOfContents class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="viewOrder(order.id)">
                                                <Eye class="mr-2 h-4 w-4" />
                                                <span>Xem chi tiết</span>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="editOrder(order.id)">
                                                <Pencil class="mr-2 h-4 w-4" />
                                                <span>Sửa</span>
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem class="text-red-600 focus:text-red-600" @click="deleteOrder(order.id)">
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                <span>Xóa</span>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden">
                    <div v-if="orders.data.length === 0" class="py-12 text-center text-sm text-gray-500">Chưa có đơn hàng nào</div>
                    <template v-else>
                        <!-- Mobile Select All Header -->
                        <div class="border-b border-gray-100 p-4 bg-gray-50/50">
                            <label class="flex items-center gap-3 text-sm">
                                <input
                                    type="checkbox"
                                    :checked="allVisibleSelected"
                                    @change="toggleAllVisible"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="font-bold text-gray-700">Chọn tất cả ({{ orders.data.length }})</span>
                            </label>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div
                                v-for="order in orders.data"
                                :key="order.id"
                                class="cursor-pointer space-y-3 p-4 hover:bg-gray-50 active:bg-gray-100 transition-colors"
                                @click="viewOrder(order.id)"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            :checked="selectedIds.includes(order.id)"
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
                                            <p class="text-[10px] text-gray-500 uppercase tracking-tight">{{ order.order_date }}</p>
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <Badge :class="[getBadgeClass(order.status_color), 'text-[10px] font-normal px-2 py-0 h-5']">{{ order.status_label }}</Badge>
                                            <Badge :class="[getBadgeClass(order.payment_status_color), 'text-[10px] font-normal px-2 py-0 h-5']">{{ order.payment_status_label }}</Badge>
                                        </div>
                                    </div>
                                    <div @click.stop>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="sm" class="h-9 w-9 p-0">
                                                    <TableOfContents class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem @click="viewOrder(order.id)">
                                                    <Eye class="mr-2 h-4 w-4" />
                                                    <span>Xem chi tiết</span>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="editOrder(order.id)">
                                                    <Pencil class="mr-2 h-4 w-4" />
                                                    <span>Sửa</span>
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem class="text-red-600 focus:text-red-600" @click="deleteOrder(order.id)">
                                                    <Trash2 class="mr-2 h-4 w-4" />
                                                    <span>Xóa</span>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4 text-sm bg-gray-50 p-3 rounded-lg border border-gray-100">
                                    <div>
                                        <p class="text-[10px] uppercase text-gray-400 font-bold mb-0.5 tracking-wider">Khách hàng</p>
                                        <p class="font-medium text-gray-900 truncate">{{ order.customer?.name ?? '-' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[10px] uppercase text-gray-400 font-bold mb-0.5 tracking-wider">Tổng SP</p>
                                        <p class="font-medium text-gray-900">{{ order.total_qty }}</p>
                                    </div>
                                    <div class="col-span-2">
                                        <p class="text-[10px] uppercase text-gray-400 font-bold mb-0.5 tracking-wider">Tổng thanh toán</p>
                                        <p class="text-base font-bold text-indigo-600">{{ formatVnd(order.total_amount) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Shared Enhanced Pagination -->
                <div v-if="orders.total > 0" class="border-t px-4 py-4 sm:px-6 bg-gray-50/30">
                    <div class="flex items-center justify-between gap-4 flex-col sm:flex-row">
                        <div class="text-xs sm:text-sm text-gray-600 order-2 sm:order-1">
                            Hiển thị từ <span class="font-bold text-gray-900">{{ orders.from }}</span>
                            đến <span class="font-bold text-gray-900">{{ orders.to }}</span>
                            trong tổng số <span class="font-bold text-gray-900">{{ orders.total }}</span> đơn hàng
                        </div>
                        <nav class="inline-flex -space-x-px rounded-lg shadow-sm bg-white overflow-hidden border border-gray-200 order-1 sm:order-2">
                            <template v-for="(link, index) in orders.links" :key="index">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'relative inline-flex items-center px-4 py-2 text-sm font-medium transition-all duration-200',
                                        link.active
                                            ? 'z-10 bg-indigo-600 text-white border-indigo-600'
                                            : 'text-gray-600 hover:bg-gray-50 border-gray-200 hover:text-indigo-600',
                                        index !== 0 ? 'border-l' : ''
                                    ]"
                                    v-html="link.label.includes('Previous') ? '‹' : (link.label.includes('Next') ? '›' : link.label)"
                                />
                                <span
                                    v-else
                                    :class="[
                                        'relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-300 bg-gray-50 cursor-not-allowed',
                                        index !== 0 ? 'border-l' : ''
                                    ]"
                                    v-html="link.label.includes('Previous') ? '‹' : (link.label.includes('Next') ? '›' : link.label)"
                                />
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Drawer Sidebar -->
        <Transition
            enter-active-class="transition opacity-0 duration-300"
            enter-to-class="opacity-100"
            leave-active-class="transition opacity-100 duration-300"
            leave-to-class="opacity-0"
        >
            <div v-if="isFilterModalOpen" class="fixed inset-0 z-50 overflow-hidden bg-black/40 backdrop-blur-sm" @click="isFilterModalOpen = false"></div>
        </Transition>

        <Transition
            enter-active-class="transform transition ease-in-out duration-500"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transform transition ease-in-out duration-500"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div v-if="isFilterModalOpen" class="fixed inset-y-0 right-0 z-[60] w-full max-w-md bg-white shadow-2xl flex flex-col pointer-events-auto">
                <div class="px-6 py-4 border-b flex items-center justify-between bg-gray-50">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Bộ lọc nâng cao</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Thiết lập tiêu chí tìm kiếm chi tiết</p>
                    </div>
                    <Button variant="ghost" size="icon" @click="isFilterModalOpen = false" class="rounded-full hover:bg-gray-200">
                        <X class="h-6 w-6 text-gray-500" />
                    </Button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-8">
                    <!-- Customer Select -->
                    <div class="space-y-3">
                        <label class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                            Khách hàng
                        </label>
                        <div class="relative">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    v-model="customerSearch"
                                    placeholder="Tìm tên / SĐT / Email..."
                                    class="h-12 pl-10 bg-gray-50 focus:bg-white transition-colors"
                                    @focus="openSuggestions"
                                    @blur="closeSuggestionsBlur"
                                />
                            </div>
                            <div v-if="isCustomerSuggestionsOpen && customerSearch.length >= 2" class="absolute z-[70] w-full mt-2 bg-white border rounded-xl shadow-2xl max-h-64 overflow-auto animate-in fade-in zoom-in-95 duration-200">
                                <div v-if="isSearchingCustomers" class="p-6 text-center text-sm text-gray-500 flex items-center justify-center gap-2">
                                    <div class="w-4 h-4 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                                    Đang tìm kiếm...
                                </div>
                                <template v-else>
                                    <button
                                        v-for="c in customerOptions"
                                        :key="c.id"
                                        class="w-full px-5 py-4 text-left text-sm hover:bg-indigo-50 border-b border-gray-50 last:border-b-0 transition-colors flex flex-col gap-0.5"
                                        @mousedown.prevent="selectCustomer(c)"
                                    >
                                        <span class="font-bold text-gray-900">{{ c.name }}</span>
                                        <span class="text-xs text-gray-500">{{ c.phone || c.email || 'N/A' }}</span>
                                    </button>
                                    <div v-if="customerOptions.length === 0" class="p-6 text-center text-sm text-gray-500">Không tìm thấy khách hàng phù hợp</div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Date Selection -->
                    <div class="space-y-4">
                        <label class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                            Khoảng thời gian
                        </label>
                        <div class="grid grid-cols-1 gap-4">
                            <div class="space-y-1.5">
                                <span class="text-xs text-gray-500 ml-1">Từ ngày</span>
                                <Input v-model="filters.date_from" type="date" class="h-12 bg-gray-50 focus:bg-white transition-colors" />
                            </div>
                            <div class="space-y-1.5">
                                <span class="text-xs text-gray-500 ml-1">Đến ngày</span>
                                <Input v-model="filters.date_to" type="date" class="h-12 bg-gray-50 focus:bg-white transition-colors" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t bg-white grid grid-cols-2 gap-4">
                    <Button variant="ghost" class="h-12 text-base font-semibold text-gray-600 hover:bg-gray-100" @click="clearFilters">Xóa trắng</Button>
                    <Button class="h-12 text-base font-bold shadow-lg shadow-indigo-200" @click="applyFilters">Áp dụng lọc</Button>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>
