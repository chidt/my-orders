<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { CheckCircle, Info } from 'lucide-vue-next';
import { ref } from 'vue';

import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Site } from '@/types/auth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';

interface Props {
    site: Site;
    status?: string;
}

const props = defineProps<Props>();

const form = useForm({
    name: props.site.name,
    slug: props.site.slug,
    description: props.site.description || '',
    settings: {
        product_prefix: props.site.settings?.product_prefix || ''
    }
});

const productPrefixExample = ref('');

const updateProductPrefixExample = () => {
    const prefix = form.settings.product_prefix;
    if (prefix) {
        productPrefixExample.value = `${prefix}001`;
    } else {
        productPrefixExample.value = '001';
    }
};

// Initialize example
updateProductPrefixExample();

const submit = () => {
    form.put(route('site.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success is handled by status prop
        }
    });
};
</script>

<template>
    <Head title="Quản lý trang web" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Success Alert -->
                <Alert v-if="status" class="border-green-200 bg-green-50">
                    <CheckCircle class="h-4 w-4 text-green-600" />
                    <AlertDescription class="text-green-800">
                        {{ status }}
                    </AlertDescription>
                </Alert>

                <!-- Site Information Card -->
                <Card>
                    <CardHeader>
                        <CardTitle>Thông tin trang web</CardTitle>
                        <CardDescription>
                            Cập nhật thông tin cơ bản cho trang web của bạn
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Site Name -->
                            <div class="space-y-2">
                                <Label for="name">Tên trang web</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    :class="{ 'border-red-500': form.errors.name }"
                                    required
                                />
                                <div v-if="form.errors.name" class="text-sm text-red-600">
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <!-- Site Slug -->
                            <div class="space-y-2">
                                <Label for="slug">Slug trang web</Label>
                                <Input
                                    id="slug"
                                    v-model="form.slug"
                                    type="text"
                                    class="mt-1 block w-full"
                                    :class="{ 'border-red-500': form.errors.slug }"
                                    required
                                />
                                <div class="text-sm text-gray-600">
                                    Chỉ được chứa chữ cái thường, số và dấu gạch ngang. Ví dụ: my-awesome-site
                                </div>
                                <div v-if="form.errors.slug" class="text-sm text-red-600">
                                    {{ form.errors.slug }}
                                </div>
                            </div>

                            <!-- Site Description -->
                            <div class="space-y-2">
                                <Label for="description">Mô tả trang web</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    class="mt-1 block w-full"
                                    :class="{ 'border-red-500': form.errors.description }"
                                    rows="3"
                                    placeholder="Mô tả ngắn gọn về trang web của bạn..."
                                />
                                <div v-if="form.errors.description" class="text-sm text-red-600">
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <!-- Product Settings -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-medium">Cài đặt sản phẩm</h3>

                                <div class="space-y-2">
                                    <Label for="product_prefix">Tiền tố mã sản phẩm</Label>
                                    <Input
                                        id="product_prefix"
                                        v-model="form.settings.product_prefix"
                                        type="text"
                                        class="mt-1 block w-full"
                                        :class="{ 'border-red-500': form.errors['settings.product_prefix'] }"
                                        @input="updateProductPrefixExample"
                                        placeholder="A"
                                        maxlength="5"
                                    />
                                    <div class="text-sm text-gray-600">
                                        <div class="flex items-center gap-1">
                                            <Info class="h-4 w-4" />
                                            <span>Ví dụ: "{{ form.settings.product_prefix || 'A' }}" → "{{ productPrefixExample }}"</span>
                                        </div>
                                        <div class="mt-1">
                                            Chỉ được chứa chữ cái in hoa và số, tối đa 5 ký tự
                                        </div>
                                    </div>
                                    <div v-if="form.errors['settings.product_prefix']" class="text-sm text-red-600">
                                        {{ form.errors['settings.product_prefix'] }}
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-4 pt-6">
                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="bg-blue-600 hover:bg-blue-700 text-white"
                                >
                                    <span v-if="form.processing">Đang lưu...</span>
                                    <span v-else>Lưu thay đổi</span>
                                </Button>

                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="form.reset()"
                                    :disabled="form.processing"
                                >
                                    Hủy
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
