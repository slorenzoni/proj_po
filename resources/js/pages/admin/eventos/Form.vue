<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import EventoController from '@/actions/App/Http/Controllers/Admin/EventoController';
import FormActions from '@/components/admin/FormActions.vue';
import FormField from '@/components/admin/FormField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import SelectInput from '@/components/admin/SelectInput.vue';
import { Input } from '@/components/ui/input';
import { adminBreadcrumbs } from '@/lib/adminNav';
import type { Opcao } from '@/types';

type Evento = {
    uuid: string;
    nome: string;
    organizacao_id: number;
    data: string;
    local: string | null;
    cidade: string | null;
    pais: string | null;
    link_canal_youtube: string | null;
    tipo_transmissao: string | null;
    status: string;
};

defineProps<{
    evento: Evento | null;
    organizacoes: Opcao[];
    tiposTransmissao: Opcao[];
    status: Opcao[];
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs(
            'Eventos e lutas',
            EventoController.index(),
        ),
    },
});
</script>

<template>
    <Head :title="evento ? 'Editar evento' : 'Novo evento'" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader :title="evento ? 'Editar evento' : 'Novo evento'" />

        <Form
            v-bind="
                evento
                    ? EventoController.update.form(evento.uuid)
                    : EventoController.store.form()
            "
            class="grid max-w-3xl gap-6 sm:grid-cols-2"
            v-slot="{ errors, processing }"
        >
            <FormField
                label="Nome"
                for="nome"
                :error="errors.nome"
                required
                class="sm:col-span-2"
            >
                <Input
                    id="nome"
                    name="nome"
                    :default-value="evento?.nome ?? ''"
                    required
                    maxlength="200"
                    placeholder="Ex.: UFC 300"
                />
            </FormField>

            <FormField
                label="Organização"
                for="organizacao_id"
                :error="errors.organizacao_id"
                required
            >
                <SelectInput
                    id="organizacao_id"
                    name="organizacao_id"
                    :options="organizacoes"
                    :default-value="evento?.organizacao_id"
                    placeholder="Selecione…"
                    required
                />
            </FormField>

            <FormField
                label="Data e hora"
                for="data"
                :error="errors.data"
                required
            >
                <Input
                    id="data"
                    type="datetime-local"
                    name="data"
                    :default-value="evento?.data ?? ''"
                    required
                />
            </FormField>

            <FormField label="Local" for="local" :error="errors.local">
                <Input
                    id="local"
                    name="local"
                    :default-value="evento?.local ?? ''"
                    maxlength="200"
                    placeholder="Arena ou ginásio"
                />
            </FormField>

            <FormField label="Cidade" for="cidade" :error="errors.cidade">
                <Input
                    id="cidade"
                    name="cidade"
                    :default-value="evento?.cidade ?? ''"
                    maxlength="100"
                />
            </FormField>

            <FormField label="País" for="pais" :error="errors.pais">
                <Input
                    id="pais"
                    name="pais"
                    :default-value="evento?.pais ?? ''"
                    maxlength="60"
                />
            </FormField>

            <FormField
                label="Link do YouTube"
                for="link_canal_youtube"
                :error="errors.link_canal_youtube"
                hint="Endereço da transmissão ou do canal."
            >
                <Input
                    id="link_canal_youtube"
                    type="url"
                    name="link_canal_youtube"
                    :default-value="evento?.link_canal_youtube ?? ''"
                    maxlength="255"
                />
            </FormField>

            <FormField
                label="Transmissão"
                for="tipo_transmissao"
                :error="errors.tipo_transmissao"
            >
                <SelectInput
                    id="tipo_transmissao"
                    name="tipo_transmissao"
                    :options="tiposTransmissao"
                    :default-value="evento?.tipo_transmissao"
                    placeholder="Não informada"
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
                    :default-value="evento?.status ?? 'agendado'"
                    required
                />
            </FormField>

            <FormActions
                class="sm:col-span-2"
                :processing="processing"
                :cancel-href="
                    evento
                        ? EventoController.show.url(evento.uuid)
                        : EventoController.index.url()
                "
            />
        </Form>
    </div>
</template>
