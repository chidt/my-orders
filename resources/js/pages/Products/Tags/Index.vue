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
import { Edit, Plus, Search, Tag, Trash2, TrendingUp } from 'lucide-vue-next';
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
const tagToDelete = ref<Tag | null>(null);
const searchQuery = ref(props.filters.search || '');
const selectedUsage = ref(props.filters.usage || 'all');
const selectedSort = ref(props.filters.sort_by || 'name');

// Computed
const canManageTags = computed(() => can('manage_tags') || can('create_tags'));

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

            <!-- Filters -->
            <div class="mb-8 rounded-lg border border-gray-200 bg-white p-4">
                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <div>
                        <Input
                            v-model="searchQuery"
                            placeholder="Tìm kiếm thẻ..."
                            @keyup.enter="performSearch"
                        >
                            <template #prefix>
                                <Search class="h-4 w-4 text-gray-400" />
                            </template>
                        </Input>
                    </div>
                    <div>
                        <Select v-model="selectedUsage">
                            <SelectTrigger>
                                <SelectValue placeholder="Trạng thái sử dụng" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tất cả</SelectItem>
                                <SelectItem value="used"
                                    >Đang sử dụng</SelectItem
                                >
                                <SelectItem value="unused"
                                    >Chưa sử dụng</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Select v-model="selectedSort">
                            <SelectTrigger>
                                <SelectValue placeholder="Sắp xếp theo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="name">Tên</SelectItem>
                                <SelectItem value="products_count"
                                    >Số lần sử dụng</SelectItem
                                >
                                <SelectItem value="created_at"
                                    >Ngày tạo</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="flex gap-2">
                        <Button @click="performSearch" class="flex-1">
                            Lọc
                        </Button>
                        <Button
                            @click="clearFilters"
                            variant="outline"
                            class="flex-1"
                        >
                            Xóa bộ lọc
                        </Button>
                    </div>
                </div>
            </div>

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
                v-else
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
