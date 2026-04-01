<script setup lang="ts">
import AppMultiselect from '@/components/ui/multiselect/AppMultiselect.vue';
import { computed } from 'vue';

export interface TagOption {
    id: number;
    name: string;
}

const props = defineProps<{
    options: TagOption[];
    modelValue: number[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
}>();

const selected = computed({
    get() {
        const idSet = new Set(props.modelValue);
        return props.options.filter((t) => idSet.has(t.id));
    },
    set(val: TagOption[]) {
        emit(
            'update:modelValue',
            val.map((t) => t.id),
        );
    },
});
</script>

<template>
    <div v-if="options.length === 0" class="text-sm text-gray-600">
        Chưa có tag nào.
    </div>
    <AppMultiselect
        v-else
        v-model="selected"
        :options="options"
        :multiple="true"
        label="name"
        track-by="id"
        placeholder="Gõ để tìm hoặc chọn thẻ…"
        :hide-selected="false"
    >
        <template #noResult>
            <span class="text-sm text-gray-600"
                >Không tìm thấy thẻ phù hợp</span
            >
        </template>
    </AppMultiselect>
</template>
