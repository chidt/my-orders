<template>
    <div class="bulk-actions">
        <!-- Action Bar -->
        <div
            v-if="selectedItems.length > 0"
            class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 bg-white border border-gray-200 rounded-lg shadow-lg px-4 py-3 flex items-center gap-4 min-w-max"
        >
            <!-- Selection Info -->
            <div class="flex items-center gap-2">
                <Badge variant="secondary" class="text-sm">
                    {{ selectedItems.length }} đã chọn
                </Badge>
                <Button
                    @click="clearSelection"
                    variant="ghost"
                    size="sm"
                    class="p-1 h-6 w-6 text-gray-400 hover:text-gray-600"
                >
                    <X class="h-4 w-4" />
                </Button>
            </div>

            <!-- Divider -->
            <div class="h-6 w-px bg-gray-200"></div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
                <!-- Activate/Deactivate (for categories) -->
                <template v-if="type === 'categories'">
                    <Button
                        @click="toggleActiveStatus(true)"
                        variant="outline"
                        size="sm"
                        class="text-green-600 hover:text-green-700 hover:bg-green-50"
                        :disabled="loading"
                    >
                        <Eye class="h-4 w-4 mr-1" />
                        Kích hoạt
                    </Button>

                    <Button
                        @click="toggleActiveStatus(false)"
                        variant="outline"
                        size="sm"
                        class="text-orange-600 hover:text-orange-700 hover:bg-orange-50"
                        :disabled="loading"
                    >
                        <EyeOff class="h-4 w-4 mr-1" />
                        Ẩn
                    </Button>

                    <Button
                        @click="showReorderModal = true"
                        variant="outline"
                        size="sm"
                        class="text-blue-600 hover:text-blue-700 hover:bg-blue-50"
                        :disabled="loading"
                    >
                        <ArrowUpDown class="h-4 w-4 mr-1" />
                        Sắp xếp
                    </Button>
                </template>

                <!-- Merge Tags (for tags) -->
                <template v-if="type === 'tags'">
                    <Button
                        @click="showMergeModal = true"
                        variant="outline"
                        size="sm"
                        class="text-blue-600 hover:text-blue-700 hover:bg-blue-50"
                        :disabled="loading || selectedItems.length < 2"
                    >
                        <Merge class="h-4 w-4 mr-1" />
                        Gộp thẻ
                    </Button>
                </template>

                <!-- Export -->
                <Button
                    @click="exportItems"
                    variant="outline"
                    size="sm"
                    class="text-gray-600 hover:text-gray-700 hover:bg-gray-50"
                    :disabled="loading"
                >
                    <Download class="h-4 w-4 mr-1" />
                    Xuất
                </Button>

                <!-- Delete -->
                <Button
                    @click="confirmDelete"
                    variant="outline"
                    size="sm"
                    class="text-red-600 hover:text-red-700 hover:bg-red-50"
                    :disabled="loading || !canDelete"
                >
                    <Trash2 class="h-4 w-4 mr-1" />
                    Xóa
                </Button>
            </div>

            <!-- Loading Indicator -->
            <div v-if="loading" class="flex items-center gap-2 text-sm text-gray-600">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                Đang xử lý...
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Dialog v-model:open="showDeleteModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Xác nhận xóa hàng loạt</DialogTitle>
                    <DialogDescription>
                        Bạn có chắc chắn muốn xóa {{ selectedItems.length }}
                        {{ type === 'categories' ? 'danh mục' : 'thẻ' }} đã chọn?
                        <br><br>
                        <strong class="text-red-600">Hành động này không thể hoàn tác.</strong>
                        <br><br>
                        <span v-if="type === 'categories'" class="text-sm text-gray-600">
                            Chỉ có thể xóa danh mục không có sản phẩm hoặc danh mục con.
                        </span>
                        <span v-if="type === 'tags'" class="text-sm text-gray-600">
                            Chỉ có thể xóa thẻ không được sử dụng bởi sản phẩm nào.
                        </span>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        @click="showDeleteModal = false"
                        variant="outline"
                        :disabled="loading"
                    >
                        Hủy
                    </Button>
                    <Button
                        @click="executeDelete"
                        variant="destructive"
                        :disabled="loading"
                    >
                        <Trash2 class="h-4 w-4 mr-2" />
                        {{ loading ? 'Đang xóa...' : 'Xóa ngay' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Merge Modal (for tags) -->
        <Dialog v-model:open="showMergeModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Gộp thẻ</DialogTitle>
                    <DialogDescription>
                        Chọn thẻ chính để giữ lại, các thẻ khác sẽ được gộp vào thẻ này.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium">Thẻ chính (sẽ được giữ lại):</label>
                        <Select v-model="primaryTagId">
                            <SelectTrigger>
                                <SelectValue placeholder="Chọn thẻ chính..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="item in selectedItems"
                                    :key="item.id"
                                    :value="item.id.toString()"
                                >
                                    {{ item.name }}
                                    <span class="text-gray-500 ml-2">({{ item.products_count || 0 }} sản phẩm)</span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600">Các thẻ sẽ bị gộp:</label>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <Badge
                                v-for="item in tagsToMerge"
                                :key="item.id"
                                variant="secondary"
                            >
                                {{ item.name }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        @click="showMergeModal = false"
                        variant="outline"
                        :disabled="loading"
                    >
                        Hủy
                    </Button>
                    <Button
                        @click="executeMerge"
                        :disabled="loading || !primaryTagId"
                    >
                        <Merge class="h-4 w-4 mr-2" />
                        {{ loading ? 'Đang gộp...' : 'Gộp thẻ' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Reorder Modal (for categories) -->
        <Dialog v-model:open="showReorderModal">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Sắp xếp danh mục</DialogTitle>
                    <DialogDescription>
                        Kéo thả để sắp xếp lại thứ tự của các danh mục đã chọn.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <div
                        v-for="(item, index) in reorderItems"
                        :key="item.id"
                        class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg"
                    >
                        <div class="flex flex-col gap-1">
                            <Button
                                @click="moveUp(index)"
                                variant="ghost"
                                size="sm"
                                class="p-1 h-6 w-6"
                                :disabled="index === 0"
                            >
                                <ChevronUp class="h-4 w-4" />
                            </Button>
                            <Button
                                @click="moveDown(index)"
                                variant="ghost"
                                size="sm"
                                class="p-1 h-6 w-6"
                                :disabled="index === reorderItems.length - 1"
                            >
                                <ChevronDown class="h-4 w-4" />
                            </Button>
                        </div>

                        <div class="flex items-center gap-2 flex-1">
                            <FolderTree class="h-4 w-4 text-gray-400" />
                            <span>{{ item.name }}</span>
                            <Badge variant="outline" class="text-xs">
                                Thứ tự: {{ index + 1 }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        @click="showReorderModal = false"
                        variant="outline"
                        :disabled="loading"
                    >
                        Hủy
                    </Button>
                    <Button
                        @click="executeReorder"
                        :disabled="loading"
                    >
                        <ArrowUpDown class="h-4 w-4 mr-2" />
                        {{ loading ? 'Đang cập nhật...' : 'Cập nhật thứ tự' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import {
    X,
    Eye,
    EyeOff,
    ArrowUpDown,
    Merge,
    Download,
    Trash2,
    ChevronUp,
    ChevronDown,
    FolderTree,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface BulkItem {
    id: number;
    name: string;
    products_count?: number;
    is_active?: boolean;
    order?: number;
}

interface Props {
    selectedItems: BulkItem[];
    type: 'categories' | 'tags';
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const emit = defineEmits<{
    'clear-selection': [];
    'toggle-active': [items: BulkItem[], active: boolean];
    'delete-items': [items: BulkItem[]];
    'merge-tags': [primaryId: number, tagIds: number[]];
    'reorder-items': [items: { id: number; order: number }[]];
    'export-items': [items: BulkItem[]];
}>();

// State
const showDeleteModal = ref(false);
const showMergeModal = ref(false);
const showReorderModal = ref(false);
const primaryTagId = ref<string>('');
const reorderItems = ref<BulkItem[]>([]);

// Computed
const canDelete = computed(() => {
    return props.selectedItems.every(item => {
        if (props.type === 'categories') {
            return (item.products_count || 0) === 0; // Categories with no products
        } else {
            return (item.products_count || 0) === 0; // Tags with no products
        }
    });
});

const tagsToMerge = computed(() => {
    if (!primaryTagId.value) return props.selectedItems;
    return props.selectedItems.filter(item => item.id.toString() !== primaryTagId.value);
});

// Methods
const clearSelection = () => {
    emit('clear-selection');
};

const toggleActiveStatus = (active: boolean) => {
    emit('toggle-active', props.selectedItems, active);
};

const confirmDelete = () => {
    showDeleteModal.value = true;
};

const executeDelete = () => {
    emit('delete-items', props.selectedItems);
    showDeleteModal.value = false;
};

const executeMerge = () => {
    if (!primaryTagId.value) return;

    const tagIds = tagsToMerge.value.map(tag => tag.id);
    emit('merge-tags', parseInt(primaryTagId.value), tagIds);
    showMergeModal.value = false;
    primaryTagId.value = '';
};

const executeReorder = () => {
    const reorderData = reorderItems.value.map((item, index) => ({
        id: item.id,
        order: index,
    }));
    emit('reorder-items', reorderData);
    showReorderModal.value = false;
};

const exportItems = () => {
    emit('export-items', props.selectedItems);
};

const moveUp = (index: number) => {
    if (index === 0) return;
    const items = [...reorderItems.value];
    [items[index], items[index - 1]] = [items[index - 1], items[index]];
    reorderItems.value = items;
};

const moveDown = (index: number) => {
    if (index === reorderItems.value.length - 1) return;
    const items = [...reorderItems.value];
    [items[index], items[index + 1]] = [items[index + 1], items[index]];
    reorderItems.value = items;
};

// Watch for modal openings to initialize data
const initializeReorderModal = () => {
    reorderItems.value = [...props.selectedItems].sort((a, b) => (a.order || 0) - (b.order || 0));
};

// Expose method for parent to trigger reorder modal
defineExpose({
    openReorderModal: () => {
        initializeReorderModal();
        showReorderModal.value = true;
    },
});
</script>

<style scoped>
.bulk-actions {
    /* Ensure the floating action bar is above other content */
}
</style>
