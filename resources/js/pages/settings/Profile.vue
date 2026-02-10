<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Select from '@/components/ui/select/Select.vue';
import SelectContent from '@/components/ui/select/SelectContent.vue';
import SelectItem from '@/components/ui/select/SelectItem.vue';
import SelectTrigger from '@/components/ui/select/SelectTrigger.vue';
import SelectValue from '@/components/ui/select/SelectValue.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref, watch } from 'vue';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
    address: {
        id: number;
        user_id: number;
        ward_id: number;
        address: string;
        ward: {
            id: number;
            name: string;
            province_id: number;
            province: {
                id: number;
                name: string;
            };
        };
    } | null;
};

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Cài đặt tài khoản',
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user;
const address = page.props.address;
const defaultAddress = address ? address.address : '';
const defaultProvinceId = address ? String(address.ward.province.id) : '';
const defaultWardId = ref(address ? String(address.ward.id) : '');

const provinces = ref([]);
const wards = ref([]);

const fetchProvinces = async (): Promise<void> => {
    try {
        const response = await axios.get('/api/provinces');
        provinces.value = response.data;
    } catch (error) {
        console.error('Error fetching provinces:', error);
    }
};

const fetchWards = async (provinceId: number): Promise<void> => {
    if (!provinceId) {
        wards.value = [];
        return;
    }

    try {
        const response = await axios.get(`/api/provinces/${provinceId}/wards`);
        wards.value = response.data;
    } catch (error) {
        console.error('Error fetching wards:', error);
    }
};

const onProvinceChange = (provinceId: string): void => {
    defaultWardId.value = '';
    fetchWards(Number(provinceId));
};
onMounted(() => {
    fetchProvinces();
    if (defaultProvinceId) {
        fetchWards(Number(defaultProvinceId));
    }
});

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Cài đặt tài khoản" />

        <h1 class="sr-only">Cài đặt tài khoản</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Thông tin hồ sơ"
                    description="Cập nhật tên và địa chỉ email của bạn"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="name">Tên</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Họ và tên"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Địa chỉ email</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="Địa chỉ email"
                        />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="address">Địa chỉ</Label>
                        <Input
                            id="address"
                            type="text"
                            class="mt-1 block w-full"
                            name="address"
                            :default-value="defaultAddress"
                            required
                            autocomplete="address"
                            placeholder="Địa chỉ"
                        />
                        <InputError class="mt-2" :message="errors.address" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="province_id">Tỉnh/Thành phố</Label>
                            <Select
                                id="province_id"
                                name="province_id"
                                @update:modelValue="onProvinceChange"
                                :default-value="defaultProvinceId"
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Chọn tỉnh/thành phố"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="province in provinces"
                                        :key="province.id"
                                        :value="String(province.id)"
                                    >
                                        {{ province.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.province_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="ward_id">Phường/Xã</Label>
                            <Select
                                id="ward_id"
                                name="ward_id"
                                :default-value="defaultWardId"
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Chọn phường/xã" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="ward in wards"
                                        :key="ward.id"
                                        :value="String(ward.id)"
                                    >
                                        {{ ward.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.ward_id" />
                        </div>
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Địa chỉ email của bạn chưa được xác minh.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Nhấn vào đây để gửi lại email xác minh.
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            Một liên kết xác minh mới đã được gửi đến địa chỉ
                            email của bạn.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                            >Lưu</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Đã lưu.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
