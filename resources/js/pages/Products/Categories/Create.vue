<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, FolderTree } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import CategoriesRoutes from '@/routes/categories';

interface Site {
    id: number;
    name: string;
    slug: string;
}

interface ParentCategory {
    id: number;
    name: string;
    depth: number;
}

interface Props {
    site?: Site;
    parentCategories: ParentCategory[];
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    slug: '',
    description: '',
    parent_id: null as number | null,
    order: 0,
    is_active: true,
});

const submit = () => {
    if (!props.site?.slug) return;
    form.post(CategoriesRoutes.store.url({ site: props.site.slug }));
};
</script>

<template>
    <AppLayout>
        <Head title="Thêm danh mục mới" />

        <div class="px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-8">
                <Button
                    :as="Link"
                    :href="props.site?.slug ? CategoriesRoutes.index.url({ site: props.site.slug }) : '#'"
                    variant="outline"
                    size="icon"
                    class="shrink-0"
                >
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl font-bold text-gray-900">Thêm danh mục mới</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Tạo danh mục mới cho sản phẩm của bạn
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Basic Information -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Thông tin cơ bản</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Nhập thông tin cơ bản cho danh mục
                        </p>
                    </div>

                    <div class="space-y-6">
                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Tên danh mục *</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="block w-full"
                                placeholder="Ví dụ: Áo Nam"
                                required
                            />
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
                                placeholder="ao-nam (tự động tạo nếu để trống)"
                            />
                            <p class="text-sm text-gray-500">
                                Đường dẫn thân thiện cho SEO. Để trống để tự động tạo từ tên danh mục.
                            </p>
                            <InputError class="mt-2" :message="form.errors.slug" />
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description">Mô tả</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                class="block w-full"
                                rows="4"
                                placeholder="Mô tả chi tiết về danh mục này..."
                            />
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>
                    </div>
                </div>

                <!-- Category Structure -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Cấu trúc danh mục</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Thiết lập vị trí của danh mục trong cây phân cấp
                        </p>
                    </div>

                    <div class="space-y-6">
                        <!-- Parent Category -->
                        <div class="space-y-2">
                            <Label for="parent_id">Danh mục cha</Label>
                            <Select v-model="form.parent_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Chọn danh mục cha (để trống cho danh mục gốc)" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="null">Không có (danh mục gốc)</SelectItem>
                                    <SelectItem
                                        v-for="category in parentCategories"
                                        :key="category.id"
                                        :value="category.id"
                                    >
                                        <div class="flex items-center">
                                            <div :class="`pl-${category.depth * 4}`" class="flex items-center">
                                                <FolderTree class="h-4 w-4 text-gray-400 mr-2" />
                                                {{ category.name }}
                                            </div>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-sm text-gray-500">
                                Chọn danh mục cha để tạo danh mục con. Tối đa 3 cấp độ.
                            </p>
                            <InputError class="mt-2" :message="form.errors.parent_id" />
                        </div>

                        <!-- Order -->
                        <div class="space-y-2">
                            <Label for="order">Thứ tự hiển thị</Label>
                            <Input
                                id="order"
                                v-model.number="form.order"
                                type="number"
                                class="block w-full"
                                min="0"
                                step="1"
                            />
                            <p class="text-sm text-gray-500">
                                Số nhỏ hơn sẽ hiển thị trước. Mặc định là 0.
                            </p>
                            <InputError class="mt-2" :message="form.errors.order" />
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Cài đặt</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Các tùy chọn hiển thị cho danh mục
                        </p>
                    </div>

                    <div class="space-y-6">
                        <!-- Active Status -->
                        <div class="flex items-center space-x-3">
                            <Checkbox
                                id="is_active"
                                v-model:checked="form.is_active"
                            />
                            <div class="grid gap-1.5 leading-none">
                                <Label
                                    for="is_active"
                                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    Kích hoạt danh mục
                                </Label>
                                <p class="text-xs text-muted-foreground">
                                    Danh mục sẽ hiển thị trong danh sách và có thể được sử dụng
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-4 pt-6">
                    <Button
                        :as="Link"
                        :href="props.site?.slug ? CategoriesRoutes.index.url({ site: props.site.slug }) : '#'"
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
                        <span v-else>Tạo danh mục</span>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
