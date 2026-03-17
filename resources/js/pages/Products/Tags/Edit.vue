<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Tag } from 'lucide-vue-next';
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

interface TagData {
    id: number;
    name: string;
    slug: string;
    products_count: number;
}

interface Props {
    site?: Site;
    tag: TagData;
}

const props = defineProps<Props>();

const form = useForm({
    name: props.tag.name,
    slug: props.tag.slug,
});

const submit = () => {
    if (!props.site?.slug) return;
    form.put(TagsRoutes.update.url({
        site: props.site.slug,
        tag: props.tag.id
    }));
};
</script>

<template>
    <AppLayout>
        <Head :title="`Sửa thẻ: ${tag.name}`" />

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
                    <h1 class="text-2xl font-bold text-gray-900">Sửa thẻ: {{ tag.name }}</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Cập nhật thông tin thẻ
                    </p>
                </div>
            </div>

            <!-- Warning if tag has products -->
            <div v-if="tag.products_count > 0" class="mb-8">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Thẻ đang được sử dụng
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>
                                    Thẻ này hiện được sử dụng bởi {{ tag.products_count }} sản phẩm.
                                    Thay đổi tên thẻ sẽ ảnh hưởng đến tất cả các sản phẩm này.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Basic Information -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Thông tin thẻ</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Cập nhật thông tin cho thẻ
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
                                placeholder="ban-chay"
                            />
                            <p class="text-sm text-gray-500">
                                Đường dẫn thân thiện cho SEO. Để trống để tự động tạo từ tên thẻ.
                            </p>
                            <InputError class="mt-2" :message="form.errors.slug" />
                        </div>
                    </div>
                </div>

                <!-- Usage Statistics -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Thống kê sử dụng</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Thông tin về việc sử dụng thẻ này
                        </p>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <Tag class="h-5 w-5 text-gray-400 mr-3" />
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    Được sử dụng bởi {{ tag.products_count }} sản phẩm
                                </p>
                                <p class="text-xs text-gray-500">
                                    Thẻ này {{ tag.products_count > 0 ? 'đang được' : 'chưa được' }} sử dụng
                                </p>
                            </div>
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
                        <span v-if="form.processing">Đang cập nhật...</span>
                        <span v-else>Cập nhật thẻ</span>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
