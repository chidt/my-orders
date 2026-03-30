<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatVnd } from '@/lib/utils';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

interface Site {
    id: number;
    slug: string;
    name: string;
}

interface StatusOption {
    value: string;
    label: string;
}

interface DetailRow {
    id: number;
    status: number;
    status_label: string;
    qty: number;
    price: number;
    discount: number;
    addition_price: number;
    total: number;
    note: string | null;
    can_update: boolean;
    allowed_status_values: number[];
    product_item: {
        id: number | null;
        name: string | null;
        sku: string | null;
        product_name: string | null;
    };
}

interface OrderPayload {
    id: number;
    order_number: string;
    order_date: string;
    status: number;
    status_label: string;
    sale_channel: number;
    shipping_payer: number;
    order_note: string | null;
    shipping_note: string | null;
    customer: {
        id: number | null;
        name: string | null;
        phone: string | null;
    };
    shipping_address: {
        id: number;
        address: string;
        ward: string | null;
        province: string | null;
    } | null;
    details: DetailRow[];
}

const props = defineProps<{
    site: Site;
    order: OrderPayload;
    statusOptions: StatusOption[];
}>();

const page = usePage<{ flash?: { success?: string; error?: string; message?: string } }>();

const updateForms = reactive(
    Object.fromEntries(
        props.order.details.map((detail) => [
            detail.id,
            { status: String(detail.status), note: detail.note ?? '', processing: false },
        ]),
    ) as Record<number, { status: string; note: string; processing: boolean }>,
);

const syncUpdateForms = (details: DetailRow[]) => {
    const detailIds = new Set(details.map((detail) => detail.id));

    for (const detail of details) {
        if (!updateForms[detail.id]) {
            updateForms[detail.id] = {
                status: String(detail.status),
                note: detail.note ?? '',
                processing: false,
            };
            continue;
        }

        updateForms[detail.id].status = String(detail.status);
        updateForms[detail.id].note = detail.note ?? '';
    }

    for (const formId of Object.keys(updateForms).map(Number)) {
        if (!detailIds.has(formId)) {
            delete updateForms[formId];
        }
    }
};

watch(
    () => props.order.details,
    (details) => {
        syncUpdateForms(details);
    },
    { immediate: true },
);

const updateDetailStatus = (detail: DetailRow) => {
    const form = updateForms[detail.id];
    form.processing = true;
    router.patch(
        `/${props.site.slug}/orders/${props.order.id}/details/${detail.id}/status`,
        { status: form.status, note: form.note },
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({
                    only: ['order'],
                    preserveScroll: true,
                });
            },
            onFinish: () => {
                form.processing = false;
            },
        },
    );
};

const deleteOrder = () => {
    if (!window.confirm('Bạn có chắc muốn xóa đơn hàng này?')) {
        return;
    }

    router.delete(`/${props.site.slug}/orders/${props.order.id}`);
};
</script>

<template>
    <Head :title="`Đơn hàng ${order.order_number}`" />

    <AppLayout
        :breadcrumbs="[
            { title: site.name, href: `/${site.slug}/dashboard`, current: false },
            { title: 'Quản lý đơn hàng', href: `/${site.slug}/orders`, current: false },
            { title: order.order_number, href: `/${site.slug}/orders/${order.id}`, current: true },
        ]"
    >
        <div class="space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ order.order_number }}</h1>
                    <p class="text-sm text-gray-600">
                        Trạng thái đơn hàng: <span class="font-semibold">{{ order.status_label }}</span>
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button :as="Link" :href="`/${site.slug}/orders/${order.id}/edit`" variant="outline">Sửa</Button>
                    <Button variant="destructive" @click="deleteOrder">Xóa</Button>
                    <Button :as="Link" :href="`/${site.slug}/orders`" variant="outline">Quay lại danh sách</Button>
                </div>
            </div>

            <div v-if="page.props.flash?.success || page.props.flash?.message" class="rounded-md border border-green-200 bg-green-50 px-4 py-3">
                <p class="text-sm font-medium text-green-800">
                    {{ page.props.flash?.success || page.props.flash?.message }}
                </p>
            </div>
            <div v-if="page.props.flash?.error" class="rounded-md border border-red-200 bg-red-50 px-4 py-3">
                <p class="text-sm font-medium text-red-800">
                    {{ page.props.flash.error }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 rounded-lg border bg-white p-4 md:grid-cols-3">
                <div>
                    <p class="text-xs text-gray-500">Khách hàng</p>
                    <p class="text-sm font-medium">{{ order.customer.name }}</p>
                    <p class="text-sm text-gray-600">{{ order.customer.phone }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Địa chỉ giao hàng</p>
                    <p class="text-sm">
                        {{ order.shipping_address?.address }}
                        {{ order.shipping_address?.ward ? `, ${order.shipping_address.ward}` : '' }}
                        {{ order.shipping_address?.province ? `, ${order.shipping_address.province}` : '' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Ngày đơn hàng</p>
                    <p class="text-sm">{{ order.order_date }}</p>
                </div>
            </div>

            <div class="rounded-lg border bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1100px] divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Sản phẩm</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">SKU</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase">SL</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Giá</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Tổng</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Trạng thái</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Ghi chú</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="detail in order.details" :key="detail.id">
                                <td class="px-4 py-3 text-sm">
                                    {{ detail.product_item.product_name ? `${detail.product_item.product_name} - ` : '' }}
                                    {{ detail.product_item.name }}
                                </td>
                                <td class="px-4 py-3 text-sm">{{ detail.product_item.sku }}</td>
                                <td class="px-4 py-3 text-right text-sm">{{ detail.qty }}</td>
                                <td class="px-4 py-3 text-right text-sm">{{ formatVnd(detail.price) }}</td>
                                <td class="px-4 py-3 text-right text-sm font-medium">{{ formatVnd(detail.total) }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <select
                                        v-model="updateForms[detail.id].status"
                                        class="h-9 rounded-md border px-2 text-sm"
                                        :disabled="!detail.can_update || updateForms[detail.id].processing"
                                    >
                                        <option
                                            v-for="option in statusOptions.filter(
                                                (statusOption) => detail.allowed_status_values.includes(Number(statusOption.value)),
                                            )"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <Input
                                        v-model="updateForms[detail.id].note"
                                        :disabled="!detail.can_update || updateForms[detail.id].processing"
                                    />
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Button
                                        size="sm"
                                        :disabled="!detail.can_update || updateForms[detail.id].processing"
                                        @click="updateDetailStatus(detail)"
                                    >
                                        Cập nhật
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
