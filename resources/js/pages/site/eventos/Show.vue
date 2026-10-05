<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Trophy } from '@lucide/vue';
import RankingController from '@/actions/App/Http/Controllers/Site/RankingController';
import BannerSlot from '@/components/site/BannerSlot.vue';
import FightCard from '@/components/site/FightCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatarDataHora } from '@/lib/formatos';
import type { BannerSite, LutaResumo } from '@/types';

type Evento = {
    uuid: string;
    nome: string;
    organizacao: { uuid: string; nome: string } | null;
    data: string;
    local: string;
    status: { value: string; label: string };
    tipo_transmissao: string | null;
    link_youtube: string | null;
    lutas: LutaResumo[];
};

defineProps<{
    evento: Evento;
    banners: BannerSite[];
}>();
</script>

<template>
    <Head :title="evento.nome" />

    <div class="space-y-8">
        <header class="space-y-3">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ evento.nome }}
                </h1>
                <Badge
                    :variant="
                        evento.status.value === 'ao_vivo'
                            ? 'default'
                            : 'outline'
                    "
                >
                    {{ evento.status.label }}
                </Badge>
            </div>
            <p class="text-muted-foreground">
                {{
                    [
                        evento.organizacao?.nome,
                        formatarDataHora(evento.data),
                        evento.local,
                    ]
                        .filter(Boolean)
                        .join(' · ')
                }}
            </p>
            <div class="flex flex-wrap gap-2">
                <Button v-if="evento.link_youtube" variant="outline" as-child>
                    <a
                        :href="evento.link_youtube"
                        target="_blank"
                        rel="noopener"
                    >
                        Assistir no YouTube
                        <template v-if="evento.tipo_transmissao">
                            ({{ evento.tipo_transmissao }})
                        </template>
                    </a>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="RankingController.evento(evento.uuid)">
                        <Trophy /> Ranking do evento
                    </Link>
                </Button>
            </div>
        </header>

        <BannerSlot :banners="banners" />

        <section class="space-y-4">
            <h2 class="text-xl font-semibold">Card de lutas</h2>

            <p
                v-if="evento.lutas.length === 0"
                class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
            >
                O card deste evento ainda não foi divulgado.
            </p>

            <ol class="grid gap-4 lg:grid-cols-2">
                <li v-for="luta in evento.lutas" :key="luta.uuid">
                    <FightCard :luta="luta" />
                </li>
            </ol>
        </section>
    </div>
</template>
