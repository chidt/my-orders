<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Settings, KeySquareIcon } from 'lucide-vue-next';
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
import { index as RolesIndex } from '@/routes/admin/roles';
import { edit as SiteEdit } from '@/routes/site';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const page = usePage();
const { can } = usePermissions();

const getDashboardUrl = () => {
    const user = page.props.auth.user;
    if (user && user.roles && user.roles.includes('Admin')) {
        return '/admin/dashboard';
    }
    if (user && user.roles && user.roles.includes('SiteAdmin')) {
        // Use current page site data if available (e.g., on settings page after slug update)
        const currentSite = page.props.site || user.site;
        if (currentSite) {
            return `/${currentSite.slug}/dashboard`;
        }
    }
    return '/';
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
        icon: KeySquareIcon,
        show: can('view_roles'),
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
