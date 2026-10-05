<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import BannerController from '@/actions/App/Http/Controllers/Admin/BannerController';
import FileInput from '@/components/admin/FileInput.vue';
import FormActions from '@/components/admin/FormActions.vue';
import FormField from '@/components/admin/FormField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import SelectInput from '@/components/admin/SelectInput.vue';
import { Input } from '@/components/ui/input';
import { adminBreadcrumbs } from '@/lib/adminNav';
import type { Opcao } from '@/types';

type Banner = {
    uuid: string;
    patrocinador_id: number;
    link_destino: string | null;
    valor_contrato: string | null;
    ordem_exibicao: number;
    impressoes: number;
    cliques: number;
    posicao: string;
    status: string;
    modelo_cobranca: string;
    data_inicio: string;
    data_fim: string | null;
    imagem: string | null;
};

defineProps<{
    banner: Banner | null;
    patrocinadores: Opcao[];
    posicoes: Opcao[];
    status: Opcao[];
    modelosCobranca: Opcao[];
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs('Banners', BannerController.index()),
    },
});
</script>

<template>
    <Head :title="banner ? 'Editar banner' : 'Novo banner'" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            :title="banner ? 'Editar banner' : 'Novo banner'"
            :description="
                banner
                    ? `${banner.impressoes} impressões e ${banner.cliques} cliques até agora.`
                    : undefined
            "
        />

        <Form
            v-bind="
                banner
                    ? BannerController.update.form(banner.uuid)
                    : BannerController.store.form()
            "
            class="grid max-w-3xl gap-6 sm:grid-cols-2"
            v-slot="{ errors, processing }"
        >
            <FormField
                label="Patrocinador"
                for="patrocinador_id"
                :error="errors.patrocinador_id"
                required
            >
                <SelectInput
                    id="patrocinador_id"
                    name="patrocinador_id"
                    :options="patrocinadores"
                    :default-value="banner?.patrocinador_id"
                    placeholder="Selecione…"
                    required
                />
            </FormField>

            <FormField
                label="Posição"
                for="posicao"
                :error="errors.posicao"
                required
            >
                <SelectInput
                    id="posicao"
                    name="posicao"
                    :options="posicoes"
                    :default-value="banner?.posicao"
                    placeholder="Selecione…"
                    required
                />
            </FormField>

            <FormField
                label="Imagem"
                for="imagem"
                :error="errors.imagem"
                :required="!banner"
                hint="Até 4 MB. Na edição, enviar outra substitui a atual."
                class="sm:col-span-2"
            >
                <img
                    v-if="banner?.imagem"
                    :src="banner.imagem"
                    alt="Banner atual"
                    class="max-h-32 w-auto rounded border object-contain"
                />
                <FileInput id="imagem" name="imagem" :required="!banner" />
            </FormField>

            <FormField
                label="Link de destino"
                for="link_destino"
                :error="errors.link_destino"
                class="sm:col-span-2"
            >
                <Input
                    id="link_destino"
                    type="url"
                    name="link_destino"
                    :default-value="banner?.link_destino ?? ''"
                    maxlength="255"
                    placeholder="https://"
                />
            </FormField>

            <FormField
                label="Início da exibição"
                for="data_inicio"
                :error="errors.data_inicio"
                required
            >
                <Input
                    id="data_inicio"
                    type="date"
                    name="data_inicio"
                    :default-value="banner?.data_inicio ?? ''"
                    required
                />
            </FormField>

            <FormField
                label="Fim da exibição"
                for="data_fim"
                :error="errors.data_fim"
                hint="Em branco: sem data de término."
            >
                <Input
                    id="data_fim"
                    type="date"
                    name="data_fim"
                    :default-value="banner?.data_fim ?? ''"
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
                    :default-value="banner?.status ?? 'ativo'"
                    required
                />
            </FormField>

            <FormField
                label="Ordem de exibição"
                for="ordem_exibicao"
                :error="errors.ordem_exibicao"
                required
                hint="Números menores aparecem primeiro."
            >
                <Input
                    id="ordem_exibicao"
                    type="number"
                    min="0"
                    name="ordem_exibicao"
                    :default-value="banner?.ordem_exibicao ?? 0"
                    required
                />
            </FormField>

            <FormField
                label="Modelo de cobrança"
                for="modelo_cobranca"
                :error="errors.modelo_cobranca"
                required
            >
                <SelectInput
                    id="modelo_cobranca"
                    name="modelo_cobranca"
                    :options="modelosCobranca"
                    :default-value="banner?.modelo_cobranca"
                    placeholder="Selecione…"
                    required
                />
            </FormField>

            <FormField
                label="Valor do contrato (R$)"
                for="valor_contrato"
                :error="errors.valor_contrato"
            >
                <Input
                    id="valor_contrato"
                    type="number"
                    step="0.01"
                    min="0"
                    name="valor_contrato"
                    :default-value="banner?.valor_contrato ?? ''"
                />
            </FormField>

            <FormActions
                class="sm:col-span-2"
                :processing="processing"
                :cancel-href="BannerController.index.url()"
            />
        </Form>
    </div>
</template>
