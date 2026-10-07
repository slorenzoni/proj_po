<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import AtletaController from '@/actions/App/Http/Controllers/Admin/AtletaController';
import DataTable from '@/components/admin/DataTable.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import RowActions from '@/components/admin/RowActions.vue';
import SearchForm from '@/components/admin/SearchForm.vue';
import AthleteAvatar from '@/components/site/AthleteAvatar.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { adminBreadcrumbs } from '@/lib/adminNav';
import type { Paginated } from '@/types';

type Atleta = {
    uuid: string;
    nome: string;
    apelido: string | null;
    pais: string | null;
    cartel: string;
    invicto: boolean;
    foto: string | null;
};

defineProps<{
    atletas: Paginated<Atleta>;
    filtros: { busca: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs('Atletas', AtletaController.index()),
    },
});
</script>

<template>
    <Head title="Atletas" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Atletas"
            description="Cadastro dos lutadores, com cartel, fotos e estilos de luta."
        >
            <Button as-child>
                <Link :href="AtletaController.create()">
                    <Plus /> Novo atleta
                </Link>
            </Button>
        </PageHeader>

        <SearchForm
            :action="AtletaController.index.url()"
            :busca="filtros.busca"
            placeholder="Buscar por nome ou apelido…"
        />

        <DataTable
            :columns="[
                { key: 'nome', label: 'Nome' },
                { key: 'apelido', label: 'Apelido' },
                { key: 'pais', label: 'País' },
                { key: 'cartel', label: 'Cartel (V-D-E)' },
            ]"
            :rows="atletas.data"
        >
            <template #cell-nome="{ row }">
                <span class="flex items-center gap-3 font-medium">
                    <AthleteAvatar
                        :nome="row.nome"
                        :foto="row.foto"
                        size="sm"
                    />
                    {{ row.nome }}
                </span>
            </template>
            <template #cell-cartel="{ row }">
                <span class="flex items-center gap-2 tabular-nums">
                    {{ row.cartel }}
                    <Badge v-if="row.invicto" variant="secondary"
                        >Invicto</Badge
                    >
                </span>
            </template>
            <template #actions="{ row }">
                <RowActions
                    :nome="row.nome"
                    :edit-href="AtletaController.edit.url(row.uuid)"
                    :delete-href="AtletaController.destroy.url(row.uuid)"
                />
            </template>
        </DataTable>

        <Pagination :page="atletas" />
    </div>
</template>
