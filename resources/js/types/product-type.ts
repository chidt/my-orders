export interface ProductType {
    id: number;
    name: string;
    order: number;
    show_on_front: boolean;
    color: string;
    products_count: number;
    site_id: number;
    created_at: string;
    updated_at: string;
}

export interface ProductTypeFormData {
    name: string;
    order: number;
    show_on_front: boolean;
    color: string;
}

export interface ProductTypeFilters {
    search?: string;
    show_on_front?: boolean;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
}

export interface PaginatedProductTypes {
    data: ProductType[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}
