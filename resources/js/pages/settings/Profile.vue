<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { cn } from '@/lib/utils';
import { edit, update } from '@/routes/profile';
import { send } from '@/routes/verification';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { CheckIcon, ChevronsUpDownIcon } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

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

const page = usePage();
const user = page.props.auth.user;
const profileAddress = page.props.address;

const form = useForm({
    ...update.form(),
    name: user.name,
    email: user.email,
    address: profileAddress ? profileAddress.address : '',
    province_id: profileAddress ? String(profileAddress.ward.province.id) : '',
    ward_id: profileAddress ? String(profileAddress.ward.id) : '',
});

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Cài đặt tài khoản',
        href: edit().url,
    },
];

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

const onProvinceSelect = (provinceId: string) => {
    form.province_id = form.province_id === provinceId ? '' : provinceId;

    form.ward_id = '';
    wards.value = [];

    if (form.province_id) {
        fetchWards(Number(form.province_id));
    }

    open.value = false;
};

const open = ref(false);
const openWard = ref(false);

const onWardSelect = (wardId: string) => {
    form.ward_id = form.ward_id === wardId ? '' : wardId;
    openWard.value = false;
};

const submitForm = (): void => {
    form.patch(update().url);
};

onMounted(() => {
    fetchProvinces();
    if (form.province_id) {
        fetchWards(Number(form.province_id));
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

                <form @submit.prevent="submitForm" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Tên</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            class="mt-1 block w-full"
                            name="name"
                            required
                            autocomplete="name"
                            placeholder="Họ và tên"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Địa chỉ email</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            required
                            autocomplete="username"
                            placeholder="Địa chỉ email"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="address">Địa chỉ</Label>
                        <Input
                            id="address"
                            type="text"
                            class="mt-1 block w-full"
                            name="address"
                            required
                            autocomplete="address"
                            placeholder="Địa chỉ"
                            v-model="form.address"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.address"
                        />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="province_id">Tỉnh/Thành phố</Label>
                            <Popover v-model:open="open">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        role="combobox"
                                        :aria-expanded="open"
                                        class="w-full justify-between font-normal"
                                    >
                                        {{
                                            form.province_id
                                                ? provinces.find(
                                                      (p) =>
                                                          String(p.id) ===
                                                          form.province_id,
                                                  )?.name
                                                : 'Chọn tỉnh/thành phố'
                                        }}
                                        <ChevronsUpDownIcon
                                            class="ml-2 h-4 w-4 shrink-0 opacity-50"
                                        />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    class="w-[--radix-popover-trigger-width] p-0"
                                >
                                    <Command>
                                        <CommandInput
                                            placeholder="Tìm kiếm tỉnh..."
                                        />
                                        <CommandList>
                                            <CommandEmpty
                                                >Không tìm thấy kết
                                                quả.</CommandEmpty
                                            >
                                            <CommandGroup>
                                                <CommandItem
                                                    v-for="province in provinces"
                                                    :key="province.id"
                                                    :value="province.name"
                                                    @select="
                                                        onProvinceSelect(
                                                            String(province.id),
                                                        )
                                                    "
                                                >
                                                    <CheckIcon
                                                        :class="
                                                            cn(
                                                                'mr-2 h-4 w-4',
                                                                form.province_id ===
                                                                    String(
                                                                        province.id,
                                                                    )
                                                                    ? 'opacity-100'
                                                                    : 'opacity-0',
                                                            )
                                                        "
                                                    />
                                                    {{ province.name }}
                                                </CommandItem>
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            <InputError :message="form.errors.province_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="ward_id">Phường/Xã</Label>
                            <Popover v-model:open="openWard">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        role="combobox"
                                        :aria-expanded="openWard"
                                        :disabled="
                                            !form.province_id ||
                                            wards.length === 0
                                        "
                                        class="w-full justify-between font-normal"
                                    >
                                        {{
                                            form.ward_id
                                                ? wards.find(
                                                      (w) =>
                                                          String(w.id) ===
                                                          form.ward_id,
                                                  )?.name
                                                : 'Chọn phường/xã'
                                        }}
                                        <ChevronsUpDownIcon
                                            class="ml-2 h-4 w-4 shrink-0 opacity-50"
                                        />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    class="w-[--radix-popover-trigger-width] p-0"
                                >
                                    <Command>
                                        <CommandInput
                                            placeholder="Tìm kiếm phường/xã..."
                                        />
                                        <CommandList>
                                            <CommandEmpty>
                                                {{
                                                    wards.length === 0
                                                        ? 'Vui lòng chọn Tỉnh/Thành phố trước.'
                                                        : 'Không tìm thấy kết quả.'
                                                }}
                                            </CommandEmpty>
                                            <CommandGroup>
                                                <CommandItem
                                                    v-for="ward in wards"
                                                    :key="ward.id"
                                                    :value="ward.name"
                                                    @select="
                                                        onWardSelect(
                                                            String(ward.id),
                                                        )
                                                    "
                                                >
                                                    <CheckIcon
                                                        :class="
                                                            cn(
                                                                'mr-2 h-4 w-4',
                                                                form.ward_id ===
                                                                    String(
                                                                        ward.id,
                                                                    )
                                                                    ? 'opacity-100'
                                                                    : 'opacity-0',
                                                            )
                                                        "
                                                    />
                                                    {{ ward.name }}
                                                </CommandItem>
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            <InputError :message="form.errors.ward_id" />
                        </div>
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Địa chỉ email của bạn chưa được xác minh.
                            <Link
                                :href="send().url"
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
                            type="submit"
                            :disabled="form.processing"
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
                                v-show="form.recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Đã lưu.
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
