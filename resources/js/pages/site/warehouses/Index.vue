<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Edit, MapPin, Plus, Search, Store, Trash2, X } from 'lucide-vue-next';
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
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import siteRoute from '@/routes/site';

interface Site {
    id: number;
    name: string;
    slug: string;
}

interface Warehouse {
    id: number;
    code: string;
    name: string;
    address: string;
    locations_count: number;
}

interface PaginatedWarehouses {
    data: Warehouse[];
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

interface Props {
    site: Site;
    warehouses: PaginatedWarehouses;
}

const props = defineProps<Props>();
const { can } = usePermissions();

const breadcrumbs = [
    {
        title: props.site.name,
        href: siteRoute.dashboard.url(props.site.slug),
        current: false,
    },
    {
        title: 'Quản lý kho',
        href: siteRoute.warehouses.index.url(props.site.slug),
        current: true,
    },
];

const search = ref('');
const isDeleting = ref<number | null>(null);
const showDeleteDialog = ref(false);
const warehouseToDelete = ref<Warehouse | null>(null);

const filteredWarehouses = computed(() => {
    if (!search.value) return props.warehouses.data;

    return props.warehouses.data.filter(
        (warehouse) =>
            warehouse.name.toLowerCase().includes(search.value.toLowerCase()) ||
            warehouse.code.toLowerCase().includes(search.value.toLowerCase()) ||
            warehouse.address
                .toLowerCase()
                .includes(search.value.toLowerCase()),
    );
});

const clearSearch = () => {
    search.value = '';
};

const openDeleteDialog = (warehouse: Warehouse) => {
    warehouseToDelete.value = warehouse;
    showDeleteDialog.value = true;
};

const confirmDelete = async () => {
    if (!warehouseToDelete.value) return;

    isDeleting.value = warehouseToDelete.value.id;
    showDeleteDialog.value = false;

    try {
        router.delete(
            siteRoute.warehouses.destroy.url([
                props.site.slug,
                warehouseToDelete.value.id,
            ]),
            {
                preserveScroll: true,
                onFinish: () => {
                    isDeleting.value = null;
                    warehouseToDelete.value = null;
                },
            },
        );
    } catch {
        isDeleting.value = null;
        warehouseToDelete.value = null;
    }
};

const cancelDelete = () => {
    showDeleteDialog.value = false;
    warehouseToDelete.value = null;
};
</script>

<template>
    <Head :title="`Quản lý kho - ${site.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header with Title and Search -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">
                        Quản lý kho
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Quản lý kho hàng cho {{ site.name }}
                    </p>
                </div>
                <div class="w-full sm:w-80">
                    <div class="relative">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                        />
                        <Input
                            v-model="search"
                            placeholder="Tìm kiếm theo tên, mã kho hoặc địa chỉ..."
                            class="h-11 pl-9 text-sm sm:h-10"
                        />
                        <button
                            v-if="search"
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            type="button"
                            @click="clearSearch"
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
                        <p class="text-sm text-gray-500">Tổng kho</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">
                            {{ warehouses.total }}
                        </p>
                    </div>
                    <div class="rounded-lg border bg-gray-50 p-4 md:col-span-2">
                        <p class="text-sm text-gray-500">
                            Hệ thống quản lý kho
                        </p>
                        <p class="mt-1 text-sm text-gray-700">
                            Quản lý kho hàng, vị trí lưu trữ và theo dõi tồn kho
                            hiệu quả cho doanh nghiệp.
                        </p>
                    </div>
                </div>

                <p class="text-sm font-medium text-gray-700">Thao tác nhanh</p>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="w-full sm:flex-1">
                        <!-- Empty space for layout consistency -->
                    </div>
                    <div class="flex gap-2">
                        <Button
                            v-if="can('create_warehouses')"
                            :as="Link"
                            :href="siteRoute.warehouses.create.url(site.slug)"
                            class="h-11 flex-1 sm:h-10 sm:flex-none"
                        >
                            <Plus class="mr-2 h-4 w-4" />
                            Thêm kho mới
                        </Button>
                    </div>
                </div>
            </div>

            <div v-if="warehouses.total > 0" class="text-sm text-gray-600">
                Hiển thị {{ filteredWarehouses.length }} trong tổng số
                {{ warehouses.total }} kho
            </div>

            <div class="overflow-hidden rounded-lg border bg-white shadow-sm">
                <div
                    v-if="filteredWarehouses.length === 0"
                    class="py-12 text-center"
                >
                    <Store class="mx-auto mb-4 h-12 w-12 text-gray-300" />
                    <h3 class="text-lg font-medium text-gray-900">
                        {{
                            search
                                ? 'Không tìm thấy kho nào'
                                : 'Chưa có kho nào'
                        }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        {{
                            search
                                ? 'Thử tìm kiếm với từ khóa khác'
                                : 'Bắt đầu bằng cách tạo kho đầu tiên của bạn'
                        }}
                    </p>
                    <div
                        v-if="can('create_warehouses') && !search"
                        class="mt-4"
                    >
                        <Button
                            :as="Link"
                            :href="siteRoute.warehouses.create.url(site.slug)"
                            class="cursor-pointer"
                        >
                            <Plus class="mr-2 h-4 w-4" />
                            Tạo kho đầu tiên
                        </Button>
                    </div>
                </div>

                <div v-else>
                    <!-- Desktop Table -->
                    <div class="hidden overflow-x-auto md:block">
                        <table
                            class="w-full min-w-200 divide-y divide-gray-200"
                        >
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                    >
                                        Mã kho
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                    >
                                        Tên kho
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                    >
                                        Địa chỉ
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                    >
                                        Số vị trí
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-gray-500 uppercase"
                                    >
                                        Thao tác
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr
                                    v-for="warehouse in filteredWarehouses"
                                    :key="warehouse.id"
                                    class="transition-colors hover:bg-gray-50"
                                >
                                    <td
                                        class="px-4 py-3 text-sm font-bold text-gray-900"
                                    >
                                        {{ warehouse.code }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-sm font-medium text-gray-900"
                                    >
                                        {{ warehouse.name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ warehouse.address }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span
                                            class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800"
                                        >
                                            {{ warehouse.locations_count }} vị
                                            trí
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div
                                            class="flex items-center justify-end gap-2"
                                        >
                                            <Button
                                                :as="Link"
                                                :href="
                                                    siteRoute.warehouses.locations.index.url(
                                                        [
                                                            site.slug,
                                                            warehouse.id,
                                                        ],
                                                    )
                                                "
                                                variant="ghost"
                                                size="sm"
                                                class="p-2"
                                                title="Quản lý vị trí"
                                            >
                                                <MapPin class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                v-if="can('edit_warehouses')"
                                                :as="Link"
                                                :href="
                                                    siteRoute.warehouses.edit.url(
                                                        [
                                                            site.slug,
                                                            warehouse.id,
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
                                                v-if="can('delete_warehouses')"
                                                type="button"
                                                class="inline-flex cursor-pointer items-center rounded p-1 text-red-600 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                                :disabled="
                                                    isDeleting === warehouse.id
                                                "
                                                @click="
                                                    openDeleteDialog(warehouse)
                                                "
                                                title="Xóa"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="space-y-3 p-3 md:hidden">
                        <div
                            v-for="warehouse in filteredWarehouses"
                            :key="`mobile-${warehouse.id}`"
                            class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
                        >
                            <div
                                class="mb-3 flex items-start justify-between gap-3"
                            >
                                <div class="min-w-0 flex-1">
                                    <h3
                                        class="truncate text-lg font-semibold text-gray-900"
                                    >
                                        {{ warehouse.name }}
                                    </h3>
                                    <p
                                        class="mt-1 font-mono text-xs text-gray-500"
                                    >
                                        {{ warehouse.code }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    <Button
                                        :as="Link"
                                        :href="
                                            siteRoute.warehouses.locations.index.url(
                                                [site.slug, warehouse.id],
                                            )
                                        "
                                        variant="ghost"
                                        size="sm"
                                        class="h-8 w-8 p-2"
                                        title="Quản lý vị trí"
                                    >
                                        <MapPin class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        v-if="can('edit_warehouses')"
                                        :as="Link"
                                        :href="
                                            siteRoute.warehouses.edit.url([
                                                site.slug,
                                                warehouse.id,
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
                                        v-if="can('delete_warehouses')"
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded text-red-600 hover:bg-red-50 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="isDeleting === warehouse.id"
                                        @click="openDeleteDialog(warehouse)"
                                        title="Xóa"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500">Địa chỉ</p>
                                    <p
                                        class="line-clamp-2 font-medium text-gray-900"
                                    >
                                        {{ warehouse.address }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Số vị trí</p>
                                    <span
                                        class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800"
                                    >
                                        {{ warehouse.locations_count }} vị trí
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="warehouses.last_page > 1"
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
                                (warehouses.current_page - 1) *
                                    warehouses.per_page +
                                1
                            }}</span>
                            đến
                            <span class="font-bold text-gray-900">{{
                                Math.min(
                                    warehouses.current_page *
                                        warehouses.per_page,
                                    warehouses.total,
                                )
                            }}</span>
                            trong tổng số
                            <span class="font-bold text-gray-900">{{
                                warehouses.total
                            }}</span>
                            kho
                        </div>
                        <nav
                            class="order-1 inline-flex -space-x-px overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm sm:order-2"
                        >
                            <template
                                v-for="(link, index) in warehouses.links"
                                :key="index"
                            >
                                <Link
                                    v-if="link.url"
                                    :href="link.url!"
                                    :class="[
                                        'relative inline-flex items-center px-4 py-2 text-sm font-medium transition-all duration-200',
                                        link.active
                                            ? 'z-10 border-indigo-600 bg-indigo-600 text-white'
                                            : 'border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-indigo-600',
                                        index !== 0 ? 'border-l' : '',
                                    ]"
                                >
                                    {{ link.label }}
                                </Link>
                                <span
                                    v-else
                                    :class="[
                                        'relative inline-flex cursor-not-allowed items-center bg-gray-50 px-4 py-2 text-sm font-medium text-gray-300',
                                        index !== 0 ? 'border-l' : '',
                                    ]"
                                >
                                    {{ link.label }}
                                </span>
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog
            :open="showDeleteDialog"
            @update:open="showDeleteDialog = $event"
        >
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Xác nhận xóa kho</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc chắn muốn xóa kho
                        <span class="font-semibold">{{
                            warehouseToDelete?.name
                        }}</span
                        >?
                        <br />
                        Hành động này không thể hoàn tác.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="cancelDelete">
                        Hủy
                    </Button>
                    <Button
                        variant="destructive"
                        @click="confirmDelete"
                        :disabled="!!isDeleting"
                    >
                        <span v-if="isDeleting">Đang xóa...</span>
                        <span v-else>Xóa kho</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
