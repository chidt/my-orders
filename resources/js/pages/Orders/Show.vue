<script setup lang="ts">
import ProductThumbnailPreview from '@/components/products/ProductThumbnailPreview.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatVnd } from '@/lib/utils';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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
    status_color: string;
    payment_status: number;
    payment_status_label: string;
    payment_status_color: string;
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
        image?: string | null;
    };
}

interface OrderPayload {
    id: number;
    order_number: string;
    order_date: string;
    status: number;
    status_label: string;
    status_color: string;
    payment_status: number;
    payment_status_label: string;
    payment_status_color: string;
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

const page = usePage<{
    flash?: { success?: string; error?: string; message?: string };
}>();

const getBadgeClass = (color: string): string => {
    const colorMap: Record<string, string> = {
        blue: 'bg-blue-100 text-blue-800',
        yellow: 'bg-yellow-100 text-yellow-800',
        orange: 'bg-orange-100 text-orange-800',
        purple: 'bg-purple-100 text-purple-800',
        cyan: 'bg-cyan-100 text-cyan-800',
        teal: 'bg-teal-100 text-teal-800',
        gray: 'bg-gray-100 text-gray-800',
        indigo: 'bg-indigo-100 text-indigo-800',
        pink: 'bg-pink-100 text-pink-800',
        lime: 'bg-lime-100 text-lime-800',
        green: 'bg-green-100 text-green-800',
        red: 'bg-red-100 text-red-800',
        slate: 'bg-slate-100 text-slate-800',
        violet: 'bg-violet-100 text-violet-800',
        amber: 'bg-amber-100 text-amber-800',
        sky: 'bg-sky-100 text-sky-800',
        emerald: 'bg-emerald-100 text-emerald-800',
    };
    return colorMap[color] || 'bg-gray-100 text-gray-800';
};

const deleteOrder = () => {
    if (!window.confirm('Bạn có chắc muốn xóa đơn hàng này?')) {
        return;
    }

    router.delete(`/${props.site.slug}/orders/${props.order.id}`);
};

const totalQty = computed(() =>
    props.order.details.reduce((sum, detail) => sum + detail.qty, 0),
);

const totalAmount = computed(() =>
    props.order.details.reduce((sum, detail) => sum + detail.total, 0),
);
</script>

<template>
    <Head :title="`Đơn hàng ${order.order_number}`" />

    <AppLayout
        :breadcrumbs="[
            {
                title: site.name,
                href: `/${site.slug}/dashboard`,
                current: false,
            },
            {
                title: 'Quản lý đơn hàng',
                href: `/${site.slug}/orders`,
                current: false,
            },
            {
                title: order.order_number,
                href: `/${site.slug}/orders/${order.id}`,
                current: true,
            },
        ]"
    >
        <div class="space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="mb-1 text-xl font-bold text-gray-900 sm:mb-2 sm:text-2xl"
                    >
                        {{ order.order_number }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs text-gray-600 sm:text-sm"
                            >Trạng thái đơn hàng:</span
                        >
                        <Badge
                            :class="getBadgeClass(order.status_color)"
                            class="text-[10px] sm:text-xs"
                            >{{ order.status_label }}</Badge
                        >
                        <Badge
                            :class="getBadgeClass(order.payment_status_color)"
                            class="text-[10px] sm:text-xs"
                            >{{ order.payment_status_label }}</Badge
                        >
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 pt-2 sm:pt-0">
                    <Button
                        :as="Link"
                        :href="`/${site.slug}/orders/${order.id}/edit`"
                        variant="outline"
                        class="min-h-11 flex-1 text-sm sm:min-h-10 sm:flex-none"
                        >Sửa</Button
                    >
                    <Button
                        variant="destructive"
                        @click="deleteOrder"
                        class="min-h-11 flex-1 text-sm sm:min-h-10 sm:flex-none"
                        >Xóa</Button
                    >
                    <Button
                        :as="Link"
                        :href="`/${site.slug}/orders`"
                        variant="outline"
                        class="min-h-11 w-full text-sm sm:min-h-10 sm:w-auto"
                        >Quay lại</Button
                    >
                </div>
            </div>

            <div
                v-if="page.props.flash?.success || page.props.flash?.message"
                class="rounded-md border border-green-200 bg-green-50 px-4 py-3"
            >
                <p class="text-sm font-medium text-green-800">
                    {{ page.props.flash?.success || page.props.flash?.message }}
                </p>
            </div>
            <div
                v-if="page.props.flash?.error"
                class="rounded-md border border-red-200 bg-red-50 px-4 py-3"
            >
                <p class="text-sm font-medium text-red-800">
                    {{ page.props.flash.error }}
                </p>
            </div>

            <div
                class="grid grid-cols-1 gap-6 rounded-xl border bg-white p-4 shadow-sm md:grid-cols-3"
            >
                <div class="space-y-3">
                    <h2
                        class="border-b pb-2 text-sm font-semibold text-gray-900 sm:text-base"
                    >
                        Thông tin khách hàng
                    </h2>
                    <div
                        class="rounded-lg border border-gray-100 bg-gray-50/50 p-3"
                    >
                        <p
                            class="text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                        >
                            Tên khách hàng
                        </p>
                        <p class="mt-0.5 text-sm font-bold text-gray-900">
                            {{ order.customer.name }}
                        </p>
                        <p class="mt-2 text-sm text-gray-600">
                            <span
                                class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                >Số điện thoại</span
                            >
                            {{ order.customer.phone || '-' }}
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <h2
                        class="border-b pb-2 text-sm font-semibold text-gray-900 sm:text-base"
                    >
                        Địa chỉ nhận hàng
                    </h2>
                    <div
                        class="rounded-lg border border-gray-100 bg-gray-50/50 p-3"
                    >
                        <p class="text-sm leading-relaxed text-gray-700">
                            {{ order.shipping_address?.address }}
                            {{
                                order.shipping_address?.ward
                                    ? `, ${order.shipping_address.ward}`
                                    : ''
                            }}
                            {{
                                order.shipping_address?.province
                                    ? `, ${order.shipping_address.province}`
                                    : ''
                            }}
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <h2
                        class="border-b pb-2 text-sm font-semibold text-gray-900 sm:text-base"
                    >
                        Thông tin bổ sung
                    </h2>
                    <div
                        class="space-y-3 rounded-lg border border-gray-100 bg-gray-50/50 p-3"
                    >
                        <div>
                            <p
                                class="text-[10px] leading-none font-bold tracking-wider text-gray-400 uppercase"
                            >
                                Ngày đặt hàng
                            </p>
                            <p class="mt-1 text-sm font-medium text-gray-900">
                                {{ order.order_date }}
                            </p>
                        </div>
                        <div v-if="order.order_note">
                            <p
                                class="text-[10px] leading-none font-bold tracking-wider text-gray-400 uppercase"
                            >
                                Ghi chú đơn
                            </p>
                            <p class="mt-1 text-xs text-gray-600 italic">
                                {{ order.order_note }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
                <div class="border-b bg-gray-50/50 px-4 py-3">
                    <h2 class="italic-none text-base font-bold text-gray-900">
                        Danh sách sản phẩm chi tiết
                    </h2>
                </div>
                <!-- Desktop Table View (Visible from md up) -->
                <div class="hidden overflow-x-auto md:block">
                    <table
                        class="w-full min-w-[900px] divide-y divide-gray-200"
                    >
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase"
                                >
                                    Sản phẩm
                                </th>
                                <th
                                    class="w-16 px-4 py-3 text-right text-xs font-semibold uppercase"
                                >
                                    SL
                                </th>
                                <th
                                    class="w-32 px-4 py-3 text-right text-xs font-semibold uppercase"
                                >
                                    Giá
                                </th>
                                <th
                                    class="w-32 px-4 py-3 text-right text-xs font-semibold uppercase"
                                >
                                    Tổng
                                </th>
                                <th
                                    class="w-48 px-4 py-3 text-left text-xs font-semibold uppercase"
                                >
                                    Trạng thái
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase"
                                >
                                    Ghi chú
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="detail in order.details"
                                :key="detail.id"
                            >
                                <td class="px-4 py-3 align-top text-sm">
                                    <div class="flex items-center gap-3">
                                        <ProductThumbnailPreview
                                            :src="detail.product_item.image"
                                            :alt="
                                                detail.product_item
                                                    .product_name ??
                                                detail.product_item.name ??
                                                ''
                                            "
                                            size-class="h-12 w-12"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate font-medium">
                                                {{
                                                    detail.product_item
                                                        .product_name ??
                                                    detail.product_item.name
                                                }}
                                            </div>
                                            <div
                                                class="text-xs text-gray-500"
                                                v-if="detail.product_item.sku"
                                            >
                                                SKU:
                                                {{ detail.product_item.sku }}
                                            </div>
                                            <div
                                                class="text-xs text-gray-500"
                                                v-if="
                                                    detail.product_item
                                                        .product_name &&
                                                    detail.product_item.name &&
                                                    detail.product_item.name !==
                                                        detail.product_item
                                                            .product_name
                                                "
                                            >
                                                {{ detail.product_item.name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-3 text-right align-top text-sm"
                                >
                                    {{ detail.qty }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right align-top text-sm"
                                >
                                    {{ formatVnd(detail.price) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right align-top text-sm font-medium"
                                >
                                    {{ formatVnd(detail.total) }}
                                </td>
                                <td class="px-4 py-3 align-top text-sm">
                                    <div class="flex flex-col gap-2">
                                        <div class="flex flex-wrap gap-1">
                                            <Badge
                                                :class="
                                                    getBadgeClass(
                                                        detail.status_color,
                                                    )
                                                "
                                                class="text-[10px]"
                                                >{{
                                                    detail.status_label
                                                }}</Badge
                                            >
                                            <Badge
                                                :class="
                                                    getBadgeClass(
                                                        detail.payment_status_color,
                                                    )
                                                "
                                                class="text-[10px]"
                                                >{{
                                                    detail.payment_status_label
                                                }}</Badge
                                            >
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top text-sm">
                                    {{ detail.note }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="border-t-2 border-gray-100 bg-gray-50">
                            <tr>
                                <td
                                    class="px-4 py-4 text-xs font-bold text-gray-500 uppercase"
                                >
                                    Tổng cộng
                                </td>
                                <td
                                    class="px-4 py-4 text-right text-sm font-black text-gray-900"
                                >
                                    {{ totalQty }}
                                </td>
                                <td class="px-4 py-4"></td>
                                <td
                                    class="px-4 py-4 text-right text-sm font-black text-indigo-700"
                                >
                                    {{ formatVnd(totalAmount) }}
                                </td>
                                <td colspan="2" class="px-4 py-4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Mobile Card View (Visible on mobile only) -->
                <div class="block divide-y divide-gray-100 md:hidden">
                    <div
                        v-for="detail in order.details"
                        :key="detail.id"
                        class="space-y-4 p-4"
                    >
                        <div class="flex gap-3">
                            <ProductThumbnailPreview
                                :src="detail.product_item.image"
                                :alt="
                                    detail.product_item.product_name ??
                                    detail.product_item.name ??
                                    ''
                                "
                                size-class="h-20 w-20 flex-shrink-0"
                            />
                            <div class="min-w-0 flex-1 space-y-1">
                                <div
                                    class="leading-tight font-bold text-gray-900"
                                >
                                    {{
                                        detail.product_item.product_name ??
                                        detail.product_item.name
                                    }}
                                </div>
                                <div
                                    class="text-xs text-gray-500"
                                    v-if="detail.product_item.sku"
                                >
                                    SKU: {{ detail.product_item.sku }}
                                </div>
                                <div class="flex flex-wrap gap-1 pt-1">
                                    <Badge
                                        :class="
                                            getBadgeClass(detail.status_color)
                                        "
                                        class="px-1.5 py-0 text-[10px]"
                                        >{{ detail.status_label }}</Badge
                                    >
                                    <Badge
                                        :class="
                                            getBadgeClass(
                                                detail.payment_status_color,
                                            )
                                        "
                                        class="px-1.5 py-0 text-[10px]"
                                        >{{
                                            detail.payment_status_label
                                        }}</Badge
                                    >
                                </div>
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-3 gap-2 border-y border-gray-100 py-3"
                        >
                            <div class="text-center">
                                <p
                                    class="text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                >
                                    Số lượng
                                </p>
                                <p class="text-sm font-bold text-gray-900">
                                    {{ detail.qty }}
                                </p>
                            </div>
                            <div class="border-x border-gray-100 text-center">
                                <p
                                    class="text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                >
                                    Đơn giá
                                </p>
                                <p class="text-sm font-medium text-gray-600">
                                    {{ formatVnd(detail.price) }}
                                </p>
                            </div>
                            <div class="text-center">
                                <p
                                    class="text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                >
                                    Thành tiền
                                </p>
                                <p class="text-sm font-black text-indigo-600">
                                    {{ formatVnd(detail.total) }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="detail.note"
                            class="rounded-lg border border-gray-100 bg-gray-50 p-2.5 text-xs text-gray-600 italic"
                        >
                            <span
                                class="mr-1 font-bold text-gray-400 not-italic"
                                >Ghi chú:</span
                            >
                            {{ detail.note }}
                        </div>
                    </div>

                    <!-- Mobile Summary Card -->
                    <div
                        class="space-y-3 border-t-2 border-indigo-100 bg-indigo-50/50 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-[10px] font-black tracking-[0.1em] text-indigo-400 uppercase"
                                >Tổng số lượng</span
                            >
                            <span class="text-sm font-black text-indigo-900">{{
                                totalQty
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span
                                class="text-[10px] font-black tracking-[0.1em] text-indigo-400 uppercase"
                                >Tổng thanh toán</span
                            >
                            <span class="text-lg font-black text-indigo-700">{{
                                formatVnd(totalAmount)
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
