<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, LayoutGrid, ShieldCheck } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
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
import { dashboard } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import type { NavItem } from '@/types';

const page = usePage();

// O item "Administração" só aparece para administradores; o acesso real é protegido no servidor.
const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Painel',
        href: dashboard(),
        icon: LayoutGrid,
    },
    ...(page.props.auth.isAdministrador
        ? [
              {
                  title: 'Administração',
                  href: adminDashboard(),
                  icon: ShieldCheck,
              },
          ]
        : []),
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Repositório',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentação',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
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
