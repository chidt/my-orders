<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        src?: string | null;
        alt?: string;
        sizeClass?: string;
    }>(),
    {
        src: null,
        alt: 'Product thumbnail',
        sizeClass: 'h-12 w-12',
    },
);

const hasImage = computed(() => Boolean(props.src));
</script>

<template>
    <Dialog v-if="hasImage">
        <DialogTrigger as-child>
            <button
                type="button"
                class="cursor-zoom-in overflow-hidden rounded-md border border-gray-200 bg-gray-50"
                :class="sizeClass"
                :title="alt"
            >
                <img
                    :src="src ?? ''"
                    :alt="alt"
                    class="h-full w-full object-cover"
                />
            </button>
        </DialogTrigger>
        <DialogContent class="max-w-4xl">
            <DialogTitle class="sr-only">{{ alt }}</DialogTitle>
            <div class="flex items-center justify-center">
                <img
                    :src="src ?? ''"
                    :alt="alt"
                    class="max-h-[75vh] w-auto rounded-md object-contain"
                />
            </div>
        </DialogContent>
    </Dialog>

    <div
        v-else
        class="flex items-center justify-center overflow-hidden rounded-md border border-dashed border-gray-300 bg-gray-50 text-[10px] text-gray-400"
        :class="sizeClass"
    >
        No image
    </div>
</template>
