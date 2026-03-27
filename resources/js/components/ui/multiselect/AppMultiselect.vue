<script setup lang="ts">
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import { computed, useAttrs, useSlots } from 'vue';

defineOptions({ inheritAttrs: false });

/** Slots vue-multiselect có thể tùy biến — chỉ forward khi cha truyền slot. */
const forwardSlotNames = [
    'option',
    'caret',
    'clear',
    'tag',
    'singleLabel',
    'multipleLabel',
    'afterList',
    'beforeList',
    'maxElements',
] as const;

const model = defineModel<unknown>();

const props = withDefaults(
    defineProps<{
        options?: unknown[];
        label?: string;
        trackBy?: string;
        placeholder?: string;
        multiple?: boolean;
        searchable?: boolean;
        disabled?: boolean;
        loading?: boolean;
        /** 0 = không giới hạn */
        max?: number;
        hideSelected?: boolean;
        internalSearch?: boolean;
        allowEmpty?: boolean;
        selectLabel?: string;
        selectedLabel?: string;
        deselectLabel?: string;
        /** null: tự động (đóng khi single, mở khi multiple) */
        closeOnSelect?: boolean | null;
        /** null: tự động */
        clearOnSelect?: boolean | null;
        /** null: tự động (giữ search khi multiple) */
        preserveSearch?: boolean | null;
    }>(),
    {
        options: () => [],
        label: 'label',
        trackBy: 'id',
        placeholder: 'Chọn…',
        multiple: false,
        searchable: true,
        disabled: false,
        loading: false,
        max: 0,
        hideSelected: false,
        internalSearch: true,
        allowEmpty: true,
        selectLabel: '',
        selectedLabel: '',
        deselectLabel: '',
        closeOnSelect: null,
        clearOnSelect: null,
        preserveSearch: null,
    },
);

const attrs = useAttrs();
const slots = useSlots();

const closeOnSelectResolved = computed(() => {
    if (props.closeOnSelect !== null) {
        return props.closeOnSelect;
    }
    return !props.multiple;
});

const clearOnSelectResolved = computed(() => {
    if (props.clearOnSelect !== null) {
        return props.clearOnSelect;
    }
    return !props.multiple;
});

const preserveSearchResolved = computed(() => {
    if (props.preserveSearch !== null) {
        return props.preserveSearch;
    }
    return props.multiple;
});

const maxResolved = computed(() =>
    props.max > 0 ? props.max : undefined,
);

const activeForwardSlots = computed(() =>
    forwardSlotNames.filter((name) => slots[name] != null),
);
</script>

<template>
    <Multiselect
        v-model="model"
        :options="(options as never[])"
        :label="label"
        :track-by="trackBy"
        :placeholder="placeholder"
        :multiple="multiple"
        :searchable="searchable"
        :disabled="disabled"
        :loading="loading"
        :hide-selected="hideSelected"
        :close-on-select="closeOnSelectResolved"
        :clear-on-select="clearOnSelectResolved"
        :preserve-search="preserveSearchResolved"
        :internal-search="internalSearch"
        :allow-empty="allowEmpty"
        :select-label="selectLabel"
        :selected-label="selectedLabel"
        :deselect-label="deselectLabel"
        :max="maxResolved"
        class="app-multiselect"
        v-bind="attrs"
    >
        <template #noResult="scope">
            <slot name="noResult" v-bind="scope ?? {}">
                <span class="app-multiselect__hint">Không có kết quả</span>
            </slot>
        </template>
        <template #noOptions="scope">
            <slot name="noOptions" v-bind="scope ?? {}">
                <span class="app-multiselect__hint">Không có lựa chọn</span>
            </slot>
        </template>
        <template
            v-for="name in activeForwardSlots"
            :key="name"
            #[name]="scope"
        >
            <slot :name="name" v-bind="scope ?? {}" />
        </template>
    </Multiselect>
</template>

<style scoped>
.app-multiselect :deep(.multiselect__tags) {
    min-height: 2.5rem;
    border-radius: 0.375rem;
    border: 1px solid #e5e7eb;
    background: #fff;
    font-size: 0.875rem;
    line-height: 1.25rem;
}

.app-multiselect :deep(.multiselect__input),
.app-multiselect :deep(.multiselect__single) {
    font-size: 0.875rem;
    color: #111827;
}

.app-multiselect :deep(.multiselect__placeholder) {
    font-size: 0.875rem;
    color: #6b7280;
}

.app-multiselect :deep(.multiselect__tag) {
    margin-bottom: 0;
    margin-right: 0.25rem;
    margin-top: 0.125rem;
    border-radius: 0.375rem;
    background: #f3f4f6;
    color: #1f2937;
}

.app-multiselect :deep(.multiselect__tag-icon::after) {
    color: #4b5563;
}

.app-multiselect__hint {
    font-size: 0.875rem;
    color: #4b5563;
}
</style>
