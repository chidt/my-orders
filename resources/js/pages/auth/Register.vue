<script setup lang="ts">
import { Form, Head, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register'   ;

const form = useForm(store.form());

const generateSlug = (): void => {
    if (!form.site_name) {
        form.site_slug = '';
        return;
    }

    const slug = form.site_name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');

    form.site_slug = slug;
};

watch(() => form.site_name, generateSlug);
</script>

<template>
    <AuthBase
        title="Tạo tài khoản"
        description="Nhập thông tin chi tiết của bạn bên dưới để tạo tài khoản"
    >
        <Head title="Đăng ký" />

        <Form
            :form="form"
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Thông tin cá nhân</h2>
                <div class="grid gap-2">
                    <Label for="name">Tên</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="Họ và tên"
                        v-model="form.name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Địa chỉ email</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="email@example.com"
                        v-model="form.email"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone_number">Số điện thoại</Label>
                    <Input
                        id="phone_number"
                        type="text"
                        required
                        :tabindex="3"
                        autocomplete="tel"
                        name="phone_number"
                        placeholder="Số điện thoại"
                        v-model="form.phone_number"
                    />
                    <InputError :message="errors.phone_number" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Mật khẩu</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Mật khẩu"
                        v-model="form.password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Xác nhận mật khẩu</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="5"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Xác nhận mật khẩu"
                        v-model="form.password_confirmation"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>
            </div>

            <div class="space-y-4 border-t border-gray-200 pt-6 mt-6">
                <h2 class="text-lg font-semibold text-gray-900">Thông tin cửa hàng</h2>
                <div class="grid gap-2">
                    <Label for="site_name">Tên cửa hàng</Label>
                    <Input
                        id="site_name"
                        type="text"
                        required
                        :tabindex="6"
                        name="site_name"
                        placeholder="Tên cửa hàng"
                        v-model="form.site_name"
                    />
                    <InputError :message="errors.site_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="site_slug">Đường dẫn</Label>
                    <Input
                        id="site_slug"
                        type="text"
                        required
                        :tabindex="7"
                        name="site_slug"
                        placeholder="duong-dan"
                        v-model="form.site_slug"
                    />
                    <InputError :message="errors.site_slug" />
                </div>

                <div class="grid gap-2">
                    <Label for="site_description">Mô tả</Label>
                    <Textarea
                        id="site_description"
                        :tabindex="8"
                        name="site_description"
                        placeholder="Mô tả ngắn về cửa hàng của bạn"
                        v-model="form.site_description"
                    />
                    <InputError :message="errors.site_description" />
                </div>
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="9"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Tạo tài khoản
            </Button>

            <div class="text-center text-sm text-muted-foreground">
                Đã có tài khoản?
                <TextLink
                    :href="login()"
                    class="underline underline-offset-4"
                    :tabindex="10"
                    >Đăng nhập</TextLink
                >
            </div>
        </Form>
    </AuthBase>
</template>
