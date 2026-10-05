<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import PatrocinadorController from '@/actions/App/Http/Controllers/Admin/PatrocinadorController';
import FileInput from '@/components/admin/FileInput.vue';
import FormActions from '@/components/admin/FormActions.vue';
import FormField from '@/components/admin/FormField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import SelectInput from '@/components/admin/SelectInput.vue';
import { Input } from '@/components/ui/input';
import { adminBreadcrumbs } from '@/lib/adminNav';
import type { Opcao } from '@/types';

type Patrocinador = {
    uuid: string;
    nome: string;
    link_site: string | null;
    email_contato: string | null;
    status: string;
    data_inicio_contrato: string | null;
    data_fim_contrato: string | null;
    logo: string | null;
};

defineProps<{
    patrocinador: Patrocinador | null;
    status: Opcao[];
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs(
            'Patrocinadores',
            PatrocinadorController.index(),
        ),
    },
});
</script>

<template>
    <Head :title="patrocinador ? 'Editar patrocinador' : 'Novo patrocinador'" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            :title="patrocinador ? 'Editar patrocinador' : 'Novo patrocinador'"
        />

        <Form
            v-bind="
                patrocinador
                    ? PatrocinadorController.update.form(patrocinador.uuid)
                    : PatrocinadorController.store.form()
            "
            class="grid max-w-3xl gap-6 sm:grid-cols-2"
            v-slot="{ errors, processing }"
        >
            <FormField label="Nome" for="nome" :error="errors.nome" required>
                <Input
                    id="nome"
                    name="nome"
                    :default-value="patrocinador?.nome ?? ''"
                    required
                    maxlength="150"
                />
            </FormField>

            <FormField
                label="Situação"
                for="status"
                :error="errors.status"
                required
            >
                <SelectInput
                    id="status"
                    name="status"
                    :options="status"
                    :default-value="patrocinador?.status ?? 'ativo'"
                    required
                />
            </FormField>

            <FormField label="Site" for="link_site" :error="errors.link_site">
                <Input
                    id="link_site"
                    type="url"
                    name="link_site"
                    :default-value="patrocinador?.link_site ?? ''"
                    maxlength="255"
                    placeholder="https://"
                />
            </FormField>

            <FormField
                label="E-mail de contato"
                for="email_contato"
                :error="errors.email_contato"
            >
                <Input
                    id="email_contato"
                    type="email"
                    name="email_contato"
                    :default-value="patrocinador?.email_contato ?? ''"
                    maxlength="150"
                />
            </FormField>

            <FormField
                label="Início do contrato"
                for="data_inicio_contrato"
                :error="errors.data_inicio_contrato"
            >
                <Input
                    id="data_inicio_contrato"
                    type="date"
                    name="data_inicio_contrato"
                    :default-value="patrocinador?.data_inicio_contrato ?? ''"
                />
            </FormField>

            <FormField
                label="Fim do contrato"
                for="data_fim_contrato"
                :error="errors.data_fim_contrato"
            >
                <Input
                    id="data_fim_contrato"
                    type="date"
                    name="data_fim_contrato"
                    :default-value="patrocinador?.data_fim_contrato ?? ''"
                />
            </FormField>

            <FormField
                label="Logo"
                for="logo"
                :error="errors.logo"
                hint="Imagem de até 2 MB. Enviar outra substitui a atual."
                class="sm:col-span-2"
            >
                <img
                    v-if="patrocinador?.logo"
                    :src="patrocinador.logo"
                    alt="Logo atual"
                    class="h-16 w-auto rounded border object-contain p-1"
                />
                <FileInput id="logo" name="logo" />
            </FormField>

            <FormActions
                class="sm:col-span-2"
                :processing="processing"
                :cancel-href="PatrocinadorController.index.url()"
            />
        </Form>
    </div>
</template>
