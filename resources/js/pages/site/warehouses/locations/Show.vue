<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    Edit,
    MapPin,
    Package,
    Star,
    Trash2,
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
}

interface Location {
    id: number;
    code: string;
    name: string;
    is_default: boolean;
    qty_in_stock: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    site: Site;
    warehouse: Warehouse;
    location: Location;
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
        current: false,
    },
    {
        title: props.warehouse.name,
        href: siteRoute.warehouses.locations.index.url([
            props.site.slug,
            props.warehouse.id,
        ]),
        current: false,
    },
    {
        title: props.location.name,
        href: siteRoute.warehouses.locations.show.url([
            props.site.slug,
            props.warehouse.id,
            props.location.id,
        ]),
        current: true,
    },
];

// Computed properties
const canEditLocation = computed(() => can('edit_warehouse_locations'));
const canDeleteLocation = computed(() => can('delete_warehouse_locations'));

// Delete dialog state
const deleteDialog = ref({
    open: false,
});

// Delete functions
function openDeleteDialog() {
    deleteDialog.value.open = true;
}

function closeDeleteDialog() {
    deleteDialog.value.open = false;
}

function deleteLocation() {
    router.delete(
        siteRoute.warehouses.locations.destroy.url([
            props.site.slug,
            props.warehouse.id,
            props.location.id,
        ]),
        {
            onSuccess: () => {
                closeDeleteDialog();
            },
        },
    );
}

// Navigation functions
function goToEdit() {
    router.visit(
        siteRoute.warehouses.locations.edit.url([
            props.site.slug,
            props.warehouse.id,
            props.location.id,
        ]),
    );
}

// Utility functions
function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head :title="`${location.name} - ${warehouse.name} - ${site.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header with Back Button and Title -->
            <div class="flex items-center gap-4">
                <Link
                    :href="
                        siteRoute.warehouses.locations.index.url([
                            site.slug,
                            warehouse.id,
                        ])
                    "
                    class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                    title="Quay lại"
                >
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100"
                    >
                        <MapPin class="h-6 w-6 text-blue-600" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1
                                class="text-xl font-bold text-gray-900 sm:text-2xl"
                            >
                                {{ location.name }}
                            </h1>
                            <span
                                v-if="location.is_default"
                                class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800"
                            >
                                <Star class="mr-1 h-3 w-3" />
                                Mặc định
                            </span>
                        </div>
                        <p class="text-sm text-gray-500">
                            {{ location.code }} - {{ warehouse.name }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ warehouse.address }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between">
                <div></div>
                <div class="flex items-center gap-3">
                    <Button
                        v-if="canEditLocation"
                        @click="goToEdit"
                        class="flex items-center gap-2"
                    >
                        <Edit class="h-4 w-4" />
                        Chỉnh sửa
                    </Button>

                    <Button
                        v-if="canDeleteLocation && !location.is_default"
                        variant="destructive"
                        @click="openDeleteDialog"
                        class="flex items-center gap-2"
                    >
                        <Trash2 class="h-4 w-4" />
                        Xóa
                    </Button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main Information -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Basic Information -->
                    <div
                        class="overflow-hidden rounded-lg border bg-white shadow-sm"
                    >
                        <div class="border-b bg-gray-50 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Thông tin cơ bản
                            </h3>
                            <p class="text-sm text-gray-500">
                                Chi tiết về vị trí {{ location.name }}
                            </p>
                        </div>
                        <div class="space-y-4 p-6">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label
                                        class="text-sm font-medium text-gray-500"
                                        >Mã vị trí</label
                                    >
                                    <p
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        {{ location.code }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="text-sm font-medium text-gray-500"
                                        >Tên vị trí</label
                                    >
                                    <p
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        {{ location.name }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="text-sm font-medium text-gray-500"
                                        >Trạng thái</label
                                    >
                                    <div class="flex items-center space-x-2">
                                        <span
                                            v-if="location.is_default"
                                            class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-sm font-medium text-yellow-800"
                                        >
                                            <Star class="mr-1 h-4 w-4" />
                                            Vị trí mặc định
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-800"
                                        >
                                            Vị trí thường
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="text-sm font-medium text-gray-500"
                                        >Kho</label
                                    >
                                    <p
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        {{ warehouse.name }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ warehouse.code }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inventory Information -->
                    <div
                        class="overflow-hidden rounded-lg border bg-white shadow-sm"
                    >
                        <div class="border-b bg-gray-50 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Thông tin tồn kho
                            </h3>
                            <p class="text-sm text-gray-500">
                                Tình trạng hàng hóa tại vị trí này
                            </p>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center space-x-4">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100"
                                >
                                    <Package class="h-6 w-6 text-green-600" />
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-medium text-gray-500"
                                    >
                                        Tổng tồn kho
                                    </p>
                                    <p
                                        class="text-3xl font-bold text-green-600"
                                    >
                                        {{ location.qty_in_stock }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{
                                            location.qty_in_stock === 0
                                                ? 'Không có hàng'
                                                : 'Có hàng tồn kho'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="location.qty_in_stock === 0"
                                class="mt-4 rounded-md border border-blue-200 bg-blue-50 p-4"
                            >
                                <p class="text-sm text-blue-800">
                                    <strong>Thông tin:</strong> Vị trí này hiện
                                    không có hàng tồn kho. Bạn có thể nhập hàng
                                    vào vị trí này thông qua chức năng nhập kho.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div
                        class="overflow-hidden rounded-lg border bg-white shadow-sm"
                    >
                        <div class="border-b bg-gray-50 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Thao tác
                            </h3>
                            <p class="text-sm text-gray-500">
                                Các hành động có thể thực hiện với vị trí này
                            </p>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <Button
                                    v-if="canEditLocation"
                                    @click="goToEdit"
                                    variant="outline"
                                    class="justify-start"
                                >
                                    <Edit class="mr-2 h-4 w-4" />
                                    Chỉnh sửa thông tin
                                </Button>

                                <Button
                                    v-if="
                                        canDeleteLocation &&
                                        !location.is_default
                                    "
                                    @click="openDeleteDialog"
                                    variant="outline"
                                    class="justify-start text-red-600 hover:text-red-700"
                                >
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Xóa vị trí
                                </Button>

                                <div
                                    v-if="location.is_default"
                                    class="md:col-span-2"
                                >
                                    <div
                                        class="rounded-md border border-yellow-200 bg-yellow-50 p-3"
                                    >
                                        <p class="text-sm text-yellow-800">
                                            <strong>Lưu ý:</strong> Đây là vị
                                            trí mặc định của kho. Bạn không thể
                                            xóa vị trí này trực tiếp. Để xóa,
                                            hãy đặt vị trí khác làm mặc định
                                            trước.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Timeline -->
                    <div
                        class="overflow-hidden rounded-lg border bg-white shadow-sm"
                    >
                        <div class="border-b bg-gray-50 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Thông tin thời gian
                            </h3>
                            <p class="text-sm text-gray-500">
                                Lịch sử tạo và cập nhật
                            </p>
                        </div>
                        <div class="space-y-4 p-6">
                            <div class="flex items-start space-x-3">
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100"
                                >
                                    <Calendar class="h-4 w-4 text-green-600" />
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        Tạo lúc
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ formatDate(location.created_at) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3">
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100"
                                >
                                    <Edit class="h-4 w-4 text-blue-600" />
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        Cập nhật lúc
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ formatDate(location.updated_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div
                        class="overflow-hidden rounded-lg border bg-white shadow-sm"
                    >
                        <div class="border-b bg-gray-50 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Thống kê nhanh
                            </h3>
                        </div>
                        <div class="space-y-4 p-6">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500"
                                    >Mã vị trí</span
                                >
                                <span class="text-sm font-medium">{{
                                    location.code
                                }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500"
                                    >Loại vị trí</span
                                >
                                <span class="text-sm font-medium">
                                    {{
                                        location.is_default
                                            ? 'Mặc định'
                                            : 'Thường'
                                    }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500"
                                    >Tồn kho</span
                                >
                                <span class="text-sm font-medium">{{
                                    location.qty_in_stock
                                }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Kho</span>
                                <span class="text-sm font-medium">{{
                                    warehouse.code
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Dialog -->
        <Dialog :open="deleteDialog.open" @update:open="closeDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Xóa vị trí</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc chắn muốn xóa vị trí "{{ location.name }}"
                        không? Hành động này không thể hoàn tác.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="closeDeleteDialog">
                        Hủy
                    </Button>
                    <Button variant="destructive" @click="deleteLocation">
                        Xóa
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
