<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Boxes,
    ContactRound,
    FolderTree,
    Handshake,
    Layers,
    LayoutGrid,
    ListOrdered,
    LucideUserKey,
    Package,
    Receipt,
    Settings,
    ShieldUser,
    ShoppingCart,
    Tag,
    ToggleLeftIcon,
    Warehouse,
} from 'lucide-vue-next';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/composables/usePermissions';
import { index as PermissionsIndex } from '@/routes/admin/permissions';
import { index as RolesIndex } from '@/routes/admin/roles';
import site, { edit as SiteEdit } from '@/routes/site';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const page = usePage();
const { can } = usePermissions();

interface Site {
    id: number;
    slug: string;
    name: string;
}

interface User {
    id: number;
    name: string;
    roles: string[];
    site?: Site;
}

const getDashboardUrl = () => {
    const user = page.props.auth.user as User;
    if (user && user.roles && user.roles.includes('Admin')) {
        return '/admin/dashboard';
    }
    if (user && user.roles && user.roles.includes('SiteAdmin')) {
        // Use current page site data if available (e.g., on settings page after slug update)
        const currentSite = (page.props.site as Site) || user.site;
        if (currentSite) {
            return `/${currentSite.slug}/dashboard`;
        }
    }
    return '/';
};

const getWarehouseUrl = (): string | null => {
    const user = page.props.auth.user as User;
    const currentSite = (page.props.site as Site) || user?.site;
    if (currentSite) {
        return site.warehouses.index.url(currentSite);
    }
    return null;
};

const getProductTypesUrl = (): string | null => {
    const user = page.props.auth.user as User;
    const currentSite = (page.props.site as Site) || user?.site;
    if (currentSite) {
        return `/${currentSite.slug}/product-types`;
    }
    return null;
};

const getProductsUrl = (): string | null => {
    const user = page.props.auth.user as User;
    const currentSite = (page.props.site as Site) || user?.site;
    if (currentSite) {
        return `/${currentSite.slug}/products`;
    }
    return null;
};

const getCategoriesUrl = (): string | null => {
    const user = page.props.auth.user as User;
    const currentSite = (page.props.site as Site) || user?.site;
    if (currentSite) {
        return `/${currentSite.slug}/categories`;
    }
    return null;
};

const getTagsUrl = (): string | null => {
    const user = page.props.auth.user as User;
    const currentSite = (page.props.site as Site) || user?.site;
    if (currentSite) {
        return `/${currentSite.slug}/tags`;
    }
    return null;
};

const getSupplierUrl = (): string | null => {
    const user = page.props.auth.user as User;
    const currentSite = (page.props.site as Site) || user?.site;
    if (currentSite) {
        return site.suppliers.index.url(currentSite.slug);
    }
    return null;
};

const getAttributesUrl = (): string | null => {
    const user = page.props.auth.user as User;
    const currentSite = (page.props.site as Site) || user?.site;
    if (currentSite) {
        return site.attributes.index.url(currentSite.slug);
    }
    return null;
};
const getCustomerUrl = (): string | null => {
    const user = page.props.auth.user as User;
    const currentSite = (page.props.site as Site) || user?.site;
    if (currentSite) {
        return `/${currentSite.slug}/customers`;
    }
    return null;
};

const getOrdersUrl = (): string | null => {
    const user = page.props.auth.user as User;
    const currentSite = (page.props.site as Site) || user?.site;
    if (currentSite) {
        return `/${currentSite.slug}/orders`;
    }
    return null;
};

const getOrderDetailsUrl = (): string | null => {
    const user = page.props.auth.user as User;
    const currentSite = (page.props.site as Site) || user?.site;
    if (currentSite) {
        return `/${currentSite.slug}/order-details`;
    }
    return null;
};

const mainNavItems: NavItem[] = [
    {
        title: 'Bảng điều khiển',
        href: getDashboardUrl(),
        icon: LayoutGrid,
        show: true,
    },
    {
        title: 'Đơn hàng',
        href: '#', // Dummy href for parent item
        icon: ShoppingCart,
        show:
            ((can('view_orders') || can('manage_orders')) &&
                getOrdersUrl() !== null) ||
            ((can('view_order_details') || can('manage_orders')) &&
                getOrderDetailsUrl() !== null),
        children: [
            {
                title: 'Danh sách đơn hàng',
                href: getOrdersUrl() || '',
                icon: ListOrdered,
                show: can('manage_orders') && getOrdersUrl() !== null,
            },
            {
                title: 'Đơn hàng chi tiết',
                href: getOrderDetailsUrl() || '',
                icon: Receipt,
                show: can('manage_orders') && getOrderDetailsUrl() !== null,
            },
        ],
    },
    {
        title: 'Sản phẩm',
        href: '#', // Dummy href for parent item
        icon: Package,
        show:
            ((can('view_products') || can('manage_products')) &&
                getProductsUrl() !== null) ||
            (can('view_product_types') && getProductTypesUrl() !== null) ||
            (can('view_attributes') && getAttributesUrl() !== null) ||
            ((can('view_categories') || can('manage_categories')) &&
                getCategoriesUrl() !== null) ||
            ((can('view_tags') || can('manage_tags')) &&
                getTagsUrl() !== null) ||
            (can('manage_suppliers') && getSupplierUrl() !== null),
        children: [
            {
                title: 'Sản phẩm',
                href: getProductsUrl() || '',
                icon: Boxes,
                show:
                    (can('view_products') || can('manage_products')) &&
                    getProductsUrl() !== null,
            },
            {
                title: 'Loại sản phẩm',
                href: getProductTypesUrl() || '',
                icon: ToggleLeftIcon,
                show:
                    can('view_product_types') && getProductTypesUrl() !== null,
            },
            {
                title: 'Thuộc tính',
                href: getAttributesUrl() || '',
                icon: Layers,
                show: can('view_attributes') && getAttributesUrl() !== null,
            },
            {
                title: 'Quản lý danh mục',
                href: getCategoriesUrl() || '',
                icon: FolderTree,
                show:
                    (can('view_categories') || can('manage_categories')) &&
                    getCategoriesUrl() !== null,
            },
            {
                title: 'Quản lý thẻ',
                href: getTagsUrl() || '',
                icon: Tag,
                show:
                    (can('view_tags') || can('manage_tags')) &&
                    getTagsUrl() !== null,
            },
            {
                title: 'Nhà cung cấp',
                href: getSupplierUrl() || '',
                icon: Handshake,
                show: can('manage_suppliers') && getSupplierUrl() !== null,
            },
        ],
    },
    {
        title: 'Khách hàng',
        href: getCustomerUrl() || '',
        icon: ContactRound,
        show:
            (can('view_customers') || can('manage_customers')) &&
            getCustomerUrl() !== null,
    },
    {
        title: 'Quản lý vai trò',
        href: RolesIndex(),
        icon: LucideUserKey,
        show: can('view_roles'),
    },
    {
        title: 'Quản lý quyền',
        href: PermissionsIndex(),
        icon: ShieldUser,
        show: can('view_permissions'),
    },
    {
        title: 'Quản lý kho',
        href: getWarehouseUrl() || '',
        icon: Warehouse,
        show: can('manage_warehouses') && getWarehouseUrl() !== null,
    },
    {
        title: 'Quản lý trang web',
        href: SiteEdit(),
        icon: Settings,
        show: can('manage_own_site'),
    },
];

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="getDashboardUrl()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
