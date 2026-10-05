<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import AtletaController from '@/actions/App/Http/Controllers/Admin/AtletaController';
import AtletaEstiloController from '@/actions/App/Http/Controllers/Admin/AtletaEstiloController';
import AtletaFotoController from '@/actions/App/Http/Controllers/Admin/AtletaFotoController';
import DeleteButton from '@/components/admin/DeleteButton.vue';
import FileInput from '@/components/admin/FileInput.vue';
import FormActions from '@/components/admin/FormActions.vue';
import FormField from '@/components/admin/FormField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import SelectInput from '@/components/admin/SelectInput.vue';
import TextArea from '@/components/admin/TextArea.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { adminBreadcrumbs } from '@/lib/adminNav';
import type { Opcao } from '@/types';

type Foto = {
    uuid: string;
    url: string | null;
    ordem: number;
    principal: boolean;
};

type Vinculo = {
    uuid: string;
    estilo: string | null;
    treinador: string | null;
};

type Atleta = {
    uuid: string;
    nome: string;
    apelido: string | null;
    tipo: string;
    equipe: string | null;
    pais: string | null;
    cidade_natal: string | null;
    data_nascimento: string | null;
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
    email_usuario: string | null;
    fotos: Foto[];
    estilos: Vinculo[];
};

defineProps<{
    atleta: Atleta | null;
    limiteFotos?: number;
    estilos?: Opcao[];
    treinadores?: Opcao[];
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs('Atletas', AtletaController.index()),
    },
});

// Campos do cartel: o total de vitórias/derrotas é calculado pelo servidor a partir deles.
const camposCartel = [
    { name: 'vitorias_ko', label: 'Vitórias por KO/TKO' },
    { name: 'vitorias_submissao', label: 'Vitórias por submissão' },
    { name: 'vitorias_decisao', label: 'Vitórias por decisão' },
    { name: 'derrotas_ko', label: 'Derrotas por KO/TKO' },
    { name: 'derrotas_submissao', label: 'Derrotas por submissão' },
    { name: 'derrotas_decisao', label: 'Derrotas por decisão' },
    { name: 'empates', label: 'Empates' },
] as const;
</script>

<template>
    <Head :title="atleta ? 'Editar atleta' : 'Novo atleta'" />

    <div class="flex h-full flex-1 flex-col gap-8 p-4">
        <PageHeader
            :title="atleta ? 'Editar atleta' : 'Novo atleta'"
            :description="
                atleta
                    ? `Cartel atual: ${atleta.vitorias} vitórias, ${atleta.derrotas} derrotas e ${atleta.empates} empates${atleta.invicto ? ' (invicto)' : ''}.`
                    : 'Depois de salvar, você poderá adicionar fotos e estilos de luta.'
            "
        />

        <Form
            v-bind="
                atleta
                    ? AtletaController.update.form(atleta.uuid)
                    : AtletaController.store.form()
            "
            class="max-w-3xl space-y-8"
            v-slot="{ errors, processing }"
        >
            <fieldset class="grid gap-6 sm:grid-cols-2">
                <legend class="mb-4 text-base font-medium">
                    Identificação
                </legend>

                <FormField
                    label="Nome"
                    for="nome"
                    :error="errors.nome"
                    required
                >
                    <Input
                        id="nome"
                        name="nome"
                        :default-value="atleta?.nome ?? ''"
                        required
                        maxlength="150"
                    />
                </FormField>
                <FormField
                    label="Apelido"
                    for="apelido"
                    :error="errors.apelido"
                >
                    <Input
                        id="apelido"
                        name="apelido"
                        :default-value="atleta?.apelido ?? ''"
                        maxlength="100"
                    />
                </FormField>
                <FormField
                    label="Tipo"
                    for="tipo"
                    :error="errors.tipo"
                    required
                >
                    <Input
                        id="tipo"
                        name="tipo"
                        :default-value="atleta?.tipo ?? 'lutador'"
                        required
                        maxlength="30"
                    />
                </FormField>
                <FormField label="Equipe" for="equipe" :error="errors.equipe">
                    <Input
                        id="equipe"
                        name="equipe"
                        :default-value="atleta?.equipe ?? ''"
                        maxlength="150"
                    />
                </FormField>
                <FormField label="País" for="pais" :error="errors.pais">
                    <Input
                        id="pais"
                        name="pais"
                        :default-value="atleta?.pais ?? ''"
                        maxlength="60"
                    />
                </FormField>
                <FormField
                    label="Cidade natal"
                    for="cidade_natal"
                    :error="errors.cidade_natal"
                >
                    <Input
                        id="cidade_natal"
                        name="cidade_natal"
                        :default-value="atleta?.cidade_natal ?? ''"
                        maxlength="100"
                    />
                </FormField>
                <FormField
                    label="Data de nascimento"
                    for="data_nascimento"
                    :error="errors.data_nascimento"
                >
                    <Input
                        id="data_nascimento"
                        type="date"
                        name="data_nascimento"
                        :default-value="atleta?.data_nascimento ?? ''"
                    />
                </FormField>
                <FormField
                    label="E-mail da conta no sistema"
                    for="email_usuario"
                    :error="errors.email_usuario"
                    hint="Opcional. Só para atletas que têm conta para entrar no sistema."
                >
                    <Input
                        id="email_usuario"
                        type="email"
                        name="email_usuario"
                        :default-value="atleta?.email_usuario ?? ''"
                    />
                </FormField>
            </fieldset>

            <fieldset class="grid gap-6 sm:grid-cols-4">
                <legend class="mb-4 text-base font-medium">Físico</legend>

                <FormField
                    label="Altura (cm)"
                    for="altura_cm"
                    :error="errors.altura_cm"
                >
                    <Input
                        id="altura_cm"
                        type="number"
                        name="altura_cm"
                        :default-value="atleta?.altura_cm ?? ''"
                    />
                </FormField>
                <FormField
                    label="Peso (kg)"
                    for="peso_kg"
                    :error="errors.peso_kg"
                >
                    <Input
                        id="peso_kg"
                        type="number"
                        step="0.01"
                        name="peso_kg"
                        :default-value="atleta?.peso_kg ?? ''"
                    />
                </FormField>
                <FormField
                    label="Alcance (cm)"
                    for="alcance_cm"
                    :error="errors.alcance_cm"
                >
                    <Input
                        id="alcance_cm"
                        type="number"
                        name="alcance_cm"
                        :default-value="atleta?.alcance_cm ?? ''"
                    />
                </FormField>
                <FormField
                    label="Base (stance)"
                    for="stance"
                    :error="errors.stance"
                >
                    <Input
                        id="stance"
                        name="stance"
                        :default-value="atleta?.stance ?? ''"
                        maxlength="20"
                        placeholder="Ex.: Ortodoxo"
                    />
                </FormField>
            </fieldset>

            <fieldset class="grid gap-6 sm:grid-cols-3">
                <legend class="mb-1 text-base font-medium">Cartel</legend>
                <p class="-mt-2 text-sm text-muted-foreground sm:col-span-3">
                    Informe o detalhamento. Os totais de vitórias e derrotas e o
                    selo de invicto são calculados automaticamente.
                </p>

                <FormField
                    v-for="campo in camposCartel"
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
                        :default-value="atleta?.[campo.name] ?? 0"
                        required
                    />
                </FormField>
                <FormField
                    label="Ranking"
                    for="ranking"
                    :error="errors.ranking"
                >
                    <Input
                        id="ranking"
                        type="number"
                        min="0"
                        name="ranking"
                        :default-value="atleta?.ranking ?? ''"
                    />
                </FormField>
            </fieldset>

            <FormField
                label="Biografia"
                for="biografia"
                :error="errors.biografia"
            >
                <TextArea
                    id="biografia"
                    name="biografia"
                    :default-value="atleta?.biografia"
                    :rows="6"
                />
            </FormField>

            <FormActions
                :processing="processing"
                :cancel-href="AtletaController.index.url()"
            />
        </Form>

        <section v-if="atleta" class="max-w-3xl space-y-4">
            <header class="space-y-0.5">
                <h2 class="text-base font-medium">Fotos</h2>
                <p class="text-sm text-muted-foreground">
                    Até {{ limiteFotos }} fotos. A principal é a exibida nos
                    cards de luta.
                </p>
            </header>

            <ul class="grid gap-3 sm:grid-cols-3">
                <li
                    v-for="foto in atleta.fotos"
                    :key="foto.uuid"
                    class="space-y-2 rounded-lg border p-2"
                >
                    <img
                        v-if="foto.url"
                        :src="foto.url"
                        :alt="`Foto ${foto.ordem} de ${atleta.nome}`"
                        class="aspect-square w-full rounded object-cover"
                    />
                    <div class="flex items-center justify-between gap-2">
                        <Badge v-if="foto.principal">Principal</Badge>
                        <Form
                            v-else
                            v-bind="AtletaFotoController.update.form(foto.uuid)"
                            :options="{ preserveScroll: true }"
                            v-slot="{ processing }"
                        >
                            <Button
                                type="submit"
                                variant="outline"
                                size="sm"
                                :disabled="processing"
                            >
                                Tornar principal
                            </Button>
                        </Form>
                        <DeleteButton
                            :href="AtletaFotoController.destroy.url(foto.uuid)"
                            :nome="`foto ${foto.ordem}`"
                        />
                    </div>
                </li>
            </ul>

            <Form
                v-if="atleta.fotos.length < (limiteFotos ?? 0)"
                v-bind="AtletaFotoController.store.form(atleta.uuid)"
                :options="{ preserveScroll: true }"
                reset-on-success
                class="flex flex-wrap items-start gap-2 rounded-lg bg-muted/40 p-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid min-w-64 flex-1 gap-1">
                    <FileInput id="foto" name="foto" required />
                    <InputError :message="errors.foto" />
                </div>
                <Button type="submit" :disabled="processing"
                    >Enviar foto</Button
                >
            </Form>
        </section>

        <section v-if="atleta" class="max-w-3xl space-y-4">
            <header class="space-y-0.5">
                <h2 class="text-base font-medium">Estilos de luta</h2>
                <p class="text-sm text-muted-foreground">
                    Cada estilo pode ter um treinador.
                </p>
            </header>

            <ul class="space-y-2">
                <li
                    v-for="vinculo in atleta.estilos"
                    :key="vinculo.uuid"
                    class="flex items-center justify-between gap-2 rounded-lg border px-3 py-2 text-sm"
                >
                    <span>
                        <span class="font-medium">{{ vinculo.estilo }}</span>
                        <span class="text-muted-foreground">
                            ·
                            {{ vinculo.treinador ?? 'sem treinador' }}
                        </span>
                    </span>
                    <DeleteButton
                        :href="AtletaEstiloController.destroy.url(vinculo.uuid)"
                        :nome="vinculo.estilo ?? 'estilo'"
                        label="Remover"
                    />
                </li>
                <li
                    v-if="atleta.estilos.length === 0"
                    class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                >
                    Nenhum estilo vinculado.
                </li>
            </ul>

            <Form
                v-bind="AtletaEstiloController.store.form(atleta.uuid)"
                :options="{ preserveScroll: true }"
                reset-on-success
                class="grid items-start gap-2 rounded-lg bg-muted/40 p-3 sm:grid-cols-[1fr_1fr_auto]"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-1">
                    <SelectInput
                        name="estilo_id"
                        :options="estilos ?? []"
                        placeholder="Estilo de luta…"
                        required
                        aria-label="Estilo de luta"
                    />
                    <InputError :message="errors.estilo_id" />
                </div>
                <div class="grid gap-1">
                    <SelectInput
                        name="treinador_id"
                        :options="treinadores ?? []"
                        placeholder="Sem treinador"
                        aria-label="Treinador"
                    />
                    <InputError :message="errors.treinador_id" />
                </div>
                <Button type="submit" :disabled="processing">Adicionar</Button>
            </Form>
        </section>
    </div>
</template>
