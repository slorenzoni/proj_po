<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import PatrocinadorController from '@/actions/App/Http/Controllers/Admin/PatrocinadorController';
import DataTable from '@/components/admin/DataTable.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import RowActions from '@/components/admin/RowActions.vue';
import SearchForm from '@/components/admin/SearchForm.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { adminBreadcrumbs } from '@/lib/adminNav';
import { formatarData } from '@/lib/formatos';
import type { Paginated } from '@/types';

type Patrocinador = {
    uuid: string;
    nome: string;
    status: string;
    data_fim_contrato: string | null;
    logo: string | null;
};

defineProps<{
    patrocinadores: Paginated<Patrocinador>;
    filtros: { busca: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs(
            'Patrocinadores',
            PatrocinadorController.index(),
        ),
    },
});
</script>

<template>
    <Head title="Patrocinadores" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Patrocinadores"
            description="Empresas que contratam banners no site."
        >
            <Button as-child>
                <Link :href="PatrocinadorController.create()">
                    <Plus /> Novo patrocinador
                </Link>
            </Button>
        </PageHeader>

        <SearchForm
            :action="PatrocinadorController.index.url()"
            :busca="filtros.busca"
            placeholder="Buscar por nome…"
        />

        <DataTable
            :columns="[
                { key: 'nome', label: 'Nome' },
                { key: 'status', label: 'Situação' },
                { key: 'data_fim_contrato', label: 'Fim do contrato' },
            ]"
            :rows="patrocinadores.data"
        >
            <template #cell-nome="{ row }">
                <span class="flex items-center gap-3 font-medium">
                    <img
                        v-if="row.logo"
                        :src="row.logo"
                        alt=""
                        class="size-8 rounded object-contain"
                    />
                    {{ row.nome }}
                </span>
            </template>
            <template #cell-status="{ row }">
                <Badge variant="secondary">{{ row.status }}</Badge>
            </template>
            <template #cell-data_fim_contrato="{ row }">
                {{ formatarData(row.data_fim_contrato) }}
            </template>
            <template #actions="{ row }">
                <RowActions
                    :nome="row.nome"
                    :edit-href="PatrocinadorController.edit.url(row.uuid)"
                    :delete-href="PatrocinadorController.destroy.url(row.uuid)"
                />
            </template>
        </DataTable>

        <Pagination :page="patrocinadores" />
    </div>
</template>
