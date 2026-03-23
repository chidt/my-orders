<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
function goBack() {
    window.history.back();
}

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
    <Head>
        <title>
            {{ location.name }} - {{ warehouse.name }} - {{ site.name }}
        </title>
    </Head>

    <AppLayout>
        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <nav class="mb-4 flex" aria-label="Breadcrumb">
                        <ol
                            class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse"
                        >
                            <li class="inline-flex items-center">
                                <Link
                                    :href="siteRoute.dashboard.url(site.slug)"
                                    class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600"
                                >
                                    {{ site.name }}
                                </Link>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <span class="mx-2 text-gray-400">/</span>
                                    <Link
                                        :href="
                                            siteRoute.warehouses.index.url(
                                                site.slug,
                                            )
                                        "
                                        class="text-sm font-medium text-gray-700 hover:text-blue-600"
                                    >
                                        Kho hàng
                                    </Link>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <span class="mx-2 text-gray-400">/</span>
                                    <Link
                                        :href="
                                            siteRoute.warehouses.show.url([
                                                site.slug,
                                                warehouse.id,
                                            ])
                                        "
                                        class="text-sm font-medium text-gray-700 hover:text-blue-600"
                                    >
                                        {{ warehouse.name }}
                                    </Link>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <span class="mx-2 text-gray-400">/</span>
                                    <Link
                                        :href="
                                            siteRoute.warehouses.locations.index.url(
                                                [site.slug, warehouse.id],
                                            )
                                        "
                                        class="text-sm font-medium text-gray-700 hover:text-blue-600"
                                    >
                                        Vị trí
                                    </Link>
                                </div>
                            </li>
                            <li aria-current="page">
                                <div class="flex items-center">
                                    <span class="mx-2 text-gray-400">/</span>
                                    <span
                                        class="text-sm font-medium text-gray-500"
                                        >{{ location.name }}</span
                                    >
                                </div>
                            </li>
                        </ol>
                    </nav>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100"
                            >
                                <MapPin class="h-6 w-6 text-blue-600" />
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h1
                                        class="text-3xl font-bold text-gray-900"
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
                                <p class="text-lg text-gray-600">
                                    {{ location.code }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ warehouse.name }} -
                                    {{ warehouse.address }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <Button
                                variant="outline"
                                @click="goBack"
                                class="flex items-center gap-2"
                            >
                                <ArrowLeft class="h-4 w-4" />
                                Quay lại
                            </Button>

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
                </div>

                <!-- Content -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Main Information -->
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Basic Information -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Thông tin cơ bản</CardTitle>
                                <CardDescription>
                                    Chi tiết về vị trí {{ location.name }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                >
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
                                        <div
                                            class="flex items-center space-x-2"
                                        >
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
                            </CardContent>
                        </Card>

                        <!-- Inventory Information -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Thông tin tồn kho</CardTitle>
                                <CardDescription>
                                    Tình trạng hàng hóa tại vị trí này
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="flex items-center space-x-4">
                                    <div
                                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100"
                                    >
                                        <Package
                                            class="h-6 w-6 text-green-600"
                                        />
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
                                        <strong>Thông tin:</strong> Vị trí này
                                        hiện không có hàng tồn kho. Bạn có thể
                                        nhập hàng vào vị trí này thông qua chức
                                        năng nhập kho.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Actions -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Thao tác</CardTitle>
                                <CardDescription>
                                    Các hành động có thể thực hiện với vị trí
                                    này
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                >
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
                                                <strong>Lưu ý:</strong> Đây là
                                                vị trí mặc định của kho. Bạn
                                                không thể xóa vị trí này trực
                                                tiếp. Để xóa, hãy đặt vị trí
                                                khác làm mặc định trước.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Timeline -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Thông tin thời gian</CardTitle>
                                <CardDescription>
                                    Lịch sử tạo và cập nhật
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-start space-x-3">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100"
                                    >
                                        <Calendar
                                            class="h-4 w-4 text-green-600"
                                        />
                                    </div>
                                    <div>
                                        <p
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            Tạo lúc
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{
                                                formatDate(location.created_at)
                                            }}
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
                                            {{
                                                formatDate(location.updated_at)
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Quick Stats -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Thống kê nhanh</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
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
                                    <span class="text-sm text-gray-500"
                                        >Kho</span
                                    >
                                    <span class="text-sm font-medium">{{
                                        warehouse.code
                                    }}</span>
                                </div>
                            </CardContent>
                        </Card>
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
