<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Settings } from 'lucide-vue-next';
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
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const page = usePage();

const getDashboardUrl = () => {
    const user = page.props.auth.user;
    if (user && user.roles && user.roles.includes('admin')) {
        return '/admin/dashboard';
    }
    if (user && user.site && user.roles && user.roles.includes('SiteAdmin')) {
        return `/${user.site.slug}/dashboard`;
    }
    return '/';
};

const canManageOwnSite = () => {
    const user = page.props.auth.user;
    if (!user || !user.permissions) {
        return false;
    }

    // Check if user has manage-own-site permission
    const hasPermission = user.permissions.includes('manage-own-site');

    // Check if user has a site they own
    // This would be checked on the backend, but we can do basic validation here
    return hasPermission;
};

const mainNavItems: NavItem[] = [
    {
        title: 'Bảng điều khiển',
        href: getDashboardUrl(),
        icon: LayoutGrid,
    },
];

// Add site management link if user has permission
if (canManageOwnSite()) {
    mainNavItems.push({
        title: 'Quản lý trang web',
        href: '/settings/site',
        icon: Settings,
    });
}

const footerNavItems: NavItem[] = [
];
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
