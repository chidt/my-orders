<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatVnd } from '@/lib/utils';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

interface Site {
    id: number;
    slug: string;
    name: string;
}

interface Option {
    value: string;
    label: string;
}

const props = defineProps<{
    site: Site;
    orderDetail: any;
    statusOptions: Option[];
    paymentStatusOptions: Option[];
}>();

const page = usePage<{ flash?: { success?: string; error?: string; message?: string } }>();

const updateForm = reactive({
    status: String(props.orderDetail.status.value),
    payment_status: String(props.orderDetail.payment_status.value),
});

watch(
    () => [props.orderDetail.status.value, props.orderDetail.payment_status.value] as const,
    ([statusVal, payVal]) => {
        updateForm.status = String(statusVal);
        updateForm.payment_status = String(payVal);
    },
);

const updateStatus = () => {
    router.patch(
        `/${props.site.slug}/order-details/${props.orderDetail.id}/status`,
        { status: updateForm.status },
        { preserveScroll: true },
    );
};

const updatePaymentStatus = () => {
    router.patch(
        `/${props.site.slug}/order-details/${props.orderDetail.id}/payment-status`,
        { payment_status: updateForm.payment_status },
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head :title="`OrderDetail #${orderDetail.id}`" />

    <AppLayout
        :breadcrumbs="[
            { title: site.name, href: `/${site.slug}/dashboard`, current: false },
            { title: 'Chi tiết đơn hàng', href: `/${site.slug}/order-details`, current: false },
            { title: `OrderDetail #${orderDetail.id}`, href: `/${site.slug}/order-details/${orderDetail.id}`, current: true },
        ]"
    >
        <div class="space-y-4 px-4 py-8 sm:px-6 lg:px-8">
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

            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">OrderDetail #{{ orderDetail.id }}</h1>
                <Button :as="Link" :href="`/${site.slug}/order-details`" variant="outline">
                    Quay lại danh sách
                </Button>
            </div>

            <div class="grid grid-cols-1 gap-4 rounded-lg border bg-white p-4 md:grid-cols-3">
                <div>
                    <p class="text-xs text-gray-500">Order Number</p>
                    <p class="text-sm font-medium">{{ orderDetail.order.order_number }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Ngày đơn</p>
                    <p class="text-sm">{{ orderDetail.order.order_date }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Trạng thái Order</p>
                    <p class="text-sm">{{ orderDetail.order.status }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 rounded-lg border bg-white p-4 md:grid-cols-2">
                <div>
                    <h2 class="text-base font-semibold">Thông tin khách hàng</h2>
                    <p class="mt-2 text-sm">{{ orderDetail.customer.name }}</p>
                    <p class="text-sm text-gray-600">{{ orderDetail.customer.phone || '-' }}</p>
                    <p class="text-sm text-gray-600">{{ orderDetail.customer.email || '-' }}</p>
                    <p class="mt-2 text-sm text-gray-700">
                        {{ orderDetail.shipping_address?.address || '-' }}
                        {{ orderDetail.shipping_address?.ward ? `, ${orderDetail.shipping_address.ward}` : '' }}
                        {{ orderDetail.shipping_address?.province ? `, ${orderDetail.shipping_address.province}` : '' }}
                    </p>
                </div>

                <div>
                    <h2 class="text-base font-semibold">Thông tin sản phẩm</h2>
                    <p class="mt-2 text-sm">{{ orderDetail.product.name }}</p>
                    <p class="text-sm text-gray-700">{{ orderDetail.product_item.name }} | {{ orderDetail.product_item.sku }}</p>
                    <p class="text-sm text-gray-600">Loại: {{ orderDetail.product.type || '-' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 rounded-lg border bg-white p-4 md:grid-cols-5">
                <div>
                    <p class="text-xs text-gray-500">Số lượng</p>
                    <p class="text-sm font-medium">{{ orderDetail.pricing.qty }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Giá</p>
                    <p class="text-sm">{{ formatVnd(orderDetail.pricing.price) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Chiết khấu</p>
                    <p class="text-sm">{{ formatVnd(orderDetail.pricing.discount) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Phụ phí</p>
                    <p class="text-sm">{{ formatVnd(orderDetail.pricing.addition_price) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Thành tiền</p>
                    <p class="text-sm font-semibold">{{ formatVnd(orderDetail.pricing.total) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 rounded-lg border bg-white p-4 md:grid-cols-2">
                <div class="space-y-2">
                    <h2 class="text-base font-semibold">Cập nhật trạng thái</h2>
                    <select
                        v-model="updateForm.status"
                        class="h-10 w-full rounded-md border px-3 text-sm"
                        :disabled="!orderDetail.status.can_update"
                    >
                        <option
                            v-for="statusOption in statusOptions.filter((status) => orderDetail.status.allowed_status_values.includes(Number(status.value)))"
                            :key="statusOption.value"
                            :value="statusOption.value"
                        >
                            {{ statusOption.label }}
                        </option>
                    </select>
                    <Button :disabled="!orderDetail.status.can_update" @click="updateStatus">Cập nhật trạng thái</Button>
                </div>

                <div class="space-y-2">
                    <h2 class="text-base font-semibold">Cập nhật thanh toán</h2>
                    <select v-model="updateForm.payment_status" class="h-10 w-full rounded-md border px-3 text-sm">
                        <option v-for="paymentOption in paymentStatusOptions" :key="paymentOption.value" :value="paymentOption.value">
                            {{ paymentOption.label }}
                        </option>
                    </select>
                    <Button @click="updatePaymentStatus">Cập nhật thanh toán</Button>
                </div>
            </div>

            <div class="rounded-lg border bg-white p-4">
                <h2 class="text-base font-semibold">Status history</h2>
                <div class="mt-3 space-y-2">
                    <div
                        v-for="(item, index) in orderDetail.status_history"
                        :key="index"
                        class="rounded-md border border-gray-200 px-3 py-2 text-sm"
                    >
                        <p class="font-medium">{{ item.title }} - {{ item.status }}</p>
                        <p class="text-gray-600">{{ item.at }}</p>
                        <p v-if="item.note" class="text-gray-700">Ghi chú: {{ item.note }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border bg-white p-4">
                <h2 class="text-base font-semibold">Ghi chú</h2>
                <p class="mt-2 text-sm">OrderDetail note: {{ orderDetail.notes.order_detail_note || '-' }}</p>
                <p class="mt-1 text-sm">Order note: {{ orderDetail.notes.order_note || '-' }}</p>
                <p class="mt-1 text-sm">Shipping note: {{ orderDetail.notes.shipping_note || '-' }}</p>
            </div>
        </div>
    </AppLayout>
</template>
