<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus, Radio } from '@lucide/vue';
import AndamentoLutaController from '@/actions/App/Http/Controllers/Admin/AndamentoLutaController';
import EventoController from '@/actions/App/Http/Controllers/Admin/EventoController';
import LutaController from '@/actions/App/Http/Controllers/Admin/LutaController';
import DataTable from '@/components/admin/DataTable.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import RowActions from '@/components/admin/RowActions.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { adminBreadcrumbs } from '@/lib/adminNav';
import { formatarDataHora } from '@/lib/formatos';

type Luta = {
    uuid: string;
    ordem_na_card: number;
    tipo_card: string | null;
    participante_a: string | null;
    participante_b: string | null;
    categoria_peso: string | null;
    numero_rounds: number | null;
    status: string;
};

type Evento = {
    uuid: string;
    nome: string;
    organizacao: string | null;
    data: string;
    local: string | null;
    status: string;
    lutas: Luta[];
};

defineProps<{
    evento: Evento;
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
    <Head :title="evento.nome" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            :title="evento.nome"
            :description="
                [
                    evento.organizacao,
                    formatarDataHora(evento.data),
                    evento.local,
                ]
                    .filter(Boolean)
                    .join(' · ')
            "
        >
            <Badge variant="secondary">{{ evento.status }}</Badge>
            <Button variant="outline" as-child>
                <Link :href="EventoController.edit(evento.uuid)">
                    <Pencil /> Editar evento
                </Link>
            </Button>
            <Button as-child>
                <Link :href="LutaController.create(evento.uuid)">
                    <Plus /> Adicionar luta
                </Link>
            </Button>
        </PageHeader>

        <section class="space-y-3">
            <h2 class="text-base font-medium">Card de lutas</h2>

            <DataTable
                :columns="[
                    { key: 'ordem_na_card', label: 'Ordem', class: 'w-px' },
                    { key: 'luta', label: 'Luta' },
                    { key: 'categoria_peso', label: 'Categoria de peso' },
                    { key: 'tipo_card', label: 'Card' },
                    { key: 'numero_rounds', label: 'Rounds' },
                    { key: 'status', label: 'Situação' },
                ]"
                :rows="evento.lutas"
                empty-message="Nenhuma luta no card ainda."
            >
                <template #cell-luta="{ row }">
                    <span class="font-medium">
                        {{ row.participante_a }}
                        <span class="font-normal text-muted-foreground">×</span>
                        {{ row.participante_b }}
                    </span>
                </template>
                <template #cell-status="{ row }">
                    <Badge variant="outline">{{ row.status }}</Badge>
                </template>
                <template #actions="{ row }">
                    <RowActions
                        :nome="`${row.participante_a} × ${row.participante_b}`"
                        :edit-href="LutaController.edit.url(row.uuid)"
                        :delete-href="LutaController.destroy.url(row.uuid)"
                    >
                        <Button variant="outline" size="sm" as-child>
                            <Link
                                :href="AndamentoLutaController.show(row.uuid)"
                            >
                                <Radio /> Andamento
                            </Link>
                        </Button>
                    </RowActions>
                </template>
            </DataTable>
        </section>
    </div>
</template>
