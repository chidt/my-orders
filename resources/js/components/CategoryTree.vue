<template>
    <div class="category-tree">
        <!-- Tree Container -->
        <div class="space-y-1">
            <CategoryTreeNode
                v-for="category in categories"
                :key="category.id"
                :category="category"
                :level="0"
                :expandedNodes="expandedNodes"
                :selectedNode="selectedNode"
                :canManage="canManage"
                @toggle="toggleNode"
                @select="selectNode"
                @edit="$emit('edit', $event)"
                @delete="$emit('delete', $event)"
                @addChild="$emit('addChild', $event)"
            />
        </div>

        <!-- Empty State -->
        <div
            v-if="categories.length === 0"
            class="py-8 text-center text-gray-500"
        >
            <FolderTree class="mx-auto mb-4 h-12 w-12 text-gray-300" />
            <p class="text-sm">Chưa có danh mục nào</p>
            <Button
                v-if="canManage"
                @click="$emit('addRoot')"
                variant="outline"
                size="sm"
                class="mt-4"
            >
                <Plus class="mr-2 h-4 w-4" />
                Thêm danh mục đầu tiên
            </Button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { FolderTree, Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import CategoryTreeNode from './CategoryTreeNode.vue';

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
    canManage?: boolean;
    selectedId?: number;
    expandAll?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canManage: false,
    expandAll: false,
});

const emit = defineEmits<{
    edit: [category: Category];
    delete: [category: Category];
    addChild: [parent: Category];
    addRoot: [];
    select: [category: Category | null];
}>();

// State
const expandedNodes = ref<Set<number>>(new Set());
const selectedNode = ref<number | null>(props.selectedId || null);

// Computed

// Methods
const toggleNode = (categoryId: number) => {
    if (expandedNodes.value.has(categoryId)) {
        expandedNodes.value.delete(categoryId);
    } else {
        expandedNodes.value.add(categoryId);
    }
};

const selectNode = (categoryId: number | null) => {
    selectedNode.value = categoryId;
    const category = categoryId
        ? props.categories.find((c) => c.id === categoryId) || null
        : null;
    emit('select', category);
};

// Auto-expand if expandAll prop is true
if (props.expandAll) {
    props.categories.forEach((category) => {
        if (category.children && category.children.length > 0) {
            expandedNodes.value.add(category.id);
        }
    });
}

// Auto-expand path to selected node
if (props.selectedId) {
    const findPathToNode = (
        nodeId: number,
        categories: Category[],
    ): number[] => {
        for (const category of categories) {
            if (category.id === nodeId) {
                return [category.id];
            }
            if (category.children) {
                const path = findPathToNode(nodeId, category.children);
                if (path.length > 0) {
                    return [category.id, ...path];
                }
            }
        }
        return [];
    };

    const pathToSelected = findPathToNode(props.selectedId, props.categories);
    pathToSelected.slice(0, -1).forEach((id) => {
        expandedNodes.value.add(id);
    });
}

// Expose methods for parent component
defineExpose({
    expandAll: () => {
        props.categories.forEach((category) => {
            expandedNodes.value.add(category.id);
        });
    },
    collapseAll: () => {
        expandedNodes.value.clear();
    },
    expandNode: (categoryId: number) => {
        expandedNodes.value.add(categoryId);
    },
    collapseNode: (categoryId: number) => {
        expandedNodes.value.delete(categoryId);
    },
    selectNode,
});
</script>
