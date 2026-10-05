<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import RankingController from '@/actions/App/Http/Controllers/Site/RankingController';
import DataTable from '@/components/admin/DataTable.vue';
import Pagination from '@/components/admin/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

type Linha = {
    uuid: string;
    posicao: number | null;
    nome: string | null;
    verificado: boolean;
    pontos: string;
    palpites_perfeitos: number;
    vencedores_corretos: number;
    sou_eu: boolean;
};

defineProps<{
    escopo: { tipo: 'geral' | 'evento' | 'organizacao'; titulo: string };
    linhas: Paginated<Linha>;
    minhaPosicao: { posicao: number | null; pontos: string } | null;
    organizacoes: { uuid: string; nome: string }[];
    eventos: { uuid: string; nome: string }[];
}>();
</script>

<template>
    <Head :title="escopo.titulo" />

    <div class="space-y-8">
        <header class="space-y-2">
            <h1 class="text-2xl font-semibold tracking-tight">
                {{
                    escopo.tipo === 'geral'
                        ? 'Ranking geral'
                        : `Ranking · ${escopo.titulo}`
                }}
            </h1>
            <p class="max-w-2xl text-sm text-muted-foreground">
                Pontos somados dos palpites em lutas já encerradas. Em caso de
                empate, vale quem tem mais palpites perfeitos, depois mais
                vencedores corretos e, por fim, quem palpitou primeiro.
            </p>
        </header>

        <p
            v-if="minhaPosicao"
            class="rounded-lg border bg-muted/40 p-3 text-sm"
        >
            Você está em
            <span class="font-semibold">{{ minhaPosicao.posicao }}º lugar</span>
            com
            <span class="font-semibold">{{ minhaPosicao.pontos }} pontos</span>.
        </p>

        <div class="grid gap-8 lg:grid-cols-[1fr_16rem]">
            <div class="space-y-4">
                <DataTable
                    :columns="[
                        { key: 'posicao', label: 'Posição', class: 'w-px' },
                        { key: 'nome', label: 'Usuário' },
                        { key: 'pontos', label: 'Pontos' },
                        { key: 'palpites_perfeitos', label: 'Perfeitos' },
                        { key: 'vencedores_corretos', label: 'Vencedores' },
                    ]"
                    :rows="linhas.data"
                    empty-message="Ninguém pontuou neste ranking ainda."
                >
                    <template #cell-posicao="{ row }">
                        <span class="font-semibold tabular-nums">
                            {{ row.posicao }}º
                        </span>
                    </template>
                    <template #cell-nome="{ row }">
                        <span class="flex flex-wrap items-center gap-2">
                            <span :class="row.sou_eu ? 'font-semibold' : ''">
                                {{ row.nome }}
                            </span>
                            <Badge v-if="row.sou_eu">Você</Badge>
                            <Badge v-if="row.verificado" variant="secondary">
                                Verificado
                            </Badge>
                        </span>
                    </template>
                    <template #cell-pontos="{ row }">
                        <span class="font-medium tabular-nums">
                            {{ row.pontos }}
                        </span>
                    </template>
                </DataTable>

                <Pagination :page="linhas" />
            </div>

            <aside class="space-y-6 text-sm">
                <Button
                    v-if="escopo.tipo !== 'geral'"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="RankingController.geral()">
                        Ver ranking geral
                    </Link>
                </Button>

                <nav v-if="organizacoes.length > 0" class="space-y-2">
                    <h2 class="font-medium">Por organização</h2>
                    <ul class="space-y-1">
                        <li
                            v-for="organizacao in organizacoes"
                            :key="organizacao.uuid"
                        >
                            <Link
                                :href="
                                    RankingController.organizacao(
                                        organizacao.uuid,
                                    )
                                "
                                class="text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                            >
                                {{ organizacao.nome }}
                            </Link>
                        </li>
                    </ul>
                </nav>

                <nav v-if="eventos.length > 0" class="space-y-2">
                    <h2 class="font-medium">Por evento</h2>
                    <ul class="space-y-1">
                        <li v-for="evento in eventos" :key="evento.uuid">
                            <Link
                                :href="RankingController.evento(evento.uuid)"
                                class="text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                            >
                                {{ evento.nome }}
                            </Link>
                        </li>
                    </ul>
                </nav>
            </aside>
        </div>
    </div>
</template>
