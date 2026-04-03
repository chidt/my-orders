<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import QuickCustomerCreateDialog from '@/components/orders/QuickCustomerCreateDialog.vue';
import ProductThumbnailPreview from '@/components/products/ProductThumbnailPreview.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppMultiselect from '@/components/ui/multiselect/AppMultiselect.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatVnd } from '@/lib/utils';

interface Site {
    id: number;
    slug: string;
    name: string;
}

interface Address {
    id: number;
    address: string;
    ward: string | null;
    province: string | null;
    is_default: boolean;
}

interface Customer {
    id: number;
    email: string | null;
    name: string;
    phone: string | null;
    type: number;
    addresses: Address[];
}

interface ProductItem {
    id: number;
    name: string;
    sku: string;
    product_name: string | null;
    price: number;
    image: string | null;
}

interface EditableOrder {
    id: number;
    customer_id: string;
    shipping_address_id: string;
    order_date: string | null;
    sale_channel: string;
    shipping_payer: string;
    shipping_note: string | null;
    order_note: string | null;
    status_label: string;
    status_color: string;
    payment_status_label: string;
    payment_status_color: string;
    details: Array<{
        id: number;
        product_item_id: string;
        status_label: string;
        status_color: string;
        payment_status_label: string;
        payment_status_color: string;
        qty: number;
        discount: number;
        addition_price: number;
        note: string;
    }>;
}

const props = defineProps<{
    site: Site;
    initialProductItems: ProductItem[];
    customerTypes: Record<number, string>;
    provinces: Array<{ id: number; name: string }>;
    selectedCustomer: Customer | null;
    canQuickCreateCustomer: boolean;
    order: EditableOrder;
}>();

import type { AppPageProps } from '@/types';
const page = usePage<
    AppPageProps & {
        flash: { success?: string; error?: string; message?: string };
    }
>();

const form = useForm({
    customer_id: props.order.customer_id,
    shipping_address_id: props.order.shipping_address_id,
    order_date: props.order.order_date ?? new Date().toISOString().slice(0, 16),
    sale_channel: props.order.sale_channel,
    shipping_payer: props.order.shipping_payer,
    shipping_note: props.order.shipping_note ?? '',
    order_note: props.order.order_note ?? '',
    details: props.order.details.map((detail) => ({
        product_item_id: detail.product_item_id,
        qty: detail.qty,
        discount: detail.discount,
        addition_price: detail.addition_price,
        note: detail.note ?? '',
        // Store status info locally for display, though it's not submitted/validated for updates
        status_label: detail.status_label,
        status_color: detail.status_color,
        payment_status_label: detail.payment_status_label,
        payment_status_color: detail.payment_status_color,
    })),
});

const selectedCustomer = ref<Customer | null>(props.selectedCustomer);
const customerOptions = ref<Customer[]>([]);
const showCreateCustomerModal = ref(false);
const isSearchingCustomers = ref(false);
let customerSearchTimeout: ReturnType<typeof setTimeout> | null = null;
const selectedProductItems = ref<Record<string, ProductItem>>(
    Object.fromEntries(
        props.initialProductItems.map((item) => [String(item.id), item]),
    ),
);
// ...existing code...
const productItemOptions = ref<ProductItem[]>([]);
const isSearchingProductItems = ref(false);
let productItemSearchTimeout: ReturnType<typeof setTimeout> | null = null;

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

const selectCustomer = (customer: Customer | null) => {
    selectedCustomer.value = customer;
    form.customer_id = customer ? String(customer.id) : '';

    if (!customer) {
        form.shipping_address_id = '';
        return;
    }

    const defaultAddress =
        customer.addresses.find((address) => address.is_default) ??
        customer.addresses[0];
    if (!defaultAddress) {
        form.shipping_address_id = '';
        return;
    }

    const shouldReplaceAddress = !customer.addresses.some(
        (address) => String(address.id) === form.shipping_address_id,
    );
    if (shouldReplaceAddress) {
        form.shipping_address_id = String(defaultAddress.id);
    }
};

const searchCustomers = async (search: string = '') => {
    isSearchingCustomers.value = true;
    try {
        const response = await fetch(
            `/${props.site.slug}/orders/customers/search?search=${encodeURIComponent(search.trim())}`,
            {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            },
        );

        const payload = (await response.json().catch(() => ({ data: [] }))) as {
            data?: Customer[];
        };
        customerOptions.value = payload.data ?? [];
    } finally {
        isSearchingCustomers.value = false;
    }
};

const onCustomerSearchChange = (search: string) => {
    if (customerSearchTimeout) {
        clearTimeout(customerSearchTimeout);
    }

    customerSearchTimeout = setTimeout(() => {
        void searchCustomers(search);
    }, 300);
};

onMounted(() => {
    void searchCustomers();
    void searchProductItems();
});

watch(
    () => selectedCustomer.value,
    (customer) => {
        if (customer) {
            selectCustomer(customer);
        } else {
            selectCustomer(null);
        }
    },
);

watch(
    () => form.customer_id,
    (customerId) => {
        const customer =
            customerOptions.value.find(
                (option) => String(option.id) === customerId,
            ) ?? null;
        selectedCustomer.value = customer;
        if (!customerId) {
            form.shipping_address_id = '';
        }
    },
);

const handleCustomerCreated = (customer: Customer) => {
    customerOptions.value = [
        customer,
        ...customerOptions.value.filter((item) => item.id !== customer.id),
    ];
    selectCustomer(customer);
};

const onProductItemSearchChange = (search: string) => {
    if (productItemSearchTimeout) {
        clearTimeout(productItemSearchTimeout);
    }

    productItemSearchTimeout = setTimeout(() => {
        void searchProductItems(search);
    }, 300);
};

const handleProductItemSelect = (item: unknown) => {
    if (item) {
        addDetailFromProductItem(item as ProductItem);
    }
};

const searchProductItems = async (search: string = '') => {
    isSearchingProductItems.value = true;
    try {
        const response = await fetch(
            `/${props.site.slug}/orders/product-items/search?search=${encodeURIComponent(search.trim())}`,
            {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            },
        );

        const payload = (await response.json().catch(() => ({ data: [] }))) as {
            data?: ProductItem[];
        };
        productItemOptions.value = payload.data ?? [];
    } finally {
        isSearchingProductItems.value = false;
    }
};

const addDetailFromProductItem = (item: ProductItem) => {
    selectedProductItems.value[String(item.id)] = item;

    const existingDetail = form.details.find(
        (detail) => detail.product_item_id === String(item.id),
    );
    if (existingDetail) {
        existingDetail.qty = Number(existingDetail.qty || 0) + 1;
        return;
    }

    form.details.push({
        product_item_id: String(item.id),
        qty: 1,
        discount: 0,
        addition_price: 0,
        note: '',
        status_label: '',
        status_color: '',
        payment_status_label: '',
        payment_status_color: '',
    });
};

const removeDetail = (index: number) => {
    form.details.splice(index, 1);
};

const getPrice = (productItemId: string) => {
    const product = selectedProductItems.value[productItemId];

    return product?.price ?? 0;
};

const getProductItemLabel = (productItemId: string): string => {
    const product = selectedProductItems.value[productItemId];
    if (!product) {
        return `#${productItemId}`;
    }

    return product.name;
};

const getLineTotal = (index: number) => {
    const line = form.details[index];
    const price = getPrice(line.product_item_id);

    return (
        line.qty * price -
        Number(line.discount || 0) +
        Number(line.addition_price || 0)
    );
};

const grandTotal = computed(() =>
    form.details.reduce((sum, _item, index) => sum + getLineTotal(index), 0),
);

const deleteOrder = () => {
    if (!window.confirm('Bạn có chắc muốn xóa đơn hàng này?')) {
        return;
    }

    router.delete(`/${props.site.slug}/orders/${props.order.id}`);
};

const submit = () => {
    form.put(`/${props.site.slug}/orders/${props.order.id}`);
};
</script>

<template>
    <Head :title="`Sửa đơn hàng - ${site.name}`" />

    <AppLayout
        :breadcrumbs="[
            {
                title: site.name,
                href: `/${site.slug}/dashboard`,
            },
            {
                title: 'Quản lý đơn hàng',
                href: `/${site.slug}/orders`,
            },
            {
                title: `Sửa #${order.id}`,
                href: `/${site.slug}/orders/${order.id}/edit`,
            },
        ]"
    >
        <form
            class="space-y-6 px-4 py-8 sm:px-6 lg:px-8"
            @submit.prevent="submit"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="truncate text-xl font-bold text-gray-900 sm:text-2xl"
                    >
                        Sửa đơn hàng #{{ order.id }}
                    </h1>
                    <div class="mt-1 flex flex-wrap items-center gap-2 sm:mt-2">
                        <span class="text-xs text-gray-600 sm:text-sm"
                            >Trạng thái:</span
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
                <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                    <Button
                        type="button"
                        :as="Link"
                        :href="`/${site.slug}/orders/${order.id}`"
                        variant="outline"
                        class="min-h-11 flex-1 text-sm sm:min-h-10 sm:flex-none"
                    >
                        <svg
                            class="mr-1.5 h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                            />
                        </svg>
                        Xem
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        @click="deleteOrder"
                        class="min-h-11 flex-1 text-sm sm:min-h-10 sm:flex-none"
                    >
                        <svg
                            class="mr-1.5 h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                        Xóa
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="min-h-11 w-full text-sm font-bold shadow-lg shadow-indigo-100 sm:min-h-10 sm:w-auto"
                    >
                        <svg
                            class="mr-1.5 h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        Lưu thay đổi
                    </Button>
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
                <div class="space-y-3 md:col-span-2">
                    <h2
                        class="border-b pb-2 text-sm font-semibold text-gray-900 sm:text-base"
                    >
                        Thông tin khách hàng
                    </h2>
                    <div class="space-y-3">
                        <label
                            class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                            >Chọn khách hàng</label
                        >
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <div class="relative flex-1">
                                <AppMultiselect
                                    v-model="selectedCustomer"
                                    :options="customerOptions"
                                    :loading="isSearchingCustomers"
                                    :internal-search="false"
                                    placeholder="Tìm tên, SĐT, email..."
                                    label="name"
                                    track-by="id"
                                    class="min-h-11 sm:min-h-10"
                                    @search-change="onCustomerSearchChange"
                                >
                                    <template #option="{ option }">
                                        <div class="flex flex-col gap-0.5">
                                            <span
                                                class="font-bold text-gray-900"
                                                >{{
                                                    (option as Customer).name
                                                }}</span
                                            >
                                            <div
                                                class="flex gap-2 text-[10px] font-medium text-gray-500"
                                            >
                                                <span
                                                    v-if="
                                                        (option as Customer)
                                                            .phone
                                                    "
                                                    >{{
                                                        (option as Customer)
                                                            .phone
                                                    }}</span
                                                >
                                                <span
                                                    v-if="
                                                        (option as Customer)
                                                            .phone &&
                                                        (option as Customer)
                                                            .email
                                                    "
                                                    >|</span
                                                >
                                                <span
                                                    v-if="
                                                        (option as Customer)
                                                            .email
                                                    "
                                                    >{{
                                                        (option as Customer)
                                                            .email
                                                    }}</span
                                                >
                                            </div>
                                        </div>
                                    </template>
                                    <template #noResult>
                                        <span
                                            class="px-3 py-2 text-sm text-gray-500"
                                            >Không tìm thấy khách hàng.</span
                                        >
                                    </template>
                                </AppMultiselect>
                            </div>
                            <Button
                                v-if="canQuickCreateCustomer"
                                type="button"
                                variant="outline"
                                class="h-11 px-4 text-xs font-semibold sm:h-auto"
                                @click="showCreateCustomerModal = true"
                            >
                                <svg
                                    class="mr-1.5 h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                Tạo khách mới
                            </Button>
                        </div>
                        <p
                            v-if="form.errors.customer_id"
                            class="mt-1 text-xs font-medium text-red-600"
                        >
                            {{ form.errors.customer_id }}
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <h2
                        class="border-b pb-2 text-sm font-semibold text-gray-900 sm:text-base"
                    >
                        Địa chỉ nhận hàng
                    </h2>
                    <div class="space-y-3">
                        <label
                            class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                            >Chọn địa chỉ</label
                        >
                        <select
                            v-model="form.shipping_address_id"
                            class="h-11 w-full rounded-md border px-3 text-sm transition-all outline-none focus:ring-2 focus:ring-indigo-500 sm:h-10"
                        >
                            <option value="">-- Chọn địa chỉ --</option>
                            <option
                                v-for="address in selectedCustomer?.addresses ??
                                []"
                                :key="address.id"
                                :value="String(address.id)"
                            >
                                {{ address.address
                                }}{{ address.ward ? `, ${address.ward}` : ''
                                }}{{
                                    address.province
                                        ? `, ${address.province}`
                                        : ''
                                }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.shipping_address_id"
                            class="mt-1 text-xs font-medium text-red-600"
                        >
                            {{ form.errors.shipping_address_id }}
                        </p>
                    </div>
                </div>

                <div class="space-y-4 border-t pt-2 md:col-span-3">
                    <h2
                        class="text-sm font-semibold text-gray-900 sm:text-base"
                    >
                        Cấu hình đơn hàng
                    </h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="space-y-1.5">
                            <label
                                class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                >Ngày đơn hàng</label
                            >
                            <Input
                                v-model="form.order_date"
                                type="datetime-local"
                                class="h-11 sm:h-10"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label
                                class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                >Kênh bán hàng</label
                            >
                            <select
                                v-model="form.sale_channel"
                                class="h-11 w-full rounded-md border px-3 text-sm transition-all outline-none focus:ring-2 focus:ring-indigo-500 sm:h-10"
                            >
                                <option value="1">Online</option>
                                <option value="2">Offline</option>
                                <option value="3">Phone</option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label
                                class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                >Người trả phí ship</label
                            >
                            <select
                                v-model="form.shipping_payer"
                                class="h-11 w-full rounded-md border px-3 text-sm transition-all outline-none focus:ring-2 focus:ring-indigo-500 sm:h-10"
                            >
                                <option value="1">Người bán</option>
                                <option value="2">Người mua</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 gap-4 pt-2 sm:grid-cols-2 md:col-span-3"
                >
                    <div class="space-y-1.5">
                        <label
                            class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                            >Ghi chú giao hàng</label
                        >
                        <textarea
                            v-model="form.shipping_note"
                            rows="3"
                            class="w-full rounded-md border px-3 py-2 text-sm transition-all outline-none focus:ring-2 focus:ring-indigo-500"
                            placeholder="Nhập ghi chú cho đơn vị vận chuyển..."
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label
                            class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                            >Ghi chú đơn hàng</label
                        >
                        <textarea
                            v-model="form.order_note"
                            rows="3"
                            class="w-full rounded-md border px-3 py-2 text-sm transition-all outline-none focus:ring-2 focus:ring-indigo-500"
                            placeholder="Ghi chú nội bộ cho cửa hàng..."
                        />
                    </div>
                </div>
            </div>

            <div class="space-y-4 rounded-xl border bg-white p-4 shadow-sm">
                <div
                    class="flex flex-col gap-2 border-b pb-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <h2 class="text-base font-bold text-gray-900 sm:text-lg">
                        Chi tiết sản phẩm
                    </h2>
                    <div
                        class="italic-none text-xs font-semibold text-gray-500"
                    >
                        Thêm các sản phẩm vào đơn hàng để chỉnh sửa
                    </div>
                </div>

                <div class="relative">
                    <AppMultiselect
                        :options="productItemOptions"
                        :loading="isSearchingProductItems"
                        :internal-search="false"
                        placeholder="Thêm nhanh sản phẩm (SKU hoặc Tên)..."
                        label="name"
                        track-by="id"
                        class="min-h-11 overflow-hidden rounded-md ring-1 ring-indigo-100 sm:min-h-10"
                        @search-change="onProductItemSearchChange"
                        @update:model-value="handleProductItemSelect"
                    >
                        <template #option="{ option }">
                            <div class="flex items-center gap-3 py-1">
                                <ProductThumbnailPreview
                                    :src="(option as ProductItem).image"
                                    :alt="(option as ProductItem).name"
                                    size-class="h-12 w-12 rounded-md"
                                />
                                <div
                                    class="flex flex-1 items-center justify-between gap-4"
                                >
                                    <div class="flex flex-col gap-0.5">
                                        <span
                                            class="leading-tight font-bold text-gray-900"
                                            >{{
                                                (option as ProductItem).name
                                            }}</span
                                        >
                                        <span
                                            class="text-[10px] font-bold text-gray-400 uppercase"
                                            >{{
                                                (option as ProductItem).sku
                                            }}</span
                                        >
                                    </div>
                                    <span
                                        class="text-sm font-black text-indigo-600"
                                        >{{
                                            formatVnd(
                                                (option as ProductItem).price,
                                            )
                                        }}</span
                                    >
                                </div>
                            </div>
                        </template>
                        <template #noResult>
                            <div class="p-4 text-center">
                                <span class="text-sm text-gray-500"
                                    >Không tìm thấy sản phẩm nào khớp với tìm
                                    kiếm.</span
                                >
                            </div>
                        </template>
                    </AppMultiselect>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(detail, index) in form.details"
                        :key="index"
                        class="rounded-xl border bg-gray-50/30 p-3 shadow-sm transition-colors hover:border-indigo-200 sm:p-4"
                    >
                        <!-- Card Header: Image, Product Name, SKU and Remove Button -->
                        <div class="mb-3 flex items-start gap-4 border-b pb-3">
                            <ProductThumbnailPreview
                                :src="
                                    selectedProductItems[detail.product_item_id]
                                        ?.image
                                "
                                :alt="
                                    getProductItemLabel(detail.product_item_id)
                                "
                                size-class="h-16 w-16 sm:h-20 sm:w-20 rounded-lg flex-shrink-0"
                            />
                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm leading-tight font-bold text-gray-900 sm:text-base"
                                >
                                    {{
                                        getProductItemLabel(
                                            detail.product_item_id,
                                        )
                                    }}
                                </p>
                                <p
                                    class="mt-1 text-[10px] font-bold tracking-wider text-gray-400 uppercase sm:text-xs"
                                    v-if="
                                        selectedProductItems[
                                            detail.product_item_id
                                        ]?.sku
                                    "
                                >
                                    SKU:
                                    {{
                                        selectedProductItems[
                                            detail.product_item_id
                                        ].sku
                                    }}
                                </p>
                                <div
                                    v-if="detail.status_label"
                                    class="mt-2 flex flex-wrap gap-1"
                                >
                                    <Badge
                                        :class="
                                            getBadgeClass(detail.status_color)
                                        "
                                        class="text-[10px]"
                                        >{{ detail.status_label }}</Badge
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
                            <Button
                                type="button"
                                variant="ghost"
                                class="h-8 w-8 rounded-full p-0 text-gray-400 transition-colors hover:text-red-500"
                                @click="removeDetail(index)"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </Button>
                        </div>

                        <!-- Card Body: Qty, Discount, Addition, Subtotal and Note -->
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="space-y-1.5">
                                <label
                                    class="block text-[10px] font-black tracking-wider text-gray-400 uppercase"
                                    >Số lượng</label
                                >
                                <Input
                                    v-model.number="detail.qty"
                                    type="number"
                                    min="1"
                                    class="h-11 bg-white text-sm font-bold sm:h-10"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <label
                                    class="block text-[10px] font-black tracking-wider text-gray-400 uppercase"
                                    >Giảm giá</label
                                >
                                <Input
                                    v-model.number="detail.discount"
                                    type="number"
                                    min="0"
                                    class="h-11 bg-white text-sm sm:h-10"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <label
                                    class="block text-[10px] font-black tracking-wider text-gray-400 uppercase"
                                    >Phụ phí</label
                                >
                                <Input
                                    v-model.number="detail.addition_price"
                                    type="number"
                                    min="0"
                                    class="h-11 bg-white text-sm sm:h-10"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <label
                                    class="block text-[10px] font-black tracking-wider text-gray-400 uppercase"
                                    >Thành tiền</label
                                >
                                <div
                                    class="flex h-11 items-center rounded-md border border-indigo-100 bg-indigo-50/50 px-3 text-sm font-black text-indigo-700 sm:h-10 sm:text-base"
                                >
                                    {{ formatVnd(getLineTotal(index)) }}
                                </div>
                            </div>
                            <div
                                class="col-span-2 space-y-1.5 pt-1 sm:col-span-4"
                            >
                                <label
                                    class="block text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                    >Ghi chú sản phẩm</label
                                >
                                <Input
                                    v-model="detail.note"
                                    placeholder="Nhập ghi chú cho sản phẩm này..."
                                    class="h-11 bg-white text-sm sm:h-10"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-col items-center justify-between gap-4 rounded-xl border-2 border-indigo-100 bg-indigo-50 p-4 sm:flex-row"
                >
                    <span
                        class="text-sm font-bold tracking-widest text-indigo-900 uppercase sm:text-base"
                        >Tổng cộng đơn hàng</span
                    >
                    <span
                        class="text-xl font-black text-indigo-700 sm:text-2xl"
                        >{{ formatVnd(grandTotal) }}</span
                    >
                </div>
            </div>
        </form>

        <QuickCustomerCreateDialog
            v-if="canQuickCreateCustomer"
            v-model:open="showCreateCustomerModal"
            :site-slug="site.slug"
            :customer-types="customerTypes"
            :provinces="provinces"
            @created="handleCustomerCreated"
        />
    </AppLayout>
</template>
