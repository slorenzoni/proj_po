<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import CategoriaController from '@/actions/App/Http/Controllers/Admin/CategoriaController';
import DataTable from '@/components/admin/DataTable.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import RowActions from '@/components/admin/RowActions.vue';
import SearchForm from '@/components/admin/SearchForm.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { adminBreadcrumbs } from '@/lib/adminNav';
import type { Paginated } from '@/types';

type Categoria = {
    uuid: string;
    nome: string;
    usa_rounds: boolean;
    categorias_peso_count: number;
};

defineProps<{
    categorias: Paginated<Categoria>;
    filtros: { busca: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs(
            'Categorias',
            CategoriaController.index(),
        ),
    },
});
</script>

<template>
    <Head title="Categorias" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Categorias"
            description="Modalidades (MMA, Boxe, Judô) e suas categorias de peso."
        >
            <Button as-child>
                <Link :href="CategoriaController.create()">
                    <Plus /> Nova categoria
                </Link>
            </Button>
        </PageHeader>

        <SearchForm
            :action="CategoriaController.index.url()"
            :busca="filtros.busca"
            placeholder="Buscar por nome…"
        />

        <DataTable
            :columns="[
                { key: 'nome', label: 'Nome' },
                { key: 'usa_rounds', label: 'Rounds' },
                { key: 'categorias_peso_count', label: 'Categorias de peso' },
            ]"
            :rows="categorias.data"
        >
            <template #cell-nome="{ row }">
                <span class="font-medium">{{ row.nome }}</span>
            </template>
            <template #cell-usa_rounds="{ row }">
                <Badge :variant="row.usa_rounds ? 'secondary' : 'outline'">
                    {{ row.usa_rounds ? 'Com rounds' : 'Sem rounds' }}
                </Badge>
            </template>
            <template #actions="{ row }">
                <RowActions
                    :nome="row.nome"
                    :edit-href="CategoriaController.edit.url(row.uuid)"
                    :delete-href="CategoriaController.destroy.url(row.uuid)"
                />
            </template>
        </DataTable>

        <Pagination :page="categorias" />
    </div>
</template>
