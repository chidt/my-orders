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

const props = defineProps<AttributeFormProps>();
const page = usePage();

if (!props.attribute) {
    throw new Error('Attribute prop is required for Edit page.');
}

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
        title: 'Chỉnh sửa thuộc tính',
        href: siteRoute.attributes.edit.url([
            props.site.slug,
            props.attribute.id,
        ]),
        current: true,
    },
];

const form = useForm({
    name: props.attribute.name,
    code: props.attribute.code,
    description: props.attribute.description ?? '',
    order: props.attribute.order ?? 0,
});

const submit = () => {
    form.put(
        siteRoute.attributes.update.url([props.site.slug, props.attribute.id]),
    );
};
</script>

<template>
    <Head :title="`Chỉnh sửa thuộc tính ${attribute.name} - ${site.name}`" />

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
                                        Chỉnh sửa thuộc tính
                                    </h1>
                                    <p class="mt-1 text-sm text-gray-700">
                                        Cập nhật thông tin cho thuộc tính
                                        {{ attribute.name }}.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Flash messages -->
                    <div
                        v-if="page.props.flash?.message"
                        class="mt-4 rounded-md bg-green-50 p-4"
                    >
                        <p class="text-sm font-medium text-green-800">
                            {{ page.props.flash.message }}
                        </p>
                    </div>

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
                                        />
                                        <p class="mt-1 text-xs text-gray-500">
                                            Dạng kebab-case, ví dụ:
                                            product-type, size, color.
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
                                        <span v-else>Cập nhật</span>
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
