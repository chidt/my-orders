<script setup lang="ts">
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
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, FolderTree } from 'lucide-vue-next';

interface Site {
    id: number;
    name: string;
    slug: string;
}

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    parent_id: number | null;
    order: number;
    is_active: boolean;
    products_count: number;
}

interface ParentCategory {
    id: number;
    name: string;
    depth: number;
}

interface Props {
    site?: Site;
    category: Category;
    parentCategories: ParentCategory[];
}

const props = defineProps<Props>();

const form = useForm({
    name: props.category.name,
    slug: props.category.slug,
    description: props.category.description || '',
    parent_id: props.category.parent_id,
    order: props.category.order,
    is_active: props.category.is_active,
});

const submit = () => {
    if (!props.site?.slug) return;
    form.put(
        CategoriesRoutes.update.url({
            site: props.site.slug,
            category: props.category.id,
        }),
    );
};
</script>

<template>
    <AppLayout>
        <Head :title="`Sửa danh mục: ${category.name}`" />

        <div class="px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center gap-4">
                <Button
                    :as="Link"
                    :href="
                        props.site?.slug
                            ? CategoriesRoutes.index.url({
                                  site: props.site.slug,
                              })
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
                        Sửa danh mục: {{ category.name }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Cập nhật thông tin danh mục
                    </p>
                </div>
            </div>

            <!-- Warning if category has products -->
            <div v-if="category.products_count > 0" class="mb-8">
                <div
                    class="rounded-lg border border-yellow-200 bg-yellow-50 p-4"
                >
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg
                                class="h-5 w-5 text-yellow-400"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Danh mục đang được sử dụng
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>
                                    Danh mục này hiện có
                                    {{ category.products_count }} sản phẩm. Hãy
                                    cẩn thận khi thay đổi thông tin có thể ảnh
                                    hưởng đến sản phẩm.
                                </p>
                            </div>
                        </div>
                    </div>
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
                            Cập nhật thông tin cơ bản cho danh mục
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
                            <InputError
                                class="mt-2"
                                :message="form.errors.name"
                            />
                        </div>

                        <!-- Slug -->
                        <div class="space-y-2">
                            <Label for="slug">Đường dẫn (slug)</Label>
                            <Input
                                id="slug"
                                v-model="form.slug"
                                type="text"
                                class="block w-full"
                                placeholder="ao-nam"
                            />
                            <p class="text-sm text-gray-500">
                                Đường dẫn thân thiện cho SEO. Để trống để tự
                                động tạo từ tên danh mục.
                            </p>
                            <InputError
                                class="mt-2"
                                :message="form.errors.slug"
                            />
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
                            <InputError
                                class="mt-2"
                                :message="form.errors.description"
                            />
                        </div>
                    </div>
                </div>

                <!-- Category Structure -->
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Cấu trúc danh mục
                        </h2>
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
                                    <SelectValue
                                        placeholder="Chọn danh mục cha (để trống cho danh mục gốc)"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="null"
                                        >Không có (danh mục gốc)</SelectItem
                                    >
                                    <SelectItem
                                        v-for="category in parentCategories"
                                        :key="category.id"
                                        :value="category.id"
                                    >
                                        <div class="flex items-center">
                                            <div
                                                :class="`pl-${category.depth * 4}`"
                                                class="flex items-center"
                                            >
                                                <FolderTree
                                                    class="mr-2 h-4 w-4 text-gray-400"
                                                />
                                                {{ category.name }}
                                            </div>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-sm text-gray-500">
                                Chọn danh mục cha để tạo danh mục con. Tối đa 3
                                cấp độ. Không thể chọn danh mục con làm danh mục
                                cha.
                            </p>
                            <InputError
                                class="mt-2"
                                :message="form.errors.parent_id"
                            />
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
                                Số nhỏ hơn sẽ hiển thị trước.
                            </p>
                            <InputError
                                class="mt-2"
                                :message="form.errors.order"
                            />
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Cài đặt
                        </h2>
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
                                    class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    Kích hoạt danh mục
                                </Label>
                                <p class="text-xs text-muted-foreground">
                                    Danh mục sẽ hiển thị trong danh sách và có
                                    thể được sử dụng
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="
                                category.products_count > 0 && !form.is_active
                            "
                            class="text-sm text-red-600"
                        >
                            <p>
                                ⚠️ Vô hiệu hóa danh mục sẽ ảnh hưởng đến
                                {{ category.products_count }} sản phẩm
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-4 pt-6">
                    <Button
                        :as="Link"
                        :href="
                            props.site?.slug
                                ? CategoriesRoutes.index.url({
                                      site: props.site.slug,
                                  })
                                : '#'
                        "
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
                        <span v-else>Cập nhật danh mục</span>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
