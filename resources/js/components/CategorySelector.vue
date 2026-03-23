<template>
    <div class="category-selector">
        <Select :modelValue="selectedValue" @update:modelValue="handleSelect">
            <SelectTrigger>
                <SelectValue :placeholder="placeholder">
                    <span v-if="selectedCategory" class="flex items-center">
                        <FolderTree class="mr-2 h-4 w-4 text-gray-500" />
                        <span class="truncate">{{
                            selectedCategory.name
                        }}</span>
                        <span
                            v-if="
                                showBreadcrumb &&
                                selectedCategory.breadcrumb.length > 1
                            "
                            class="ml-2 text-gray-400"
                        >
                            {{
                                selectedCategory.breadcrumb
                                    .slice(0, -1)
                                    .join(' › ')
                            }}
                        </span>
                    </span>
                </SelectValue>
            </SelectTrigger>

            <SelectContent class="max-w-sm">
                <!-- Clear Selection Option -->
                <SelectItem
                    v-if="!required"
                    value=""
                    class="text-gray-500 italic"
                >
                    {{ clearText }}
                </SelectItem>

                <!-- Root Categories Only -->
                <SelectGroup v-if="rootOnly">
                    <SelectLabel>Danh mục gốc</SelectLabel>
                    <SelectItem
                        v-for="category in rootCategories"
                        :key="category.id"
                        :value="category.id.toString()"
                        :disabled="isDisabled(category)"
                    >
                        <div class="flex w-full items-center">
                            <FolderTree class="mr-2 h-4 w-4 text-gray-400" />
                            <span class="flex-1 truncate">{{
                                category.name
                            }}</span>
                            <Badge
                                v-if="category.products_count > 0"
                                variant="secondary"
                                class="ml-2 text-xs"
                            >
                                {{ category.products_count }}
                            </Badge>
                        </div>
                    </SelectItem>
                </SelectGroup>

                <!-- Hierarchical Categories -->
                <div v-else>
                    <div v-if="searchable" class="border-b p-2">
                        <Input
                            v-model="searchQuery"
                            placeholder="Tìm kiếm danh mục..."
                            class="text-sm"
                        >
                            <template #prefix>
                                <Search class="h-4 w-4 text-gray-400" />
                            </template>
                        </Input>
                    </div>

                    <SelectItem
                        v-for="category in filteredHierarchicalCategories"
                        :key="category.id"
                        :value="category.id.toString()"
                        :disabled="isDisabled(category)"
                    >
                        <div
                            class="flex w-full items-center"
                            :style="{ paddingLeft: `${category.depth * 12}px` }"
                        >
                            <FolderTree class="mr-2 h-4 w-4 text-gray-400" />
                            <span class="flex-1 truncate">{{
                                category.name
                            }}</span>

                            <!-- Depth indicators -->
                            <span
                                v-if="showDepth && category.depth > 0"
                                class="mr-2 text-xs text-gray-400"
                            >
                                L{{ category.depth + 1 }}
                            </span>

                            <Badge
                                v-if="category.products_count > 0"
                                variant="secondary"
                                class="ml-2 text-xs"
                            >
                                {{ category.products_count }}
                            </Badge>
                        </div>
                    </SelectItem>

                    <!-- No Results -->
                    <div
                        v-if="
                            searchQuery &&
                            filteredHierarchicalCategories.length === 0
                        "
                        class="p-4 text-center text-sm text-gray-500"
                    >
                        Không tìm thấy danh mục phù hợp
                    </div>
                </div>
            </SelectContent>
        </Select>

        <!-- Selected Breadcrumb (outside select) -->
        <div
            v-if="
                showExternalBreadcrumb &&
                selectedCategory &&
                selectedCategory.breadcrumb.length > 1
            "
            class="mt-2 text-xs text-gray-500"
        >
            <span class="font-medium">Đường dẫn:</span>
            {{ selectedCategory.breadcrumb.join(' › ') }}
        </div>
    </div>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { FolderTree, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Category {
    id: number;
    name: string;
    slug: string;
    description?: string;
    order: number;
    is_active: boolean;
    parent_id?: number;
    products_count: number;
    depth: number;
    children?: Category[];
    breadcrumb: string[];
}

interface Props {
    categories: Category[];
    modelValue?: number | string | null;
    placeholder?: string;
    clearText?: string;
    required?: boolean;
    rootOnly?: boolean;
    excludeIds?: number[];
    onlyActive?: boolean;
    searchable?: boolean;
    showBreadcrumb?: boolean;
    showExternalBreadcrumb?: boolean;
    showDepth?: boolean;
    maxDepth?: number;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Chọn danh mục...',
    clearText: 'Không chọn danh mục',
    required: false,
    rootOnly: false,
    excludeIds: () => [],
    onlyActive: true,
    searchable: false,
    showBreadcrumb: false,
    showExternalBreadcrumb: false,
    showDepth: false,
    maxDepth: 3,
});

const emit = defineEmits<{
    'update:modelValue': [value: number | string | null];
    change: [category: Category | null];
}>();

// State
const searchQuery = ref('');

// Computed
const selectedValue = computed(() => {
    return props.modelValue?.toString() || '';
});

const selectedCategory = computed(() => {
    if (!props.modelValue) return null;
    return (
        props.categories.find((c) => c.id === Number(props.modelValue)) || null
    );
});

const filteredCategories = computed(() => {
    let filtered = [...props.categories];

    // Filter by active status
    if (props.onlyActive) {
        filtered = filtered.filter((c) => c.is_active);
    }

    // Exclude specified IDs
    if (props.excludeIds.length > 0) {
        filtered = filtered.filter((c) => !props.excludeIds.includes(c.id));
    }

    // Filter by max depth
    if (props.maxDepth !== undefined) {
        filtered = filtered.filter((c) => c.depth < props.maxDepth);
    }

    return filtered;
});

const rootCategories = computed(() => {
    return filteredCategories.value
        .filter((c) => !c.parent_id)
        .sort((a, b) => {
            if (a.order !== b.order) return a.order - b.order;
            return a.name.localeCompare(b.name);
        });
});

const hierarchicalCategories = computed(() => {
    return [...filteredCategories.value].sort((a, b) => {
        // Sort by breadcrumb path for hierarchical display
        const aPath = a.breadcrumb.join('');
        const bPath = b.breadcrumb.join('');
        return aPath.localeCompare(bPath);
    });
});

const filteredHierarchicalCategories = computed(() => {
    if (!searchQuery.value.trim()) {
        return hierarchicalCategories.value;
    }

    const query = searchQuery.value.toLowerCase().trim();
    return hierarchicalCategories.value.filter((category) => {
        return (
            category.name.toLowerCase().includes(query) ||
            category.breadcrumb.some((name) =>
                name.toLowerCase().includes(query),
            ) ||
            (category.description &&
                category.description.toLowerCase().includes(query))
        );
    });
});

// Methods
const isDisabled = (category: Category): boolean => {
    return (
        !category.is_active ||
        props.excludeIds.includes(category.id) ||
        (props.maxDepth !== undefined && category.depth >= props.maxDepth)
    );
};

const handleSelect = (value: string) => {
    const categoryId = value ? Number(value) : null;
    const category = categoryId
        ? props.categories.find((c) => c.id === categoryId) || null
        : null;

    emit('update:modelValue', categoryId);
    emit('change', category);
};

// Clear search when categories change
watch(
    () => props.categories,
    () => {
        searchQuery.value = '';
    },
);
</script>
