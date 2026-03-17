<template>
    <div class="category-tree-node">
        <!-- Node Content -->
        <div
            class="flex items-center group hover:bg-gray-50 rounded-lg transition-colors duration-150"
            :class="{
                'bg-blue-50 border-l-2 border-l-blue-500': isSelected,
                'ml-4': level > 0
            }"
        >
            <!-- Expand/Collapse Button -->
            <Button
                v-if="hasChildren"
                @click="toggleExpanded"
                variant="ghost"
                size="sm"
                class="p-1 h-6 w-6 mr-1 shrink-0"
            >
                <ChevronRight
                    class="h-4 w-4 transition-transform duration-200"
                    :class="{ 'rotate-90': isExpanded }"
                />
            </Button>
            <div v-else class="w-7 shrink-0"></div>

            <!-- Category Icon -->
            <FolderTree
                class="h-4 w-4 mr-2 shrink-0"
                :class="{
                    'text-gray-400': category.is_active,
                    'text-gray-300': !category.is_active
                }"
            />

            <!-- Category Info -->
            <div
                class="flex-1 min-w-0 py-2 pr-2 cursor-pointer"
                @click="selectCategory"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center min-w-0">
                        <span
                            class="font-medium text-sm truncate"
                            :class="{
                                'text-gray-900': category.is_active,
                                'text-gray-500 line-through': !category.is_active
                            }"
                        >
                            {{ category.name }}
                        </span>

                        <!-- Product Count Badge -->
                        <Badge
                            v-if="category.products_count > 0"
                            variant="secondary"
                            class="ml-2 text-xs"
                        >
                            {{ category.products_count }}
                        </Badge>

                        <!-- Inactive Badge -->
                        <Badge
                            v-if="!category.is_active"
                            variant="outline"
                            class="ml-2 text-xs text-gray-500"
                        >
                            Ẩn
                        </Badge>
                    </div>

                    <!-- Actions Menu (visible on hover) -->
                    <div
                        v-if="canManage"
                        class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                    >
                        <Button
                            @click.stop="addChild"
                            variant="ghost"
                            size="sm"
                            class="p-1 h-6 w-6 text-green-600 hover:text-green-700 hover:bg-green-50"
                            title="Thêm danh mục con"
                        >
                            <Plus class="h-3 w-3" />
                        </Button>

                        <Button
                            @click.stop="editCategory"
                            variant="ghost"
                            size="sm"
                            class="p-1 h-6 w-6 text-blue-600 hover:text-blue-700 hover:bg-blue-50"
                            title="Sửa danh mục"
                        >
                            <Edit class="h-3 w-3" />
                        </Button>

                        <Button
                            @click.stop="deleteCategory"
                            variant="ghost"
                            size="sm"
                            class="p-1 h-6 w-6 text-red-600 hover:text-red-700 hover:bg-red-50"
                            title="Xóa danh mục"
                            :disabled="category.products_count > 0 || hasChildren"
                        >
                            <Trash2 class="h-3 w-3" />
                        </Button>
                    </div>
                </div>

                <!-- Description -->
                <p
                    v-if="category.description"
                    class="text-xs text-gray-600 mt-1 truncate"
                    :title="category.description"
                >
                    {{ category.description }}
                </p>
            </div>
        </div>

        <!-- Children -->
        <div v-if="hasChildren && isExpanded" class="ml-6 mt-1 space-y-1">
            <CategoryTreeNode
                v-for="child in sortedChildren"
                :key="child.id"
                :category="child"
                :level="level + 1"
                :expandedNodes="expandedNodes"
                :selectedNode="selectedNode"
                :canManage="canManage"
                @toggle="$emit('toggle', $event)"
                @select="$emit('select', $event)"
                @edit="$emit('edit', $event)"
                @delete="$emit('delete', $event)"
                @addChild="$emit('addChild', $event)"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    ChevronRight,
    FolderTree,
    Plus,
    Edit,
    Trash2
} from 'lucide-vue-next';

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
    category: Category;
    level: number;
    expandedNodes: Set<number>;
    selectedNode: number | null;
    canManage?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canManage: false,
});

const emit = defineEmits<{
    toggle: [categoryId: number];
    select: [categoryId: number];
    edit: [category: Category];
    delete: [category: Category];
    addChild: [parent: Category];
}>();

// Computed
const hasChildren = computed(() => {
    return props.category.children && props.category.children.length > 0;
});

const isExpanded = computed(() => {
    return props.expandedNodes.has(props.category.id);
});

const isSelected = computed(() => {
    return props.selectedNode === props.category.id;
});

const sortedChildren = computed(() => {
    if (!props.category.children) return [];

    return [...props.category.children].sort((a, b) => {
        if (a.order !== b.order) {
            return a.order - b.order;
        }
        return a.name.localeCompare(b.name);
    });
});

// Methods
const toggleExpanded = () => {
    emit('toggle', props.category.id);
};

const selectCategory = () => {
    emit('select', props.category.id);
};

const editCategory = () => {
    emit('edit', props.category);
};

const deleteCategory = () => {
    emit('delete', props.category);
};

const addChild = () => {
    emit('addChild', props.category);
};
</script>

<style scoped>
.category-tree-node {
    /* Custom styles can be added here if needed */
}
</style>
