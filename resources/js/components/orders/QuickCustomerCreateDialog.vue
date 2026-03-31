<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

type CustomerAddress = {
    id: number;
    address: string;
    is_default: boolean;
    ward: string | null;
    province: string | null;
};

type CustomerOption = {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    type: number;
    addresses: CustomerAddress[];
};

const props = defineProps<{
    siteSlug: string;
    customerTypes: Record<number, string>;
    provinces: Array<{ id: number; name: string }>;
}>();

const isOpen = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    created: [customer: CustomerOption];
}>();

const form = ref({
    name: '',
    phone: '',
    email: '',
    type: '1',
    address: '',
    province_id: '',
    ward_id: '',
    description: '',
});

const submitting = ref(false);
const errors = ref<Record<string, string>>({});
const generalError = ref('');
const wards = ref<Array<{ id: number; name: string }>>([]);
const loadingWards = ref(false);

const canSubmit = computed(() => {
    return Boolean(
        form.value.name.trim() &&
            form.value.phone.trim() &&
            form.value.email.trim() &&
            form.value.type &&
            form.value.address.trim() &&
            form.value.ward_id,
    );
});

function resetForm(): void {
    form.value = {
        name: '',
        phone: '',
        email: '',
        type: '1',
        address: '',
        province_id: '',
        ward_id: '',
        description: '',
    };
    errors.value = {};
    generalError.value = '';
    wards.value = [];
    loadingWards.value = false;
}

watch(
    () => isOpen.value,
    (open) => {
        if (open) {
            resetForm();
        }
    },
);

watch(
    () => form.value.province_id,
    async (provinceId) => {
        form.value.ward_id = '';
        wards.value = [];
        if (!provinceId) {
            return;
        }

        loadingWards.value = true;
        try {
            const response = await axios.get<Array<{ id: number; name: string }>>(`/api/provinces/${provinceId}/wards`);
            wards.value = response.data;
        } finally {
            loadingWards.value = false;
        }
    },
);

function onlyDigitsPhone(): void {
    form.value.phone = form.value.phone.replace(/\D/g, '').slice(0, 11);
}

async function submit(): Promise<void> {
    submitting.value = true;
    errors.value = {};
    generalError.value = '';

    try {
        const response = await fetch(`/${props.siteSlug}/orders/customers/quick-store`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie
                        .split('; ')
                        .find((row) => row.startsWith('XSRF-TOKEN='))
                        ?.split('=')
                        .slice(1)
                        .join('=') ?? '',
                ),
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                name: form.value.name.trim(),
                phone: form.value.phone.trim(),
                email: form.value.email.trim(),
                type: Number(form.value.type),
                description: form.value.description.trim() || null,
                address: form.value.address.trim(),
                ward_id: Number(form.value.ward_id),
                addresses: [
                    {
                        address: form.value.address.trim(),
                        ward_id: Number(form.value.ward_id),
                        is_default: 1,
                    },
                ],
            }),
        });

        const data = (await response.json().catch(() => ({}))) as {
            message?: string;
            errors?: Record<string, string[]>;
            customer?: CustomerOption;
        };

        if (response.status === 422 && data.errors) {
            const mapped: Record<string, string> = {};
            for (const [key, messages] of Object.entries(data.errors)) {
                mapped[key] = Array.isArray(messages) ? messages[0] : String(messages);
            }
            errors.value = mapped;
            return;
        }

        if (!response.ok || !data.customer) {
            generalError.value = data.message ?? 'Không thể tạo khách hàng.';
            return;
        }

        emit('created', data.customer);
        isOpen.value = false;
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Tạo khách hàng nhanh</DialogTitle>
            </DialogHeader>

            <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submit">
                <div class="md:col-span-2">
                    <Label for="quick-customer-name">Tên khách hàng *</Label>
                    <Input id="quick-customer-name" v-model="form.name" class="mt-1" />
                    <InputError class="mt-1" :message="errors.name" />
                </div>

                <div>
                    <Label for="quick-customer-phone">Số điện thoại *</Label>
                    <Input
                        id="quick-customer-phone"
                        v-model="form.phone"
                        class="mt-1"
                        inputmode="numeric"
                        maxlength="11"
                        @input="onlyDigitsPhone"
                    />
                    <InputError class="mt-1" :message="errors.phone" />
                </div>

                <div>
                    <Label for="quick-customer-email">Email *</Label>
                    <Input id="quick-customer-email" v-model="form.email" type="email" class="mt-1" />
                    <InputError class="mt-1" :message="errors.email" />
                </div>

                <div>
                    <Label for="quick-customer-type">Loại khách hàng *</Label>
                    <select
                        id="quick-customer-type"
                        v-model="form.type"
                        class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option v-for="(label, key) in customerTypes" :key="key" :value="String(key)">
                            {{ label }}
                        </option>
                    </select>
                    <InputError class="mt-1" :message="errors.type" />
                </div>

                <div>
                    <Label for="quick-customer-province">Tỉnh/Thành phố *</Label>
                    <select
                        id="quick-customer-province"
                        v-model="form.province_id"
                        class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="">Chọn tỉnh/thành</option>
                        <option v-for="province in provinces" :key="province.id" :value="String(province.id)">
                            {{ province.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <Label for="quick-customer-ward">Phường/Xã *</Label>
                    <select
                        id="quick-customer-ward"
                        v-model="form.ward_id"
                        class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        :disabled="!form.province_id || loadingWards"
                    >
                        <option value="">{{ loadingWards ? 'Đang tải...' : 'Chọn phường/xã' }}</option>
                        <option v-for="ward in wards" :key="ward.id" :value="String(ward.id)">
                            {{ ward.name }}
                        </option>
                    </select>
                    <InputError class="mt-1" :message="errors.ward_id" />
                </div>

                <div class="md:col-span-2">
                    <Label for="quick-customer-address">Địa chỉ *</Label>
                    <Input id="quick-customer-address" v-model="form.address" class="mt-1" />
                    <InputError class="mt-1" :message="errors.address" />
                </div>

                <div class="md:col-span-2">
                    <Label for="quick-customer-description">Mô tả</Label>
                    <Input id="quick-customer-description" v-model="form.description" class="mt-1" />
                </div>

                <p v-if="generalError" class="md:col-span-2 text-sm text-red-600">
                    {{ generalError }}
                </p>

                <DialogFooter class="md:col-span-2 mt-2 gap-2 sm:gap-0">
                    <Button type="button" variant="outline" :disabled="submitting" @click="isOpen = false">
                        Hủy
                    </Button>
                    <Button type="submit" :disabled="submitting || !canSubmit">
                        {{ submitting ? 'Đang lưu...' : 'Tạo khách hàng' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
