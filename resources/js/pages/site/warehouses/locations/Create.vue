<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
        }
    );
}

// Navigation
function goBack() {
    window.history.back();
}
</script>

<template>
    <Head>
        <title>Thêm vị trí mới - {{ warehouse.name }} - {{ site.name }}</title>
    </Head>

    <AppLayout>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <nav class="flex mb-4" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                            <li class="inline-flex items-center">
                                <Link
                                    :href="siteRoute.dashboard.url(site.slug)"
                                    class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600"
                                >
                                    {{ site.name }}
                                </Link>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <span class="mx-2 text-gray-400">/</span>
                                    <Link
                                        :href="siteRoute.warehouses.index.url(site.slug)"
                                        class="text-sm font-medium text-gray-700 hover:text-blue-600"
                                    >
                                        Kho hàng
                                    </Link>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <span class="mx-2 text-gray-400">/</span>
                                    <Link
                                        :href="siteRoute.warehouses.show.url([site.slug, warehouse.id])"
                                        class="text-sm font-medium text-gray-700 hover:text-blue-600"
                                    >
                                        {{ warehouse.name }}
                                    </Link>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <span class="mx-2 text-gray-400">/</span>
                                    <Link
                                        :href="siteRoute.warehouses.locations.index.url([site.slug, warehouse.id])"
                                        class="text-sm font-medium text-gray-700 hover:text-blue-600"
                                    >
                                        Vị trí
                                    </Link>
                                </div>
                            </li>
                            <li aria-current="page">
                                <div class="flex items-center">
                                    <span class="mx-2 text-gray-400">/</span>
                                    <span class="text-sm font-medium text-gray-500">Thêm mới</span>
                                </div>
                            </li>
                        </ol>
                    </nav>

                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Thêm vị trí mới</h1>
                            <p class="mt-2 text-gray-600">{{ warehouse.name }} - {{ warehouse.code }}</p>
                            <p class="text-sm text-gray-500">{{ warehouse.address }}</p>
                        </div>

                        <Button
                            variant="outline"
                            @click="goBack"
                            class="flex items-center gap-2"
                        >
                            <ArrowLeft class="w-4 h-4" />
                            Quay lại
                        </Button>
                    </div>
                </div>

                <!-- Form -->
                <Card>
                    <CardHeader>
                        <CardTitle>Thông tin vị trí</CardTitle>
                        <CardDescription>
                            Nhập thông tin cho vị trí mới trong kho {{ warehouse.name }}.
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Code -->
                            <div class="space-y-2">
                                <Label for="code">
                                    Mã vị trí <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="code"
                                    v-model="form.code"
                                    type="text"
                                    placeholder="Ví dụ: A01, B-15, ZONE-1"
                                    :class="{ 'border-red-500': form.errors.code }"
                                    maxlength="50"
                                    required
                                />
                                <p v-if="form.errors.code" class="text-sm text-red-600">
                                    {{ form.errors.code }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    Mã vị trí phải duy nhất trong kho này. Chỉ sử dụng chữ cái in hoa, số và dấu gạch ngang.
                                </p>
                            </div>

                            <!-- Name -->
                            <div class="space-y-2">
                                <Label for="name">
                                    Tên vị trí <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Ví dụ: Kệ A01, Vùng lưu trữ B, Khu vực đặc biệt"
                                    :class="{ 'border-red-500': form.errors.name }"
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
                                <p v-if="form.errors.is_default" class="text-sm text-red-600">
                                    {{ form.errors.is_default }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    Vị trí mặc định sẽ được sử dụng khi không chỉ định vị trí cụ thể.
                                    Mỗi kho chỉ có một vị trí mặc định.
                                </p>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="flex justify-end space-x-4 pt-6 border-t">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="goBack"
                                    :disabled="form.processing"
                                >
                                    Hủy
                                </Button>
                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex items-center gap-2"
                                >
                                    <Save class="w-4 h-4" />
                                    {{ form.processing ? 'Đang lưu...' : 'Tạo vị trí' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Help Section -->
                <Card class="mt-6">
                    <CardHeader>
                        <CardTitle class="text-lg">Hướng dẫn</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm text-gray-600">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Quy tắc đặt mã vị trí:</h4>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Mã vị trí phải duy nhất trong kho này</li>
                                <li>Chỉ sử dụng chữ cái in hoa (A-Z), số (0-9) và dấu gạch ngang (-)</li>
                                <li>Tối đa 50 ký tự</li>
                                <li>Ví dụ: A01, B-15, ZONE-1, SPECIAL-AREA</li>
                            </ul>
                        </div>

                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Về vị trí mặc định:</h4>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Mỗi kho phải có ít nhất một vị trí mặc định</li>
                                <li>Nếu không có vị trí nào là mặc định, vị trí đầu tiên sẽ tự động trở thành mặc định</li>
                                <li>Khi đặt vị trí này làm mặc định, vị trí mặc định hiện tại sẽ bị bỏ</li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
