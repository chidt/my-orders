<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import logoUrl from '@/assets/my-order-logo.png'; // 1. Import the image

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!-- 2. Use it in an <img> tag -->
    <img
        :src="logoUrl"
        alt="App Logo"
        :class="className"
        v-bind="$attrs"
    />
</template>
