<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EventoController from '@/actions/App/Http/Controllers/Site/EventoController';
import LutaController from '@/actions/App/Http/Controllers/Site/LutaController';
import RankingController from '@/actions/App/Http/Controllers/Site/RankingController';
import DataTable from '@/components/admin/DataTable.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatarDataHora } from '@/lib/formatos';
import { dashboard } from '@/routes';
import type { Paginated } from '@/types';

type Palpite = {
    uuid: string;
    luta_uuid: string;
    luta: string;
    evento: string;
    data: string;
    situacao: string;
    vencedor: string | null;
    metodo: string | null;
    round: number | null;
    peso_aplicado: string;
    pontos_obtidos: string | null;
};

defineProps<{
    resumo: {
        plano: string;
        cliente: boolean;
        pontos: string;
        posicao: number | null;
        palpites: number;
        palpites_perfeitos: number;
    };
    palpites: Paginated<Palpite>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Meus palpites',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Meus palpites" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Meus palpites"
            description="Sua pontuação e os palpites que você já deu."
        >
            <Button as-child>
                <Link :href="EventoController.index()">Ver eventos</Link>
            </Button>
        </PageHeader>

        <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border p-4">
                <dt class="text-sm text-muted-foreground">Pontos</dt>
                <dd class="text-2xl font-semibold tabular-nums">
                    {{ resumo.pontos }}
                </dd>
            </div>
            <div class="rounded-lg border p-4">
                <dt class="text-sm text-muted-foreground">Ranking geral</dt>
                <dd class="text-2xl font-semibold tabular-nums">
                    <Link
                        v-if="resumo.posicao"
                        :href="RankingController.geral()"
                        class="underline-offset-4 hover:underline"
                    >
                        {{ resumo.posicao }}º
                    </Link>
                    <template v-else>—</template>
                </dd>
            </div>
            <div class="rounded-lg border p-4">
                <dt class="text-sm text-muted-foreground">Palpites</dt>
                <dd class="text-2xl font-semibold tabular-nums">
                    {{ resumo.palpites }}
                    <span class="text-sm font-normal text-muted-foreground">
                        · {{ resumo.palpites_perfeitos }} perfeitos
                    </span>
                </dd>
            </div>
            <div class="rounded-lg border p-4">
                <dt class="text-sm text-muted-foreground">Plano</dt>
                <dd class="text-2xl font-semibold">{{ resumo.plano }}</dd>
            </div>
        </dl>

        <p
            v-if="!resumo.cliente"
            class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
        >
            Esta conta não tem perfil de cliente, por isso não pode palpitar.
        </p>

        <DataTable
            :columns="[
                { key: 'luta', label: 'Luta' },
                { key: 'palpite', label: 'Palpite' },
                { key: 'situacao', label: 'Situação' },
                { key: 'peso_aplicado', label: 'Peso' },
                { key: 'pontos_obtidos', label: 'Pontos' },
            ]"
            :rows="palpites.data"
            empty-message="Você ainda não deu nenhum palpite."
        >
            <template #cell-luta="{ row }">
                <Link
                    :href="LutaController.show(row.luta_uuid)"
                    class="font-medium underline-offset-4 hover:underline"
                >
                    {{ row.luta }}
                </Link>
                <span class="block text-xs text-muted-foreground">
                    {{ row.evento }} · {{ formatarDataHora(row.data) }}
                </span>
            </template>
            <template #cell-palpite="{ row }">
                {{ row.vencedor }}
                <span
                    v-if="row.metodo || row.round"
                    class="block text-xs text-muted-foreground"
                >
                    {{
                        [row.metodo, row.round ? `round ${row.round}` : null]
                            .filter(Boolean)
                            .join(' · ')
                    }}
                </span>
            </template>
            <template #cell-situacao="{ row }">
                <Badge variant="outline">{{ row.situacao }}</Badge>
            </template>
            <template #cell-peso_aplicado="{ row }">
                <span class="tabular-nums">{{ row.peso_aplicado }}%</span>
            </template>
            <template #cell-pontos_obtidos="{ row }">
                <span class="font-medium tabular-nums">
                    {{ row.pontos_obtidos ?? '—' }}
                </span>
            </template>
        </DataTable>

        <Pagination :page="palpites" />
    </div>
</template>
