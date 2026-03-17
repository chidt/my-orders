export interface Tag {
    id: number;
    name: string;
    slug: string;
    products_count: number;
    site_id: number;
    created_at: string;
    updated_at: string;
}

export interface TagFilters {
    search?: string;
    usage?: 'all' | 'used' | 'unused';
    sort_by?: 'name' | 'products_count' | 'created_at';
    sort_direction?: 'asc' | 'desc';
}

export interface TagFormData {
    name: string;
    slug?: string;
}

export interface TagStats {
    total: number;
    used: number;
    unused: number;
    most_popular?: Tag;
}

export interface TagMergeData {
    primary_tag_id: number;
    tag_ids: number[];
}

export interface TagBulkAction {
    action: 'delete' | 'merge' | 'export' | 'bulk-delete-unused';
    tag_ids?: number[];
    data?: Record<string, any>;
}

export interface PopularTag extends Tag {
    usage_rank: number;
}
