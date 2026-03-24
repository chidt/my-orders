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
import type { AttributeListProps } from '@/types/attribute';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Code,
    Edit,
    Hash,
    Layers,
    Plus,
    Search,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<AttributeListProps>();
const { can } = usePermissions();

const breadcrumbs = [
    {
        title: props.site.name,
        href: siteRoute.dashboard.url(props.site.slug),
        current: false,
    },
    {
        title: 'Thuộc tính sản phẩm',
        href: siteRoute.attributes.index.url(props.site.slug),
        current: true,
    },
];

const search = ref(props.filters.search ?? '');
const sortBy = ref(props.filters.sort_by ?? 'order');
const isDeleting = ref<number | null>(null);
const showDeleteDialog = ref(false);
const attributeToDelete = ref<
    null | AttributeListProps['attributes']['data'][number]
>(null);

const showSummary = computed(() => props.attributes.total > 0);

const applyFilters = () => {
    router.get(
        siteRoute.attributes.index.url(props.site.slug),
        {
            search: search.value || undefined,
            sort_by: sortBy.value || 'order',
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
    sortBy.value = 'order';
    applyFilters();
};

const openDeleteDialog = (
    attribute: AttributeListProps['attributes']['data'][number],
) => {
    attributeToDelete.value = attribute;
    showDeleteDialog.value = true;
};

const confirmDelete = () => {
    if (!attributeToDelete.value) return;

    isDeleting.value = attributeToDelete.value.id;
    showDeleteDialog.value = false;

    router.delete(
        siteRoute.attributes.destroy.url([
            props.site.slug,
            attributeToDelete.value.id,
        ]),
        {
            preserveScroll: true,
            onFinish: () => {
                isDeleting.value = null;
                attributeToDelete.value = null;
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
    <Head :title="`Thuộc tính sản phẩm - ${site.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-8 sm:flex sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Thuộc tính sản phẩm
                    </h1>
                    <p class="mt-2 text-sm text-gray-700">
                        Quản lý các thuộc tính sản phẩm cho {{ site.name }}
                    </p>
                </div>
                <Button
                    v-if="can('create_attributes')"
                    :as="Link"
                    :href="siteRoute.attributes.create.url(site.slug)"
                    class="mt-4 cursor-pointer sm:mt-0"
                >
                    <Plus class="mr-2 h-4 w-4" />
                    Thêm thuộc tính mới
                </Button>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-lg border bg-white p-5">
                    <p class="text-sm text-gray-500">Tổng thuộc tính</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ statistics.total }}
                    </p>
                </div>
                <div class="rounded-lg border bg-white p-5 md:col-span-2">
                    <p class="text-sm text-gray-500">Các thuộc tính sản phẩm</p>
                    <p class="mt-1 text-sm text-gray-700">
                        Quản lý thuộc tính như Kích thước, Màu sắc, Chất liệu...
                        dùng để tạo biến thể sản phẩm.
                    </p>
                </div>
            </div>

            <div class="mb-6 rounded-lg border bg-white p-4">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <Input
                        v-model="search"
                        placeholder="Tìm theo tên hoặc mã thuộc tính..."
                        @keyup.enter="applyFilters"
                    >
                        <template #prefix>
                            <Search class="h-4 w-4 text-gray-400" />
                        </template>
                    </Input>
                    <Select v-model="sortBy">
                        <SelectTrigger
                            ><SelectValue placeholder="Sắp xếp theo"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="order">Thứ tự</SelectItem>
                            <SelectItem value="name">Tên</SelectItem>
                            <SelectItem value="code">Mã</SelectItem>
                            <SelectItem value="product_attribute_values_count"
                                >Số giá trị</SelectItem
                            >
                            <SelectItem value="created_at">Ngày tạo</SelectItem>
                        </SelectContent>
                    </Select>
                    <div class="flex gap-2">
                        <Button
                            class="flex-1 cursor-pointer"
                            @click="applyFilters"
                            >Lọc</Button
                        >
                        <Button
                            class="flex-1 cursor-pointer"
                            variant="outline"
                            @click="clearFilters"
                            >Xóa lọc</Button
                        >
                    </div>
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
                Hiển thị {{ attributes.data.length }} trong tổng số
                {{ attributes.total }} thuộc tính
            </div>

            <div class="overflow-hidden rounded-lg border bg-white">
                <div
                    v-if="attributes.data.length === 0"
                    class="py-12 text-center"
                >
                    <Layers class="mx-auto mb-4 h-12 w-12 text-gray-300" />
                    <h3 class="text-lg font-medium text-gray-900">
                        Chưa có thuộc tính nào
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Bắt đầu bằng cách thêm thuộc tính đầu tiên (VD: Kích
                        thước, Màu sắc).
                    </p>
                    <div v-if="can('create_attributes')" class="mt-4">
                        <Button
                            :as="Link"
                            :href="siteRoute.attributes.create.url(site.slug)"
                            class="cursor-pointer"
                        >
                            <Plus class="mr-2 h-4 w-4" />Thêm thuộc tính đầu
                            tiên
                        </Button>
                    </div>
                </div>

                <div v-else>
                    <div class="hidden overflow-x-auto md:block">
                        <table
                            class="w-full min-w-[800px] divide-y divide-gray-200"
                        >
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap text-gray-500 uppercase"
                                    >
                                        Tên thuộc tính
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap text-gray-500 uppercase"
                                    >
                                        Mã (Code)
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap text-gray-500 uppercase"
                                    >
                                        Mô tả
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap text-gray-500 uppercase"
                                    >
                                        Thứ tự
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap text-gray-500 uppercase"
                                    >
                                        Số giá trị
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
                                    v-for="attribute in attributes.data"
                                    :key="attribute.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ attribute.name }}
                                        </div>
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm whitespace-nowrap text-gray-700"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <Code
                                                class="h-3.5 w-3.5 text-gray-400"
                                            />
                                            <code
                                                class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-700"
                                                >{{ attribute.code }}</code
                                            >
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <span
                                            v-if="attribute.description"
                                            class="line-clamp-1"
                                            >{{ attribute.description }}</span
                                        >
                                        <span v-else class="text-gray-400"
                                            >-</span
                                        >
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm whitespace-nowrap text-gray-700"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <Hash
                                                class="h-3.5 w-3.5 text-gray-400"
                                            />
                                            <span>{{ attribute.order }}</span>
                                        </div>
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm whitespace-nowrap text-gray-700"
                                    >
                                        {{
                                            (attribute as any)
                                                .product_attribute_values_count ??
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
                                                v-if="can('edit_attributes')"
                                                :as="Link"
                                                :href="
                                                    siteRoute.attributes.edit.url(
                                                        [
                                                            site.slug,
                                                            attribute.id,
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
                                                v-if="can('delete_attributes')"
                                                type="button"
                                                class="inline-flex cursor-pointer items-center rounded p-1 text-red-600 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                                :disabled="
                                                    isDeleting ===
                                                        attribute.id ||
                                                    ((attribute as any)
                                                        .product_attribute_values_count ??
                                                        0) > 0
                                                "
                                                @click="
                                                    openDeleteDialog(attribute)
                                                "
                                                :title="
                                                    ((attribute as any)
                                                        .product_attribute_values_count ??
                                                        0) > 0
                                                        ? 'Thuộc tính đang được sử dụng, không thể xóa'
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
                            v-for="attribute in attributes.data"
                            :key="`mobile-${attribute.id}`"
                            class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
                        >
                            <div
                                class="mb-3 flex items-start justify-between gap-3"
                            >
                                <div class="min-w-0 flex-1">
                                    <h3
                                        class="truncate text-lg font-semibold text-gray-900"
                                    >
                                        {{ attribute.name }}
                                    </h3>
                                    <div class="mt-1 flex items-center gap-1.5">
                                        <Code
                                            class="h-3.5 w-3.5 text-gray-400"
                                        />
                                        <code
                                            class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-700"
                                            >{{ attribute.code }}</code
                                        >
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <Button
                                        v-if="can('edit_attributes')"
                                        :as="Link"
                                        :href="
                                            siteRoute.attributes.edit.url([
                                                site.slug,
                                                attribute.id,
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
                                        v-if="can('delete_attributes')"
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded text-red-600 hover:bg-red-50 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="
                                            isDeleting === attribute.id ||
                                            ((attribute as any)
                                                .product_attribute_values_count ??
                                                0) > 0
                                        "
                                        @click="openDeleteDialog(attribute)"
                                        :title="
                                            ((attribute as any)
                                                .product_attribute_values_count ??
                                                0) > 0
                                                ? 'Thuộc tính đang được sử dụng, không thể xóa'
                                                : 'Xóa'
                                        "
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-gray-500">Thứ tự</p>
                                    <p class="font-medium text-gray-900">
                                        {{ attribute.order }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Số giá trị</p>
                                    <p class="font-medium text-gray-900">
                                        {{
                                            (attribute as any)
                                                .product_attribute_values_count ??
                                            0
                                        }}
                                    </p>
                                </div>
                                <div
                                    v-if="attribute.description"
                                    class="col-span-2"
                                >
                                    <p class="text-gray-500">Mô tả</p>
                                    <p class="font-medium text-gray-900">
                                        {{ attribute.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="attributes.last_page > 1"
                    class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
                >
                    <div class="flex flex-wrap justify-center gap-1">
                        <template
                            v-for="(link, index) in attributes.links"
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
                    <DialogTitle>Xác nhận xóa thuộc tính</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc chắn muốn xóa thuộc tính
                        <span class="font-semibold">{{
                            attributeToDelete?.name
                        }}</span
                        >?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
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
                        <span v-else>Xóa thuộc tính</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
