<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Edit, Trash2, Eye, EyeOff, FolderTree, List, TreePine } from 'lucide-vue-next';
import { computed, ref, watch, onMounted } from 'vue';
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
import CategoryTree from '@/components/CategoryTree.vue';
import CategoriesRoutes from '@/routes/categories';

interface Site {
    id: number;
    name: string;
    slug: string;
}

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    order: number;
    is_active: boolean;
    parent_id: number | null;
    parent?: Category;
    products_count: number;
    depth: number;
    breadcrumb: string[];
    children?: Category[]; // Add children property for tree structure
    created_at: string;
    updated_at: string;
}

interface PaginatedCategories {
    data: Category[];
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

interface ParentCategory {
    id: number;
    name: string;
}

interface Filters {
    search?: string;
    parent_id?: string;
    is_active?: boolean;
    sort_by?: string;
    sort_direction?: string;
}

interface Props {
    site?: Site;
    categories: PaginatedCategories;
    parentCategories: ParentCategory[];
    filters: Filters;
}

const props = defineProps<Props>();

const { can } = usePermissions();

// State
const showDeleteDialog = ref(false);
const categoryToDelete = ref<Category | null>(null);
const searchQuery = ref(props.filters.search || '');
const selectedParent = ref(props.filters.parent_id || 'all');
const selectedStatus = ref(
    props.filters.is_active !== undefined
        ? props.filters.is_active
            ? 'true'
            : 'false'
        : 'all',
);
const viewMode = ref<'table' | 'tree'>('tree'); // Default to tree view
const allCategories = ref<Category[]>([]); // Store all categories for tree view
const loadingAllCategories = ref(false); // Loading state for fetching all categories

// Computed
const canManageCategories = computed(
    () => can('manage_categories') || can('create_categories'),
);

// Build tree structure from categories array
const categoriesTree = computed(() => {
    // Use all categories for tree view, paginated categories for table view
    const categoriesToProcess = viewMode.value === 'tree' && allCategories.value.length > 0
        ? allCategories.value
        : props.categories.data;

    if (!categoriesToProcess.length) return [];

    // Create a map for quick lookup and initialize children arrays
    const categoryMap = new Map<number, Category>();
    const tree: Category[] = [];

    // First pass: create map entries with children arrays
    categoriesToProcess.forEach(category => {
        categoryMap.set(category.id, { ...category, children: [] });
    });

    // Second pass: build the tree structure
    categoriesToProcess.forEach(category => {
        const node = categoryMap.get(category.id)!;

        if (category.parent_id && categoryMap.has(category.parent_id)) {
            // Add to parent's children array
            const parent = categoryMap.get(category.parent_id)!;
            parent.children!.push(node);
        } else {
            // Root level category
            tree.push(node);
        }
    });

    // Sort function for consistent ordering
    const sortCategories = (categories: Category[]) => {
        categories.sort((a, b) => {
            if (a.order !== b.order) return a.order - b.order;
            return a.name.localeCompare(b.name);
        });

        categories.forEach(category => {
            if (category.children && category.children.length > 0) {
                sortCategories(category.children);
            }
        });
    };

    sortCategories(tree);
    return tree;
});

// Fetch all categories for tree view
const fetchAllCategories = async () => {
    if (!props.site?.slug || loadingAllCategories.value) return;

    loadingAllCategories.value = true;

    try {
        // Build URL with current filters but without pagination
        const params = new URLSearchParams();
        params.set('all', 'true'); // Special parameter to get all categories

        if (searchQuery.value) {
            params.set('search', searchQuery.value);
        }
        if (selectedParent.value && selectedParent.value !== 'all') {
            params.set('parent_id', selectedParent.value);
        }
        if (selectedStatus.value && selectedStatus.value !== 'all') {
            params.set('is_active', selectedStatus.value);
        }

        // Make request to get all categories
        const response = await fetch(
            CategoriesRoutes.index.url({ site: props.site.slug }) + '?' + params.toString(),
            {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            }
        );

        if (response.ok) {
            const data = await response.json();
            allCategories.value = data.props?.categories?.data || data.categories?.data || [];
        }
    } catch (error) {
        console.error('Error fetching all categories:', error);
        // Fallback to using paginated data
        allCategories.value = props.categories.data;
    } finally {
        loadingAllCategories.value = false;
    }
};

// Methods
const confirmDelete = (category: Category) => {
    categoryToDelete.value = category;
    showDeleteDialog.value = true;
};

const deleteCategory = () => {
    if (categoryToDelete.value && props.site?.slug) {
        router.delete(
            CategoriesRoutes.destroy.url({
                site: props.site.slug,
                category: categoryToDelete.value.id,
            }),
            {
                onSuccess: () => {
                    showDeleteDialog.value = false;
                    categoryToDelete.value = null;
                },
            },
        );
    }
};

const performSearch = () => {
    if (!props.site?.slug) return;

    const params = new URLSearchParams();
    if (searchQuery.value) {
        params.set('search', searchQuery.value);
    }
    if (selectedParent.value && selectedParent.value !== 'all') {
        params.set('parent_id', selectedParent.value);
    }
    if (selectedStatus.value && selectedStatus.value !== 'all') {
        params.set('is_active', selectedStatus.value);
    }

    router.get(
        CategoriesRoutes.index.url({ site: props.site.slug }) +
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
    selectedParent.value = 'all';
    selectedStatus.value = 'all';
    performSearch();
};

const toggleActive = (category: Category) => {
    if (!props.site?.slug) return;

    router.put(
        CategoriesRoutes.update.url({
            site: props.site.slug,
            category: category.id,
        }),
        {
            name: category.name,
            description: category.description,
            order: category.order,
            is_active: !category.is_active,
            parent_id: category.parent_id,
        },
        {
            preserveScroll: true,
        },
    );
};

const getIndentClass = (depth: number) => {
    return `pl-${Math.min(depth * 4, 16)}`;
};

// Tree view handlers
const handleTreeEdit = (category: Category) => {
    if (props.site?.slug) {
        window.location.href = CategoriesRoutes.edit.url({
            site: props.site.slug,
            category: category.id,
        });
    }
};

const handleTreeDelete = (category: Category) => {
    confirmDelete(category);
};

const handleTreeAddChild = (parentCategory: Category) => {
    if (props.site?.slug) {
        window.location.href = CategoriesRoutes.create.url({
            site: props.site.slug,
        }) + `?parent_id=${parentCategory.id}`;
    }
};

const handleTreeAddRoot = () => {
    if (props.site?.slug) {
        window.location.href = CategoriesRoutes.create.url({
            site: props.site.slug,
        });
    }
};

const handleTreeSelect = (category: Category | null) => {
    // Handle category selection if needed
    console.log('Selected category:', category);
};

const toggleViewMode = () => {
    viewMode.value = viewMode.value === 'table' ? 'tree' : 'table';
};

// Watch for view mode changes and fetch all categories when switching to tree view
watch(viewMode, (newMode) => {
    if (newMode === 'tree' && allCategories.value.length === 0) {
        fetchAllCategories();
    }
});

// Watch for filter changes and refetch all categories if in tree view
watch([searchQuery, selectedParent, selectedStatus], () => {
    if (viewMode.value === 'tree') {
        fetchAllCategories();
    }
});

// Fetch all categories on component mount if starting in tree view
onMounted(() => {
    if (viewMode.value === 'tree') {
        fetchAllCategories();
    }
});

</script>

<template>
    <AppLayout>
        <Head title="Quản lý danh mục" />

        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 sm:flex sm:items-center sm:justify-between">
                <div class="mb-4 sm:mb-0">
                    <h1 class="text-2xl font-bold text-gray-900">
                        Quản lý danh mục
                    </h1>
                    <p class="mt-2 text-sm text-gray-700">
                        Quản lý danh mục sản phẩm theo cấu trúc phân cấp
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- View Toggle -->
                    <div class="flex items-center gap-1 border border-gray-200 rounded-lg p-1">
                        <Button
                            @click="viewMode = 'tree'"
                            variant="ghost"
                            size="sm"
                            :class="{ 'bg-gray-100': viewMode === 'tree' }"
                            class="p-2"
                            title="Xem dạng cây"
                        >
                            <TreePine class="h-4 w-4" />
                        </Button>
                        <Button
                            @click="viewMode = 'table'"
                            variant="ghost"
                            size="sm"
                            :class="{ 'bg-gray-100': viewMode === 'table' }"
                            class="p-2"
                            title="Xem dạng bảng"
                        >
                            <List class="h-4 w-4" />
                        </Button>
                    </div>

                    <!-- Add Category Button -->
                    <Button
                        v-if="canManageCategories && props.site?.slug"
                        :as="Link"
                        :href="
                            CategoriesRoutes.create.url({ site: props.site.slug })
                        "
                        class="flex w-full items-center justify-center gap-2 sm:w-auto"
                    >
                        <Plus class="h-4 w-4" />
                        Thêm danh mục
                    </Button>
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
                            placeholder="Tìm kiếm danh mục..."
                            @keyup.enter="performSearch"
                        />
                    </div>
                    <div>
                        <Select v-model="selectedParent">
                            <SelectTrigger>
                                <SelectValue placeholder="Danh mục cha" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all"
                                    >Tất cả danh mục</SelectItem
                                >
                                <SelectItem value="root"
                                    >Danh mục gốc</SelectItem
                                >
                                <SelectItem
                                    v-for="parent in parentCategories"
                                    :key="parent.id"
                                    :value="parent.id.toString()"
                                >
                                    {{ parent.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Select v-model="selectedStatus">
                            <SelectTrigger>
                                <SelectValue placeholder="Trạng thái" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tất cả</SelectItem>
                                <SelectItem value="true"
                                    >Đang hoạt động</SelectItem
                                >
                                <SelectItem value="false"
                                    >Không hoạt động</SelectItem
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
                    Tổng cộng
                    <span class="font-medium">
                        {{ viewMode === 'tree' && allCategories.length > 0 ? allCategories.length : categories.total }}
                    </span>
                    danh mục
                    <span v-if="viewMode === 'tree'" class="text-gray-500">
                        (hiển thị tất cả)
                    </span>
                </p>
            </div>

            <!-- Content -->
            <div v-if="categories.data.length === 0" class="rounded-lg border border-gray-200 bg-white py-12 text-center">
                <FolderTree class="mx-auto h-16 w-16 text-gray-300" />
                <h3 class="mt-4 text-lg font-medium text-gray-900">
                    Chưa có danh mục
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                    Bắt đầu bằng cách tạo danh mục đầu tiên.
                </p>
                <div class="mt-6">
                    <Button
                        v-if="canManageCategories && props.site?.slug"
                        :as="Link"
                        :href="CategoriesRoutes.create.url({ site: props.site.slug })"
                        class="flex items-center gap-2"
                    >
                        <Plus class="h-4 w-4" />
                        Thêm danh mục
                    </Button>
                </div>
            </div>

            <!-- Tree View (Desktop) -->
            <div v-else-if="viewMode === 'tree'" class="hidden md:block rounded-lg border border-gray-200 bg-white p-6">
                <!-- Loading State -->
                <div v-if="loadingAllCategories" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mx-auto"></div>
                        <p class="mt-2 text-sm text-gray-600">Đang tải danh mục...</p>
                    </div>
                </div>

                <!-- Category Tree -->
                <CategoryTree
                    v-else
                    :categories="categoriesTree"
                    :canManage="canManageCategories"
                    @edit="handleTreeEdit"
                    @delete="handleTreeDelete"
                    @addChild="handleTreeAddChild"
                    @addRoot="handleTreeAddRoot"
                    @select="handleTreeSelect"
                />
            </div>

            <!-- Desktop Table -->
            <div
                v-else-if="viewMode === 'table'"
                class="hidden overflow-hidden rounded-lg border border-gray-200 bg-white md:block"
            >
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Tên danh mục
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Danh mục cha
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Thứ tự
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Trạng thái
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Số sản phẩm
                            </th>
                            <th
                                v-if="canManageCategories"
                                scope="col"
                                class="px-6 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Thao tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr
                            v-for="category in categories.data"
                            :key="category.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div
                                    class="flex items-center"
                                    :class="getIndentClass(category.depth)"
                                >
                                    <FolderTree
                                        class="mr-2 h-4 w-4 text-gray-400"
                                    />
                                    <div>
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ category.name }}
                                        </div>
                                        <div
                                            v-if="category.description"
                                            class="max-w-xs truncate text-xs text-gray-500"
                                        >
                                            {{ category.description }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div
                                    v-if="category.parent"
                                    class="text-sm text-gray-900"
                                >
                                    {{ category.parent.name }}
                                </div>
                                <div
                                    v-else
                                    class="text-sm text-gray-500 italic"
                                >
                                    Danh mục gốc
                                </div>
                            </td>

                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <Badge variant="outline">
                                    {{ category.order }}
                                </Badge>
                            </td>

                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <Button
                                    v-if="canManageCategories"
                                    variant="ghost"
                                    size="sm"
                                    @click="toggleActive(category)"
                                    class="p-2"
                                >
                                    <Eye
                                        v-if="category.is_active"
                                        class="h-4 w-4 text-green-600"
                                    />
                                    <EyeOff
                                        v-else
                                        class="h-4 w-4 text-gray-400"
                                    />
                                </Button>
                                <Badge
                                    v-else
                                    :variant="
                                        category.is_active
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        category.is_active
                                            ? 'Hoạt động'
                                            : 'Không hoạt động'
                                    }}
                                </Badge>
                            </td>

                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <Badge variant="outline">
                                    {{ category.products_count }}
                                </Badge>
                            </td>

                            <td
                                v-if="canManageCategories"
                                class="px-6 py-4 text-center whitespace-nowrap"
                            >
                                <div class="flex justify-center gap-2">
                                    <Button
                                        v-if="props.site?.slug"
                                        :as="Link"
                                        :href="
                                            CategoriesRoutes.edit.url({
                                                site: props.site.slug,
                                                category: category.id,
                                            })
                                        "
                                        variant="ghost"
                                        size="sm"
                                        class="p-2"
                                    >
                                        <Edit class="h-4 w-4" />
                                    </Button>

                                    <Button
                                        @click="confirmDelete(category)"
                                        variant="ghost"
                                        size="sm"
                                        class="p-2 text-red-600 hover:text-red-700"
                                        :disabled="category.products_count > 0"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile List (Table Mode) -->
            <div v-if="categories.data.length > 0 && viewMode === 'table'" class="space-y-3 md:hidden">
                <div
                    v-for="category in categories.data"
                    :key="category.id"
                    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <!-- Header -->
                    <div class="mb-3 flex items-start justify-between">
                        <div
                            class="min-w-0 flex-1"
                            :class="getIndentClass(category.depth)"
                        >
                            <div class="flex items-center">
                                <FolderTree
                                    class="mr-2 h-4 w-4 shrink-0 text-gray-400"
                                />
                                <h3
                                    class="truncate text-lg font-medium text-gray-900"
                                >
                                    {{ category.name }}
                                </h3>
                            </div>
                            <p
                                v-if="category.description"
                                class="mt-1 line-clamp-2 text-sm text-gray-600"
                            >
                                {{ category.description }}
                            </p>
                        </div>
                        <div v-if="canManageCategories" class="ml-3 flex gap-1">
                            <Button
                                v-if="props.site?.slug"
                                :as="Link"
                                :href="
                                    CategoriesRoutes.edit.url({
                                        site: props.site.slug,
                                        category: category.id,
                                    })
                                "
                                variant="ghost"
                                size="sm"
                                class="h-8 w-8 p-2"
                            >
                                <Edit class="h-4 w-4" />
                            </Button>

                            <Button
                                @click="confirmDelete(category)"
                                variant="ghost"
                                size="sm"
                                class="h-8 w-8 p-2 text-red-600 hover:text-red-700"
                                :disabled="category.products_count > 0"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Danh mục cha:</span
                                >
                                <div class="font-medium text-gray-900">
                                    {{
                                        category.parent?.name || 'Danh mục gốc'
                                    }}
                                </div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Thứ tự:</span
                                >
                                <div>
                                    <Badge variant="outline" class="text-sm">
                                        {{ category.order }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Trạng thái:</span
                                >
                                <div class="mt-1 flex items-center">
                                    <Button
                                        v-if="canManageCategories"
                                        variant="ghost"
                                        size="sm"
                                        @click="toggleActive(category)"
                                        class="-ml-1 h-7 w-7 p-1"
                                    >
                                        <Eye
                                            v-if="category.is_active"
                                            class="h-4 w-4 text-green-600"
                                        />
                                        <EyeOff
                                            v-else
                                            class="h-4 w-4 text-gray-400"
                                        />
                                    </Button>
                                    <Badge
                                        v-else
                                        :variant="
                                            category.is_active
                                                ? 'default'
                                                : 'secondary'
                                        "
                                        class="text-sm"
                                    >
                                        {{
                                            category.is_active
                                                ? 'Hoạt động'
                                                : 'Không hoạt động'
                                        }}
                                    </Badge>
                                </div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500"
                                    >Số sản phẩm:</span
                                >
                                <div>
                                    <Badge variant="outline" class="text-sm">
                                        {{ category.products_count }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Tree View -->
            <div v-if="categories.data.length > 0 && viewMode === 'tree'" class="md:hidden">
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <!-- Loading State -->
                    <div v-if="loadingAllCategories" class="flex items-center justify-center py-8">
                        <div class="text-center">
                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-gray-900 mx-auto"></div>
                            <p class="mt-2 text-xs text-gray-600">Đang tải...</p>
                        </div>
                    </div>

                    <!-- Category Tree -->
                    <CategoryTree
                        v-else
                        :categories="categoriesTree"
                        :canManage="canManageCategories"
                        @edit="handleTreeEdit"
                        @delete="handleTreeDelete"
                        @addChild="handleTreeAddChild"
                        @addRoot="handleTreeAddRoot"
                        @select="handleTreeSelect"
                    />
                </div>
            </div>

            <!-- Pagination (only show in table view) -->
            <div
                v-if="categories.last_page > 1 && viewMode === 'table'"
                class="mt-8 flex justify-center"
            >
                <div class="flex flex-wrap justify-center gap-1">
                    <template
                        v-for="link in categories.links"
                        :key="link.label"
                    >
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
                        Bạn có chắc chắn muốn xóa danh mục "{{
                            categoryToDelete?.name
                        }}"?
                        <br />
                        Hành động này không thể hoàn tác.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">
                        Hủy
                    </Button>
                    <Button variant="destructive" @click="deleteCategory">
                        Xóa
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
