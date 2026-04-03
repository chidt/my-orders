<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Edit,
    Phone,
    Plus,
    Search,
    Trash2,
    UserRoundCog,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import siteRoute from '@/routes/site';
import type { SupplierListProps } from '@/types/supplier';

const props = defineProps<SupplierListProps>();
const { can } = usePermissions();

const breadcrumbs = [
    {
        title: props.site.name,
        href: siteRoute.dashboard.url(props.site.slug),
        current: false,
    },
    {
        title: 'Quản lý nhà cung cấp',
        href: siteRoute.suppliers.index.url(props.site.slug),
        current: true,
    },
];

const search = ref(props.filters.search ?? '');
const sortBy = ref(props.filters.sort_by ?? 'name');
const isDeleting = ref<number | null>(null);
const showDeleteDialog = ref(false);
const supplierToDelete = ref<
    null | SupplierListProps['suppliers']['data'][number]
>(null);

const showSummary = computed(() => props.suppliers.total > 0);

const applyFilters = () => {
    router.get(
        siteRoute.suppliers.index.url(props.site.slug),
        {
            search: search.value || undefined,
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

const clearFilters = () => {
    search.value = '';
    sortBy.value = 'name';
    applyFilters();
};

const openDeleteDialog = (
    supplier: SupplierListProps['suppliers']['data'][number],
) => {
    supplierToDelete.value = supplier;
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (!supplierToDelete.value) return;

    isDeleting.value = supplierToDelete.value.id;
    showDeleteDialog.value = false;

    router.delete(
        siteRoute.suppliers.destroy.url([
            props.site.slug,
            supplierToDelete.value.id,
        ]),
        {
            preserveScroll: true,
            onFinish: () => {
                isDeleting.value = null;
                supplierToDelete.value = null;
            },
        },
    );
};

const translatePaginationLabel = (label: string) => {
    if (label.includes('Previous')) return '« Trước';
    if (label.includes('Next')) return 'Sau »';

    return label;
};
</script>

<template>
    <Head :title="`Quản lý nhà cung cấp - ${site.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header with Title and Search -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">
                        Quản lý nhà cung cấp
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Quản lý danh sách nhà cung cấp cho {{ site.name }}
                    </p>
                </div>
                <div class="w-full sm:w-80">
                    <div class="relative">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                        />
                        <Input
                            v-model="search"
                            placeholder="Tìm tên, người phụ trách, điện thoại..."
                            class="h-11 pl-9 text-sm sm:h-10"
                            @keyup.enter="applyFilters"
                        />
                        <button
                            v-if="search"
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            type="button"
                            @click="
                                search = '';
                                applyFilters();
                            "
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="
                    ($page.props.flash as any)?.success ||
                    ($page.props.flash as any)?.message
                "
                class="rounded-md border border-green-200 bg-green-50 px-4 py-3"
            >
                <p class="text-sm font-medium text-green-800">
                    {{
                        ($page.props.flash as any)?.success ||
                        ($page.props.flash as any)?.message
                    }}
                </p>
            </div>
            <div
                v-if="($page.props.flash as any)?.error"
                class="rounded-md border border-red-200 bg-red-50 px-4 py-3"
            >
                <p class="text-sm font-medium text-red-800">
                    {{ ($page.props.flash as any).error }}
                </p>
            </div>

            <!-- Stats and Filter Section -->
            <div class="space-y-3 rounded-lg border bg-white p-3 sm:p-4">
                <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="rounded-lg border bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Tổng nhà cung cấp</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">
                            {{ statistics.total }}
                        </p>
                    </div>
                    <div class="rounded-lg border bg-gray-50 p-4 md:col-span-2">
                        <p class="text-sm text-gray-500">
                            Danh sách nhà cung cấp hiện có
                        </p>
                        <p class="mt-1 text-sm text-gray-700">
                            Bạn có thể tìm kiếm, sắp xếp, chỉnh sửa và xóa nhà
                            cung cấp tại đây.
                        </p>
                    </div>
                </div>

                <p class="text-sm font-medium text-gray-700">
                    Bộ lọc và sắp xếp
                </p>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="w-full sm:min-w-50 sm:flex-1">
                        <Select v-model="sortBy">
                            <SelectTrigger class="h-11 sm:h-10">
                                <SelectValue placeholder="Sắp xếp theo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="name">Tên</SelectItem>
                                <SelectItem value="products_count"
                                    >Số sản phẩm</SelectItem
                                >
                                <SelectItem value="created_at"
                                    >Ngày tạo</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            type="button"
                            class="h-11 flex-1 sm:h-10 sm:flex-none"
                            @click="applyFilters"
                        >
                            Lọc
                        </Button>
                        <Button
                            variant="outline"
                            type="button"
                            class="h-11 flex-1 sm:h-10 sm:flex-none"
                            @click="clearFilters"
                        >
                            Xóa lọc
                        </Button>
                        <Button
                            v-if="can('create_suppliers')"
                            :as="Link"
                            :href="siteRoute.suppliers.create.url(site.slug)"
                            class="h-11 flex-1 sm:h-10 sm:flex-none"
                        >
                            <Plus class="mr-2 h-4 w-4" />
                            Thêm mới
                        </Button>
                    </div>
                </div>
            </div>

            <div v-if="showSummary" class="text-sm text-gray-600">
                Hiển thị {{ suppliers.data.length }} trong tổng số
                {{ suppliers.total }} nhà cung cấp
            </div>

            <div class="overflow-hidden rounded-lg border bg-white shadow-sm">
                <div
                    v-if="suppliers.data.length === 0"
                    class="py-12 text-center"
                >
                    <UserRoundCog
                        class="mx-auto mb-4 h-12 w-12 text-gray-300"
                    />
                    <h3 class="text-lg font-medium text-gray-900">
                        Chưa có nhà cung cấp nào
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Bắt đầu bằng cách thêm nhà cung cấp đầu tiên.
                    </p>
                    <div v-if="can('create_suppliers')" class="mt-4">
                        <Button
                            :as="Link"
                            :href="siteRoute.suppliers.create.url(site.slug)"
                            class="cursor-pointer"
                        >
                            <Plus class="mr-2 h-4 w-4" />Thêm nhà cung cấp đầu
                            tiên
                        </Button>
                    </div>
                </div>

                <div v-else>
                    <div class="hidden overflow-x-auto md:block">
                        <table
                            class="w-full min-w-225 divide-y divide-gray-200"
                        >
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap text-gray-500 uppercase"
                                    >
                                        Tên nhà cung cấp
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap text-gray-500 uppercase"
                                    >
                                        Người phụ trách
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap text-gray-500 uppercase"
                                    >
                                        Liên hệ
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap text-gray-500 uppercase"
                                    >
                                        Số SP
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium tracking-wider whitespace-nowrap text-gray-500 uppercase"
                                    >
                                        Hành động
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="supplier in suppliers.data"
                                    :key="supplier.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ supplier.name }}
                                        </div>
                                        <div
                                            v-if="supplier.address"
                                            class="mt-1 text-xs text-gray-500"
                                        >
                                            {{ supplier.address }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ supplier.person_in_charge || '-' }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm whitespace-nowrap text-gray-700"
                                    >
                                        <div class="flex items-center gap-1">
                                            <Phone
                                                v-if="supplier.phone"
                                                class="h-3 w-3 text-gray-400"
                                            />
                                            <span>{{
                                                supplier.phone || '-'
                                            }}</span>
                                        </div>
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm whitespace-nowrap text-gray-700"
                                    >
                                        {{
                                            (supplier as any).products_count ??
                                            0
                                        }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-right text-sm whitespace-nowrap"
                                    >
                                        <div
                                            class="flex items-center justify-end gap-2"
                                        >
                                            <Button
                                                v-if="can('edit_suppliers')"
                                                :as="Link"
                                                :href="
                                                    siteRoute.suppliers.edit.url(
                                                        [
                                                            site.slug,
                                                            supplier.id,
                                                        ],
                                                    )
                                                "
                                                variant="ghost"
                                                size="sm"
                                                class="p-2"
                                                title="Chỉnh sửa"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <button
                                                v-if="can('delete_suppliers')"
                                                type="button"
                                                class="inline-flex cursor-pointer items-center rounded p-1 text-red-600 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                                :disabled="
                                                    isDeleting ===
                                                        supplier.id ||
                                                    ((supplier as any)
                                                        .products_count ?? 0) >
                                                        0
                                                "
                                                @click="
                                                    openDeleteDialog(supplier)
                                                "
                                                :title="
                                                    ((supplier as any)
                                                        .products_count ?? 0) >
                                                    0
                                                        ? 'Nhà cung cấp đã có sản phẩm, không thể xóa'
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

                    <div class="space-y-3 p-3 md:hidden">
                        <div
                            v-for="supplier in suppliers.data"
                            :key="`mobile-${supplier.id}`"
                            class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
                        >
                            <div
                                class="mb-3 flex items-start justify-between gap-3"
                            >
                                <div class="min-w-0 flex-1">
                                    <h3
                                        class="truncate text-lg font-semibold text-gray-900"
                                    >
                                        {{ supplier.name }}
                                    </h3>
                                    <p
                                        v-if="supplier.address"
                                        class="mt-1 line-clamp-1 text-xs text-gray-500"
                                    >
                                        {{ supplier.address }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    <Button
                                        v-if="can('edit_suppliers')"
                                        :as="Link"
                                        :href="
                                            siteRoute.suppliers.edit.url([
                                                site.slug,
                                                supplier.id,
                                            ])
                                        "
                                        variant="ghost"
                                        size="sm"
                                        class="h-8 w-8 p-2"
                                        title="Chỉnh sửa"
                                    >
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <button
                                        v-if="can('delete_suppliers')"
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded text-red-600 hover:bg-red-50 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="
                                            isDeleting === supplier.id ||
                                            ((supplier as any).products_count ??
                                                0) > 0
                                        "
                                        @click="openDeleteDialog(supplier)"
                                        :title="
                                            ((supplier as any).products_count ??
                                                0) > 0
                                                ? 'Nhà cung cấp đã có sản phẩm, không thể xóa'
                                                : 'Xóa'
                                        "
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500">Người phụ trách</p>
                                    <p class="font-medium text-gray-900">
                                        {{ supplier.person_in_charge || '-' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Số SP</p>
                                    <p class="font-medium text-gray-900">
                                        {{
                                            (supplier as any).products_count ??
                                            0
                                        }}
                                    </p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-gray-500">Liên hệ</p>
                                    <div
                                        class="flex items-center gap-1 font-medium text-gray-900"
                                    >
                                        <Phone
                                            v-if="supplier.phone"
                                            class="h-3 w-3 text-gray-400"
                                        />
                                        <span>{{ supplier.phone || '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="suppliers.last_page > 1"
                    class="border-t bg-gray-50/30 px-4 py-4 sm:px-6"
                >
                    <div
                        class="flex flex-col items-center justify-between gap-4 sm:flex-row"
                    >
                        <div
                            class="order-2 text-xs text-gray-600 sm:order-1 sm:text-sm"
                        >
                            Hiển thị từ
                            <span class="font-bold text-gray-900">{{
                                (suppliers as any).from ?? 1
                            }}</span>
                            đến
                            <span class="font-bold text-gray-900">{{
                                (suppliers as any).to ?? suppliers.data.length
                            }}</span>
                            trong tổng số
                            <span class="font-bold text-gray-900">{{
                                suppliers.total
                            }}</span>
                            nhà cung cấp
                        </div>
                        <nav
                            class="order-1 inline-flex -space-x-px overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm sm:order-2"
                        >
                            <template
                                v-for="(link, index) in suppliers.links"
                                :key="index"
                            >
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'relative inline-flex items-center px-4 py-2 text-sm font-medium transition-all duration-200',
                                        link.active
                                            ? 'z-10 border-indigo-600 bg-indigo-600 text-white'
                                            : 'border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-indigo-600',
                                        index !== 0 ? 'border-l' : '',
                                    ]"
                                >
                                    <span>{{
                                        translatePaginationLabel(link.label)
                                    }}</span>
                                </Link>
                                <span
                                    v-else
                                    :class="[
                                        'relative inline-flex cursor-not-allowed items-center bg-gray-50 px-4 py-2 text-sm font-medium text-gray-300',
                                        index !== 0 ? 'border-l' : '',
                                    ]"
                                >
                                    <span>{{
                                        translatePaginationLabel(link.label)
                                    }}</span>
                                </span>
                            </template>
                        </nav>
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
                    <DialogTitle>Xác nhận xóa nhà cung cấp</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc chắn muốn xóa nhà cung cấp
                        <span class="font-semibold">{{
                            supplierToDelete?.name
                        }}</span
                        >?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        class="cursor-pointer"
                        type="button"
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
                            >Xóa nhà cung cấp</span
                        >
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
