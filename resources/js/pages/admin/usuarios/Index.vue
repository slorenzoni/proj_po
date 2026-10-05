<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import UsuarioController from '@/actions/App/Http/Controllers/Admin/UsuarioController';
import DataTable from '@/components/admin/DataTable.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import SearchForm from '@/components/admin/SearchForm.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { adminBreadcrumbs } from '@/lib/adminNav';
import type { Paginated } from '@/types';

type Usuario = {
    uuid: string;
    name: string;
    email: string;
    cliente: boolean;
    nivel_acesso: string | null;
};

defineProps<{
    usuarios: Paginated<Usuario>;
    filtros: { busca: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs('Usuários', UsuarioController.index()),
    },
});
</script>

<template>
    <Head title="Usuários" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Usuários"
            description="Contas cadastradas. Abra uma conta para gerenciar papéis e o perfil de administrador."
        />

        <SearchForm
            :action="UsuarioController.index.url()"
            :busca="filtros.busca"
            placeholder="Buscar por nome ou e-mail…"
        />

        <DataTable
            :columns="[
                { key: 'name', label: 'Nome' },
                { key: 'email', label: 'E-mail' },
                { key: 'perfis', label: 'Perfis' },
            ]"
            :rows="usuarios.data"
        >
            <template #cell-name="{ row }">
                <span class="font-medium">{{ row.name }}</span>
            </template>
            <template #cell-perfis="{ row }">
                <span class="flex flex-wrap gap-1">
                    <Badge v-if="row.cliente" variant="outline">Cliente</Badge>
                    <Badge v-if="row.nivel_acesso" variant="secondary">
                        Admin · {{ row.nivel_acesso }}
                    </Badge>
                </span>
            </template>
            <template #actions="{ row }">
                <Button variant="outline" size="sm" as-child>
                    <Link :href="UsuarioController.show(row.uuid)">
                        Gerenciar
                    </Link>
                </Button>
            </template>
        </DataTable>

        <Pagination :page="usuarios" />
    </div>
</template>
