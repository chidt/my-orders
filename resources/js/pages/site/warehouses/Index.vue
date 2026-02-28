<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Store, Plus, Edit, Trash2, MapPin } from 'lucide-vue-next';
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
    { title: props.site.name, href: siteRoute.dashboard.url(props.site.slug), current: false },
    { title: 'Quản lý kho', href: siteRoute.warehouses.index.url(props.site.slug), current: true },
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
            siteRoute.warehouses.destroy.url([props.site.slug, warehouseToDelete.value.id]),
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
        <div class="min-h-screen bg-gray-50">
            <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
                <div class="px-4 py-6 sm:px-0">
                    <!-- Header -->
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <h1 class="text-base font-semibold leading-6 text-gray-900">
                                Quản lý kho
                            </h1>
                            <p class="mt-2 text-sm text-gray-700">
                                Quản lý kho hàng cho {{ site.name }}
                            </p>
                        </div>
                        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">

                            <Link
                                v-if="can('create_warehouses')"
                                :href="siteRoute.warehouses.create.url(site.slug)"
                                class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                            >
                                Thêm kho mới
                            </Link>
                        </div>
                    </div>

                <!-- Search -->
                <div class="mb-6">
                    <div class="max-w-md">
                        <label for="search" class="sr-only">Tìm kiếm kho</label>
                        <input
                            id="search"
                            v-model="search"
                            type="text"
                            placeholder="Tìm kiếm theo tên, mã kho hoặc địa chỉ..."
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Warehouses Table -->
                <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                    <div
                        v-if="filteredWarehouses.length === 0"
                        class="py-12 text-center"
                    >
                        <Store
                            class="mx-auto mb-4 h-12 w-12 text-gray-400"
                        />
                        <h3 class="mb-2 text-lg font-medium text-gray-900">
                            {{
                                search
                                    ? 'Không tìm thấy kho nào'
                                    : 'Chưa có kho nào'
                            }}
                        </h3>
                        <p class="mb-4 text-gray-500">
                            {{
                                search
                                    ? 'Thử tìm kiếm với từ khóa khác'
                                    : 'Bắt đầu bằng cách tạo kho đầu tiên của bạn'
                            }}
                        </p>
                        <Link
                            v-if="can('create_warehouses') && !search"
                            :href="siteRoute.warehouses.create.url(site.slug)"
                            class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out hover:bg-blue-500"
                        >
                            <Plus class="mr-2 h-4 w-4" />
                            Tạo kho đầu tiên
                        </Link>
                    </div>

                    <div v-else>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Mã kho
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Tên kho
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Địa chỉ
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Số vị trí
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
                                    v-for="warehouse in filteredWarehouses"
                                    :key="warehouse.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ warehouse.code }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ warehouse.name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            {{ warehouse.address }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800"
                                        >
                                            {{ warehouse.locations_count }} vị
                                            trí
                                        </span>
                                    </td>
                                    <td
                                        class="px-6 py-4 text-right text-sm font-medium whitespace-nowrap"
                                    >
                                        <div
                                            class="flex items-center justify-end space-x-2"
                                        >
                                            <Link
                                                :href="
                                                    siteRoute.warehouses.locations.index.url([
                                                        site.slug,
                                                        warehouse.id,
                                                    ])
                                                "
                                                class="rounded p-1 text-green-600 hover:text-green-900"
                                                title="Quản lý vị trí"
                                            >
                                                <MapPin class="h-4 w-4" />
                                            </Link>

                                            <Link
                                                v-if="can('edit_warehouses')"
                                                :href="
                                                    siteRoute.warehouses.edit.url([
                                                        site.slug,
                                                        warehouse.id,
                                                    ])
                                                "
                                                class="rounded p-1 text-blue-600 hover:text-blue-900"
                                                title="Chỉnh sửa"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Link>

                                            <button
                                                v-if="can('delete_warehouses')"
                                                @click="openDeleteDialog(warehouse)"
                                                :disabled="
                                                    isDeleting === warehouse.id
                                                "
                                                class="rounded p-1 text-red-600 hover:text-red-900 disabled:opacity-50"
                                                title="Xóa"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div
                            v-if="warehouses.last_page > 1"
                            class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
                        >
                            <div class="flex items-center justify-between">
                                <div
                                    class="flex flex-1 justify-between sm:hidden"
                                >
                                    <Link
                                        v-if="warehouses.links[0].url"
                                        :href="warehouses.links[0].url!"
                                        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Trước
                                    </Link>
                                    <Link
                                        v-if="
                                            warehouses.links[
                                                warehouses.links.length - 1
                                            ].url
                                        "
                                        :href="
                                            warehouses.links[
                                                warehouses.links.length - 1
                                            ].url!
                                        "
                                        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Sau
                                    </Link>
                                </div>
                                <div
                                    class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Hiển thị
                                            <span class="font-medium">{{
                                                (warehouses.current_page - 1) *
                                                    warehouses.per_page +
                                                1
                                            }}</span>
                                            đến
                                            <span class="font-medium">{{
                                                Math.min(
                                                    warehouses.current_page *
                                                        warehouses.per_page,
                                                    warehouses.total,
                                                )
                                            }}</span>
                                            trong tổng số
                                            <span class="font-medium">{{
                                                warehouses.total
                                            }}</span>
                                            kết quả
                                        </p>
                                    </div>
                                    <div>
                                        <nav
                                            class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm"
                                            aria-label="Pagination"
                                        >
                                            <template
                                                v-for="(
                                                    link, index
                                                ) in warehouses.links"
                                                :key="index"
                                            >
                                                <Link
                                                    v-if="link.url"
                                                    :href="link.url!"
                                                    :class="[
                                                        link.active
                                                            ? 'z-10 border-blue-500 bg-blue-50 text-blue-600'
                                                            : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50',
                                                        'relative inline-flex items-center border px-4 py-2 text-sm font-medium',
                                                    ]"
                                                >
                                                    {{ link.label }}
                                                </Link>
                                                <span
                                                    v-else
                                                    :class="[
                                                        'relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-300',
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
                    </div>
                </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Xác nhận xóa kho</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc chắn muốn xóa kho
                        <span class="font-semibold">{{ warehouseToDelete?.name }}</span>?
                        <br>
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
