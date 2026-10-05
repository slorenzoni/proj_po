<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import CategoriaController from '@/actions/App/Http/Controllers/Admin/CategoriaController';
import CategoriaPesoController from '@/actions/App/Http/Controllers/Admin/CategoriaPesoController';
import CheckboxField from '@/components/admin/CheckboxField.vue';
import DeleteButton from '@/components/admin/DeleteButton.vue';
import FormActions from '@/components/admin/FormActions.vue';
import FormField from '@/components/admin/FormField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { adminBreadcrumbs } from '@/lib/adminNav';

type Peso = {
    uuid: string;
    nome: string;
    peso_minimo_kg: string | null;
    peso_maximo_kg: string | null;
};

type Categoria = {
    uuid: string;
    nome: string;
    usa_rounds: boolean;
    pesos: Peso[];
};

defineProps<{
    categoria: Categoria | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs(
            'Categorias',
            CategoriaController.index(),
        ),
    },
});
</script>

<template>
    <Head :title="categoria ? 'Editar categoria' : 'Nova categoria'" />

    <div class="flex h-full flex-1 flex-col gap-8 p-4">
        <PageHeader
            :title="categoria ? 'Editar categoria' : 'Nova categoria'"
        />

        <Form
            v-bind="
                categoria
                    ? CategoriaController.update.form(categoria.uuid)
                    : CategoriaController.store.form()
            "
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <FormField label="Nome" for="nome" :error="errors.nome" required>
                <Input
                    id="nome"
                    name="nome"
                    :default-value="categoria?.nome ?? ''"
                    required
                    maxlength="50"
                    placeholder="Ex.: MMA"
                />
            </FormField>

            <div class="grid gap-2">
                <CheckboxField
                    id="usa_rounds"
                    name="usa_rounds"
                    label="A modalidade é disputada em rounds"
                    :default-checked="categoria?.usa_rounds ?? true"
                    hint="Desmarque para modalidades como o Judô: sem round no palpite, sem palpite ao vivo e sem placar por round."
                />
                <InputError :message="errors.usa_rounds" />
            </div>

            <FormActions
                :processing="processing"
                :cancel-href="CategoriaController.index.url()"
            />
        </Form>

        <section v-if="categoria" class="max-w-3xl space-y-4">
            <header class="space-y-0.5">
                <h2 class="text-base font-medium">Categorias de peso</h2>
                <p class="text-sm text-muted-foreground">
                    Limites em quilos. Deixe em branco quando não houver limite.
                </p>
            </header>

            <ul class="space-y-2">
                <li v-for="peso in categoria.pesos" :key="peso.uuid">
                    <Form
                        v-bind="CategoriaPesoController.update.form(peso.uuid)"
                        :options="{ preserveScroll: true }"
                        class="grid items-start gap-2 rounded-lg border p-3 sm:grid-cols-[1fr_7rem_7rem_auto]"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-1">
                            <Input
                                name="nome"
                                :default-value="peso.nome"
                                required
                                maxlength="60"
                                :aria-label="`Nome de ${peso.nome}`"
                            />
                            <InputError :message="errors.nome" />
                        </div>
                        <div class="grid gap-1">
                            <Input
                                name="peso_minimo_kg"
                                type="number"
                                step="0.01"
                                min="0"
                                :default-value="peso.peso_minimo_kg ?? ''"
                                placeholder="Mín."
                                :aria-label="`Peso mínimo de ${peso.nome}`"
                            />
                            <InputError :message="errors.peso_minimo_kg" />
                        </div>
                        <div class="grid gap-1">
                            <Input
                                name="peso_maximo_kg"
                                type="number"
                                step="0.01"
                                min="0"
                                :default-value="peso.peso_maximo_kg ?? ''"
                                placeholder="Máx."
                                :aria-label="`Peso máximo de ${peso.nome}`"
                            />
                            <InputError :message="errors.peso_maximo_kg" />
                        </div>
                        <div class="flex items-center gap-1">
                            <Button
                                type="submit"
                                variant="outline"
                                :disabled="processing"
                            >
                                Salvar
                            </Button>
                            <DeleteButton
                                :href="
                                    CategoriaPesoController.destroy.url(
                                        peso.uuid,
                                    )
                                "
                                :nome="peso.nome"
                            />
                        </div>
                    </Form>
                </li>
                <li
                    v-if="categoria.pesos.length === 0"
                    class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                >
                    Nenhuma categoria de peso cadastrada.
                </li>
            </ul>

            <Form
                v-bind="CategoriaPesoController.store.form(categoria.uuid)"
                :options="{ preserveScroll: true }"
                reset-on-success
                class="grid items-start gap-2 rounded-lg bg-muted/40 p-3 sm:grid-cols-[1fr_7rem_7rem_auto]"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-1">
                    <Input
                        name="nome"
                        required
                        maxlength="60"
                        placeholder="Nova categoria de peso (ex.: Peso Leve)"
                        aria-label="Nome da nova categoria de peso"
                    />
                    <InputError :message="errors.nome" />
                </div>
                <div class="grid gap-1">
                    <Input
                        name="peso_minimo_kg"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="Mín."
                        aria-label="Peso mínimo"
                    />
                    <InputError :message="errors.peso_minimo_kg" />
                </div>
                <div class="grid gap-1">
                    <Input
                        name="peso_maximo_kg"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="Máx."
                        aria-label="Peso máximo"
                    />
                    <InputError :message="errors.peso_maximo_kg" />
                </div>
                <Button type="submit" :disabled="processing">Adicionar</Button>
            </Form>
        </section>
    </div>
</template>
