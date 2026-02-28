<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { MapPin, Plus, Edit, Trash2, Star } from 'lucide-vue-next';
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

interface PaginatedLocations {
    data: Location[];
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
    warehouse: Warehouse;
    locations: PaginatedLocations;
}

const props = defineProps<Props>();

const { can } = usePermissions();

// Computed properties
const canCreateLocation = computed(() => can('create_warehouse_locations'));
const canEditLocation = computed(() => can('edit_warehouse_locations'));
const canDeleteLocation = computed(() => can('delete_warehouse_locations'));

// Delete dialog state
const deleteDialog = ref({
    open: false,
    location: null as Location | null,
});

// Delete functions
function openDeleteDialog(location: Location) {
    deleteDialog.value = {
        open: true,
        location: location,
    };
}

function closeDeleteDialog() {
    deleteDialog.value = {
        open: false,
        location: null,
    };
}

function deleteLocation() {
    if (!deleteDialog.value.location) return;

    router.delete(
        siteRoute.warehouses.locations.destroy.url([
            props.site.slug,
            props.warehouse.id,
            deleteDialog.value.location.id,
        ]),
        {
            onSuccess: () => {
                closeDeleteDialog();
            },
        }
    );
}

// Navigation functions
function goToCreateLocation() {
    router.visit(
        siteRoute.warehouses.locations.create.url([
            props.site.slug,
            props.warehouse.id,
        ])
    );
}

function goToEditLocation(locationId: number) {
    router.visit(
        siteRoute.warehouses.locations.edit.url([
            props.site.slug,
            props.warehouse.id,
            locationId,
        ])
    );
}

function goToShowLocation(locationId: number) {
    router.visit(
        siteRoute.warehouses.locations.show.url([
            props.site.slug,
            props.warehouse.id,
            locationId,
        ])
    );
}

// Utility functions
function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('vi-VN');
}
</script>

<template>
    <Head>
        <title>Quản lý vị trí - {{ warehouse.name }} - {{ site.name }}</title>
    </Head>

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <nav class="flex mb-4" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
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
                                        :href="siteRoute.warehouses.index.url(site.slug)"
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
                                        :href="siteRoute.warehouses.show.url([site.slug, warehouse.id])"
                                        class="text-sm font-medium text-gray-700 hover:text-blue-600"
                                    >
                                        {{ warehouse.name }}
                                    </Link>
                                </div>
                            </li>
                            <li aria-current="page">
                                <div class="flex items-center">
                                    <span class="mx-2 text-gray-400">/</span>
                                    <span class="text-sm font-medium text-gray-500">Vị trí</span>
                                </div>
                            </li>
                        </ol>
                    </nav>

                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Quản lý vị trí</h1>
                            <p class="mt-2 text-gray-600">{{ warehouse.name }} - {{ warehouse.code }}</p>
                            <p class="text-sm text-gray-500">{{ warehouse.address }}</p>
                        </div>

                        <Button
                            v-if="canCreateLocation"
                            @click="goToCreateLocation"
                            class="flex items-center gap-2"
                        >
                            <Plus class="w-4 h-4" />
                            Thêm vị trí
                        </Button>
                    </div>
                </div>

                <!-- Content -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Stats -->
                        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <MapPin class="w-8 h-8 text-blue-600" />
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-blue-600">Tổng vị trí</p>
                                        <p class="text-2xl font-bold text-blue-900">{{ locations.total }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-yellow-50 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <Star class="w-8 h-8 text-yellow-600" />
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-yellow-600">Vị trí mặc định</p>
                                        <p class="text-2xl font-bold text-yellow-900">
                                            {{ locations.data.filter(l => l.is_default).length }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
                                        <span class="text-white font-bold text-sm">📦</span>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-green-600">Tổng tồn kho</p>
                                        <p class="text-2xl font-bold text-green-900">
                                            {{ locations.data.reduce((sum, l) => sum + l.qty_in_stock, 0) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Locations List -->
                        <div v-if="locations.data.length > 0" class="space-y-4">
                            <div
                                v-for="location in locations.data"
                                :key="location.id"
                                class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="shrink-0">
                                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <MapPin class="w-5 h-5 text-blue-600" />
                                            </div>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center space-x-2">
                                                <h3 class="text-lg font-semibold text-gray-900">
                                                    {{ location.name }}
                                                </h3>
                                                <span
                                                    v-if="location.is_default"
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                                                >
                                                    <Star class="w-3 h-3 mr-1" />
                                                    Mặc định
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600">Mã: {{ location.code }}</p>
                                            <p class="text-sm text-gray-500">
                                                Tồn kho: {{ location.qty_in_stock }} |
                                                Tạo: {{ formatDate(location.created_at) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="goToShowLocation(location.id)"
                                        >
                                            Xem
                                        </Button>

                                        <Button
                                            v-if="canEditLocation"
                                            variant="outline"
                                            size="sm"
                                            @click="goToEditLocation(location.id)"
                                        >
                                            <Edit class="w-4 h-4" />
                                        </Button>

                                        <Button
                                            v-if="canDeleteLocation && !location.is_default"
                                            variant="outline"
                                            size="sm"
                                            @click="openDeleteDialog(location)"
                                            class="text-red-600 hover:text-red-700"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <MapPin class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Chưa có vị trí nào</h3>
                            <p class="text-gray-500 mb-6">
                                Bắt đầu bằng cách tạo vị trí đầu tiên cho kho {{ warehouse.name }}.
                            </p>
                            <Button
                                v-if="canCreateLocation"
                                @click="goToCreateLocation"
                                class="flex items-center gap-2 mx-auto"
                            >
                                <Plus class="w-4 h-4" />
                                Tạo vị trí đầu tiên
                            </Button>
                        </div>

                        <!-- Pagination -->
                        <div v-if="locations.total > locations.per_page" class="mt-6">
                            <nav class="flex items-center justify-between">
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <Link
                                        v-if="locations.current_page > 1 && locations.links[0].url"
                                        :href="locations.links[0].url!"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Trước
                                    </Link>
                                    <Link
                                        v-if="locations.current_page < locations.last_page && locations.links[locations.links.length - 1].url"
                                        :href="locations.links[locations.links.length - 1].url!"
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Sau
                                    </Link>
                                </div>
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Hiển thị từ
                                            <span class="font-medium">{{ (locations.current_page - 1) * locations.per_page + 1 }}</span>
                                            đến
                                            <span class="font-medium">{{ Math.min(locations.current_page * locations.per_page, locations.total) }}</span>
                                            trong tổng số
                                            <span class="font-medium">{{ locations.total }}</span>
                                            vị trí
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                            <template
                                                v-for="link in locations.links"
                                                :key="link.label"
                                            >
                                                <Link
                                                    v-if="link.url"
                                                    :href="link.url!"
                                                    :class="[
                                                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                        link.active
                                                            ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                                    ]"
                                                    :aria-current="link.active ? 'page' : undefined"
                                                >
                                                    {{ link.label }}
                                                </Link>
                                                <span
                                                    v-else
                                                    :class="[
                                                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium cursor-not-allowed opacity-50',
                                                        link.active
                                                            ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                                            : 'bg-white border-gray-300 text-gray-500',
                                                    ]"
                                                >
                                                    {{ link.label }}
                                                </span>
                                            </template>
                                        </nav>
                                    </div>
                                </div>
                            </nav>
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
                        Bạn có chắc chắn muốn xóa vị trí "{{ deleteDialog.location?.name }}" không?
                        Hành động này không thể hoàn tác.
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
