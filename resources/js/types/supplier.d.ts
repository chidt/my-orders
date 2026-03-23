export interface Supplier {
    id: number;
    name: string;
    person_in_charge?: string | null;
    phone?: string | null;
    address?: string | null;
    description?: string | null;
    site_id: number;
    products_count?: number;
    created_at: string;
    updated_at: string;
}

export interface SupplierListProps {
    site: {
        id: number;
        name: string;
        slug: string;
    };
    suppliers: {
        data: Supplier[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    statistics: {
        total: number;
    };
    filters: {
        search?: string | null;
        sort_by?: string | null;
        sort_direction?: string | null;
    };
}

export interface SupplierFormProps {
    site: {
        id: number;
        name: string;
        slug: string;
    };
    supplier?: Supplier;
}
