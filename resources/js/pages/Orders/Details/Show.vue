<script setup lang="ts">
// ...existing code...
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
</script>

<template>
    <Head :title="`OrderDetail #${orderDetail.id}`" />

    <AppLayout
        :breadcrumbs="[
            {
                title: site.name,
                href: `/${site.slug}/dashboard`,
                current: false,
            },
            {
                title: 'Chi tiết đơn hàng',
                href: `/${site.slug}/order-details`,
                current: false,
            },
            {
                title: `OrderDetail #${orderDetail.id}`,
                href: `/${site.slug}/order-details/${orderDetail.id}`,
                current: true,
            },
        ]"
    >
        <div class="space-y-4 px-4 py-8 sm:px-6 lg:px-8">
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
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <h1
                    class="truncate text-xl font-bold text-gray-900 sm:text-2xl"
                >
                    OrderDetail #{{ orderDetail.id }}
                </h1>
                <Button
                    :as="Link"
                    :href="`/${site.slug}/order-details`"
                    variant="outline"
                    class="min-h-11 w-full text-sm sm:min-h-10 sm:w-auto"
                >
                    Quay lại danh sách
                </Button>
            </div>

            <div
                class="grid grid-cols-1 gap-3 rounded-lg border bg-white p-3 sm:grid-cols-3 sm:gap-4 sm:p-4"
            >
                <div>
                    <p
                        class="text-[10px] font-semibold text-gray-500 uppercase sm:text-xs"
                    >
                        Mã đơn hàng
                    </p>
                    <Link
                        :href="`/${site.slug}/orders/${orderDetail.order.id}`"
                        class="mt-1 block text-sm font-semibold text-blue-600 hover:text-blue-800"
                    >
                        {{ orderDetail.order.order_number }}
                    </Link>
                </div>
                <div>
                    <p
                        class="text-[10px] font-semibold text-gray-500 uppercase sm:text-xs"
                    >
                        Ngày đơn
                    </p>
                    <p class="mt-1 text-sm font-medium">
                        {{ orderDetail.order.order_date }}
                    </p>
                </div>
                <div>
                    <p
                        class="text-[10px] font-semibold text-gray-500 uppercase sm:text-xs"
                    >
                        Trạng thái Order
                    </p>
                    <p class="mt-1 text-sm font-medium">
                        {{ orderDetail.order.status }}
                    </p>
                </div>
            </div>

            <div
                class="grid grid-cols-1 gap-4 rounded-lg border bg-white p-3 sm:p-4 lg:grid-cols-2"
            >
                <div class="space-y-2">
                    <h2
                        class="border-b pb-2 text-sm font-semibold sm:text-base"
                    >
                        Thông tin khách hàng
                    </h2>
                    <div class="mt-1">
                        <p class="text-sm font-medium text-gray-900">
                            {{ orderDetail.customer.name }}
                        </p>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ orderDetail.customer.phone || '-' }}
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ orderDetail.customer.email || '-' }}
                        </p>
                        <div
                            class="mt-3 rounded bg-gray-50 p-2 text-xs text-gray-700"
                        >
                            <span class="font-semibold text-gray-500"
                                >Giao đến:</span
                            >
                            {{ orderDetail.shipping_address?.address || '-' }}
                            {{
                                orderDetail.shipping_address?.ward
                                    ? `, ${orderDetail.shipping_address.ward}`
                                    : ''
                            }}
                            {{
                                orderDetail.shipping_address?.province
                                    ? `, ${orderDetail.shipping_address.province}`
                                    : ''
                            }}
                        </div>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <ProductThumbnailPreview
                        :src="orderDetail.product_item.image"
                        :alt="orderDetail.product_item.name"
                        size-class="h-20 w-20 sm:h-24 sm:w-24 flex-shrink-0"
                    />
                    <div class="min-w-0 flex-1">
                        <h2
                            class="mb-2 border-b pb-2 text-sm font-semibold sm:text-base"
                        >
                            Thông tin sản phẩm
                        </h2>
                        <p
                            class="text-sm leading-tight font-semibold text-gray-900"
                        >
                            {{ orderDetail.product.name }}
                        </p>
                        <p class="mt-1 text-sm text-gray-700">
                            {{ orderDetail.product_item.name }}
                        </p>
                        <p
                            class="text-xs text-gray-500"
                            v-if="orderDetail.product_item.sku"
                        >
                            SKU: {{ orderDetail.product_item.sku }}
                        </p>
                        <div class="mt-2 flex flex-wrap gap-1">
                            <Badge
                                :class="getBadgeClass(orderDetail.status.color)"
                                class="px-1.5 py-0 text-[10px]"
                                >{{ orderDetail.status.label }}</Badge
                            >
                            <Badge
                                :class="
                                    getBadgeClass(
                                        orderDetail.payment_status.color,
                                    )
                                "
                                class="px-1.5 py-0 text-[10px]"
                                >{{ orderDetail.payment_status.label }}</Badge
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="grid grid-cols-2 gap-3 rounded-lg border bg-white p-3 sm:grid-cols-3 sm:gap-4 sm:p-4 lg:grid-cols-5"
            >
                <div>
                    <p
                        class="text-[10px] font-semibold text-gray-500 uppercase sm:text-xs"
                    >
                        Số lượng
                    </p>
                    <p class="mt-1 text-sm font-medium">
                        {{ orderDetail.pricing.qty }}
                    </p>
                </div>
                <div>
                    <p
                        class="text-[10px] font-semibold text-gray-500 uppercase sm:text-xs"
                    >
                        Giá
                    </p>
                    <p class="mt-1 text-sm font-medium">
                        {{ formatVnd(orderDetail.pricing.price) }}
                    </p>
                </div>
                <div>
                    <p
                        class="text-[10px] font-semibold text-gray-500 uppercase sm:text-xs"
                    >
                        Chiết khấu
                    </p>
                    <p class="mt-1 text-sm font-medium">
                        {{ formatVnd(orderDetail.pricing.discount) }}
                    </p>
                </div>
                <div>
                    <p
                        class="text-[10px] font-semibold text-gray-500 uppercase sm:text-xs"
                    >
                        Phụ phí
                    </p>
                    <p class="mt-1 text-sm font-medium">
                        {{ formatVnd(orderDetail.pricing.addition_price) }}
                    </p>
                </div>
                <div
                    class="col-span-2 border-t pt-2 sm:col-span-1 sm:border-0 sm:pt-0"
                >
                    <p
                        class="text-[10px] font-semibold text-gray-500 uppercase sm:text-xs"
                    >
                        Thành tiền
                    </p>
                    <p
                        class="mt-0.5 text-base font-bold text-indigo-600 sm:text-lg"
                    >
                        {{ formatVnd(orderDetail.pricing.total) }}
                    </p>
                </div>
            </div>

            <div class="rounded-lg border bg-white p-3 sm:p-4">
                <h2 class="border-b pb-2 text-sm font-semibold sm:text-base">
                    Status history
                </h2>
                <div class="mt-3 space-y-3">
                    <div
                        v-for="(item, index) in orderDetail.status_history"
                        :key="index"
                        class="rounded-md border border-gray-100 bg-gray-50/50 px-3 py-2.5 text-xs sm:text-sm"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <p class="leading-tight font-bold text-gray-900">
                                {{ item.title }} - {{ item.status }}
                            </p>
                            <p
                                class="text-[10px] whitespace-nowrap text-gray-400"
                            >
                                {{ item.at }}
                            </p>
                        </div>
                        <p v-if="item.note" class="mt-1 text-gray-600 italic">
                            <span class="font-semibold text-gray-500 not-italic"
                                >Ghi chú:</span
                            >
                            {{ item.note }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border bg-white p-3 sm:p-4">
                <h2 class="border-b pb-2 text-sm font-semibold sm:text-base">
                    Ghi chú
                </h2>
                <div class="mt-3 space-y-2">
                    <div class="rounded bg-gray-50 p-2.5 text-xs sm:text-sm">
                        <span class="mb-1 block font-semibold text-gray-500"
                            >Mục này:</span
                        >
                        {{ orderDetail.notes.order_detail_note || '-' }}
                    </div>
                    <div class="rounded bg-gray-50 p-2.5 text-xs sm:text-sm">
                        <span class="mb-1 block font-semibold text-gray-500"
                            >Của đơn:</span
                        >
                        {{ orderDetail.notes.order_note || '-' }}
                    </div>
                    <div class="rounded bg-gray-50 p-2.5 text-xs sm:text-sm">
                        <span class="mb-1 block font-semibold text-gray-500"
                            >Giao nhận:</span
                        >
                        {{ orderDetail.notes.shipping_note || '-' }}
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
