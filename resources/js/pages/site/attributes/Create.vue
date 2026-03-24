<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import siteRoute from '@/routes/site';
import type { AttributeFormProps } from '@/types/attribute';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<AttributeFormProps>();
const page = usePage();

const breadcrumbs = [
    {
        title: props.site.name,
        href: siteRoute.dashboard.url(props.site.slug),
        current: false,
    },
    {
        title: 'Thuộc tính sản phẩm',
        href: siteRoute.attributes.index.url(props.site.slug),
        current: false,
    },
    {
        title: 'Thêm thuộc tính',
        href: siteRoute.attributes.create.url(props.site.slug),
        current: true,
    },
];

const autoGenerateCode = ref(true);

const form = useForm({
    name: '',
    code: '',
    description: '',
    order: 0,
});

const generateCodeFromName = (name: string): string => {
    return name
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'd')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
};

watch(
    () => form.name,
    (newName) => {
        if (autoGenerateCode.value) {
            form.code = generateCodeFromName(newName);
        }
    },
);

watch(
    () => form.code,
    () => {
        if (form.code !== generateCodeFromName(form.name)) {
            autoGenerateCode.value = false;
        }
    },
);

const submit = () => {
    form.post(siteRoute.attributes.store.url(props.site.slug));
};
</script>

<template>
    <Head :title="`Thêm thuộc tính - ${site.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50">
            <div class="mx-auto max-w-4xl py-6 sm:px-6 lg:px-8">
                <div class="px-4 py-6 sm:px-0">
                    <!-- Header -->
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <div class="flex items-center space-x-4">
                                <Link
                                    :href="
                                        siteRoute.attributes.index.url(
                                            site.slug,
                                        )
                                    "
                                    class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                                    title="Quay lại"
                                >
                                    <ArrowLeft class="h-5 w-5" />
                                </Link>

                                <div>
                                    <h1
                                        class="text-base leading-6 font-semibold text-gray-900"
                                    >
                                        Thêm thuộc tính
                                    </h1>
                                    <p class="mt-1 text-sm text-gray-700">
                                        Thêm thuộc tính sản phẩm mới cho
                                        {{ site.name }}.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Flash messages -->
                    <div
                        v-if="page.props.flash?.error"
                        class="mt-4 rounded-md bg-red-50 p-4"
                    >
                        <p class="text-sm font-medium text-red-800">
                            {{ page.props.flash.error }}
                        </p>
                    </div>

                    <!-- Form -->
                    <div class="mt-8">
                        <div class="rounded-lg bg-white shadow">
                            <form
                                class="space-y-6 p-6"
                                @submit.prevent="submit"
                            >
                                <div class="grid gap-6 md:grid-cols-2">
                                    <!-- Name -->
                                    <div>
                                        <Label for="name">Tên thuộc tính</Label>
                                        <Input
                                            id="name"
                                            v-model="form.name"
                                            type="text"
                                            class="mt-1 block w-full"
                                            placeholder="VD: Kích Thước, Màu Sắc, Chất Liệu"
                                            required
                                        />
                                        <InputError
                                            class="mt-2"
                                            :message="form.errors.name"
                                        />
                                    </div>

                                    <!-- Code -->
                                    <div>
                                        <Label for="code"
                                            >Mã thuộc tính (Code)</Label
                                        >
                                        <Input
                                            id="code"
                                            v-model="form.code"
                                            type="text"
                                            class="mt-1 block w-full"
                                            placeholder="VD: size, color, material"
                                        />
                                        <p class="mt-1 text-xs text-gray-500">
                                            Mã tự động sinh từ tên, dạng
                                            kebab-case. Bạn có thể tùy chỉnh.
                                        </p>
                                        <InputError
                                            class="mt-2"
                                            :message="form.errors.code"
                                        />
                                    </div>

                                    <!-- Order -->
                                    <div>
                                        <Label for="order"
                                            >Thứ tự hiển thị</Label
                                        >
                                        <Input
                                            id="order"
                                            v-model.number="form.order"
                                            type="number"
                                            class="mt-1 block w-full"
                                            min="0"
                                            placeholder="0"
                                        />
                                        <InputError
                                            class="mt-2"
                                            :message="form.errors.order"
                                        />
                                    </div>

                                    <!-- Description -->
                                    <div class="md:col-span-2">
                                        <Label for="description">Mô tả</Label>
                                        <Textarea
                                            id="description"
                                            v-model="form.description"
                                            rows="3"
                                            class="mt-1"
                                            placeholder="Mô tả ngắn gọn về thuộc tính này..."
                                        />
                                        <InputError
                                            class="mt-2"
                                            :message="form.errors.description"
                                        />
                                    </div>
                                </div>

                                <div
                                    class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4"
                                >
                                    <Link
                                        :href="
                                            siteRoute.attributes.index.url(
                                                site.slug,
                                            )
                                        "
                                        class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                                    >
                                        Hủy
                                    </Link>
                                    <Button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="cursor-pointer disabled:cursor-not-allowed"
                                    >
                                        <span v-if="form.processing"
                                            >Đang lưu...</span
                                        >
                                        <span v-else>Tạo thuộc tính</span>
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tips -->
                    <div
                        class="mt-8 rounded-lg bg-indigo-50 p-5 text-sm text-indigo-900"
                    >
                        <p class="font-medium">💡 Gợi ý</p>
                        <ul class="mt-2 list-inside list-disc space-y-1">
                            <li>
                                Tên và mã thuộc tính phải là duy nhất trong cùng
                                một site.
                            </li>
                            <li>
                                Mã thuộc tính phải ở dạng kebab-case (ví dụ:
                                <code class="rounded bg-indigo-100 px-1"
                                    >product-type</code
                                >).
                            </li>
                            <li>
                                Thuộc tính phổ biến: Kích thước (size), Màu sắc
                                (color), Chất liệu (material).
                            </li>
                            <li>
                                Không thể xóa thuộc tính khi đã có giá trị liên
                                kết.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
