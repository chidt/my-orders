<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import siteRoute from '@/routes/site';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

interface Site {
    id: number;
    name: string;
    slug: string;
}

interface Props {
    site: Site;
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
        title: 'Tạo kho mới',
        href: siteRoute.warehouses.create.url(props.site.slug),
        current: true,
    },
];

const form = useForm({
    code: props.suggestedCode,
    name: '',
    address: '',
});

const submit = () => {
    form.post(siteRoute.warehouses.store.url(props.site.slug), {
        onFinish: () => form.reset('code', 'name', 'address'),
    });
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
    <Head :title="`Tạo kho mới - ${site.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50">
            <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
                <div class="px-4 py-6 sm:px-0">
                    <!-- Header -->
                    <div class="sm:flex sm:items-center">
                        <div class="sm:flex-auto">
                            <div class="flex items-center space-x-4">
                                <Link
                                    :href="
                                        siteRoute.warehouses.index.url(
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
                                        Tạo kho mới
                                    </h1>
                                    <p class="mt-1 text-sm text-gray-700">
                                        Tạo kho mới cho {{ site.name }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Success Message -->
                    <div
                        v-if="page.props.flash?.message"
                        class="mt-4 rounded-md bg-green-50 p-4"
                    >
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg
                                    class="h-5 w-5 text-green-400"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L7.53 10.53a.75.75 0 00-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    {{ page.props.flash.message }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <div
                        v-if="page.props.flash?.error"
                        class="mt-4 rounded-md bg-red-50 p-4"
                    >
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg
                                    class="h-5 w-5 text-red-400"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">
                                    {{ page.props.flash.error }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="mt-8">
                        <div class="bg-white shadow sm:rounded-lg">
                            <form
                                @submit.prevent="submit"
                                class="space-y-6 p-6"
                            >
                                <!-- Warehouse Code -->
                                <div>
                                    <Label for="code">Mã kho</Label>
                                    <div class="mt-1 flex rounded-md shadow-sm">
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
                                            class="ml-3"
                                        >
                                            Tự động tạo
                                        </Button>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Mã kho duy nhất trong phạm vi trang web
                                        này. Chỉ sử dụng chữ cái in hoa, số và
                                        dấu gạch ngang.
                                    </p>
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.code"
                                    />
                                </div>

                                <!-- Warehouse Name -->
                                <div>
                                    <Label for="name">Tên kho</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="VD: Kho trung tâm Hà Nội, Warehouse A"
                                        required
                                    />
                                    <p class="mt-1 text-sm text-gray-500">
                                        Tên mô tả cho kho hàng của bạn
                                    </p>
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.name"
                                    />
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
                                    class="flex items-center justify-end space-x-4 border-t border-gray-200 pt-4"
                                >
                                    <Button
                                        variant="outline"
                                        as="Link"
                                        :href="
                                            siteRoute.warehouses.index.url(
                                                site.slug,
                                            )
                                        "
                                    >
                                        Hủy
                                    </Button>

                                    <Button
                                        type="submit"
                                        :disabled="form.processing"
                                    >
                                        <span v-if="form.processing"
                                            >Đang tạo...</span
                                        >
                                        <span v-else>Tạo kho</span>
                                    </Button>
                                </div>
                            </form>
                        </div>

                        <!-- Help Section -->
                        <div class="mt-8 rounded-lg bg-indigo-50 p-6">
                            <h3
                                class="mb-2 text-sm font-medium text-indigo-900"
                            >
                                💡 Gợi ý
                            </h3>
                            <ul class="space-y-1 text-sm text-indigo-800">
                                <li>
                                    • Mã kho nên ngắn gọn và dễ nhớ (VD: W001,
                                    MAIN, KHO-HN)
                                </li>
                                <li>
                                    • Tên kho nên mô tả rõ ràng vị trí hoặc chức
                                    năng của kho
                                </li>
                                <li>
                                    • Địa chỉ nên đầy đủ để dễ dàng vận chuyển
                                    và quản lý
                                </li>
                                <li>
                                    • Sau khi tạo kho, bạn có thể thêm các vị
                                    trí lưu trữ bên trong
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
