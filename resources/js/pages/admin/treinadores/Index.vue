<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import TreinadorController from '@/actions/App/Http/Controllers/Admin/TreinadorController';
import DataTable from '@/components/admin/DataTable.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import RowActions from '@/components/admin/RowActions.vue';
import SearchForm from '@/components/admin/SearchForm.vue';
import { Button } from '@/components/ui/button';
import { adminBreadcrumbs } from '@/lib/adminNav';
import type { Paginated } from '@/types';

type Treinador = {
    uuid: string;
    nome: string;
    pais: string | null;
    email_usuario: string | null;
};

defineProps<{
    treinadores: Paginated<Treinador>;
    filtros: { busca: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs(
            'Treinadores',
            TreinadorController.index(),
        ),
    },
});
</script>

<template>
    <Head title="Treinadores" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Treinadores"
            description="Treinadores dos atletas, por estilo de luta."
        >
            <Button as-child>
                <Link :href="TreinadorController.create()">
                    <Plus /> Novo treinador
                </Link>
            </Button>
        </PageHeader>

        <SearchForm
            :action="TreinadorController.index.url()"
            :busca="filtros.busca"
            placeholder="Buscar por nome…"
        />

        <DataTable
            :columns="[
                { key: 'nome', label: 'Nome' },
                { key: 'pais', label: 'País' },
                { key: 'email_usuario', label: 'Conta vinculada' },
            ]"
            :rows="treinadores.data"
        >
            <template #cell-nome="{ row }">
                <span class="font-medium">{{ row.nome }}</span>
            </template>
            <template #actions="{ row }">
                <RowActions
                    :nome="row.nome"
                    :edit-href="TreinadorController.edit.url(row.uuid)"
                    :delete-href="TreinadorController.destroy.url(row.uuid)"
                />
            </template>
        </DataTable>

        <Pagination :page="treinadores" />
    </div>
</template>
