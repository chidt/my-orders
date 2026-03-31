<script setup lang="ts">
import QuickCustomerCreateDialog from '@/components/orders/QuickCustomerCreateDialog.vue';
import { Button } from '@/components/ui/button';
import { formatVnd } from '@/lib/utils';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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
}

const props = defineProps<{
    site: Site;
    initialProductItems: ProductItem[];
    customerTypes: Record<number, string>;
    provinces: Array<{ id: number; name: string }>;
    selectedCustomer: Customer | null;
    canQuickCreateCustomer: boolean;
}>();

const page = usePage<{ flash?: { success?: string; error?: string; message?: string } }>();

const form = useForm({
    customer_id: '',
    shipping_address_id: '',
    order_date: new Date().toISOString().slice(0, 16),
    sale_channel: '1',
    shipping_payer: '1',
    shipping_note: '',
    order_note: '',
    details: [] as Array<{
        product_item_id: string;
        qty: number;
        discount: number;
        addition_price: number;
        note: string;
    }>,
});

const selectedCustomer = ref<Customer | null>(props.selectedCustomer);
const customerOptions = ref<Customer[]>(props.selectedCustomer ? [props.selectedCustomer] : []);
const customerSearch = ref(props.selectedCustomer ? props.selectedCustomer.name : '');
const showCreateCustomerModal = ref(false);
const isSearchingCustomers = ref(false);
const isCustomerSuggestionsOpen = ref(false);
const suppressCustomerSearchWatch = ref(false);
let customerSearchTimeout: ReturnType<typeof setTimeout> | null = null;
const selectedProductItems = ref<Record<string, ProductItem>>(
    Object.fromEntries(props.initialProductItems.map((item) => [String(item.id), item])),
);
const productItemSearch = ref('');
const productItemOptions = ref<ProductItem[]>([]);
const isSearchingProductItems = ref(false);
const isProductItemSuggestionsOpen = ref(false);
let productItemSearchTimeout: ReturnType<typeof setTimeout> | null = null;

const selectCustomer = (customer: Customer | null) => {
    selectedCustomer.value = customer;
    form.customer_id = customer ? String(customer.id) : '';

    if (!customer) {
        form.shipping_address_id = '';
        return;
    }

    const defaultAddress = customer.addresses.find((address) => address.is_default) ?? customer.addresses[0];
    if (!defaultAddress) {
        form.shipping_address_id = '';
        return;
    }

    const shouldReplaceAddress = !customer.addresses.some((address) => String(address.id) === form.shipping_address_id);
    if (shouldReplaceAddress) {
        form.shipping_address_id = String(defaultAddress.id);
    }

    if (customer) {
        suppressCustomerSearchWatch.value = true;
        customerSearch.value = customer.name;
        customerOptions.value = [customer, ...customerOptions.value.filter((item) => item.id !== customer.id)];
        setTimeout(() => {
            suppressCustomerSearchWatch.value = false;
        }, 0);
    }
    isCustomerSuggestionsOpen.value = false;
};

const searchCustomers = async (search: string) => {
    if (search.trim().length < 2) {
        customerOptions.value = selectedCustomer.value ? [selectedCustomer.value] : props.selectedCustomer ? [props.selectedCustomer] : [];
        return;
    }

    isSearchingCustomers.value = true;
    try {
        const response = await fetch(`/${props.site.slug}/orders/customers/search?search=${encodeURIComponent(search.trim())}`, {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        const payload = (await response.json().catch(() => ({ data: [] }))) as { data?: Customer[] };
        customerOptions.value = payload.data ?? [];
    } finally {
        isSearchingCustomers.value = false;
    }
};

watch(
    () => customerSearch.value,
    (search) => {
        const normalizedSearch = search.trim();

        if (suppressCustomerSearchWatch.value) {
            isCustomerSuggestionsOpen.value = false;
            return;
        }

        if (selectedCustomer.value && normalizedSearch === selectedCustomer.value.name) {
            isCustomerSuggestionsOpen.value = false;
            return;
        }

        if (normalizedSearch.length >= 2) {
            isCustomerSuggestionsOpen.value = true;
        } else {
            isCustomerSuggestionsOpen.value = false;
        }

        if (selectedCustomer.value && normalizedSearch !== selectedCustomer.value.name) {
            selectedCustomer.value = null;
            form.customer_id = '';
            form.shipping_address_id = '';
        }

        if (customerSearchTimeout) {
            clearTimeout(customerSearchTimeout);
        }

        customerSearchTimeout = setTimeout(() => {
            void searchCustomers(search);
        }, 300);
    },
);

watch(
    () => form.customer_id,
    (customerId) => {
        const customer = customerOptions.value.find((option) => String(option.id) === customerId) ?? null;
        selectedCustomer.value = customer;
        if (!customerId) {
            form.shipping_address_id = '';
        }
    },
);

const handleCustomerCreated = (customer: Customer) => {
    customerOptions.value = [customer, ...customerOptions.value.filter((item) => item.id !== customer.id)];
    selectCustomer(customer);
};

const customerOptionLabel = (customer: Customer): string => {
    return [customer.name, customer.phone, customer.email].filter((value) => Boolean(value && String(value).trim().length > 0)).join(' | ');
};

const addDetailFromProductItem = (item: ProductItem) => {
    selectedProductItems.value[String(item.id)] = item;

    const existingDetail = form.details.find((detail) => detail.product_item_id === String(item.id));
    if (existingDetail) {
        existingDetail.qty = Number(existingDetail.qty || 0) + 1;
        productItemSearch.value = '';
        productItemOptions.value = [];
        isProductItemSuggestionsOpen.value = false;
        return;
    }

    form.details.push({
        product_item_id: String(item.id),
        qty: 1,
        discount: 0,
        addition_price: 0,
        note: '',
    });
    productItemSearch.value = '';
    productItemOptions.value = [];
    isProductItemSuggestionsOpen.value = false;
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

    return [product.name, product.sku].filter(Boolean).join(' | ');
};

const searchProductItems = async (search: string) => {
    const normalizedSearch = search.trim();
    if (normalizedSearch.length < 2) {
        productItemOptions.value = [];
        isProductItemSuggestionsOpen.value = false;
        return;
    }

    isSearchingProductItems.value = true;
    isProductItemSuggestionsOpen.value = true;
    try {
        const response = await fetch(`/${props.site.slug}/orders/product-items/search?search=${encodeURIComponent(normalizedSearch)}`, {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        const payload = (await response.json().catch(() => ({ data: [] }))) as { data?: ProductItem[] };
        productItemOptions.value = payload.data ?? [];
    } finally {
        isSearchingProductItems.value = false;
    }
};

watch(
    () => productItemSearch.value,
    (search) => {
        if (productItemSearchTimeout) {
            clearTimeout(productItemSearchTimeout);
        }

        productItemSearchTimeout = setTimeout(() => {
            void searchProductItems(search);
        }, 300);
    },
);

const getLineTotal = (index: number) => {
    const line = form.details[index];
    const price = getPrice(line.product_item_id);

    return line.qty * price - Number(line.discount || 0) + Number(line.addition_price || 0);
};

const grandTotal = computed(() =>
    form.details.reduce((sum, _item, index) => sum + getLineTotal(index), 0),
);

const submit = () => {
    form.post(`/${props.site.slug}/orders`);
};
</script>

<template>
    <Head :title="`Tạo đơn hàng - ${site.name}`" />

    <AppLayout
        :breadcrumbs="[
            { title: site.name, href: `/${site.slug}/dashboard`, current: false },
            { title: 'Quản lý đơn hàng', href: `/${site.slug}/orders`, current: false },
            { title: 'Tạo đơn hàng', href: `/${site.slug}/orders/create`, current: true },
        ]"
    >
        <form class="space-y-6 px-4 py-8 sm:px-6 lg:px-8" @submit.prevent="submit">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Tạo đơn hàng mới</h1>
                <div class="flex gap-2">
                    <Button type="button" :as="Link" :href="`/${site.slug}/orders`" variant="outline">
                        Quay lại
                    </Button>
                    <Button type="submit" :disabled="form.processing">Tạo đơn hàng</Button>
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
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Khách hàng</label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <Input
                                v-model="customerSearch"
                                type="text"
                                placeholder="Nhập tên / SĐT / email để tìm khách hàng..."
                                @focus="isCustomerSuggestionsOpen = true"
                                @blur="setTimeout(() => (isCustomerSuggestionsOpen = false), 120)"
                            />
                            <div
                                v-if="isCustomerSuggestionsOpen && customerSearch.trim().length >= 2"
                                class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-white shadow"
                            >
                                <div v-if="isSearchingCustomers" class="px-3 py-2 text-sm text-gray-500">
                                    Đang tìm khách hàng...
                                </div>
                                <button
                                    v-for="customer in customerOptions"
                                    :key="customer.id"
                                    type="button"
                                    class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-gray-50"
                                    @mousedown.prevent="selectCustomer(customer)"
                                >
                                    <span>{{ customerOptionLabel(customer) }}</span>
                                </button>
                                <div v-if="!isSearchingCustomers && customerOptions.length === 0" class="px-3 py-2 text-sm text-gray-500">
                                    Không tìm thấy khách hàng phù hợp.
                                </div>
                            </div>
                        </div>
                        <Button
                            v-if="canQuickCreateCustomer"
                            type="button"
                            variant="outline"
                            @click="showCreateCustomerModal = true"
                        >
                            Tạo khách hàng
                        </Button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        Nhập ít nhất 2 ký tự để tìm kiếm và bấm chọn ngay trong danh sách.
                    </p>
                    <p v-if="form.errors.customer_id" class="mt-1 text-xs text-red-600">
                        {{ form.errors.customer_id }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Địa chỉ giao hàng</label>
                    <select v-model="form.shipping_address_id" class="h-10 w-full rounded-md border px-3 text-sm">
                        <option value="">Chọn địa chỉ</option>
                        <option
                            v-for="address in selectedCustomer?.addresses ?? []"
                            :key="address.id"
                            :value="String(address.id)"
                        >
                            {{ address.address }}{{ address.ward ? `, ${address.ward}` : ''
                            }}{{ address.province ? `, ${address.province}` : '' }}
                        </option>
                    </select>
                    <p v-if="form.errors.shipping_address_id" class="mt-1 text-xs text-red-600">
                        {{ form.errors.shipping_address_id }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Ngày đơn hàng</label>
                    <Input v-model="form.order_date" type="datetime-local" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Kênh bán hàng</label>
                    <select v-model="form.sale_channel" class="h-10 w-full rounded-md border px-3 text-sm">
                        <option value="1">Online</option>
                        <option value="2">Offline</option>
                        <option value="3">Phone</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Người trả phí ship</label>
                    <select v-model="form.shipping_payer" class="h-10 w-full rounded-md border px-3 text-sm">
                        <option value="1">Người bán</option>
                        <option value="2">Người mua</option>
                    </select>
                </div>

                <div class="md:col-span-3">
                    <label class="mb-1 block text-sm font-medium">Ghi chú giao hàng</label>
                    <textarea v-model="form.shipping_note" rows="2" class="w-full rounded-md border px-3 py-2 text-sm" />
                </div>

                <div class="md:col-span-3">
                    <label class="mb-1 block text-sm font-medium">Ghi chú đơn hàng</label>
                    <textarea v-model="form.order_note" rows="2" class="w-full rounded-md border px-3 py-2 text-sm" />
                </div>
            </div>

            <div class="space-y-3 rounded-lg border bg-white p-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Chi tiết sản phẩm</h2>
                </div>
                <div class="relative">
                    <Input
                        v-model="productItemSearch"
                        placeholder="Tìm sản phẩm theo tên / SKU..."
                        @focus="isProductItemSuggestionsOpen = productItemSearch.trim().length >= 2"
                        @blur="setTimeout(() => (isProductItemSuggestionsOpen = false), 120)"
                    />
                    <div
                        v-if="isProductItemSuggestionsOpen && productItemSearch.trim().length >= 2"
                        class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-white shadow"
                    >
                        <div v-if="isSearchingProductItems" class="px-3 py-2 text-sm text-gray-500">
                            Đang tìm sản phẩm...
                        </div>
                        <button
                            v-for="item in productItemOptions"
                            :key="item.id"
                            type="button"
                            class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-gray-50"
                            @mousedown.prevent="addDetailFromProductItem(item)"
                        >
                            <span>{{ [item.name, item.sku].filter(Boolean).join(' | ') }}</span>
                            <span class="text-xs text-gray-500">{{ formatVnd(item.price) }}</span>
                        </button>
                        <div v-if="!isSearchingProductItems && productItemOptions.length === 0" class="px-3 py-2 text-sm text-gray-500">
                            Không tìm thấy sản phẩm phù hợp.
                        </div>
                    </div>
                </div>

                <div
                    v-for="(detail, index) in form.details"
                    :key="index"
                    class="grid grid-cols-1 gap-3 rounded-md border p-3 md:grid-cols-12"
                >
                    <div class="md:col-span-4">
                        <label class="mb-1 block text-xs font-medium">Sản phẩm</label>
                        <div class="h-10 rounded-md border bg-gray-50 px-3 py-2 text-sm">
                            {{ getProductItemLabel(detail.product_item_id) }}
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-medium">Số lượng</label>
                        <Input v-model.number="detail.qty" type="number" min="1" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-medium">Giảm giá</label>
                        <Input v-model.number="detail.discount" type="number" min="0" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-medium">Phụ phí</label>
                        <Input v-model.number="detail.addition_price" type="number" min="0" />
                    </div>
                    <div class="md:col-span-1">
                        <label class="mb-1 block text-xs font-medium">Thành tiền</label>
                        <div class="h-10 rounded-md border bg-gray-50 px-3 py-2 text-sm">
                            {{ formatVnd(getLineTotal(index)) }}
                        </div>
                    </div>
                    <div class="md:col-span-1">
                        <label class="mb-1 block text-xs font-medium opacity-0">Xóa</label>
                        <Button type="button" variant="destructive" class="w-full" @click="removeDetail(index)">
                            X
                        </Button>
                    </div>
                </div>

                <div class="text-right text-lg font-semibold">
                    Tổng tiền: {{ formatVnd(grandTotal) }}
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

