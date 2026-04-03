<script setup lang="ts">
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { X } from 'lucide-vue-next';
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
                class="cursor-zoom-in overflow-hidden rounded-md border border-gray-100 bg-gray-50 shadow-sm transition-all hover:border-gray-200 hover:shadow-md"
                :class="sizeClass"
                :title="`Xem ảnh lớn: ${alt}`"
            >
                <img
                    :src="src ?? ''"
                    :alt="alt"
                    class="h-full w-full object-cover transition-transform hover:scale-105"
                />
            </button>
        </DialogTrigger>
        <DialogContent class="max-w-5xl border-none bg-transparent p-0 shadow-2xl">
            <DialogTitle class="sr-only">{{ alt }}</DialogTitle>

            <!-- Close Button using DialogClose -->
            <DialogClose class="absolute top-4 right-4 z-50 flex h-10 w-10 items-center justify-center rounded-full bg-black/50 text-white shadow-lg backdrop-blur-sm transition-all hover:bg-black/70 focus:bg-black/70 focus:outline-none focus:ring-2 focus:ring-white/50">
                <X class="h-5 w-5" />
                <span class="sr-only">Đóng</span>
            </DialogClose>

            <!-- Image container with proper spacing -->
            <div class="relative flex min-h-[50vh] max-h-[90vh] items-center justify-center bg-white/95 backdrop-blur-sm rounded-2xl p-6">
                <img
                    :src="src ?? ''"
                    :alt="alt"
                    class="max-h-full max-w-full rounded-lg object-contain shadow-lg"
                />
            </div>
        </DialogContent>
    </Dialog>

    <div
        v-else
        class="flex items-center justify-center overflow-hidden rounded-md border border-dashed border-gray-200 bg-gray-50/50 text-[10px] font-medium text-gray-400 transition-colors hover:border-gray-300 hover:bg-gray-100/50"
        :class="sizeClass"
    >
        <div class="text-center">
            <svg class="mx-auto h-4 w-4 mb-1 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span class="text-[9px]">Không có ảnh</span>
        </div>
    </div>
</template>
