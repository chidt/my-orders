export interface Attribute {
    id: number;
    name: string;
    code: string;
    description?: string | null;
    order: number;
    site_id: number;
    product_attribute_values_count?: number;
    created_at: string;
    updated_at: string;
}

export interface AttributeListProps {
    site: {
        id: number;
        name: string;
        slug: string;
    };
    attributes: {
        data: Attribute[];
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

export interface AttributeFormProps {
    site: {
        id: number;
        name: string;
        slug: string;
    };
    attribute?: Attribute;
}
