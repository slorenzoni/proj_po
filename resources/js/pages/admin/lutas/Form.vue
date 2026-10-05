<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Radio } from '@lucide/vue';
import { computed, ref } from 'vue';
import AndamentoLutaController from '@/actions/App/Http/Controllers/Admin/AndamentoLutaController';
import EventoController from '@/actions/App/Http/Controllers/Admin/EventoController';
import LutaController from '@/actions/App/Http/Controllers/Admin/LutaController';
import LutaJuizController from '@/actions/App/Http/Controllers/Admin/LutaJuizController';
import DeleteButton from '@/components/admin/DeleteButton.vue';
import FormActions from '@/components/admin/FormActions.vue';
import FormField from '@/components/admin/FormField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import SelectInput from '@/components/admin/SelectInput.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { adminBreadcrumbs } from '@/lib/adminNav';
import type { Opcao } from '@/types';

type Luta = {
    uuid: string;
    categoria_id: number;
    categoria_peso_id: number;
    participante_a_id: number;
    participante_b_id: number;
    ordem_na_card: number;
    numero_rounds: number | null;
    chance_do_a: string | null;
    chance_do_b: string | null;
    tipo_card: string | null;
    editavel: boolean;
    status: string;
    juizes: { uuid: string; nome: string | null; funcao: string }[];
};

const props = defineProps<{
    evento: { uuid: string; nome: string };
    luta: Luta | null;
    proximaOrdem: number | null;
    categorias: (Opcao & { usa_rounds: boolean })[];
    categoriasPeso: (Opcao & { categoria_id: number })[];
    atletas: Opcao[];
    tiposCard: Opcao[];
    juizes?: Opcao[];
    funcoesJuiz?: Opcao[];
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs(
            'Eventos e lutas',
            EventoController.index(),
        ),
    },
});

// A modalidade escolhida define as categorias de peso disponíveis e se a luta tem rounds.
const categoriaId = ref<string | number | null>(
    props.luta?.categoria_id ?? null,
);

const categoria = computed(() =>
    props.categorias.find(
        (opcao) => String(opcao.value) === String(categoriaId.value),
    ),
);

const pesosDaCategoria = computed(() =>
    props.categoriasPeso.filter(
        (peso) => String(peso.categoria_id) === String(categoriaId.value),
    ),
);

const usaRounds = computed(() => categoria.value?.usa_rounds ?? true);

const bloqueada = computed(() => props.luta !== null && !props.luta.editavel);
</script>

<template>
    <Head :title="luta ? 'Editar luta' : 'Nova luta'" />

    <div class="flex h-full flex-1 flex-col gap-8 p-4">
        <PageHeader
            :title="luta ? 'Editar luta' : 'Nova luta'"
            :description="`Evento: ${evento.nome}`"
        >
            <template v-if="luta">
                <Badge variant="secondary">{{ luta.status }}</Badge>
                <Button variant="outline" as-child>
                    <Link :href="AndamentoLutaController.show(luta.uuid)">
                        <Radio /> Andamento
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <p
            v-if="bloqueada"
            class="max-w-3xl rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-200/20 dark:bg-amber-500/10 dark:text-amber-100"
        >
            Esta luta já saiu da situação “agendada” e não pode mais ser
            alterada por aqui. Use a tela de andamento.
        </p>

        <Form
            v-bind="
                luta
                    ? LutaController.update.form(luta.uuid)
                    : LutaController.store.form(evento.uuid)
            "
            class="grid max-w-3xl gap-6 sm:grid-cols-2"
            v-slot="{ errors, processing }"
        >
            <FormField
                label="Modalidade"
                for="categoria_id"
                :error="errors.categoria_id"
                required
            >
                <SelectInput
                    id="categoria_id"
                    v-model="categoriaId"
                    name="categoria_id"
                    :options="categorias"
                    placeholder="Selecione…"
                    required
                />
            </FormField>

            <FormField
                label="Categoria de peso"
                for="categoria_peso_id"
                :error="errors.categoria_peso_id"
                required
                :hint="
                    categoriaId && pesosDaCategoria.length === 0
                        ? 'Esta modalidade ainda não tem categorias de peso cadastradas.'
                        : undefined
                "
            >
                <!-- A chave recria o campo ao trocar de modalidade, descartando a seleção anterior. -->
                <SelectInput
                    id="categoria_peso_id"
                    :key="String(categoriaId)"
                    name="categoria_peso_id"
                    :options="pesosDaCategoria"
                    :default-value="
                        String(categoriaId) === String(luta?.categoria_id)
                            ? luta?.categoria_peso_id
                            : null
                    "
                    placeholder="Selecione…"
                    required
                />
            </FormField>

            <FormField
                label="Participante A"
                for="participante_a_id"
                :error="errors.participante_a_id"
                required
            >
                <SelectInput
                    id="participante_a_id"
                    name="participante_a_id"
                    :options="atletas"
                    :default-value="luta?.participante_a_id"
                    placeholder="Selecione…"
                    required
                />
            </FormField>

            <FormField
                label="Participante B"
                for="participante_b_id"
                :error="errors.participante_b_id"
                required
            >
                <SelectInput
                    id="participante_b_id"
                    name="participante_b_id"
                    :options="atletas"
                    :default-value="luta?.participante_b_id"
                    placeholder="Selecione…"
                    required
                />
            </FormField>

            <FormField
                label="Ordem no card"
                for="ordem_na_card"
                :error="errors.ordem_na_card"
                required
                hint="1 é a luta principal; números maiores acontecem antes."
            >
                <Input
                    id="ordem_na_card"
                    type="number"
                    min="1"
                    name="ordem_na_card"
                    :default-value="luta?.ordem_na_card ?? proximaOrdem ?? 1"
                    required
                />
            </FormField>

            <FormField label="Card" for="tipo_card" :error="errors.tipo_card">
                <SelectInput
                    id="tipo_card"
                    name="tipo_card"
                    :options="tiposCard"
                    :default-value="luta?.tipo_card"
                    placeholder="Não informado"
                />
            </FormField>

            <FormField
                v-if="usaRounds"
                label="Número de rounds"
                for="numero_rounds"
                :error="errors.numero_rounds"
                required
            >
                <SelectInput
                    id="numero_rounds"
                    name="numero_rounds"
                    :options="[
                        { value: 3, label: '3 rounds' },
                        { value: 5, label: '5 rounds' },
                    ]"
                    :default-value="luta?.numero_rounds ?? 3"
                    required
                />
            </FormField>
            <p v-else class="self-end text-sm text-muted-foreground">
                Modalidade sem rounds.
            </p>

            <div class="grid grid-cols-2 gap-4 sm:col-span-2 sm:max-w-sm">
                <FormField
                    label="Chance do A (%)"
                    for="chance_do_a"
                    :error="errors.chance_do_a"
                >
                    <Input
                        id="chance_do_a"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        name="chance_do_a"
                        :default-value="luta?.chance_do_a ?? ''"
                    />
                </FormField>
                <FormField
                    label="Chance do B (%)"
                    for="chance_do_b"
                    :error="errors.chance_do_b"
                >
                    <Input
                        id="chance_do_b"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        name="chance_do_b"
                        :default-value="luta?.chance_do_b ?? ''"
                    />
                </FormField>
            </div>

            <FormActions
                class="sm:col-span-2"
                :processing="processing || bloqueada"
                :cancel-href="EventoController.show.url(evento.uuid)"
            />
        </Form>

        <section v-if="luta" class="max-w-3xl space-y-4">
            <header class="space-y-0.5">
                <h2 class="text-base font-medium">Juízes escalados</h2>
                <p class="text-sm text-muted-foreground">
                    Só os juízes laterais lançam placar.
                </p>
            </header>

            <ul class="space-y-2">
                <li
                    v-for="vinculo in luta.juizes"
                    :key="vinculo.uuid"
                    class="flex items-center justify-between gap-2 rounded-lg border px-3 py-2 text-sm"
                >
                    <span>
                        <span class="font-medium">{{ vinculo.nome }}</span>
                        <span class="text-muted-foreground">
                            · {{ vinculo.funcao }}
                        </span>
                    </span>
                    <DeleteButton
                        :href="LutaJuizController.destroy.url(vinculo.uuid)"
                        :nome="vinculo.nome ?? 'juiz'"
                        label="Remover"
                    />
                </li>
                <li
                    v-if="luta.juizes.length === 0"
                    class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                >
                    Nenhum juiz escalado.
                </li>
            </ul>

            <Form
                v-bind="LutaJuizController.store.form(luta.uuid)"
                :options="{ preserveScroll: true }"
                reset-on-success
                class="grid items-start gap-2 rounded-lg bg-muted/40 p-3 sm:grid-cols-[1fr_1fr_auto]"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-1">
                    <SelectInput
                        name="juiz_id"
                        :options="juizes ?? []"
                        placeholder="Juiz…"
                        required
                        aria-label="Juiz"
                    />
                    <InputError :message="errors.juiz_id" />
                </div>
                <div class="grid gap-1">
                    <SelectInput
                        name="funcao"
                        :options="funcoesJuiz ?? []"
                        placeholder="Função…"
                        required
                        aria-label="Função"
                    />
                    <InputError :message="errors.funcao" />
                </div>
                <Button type="submit" :disabled="processing">Escalar</Button>
            </Form>
        </section>
    </div>
</template>
