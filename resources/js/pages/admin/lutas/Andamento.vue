<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AndamentoLutaController from '@/actions/App/Http/Controllers/Admin/AndamentoLutaController';
import EventoController from '@/actions/App/Http/Controllers/Admin/EventoController';
import LutaController from '@/actions/App/Http/Controllers/Admin/LutaController';
import PlacarController from '@/actions/App/Http/Controllers/Admin/PlacarController';
import DataTable from '@/components/admin/DataTable.vue';
import DeleteButton from '@/components/admin/DeleteButton.vue';
import FormField from '@/components/admin/FormField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import SelectInput from '@/components/admin/SelectInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { adminBreadcrumbs } from '@/lib/adminNav';
import type { Opcao } from '@/types';

type Participante = { id: number; nome: string | null };

type Luta = {
    uuid: string;
    evento: { uuid: string; nome: string };
    participante_a: Participante;
    participante_b: Participante;
    status: string;
    usa_rounds: boolean;
    numero_rounds: number | null;
    round_atual: number | null;
    em_intervalo: boolean;
    vencedor: string | null;
    metodo_vitoria: string | null;
    round_fim: number | null;
    tempo_fim: string | null;
};

type Placar = {
    uuid: string;
    juiz: string | null;
    round: number;
    pontos_atleta_a: number;
    pontos_atleta_b: number;
};

const props = defineProps<{
    luta: Luta;
    acoes: {
        iniciar: boolean;
        encerrarRound: boolean;
        iniciarProximoRound: boolean;
        encerrar: boolean;
        cancelar: boolean;
    };
    metodos: (Opcao & { tem_vencedor: boolean })[];
    juizesLaterais: Opcao[];
    placares: Placar[];
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs(
            'Eventos e lutas',
            EventoController.index(),
        ),
    },
});

const metodoEscolhido = ref<string | number | null>(null);

// Empate e "sem resultado" não têm vencedor: o campo some do formulário.
const exigeVencedor = computed(
    () =>
        props.metodos.find(
            (metodo) => String(metodo.value) === String(metodoEscolhido.value),
        )?.tem_vencedor ?? true,
);

const momento = computed(() => {
    if (props.luta.round_atual === null) {
        return null;
    }

    return props.luta.em_intervalo
        ? `Intervalo após o round ${props.luta.round_atual}`
        : `Round ${props.luta.round_atual} de ${props.luta.numero_rounds}`;
});

const rounds = computed<Opcao[]>(() =>
    Array.from({ length: props.luta.numero_rounds ?? 0 }, (_, indice) => ({
        value: indice + 1,
        label: `Round ${indice + 1}`,
    })),
);
</script>

<template>
    <Head title="Andamento da luta" />

    <div class="flex h-full flex-1 flex-col gap-8 p-4">
        <PageHeader
            :title="`${luta.participante_a.nome} × ${luta.participante_b.nome}`"
            :description="`Evento: ${luta.evento.nome}`"
        >
            <Button variant="ghost" as-child>
                <Link :href="EventoController.show(luta.evento.uuid)">
                    Voltar ao card
                </Link>
            </Button>
            <Button variant="outline" as-child>
                <Link :href="LutaController.edit(luta.uuid)"
                    >Dados da luta</Link
                >
            </Button>
        </PageHeader>

        <section class="max-w-3xl space-y-4 rounded-lg border p-4">
            <div class="flex flex-wrap items-center gap-3">
                <Badge>{{ luta.status }}</Badge>
                <span v-if="momento" class="text-sm font-medium">
                    {{ momento }}
                </span>
                <span
                    v-if="luta.em_intervalo"
                    class="text-sm text-muted-foreground"
                >
                    Troca de palpite liberada para Membros.
                </span>
            </div>

            <p v-if="luta.metodo_vitoria" class="text-sm">
                Resultado:
                <span class="font-medium">
                    {{ luta.vencedor ?? 'sem vencedor' }}
                </span>
                · {{ luta.metodo_vitoria }}
                <template v-if="luta.round_fim">
                    · round {{ luta.round_fim }}
                </template>
                <template v-if="luta.tempo_fim">
                    · {{ luta.tempo_fim }}</template
                >
            </p>

            <div class="flex flex-wrap gap-2">
                <Form
                    v-if="acoes.iniciar"
                    v-bind="AndamentoLutaController.iniciar.form(luta.uuid)"
                    v-slot="{ processing }"
                >
                    <Button type="submit" :disabled="processing">
                        Iniciar luta
                    </Button>
                </Form>
                <Form
                    v-if="acoes.encerrarRound"
                    v-bind="
                        AndamentoLutaController.encerrarRound.form(luta.uuid)
                    "
                    v-slot="{ processing }"
                >
                    <Button type="submit" :disabled="processing">
                        Encerrar round {{ luta.round_atual }}
                    </Button>
                </Form>
                <Form
                    v-if="acoes.iniciarProximoRound"
                    v-bind="
                        AndamentoLutaController.iniciarProximoRound.form(
                            luta.uuid,
                        )
                    "
                    v-slot="{ processing }"
                >
                    <Button type="submit" :disabled="processing">
                        Iniciar round {{ (luta.round_atual ?? 0) + 1 }}
                    </Button>
                </Form>
                <Form
                    v-if="acoes.cancelar"
                    v-bind="AndamentoLutaController.cancelar.form(luta.uuid)"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        variant="outline"
                        :disabled="processing"
                    >
                        Cancelar luta
                    </Button>
                </Form>
            </div>
        </section>

        <section v-if="acoes.encerrar" class="max-w-3xl space-y-4">
            <h2 class="text-base font-medium">Encerrar com o resultado</h2>

            <Form
                v-bind="AndamentoLutaController.encerrar.form(luta.uuid)"
                class="grid gap-6 sm:grid-cols-2"
                v-slot="{ errors, processing }"
            >
                <FormField
                    label="Método"
                    for="metodo_vitoria"
                    :error="errors.metodo_vitoria"
                    required
                >
                    <SelectInput
                        id="metodo_vitoria"
                        v-model="metodoEscolhido"
                        name="metodo_vitoria"
                        :options="metodos"
                        placeholder="Selecione…"
                        required
                    />
                </FormField>

                <FormField
                    v-if="exigeVencedor"
                    label="Vencedor"
                    for="vencedor_id"
                    :error="errors.vencedor_id"
                    required
                >
                    <SelectInput
                        id="vencedor_id"
                        name="vencedor_id"
                        :options="[
                            {
                                value: luta.participante_a.id,
                                label: luta.participante_a.nome ?? 'A',
                            },
                            {
                                value: luta.participante_b.id,
                                label: luta.participante_b.nome ?? 'B',
                            },
                        ]"
                        placeholder="Selecione…"
                        required
                    />
                </FormField>

                <FormField
                    v-if="luta.usa_rounds"
                    label="Round do fim"
                    for="round_fim"
                    :error="errors.round_fim"
                >
                    <SelectInput
                        id="round_fim"
                        name="round_fim"
                        :options="rounds"
                        :default-value="luta.round_atual"
                        placeholder="Não informado"
                    />
                </FormField>

                <FormField
                    label="Tempo do fim"
                    for="tempo_fim"
                    :error="errors.tempo_fim"
                    hint="Formato mm:ss."
                >
                    <Input
                        id="tempo_fim"
                        name="tempo_fim"
                        placeholder="04:35"
                        maxlength="5"
                    />
                </FormField>

                <div class="sm:col-span-2">
                    <Button type="submit" :disabled="processing">
                        Encerrar luta
                    </Button>
                </div>
            </Form>
        </section>

        <section v-if="luta.usa_rounds" class="max-w-3xl space-y-4">
            <header class="space-y-0.5">
                <h2 class="text-base font-medium">Placar oficial</h2>
                <p class="text-sm text-muted-foreground">
                    Um lançamento por juiz lateral por round. Lançar de novo
                    corrige o valor anterior.
                </p>
            </header>

            <DataTable
                :columns="[
                    { key: 'round', label: 'Round' },
                    { key: 'juiz', label: 'Juiz' },
                    {
                        key: 'pontos_atleta_a',
                        label: luta.participante_a.nome ?? 'A',
                    },
                    {
                        key: 'pontos_atleta_b',
                        label: luta.participante_b.nome ?? 'B',
                    },
                ]"
                :rows="placares"
                empty-message="Nenhum placar lançado."
            >
                <template #actions="{ row }">
                    <DeleteButton
                        :href="PlacarController.destroy.url(row.uuid)"
                        :nome="`placar do round ${row.round}`"
                    />
                </template>
            </DataTable>

            <p
                v-if="juizesLaterais.length === 0"
                class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
            >
                Escale ao menos um juiz lateral nos dados da luta para lançar o
                placar.
            </p>
            <Form
                v-else
                v-bind="PlacarController.store.form(luta.uuid)"
                :options="{ preserveScroll: true }"
                class="grid items-start gap-3 rounded-lg bg-muted/40 p-3 sm:grid-cols-[1fr_1fr_6rem_6rem_auto]"
                v-slot="{ errors, processing }"
            >
                <FormField label="Juiz" for="juiz_id" :error="errors.juiz_id">
                    <SelectInput
                        id="juiz_id"
                        name="juiz_id"
                        :options="juizesLaterais"
                        placeholder="Selecione…"
                        required
                    />
                </FormField>
                <FormField label="Round" for="round" :error="errors.round">
                    <SelectInput
                        id="round"
                        name="round"
                        :options="rounds"
                        :default-value="luta.round_atual"
                        placeholder="Selecione…"
                        required
                    />
                </FormField>
                <FormField
                    label="Pontos A"
                    for="pontos_atleta_a"
                    :error="errors.pontos_atleta_a"
                >
                    <Input
                        id="pontos_atleta_a"
                        type="number"
                        min="0"
                        max="10"
                        name="pontos_atleta_a"
                        :default-value="10"
                        required
                    />
                </FormField>
                <FormField
                    label="Pontos B"
                    for="pontos_atleta_b"
                    :error="errors.pontos_atleta_b"
                >
                    <Input
                        id="pontos_atleta_b"
                        type="number"
                        min="0"
                        max="10"
                        name="pontos_atleta_b"
                        :default-value="9"
                        required
                    />
                </FormField>
                <Button type="submit" class="self-end" :disabled="processing">
                    Lançar
                </Button>
            </Form>
        </section>
    </div>
</template>
