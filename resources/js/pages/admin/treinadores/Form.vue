<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TreinadorController from '@/actions/App/Http/Controllers/Admin/TreinadorController';
import FormActions from '@/components/admin/FormActions.vue';
import FormField from '@/components/admin/FormField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { Input } from '@/components/ui/input';
import { adminBreadcrumbs } from '@/lib/adminNav';

type Treinador = {
    uuid: string;
    nome: string;
    pais: string | null;
    email_usuario: string | null;
};

defineProps<{
    treinador: Treinador | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs(
            'Treinadores',
            TreinadorController.index(),
        ),
    },
});
</script>

<template>
    <Head :title="treinador ? 'Editar treinador' : 'Novo treinador'" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            :title="treinador ? 'Editar treinador' : 'Novo treinador'"
        />

        <Form
            v-bind="
                treinador
                    ? TreinadorController.update.form(treinador.uuid)
                    : TreinadorController.store.form()
            "
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <FormField label="Nome" for="nome" :error="errors.nome" required>
                <Input
                    id="nome"
                    name="nome"
                    :default-value="treinador?.nome ?? ''"
                    required
                    maxlength="150"
                />
            </FormField>

            <FormField label="País" for="pais" :error="errors.pais">
                <Input
                    id="pais"
                    name="pais"
                    :default-value="treinador?.pais ?? ''"
                    maxlength="60"
                />
            </FormField>

            <FormField
                label="E-mail da conta no sistema"
                for="email_usuario"
                :error="errors.email_usuario"
                hint="Opcional. Preencha só se o treinador tiver conta para entrar no sistema."
            >
                <Input
                    id="email_usuario"
                    type="email"
                    name="email_usuario"
                    :default-value="treinador?.email_usuario ?? ''"
                />
            </FormField>

            <FormActions
                :processing="processing"
                :cancel-href="TreinadorController.index.url()"
            />
        </Form>
    </div>
</template>
