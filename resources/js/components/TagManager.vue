<template>
    <div class="tag-manager">
        <!-- Input Section -->
        <div class="mb-4">
            <div class="flex flex-wrap gap-2 mb-3" v-if="selectedTags.length > 0">
                <Badge
                    v-for="tag in selectedTags"
                    :key="tag.id"
                    variant="secondary"
                    class="flex items-center gap-1 px-3 py-1"
                >
                    <Tag class="h-3 w-3" />
                    {{ tag.name }}
                    <Button
                        @click="removeTag(tag)"
                        variant="ghost"
                        size="sm"
                        class="p-0 h-auto ml-1 hover:bg-transparent"
                    >
                        <X class="h-3 w-3 text-gray-500 hover:text-red-500" />
                    </Button>
                </Badge>
            </div>

            <div class="relative">
                <Input
                    ref="inputRef"
                    v-model="inputValue"
                    :placeholder="placeholder"
                    @keydown="handleKeyDown"
                    @input="handleInput"
                    @focus="showSuggestions = true"
                    @blur="handleBlur"
                    class="pr-10"
                />

                <Button
                    v-if="inputValue"
                    @click="clearInput"
                    variant="ghost"
                    size="sm"
                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1 h-6 w-6"
                >
                    <X class="h-4 w-4 text-gray-400" />
                </Button>

                <!-- Suggestions Dropdown -->
                <div
                    v-if="showSuggestions && (filteredSuggestions.length > 0 || canCreateNew)"
                    class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"
                >
                    <!-- Existing suggestions -->
                    <button
                        v-for="suggestion in filteredSuggestions"
                        :key="suggestion.id"
                        @mousedown.prevent="selectSuggestion(suggestion)"
                        class="w-full px-4 py-2 text-left hover:bg-gray-50 flex items-center justify-between group"
                    >
                        <div class="flex items-center">
                            <Tag class="h-4 w-4 mr-2 text-gray-400" />
                            <span>{{ suggestion.name }}</span>
                        </div>
                        <Badge
                            v-if="suggestion.products_count > 0"
                            variant="outline"
                            class="text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            {{ suggestion.products_count }}
                        </Badge>
                    </button>

                    <!-- Create new tag option -->
                    <button
                        v-if="canCreateNew && inputValue.trim() && !exactMatch"
                        @mousedown.prevent="createNewTag"
                        class="w-full px-4 py-2 text-left hover:bg-blue-50 flex items-center text-blue-600 border-t"
                    >
                        <Plus class="h-4 w-4 mr-2" />
                        Tạo thẻ mới: "{{ inputValue.trim() }}"
                    </button>
                </div>
            </div>

            <!-- Helper text -->
            <p v-if="helperText" class="text-sm text-gray-500 mt-2">
                {{ helperText }}
            </p>
        </div>

        <!-- Popular Tags Section -->
        <div v-if="popularTags.length > 0 && showPopular" class="mb-4">
            <label class="text-sm font-medium text-gray-700 mb-2 block">
                Thẻ phổ biến:
            </label>
            <div class="flex flex-wrap gap-2">
                <Badge
                    v-for="tag in popularTags.slice(0, maxPopular)"
                    :key="tag.id"
                    variant="outline"
                    class="cursor-pointer hover:bg-gray-50 transition-colors"
                    :class="{ 'opacity-50 cursor-not-allowed': isTagSelected(tag) }"
                    @click="selectSuggestion(tag)"
                >
                    <Tag class="h-3 w-3 mr-1" />
                    {{ tag.name }}
                    <span class="text-xs text-gray-500 ml-1">({{ tag.products_count }})</span>
                </Badge>
            </div>
        </div>

        <!-- Selected Tags Summary -->
        <div v-if="showSummary && selectedTags.length > 0" class="text-sm text-gray-600">
            Đã chọn {{ selectedTags.length }} thẻ
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, nextTick } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tag, X, Plus } from 'lucide-vue-next';

interface TagData {
    id: number;
    name: string;
    slug: string;
    products_count: number;
    site_id: number;
}

interface Props {
    modelValue: TagData[];
    availableTags?: TagData[];
    popularTags?: TagData[];
    placeholder?: string;
    helperText?: string;
    canCreateNew?: boolean;
    showPopular?: boolean;
    showSummary?: boolean;
    maxPopular?: number;
    maxTags?: number;
}

const props = withDefaults(defineProps<Props>(), {
    availableTags: () => [],
    popularTags: () => [],
    placeholder: 'Nhập tên thẻ...',
    helperText: 'Nhập và nhấn Enter để thêm thẻ, hoặc chọn từ gợi ý.',
    canCreateNew: true,
    showPopular: true,
    showSummary: false,
    maxPopular: 8,
});

const emit = defineEmits<{
    'update:modelValue': [tags: TagData[]];
    'create-tag': [name: string];
    'search': [query: string];
}>();

// State
const inputValue = ref('');
const showSuggestions = ref(false);
const inputRef = ref<HTMLInputElement>();

// Computed
const selectedTags = computed(() => props.modelValue || []);

const filteredSuggestions = computed(() => {
    if (!inputValue.value.trim()) return [];

    const query = inputValue.value.toLowerCase().trim();
    const selectedIds = new Set(selectedTags.value.map(tag => tag.id));

    return props.availableTags
        .filter(tag =>
            !selectedIds.has(tag.id) &&
            tag.name.toLowerCase().includes(query)
        )
        .slice(0, 10);
});

const exactMatch = computed(() => {
    const query = inputValue.value.trim().toLowerCase();
    return props.availableTags.some(tag =>
        tag.name.toLowerCase() === query
    );
});

const canAddMore = computed(() => {
    if (!props.maxTags) return true;
    return selectedTags.value.length < props.maxTags;
});

// Methods
const handleInput = () => {
    if (inputValue.value.trim()) {
        emit('search', inputValue.value.trim());
    }
    showSuggestions.value = true;
};

const handleKeyDown = (event: KeyboardEvent) => {
    if (event.key === 'Enter') {
        event.preventDefault();

        if (filteredSuggestions.value.length > 0) {
            selectSuggestion(filteredSuggestions.value[0]);
        } else if (props.canCreateNew && inputValue.value.trim() && !exactMatch.value) {
            createNewTag();
        }
    } else if (event.key === 'Backspace' && !inputValue.value && selectedTags.value.length > 0) {
        // Remove last tag when backspace on empty input
        removeTag(selectedTags.value[selectedTags.value.length - 1]);
    } else if (event.key === 'Escape') {
        showSuggestions.value = false;
        inputRef.value?.blur();
    }
};

const handleBlur = () => {
    // Delay hiding suggestions to allow clicking
    setTimeout(() => {
        showSuggestions.value = false;
    }, 200);
};

const selectSuggestion = (tag: TagData) => {
    if (isTagSelected(tag) || !canAddMore.value) return;

    const newTags = [...selectedTags.value, tag];
    emit('update:modelValue', newTags);
    inputValue.value = '';
    showSuggestions.value = false;

    // Focus back to input
    nextTick(() => {
        inputRef.value?.focus();
    });
};

const createNewTag = () => {
    const name = inputValue.value.trim();
    if (!name || !canAddMore.value) return;

    emit('create-tag', name);
    inputValue.value = '';
    showSuggestions.value = false;
};

const removeTag = (tag: TagData) => {
    const newTags = selectedTags.value.filter(t => t.id !== tag.id);
    emit('update:modelValue', newTags);
};

const clearInput = () => {
    inputValue.value = '';
    showSuggestions.value = false;
};

const isTagSelected = (tag: TagData): boolean => {
    return selectedTags.value.some(t => t.id === tag.id);
};

const clearAllTags = () => {
    emit('update:modelValue', []);
};

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
    // React to external changes if needed
}, { deep: true });

// Expose methods
defineExpose({
    focus: () => inputRef.value?.focus(),
    blur: () => inputRef.value?.blur(),
    clearInput,
    clearAllTags,
});
</script>

<style scoped>
/* Custom styles for tag manager */
.tag-manager .tag-badge {
    animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>
