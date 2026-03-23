<script setup lang="ts">
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
import { Head, Link, router } from '@inertiajs/vue3';
import { Edit, MapPin, Plus, Star, Trash2 } from 'lucide-vue-next';
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
    <Head>
        <title>Quản lý vị trí - {{ warehouse.name }} - {{ site.name }}</title>
    </Head>

    <AppLayout>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
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
                            <li aria-current="page">
                                <div class="flex items-center">
                                    <span class="mx-2 text-gray-400">/</span>
                                    <span
                                        class="text-sm font-medium text-gray-500"
                                        >Vị trí</span
                                    >
                                </div>
                            </li>
                        </ol>
                    </nav>

                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">
                                Quản lý vị trí
                            </h1>
                            <p class="mt-2 text-gray-600">
                                {{ warehouse.name }} - {{ warehouse.code }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ warehouse.address }}
                            </p>
                        </div>

                        <Button
                            v-if="canCreateLocation"
                            @click="goToCreateLocation"
                            class="flex items-center gap-2"
                        >
                            <Plus class="h-4 w-4" />
                            Thêm vị trí
                        </Button>
                    </div>
                </div>

                <!-- Content -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Stats -->
                        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="rounded-lg bg-blue-50 p-4">
                                <div class="flex items-center">
                                    <MapPin class="h-8 w-8 text-blue-600" />
                                    <div class="ml-3">
                                        <p
                                            class="text-sm font-medium text-blue-600"
                                        >
                                            Tổng vị trí
                                        </p>
                                        <p
                                            class="text-2xl font-bold text-blue-900"
                                        >
                                            {{ locations.total }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-lg bg-yellow-50 p-4">
                                <div class="flex items-center">
                                    <Star class="h-8 w-8 text-yellow-600" />
                                    <div class="ml-3">
                                        <p
                                            class="text-sm font-medium text-yellow-600"
                                        >
                                            Vị trí mặc định
                                        </p>
                                        <p
                                            class="text-2xl font-bold text-yellow-900"
                                        >
                                            {{
                                                locations.data.filter(
                                                    (l) => l.is_default,
                                                ).length
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-lg bg-green-50 p-4">
                                <div class="flex items-center">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-green-600"
                                    >
                                        <span
                                            class="text-sm font-bold text-white"
                                            >📦</span
                                        >
                                    </div>
                                    <div class="ml-3">
                                        <p
                                            class="text-sm font-medium text-green-600"
                                        >
                                            Tổng tồn kho
                                        </p>
                                        <p
                                            class="text-2xl font-bold text-green-900"
                                        >
                                            {{
                                                locations.data.reduce(
                                                    (sum, l) =>
                                                        sum + l.qty_in_stock,
                                                    0,
                                                )
                                            }}
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
                                class="rounded-lg border border-gray-200 p-4 transition-colors hover:bg-gray-50"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="shrink-0">
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100"
                                            >
                                                <MapPin
                                                    class="h-5 w-5 text-blue-600"
                                                />
                                            </div>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="flex items-center space-x-2"
                                            >
                                                <h3
                                                    class="text-lg font-semibold text-gray-900"
                                                >
                                                    {{ location.name }}
                                                </h3>
                                                <span
                                                    v-if="location.is_default"
                                                    class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800"
                                                >
                                                    <Star
                                                        class="mr-1 h-3 w-3"
                                                    />
                                                    Mặc định
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600">
                                                Mã: {{ location.code }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                Tồn kho:
                                                {{ location.qty_in_stock }} |
                                                Tạo:
                                                {{
                                                    formatDate(
                                                        location.created_at,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="
                                                goToShowLocation(location.id)
                                            "
                                        >
                                            Xem
                                        </Button>

                                        <Button
                                            v-if="canEditLocation"
                                            variant="outline"
                                            size="sm"
                                            @click="
                                                goToEditLocation(location.id)
                                            "
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
                            <MapPin
                                class="mx-auto mb-4 h-12 w-12 text-gray-400"
                            />
                            <h3 class="mb-2 text-lg font-medium text-gray-900">
                                Chưa có vị trí nào
                            </h3>
                            <p class="mb-6 text-gray-500">
                                Bắt đầu bằng cách tạo vị trí đầu tiên cho kho
                                {{ warehouse.name }}.
                            </p>
                            <Button
                                v-if="canCreateLocation"
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
                            class="mt-6"
                        >
                            <nav class="flex items-center justify-between">
                                <div
                                    class="flex flex-1 justify-between sm:hidden"
                                >
                                    <Link
                                        v-if="
                                            locations.current_page > 1 &&
                                            locations.links[0].url
                                        "
                                        :href="locations.links[0].url!"
                                        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Trước
                                    </Link>
                                    <Link
                                        v-if="
                                            locations.current_page <
                                                locations.last_page &&
                                            locations.links[
                                                locations.links.length - 1
                                            ].url
                                        "
                                        :href="
                                            locations.links[
                                                locations.links.length - 1
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
                                            Hiển thị từ
                                            <span class="font-medium">{{
                                                (locations.current_page - 1) *
                                                    locations.per_page +
                                                1
                                            }}</span>
                                            đến
                                            <span class="font-medium">{{
                                                Math.min(
                                                    locations.current_page *
                                                        locations.per_page,
                                                    locations.total,
                                                )
                                            }}</span>
                                            trong tổng số
                                            <span class="font-medium">{{
                                                locations.total
                                            }}</span>
                                            vị trí
                                        </p>
                                    </div>
                                    <div>
                                        <nav
                                            class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm"
                                        >
                                            <template
                                                v-for="link in locations.links"
                                                :key="link.label"
                                            >
                                                <Link
                                                    v-if="link.url"
                                                    :href="link.url!"
                                                    :class="[
                                                        'relative inline-flex items-center border px-4 py-2 text-sm font-medium',
                                                        link.active
                                                            ? 'z-10 border-blue-500 bg-blue-50 text-blue-600'
                                                            : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50',
                                                    ]"
                                                    :aria-current="
                                                        link.active
                                                            ? 'page'
                                                            : undefined
                                                    "
                                                >
                                                    {{ link.label }}
                                                </Link>
                                                <span
                                                    v-else
                                                    :class="[
                                                        'relative inline-flex cursor-not-allowed items-center border px-4 py-2 text-sm font-medium opacity-50',
                                                        link.active
                                                            ? 'z-10 border-blue-500 bg-blue-50 text-blue-600'
                                                            : 'border-gray-300 bg-white text-gray-500',
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
