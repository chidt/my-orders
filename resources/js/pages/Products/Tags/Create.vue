<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Tag } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import TagsRoutes from '@/routes/tags';

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
    slug: '',
});

const submit = () => {
    if (!props.site?.slug) return;
    form.post(TagsRoutes.store.url({ site: props.site.slug }));
};
</script>

<template>
    <AppLayout>
        <Head title="Thêm thẻ mới" />

        <div class="px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-8">
                <Button
                    :as="Link"
                    :href="props.site?.slug ? TagsRoutes.index.url({ site: props.site.slug }) : '#'"
                    variant="outline"
                    size="icon"
                    class="shrink-0"
                >
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl font-bold text-gray-900">Thêm thẻ mới</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Tạo thẻ mới để phân loại sản phẩm
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Basic Information -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Thông tin thẻ</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Nhập thông tin cơ bản cho thẻ
                        </p>
                    </div>

                    <div class="space-y-6">
                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Tên thẻ *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="block w-full"
                                placeholder="Ví dụ: Bán chạy, Mới, Sale..."
                                required
                            />
                            <p class="text-sm text-gray-500">
                                Tên thẻ ngắn gọn, dễ nhớ để phân loại sản phẩm
                            </p>
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <!-- Slug -->
                        <div class="space-y-2">
                            <Label for="slug">Đường dẫn (slug)</Label>
                            <Input
                                id="slug"
                                v-model="form.slug"
                                type="text"
                                class="block w-full"
                                placeholder="ban-chay (tự động tạo nếu để trống)"
                            />
                            <p class="text-sm text-gray-500">
                                Đường dẫn thân thiện cho SEO. Để trống để tự động tạo từ tên thẻ.
                            </p>
                            <InputError class="mt-2" :message="form.errors.slug" />
                        </div>
                    </div>
                </div>

                <!-- Examples -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div class="mb-4">
                        <h3 class="text-base font-medium text-blue-900">Gợi ý thẻ phổ biến</h3>
                        <p class="mt-1 text-sm text-blue-700">
                            Một số ví dụ về thẻ thường được sử dụng
                        </p>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <h4 class="font-medium text-blue-900 mb-2">Khuyến mãi</h4>
                            <ul class="space-y-1 text-blue-700">
                                <li>Sale</li>
                                <li>Giảm giá</li>
                                <li>Flash Sale</li>
                                <li>Khuyến mãi</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-medium text-blue-900 mb-2">Trạng thái</h4>
                            <ul class="space-y-1 text-blue-700">
                                <li>Mới</li>
                                <li>Bán chạy</li>
                                <li>Xu hướng</li>
                                <li>Hot</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-medium text-blue-900 mb-2">Chất lượng</h4>
                            <ul class="space-y-1 text-blue-700">
                                <li>Cao cấp</li>
                                <li>Chất lượng</li>
                                <li>VIP</li>
                                <li>Premium</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-medium text-blue-900 mb-2">Xuất xứ</h4>
                            <ul class="space-y-1 text-blue-700">
                                <li>Made in Vietnam</li>
                                <li>Hàng nhập</li>
                                <li>Hàng Thái</li>
                                <li>Hàng Hàn</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-4 pt-6">
                    <Button
                        :as="Link"
                        :href="props.site?.slug ? TagsRoutes.index.url({ site: props.site.slug }) : '#'"
                        variant="outline"
                        type="button"
                    >
                        Hủy
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="min-w-32"
                    >
                        <span v-if="form.processing">Đang tạo...</span>
                        <span v-else>Tạo thẻ</span>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
