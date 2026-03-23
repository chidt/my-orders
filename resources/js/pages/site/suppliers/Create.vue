<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import siteRoute from '@/routes/site';
import type { SupplierFormProps } from '@/types/supplier';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

const props = defineProps<SupplierFormProps>();
const page = usePage();

const breadcrumbs = [
    {
        title: props.site.name,
        href: siteRoute.dashboard.url(props.site.slug),
        current: false,
    },
    {
        title: 'Quản lý nhà cung cấp',
        href: siteRoute.suppliers.index.url(props.site.slug),
        current: false,
    },
    {
        title: 'Thêm nhà cung cấp',
        href: siteRoute.suppliers.create.url(props.site.slug),
        current: true,
    },
];

const form = useForm({
    name: '',
    person_in_charge: '',
    phone: '',
    address: '',
    description: '',
});

const submit = () => {
    form.post(siteRoute.suppliers.store.url(props.site.slug));
};
</script>

<template>
    <Head :title="`Thêm nhà cung cấp - ${site.name}`" />

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
                                        siteRoute.suppliers.index.url(site.slug)
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
                                        Thêm nhà cung cấp
                                    </h1>
                                    <p class="mt-1 text-sm text-gray-700">
                                        Thêm nhà cung cấp mới cho
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
                                    <div class="md:col-span-2">
                                        <Label for="name"
                                            >Tên nhà cung cấp</Label
                                        >
                                        <Input
                                            id="name"
                                            v-model="form.name"
                                            type="text"
                                            class="mt-1 block w-full"
                                            placeholder="VD: Công ty TNHH ABC"
                                            required
                                        />
                                        <InputError
                                            class="mt-2"
                                            :message="form.errors.name"
                                        />
                                    </div>

                                    <!-- Person in charge -->
                                    <div>
                                        <Label for="person_in_charge"
                                            >Người phụ trách</Label
                                        >
                                        <Input
                                            id="person_in_charge"
                                            v-model="form.person_in_charge"
                                            type="text"
                                            class="mt-1 block w-full"
                                            placeholder="VD: Nguyễn Văn A"
                                        />
                                        <InputError
                                            class="mt-2"
                                            :message="
                                                form.errors.person_in_charge
                                            "
                                        />
                                    </div>

                                    <!-- Phone -->
                                    <div>
                                        <Label for="phone">Số điện thoại</Label>
                                        <Input
                                            id="phone"
                                            v-model="form.phone"
                                            type="text"
                                            class="mt-1 block w-full"
                                            placeholder="VD: +84901234567"
                                        />
                                        <p class="mt-1 text-xs text-gray-500">
                                            Số điện thoại theo định dạng quốc tế
                                            hoặc trong nước, ví dụ: 0901234567
                                            hoặc +84901234567.
                                        </p>
                                        <InputError
                                            class="mt-2"
                                            :message="form.errors.phone"
                                        />
                                    </div>

                                    <!-- Address -->
                                    <div class="md:col-span-2">
                                        <Label for="address">Địa chỉ</Label>
                                        <Textarea
                                            id="address"
                                            v-model="form.address"
                                            rows="2"
                                            class="mt-1"
                                            placeholder="VD: 123 Đường ABC, Phường XYZ, Quận 1, TP.HCM"
                                        />
                                        <InputError
                                            class="mt-2"
                                            :message="form.errors.address"
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
                                            placeholder="Ghi chú thêm về điều khoản thanh toán, lịch sử hợp tác, v.v."
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
                                            siteRoute.suppliers.index.url(
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
                                        <span v-else>Tạo nhà cung cấp</span>
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
                                Có thể tạo nhiều nhà cung cấp có cùng tên trong
                                cùng một site.
                            </li>
                            <li>
                                Nên ghi rõ người phụ trách để dễ liên hệ khi đặt
                                hàng.
                            </li>
                            <li>
                                Không thể xóa nhà cung cấp khi đã có sản phẩm
                                liên kết.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
