<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import EstiloDeLutaController from '@/actions/App/Http/Controllers/Admin/EstiloDeLutaController';
import DataTable from '@/components/admin/DataTable.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import RowActions from '@/components/admin/RowActions.vue';
import SearchForm from '@/components/admin/SearchForm.vue';
import { Button } from '@/components/ui/button';
import { adminBreadcrumbs } from '@/lib/adminNav';
import type { Paginated } from '@/types';

type Estilo = { uuid: string; nome: string };

defineProps<{
    estilos: Paginated<Estilo>;
    filtros: { busca: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs(
            'Estilos de luta',
            EstiloDeLutaController.index(),
        ),
    },
});
</script>

<template>
    <Head title="Estilos de luta" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Estilos de luta"
            description="Estilos praticados pelos atletas, como Jiu-Jitsu e Muay Thai."
        >
            <Button as-child>
                <Link :href="EstiloDeLutaController.create()">
                    <Plus /> Novo estilo
                </Link>
            </Button>
        </PageHeader>

        <SearchForm
            :action="EstiloDeLutaController.index.url()"
            :busca="filtros.busca"
            placeholder="Buscar por nome…"
        />

        <DataTable
            :columns="[{ key: 'nome', label: 'Nome' }]"
            :rows="estilos.data"
        >
            <template #cell-nome="{ row }">
                <span class="font-medium">{{ row.nome }}</span>
            </template>
            <template #actions="{ row }">
                <RowActions
                    :nome="row.nome"
                    :edit-href="EstiloDeLutaController.edit.url(row.uuid)"
                    :delete-href="EstiloDeLutaController.destroy.url(row.uuid)"
                />
            </template>
        </DataTable>

        <Pagination :page="estilos" />
    </div>
</template>
