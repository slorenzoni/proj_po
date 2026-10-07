<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AtletaController from '@/actions/App/Http/Controllers/Site/AtletaController';
import ComentarioController from '@/actions/App/Http/Controllers/Site/ComentarioController';
import DicaController from '@/actions/App/Http/Controllers/Site/DicaController';
import EventoController from '@/actions/App/Http/Controllers/Site/EventoController';
import PalpiteController from '@/actions/App/Http/Controllers/Site/PalpiteController';
import PlacarFanController from '@/actions/App/Http/Controllers/Site/PlacarFanController';
import SolicitacaoVerificacaoController from '@/actions/App/Http/Controllers/Site/SolicitacaoVerificacaoController';
import SelectInput from '@/components/admin/SelectInput.vue';
import TextArea from '@/components/admin/TextArea.vue';
import InputError from '@/components/InputError.vue';
import AthleteAvatar from '@/components/site/AthleteAvatar.vue';
import BannerSlot from '@/components/site/BannerSlot.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { formatarDataHora } from '@/lib/formatos';
import { login } from '@/routes';
import type { AtletaResumo, BannerSite, LutaResumo, Opcao } from '@/types';

type Luta = LutaResumo & {
    categoria: string;
    usa_rounds: boolean;
    round_atual: number | null;
    em_intervalo: boolean;
    tempo_fim: string | null;
    chance_do_a: string | null;
    chance_do_b: string | null;
    evento: {
        uuid: string;
        nome: string;
        data: string;
        video: string | null;
        link_youtube: string | null;
    };
};

type Palpite = {
    janela: {
        aberta: boolean;
        ao_vivo: boolean;
        peso: string | null;
        motivo: string | null;
    } | null;
    meu: {
        vencedor_id: number;
        metodo: string | null;
        metodo_label: string | null;
        round: number | null;
        peso_aplicado: string;
        pontos_obtidos: string | null;
    } | null;
    metodos: (Opcao & { permite_round: boolean })[];
    distribuicao: { a: number; b: number };
};

type Placar = {
    round_aberto: number | null;
    rounds_pontuados: number[];
    medias: {
        round: number;
        media_a: number;
        media_b: number;
        votos: number;
    }[];
    oficial: {
        juiz: string | null;
        round: number;
        pontos_atleta_a: number;
        pontos_atleta_b: number;
    }[];
};

const props = defineProps<{
    luta: Luta;
    palpite: Palpite;
    /** Nulo em modalidades sem rounds. */
    placar: Placar | null;
    comentarios: {
        uuid: string;
        autor: string | null;
        verificado: boolean;
        destaque: string | null;
        mensagem: string;
        criado_em: string | null;
    }[];
    podeComentar: boolean;
    dicas: {
        uuid: string;
        autor: string | null;
        destaque: string;
        texto: string;
        criado_em: string | null;
    }[];
    podeDarDica: boolean;
    banners: { luta: BannerSite[]; chat: BannerSite[] };
}>();

const page = usePage();

// Página pública: pode não haver usuário logado.
const logado = computed(() => page.props.auth.user !== null);

const participantes = computed<AtletaResumo[]>(() => [
    props.luta.participante_a,
    props.luta.participante_b,
]);

function nomeDoAtleta(id: number | null): string {
    return participantes.value.find((atleta) => atleta.id === id)?.nome ?? '—';
}

// --- Palpite -------------------------------------------------------------------------------

const metodoEscolhido = ref<string | number | null>(
    props.palpite.meu?.metodo ?? null,
);

// Sem método escolhido, o round pode ser informado nas modalidades com rounds.
const permiteRound = computed(() => {
    const metodo = props.palpite.metodos.find(
        (opcao) => String(opcao.value) === String(metodoEscolhido.value),
    );

    return metodo ? metodo.permite_round : props.luta.usa_rounds;
});

const rounds = computed<Opcao[]>(() =>
    Array.from({ length: props.luta.numero_rounds ?? 0 }, (_, indice) => ({
        value: indice + 1,
        label: `Round ${indice + 1}`,
    })),
);

const totalDePalpites = computed(
    () => props.palpite.distribuicao.a + props.palpite.distribuicao.b,
);

function percentual(quantidade: number): number {
    return totalDePalpites.value === 0
        ? 0
        : Math.round((quantidade / totalDePalpites.value) * 100);
}

// --- Placar dos fãs ------------------------------------------------------------------------

const podePontuarRound = computed(
    () =>
        props.placar !== null &&
        props.placar.round_aberto !== null &&
        !props.placar.rounds_pontuados.includes(props.placar.round_aberto),
);

const pontosPossiveis: Opcao[] = [10, 9, 8, 7].map((pontos) => ({
    value: pontos,
    label: String(pontos),
}));

const momento = computed(() => {
    if (props.luta.status.value !== 'em_andamento') {
        return null;
    }

    if (props.luta.round_atual === null) {
        return 'Em andamento';
    }

    return props.luta.em_intervalo
        ? `Intervalo após o round ${props.luta.round_atual}`
        : `Round ${props.luta.round_atual} de ${props.luta.numero_rounds}`;
});
</script>

<template>
    <Head
        :title="`${luta.participante_a.nome} × ${luta.participante_b.nome}`"
    />

    <div class="space-y-10">
        <header class="space-y-6">
            <p class="text-sm text-muted-foreground">
                <Link
                    :href="EventoController.show(luta.evento.uuid)"
                    class="underline-offset-4 hover:underline"
                >
                    {{ luta.evento.nome }}
                </Link>
                · {{ formatarDataHora(luta.evento.data) }} ·
                {{ luta.categoria }}
                <template v-if="luta.categoria_peso">
                    · {{ luta.categoria_peso }}
                </template>
            </p>

            <h1 class="sr-only">
                {{ luta.participante_a.nome }} contra
                {{ luta.participante_b.nome }}
            </h1>

            <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-4">
                <div
                    v-for="(atleta, indice) in participantes"
                    :key="atleta.uuid"
                    class="flex flex-col items-center gap-2 text-center"
                    :class="indice === 1 ? 'order-3' : 'order-1'"
                >
                    <AthleteAvatar
                        :nome="atleta.nome"
                        :foto="atleta.foto"
                        size="lg"
                    />
                    <Link
                        :href="AtletaController.show(atleta.uuid)"
                        class="text-lg font-semibold underline-offset-4 hover:underline"
                    >
                        {{ atleta.nome }}
                    </Link>
                    <p
                        v-if="atleta.apelido"
                        class="text-sm text-muted-foreground"
                    >
                        “{{ atleta.apelido }}”
                    </p>
                    <p class="text-sm tabular-nums">{{ atleta.cartel }}</p>
                    <p
                        v-if="
                            indice === 0 ? luta.chance_do_a : luta.chance_do_b
                        "
                        class="text-xs text-muted-foreground"
                    >
                        {{
                            indice === 0 ? luta.chance_do_a : luta.chance_do_b
                        }}% de chance
                    </p>
                    <Badge v-if="luta.vencedor_id === atleta.id">Venceu</Badge>
                </div>
                <div class="order-2 space-y-2 text-center">
                    <Badge
                        :variant="
                            luta.status.value === 'em_andamento'
                                ? 'default'
                                : 'outline'
                        "
                    >
                        {{ luta.status.label }}
                    </Badge>
                    <p v-if="momento" class="text-sm font-medium">
                        {{ momento }}
                    </p>
                    <p
                        v-else-if="luta.numero_rounds"
                        class="text-sm text-muted-foreground"
                    >
                        {{ luta.numero_rounds }} rounds
                    </p>
                </div>
            </div>

            <p
                v-if="luta.metodo_vitoria"
                class="rounded-lg border p-3 text-center text-sm"
            >
                Resultado:
                <span class="font-medium">
                    {{
                        luta.vencedor_id
                            ? nomeDoAtleta(luta.vencedor_id)
                            : 'sem vencedor'
                    }}
                </span>
                · {{ luta.metodo_vitoria }}
                <template v-if="luta.round_fim">
                    · round {{ luta.round_fim }}
                </template>
                <template v-if="luta.tempo_fim">
                    · {{ luta.tempo_fim }}</template
                >
            </p>
        </header>

        <section v-if="luta.evento.video || luta.evento.link_youtube">
            <div
                v-if="luta.evento.video"
                class="aspect-video overflow-hidden rounded-lg border"
            >
                <iframe
                    :src="luta.evento.video"
                    :title="`Transmissão de ${luta.evento.nome}`"
                    class="size-full"
                    allow="accelerometer; encrypted-media; picture-in-picture"
                    allowfullscreen
                    loading="lazy"
                />
            </div>
            <Button
                v-else-if="luta.evento.link_youtube"
                variant="outline"
                as-child
            >
                <a
                    :href="luta.evento.link_youtube"
                    target="_blank"
                    rel="noopener"
                >
                    Assistir no YouTube
                </a>
            </Button>
        </section>

        <BannerSlot :banners="banners.luta" />

        <div class="grid gap-10 lg:grid-cols-2">
            <section class="space-y-4">
                <h2 class="text-xl font-semibold">Seu palpite</h2>

                <div v-if="totalDePalpites > 0" class="space-y-1">
                    <div
                        class="flex h-2 overflow-hidden rounded-full bg-muted"
                        role="img"
                        :aria-label="`${percentual(palpite.distribuicao.a)}% dos palpites em ${luta.participante_a.nome} e ${percentual(palpite.distribuicao.b)}% em ${luta.participante_b.nome}`"
                    >
                        <div
                            class="bg-primary"
                            :style="{
                                width: `${percentual(palpite.distribuicao.a)}%`,
                            }"
                        />
                    </div>
                    <p
                        class="flex justify-between text-xs text-muted-foreground"
                    >
                        <span>
                            {{ percentual(palpite.distribuicao.a) }}%
                            {{ luta.participante_a.nome }}
                        </span>
                        <span>
                            {{ luta.participante_b.nome }}
                            {{ percentual(palpite.distribuicao.b) }}%
                        </span>
                    </p>
                </div>

                <div
                    v-if="palpite.meu"
                    class="rounded-lg border bg-muted/40 p-3 text-sm"
                >
                    <p>
                        Você palpitou em
                        <span class="font-semibold">
                            {{ nomeDoAtleta(palpite.meu.vencedor_id) }}
                        </span>
                        <template v-if="palpite.meu.metodo_label">
                            por {{ palpite.meu.metodo_label }}
                        </template>
                        <template v-if="palpite.meu.round">
                            no round {{ palpite.meu.round }}
                        </template>
                        .
                    </p>
                    <p class="text-muted-foreground">
                        Peso do palpite: {{ palpite.meu.peso_aplicado }}%
                        <template v-if="palpite.meu.pontos_obtidos !== null">
                            · Pontos obtidos:
                            <span class="font-semibold text-foreground">
                                {{ palpite.meu.pontos_obtidos }}
                            </span>
                        </template>
                    </p>
                </div>

                <p
                    v-if="!logado"
                    class="rounded-lg border border-dashed p-4 text-sm"
                >
                    <Link :href="login()" class="font-medium underline">
                        Entre na sua conta
                    </Link>
                    para dar o seu palpite.
                </p>

                <p
                    v-else-if="palpite.janela && !palpite.janela.aberta"
                    class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                >
                    {{ palpite.janela.motivo }}
                </p>

                <Form
                    v-else-if="palpite.janela"
                    v-bind="PalpiteController.store.form(luta.uuid)"
                    :options="{ preserveScroll: true }"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                >
                    <p
                        v-if="palpite.janela.ao_vivo"
                        class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-200/20 dark:bg-amber-500/10 dark:text-amber-100"
                    >
                        Intervalo: trocar o palpite agora faz ele valer
                        {{ palpite.janela.peso }}% dos pontos, mesmo que você
                        volte ao palpite anterior depois.
                    </p>

                    <fieldset class="grid gap-2">
                        <legend class="mb-2 text-sm font-medium">
                            Quem vence?
                        </legend>
                        <div class="grid grid-cols-2 gap-2">
                            <label
                                v-for="atleta in participantes"
                                :key="atleta.uuid"
                                class="flex cursor-pointer items-center gap-2 rounded-lg border p-3 text-sm font-medium has-checked:border-primary has-checked:bg-primary/5"
                            >
                                <input
                                    type="radio"
                                    name="vencedor_id"
                                    :value="atleta.id"
                                    :checked="
                                        palpite.meu?.vencedor_id === atleta.id
                                    "
                                    required
                                    class="accent-primary"
                                />
                                {{ atleta.nome }}
                            </label>
                        </div>
                        <InputError :message="errors.vencedor_id" />
                    </fieldset>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="metodo">Método (opcional)</Label>
                            <SelectInput
                                id="metodo"
                                v-model="metodoEscolhido"
                                name="metodo"
                                :options="palpite.metodos"
                                placeholder="Não informar"
                            />
                            <InputError :message="errors.metodo" />
                        </div>

                        <div v-if="permiteRound" class="grid gap-2">
                            <Label for="round">Round (opcional)</Label>
                            <SelectInput
                                id="round"
                                name="round"
                                :options="rounds"
                                :default-value="palpite.meu?.round"
                                placeholder="Não informar"
                            />
                            <InputError :message="errors.round" />
                        </div>
                    </div>

                    <p class="text-xs text-muted-foreground">
                        Método e round só pontuam se você acertar o vencedor.
                    </p>

                    <Button type="submit" :disabled="processing">
                        {{ palpite.meu ? 'Trocar palpite' : 'Enviar palpite' }}
                    </Button>
                </Form>
            </section>

            <section v-if="placar" class="space-y-4">
                <header class="space-y-0.5">
                    <h2 class="text-xl font-semibold">Placar dos fãs</h2>
                    <p class="text-sm text-muted-foreground">
                        Pontue cada round ao final dele: 10 para quem venceu o
                        round e de 7 a 9 para o outro (10-10 em caso de empate).
                    </p>
                </header>

                <table
                    v-if="placar.medias.length > 0"
                    class="w-full text-sm tabular-nums"
                >
                    <thead class="text-left text-muted-foreground">
                        <tr>
                            <th scope="col" class="py-1 font-medium">Round</th>
                            <th scope="col" class="py-1 font-medium">
                                {{ luta.participante_a.nome }}
                            </th>
                            <th scope="col" class="py-1 font-medium">
                                {{ luta.participante_b.nome }}
                            </th>
                            <th scope="col" class="py-1 font-medium">Votos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="media in placar.medias"
                            :key="media.round"
                            class="border-t"
                        >
                            <td class="py-1.5">{{ media.round }}</td>
                            <td class="py-1.5">{{ media.media_a }}</td>
                            <td class="py-1.5">{{ media.media_b }}</td>
                            <td class="py-1.5">{{ media.votos }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="text-sm text-muted-foreground">
                    Nenhum round pontuado pela comunidade ainda.
                </p>

                <Form
                    v-if="logado && podePontuarRound"
                    v-bind="PlacarFanController.store.form(luta.uuid)"
                    :options="{ preserveScroll: true }"
                    class="space-y-3 rounded-lg bg-muted/40 p-3"
                    v-slot="{ errors, processing }"
                >
                    <p class="text-sm font-medium">
                        Pontue o round {{ placar.round_aberto }}
                    </p>
                    <input
                        type="hidden"
                        name="round"
                        :value="placar.round_aberto"
                    />
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <Label for="pontos_atleta_a">
                                {{ luta.participante_a.nome }}
                            </Label>
                            <SelectInput
                                id="pontos_atleta_a"
                                name="pontos_atleta_a"
                                :options="pontosPossiveis"
                                :default-value="10"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="pontos_atleta_b">
                                {{ luta.participante_b.nome }}
                            </Label>
                            <SelectInput
                                id="pontos_atleta_b"
                                name="pontos_atleta_b"
                                :options="pontosPossiveis"
                                :default-value="9"
                            />
                        </div>
                    </div>
                    <InputError
                        :message="
                            errors.round ??
                            errors.pontos_atleta_a ??
                            errors.pontos_atleta_b
                        "
                    />
                    <Button type="submit" :disabled="processing">
                        Enviar pontuação
                    </Button>
                </Form>
                <p
                    v-else-if="
                        placar.round_aberto !== null &&
                        placar.rounds_pontuados.includes(placar.round_aberto)
                    "
                    class="text-sm text-muted-foreground"
                >
                    Você já pontuou o round {{ placar.round_aberto }}.
                </p>

                <div v-if="placar.oficial.length > 0" class="space-y-2">
                    <h3 class="text-sm font-medium">Placar oficial</h3>
                    <table class="w-full text-sm tabular-nums">
                        <thead class="text-left text-muted-foreground">
                            <tr>
                                <th scope="col" class="py-1 font-medium">
                                    Round
                                </th>
                                <th scope="col" class="py-1 font-medium">
                                    Juiz
                                </th>
                                <th scope="col" class="py-1 font-medium">
                                    {{ luta.participante_a.nome }}
                                </th>
                                <th scope="col" class="py-1 font-medium">
                                    {{ luta.participante_b.nome }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(linha, indice) in placar.oficial"
                                :key="indice"
                                class="border-t"
                            >
                                <td class="py-1.5">{{ linha.round }}</td>
                                <td class="py-1.5">{{ linha.juiz }}</td>
                                <td class="py-1.5">
                                    {{ linha.pontos_atleta_a }}
                                </td>
                                <td class="py-1.5">
                                    {{ linha.pontos_atleta_b }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <section class="space-y-4">
            <header class="space-y-0.5">
                <h2 class="text-xl font-semibold">Dicas dos especialistas</h2>
                <p class="text-sm text-muted-foreground">
                    Análises de comentaristas e de Membros verificados.
                </p>
            </header>

            <Form
                v-if="podeDarDica"
                v-bind="DicaController.store.form(luta.uuid)"
                :options="{ preserveScroll: true }"
                reset-on-success
                class="space-y-2"
                v-slot="{ errors, processing }"
            >
                <Label for="dica" class="sr-only">Sua dica</Label>
                <TextArea
                    id="dica"
                    name="texto"
                    :rows="4"
                    :maxlength="2000"
                    required
                    placeholder="Compartilhe sua análise da luta…"
                />
                <InputError :message="errors.texto" />
                <Button type="submit" :disabled="processing">
                    Publicar dica
                </Button>
            </Form>
            <p
                v-else-if="logado"
                class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
            >
                Dar dicas é exclusivo de comentaristas e de Membros com selo de
                verificado.
                <Link
                    :href="SolicitacaoVerificacaoController.index()"
                    class="font-medium text-foreground underline"
                >
                    Saiba como obter o selo.
                </Link>
            </p>

            <ul class="space-y-3">
                <li
                    v-for="dica in dicas"
                    :key="dica.uuid"
                    class="rounded-lg border border-primary/30 bg-primary/5 p-4"
                >
                    <p class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="font-medium">{{ dica.autor }}</span>
                        <Badge>{{ dica.destaque }}</Badge>
                        <span class="text-xs text-muted-foreground">
                            {{ formatarDataHora(dica.criado_em) }}
                        </span>
                    </p>
                    <p class="mt-2 text-sm whitespace-pre-line">
                        {{ dica.texto }}
                    </p>
                </li>
                <li
                    v-if="dicas.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Nenhuma dica para esta luta ainda.
                </li>
            </ul>
        </section>

        <section class="grid gap-6 lg:grid-cols-[1fr_18rem]">
            <div class="space-y-4">
                <header class="space-y-0.5">
                    <h2 class="text-xl font-semibold">Comentários</h2>
                    <p class="text-sm text-muted-foreground">
                        Novos comentários aparecem ao recarregar a página.
                    </p>
                </header>

                <Form
                    v-if="podeComentar"
                    v-bind="ComentarioController.store.form(luta.uuid)"
                    :options="{ preserveScroll: true }"
                    reset-on-success
                    class="space-y-2"
                    v-slot="{ errors, processing }"
                >
                    <Label for="mensagem" class="sr-only">Seu comentário</Label>
                    <TextArea
                        id="mensagem"
                        name="mensagem"
                        :rows="3"
                        :maxlength="500"
                        required
                        placeholder="Escreva um comentário…"
                    />
                    <InputError :message="errors.mensagem" />
                    <Button type="submit" :disabled="processing">
                        Comentar
                    </Button>
                </Form>
                <p
                    v-else
                    class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                >
                    <template v-if="logado">
                        Comentar é exclusivo do plano Membro.
                    </template>
                    <template v-else>
                        <Link :href="login()" class="font-medium underline">
                            Entre na sua conta
                        </Link>
                        para comentar (plano Membro).
                    </template>
                </p>

                <ul class="space-y-3">
                    <li
                        v-for="comentario in comentarios"
                        :key="comentario.uuid"
                        class="rounded-lg border p-3"
                    >
                        <p class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="font-medium">
                                {{ comentario.autor }}
                            </span>
                            <Badge v-if="comentario.destaque">
                                {{ comentario.destaque }}
                            </Badge>
                            <Badge
                                v-if="comentario.verificado"
                                variant="secondary"
                            >
                                Verificado
                            </Badge>
                            <span class="text-xs text-muted-foreground">
                                {{ formatarDataHora(comentario.criado_em) }}
                            </span>
                        </p>
                        <p class="mt-1 text-sm whitespace-pre-line">
                            {{ comentario.mensagem }}
                        </p>
                    </li>
                    <li
                        v-if="comentarios.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Nenhum comentário ainda.
                    </li>
                </ul>
            </div>

            <BannerSlot :banners="banners.chat" />
        </section>
    </div>
</template>
