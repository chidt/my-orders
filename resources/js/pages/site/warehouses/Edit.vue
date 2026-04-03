<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
        title: 'Chỉnh sửa kho',
        href: siteRoute.warehouses.edit.url([
            props.site.slug,
            props.warehouse.id,
        ]),
        current: true,
    },
];

const form = useForm({
    code: props.warehouse.code,
    name: props.warehouse.name,
    address: props.warehouse.address,
});

const submit = () => {
    form.put(
        siteRoute.warehouses.update.url([props.site.slug, props.warehouse.id]),
    );
};

const generateCode = () => {
    // Simple code generation based on warehouse name
    if (form.name) {
        const words = form.name.split(' ');
        let code: string;

        if (words.length === 1) {
            code = words[0].substring(0, 3).toUpperCase();
        } else {
            code = words
                .map((word) => word.charAt(0))
                .join('')
                .toUpperCase();
        }

        // Add number suffix if code is too short
        if (code.length < 2) {
            code += '001';
        }

        form.code = code;
    }
};
</script>

<template>
    <Head :title="`Chỉnh sửa kho ${warehouse.name} - ${site.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header with Back Button and Title -->
            <div class="flex items-center gap-4">
                <Link
                    :href="siteRoute.warehouses.index.url(site.slug)"
                    class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                    title="Quay lại"
                >
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">
                        Chỉnh sửa kho
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Cập nhật thông tin kho {{ warehouse.name }}
                    </p>
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

            <!-- Main Form Container -->
            <div class="overflow-hidden rounded-lg border bg-white shadow-sm">
                <form @submit.prevent="submit" class="space-y-6 p-6">
                    <!-- Warehouse Code -->
                    <div>
                        <Label for="code">Mã kho</Label>
                        <div class="mt-1 flex gap-3">
                            <Input
                                id="code"
                                v-model="form.code"
                                type="text"
                                class="flex-1"
                                placeholder="VD: W001, KHO01, MAIN"
                                required
                            />
                            <Button
                                type="button"
                                variant="outline"
                                @click="generateCode"
                                :disabled="!form.name"
                                class="shrink-0"
                            >
                                Tự động tạo
                            </Button>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Mã kho duy nhất trong phạm vi trang web này. Chỉ sử
                            dụng chữ cái in hoa, số và dấu gạch ngang.
                        </p>
                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>

                    <!-- Warehouse Name -->
                    <div>
                        <Label for="name">Tên kho</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1"
                            placeholder="VD: Kho trung tâm Hà Nội, Warehouse A"
                            required
                        />
                        <p class="mt-1 text-sm text-gray-500">
                            Tên mô tả cho kho hàng của bạn
                        </p>
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <!-- Warehouse Address -->
                    <div>
                        <Label for="address">Địa chỉ kho</Label>
                        <Textarea
                            id="address"
                            v-model="form.address"
                            rows="3"
                            class="mt-1"
                            placeholder="VD: 123 Đường ABC, Phường XYZ, Quận 1, TP.HCM"
                            required
                        />
                        <p class="mt-1 text-sm text-gray-500">
                            Địa chỉ đầy đủ của kho hàng
                        </p>
                        <InputError
                            class="mt-2"
                            :message="form.errors.address"
                        />
                    </div>

                    <!-- Actions -->
                    <div
                        class="flex items-center justify-end gap-4 border-t border-gray-200 pt-6"
                    >
                        <Button
                            variant="outline"
                            as="Link"
                            :href="siteRoute.warehouses.index.url(site.slug)"
                        >
                            Hủy
                        </Button>

                        <Button type="submit" :disabled="form.processing">
                            <span v-if="form.processing">Đang cập nhật...</span>
                            <span v-else>Cập nhật kho</span>
                        </Button>
                    </div>
                </form>
            </div>

            <!-- Warning Section -->
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-6">
                <h3 class="mb-2 text-sm font-medium text-amber-900">
                    ⚠️ Lưu ý
                </h3>
                <ul class="space-y-1 text-sm text-amber-800">
                    <li>
                        • Thay đổi mã kho có thể ảnh hưởng đến các báo cáo và
                        tài liệu có sẵn
                    </li>
                    <li>
                        • Nếu kho đã có vị trí lưu trữ, việc thay đổi thông tin
                        sẽ được áp dụng cho tất cả vị trí
                    </li>
                    <li>
                        • Địa chỉ kho được sử dụng cho việc vận chuyển và báo
                        cáo logistics
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
