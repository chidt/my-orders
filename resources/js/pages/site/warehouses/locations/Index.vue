<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Edit, MapPin, Plus, Search, Star, Trash2, X } from 'lucide-vue-next';
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
        current: true,
    },
];

// Search functionality
const search = ref('');

const filteredLocations = computed(() => {
    if (!search.value) return props.locations.data;

    return props.locations.data.filter(
        (location) =>
            location.name.toLowerCase().includes(search.value.toLowerCase()) ||
            location.code.toLowerCase().includes(search.value.toLowerCase()),
    );
});

const clearSearch = () => {
    search.value = '';
};

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
        },
    );
}

// Navigation functions
function goToCreateLocation() {
    router.visit(
        siteRoute.warehouses.locations.create.url([
            props.site.slug,
            props.warehouse.id,
        ]),
    );
}

function goToEditLocation(locationId: number) {
    router.visit(
        siteRoute.warehouses.locations.edit.url([
            props.site.slug,
            props.warehouse.id,
            locationId,
        ]),
    );
}

function goToShowLocation(locationId: number) {
    router.visit(
        siteRoute.warehouses.locations.show.url([
            props.site.slug,
            props.warehouse.id,
            locationId,
        ]),
    );
}

// Utility functions
function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('vi-VN');
}
</script>

<template>
    <Head :title="`Quản lý vị trí - ${warehouse.name} - ${site.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header with Title and Search -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">
                        Quản lý vị trí
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ warehouse.name }} - {{ warehouse.code }}
                    </p>
                    <p class="text-xs text-gray-400">{{ warehouse.address }}</p>
                </div>
                <div class="w-full sm:w-80">
                    <div class="relative">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                        />
                        <Input
                            v-model="search"
                            placeholder="Tìm kiếm theo tên hoặc mã vị trí..."
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

            <!-- Action Section -->
            <div
                v-if="canCreateLocation"
                class="flex items-center justify-between"
            >
                <div></div>
                <Button
                    @click="goToCreateLocation"
                    class="flex items-center gap-2"
                >
                    <Plus class="h-4 w-4" />
                    Thêm vị trí
                </Button>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-lg border bg-white p-4">
                    <div class="flex items-center">
                        <MapPin class="h-8 w-8 text-blue-600" />
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-600">
                                Tổng vị trí
                            </p>
                            <p class="text-2xl font-bold text-blue-900">
                                {{ locations.total }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-white p-4">
                    <div class="flex items-center">
                        <Star class="h-8 w-8 text-yellow-600" />
                        <div class="ml-3">
                            <p class="text-sm font-medium text-yellow-600">
                                Vị trí mặc định
                            </p>
                            <p class="text-2xl font-bold text-yellow-900">
                                {{
                                    locations.data.filter((l) => l.is_default)
                                        .length
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-white p-4">
                    <div class="flex items-center">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-green-600"
                        >
                            <span class="text-sm font-bold text-white">📦</span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-600">
                                Tổng tồn kho
                            </p>
                            <p class="text-2xl font-bold text-green-900">
                                {{
                                    locations.data.reduce(
                                        (sum, l) => sum + l.qty_in_stock,
                                        0,
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Locations List -->
            <div class="overflow-hidden rounded-lg border bg-white shadow-sm">
                <div
                    v-if="filteredLocations.length > 0"
                    class="divide-y divide-gray-200"
                >
                    <div
                        v-for="location in filteredLocations"
                        :key="location.id"
                        class="p-4 transition-colors hover:bg-gray-50"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="shrink-0">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100"
                                    >
                                        <MapPin class="h-5 w-5 text-blue-600" />
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center space-x-2">
                                        <h3
                                            class="text-lg font-semibold text-gray-900"
                                        >
                                            {{ location.name }}
                                        </h3>
                                        <span
                                            v-if="location.is_default"
                                            class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800"
                                        >
                                            <Star class="mr-1 h-3 w-3" />
                                            Mặc định
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        Mã: {{ location.code }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Tồn kho: {{ location.qty_in_stock }} |
                                        Tạo:
                                        {{ formatDate(location.created_at) }}
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
                                    <Edit class="h-4 w-4" />
                                </Button>

                                <Button
                                    v-if="
                                        canDeleteLocation &&
                                        !location.is_default
                                    "
                                    variant="outline"
                                    size="sm"
                                    @click="openDeleteDialog(location)"
                                    class="text-red-600 hover:text-red-700"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="py-12 text-center">
                    <MapPin class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                    <h3 class="mb-2 text-lg font-medium text-gray-900">
                        {{
                            search
                                ? 'Không tìm thấy vị trí nào'
                                : 'Chưa có vị trí nào'
                        }}
                    </h3>
                    <p class="mb-6 text-gray-500">
                        {{
                            search
                                ? 'Thử tìm kiếm với từ khóa khác'
                                : `Bắt đầu bằng cách tạo vị trí đầu tiên cho kho ${warehouse.name}.`
                        }}
                    </p>
                    <Button
                        v-if="canCreateLocation && !search"
                        @click="goToCreateLocation"
                        class="mx-auto flex items-center gap-2"
                    >
                        <Plus class="h-4 w-4" />
                        Tạo vị trí đầu tiên
                    </Button>
                </div>

                <!-- Pagination -->
                <div
                    v-if="locations.total > locations.per_page"
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
                                (locations.current_page - 1) *
                                    locations.per_page +
                                1
                            }}</span>
                            đến
                            <span class="font-bold text-gray-900">{{
                                Math.min(
                                    locations.current_page * locations.per_page,
                                    locations.total,
                                )
                            }}</span>
                            trong tổng số
                            <span class="font-bold text-gray-900">{{
                                locations.total
                            }}</span>
                            vị trí
                        </div>
                        <nav
                            class="order-1 inline-flex -space-x-px overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm sm:order-2"
                        >
                            <template
                                v-for="(link, index) in locations.links"
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

        <!-- Delete Dialog -->
        <Dialog :open="deleteDialog.open" @update:open="closeDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Xóa vị trí</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc chắn muốn xóa vị trí "{{
                            deleteDialog.location?.name
                        }}" không? Hành động này không thể hoàn tác.
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
