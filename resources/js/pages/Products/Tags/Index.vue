<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
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
import TagsRoutes from '@/routes/tags';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Edit,
    Filter,
    Plus,
    Search,
    Tag,
    Trash2,
    TrendingUp,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Site {
    id: number;
    name: string;
    slug: string;
}

interface Tag {
    id: number;
    name: string;
    slug: string;
    products_count: number;
    created_at: string;
    updated_at: string;
}

interface PaginatedTags {
    data: Tag[];
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

interface Statistics {
    total: number;
    used: number;
    unused: number;
}

interface PopularTag {
    id: number;
    name: string;
    products_count: number;
}

interface Filters {
    search?: string;
    usage?: string;
    sort_by?: string;
    sort_direction?: string;
}

interface Props {
    site?: Site;
    tags: PaginatedTags;
    statistics: Statistics;
    popularTags: PopularTag[];
    filters: Filters;
}

const props = defineProps<Props>();

const { can } = usePermissions();

// State
const showDeleteDialog = ref(false);
const showBulkDeleteDialog = ref(false);
const showFilterModal = ref(false);
const tagToDelete = ref<Tag | null>(null);
const searchQuery = ref(props.filters.search || '');
const selectedUsage = ref(props.filters.usage || 'all');
const selectedSort = ref(props.filters.sort_by || 'name');

// Computed
const canManageTags = computed(() => can('manage_tags') || can('create_tags'));

const activeFiltersCount = computed(() => {
    let count = 0;
    if (searchQuery.value.trim()) count++;
    if (selectedUsage.value && selectedUsage.value !== 'all') count++;
    if (selectedSort.value && selectedSort.value !== 'name') count++;
    return count;
});

const hasActiveFilters = computed(() => activeFiltersCount.value > 0);

// Methods
const confirmDelete = (tag: Tag) => {
    tagToDelete.value = tag;
    showDeleteDialog.value = true;
};

const deleteTag = () => {
    if (tagToDelete.value && props.site?.slug) {
        router.delete(
            TagsRoutes.destroy.url({
                site: props.site.slug,
                tag: tagToDelete.value.id,
            }),
            {
                onSuccess: () => {
                    showDeleteDialog.value = false;
                    tagToDelete.value = null;
                },
            },
        );
    }
};

const confirmBulkDeleteUnused = () => {
    showBulkDeleteDialog.value = true;
};

const bulkDeleteUnused = () => {
    if (props.site?.slug) {
        router.delete(`/${props.site.slug}/tags/bulk-delete-unused`, {
            onSuccess: () => {
                showBulkDeleteDialog.value = false;
            },
        });
    }
};

const performSearch = () => {
    if (!props.site?.slug) return;

    const params = new URLSearchParams();
    if (searchQuery.value) {
        params.set('search', searchQuery.value);
    }
    if (selectedUsage.value && selectedUsage.value !== 'all') {
        params.set('usage', selectedUsage.value);
    }
    if (selectedSort.value) {
        params.set('sort_by', selectedSort.value);
    }

    router.get(
        TagsRoutes.index.url({ site: props.site.slug }) +
            '?' +
            params.toString(),
        {},
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedUsage.value = 'all';
    selectedSort.value = 'name';
    performSearch();
};

const closeFilterModal = () => {
    showFilterModal.value = false;
};

const applyAdvancedFilters = () => {
    performSearch();
    closeFilterModal();
};

const getUsageBadgeVariant = (count: number) => {
    if (count === 0) return 'secondary';
    if (count < 5) return 'outline';
    return 'default';
};
</script>

<template>
    <AppLayout>
        <Head title="Quản lý thẻ" />

        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 sm:flex sm:items-center sm:justify-between">
                <div class="mb-4 sm:mb-0">
                    <h1 class="text-2xl font-bold text-gray-900">
                        Quản lý thẻ
                    </h1>
                    <p class="mt-2 text-sm text-gray-700">
                        Quản lý thẻ cho sản phẩm của bạn
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Button
                        v-if="canManageTags && statistics.unused > 0"
                        @click="confirmBulkDeleteUnused"
                        variant="outline"
                        class="w-full sm:w-auto"
                    >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Xóa thẻ không dùng ({{ statistics.unused }})
                    </Button>

                    <Button
                        v-if="canManageTags && props.site?.slug"
                        :as="Link"
                        :href="TagsRoutes.create.url({ site: props.site.slug })"
                        class="flex w-full items-center justify-center gap-2 sm:w-auto"
                    >
                        <Plus class="h-4 w-4" />
                        Thêm thẻ
                    </Button>
                </div>
            </div>

            <!-- Statistics -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <Tag class="h-6 w-6 text-gray-400" />
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt
                                        class="truncate text-sm font-medium text-gray-500"
                                    >
                                        Tổng thẻ
                                    </dt>
                                    <dd
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        {{ statistics.total }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <TrendingUp class="h-6 w-6 text-green-400" />
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt
                                        class="truncate text-sm font-medium text-gray-500"
                                    >
                                        Đang sử dụng
                                    </dt>
                                    <dd
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        {{ statistics.used }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <Trash2 class="h-6 w-6 text-red-400" />
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt
                                        class="truncate text-sm font-medium text-gray-500"
                                    >
                                        Không sử dụng
                                    </dt>
                                    <dd
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        {{ statistics.unused }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Popular Tags -->
            <div
                v-if="popularTags.length > 0"
                class="mb-8 rounded-lg border border-gray-200 bg-white p-6"
            >
                <h3 class="mb-4 text-lg font-medium text-gray-900">
                    Thẻ phổ biến
                </h3>
                <div class="flex flex-wrap gap-2">
                    <Badge
                        v-for="tag in popularTags"
                        :key="tag.id"
                        variant="secondary"
                        class="px-3 py-1 text-sm"
                    >
                        {{ tag.name }}
                        <span class="ml-2 text-xs text-gray-500">{{
                            tag.products_count
                        }}</span>
                    </Badge>
                </div>
            </div>

            <!-- Search + Filter Bar -->
            <div
                class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div
                    class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:gap-2"
                >
                    <div class="relative flex-1 sm:w-80">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                        />
                        <Input
                            v-model="searchQuery"
                            placeholder="Tìm kiếm thẻ..."
                            class="h-11 pr-10 pl-9"
                            @keyup.enter="performSearch"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            @click="
                                searchQuery = '';
                                performSearch();
                            "
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
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
                    </div>
                </div>
            </div>

            <!-- Advanced Filters Modal -->
            <Transition
                enter-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showFilterModal"
                    class="fixed inset-0 z-50 size-auto max-h-none max-w-none overflow-hidden bg-transparent"
                >
                    <div
                        class="absolute inset-0 cursor-pointer bg-gray-500/75"
                        @click="closeFilterModal"
                    ></div>

                    <div
                        class="pointer-events-none absolute inset-0 pl-0 focus:outline-none sm:pl-10 lg:pl-16"
                    >
                        <Transition
                            enter-active-class="transition-transform duration-500 ease-in-out sm:duration-700"
                            enter-from-class="translate-x-full"
                            enter-to-class="translate-x-0"
                            leave-active-class="transition-transform duration-500 ease-in-out sm:duration-700"
                            leave-from-class="translate-x-0"
                            leave-to-class="translate-x-full"
                        >
                            <div
                                v-if="showFilterModal"
                                class="pointer-events-auto relative ml-auto block size-full w-full sm:max-w-md"
                            >
                                <div
                                    class="relative flex h-full flex-col overflow-y-auto bg-white py-6 shadow-xl"
                                >
                                    <div class="px-4 sm:px-6">
                                        <div
                                            class="mb-2 flex items-center justify-between sm:mb-0"
                                        >
                                            <div class="flex-1">
                                                <h2
                                                    class="text-xl font-bold text-gray-900"
                                                >
                                                    Bộ lọc nâng cao
                                                </h2>
                                                <p
                                                    class="mt-0.5 text-xs text-gray-500"
                                                >
                                                    Thiết lập tiêu chí tìm kiếm
                                                    chi tiết
                                                </p>
                                            </div>
                                            <button
                                                type="button"
                                                @click="closeFilterModal"
                                                class="rounded-md p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                                            >
                                                <span class="sr-only"
                                                    >Đóng panel</span
                                                >
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.5"
                                                    aria-hidden="true"
                                                    class="size-6"
                                                >
                                                    <path
                                                        d="M6 18 18 6M6 6l12 12"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div
                                        class="flex-1 space-y-8 overflow-y-auto p-6"
                                    >
                                        <div class="space-y-3">
                                            <label
                                                class="flex items-center gap-2 text-sm font-bold tracking-wider text-gray-700 uppercase"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full bg-indigo-500"
                                                ></span>
                                                Trạng thái sử dụng
                                            </label>
                                            <Select v-model="selectedUsage">
                                                <SelectTrigger
                                                    class="h-12 w-full bg-gray-50 transition-colors focus:bg-white"
                                                >
                                                    <SelectValue
                                                        placeholder="Chọn trạng thái"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all"
                                                        >Tất cả</SelectItem
                                                    >
                                                    <SelectItem value="used"
                                                        >Đang sử
                                                        dụng</SelectItem
                                                    >
                                                    <SelectItem value="unused"
                                                        >Chưa sử
                                                        dụng</SelectItem
                                                    >
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="space-y-3">
                                            <label
                                                class="flex items-center gap-2 text-sm font-bold tracking-wider text-gray-700 uppercase"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full bg-indigo-500"
                                                ></span>
                                                Sắp xếp
                                            </label>
                                            <Select v-model="selectedSort">
                                                <SelectTrigger
                                                    class="h-12 w-full bg-gray-50 transition-colors focus:bg-white"
                                                >
                                                    <SelectValue
                                                        placeholder="Chọn trường sắp xếp"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="name"
                                                        >Tên</SelectItem
                                                    >
                                                    <SelectItem
                                                        value="products_count"
                                                        >Số lần sử
                                                        dụng</SelectItem
                                                    >
                                                    <SelectItem
                                                        value="created_at"
                                                        >Ngày tạo</SelectItem
                                                    >
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>

                                    <div
                                        class="flex flex-shrink-0 items-center justify-between border-t border-gray-100 bg-gray-50 px-4 py-4"
                                    >
                                        <button
                                            type="button"
                                            class="text-sm font-medium text-gray-500 underline hover:text-gray-700"
                                            @click="clearFilters"
                                        >
                                            Xóa tất cả
                                        </button>

                                        <div class="flex gap-2">
                                            <Button
                                                variant="outline"
                                                @click="closeFilterModal"
                                                >Hủy</Button
                                            >
                                            <Button
                                                @click="applyAdvancedFilters"
                                                class="px-8 shadow-sm"
                                                >Áp dụng</Button
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>

            <!-- Summary -->
            <div class="mb-6">
                <p class="text-sm text-gray-600">
                    Hiển thị
                    <span class="font-medium">{{ tags.data.length }}</span>
                    trong tổng số
                    <span class="font-medium">{{ tags.total }}</span> thẻ
                </p>
            </div>

            <!-- Content -->
            <div
                v-if="tags.data.length === 0"
                class="rounded-lg border border-gray-200 bg-white py-12 text-center"
            >
                <Tag class="mx-auto h-16 w-16 text-gray-300" />
                <h3 class="mt-4 text-lg font-medium text-gray-900">
                    Chưa có thẻ nào
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                    Bắt đầu bằng cách tạo thẻ đầu tiên.
                </p>
                <div class="mt-6">
                    <Button
                        v-if="canManageTags && props.site?.slug"
                        :as="Link"
                        :href="TagsRoutes.create.url({ site: props.site.slug })"
                        class="flex items-center gap-2"
                    >
                        <Plus class="h-4 w-4" />
                        Thêm thẻ
                    </Button>
                </div>
            </div>

            <!-- Desktop Table -->
            <div
                v-if="tags.data.length > 0"
                class="hidden overflow-hidden rounded-lg border border-gray-200 bg-white md:block"
            >
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Tên thẻ
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Số lần sử dụng
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Ngày tạo
                            </th>
                            <th
                                v-if="canManageTags"
                                scope="col"
                                class="px-6 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Thao tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr
                            v-for="tag in tags.data"
                            :key="tag.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <Tag
                                        class="mr-3 h-4 w-4 flex-shrink-0 text-gray-400"
                                    />
                                    <div>
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ tag.name }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ tag.slug }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <Badge
                                    :variant="
                                        getUsageBadgeVariant(tag.products_count)
                                    "
                                >
                                    {{ tag.products_count }}
                                </Badge>
                            </td>

                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-gray-900"
                            >
                                {{
                                    new Date(tag.created_at).toLocaleDateString(
                                        'vi-VN',
                                    )
                                }}
                            </td>

                            <td
                                v-if="canManageTags"
                                class="px-6 py-4 text-center whitespace-nowrap"
                            >
                                <div class="flex justify-center gap-2">
                                    <Button
                                        v-if="props.site?.slug"
                                        :as="Link"
                                        :href="
                                            TagsRoutes.edit.url({
                                                site: props.site.slug,
                                                tag: tag.id,
                                            })
                                        "
                                        variant="ghost"
                                        size="sm"
                                        class="p-2"
                                    >
                                        <Edit class="h-4 w-4" />
                                    </Button>

                                    <Button
                                        @click="confirmDelete(tag)"
                                        variant="ghost"
                                        size="sm"
                                        class="p-2 text-red-600 hover:text-red-700"
                                        :disabled="tag.products_count > 0"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile List -->
            <div v-if="tags.data.length > 0" class="space-y-3 md:hidden">
                <div
                    v-for="tag in tags.data"
                    :key="tag.id"
                    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <!-- Header -->
                    <div class="mb-3 flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center">
                                <Tag
                                    class="mr-2 h-4 w-4 shrink-0 text-gray-400"
                                />
                                <h3
                                    class="truncate text-lg font-medium text-gray-900"
                                >
                                    {{ tag.name }}
                                </h3>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ tag.slug }}
                            </p>
                        </div>
                        <div v-if="canManageTags" class="ml-3 flex gap-1">
                            <Button
                                v-if="props.site?.slug"
                                :as="Link"
                                :href="
                                    TagsRoutes.edit.url({
                                        site: props.site.slug,
                                        tag: tag.id,
                                    })
                                "
                                variant="ghost"
                                size="sm"
                                class="h-8 w-8 p-2"
                            >
                                <Edit class="h-4 w-4" />
                            </Button>

                            <Button
                                @click="confirmDelete(tag)"
                                variant="ghost"
                                size="sm"
                                class="h-8 w-8 p-2 text-red-600 hover:text-red-700"
                                :disabled="tag.products_count > 0"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-500"
                                >Số lần sử dụng:</span
                            >
                            <div>
                                <Badge
                                    :variant="
                                        getUsageBadgeVariant(tag.products_count)
                                    "
                                    class="text-sm"
                                >
                                    {{ tag.products_count }}
                                </Badge>
                            </div>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Ngày tạo:</span>
                            <div class="text-sm text-gray-900">
                                {{
                                    new Date(tag.created_at).toLocaleDateString(
                                        'vi-VN',
                                    )
                                }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="tags.last_page > 1" class="mt-8 flex justify-center">
                <div class="flex flex-wrap justify-center gap-1">
                    <template v-for="link in tags.links" :key="link.label">
                        <Button
                            v-if="link.url"
                            :as="Link"
                            :href="link.url"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            class="h-9 min-w-10"
                        >
                            <span v-html="link.label"></span>
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            size="sm"
                            disabled
                            class="h-9 min-w-10"
                        >
                            <span v-html="link.label"></span>
                        </Button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Xác nhận xóa</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc chắn muốn xóa thẻ "{{ tagToDelete?.name }}"?
                        <br />
                        Hành động này không thể hoàn tác.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">
                        Hủy
                    </Button>
                    <Button variant="destructive" @click="deleteTag">
                        Xóa
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Bulk Delete Confirmation Dialog -->
        <Dialog v-model:open="showBulkDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Xác nhận xóa hàng loạt</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc chắn muốn xóa tất cả
                        {{ statistics.unused }} thẻ không được sử dụng?
                        <br />
                        Hành động này không thể hoàn tác.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        @click="showBulkDeleteDialog = false"
                    >
                        Hủy
                    </Button>
                    <Button variant="destructive" @click="bulkDeleteUnused">
                        Xóa tất cả
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
