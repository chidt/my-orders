<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Palette } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import siteRoute from '@/routes/site';

interface Site {
    id: number;
    name: string;
    slug: string;
}

interface ProductType {
    id: number;
    name: string;
    order: number;
    show_on_front: boolean;
    color: string;
    products_count: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    site: Site;
    productType: ProductType;
}

const props = defineProps<Props>();

const form = useForm({
    name: props.productType.name,
    order: props.productType.order,
    show_on_front: props.productType.show_on_front,
    color: props.productType.color,
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
    form.put(siteRoute('product-types.update', {
        site: props.site.slug,
        product_type: props.productType.id
    }));
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
        <Head :title="`Chỉnh sửa loại sản phẩm: ${productType.name}`" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button
                    :as="Link"
                    :href="siteRoute('product-types.index', { site: props.site.slug })"
                    variant="outline"
                    size="icon"
                >
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Chỉnh sửa loại sản phẩm</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Cập nhật thông tin cho "{{ productType.name }}"
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Thông tin cơ bản</CardTitle>
                        <CardDescription>
                            Cập nhật thông tin cơ bản cho loại sản phẩm
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Tên loại sản phẩm *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Ví dụ: Điện tử, Thời trang, Sách..."
                                required
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
                                class="max-w-xs"
                            />
                            <p class="text-sm text-gray-600">
                                Thứ tự sắp xếp khi hiển thị danh sách (số càng nhỏ càng ưu tiên)
                            </p>
                            <InputError :message="form.errors.order" />
                        </div>

                        <!-- Show on Front -->
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="show_on_front"
                                :checked="form.show_on_front"
                                @update:checked="form.show_on_front = $event"
                            />
                            <Label for="show_on_front" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                Hiển thị trên trang chủ
                            </Label>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Màu sắc</CardTitle>
                        <CardDescription>
                            Chọn màu sắc đại diện cho loại sản phẩm
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Color Preview -->
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-8 h-8 rounded-lg border-2 border-gray-200"
                                    :style="{ backgroundColor: form.color }"
                                ></div>
                                <span class="text-sm text-gray-600">{{ form.color }}</span>
                            </div>
                        </div>

                        <!-- Predefined Colors -->
                        <div class="space-y-2">
                            <Label>Màu sắc có sẵn</Label>
                            <div class="flex gap-2 flex-wrap">
                                <button
                                    v-for="color in predefinedColors"
                                    :key="color"
                                    type="button"
                                    @click="selectColor(color)"
                                    class="w-8 h-8 rounded-lg border-2 hover:scale-110 transition-transform"
                                    :class="form.color === color ? 'border-gray-900' : 'border-gray-200'"
                                    :style="{ backgroundColor: color }"
                                ></button>
                            </div>
                        </div>

                        <!-- Custom Color -->
                        <div class="space-y-2">
                            <Label for="color">Màu sắc tùy chỉnh</Label>
                            <div class="flex gap-2">
                                <Input
                                    id="color"
                                    v-model="form.color"
                                    placeholder="#3b82f6"
                                    pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
                                    class="max-w-xs"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    @click="openColorPicker"
                                >
                                    <Palette class="h-4 w-4" />
                                </Button>
                                <input
                                    ref="colorInput"
                                    type="color"
                                    v-model="form.color"
                                    class="sr-only"
                                />
                            </div>
                            <p class="text-sm text-gray-600">
                                Nhập mã màu hex (ví dụ: #ff0000) hoặc sử dụng bộ chọn màu
                            </p>
                            <InputError :message="form.errors.color" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Preview Card -->
                <Card>
                    <CardHeader>
                        <CardTitle>Xem trước</CardTitle>
                        <CardDescription>
                            Xem trước loại sản phẩm sẽ hiển thị như thế nào
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-3 p-4 border rounded-lg bg-gray-50">
                            <div
                                class="w-4 h-4 rounded-full"
                                :style="{ backgroundColor: form.color }"
                            ></div>
                            <span class="font-medium">
                                {{ form.name || 'Tên loại sản phẩm' }}
                            </span>
                            <span v-if="form.show_on_front" class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                                Hiển thị trang chủ
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Usage Warning -->
                <Card v-if="productType.products_count > 0" class="border-amber-200 bg-amber-50">
                    <CardContent class="pt-6">
                        <div class="flex gap-3">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-amber-800">Cảnh báo</h3>
                                <p class="mt-1 text-sm text-amber-700">
                                    Loại sản phẩm này đang được sử dụng bởi {{ productType.products_count }} sản phẩm.
                                    Việc thay đổi thông tin có thể ảnh hưởng đến cách hiển thị của các sản phẩm này.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-4">
                    <Button
                        :as="Link"
                        :href="siteRoute('product-types.index', { site: props.site.slug })"
                        variant="outline"
                    >
                        Hủy
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Đang cập nhật...' : 'Cập nhật loại sản phẩm' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
