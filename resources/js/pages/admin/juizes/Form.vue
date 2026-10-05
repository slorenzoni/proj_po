<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import JuizController from '@/actions/App/Http/Controllers/Admin/JuizController';
import FormActions from '@/components/admin/FormActions.vue';
import FormField from '@/components/admin/FormField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { Input } from '@/components/ui/input';
import { adminBreadcrumbs } from '@/lib/adminNav';

type Juiz = {
    uuid: string;
    nome: string;
    pais: string | null;
    certificado_por: string | null;
};

defineProps<{
    juiz: Juiz | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs('Juízes', JuizController.index()),
    },
});
</script>

<template>
    <Head :title="juiz ? 'Editar juiz' : 'Novo juiz'" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader :title="juiz ? 'Editar juiz' : 'Novo juiz'" />

        <Form
            v-bind="
                juiz
                    ? JuizController.update.form(juiz.uuid)
                    : JuizController.store.form()
            "
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <FormField label="Nome" for="nome" :error="errors.nome" required>
                <Input
                    id="nome"
                    name="nome"
                    :default-value="juiz?.nome ?? ''"
                    required
                    maxlength="150"
                />
            </FormField>

            <FormField label="País" for="pais" :error="errors.pais">
                <Input
                    id="pais"
                    name="pais"
                    :default-value="juiz?.pais ?? ''"
                    maxlength="60"
                />
            </FormField>

            <FormField
                label="Certificado por"
                for="certificado_por"
                :error="errors.certificado_por"
                hint="Comissão ou entidade que certifica o juiz."
            >
                <Input
                    id="certificado_por"
                    name="certificado_por"
                    :default-value="juiz?.certificado_por ?? ''"
                    maxlength="150"
                />
            </FormField>

            <FormActions
                :processing="processing"
                :cancel-href="JuizController.index.url()"
            />
        </Form>
    </div>
</template>
