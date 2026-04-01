<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { formatVnd } from '@/lib/utils';
import { ref, watch } from 'vue';

type ProductItemOption = {
    id: number;
    name: string;
    sku: string;
    product_name: string | null;
    price: number;
};

const props = defineProps<{
    siteSlug: string;
}>();

const isOpen = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    selected: [item: ProductItemOption];
}>();

const search = ref('');
const options = ref<ProductItemOption[]>([]);
const searching = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const fetchOptions = async (keyword: string) => {
    if (keyword.trim().length < 2) {
        options.value = [];
        return;
    }

    searching.value = true;
    try {
        const response = await fetch(
            `/${props.siteSlug}/orders/product-items/search?search=${encodeURIComponent(keyword.trim())}`,
            {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            },
        );

        const payload = (await response.json().catch(() => ({ data: [] }))) as {
            data?: ProductItemOption[];
        };
        options.value = payload.data ?? [];
    } finally {
        searching.value = false;
    }
};

watch(
    () => isOpen.value,
    (open) => {
        if (open) {
            search.value = '';
            options.value = [];
        }
    },
);

watch(
    () => search.value,
    (value) => {
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        searchTimeout = setTimeout(() => {
            void fetchOptions(value);
        }, 300);
    },
);

const chooseItem = (item: ProductItemOption) => {
    emit('selected', item);
    isOpen.value = false;
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Tìm sản phẩm</DialogTitle>
            </DialogHeader>

            <div class="space-y-3">
                <Input
                    v-model="search"
                    placeholder="Nhập tên sản phẩm / SKU..."
                />

                <div class="max-h-80 overflow-auto rounded-md border">
                    <div
                        v-if="searching"
                        class="px-3 py-2 text-sm text-gray-500"
                    >
                        Đang tìm sản phẩm...
                    </div>
                    <div
                        v-else-if="search.trim().length < 2"
                        class="px-3 py-2 text-sm text-gray-500"
                    >
                        Nhập ít nhất 2 ký tự để tìm kiếm.
                    </div>
                    <div
                        v-else-if="options.length === 0"
                        class="px-3 py-2 text-sm text-gray-500"
                    >
                        Không tìm thấy sản phẩm phù hợp.
                    </div>
                    <button
                        v-for="item in options"
                        :key="item.id"
                        type="button"
                        class="flex w-full items-center justify-between border-b px-3 py-2 text-left text-sm last:border-b-0 hover:bg-gray-50"
                        @click="chooseItem(item)"
                    >
                        <span>{{
                            [item.product_name, item.name, item.sku]
                                .filter(Boolean)
                                .join(' | ')
                        }}</span>
                        <span class="text-xs text-gray-500">{{
                            formatVnd(item.price)
                        }}</span>
                    </button>
                </div>

                <div class="flex justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isOpen = false"
                    >
                        Đóng
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
