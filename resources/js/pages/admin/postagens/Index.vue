<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import PostagemController from '@/actions/App/Http/Controllers/Admin/PostagemController';
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

type Postagem = {
    uuid: string;
    titulo: string;
    autor: string | null;
    status: string;
    patrocinado: boolean;
    data_publicacao: string | null;
};

defineProps<{
    postagens: Paginated<Postagem>;
    filtros: { busca: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs('Blog', PostagemController.index()),
    },
});
</script>

<template>
    <Head title="Blog" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Blog"
            description="Postagens do site, patrocinadas ou não."
        >
            <Button as-child>
                <Link :href="PostagemController.create()">
                    <Plus /> Nova postagem
                </Link>
            </Button>
        </PageHeader>

        <SearchForm
            :action="PostagemController.index.url()"
            :busca="filtros.busca"
            placeholder="Buscar por título…"
        />

        <DataTable
            :columns="[
                { key: 'titulo', label: 'Título' },
                { key: 'autor', label: 'Autor' },
                { key: 'status', label: 'Situação' },
                { key: 'data_publicacao', label: 'Publicação' },
            ]"
            :rows="postagens.data"
        >
            <template #cell-titulo="{ row }">
                <span class="flex items-center gap-2 font-medium">
                    {{ row.titulo }}
                    <Badge v-if="row.patrocinado" variant="outline">
                        Patrocinado
                    </Badge>
                </span>
            </template>
            <template #cell-status="{ row }">
                <Badge variant="secondary">{{ row.status }}</Badge>
            </template>
            <template #cell-data_publicacao="{ row }">
                {{ formatarData(row.data_publicacao) }}
            </template>
            <template #actions="{ row }">
                <RowActions
                    :nome="row.titulo"
                    :edit-href="PostagemController.edit.url(row.uuid)"
                    :delete-href="PostagemController.destroy.url(row.uuid)"
                />
            </template>
        </DataTable>

        <Pagination :page="postagens" />
    </div>
</template>
