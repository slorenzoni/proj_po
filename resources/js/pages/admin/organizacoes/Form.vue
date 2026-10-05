<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import OrganizacaoController from '@/actions/App/Http/Controllers/Admin/OrganizacaoController';
import FileInput from '@/components/admin/FileInput.vue';
import FormActions from '@/components/admin/FormActions.vue';
import FormField from '@/components/admin/FormField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { Input } from '@/components/ui/input';
import { adminBreadcrumbs } from '@/lib/adminNav';

type Organizacao = {
    uuid: string;
    nome: string;
    pais_origem: string | null;
    logo: string | null;
};

defineProps<{
    organizacao: Organizacao | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs(
            'Organizações',
            OrganizacaoController.index(),
        ),
    },
});
</script>

<template>
    <Head :title="organizacao ? 'Editar organização' : 'Nova organização'" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            :title="organizacao ? 'Editar organização' : 'Nova organização'"
        />

        <Form
            v-bind="
                organizacao
                    ? OrganizacaoController.update.form(organizacao.uuid)
                    : OrganizacaoController.store.form()
            "
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <FormField label="Nome" for="nome" :error="errors.nome" required>
                <Input
                    id="nome"
                    name="nome"
                    :default-value="organizacao?.nome ?? ''"
                    required
                    maxlength="150"
                />
            </FormField>

            <FormField
                label="País de origem"
                for="pais_origem"
                :error="errors.pais_origem"
            >
                <Input
                    id="pais_origem"
                    name="pais_origem"
                    :default-value="organizacao?.pais_origem ?? ''"
                    maxlength="60"
                />
            </FormField>

            <FormField
                label="Logo"
                for="logo"
                :error="errors.logo"
                hint="Imagem de até 2 MB. Enviar outra substitui a atual."
            >
                <img
                    v-if="organizacao?.logo"
                    :src="organizacao.logo"
                    alt="Logo atual"
                    class="h-16 w-auto rounded border object-contain p-1"
                />
                <FileInput id="logo" name="logo" />
            </FormField>

            <FormActions
                :processing="processing"
                :cancel-href="OrganizacaoController.index.url()"
            />
        </Form>
    </div>
</template>
