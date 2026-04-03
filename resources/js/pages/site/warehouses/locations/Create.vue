<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import siteRoute from '@/routes/site';

interface Site {
    id: number;
    name: string;
    slug: string;
}

interface Warehouse {
    id: number;
    code: string;
    name: string;
    address: string;
}

interface Props {
    site: Site;
    warehouse: Warehouse;
    suggestedCode: string;
}

const props = defineProps<Props>();
const page = usePage();

const breadcrumbs = [
    {
        title: props.site.name,
        href: siteRoute.dashboard.url(props.site.slug),
        current: false,
    },
    {
        title: 'Quản lý kho',
        href: siteRoute.warehouses.index.url(props.site.slug),
        current: false,
    },
    {
        title: props.warehouse.name,
        href: siteRoute.warehouses.locations.index.url([
            props.site.slug,
            props.warehouse.id,
        ]),
        current: false,
    },
    {
        title: 'Thêm vị trí',
        href: siteRoute.warehouses.locations.create.url([
            props.site.slug,
            props.warehouse.id,
        ]),
        current: true,
    },
];

// Form setup
const form = useForm({
    code: props.suggestedCode,
    name: '',
    is_default: false,
});

// Submit form
function submit() {
    form.post(
        siteRoute.warehouses.locations.store.url([
            props.site.slug,
            props.warehouse.id,
        ]),
        {
            onSuccess: () => {
                // Success message will be handled by redirect
            },
        },
    );
}
</script>

<template>
    <Head :title="`Thêm vị trí mới - ${warehouse.name} - ${site.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header with Back Button and Title -->
            <div class="flex items-center gap-4">
                <Link
                    :href="
                        siteRoute.warehouses.locations.index.url([
                            site.slug,
                            warehouse.id,
                        ])
                    "
                    class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                    title="Quay lại"
                >
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">
                        Thêm vị trí mới
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ warehouse.name }} - {{ warehouse.code }}
                    </p>
                    <p class="text-xs text-gray-400">{{ warehouse.address }}</p>
                </div>
            </div>

            <!-- Flash Messages -->
            <div
                v-if="
                    (page.props.flash as any)?.success ||
                    (page.props.flash as any)?.message
                "
                class="rounded-md border border-green-200 bg-green-50 px-4 py-3"
            >
                <p class="text-sm font-medium text-green-800">
                    {{
                        (page.props.flash as any)?.success ||
                        (page.props.flash as any)?.message
                    }}
                </p>
            </div>
            <div
                v-if="(page.props.flash as any)?.error"
                class="rounded-md border border-red-200 bg-red-50 px-4 py-3"
            >
                <p class="text-sm font-medium text-red-800">
                    {{ (page.props.flash as any).error }}
                </p>
            </div>

            <!-- Form -->
            <div class="overflow-hidden rounded-lg border bg-white shadow-sm">
                <div class="border-b bg-gray-50 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Thông tin vị trí
                    </h3>
                    <p class="text-sm text-gray-500">
                        Nhập thông tin cho vị trí mới trong kho
                        {{ warehouse.name }}.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6 p-6">
                    <!-- Code -->
                    <div class="space-y-2">
                        <Label for="code">
                            Mã vị trí
                            <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="code"
                            v-model="form.code"
                            type="text"
                            placeholder="Ví dụ: A01, B-15, ZONE-1"
                            :class="{
                                'border-red-500': form.errors.code,
                            }"
                            maxlength="50"
                            required
                        />
                        <p v-if="form.errors.code" class="text-sm text-red-600">
                            {{ form.errors.code }}
                        </p>
                        <p class="text-sm text-gray-500">
                            Mã vị trí phải duy nhất trong kho này. Chỉ sử dụng
                            chữ cái in hoa, số và dấu gạch ngang.
                        </p>
                    </div>

                    <!-- Name -->
                    <div class="space-y-2">
                        <Label for="name">
                            Tên vị trí
                            <span class="text-red-500">*</span>
                        </Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="Ví dụ: Kệ A01, Vùng lưu trữ B, Khu vực đặc biệt"
                            :class="{
                                'border-red-500': form.errors.name,
                            }"
                            maxlength="255"
                            required
                        />
                        <p v-if="form.errors.name" class="text-sm text-red-600">
                            {{ form.errors.name }}
                        </p>
                        <p class="text-sm text-gray-500">
                            Tên mô tả chi tiết về vị trí này.
                        </p>
                    </div>

                    <!-- Is Default -->
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="is_default"
                                v-model:checked="form.is_default"
                            />
                            <Label for="is_default" class="text-sm font-medium">
                                Đặt làm vị trí mặc định
                            </Label>
                        </div>
                        <p
                            v-if="form.errors.is_default"
                            class="text-sm text-red-600"
                        >
                            {{ form.errors.is_default }}
                        </p>
                        <p class="text-sm text-gray-500">
                            Vị trí mặc định sẽ được sử dụng khi không chỉ định
                            vị trí cụ thể. Mỗi kho chỉ có một vị trí mặc định.
                        </p>
                    </div>

                    <!-- Submit Buttons -->
                    <div
                        class="flex justify-end gap-4 border-t border-gray-200 pt-6"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            :as="Link"
                            :href="
                                siteRoute.warehouses.locations.index.url([
                                    site.slug,
                                    warehouse.id,
                                ])
                            "
                            :disabled="form.processing"
                        >
                            Hủy
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="flex items-center gap-2"
                        >
                            <Save class="h-4 w-4" />
                            {{ form.processing ? 'Đang lưu...' : 'Tạo vị trí' }}
                        </Button>
                    </div>
                </form>
            </div>

            <!-- Help Section -->
            <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-6">
                <h3 class="mb-4 text-lg font-semibold text-indigo-900">
                    💡 Hướng dẫn
                </h3>

                <div class="space-y-4 text-sm text-indigo-800">
                    <div>
                        <h4 class="mb-2 font-semibold text-indigo-900">
                            Quy tắc đặt mã vị trí:
                        </h4>
                        <ul class="list-inside list-disc space-y-1">
                            <li>• Mã vị trí phải duy nhất trong kho này</li>
                            <li>
                                • Chỉ sử dụng chữ cái in hoa (A-Z), số (0-9) và
                                dấu gạch ngang (-)
                            </li>
                            <li>• Tối đa 50 ký tự</li>
                            <li>• Ví dụ: A01, B-15, ZONE-1, SPECIAL-AREA</li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-2 font-semibold text-indigo-900">
                            Về vị trí mặc định:
                        </h4>
                        <ul class="list-inside list-disc space-y-1">
                            <li>
                                • Mỗi kho phải có ít nhất một vị trí mặc định
                            </li>
                            <li>
                                • Nếu không có vị trí nào là mặc định, vị trí
                                đầu tiên sẽ tự động trở thành mặc định
                            </li>
                            <li>
                                • Khi đặt vị trí này làm mặc định, vị trí mặc
                                định hiện tại sẽ bị bỏ
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
