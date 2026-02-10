export type Site = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    settings: {
        product_prefix?: string;
        [key: string]: unknown;
    };
    user_id: number;
    created_at: string;
    updated_at: string;
};

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    site?: Site;
    roles?: string[];
    can?: Record<string, boolean>;
    permissions?: string[];
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
