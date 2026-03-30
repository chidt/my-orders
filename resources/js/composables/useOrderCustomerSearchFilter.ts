import { ref, watch } from 'vue';

export interface OrderSearchCustomerAddress {
    id: number;
    address: string;
    is_default: boolean;
    ward: string | null;
    province: string | null;
}

export interface OrderSearchCustomer {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    type: number;
    addresses: OrderSearchCustomerAddress[];
}

export function orderSearchCustomerLabel(c: OrderSearchCustomer): string {
    return [c.name, c.phone, c.email].filter((v) => v && String(v).trim()).join(' | ');
}

export function useOrderCustomerSearchFilter(options: {
    siteSlug: () => string;
    getCustomerId: () => string;
    getFilterCustomer: () => OrderSearchCustomer | null | undefined;
}) {
    const customerId = ref('');
    const customerSearch = ref('');
    const customerOptions = ref<OrderSearchCustomer[]>([]);
    const selectedCustomer = ref<OrderSearchCustomer | null>(null);
    const isSearchingCustomers = ref(false);
    const isCustomerSuggestionsOpen = ref(false);
    const suppressCustomerSearchWatch = ref(false);
    let customerSearchTimeout: ReturnType<typeof setTimeout> | null = null;

    const hydrateFromProps = () => {
        const fc = options.getFilterCustomer() ?? null;
        const id = options.getCustomerId() || '';
        customerId.value = id;
        selectedCustomer.value = fc;
        customerSearch.value = fc?.name ?? '';
        customerOptions.value = fc ? [fc] : [];
    };

    watch(
        () => [options.getCustomerId(), options.getFilterCustomer()?.id ?? null] as const,
        () => {
            hydrateFromProps();
        },
        { immediate: true },
    );

    const searchCustomers = async (search: string) => {
        if (search.trim().length < 2) {
            customerOptions.value = selectedCustomer.value ? [selectedCustomer.value] : [];
            return;
        }

        isSearchingCustomers.value = true;
        try {
            const response = await fetch(
                `/${options.siteSlug()}/orders/customers/search?search=${encodeURIComponent(search.trim())}`,
                {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                },
            );
            const payload = (await response.json().catch(() => ({ data: [] }))) as { data?: OrderSearchCustomer[] };
            customerOptions.value = payload.data ?? [];
        } finally {
            isSearchingCustomers.value = false;
        }
    };

    const selectCustomer = (customer: OrderSearchCustomer | null) => {
        if (!customer) {
            selectedCustomer.value = null;
            customerId.value = '';
            customerSearch.value = '';
            customerOptions.value = [];
            isCustomerSuggestionsOpen.value = false;
            return;
        }

        suppressCustomerSearchWatch.value = true;
        selectedCustomer.value = customer;
        customerId.value = String(customer.id);
        customerSearch.value = customer.name;
        customerOptions.value = [customer, ...customerOptions.value.filter((c) => c.id !== customer.id)];
        isCustomerSuggestionsOpen.value = false;
        setTimeout(() => {
            suppressCustomerSearchWatch.value = false;
        }, 0);
    };

    const clearCustomerSelection = () => {
        selectCustomer(null);
    };

    watch(customerSearch, (search) => {
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
            customerId.value = '';
        }

        if (customerSearchTimeout) {
            clearTimeout(customerSearchTimeout);
        }

        customerSearchTimeout = setTimeout(() => {
            void searchCustomers(search);
        }, 300);
    });

    const openSuggestions = () => {
        isCustomerSuggestionsOpen.value = true;
    };

    const closeSuggestionsBlur = () => {
        setTimeout(() => {
            isCustomerSuggestionsOpen.value = false;
        }, 120);
    };

    return {
        customerId,
        customerSearch,
        customerOptions,
        isSearchingCustomers,
        isCustomerSuggestionsOpen,
        selectCustomer,
        clearCustomerSelection,
        orderSearchCustomerLabel,
        openSuggestions,
        closeSuggestionsBlur,
    };
}
