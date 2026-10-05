<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import ConfiguracaoPontuacaoController from '@/actions/App/Http/Controllers/Admin/ConfiguracaoPontuacaoController';
import DeleteButton from '@/components/admin/DeleteButton.vue';
import FormField from '@/components/admin/FormField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { adminBreadcrumbs } from '@/lib/adminNav';
import type { Opcao } from '@/types';

type Pontuacao = {
    pontos_vencedor: number;
    pontos_vencedor_metodo: number;
    pontos_vencedor_round: number;
    pontos_perfeito: number;
    prazo_placar_fans_minutos: number;
};

type Grade = {
    numero_rounds: number;
    propria: boolean;
    pesos: { round_da_troca: number; peso: string | null }[];
};

const props = defineProps<{
    /** Categoria em edição, ou nulo para o padrão geral. */
    escopo: { uuid: string; nome: string } | null;
    categorias: Opcao[];
    pontuacao: Pontuacao | null;
    pontuacaoPropria: boolean;
    grades: Grade[];
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs(
            'Pontuação',
            ConfiguracaoPontuacaoController.edit(),
        ),
    },
});

const camposDePontos = [
    { name: 'pontos_vencedor', label: 'Só o vencedor' },
    { name: 'pontos_vencedor_metodo', label: 'Vencedor + método' },
    { name: 'pontos_vencedor_round', label: 'Vencedor + round' },
    { name: 'pontos_perfeito', label: 'Vencedor + método + round' },
] as const;

function rotuloDoMomento(roundDaTroca: number): string {
    return roundDaTroca === 0
        ? 'Pré-luta'
        : `Intervalo após o R${roundDaTroca}`;
}

const temConfiguracaoPropria =
    props.pontuacaoPropria || props.grades.some((grade) => grade.propria);
</script>

<template>
    <Head title="Pontuação" />

    <div class="flex h-full flex-1 flex-col gap-8 p-4">
        <PageHeader
            title="Pontuação dos palpites"
            description="Os valores do padrão geral valem para todas as modalidades. Uma categoria pode ter valores próprios, que substituem o padrão."
        />

        <nav class="flex flex-wrap gap-2" aria-label="Escopo da configuração">
            <Button
                :variant="escopo ? 'outline' : 'default'"
                size="sm"
                as-child
            >
                <Link :href="ConfiguracaoPontuacaoController.edit()">
                    Padrão geral
                </Link>
            </Button>
            <Button
                v-for="categoria in categorias"
                :key="categoria.value"
                :variant="
                    escopo?.uuid === categoria.value ? 'default' : 'outline'
                "
                size="sm"
                as-child
            >
                <Link
                    :href="
                        ConfiguracaoPontuacaoController.edit({
                            query: { categoria: String(categoria.value) },
                        })
                    "
                >
                    {{ categoria.label }}
                </Link>
            </Button>
        </nav>

        <div
            v-if="escopo"
            class="flex max-w-3xl flex-wrap items-center justify-between gap-3 rounded-lg border p-3 text-sm"
        >
            <p>
                <span class="font-medium">{{ escopo.nome }}</span>
                {{
                    temConfiguracaoPropria
                        ? 'tem configuração própria.'
                        : 'usa o padrão geral. Salvar aqui cria uma configuração própria.'
                }}
            </p>
            <DeleteButton
                v-if="temConfiguracaoPropria"
                :href="
                    ConfiguracaoPontuacaoController.voltarAoPadrao.url(
                        escopo.uuid,
                    )
                "
                :nome="`a configuração própria de ${escopo.nome}`"
                label="Voltar ao padrão geral"
                :icon-only="false"
            />
        </div>

        <section class="max-w-3xl space-y-4">
            <header class="flex flex-wrap items-center gap-2">
                <h2 class="text-base font-medium">Pontos por acerto</h2>
                <Badge v-if="escopo && !pontuacaoPropria" variant="outline">
                    Valores do padrão geral
                </Badge>
            </header>

            <!-- A chave recria o formulário ao trocar de escopo, recarregando os valores. -->
            <Form
                :key="escopo?.uuid ?? 'geral'"
                v-bind="ConfiguracaoPontuacaoController.updatePontuacao.form()"
                :options="{ preserveScroll: true }"
                class="grid gap-6 sm:grid-cols-2"
                v-slot="{ errors, processing }"
            >
                <input
                    type="hidden"
                    name="categoria"
                    :value="escopo?.uuid ?? ''"
                />

                <FormField
                    v-for="campo in camposDePontos"
                    :key="campo.name"
                    :label="campo.label"
                    :for="campo.name"
                    :error="errors[campo.name]"
                    required
                >
                    <Input
                        :id="campo.name"
                        type="number"
                        min="0"
                        :name="campo.name"
                        :default-value="pontuacao?.[campo.name] ?? ''"
                        required
                    />
                </FormField>

                <FormField
                    label="Prazo do placar dos fãs (minutos)"
                    for="prazo_placar_fans_minutos"
                    :error="errors.prazo_placar_fans_minutos"
                    required
                    hint="Tempo após o fim do round em que o usuário ainda pode pontuá-lo."
                >
                    <Input
                        id="prazo_placar_fans_minutos"
                        type="number"
                        min="1"
                        name="prazo_placar_fans_minutos"
                        :default-value="
                            pontuacao?.prazo_placar_fans_minutos ?? ''
                        "
                        required
                    />
                </FormField>

                <div class="sm:col-span-2">
                    <InputError :message="errors.categoria" />
                    <Button type="submit" :disabled="processing">
                        Salvar pontuação
                    </Button>
                </div>
            </Form>
        </section>

        <section class="max-w-3xl space-y-4">
            <header class="space-y-0.5">
                <h2 class="text-base font-medium">Peso por momento da troca</h2>
                <p class="text-sm text-muted-foreground">
                    Percentual aplicado sobre os pontos, conforme o momento da
                    última troca de palpite.
                </p>
            </header>

            <Form
                v-for="grade in grades"
                :key="`${escopo?.uuid ?? 'geral'}-${grade.numero_rounds}`"
                v-bind="ConfiguracaoPontuacaoController.updatePesos.form()"
                :options="{ preserveScroll: true }"
                class="space-y-4 rounded-lg border p-4"
                v-slot="{ errors, processing }"
            >
                <input
                    type="hidden"
                    name="categoria"
                    :value="escopo?.uuid ?? ''"
                />
                <input
                    type="hidden"
                    name="numero_rounds"
                    :value="grade.numero_rounds"
                />

                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="font-medium">
                        Lutas de {{ grade.numero_rounds }} rounds
                    </h3>
                    <Badge v-if="escopo && !grade.propria" variant="outline">
                        Valores do padrão geral
                    </Badge>
                </div>

                <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    <FormField
                        v-for="item in grade.pesos"
                        :key="item.round_da_troca"
                        :label="rotuloDoMomento(item.round_da_troca)"
                        :for="`peso-${grade.numero_rounds}-${item.round_da_troca}`"
                        :error="errors[`pesos.${item.round_da_troca}`]"
                    >
                        <Input
                            :id="`peso-${grade.numero_rounds}-${item.round_da_troca}`"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            :name="`pesos[${item.round_da_troca}]`"
                            :default-value="item.peso ?? ''"
                            required
                        />
                    </FormField>
                </div>

                <InputError :message="errors.numero_rounds ?? errors.pesos" />
                <Button type="submit" variant="outline" :disabled="processing">
                    Salvar pesos de {{ grade.numero_rounds }} rounds
                </Button>
            </Form>
        </section>
    </div>
</template>
