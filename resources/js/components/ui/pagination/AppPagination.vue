<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationMeta {
    from?: number;
    to?: number;
    total: number;
    links: PaginationLink[];
}

defineProps<{
    meta: PaginationMeta;
    label?: string;
    onNavigate?: (url: string) => void;
}>();

/**
 * Format pagination labels like "Previous" and "Next"
 */
const formatLabel = (label: string): string => {
    if (label.includes('Previous')) return '‹';
    if (label.includes('Next')) return '›';
    return label;
};
</script>

<template>
    <div v-if="meta.total > 0" class="border-t px-4 py-4 sm:px-6 bg-gray-50/30">
        <div class="flex items-center justify-between gap-4 flex-col sm:flex-row">
            <div class="text-xs sm:text-sm text-gray-600 order-2 sm:order-1">
                Hiển thị từ <span class="font-bold text-gray-900">{{ meta.from }}</span> đến
                <span class="font-bold text-gray-900">{{ meta.to }}</span> trong tổng số
                <span class="font-bold text-gray-900">{{ meta.total }}</span> {{ label || 'mục' }}
            </div>
            <nav
                class="inline-flex -space-x-px rounded-lg shadow-sm bg-white overflow-hidden border border-gray-200 order-1 sm:order-2"
            >
                <template v-for="(link, index) in meta.links" :key="index">
                    <button
                        v-if="link.url && typeof onNavigate === 'function'"
                        type="button"
                        @click.prevent="onNavigate(link.url)"
                        :class="[
                            'relative inline-flex items-center px-4 py-2 text-sm font-medium transition-all duration-200',
                            link.active
                                ? 'z-10 bg-indigo-600 text-white border-indigo-600'
                                : 'text-gray-600 hover:bg-gray-50 border-gray-200 hover:text-indigo-600',
                            index !== 0 ? 'border-l' : '',
                        ]"
                        v-html="formatLabel(link.label)"
                    />
                    <Link
                        v-else-if="link.url"
                        :href="link.url"
                        :class="[
                            'relative inline-flex items-center px-4 py-2 text-sm font-medium transition-all duration-200',
                            link.active
                                ? 'z-10 bg-indigo-600 text-white border-indigo-600'
                                : 'text-gray-600 hover:bg-gray-50 border-gray-200 hover:text-indigo-600',
                            index !== 0 ? 'border-l' : '',
                        ]"
                        v-html="formatLabel(link.label)"
                    />
                    <span
                        v-else
                        :class="[
                            'relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-300 bg-gray-50 cursor-not-allowed',
                            index !== 0 ? 'border-l' : '',
                        ]"
                        v-html="formatLabel(link.label)"
                    />
                </template>
            </nav>
        </div>
    </div>
</template>
