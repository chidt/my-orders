<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    index as ProductTypesIndex,
    store as ProductTypesStore,
} from '@/routes/product-types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Palette } from 'lucide-vue-next';
import { ref } from 'vue';

interface Site {
    id: number;
    name: string;
    slug: string;
}

interface Props {
    site?: Site;
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    order: 0,
    show_on_front: false,
    color: '#3b82f6',
});

const colorInput = ref<HTMLInputElement>();

const predefinedColors = [
    '#3b82f6', // Blue
    '#ef4444', // Red
    '#10b981', // Green
    '#f59e0b', // Amber
    '#8b5cf6', // Purple
    '#ec4899', // Pink
    '#14b8a6', // Teal
    '#f97316', // Orange
];

const submit = () => {
    if (!props.site?.slug) return;
    form.post(ProductTypesStore.url({ site: props.site.slug }));
};

const selectColor = (color: string) => {
    form.color = color;
};

const openColorPicker = () => {
    colorInput.value?.click();
};
</script>

<template>
    <AppLayout>
        <Head title="Thêm loại sản phẩm mới" />

        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center gap-4">
                <Button
                    :as="Link"
                    :href="
                        props.site?.slug
                            ? ProductTypesIndex.url({ site: props.site.slug })
                            : '#'
                    "
                    variant="outline"
                    size="icon"
                    class="shrink-0"
                >
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl font-bold text-gray-900">
                        Thêm loại sản phẩm mới
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Tạo loại sản phẩm mới cho cửa hàng của bạn
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Basic Information -->
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Thông tin cơ bản
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Nhập thông tin cơ bản cho loại sản phẩm
                        </p>
                    </div>

                    <div class="space-y-6">
                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Tên loại sản phẩm *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Ví dụ: Điện tử, Thời trang, Sách..."
                                required
                                class="w-full"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <!-- Order -->
                        <div class="space-y-2">
                            <Label for="order">Thứ tự hiển thị</Label>
                            <Input
                                id="order"
                                v-model.number="form.order"
                                type="number"
                                min="0"
                                placeholder="0"
                                class="w-full sm:max-w-xs"
                            />
                            <p class="text-sm text-gray-600">
                                Thứ tự sắp xếp khi hiển thị danh sách (số càng
                                nhỏ càng ưu tiên)
                            </p>
                            <InputError :message="form.errors.order" />
                        </div>

                        <!-- Show on Front -->
                        <div class="flex items-center space-x-3">
                            <Checkbox
                                id="show_on_front"
                                :checked="form.show_on_front"
                                @update:checked="form.show_on_front = $event"
                            />
                            <Label
                                for="show_on_front"
                                class="cursor-pointer text-sm leading-none font-medium"
                            >
                                Hiển thị trên trang chủ
                            </Label>
                        </div>
                    </div>
                </div>

                <!-- Color Selection -->
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Màu sắc
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Chọn màu sắc đại diện cho loại sản phẩm
                        </p>
                    </div>

                    <div class="space-y-6">
                        <!-- Color Preview -->
                        <div
                            class="flex items-center gap-4 rounded-lg bg-gray-50 p-4"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="h-8 w-8 shrink-0 rounded-lg border-2 border-gray-200"
                                    :style="{ backgroundColor: form.color }"
                                ></div>
                                <span
                                    class="text-sm font-medium text-gray-600"
                                    >{{ form.color }}</span
                                >
                            </div>
                        </div>

                        <!-- Predefined Colors -->
                        <div class="space-y-3">
                            <Label>Màu sắc có sẵn</Label>
                            <div
                                class="grid grid-cols-4 gap-3 sm:flex sm:flex-wrap"
                            >
                                <button
                                    v-for="color in predefinedColors"
                                    :key="color"
                                    type="button"
                                    @click="selectColor(color)"
                                    class="h-12 w-12 rounded-lg border-2 transition-all duration-200 hover:scale-105"
                                    :class="
                                        form.color === color
                                            ? 'border-gray-900 ring-2 ring-gray-900 ring-offset-2'
                                            : 'border-gray-200 hover:border-gray-300'
                                    "
                                    :style="{ backgroundColor: color }"
                                    :title="color"
                                ></button>
                            </div>
                        </div>

                        <!-- Custom Color -->
                        <div class="space-y-3">
                            <Label for="color">Màu sắc tùy chỉnh</Label>
                            <div class="flex flex-col gap-3 sm:flex-row">
                                <Input
                                    id="color"
                                    v-model="form.color"
                                    placeholder="#3b82f6"
                                    pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
                                    class="flex-1 sm:max-w-xs"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="openColorPicker"
                                    class="flex w-full items-center gap-2 sm:w-auto"
                                >
                                    <Palette class="h-4 w-4" />
                                    <span class="sm:hidden">Chọn màu</span>
                                </Button>
                                <input
                                    ref="colorInput"
                                    type="color"
                                    v-model="form.color"
                                    class="sr-only"
                                />
                            </div>
                            <p class="text-sm text-gray-600">
                                Nhập mã màu hex (ví dụ: #ff0000) hoặc sử dụng bộ
                                chọn màu
                            </p>
                            <InputError :message="form.errors.color" />
                        </div>
                    </div>
                </div>

                <!-- Preview -->
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Xem trước
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Xem trước loại sản phẩm sẽ hiển thị như thế nào
                        </p>
                    </div>

                    <div
                        class="flex items-center gap-3 rounded-lg border-2 border-dashed border-gray-200 bg-gray-50 p-4"
                    >
                        <div
                            class="h-5 w-5 shrink-0 rounded-full"
                            :style="{ backgroundColor: form.color }"
                        ></div>
                        <span class="font-medium text-gray-900">
                            {{ form.name || 'Tên loại sản phẩm' }}
                        </span>
                        <span
                            v-if="form.show_on_front"
                            class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                        >
                            Hiển thị trang chủ
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col gap-4 sm:flex-row sm:justify-end">
                    <Button
                        :as="Link"
                        :href="
                            props.site?.slug
                                ? ProductTypesIndex.url({
                                      site: props.site.slug,
                                  })
                                : '#'
                        "
                        variant="outline"
                        class="order-2 w-full sm:order-1 sm:w-auto"
                    >
                        Hủy
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="order-1 w-full sm:order-2 sm:w-auto"
                    >
                        {{
                            form.processing
                                ? 'Đang lưu...'
                                : 'Tạo loại sản phẩm'
                        }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
