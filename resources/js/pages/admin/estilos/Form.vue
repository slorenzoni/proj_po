<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import EstiloDeLutaController from '@/actions/App/Http/Controllers/Admin/EstiloDeLutaController';
import FormActions from '@/components/admin/FormActions.vue';
import FormField from '@/components/admin/FormField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { Input } from '@/components/ui/input';
import { adminBreadcrumbs } from '@/lib/adminNav';

defineProps<{
    estilo: { uuid: string; nome: string } | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs(
            'Estilos de luta',
            EstiloDeLutaController.index(),
        ),
    },
});
</script>

<template>
    <Head :title="estilo ? 'Editar estilo de luta' : 'Novo estilo de luta'" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            :title="estilo ? 'Editar estilo de luta' : 'Novo estilo de luta'"
        />

        <Form
            v-bind="
                estilo
                    ? EstiloDeLutaController.update.form(estilo.uuid)
                    : EstiloDeLutaController.store.form()
            "
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <FormField label="Nome" for="nome" :error="errors.nome" required>
                <Input
                    id="nome"
                    name="nome"
                    :default-value="estilo?.nome ?? ''"
                    required
                    maxlength="60"
                    placeholder="Ex.: Jiu-Jitsu"
                />
            </FormField>

            <FormActions
                :processing="processing"
                :cancel-href="EstiloDeLutaController.index.url()"
            />
        </Form>
    </div>
</template>
