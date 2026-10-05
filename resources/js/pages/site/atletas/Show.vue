<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import LutaController from '@/actions/App/Http/Controllers/Site/LutaController';
import AthleteAvatar from '@/components/site/AthleteAvatar.vue';
import { Badge } from '@/components/ui/badge';
import { formatarDataHora } from '@/lib/formatos';

type Atleta = {
    uuid: string;
    nome: string;
    apelido: string | null;
    equipe: string | null;
    pais: string | null;
    cidade_natal: string | null;
    idade: number | null;
    altura_cm: number | null;
    peso_kg: string | null;
    alcance_cm: number | null;
    stance: string | null;
    biografia: string | null;
    vitorias: number;
    vitorias_ko: number;
    vitorias_submissao: number;
    vitorias_decisao: number;
    empates: number;
    derrotas: number;
    derrotas_ko: number;
    derrotas_submissao: number;
    derrotas_decisao: number;
    invicto: boolean;
    ranking: number | null;
    fotos: { url: string | null; principal: boolean }[];
    estilos: { estilo: string | null; treinador: string | null }[];
};

const props = defineProps<{
    atleta: Atleta;
    lutas: {
        uuid: string;
        evento: string;
        data: string;
        adversario: string | null;
        status: string;
        venceu: boolean | null;
        metodo_vitoria: string | null;
    }[];
}>();

const fotoPrincipal = computed(
    () => props.atleta.fotos.find((foto) => foto.principal)?.url ?? null,
);

const outrasFotos = computed(() =>
    props.atleta.fotos.filter((foto) => !foto.principal && foto.url),
);

const ficha = computed(() =>
    [
        { rotulo: 'País', valor: props.atleta.pais },
        { rotulo: 'Cidade natal', valor: props.atleta.cidade_natal },
        { rotulo: 'Equipe', valor: props.atleta.equipe },
        {
            rotulo: 'Idade',
            valor: props.atleta.idade ? `${props.atleta.idade} anos` : null,
        },
        {
            rotulo: 'Altura',
            valor: props.atleta.altura_cm
                ? `${props.atleta.altura_cm} cm`
                : null,
        },
        {
            rotulo: 'Peso',
            valor: props.atleta.peso_kg ? `${props.atleta.peso_kg} kg` : null,
        },
        {
            rotulo: 'Alcance',
            valor: props.atleta.alcance_cm
                ? `${props.atleta.alcance_cm} cm`
                : null,
        },
        { rotulo: 'Base', valor: props.atleta.stance },
        {
            rotulo: 'Ranking',
            valor: props.atleta.ranking ? `${props.atleta.ranking}º` : null,
        },
    ].filter((item) => item.valor),
);
</script>

<template>
    <Head :title="atleta.nome" />

    <div class="space-y-10">
        <header class="flex flex-wrap items-center gap-6">
            <AthleteAvatar
                :nome="atleta.nome"
                :foto="fotoPrincipal"
                size="lg"
            />
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ atleta.nome }}
                </h1>
                <p v-if="atleta.apelido" class="text-muted-foreground">
                    “{{ atleta.apelido }}”
                </p>
                <p
                    class="flex flex-wrap items-center gap-2 text-lg tabular-nums"
                >
                    {{ atleta.vitorias }}-{{ atleta.derrotas }}-{{
                        atleta.empates
                    }}
                    <span class="text-sm text-muted-foreground">
                        (vitórias-derrotas-empates)
                    </span>
                    <Badge v-if="atleta.invicto" variant="secondary">
                        Invicto
                    </Badge>
                </p>
            </div>
        </header>

        <div class="grid gap-10 lg:grid-cols-[1fr_20rem]">
            <div class="space-y-10">
                <section v-if="atleta.biografia" class="space-y-2">
                    <h2 class="text-xl font-semibold">Biografia</h2>
                    <p class="whitespace-pre-line text-muted-foreground">
                        {{ atleta.biografia }}
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-semibold">Cartel</h2>
                    <table class="w-full max-w-md text-sm tabular-nums">
                        <thead class="text-left text-muted-foreground">
                            <tr>
                                <th scope="col" class="py-1 font-medium"></th>
                                <th scope="col" class="py-1 font-medium">
                                    KO/TKO
                                </th>
                                <th scope="col" class="py-1 font-medium">
                                    Submissão
                                </th>
                                <th scope="col" class="py-1 font-medium">
                                    Decisão
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t">
                                <th scope="row" class="py-1.5 text-left">
                                    Vitórias
                                </th>
                                <td class="py-1.5">{{ atleta.vitorias_ko }}</td>
                                <td class="py-1.5">
                                    {{ atleta.vitorias_submissao }}
                                </td>
                                <td class="py-1.5">
                                    {{ atleta.vitorias_decisao }}
                                </td>
                            </tr>
                            <tr class="border-t">
                                <th scope="row" class="py-1.5 text-left">
                                    Derrotas
                                </th>
                                <td class="py-1.5">{{ atleta.derrotas_ko }}</td>
                                <td class="py-1.5">
                                    {{ atleta.derrotas_submissao }}
                                </td>
                                <td class="py-1.5">
                                    {{ atleta.derrotas_decisao }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-semibold">Lutas</h2>
                    <p
                        v-if="lutas.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Nenhuma luta cadastrada.
                    </p>
                    <ul class="divide-y rounded-lg border">
                        <li v-for="luta in lutas" :key="luta.uuid">
                            <Link
                                :href="LutaController.show(luta.uuid)"
                                class="flex flex-wrap items-center justify-between gap-2 px-3 py-2.5 text-sm transition-colors hover:bg-accent/50"
                            >
                                <span>
                                    <span class="font-medium">
                                        vs. {{ luta.adversario }}
                                    </span>
                                    <span class="text-muted-foreground">
                                        · {{ luta.evento }} ·
                                        {{ formatarDataHora(luta.data) }}
                                    </span>
                                </span>
                                <span class="flex items-center gap-2">
                                    <span
                                        v-if="luta.metodo_vitoria"
                                        class="text-muted-foreground"
                                    >
                                        {{ luta.metodo_vitoria }}
                                    </span>
                                    <Badge
                                        v-if="luta.venceu !== null"
                                        :variant="
                                            luta.venceu ? 'default' : 'outline'
                                        "
                                    >
                                        {{
                                            luta.venceu ? 'Vitória' : 'Derrota'
                                        }}
                                    </Badge>
                                    <Badge v-else variant="secondary">
                                        {{ luta.status }}
                                    </Badge>
                                </span>
                            </Link>
                        </li>
                    </ul>
                </section>
            </div>

            <aside class="space-y-8">
                <section v-if="ficha.length > 0" class="space-y-3">
                    <h2 class="text-xl font-semibold">Ficha</h2>
                    <dl class="divide-y rounded-lg border text-sm">
                        <div
                            v-for="item in ficha"
                            :key="item.rotulo"
                            class="flex justify-between gap-4 px-3 py-2"
                        >
                            <dt class="text-muted-foreground">
                                {{ item.rotulo }}
                            </dt>
                            <dd class="text-right font-medium">
                                {{ item.valor }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section v-if="atleta.estilos.length > 0" class="space-y-3">
                    <h2 class="text-xl font-semibold">Estilos de luta</h2>
                    <ul class="space-y-2 text-sm">
                        <li
                            v-for="(vinculo, indice) in atleta.estilos"
                            :key="indice"
                            class="rounded-lg border px-3 py-2"
                        >
                            <span class="font-medium">{{
                                vinculo.estilo
                            }}</span>
                            <span
                                v-if="vinculo.treinador"
                                class="block text-muted-foreground"
                            >
                                Treinador: {{ vinculo.treinador }}
                            </span>
                        </li>
                    </ul>
                </section>

                <section v-if="outrasFotos.length > 0" class="space-y-3">
                    <h2 class="text-xl font-semibold">Fotos</h2>
                    <div class="grid grid-cols-2 gap-2">
                        <img
                            v-for="(foto, indice) in outrasFotos"
                            :key="indice"
                            :src="foto.url ?? ''"
                            :alt="`Foto de ${atleta.nome}`"
                            class="aspect-square w-full rounded-lg border object-cover"
                        />
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>
