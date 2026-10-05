<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ListOrdered, Plus } from '@lucide/vue';
import EventoController from '@/actions/App/Http/Controllers/Admin/EventoController';
import DataTable from '@/components/admin/DataTable.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import RowActions from '@/components/admin/RowActions.vue';
import SearchForm from '@/components/admin/SearchForm.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { adminBreadcrumbs } from '@/lib/adminNav';
import { formatarDataHora } from '@/lib/formatos';
import type { Paginated } from '@/types';

type Evento = {
    uuid: string;
    nome: string;
    organizacao: string | null;
    data: string;
    status: string;
    lutas_count: number;
};

defineProps<{
    eventos: Paginated<Evento>;
    filtros: { busca: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs(
            'Eventos e lutas',
            EventoController.index(),
        ),
    },
});
</script>

<template>
    <Head title="Eventos" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Eventos"
            description="Abra um evento para montar o card e acompanhar as lutas."
        >
            <Button as-child>
                <Link :href="EventoController.create()">
                    <Plus /> Novo evento
                </Link>
            </Button>
        </PageHeader>

        <SearchForm
            :action="EventoController.index.url()"
            :busca="filtros.busca"
            placeholder="Buscar por nome…"
        />

        <DataTable
            :columns="[
                { key: 'nome', label: 'Evento' },
                { key: 'organizacao', label: 'Organização' },
                { key: 'data', label: 'Data' },
                { key: 'status', label: 'Situação' },
                { key: 'lutas_count', label: 'Lutas' },
            ]"
            :rows="eventos.data"
        >
            <template #cell-nome="{ row }">
                <Link
                    :href="EventoController.show(row.uuid)"
                    class="font-medium underline-offset-4 hover:underline"
                >
                    {{ row.nome }}
                </Link>
            </template>
            <template #cell-data="{ row }">
                {{ formatarDataHora(row.data) }}
            </template>
            <template #cell-status="{ row }">
                <Badge variant="secondary">{{ row.status }}</Badge>
            </template>
            <template #actions="{ row }">
                <RowActions
                    :nome="row.nome"
                    :edit-href="EventoController.edit.url(row.uuid)"
                    :delete-href="EventoController.destroy.url(row.uuid)"
                >
                    <Button variant="ghost" size="icon" as-child>
                        <Link
                            :href="EventoController.show(row.uuid)"
                            :aria-label="`Card de ${row.nome}`"
                        >
                            <ListOrdered />
                        </Link>
                    </Button>
                </RowActions>
            </template>
        </DataTable>

        <Pagination :page="eventos" />
    </div>
</template>
