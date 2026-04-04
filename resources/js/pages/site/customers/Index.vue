<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    ContactRound,
    Edit,
    Filter,
    Search,
    Trash2,
    UserRound,

} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
import { cn } from '@/lib/utils';
import siteRoute from '@/routes/site';
import type { CustomerListProps } from '@/types/customer';

const props = defineProps<CustomerListProps>();
const { can } = usePermissions();

const breadcrumbs = [
    {
        title: props.site.name,
        href: siteRoute.dashboard.url(props.site.slug),
        current: false,
    },
    {
        title: 'Quản lý khách hàng',
        href: `/${props.site.slug}/customers`,
        current: true,
    },
];

const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? 'all');
const provinceId = ref(props.filters.province_id ?? 'all');
const wardId = ref(props.filters.ward_id ?? 'all');
const sortBy = ref(props.filters.sort_by ?? 'name');
const open = ref(false);
const openWard = ref(false);

const wards = ref<Array<{ id: number; name: string }>>([]);

const loadWards = async (selectedProvince: string) => {
    if (selectedProvince === 'all') {
        wards.value = [];
        return;
    }

    try {
        const response = await axios.get(
            `/api/provinces/${selectedProvince}/wards`,
        );
        wards.value = response.data ?? [];
    } catch {
        wards.value = [];
    }
};

watch(
    provinceId,
    async (newValue) => {
        if (newValue === 'all') {
            wardId.value = 'all';
            wards.value = [];
            return;
        }

        await loadWards(newValue);

        const exists = wards.value.some(
            (ward) => String(ward.id) === String(wardId.value),
        );

        if (!exists) {
            wardId.value = 'all';
        }
    },
    { immediate: true },
);
const isDeleting = ref<number | null>(null);
const showDeleteDialog = ref(false);
const customerToDelete = ref<
    null | CustomerListProps['customers']['data'][number]
>(null);

const showSummary = computed(() => props.customers.total > 0);

const activeFiltersCount = computed(() => {
    let count = 0;
    if (search.value) count++;
    if (type.value !== 'all') count++;
    if (provinceId.value !== 'all') count++;
    if (wardId.value !== 'all') count++;
    if (sortBy.value && sortBy.value !== 'name') count++;
    return count;
});

const showFilterModal = ref(false);

const applyFilters = () => {
    router.get(
        `/${props.site.slug}/customers`,
        {
            search: search.value || undefined,
            type: type.value === 'all' ? undefined : type.value,
            province_id:
                provinceId.value === 'all' ? undefined : provinceId.value,
            ward_id: wardId.value === 'all' ? undefined : wardId.value,
            sort_by: sortBy.value || 'name',
            sort_direction: 'asc',
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const closeFilterModal = () => {
    showFilterModal.value = false;
};

const applyAdvancedFilters = () => {
    applyFilters();
    closeFilterModal();
};

const clearFilters = () => {
    search.value = '';
    type.value = 'all';
    provinceId.value = 'all';
    wardId.value = 'all';
    sortBy.value = 'name';
    applyFilters();
    closeFilterModal();
};

const openDeleteDialog = (
    customer: CustomerListProps['customers']['data'][number],
) => {
    customerToDelete.value = customer;
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (!customerToDelete.value) return;

    isDeleting.value = customerToDelete.value.id;
    showDeleteDialog.value = false;

    router.delete(
        `/${props.site.slug}/customers/${customerToDelete.value.id}`,
        {
            preserveScroll: true,
            onFinish: () => {
                isDeleting.value = null;
                customerToDelete.value = null;
            },
        },
    );
};

const translatePaginationLabel = (label: string) => {
    if (label.includes('Previous')) return '« Trước';
    if (label.includes('Next')) return 'Sau »';

    return label;
};

const typeLabel = (value: number) => props.customerTypes[String(value)] ?? '-';

const formatDate = (value: string) => {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return value;

    return new Intl.DateTimeFormat('vi-VN').format(date);
};
</script>

<template>
    <Head :title="`Quản lý khách hàng - ${site.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 space-y-4 sm:mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Quản lý khách hàng
                    </h1>
                    <p class="mt-2 text-sm text-gray-700">
                        Quản lý danh sách khách hàng cho {{ site.name }}
                    </p>
                </div>
            </div>

            <div
                class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
                >
                    <p class="text-sm text-gray-500">Tổng khách hàng</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ statistics.total }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
                >
                    <p class="text-sm text-gray-500">Cá nhân</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ statistics.individual }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
                >
                    <p class="text-sm text-gray-500">Cộng tác viên</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ statistics.business }}
                    </p>
                </div>
            </div>

            <div
                class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="w-full sm:max-w-md">
                    <div class="relative">
                        <Input
                            v-model="search"
                            placeholder="Tìm kiếm tên, email, SĐT..."
                            class="h-11 pr-10"
                            @input="applyFilters"
                            @keyup.enter="applyFilters"
                        />
                        <button
                            type="button"
                            @click="applyFilters"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                        >
                            <Search class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <Button
                        variant="outline"
                        class="h-11 px-3"
                        @click="showFilterModal = true"
                    >
                        <Filter class="h-4 w-4" />
                        <span class="ml-1">Bộ lọc</span>
                        <span
                            v-if="activeFiltersCount > 0"
                            class="ml-1 inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-indigo-600 px-2 text-xs font-bold text-white"
                        >
                            {{ activeFiltersCount }}
                        </span>
                    </Button>
                    <Button
                        v-if="can('create_customers')"
                        :as="Link"
                        :href="`/${site.slug}/customers/create`"
                        class="h-11"
                    >
                        Tạo khách hàng mới
                    </Button>
                </div>
            </div>

            <div
                v-if="$page.props.flash?.success"
                class="mb-4 rounded-md bg-green-50 p-4"
            >
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg
                            class="h-5 w-5 text-green-400"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L7.53 10.53a.75.75 0 00-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">
                            {{ $page.props.flash.success }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="$page.props.flash?.error"
                class="mb-4 rounded-md bg-red-50 p-4"
            >
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg
                            class="h-5 w-5 text-red-400"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">
                            {{ $page.props.flash.error }}
                        </p>
                    </div>
                </div>
            </div>

            <div v-if="showSummary" class="mb-4 text-sm text-gray-600">
                Hiển thị {{ customers.data.length }} trong tổng số
                {{ customers.total }} khách hàng
            </div>

            <div
                class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    v-if="customers.data.length === 0"
                    class="py-12 text-center"
                >
                    <ContactRound
                        class="mx-auto mb-4 h-12 w-12 text-gray-300"
                    />
                    <h3 class="text-lg font-medium text-gray-900">
                        Chưa có khách hàng nào
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Bắt đầu bằng cách thêm khách hàng đầu tiên.
                    </p>
                </div>

                <div v-else>
                    <div class="hidden overflow-x-auto md:block">
                        <table
                            class="w-full min-w-[900px] divide-y divide-gray-200"
                        >
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Khách hàng
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Liên hệ
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Loại
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Địa chỉ
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Hành động
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="customer in customers.data"
                                    :key="customer.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ customer.name }}
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            Tạo lúc:
                                            {{
                                                formatDate(customer.created_at)
                                            }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div>{{ customer.phone }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ customer.email || '-' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ typeLabel(customer.type) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ customer.address || '-' }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-right text-sm whitespace-nowrap"
                                    >
                                        <div
                                            class="flex items-center justify-end gap-2"
                                        >
                                            <Button
                                                v-if="can('edit_customers')"
                                                :as="Link"
                                                :href="`/${site.slug}/customers/${customer.id}/edit`"
                                                variant="ghost"
                                                size="sm"
                                                class="p-2"
                                                title="Chỉnh sửa"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <button
                                                v-if="can('delete_customers')"
                                                type="button"
                                                class="inline-flex cursor-pointer items-center rounded p-1 text-red-600 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                                :disabled="
                                                    isDeleting ===
                                                        customer.id ||
                                                    customer.can_delete ===
                                                        false
                                                "
                                                @click="
                                                    openDeleteDialog(customer)
                                                "
                                                :title="
                                                    customer.can_delete ===
                                                    false
                                                        ? 'Không thể xóa khách hàng đã có đơn hàng'
                                                        : 'Xóa'
                                                "
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="space-y-3 bg-slate-50/70 p-4 md:hidden">
                        <div
                            v-for="customer in customers.data"
                            :key="`mobile-${customer.id}`"
                            class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                        >
                            <div
                                class="mb-3 flex items-start justify-between gap-3"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <UserRound
                                            class="h-4 w-4 shrink-0 text-gray-400"
                                        />
                                        <p
                                            class="truncate text-sm leading-tight font-semibold text-gray-900"
                                        >
                                            {{ customer.name }}
                                        </p>
                                    </div>
                                    <p
                                        class="mt-1 truncate text-sm text-gray-500"
                                    >
                                        {{ customer.email || '-' }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    <Button
                                        v-if="can('edit_customers')"
                                        :as="Link"
                                        :href="`/${site.slug}/customers/${customer.id}/edit`"
                                        variant="ghost"
                                        size="sm"
                                        class="h-8 w-8 p-2"
                                    >
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <button
                                        v-if="can('delete_customers')"
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded text-red-600 hover:bg-red-50 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="
                                            isDeleting === customer.id ||
                                            customer.can_delete === false
                                        "
                                        @click="openDeleteDialog(customer)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <p class="mb-3 text-sm text-gray-500">
                                {{ customer.phone }}
                            </p>

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span
                                        class="text-xs font-medium tracking-wide text-gray-500 uppercase"
                                        >Loại khách</span
                                    >
                                    <div
                                        class="mt-1 text-sm leading-tight font-medium text-gray-900"
                                    >
                                        {{ typeLabel(customer.type) }}
                                    </div>
                                </div>
                                <div>
                                    <span
                                        class="text-xs font-medium tracking-wide text-gray-500 uppercase"
                                        >Ngày tạo</span
                                    >
                                    <div
                                        class="mt-1 text-sm leading-tight font-medium text-gray-900"
                                    >
                                        {{ formatDate(customer.created_at) }}
                                    </div>
                                </div>
                                <div class="col-span-2">
                                    <span
                                        class="text-xs font-medium tracking-wide text-gray-500 uppercase"
                                        >Địa chỉ</span
                                    >
                                    <div
                                        class="mt-1 text-sm leading-tight font-medium break-words text-gray-900"
                                    >
                                        {{ customer.address || '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="customers.last_page > 1"
                    class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
                >
                    <div class="flex flex-wrap justify-center gap-1">
                        <template
                            v-for="(link, index) in customers.links"
                            :key="index"
                        >
                            <Button
                                v-if="link.url"
                                :as="Link"
                                :href="link.url"
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                class="h-9 min-w-10"
                            >
                                <span>{{
                                    translatePaginationLabel(link.label)
                                }}</span>
                            </Button>
                            <Button
                                v-else
                                variant="outline"
                                size="sm"
                                disabled
                                class="h-9 min-w-10"
                            >
                                <span>{{
                                    translatePaginationLabel(link.label)
                                }}</span>
                            </Button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <Dialog
            :open="showDeleteDialog"
            @update:open="showDeleteDialog = $event"
        >
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Xác nhận xóa khách hàng</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc chắn muốn xóa khách hàng
                        <span class="font-semibold">{{
                            customerToDelete?.name
                        }}</span
                        >?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        type="button"
                        class="cursor-pointer"
                        @click="showDeleteDialog = false"
                        >Hủy</Button
                    >
                    <Button
                        variant="destructive"
                        type="button"
                        :disabled="!!isDeleting"
                        @click="confirmDelete"
                    >
                        <span v-if="isDeleting">Đang xóa...</span>
                        <span v-else class="cursor-pointer"
                            >Xóa khách hàng</span
                        >
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
