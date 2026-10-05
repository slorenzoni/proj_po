<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BlogController from '@/actions/App/Http/Controllers/Site/BlogController';
import EventoController from '@/actions/App/Http/Controllers/Site/EventoController';
import RankingController from '@/actions/App/Http/Controllers/Site/RankingController';
import BannerSlot from '@/components/site/BannerSlot.vue';
import FightCard from '@/components/site/FightCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatarDataHora } from '@/lib/formatos';
import { register } from '@/routes';
import type { BannerSite, LutaResumo } from '@/types';

type Evento = {
    uuid: string;
    nome: string;
    organizacao: string | null;
    data: string;
    local: string | null;
    ao_vivo: boolean;
    luta_principal: LutaResumo | null;
};

defineProps<{
    eventos: Evento[];
    ranking: {
        posicao: number | null;
        nome: string | null;
        verificado: boolean;
        pontos: string;
    }[];
    postagens: {
        slug: string;
        titulo: string;
        resumo: string | null;
        capa: string | null;
        patrocinado: boolean;
    }[];
    banners: BannerSite[];
}>();
</script>

<template>
    <Head title="Palpites em lutas" />

    <div class="space-y-12">
        <section class="space-y-4">
            <h1 class="max-w-2xl text-3xl font-semibold tracking-tight">
                Dê o seu palpite nas lutas e dispute o ranking
            </h1>
            <p class="max-w-2xl text-muted-foreground">
                Escolha o vencedor, o método e o round de cada luta de MMA, Boxe
                e Judô. Acertou? Você pontua e sobe no ranking.
            </p>
            <div class="flex flex-wrap gap-2">
                <Button as-child>
                    <Link :href="EventoController.index()">
                        Ver próximos eventos
                    </Link>
                </Button>
                <Button
                    v-if="!$page.props.auth.user"
                    variant="outline"
                    as-child
                >
                    <Link :href="register()">Criar conta grátis</Link>
                </Button>
            </div>
        </section>

        <BannerSlot :banners="banners" />

        <section class="space-y-4">
            <div class="flex items-end justify-between gap-4">
                <h2 class="text-xl font-semibold">Próximos eventos</h2>
                <Link
                    :href="EventoController.index()"
                    class="text-sm underline-offset-4 hover:underline"
                >
                    Ver todos
                </Link>
            </div>

            <p
                v-if="eventos.length === 0"
                class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
            >
                Nenhum evento agendado no momento.
            </p>

            <div class="grid gap-6 lg:grid-cols-2">
                <article
                    v-for="evento in eventos"
                    :key="evento.uuid"
                    class="space-y-3"
                >
                    <header>
                        <Link
                            :href="EventoController.show(evento.uuid)"
                            class="flex items-center gap-2 font-semibold underline-offset-4 hover:underline"
                        >
                            {{ evento.nome }}
                            <Badge v-if="evento.ao_vivo">Ao vivo</Badge>
                        </Link>
                        <p class="text-sm text-muted-foreground">
                            {{
                                [
                                    evento.organizacao,
                                    formatarDataHora(evento.data),
                                    evento.local,
                                ]
                                    .filter(Boolean)
                                    .join(' · ')
                            }}
                        </p>
                    </header>
                    <FightCard
                        v-if="evento.luta_principal"
                        :luta="evento.luta_principal"
                    />
                </article>
            </div>
        </section>

        <div class="grid gap-12 lg:grid-cols-[1fr_20rem]">
            <section class="space-y-4">
                <div class="flex items-end justify-between gap-4">
                    <h2 class="text-xl font-semibold">Do blog</h2>
                    <Link
                        :href="BlogController.index()"
                        class="text-sm underline-offset-4 hover:underline"
                    >
                        Ver todas
                    </Link>
                </div>

                <p
                    v-if="postagens.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhuma postagem publicada ainda.
                </p>

                <ul class="space-y-4">
                    <li v-for="postagem in postagens" :key="postagem.slug">
                        <Link
                            :href="BlogController.show(postagem.slug)"
                            class="flex gap-4 rounded-lg border p-3 transition-colors hover:bg-accent/50"
                        >
                            <img
                                v-if="postagem.capa"
                                :src="postagem.capa"
                                alt=""
                                class="h-20 w-28 shrink-0 rounded object-cover"
                            />
                            <span class="min-w-0 space-y-1">
                                <span
                                    class="flex flex-wrap items-center gap-2 font-medium"
                                >
                                    {{ postagem.titulo }}
                                    <Badge
                                        v-if="postagem.patrocinado"
                                        variant="outline"
                                    >
                                        Patrocinado
                                    </Badge>
                                </span>
                                <span
                                    v-if="postagem.resumo"
                                    class="block text-sm text-muted-foreground"
                                >
                                    {{ postagem.resumo }}
                                </span>
                            </span>
                        </Link>
                    </li>
                </ul>
            </section>

            <section class="space-y-4">
                <div class="flex items-end justify-between gap-4">
                    <h2 class="text-xl font-semibold">Ranking geral</h2>
                    <Link
                        :href="RankingController.geral()"
                        class="text-sm underline-offset-4 hover:underline"
                    >
                        Completo
                    </Link>
                </div>

                <p
                    v-if="ranking.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    O ranking começa quando a primeira luta for encerrada.
                </p>

                <ol class="divide-y rounded-lg border">
                    <li
                        v-for="linha in ranking"
                        :key="linha.posicao ?? linha.nome ?? ''"
                        class="flex items-center gap-3 px-3 py-2 text-sm"
                    >
                        <span
                            class="w-6 font-semibold text-muted-foreground tabular-nums"
                        >
                            {{ linha.posicao }}º
                        </span>
                        <span class="min-w-0 flex-1 truncate">
                            {{ linha.nome }}
                        </span>
                        <span class="font-medium tabular-nums">
                            {{ linha.pontos }}
                        </span>
                    </li>
                </ol>
            </section>
        </div>
    </div>
</template>
