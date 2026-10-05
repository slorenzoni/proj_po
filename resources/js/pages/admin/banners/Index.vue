<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import BannerController from '@/actions/App/Http/Controllers/Admin/BannerController';
import DataTable from '@/components/admin/DataTable.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import RowActions from '@/components/admin/RowActions.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { adminBreadcrumbs } from '@/lib/adminNav';
import type { Paginated } from '@/types';

type Banner = {
    uuid: string;
    imagem: string | null;
    patrocinador: string | null;
    posicao: string;
    status: string;
    periodo: string;
    impressoes: number;
    cliques: number;
};

defineProps<{
    banners: Paginated<Banner>;
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs('Banners', BannerController.index()),
    },
});
</script>

<template>
    <Head title="Banners" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Banners"
            description="Peças dos patrocinadores, por posição do site e período de exibição."
        >
            <Button as-child>
                <Link :href="BannerController.create()">
                    <Plus /> Novo banner
                </Link>
            </Button>
        </PageHeader>

        <DataTable
            :columns="[
                { key: 'imagem', label: 'Peça' },
                { key: 'patrocinador', label: 'Patrocinador' },
                { key: 'posicao', label: 'Posição' },
                { key: 'periodo', label: 'Período' },
                { key: 'status', label: 'Situação' },
                { key: 'impressoes', label: 'Impressões' },
                { key: 'cliques', label: 'Cliques' },
            ]"
            :rows="banners.data"
        >
            <template #cell-imagem="{ row }">
                <img
                    v-if="row.imagem"
                    :src="row.imagem"
                    :alt="`Banner de ${row.patrocinador}`"
                    class="h-10 w-24 rounded border object-cover"
                />
            </template>
            <template #cell-status="{ row }">
                <Badge variant="secondary">{{ row.status }}</Badge>
            </template>
            <template #actions="{ row }">
                <RowActions
                    :nome="`banner de ${row.patrocinador}`"
                    :edit-href="BannerController.edit.url(row.uuid)"
                    :delete-href="BannerController.destroy.url(row.uuid)"
                />
            </template>
        </DataTable>

        <Pagination :page="banners" />
    </div>
</template>
