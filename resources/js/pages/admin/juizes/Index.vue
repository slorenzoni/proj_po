<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import JuizController from '@/actions/App/Http/Controllers/Admin/JuizController';
import DataTable from '@/components/admin/DataTable.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import RowActions from '@/components/admin/RowActions.vue';
import SearchForm from '@/components/admin/SearchForm.vue';
import { Button } from '@/components/ui/button';
import { adminBreadcrumbs } from '@/lib/adminNav';
import type { Paginated } from '@/types';

type Juiz = {
    uuid: string;
    nome: string;
    pais: string | null;
    certificado_por: string | null;
};

defineProps<{
    juizes: Paginated<Juiz>;
    filtros: { busca: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs('Juízes', JuizController.index()),
    },
});
</script>

<template>
    <Head title="Juízes" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Juízes"
            description="Árbitros e juízes laterais. A função em cada luta é definida na escalação da luta."
        >
            <Button as-child>
                <Link :href="JuizController.create()"><Plus /> Novo juiz</Link>
            </Button>
        </PageHeader>

        <SearchForm
            :action="JuizController.index.url()"
            :busca="filtros.busca"
            placeholder="Buscar por nome…"
        />

        <DataTable
            :columns="[
                { key: 'nome', label: 'Nome' },
                { key: 'pais', label: 'País' },
                { key: 'certificado_por', label: 'Certificado por' },
            ]"
            :rows="juizes.data"
        >
            <template #cell-nome="{ row }">
                <span class="font-medium">{{ row.nome }}</span>
            </template>
            <template #actions="{ row }">
                <RowActions
                    :nome="row.nome"
                    :edit-href="JuizController.edit.url(row.uuid)"
                    :delete-href="JuizController.destroy.url(row.uuid)"
                />
            </template>
        </DataTable>

        <Pagination :page="juizes" />
    </div>
</template>
