<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Settings,
    LucideUserKey,
    Warehouse,
    MapPin,
    ShieldUser,
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
import { edit as SiteEdit } from '@/routes/site';
import site from '@/routes/site';
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


const mainNavItems: NavItem[] = [
    {
        title: 'Bảng điều khiển',
        href: getDashboardUrl(),
        icon: LayoutGrid,
        show: true,
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
