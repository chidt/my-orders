<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid } from 'lucide-vue-next';
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

const mainNavItems: NavItem[] = [
    {
        title: 'Bảng điều khiển',
        href: getDashboardUrl(),
        icon: LayoutGrid,
    },
];

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
