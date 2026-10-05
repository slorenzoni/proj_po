<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import PostagemController from '@/actions/App/Http/Controllers/Admin/PostagemController';
import FileInput from '@/components/admin/FileInput.vue';
import FormActions from '@/components/admin/FormActions.vue';
import FormField from '@/components/admin/FormField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import SelectInput from '@/components/admin/SelectInput.vue';
import TextArea from '@/components/admin/TextArea.vue';
import { Input } from '@/components/ui/input';
import { adminBreadcrumbs } from '@/lib/adminNav';
import type { Opcao } from '@/types';

type Postagem = {
    uuid: string;
    titulo: string;
    slug: string;
    conteudo: string;
    meta_description: string | null;
    patrocinador_id: number | null;
    fonte_original_url: string | null;
    categoria_id: number | null;
    status: string;
    data_publicacao: string | null;
    capa: string | null;
};

defineProps<{
    postagem: Postagem | null;
    patrocinadores: Opcao[];
    categorias: Opcao[];
    status: Opcao[];
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs('Blog', PostagemController.index()),
    },
});
</script>

<template>
    <Head :title="postagem ? 'Editar postagem' : 'Nova postagem'" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader :title="postagem ? 'Editar postagem' : 'Nova postagem'" />

        <Form
            v-bind="
                postagem
                    ? PostagemController.update.form(postagem.uuid)
                    : PostagemController.store.form()
            "
            class="grid max-w-3xl gap-6 sm:grid-cols-2"
            v-slot="{ errors, processing }"
        >
            <FormField
                label="Título"
                for="titulo"
                :error="errors.titulo"
                required
                class="sm:col-span-2"
            >
                <Input
                    id="titulo"
                    name="titulo"
                    :default-value="postagem?.titulo ?? ''"
                    required
                    maxlength="200"
                />
            </FormField>

            <FormField
                label="Endereço (slug)"
                for="slug"
                :error="errors.slug"
                hint="Em branco: gerado a partir do título."
                class="sm:col-span-2"
            >
                <Input
                    id="slug"
                    name="slug"
                    :default-value="postagem?.slug ?? ''"
                    maxlength="220"
                    placeholder="minha-materia"
                />
            </FormField>

            <FormField
                label="Conteúdo"
                for="conteudo"
                :error="errors.conteudo"
                required
                hint="Aceita HTML ou Markdown."
                class="sm:col-span-2"
            >
                <TextArea
                    id="conteudo"
                    name="conteudo"
                    :default-value="postagem?.conteudo"
                    :rows="14"
                    required
                />
            </FormField>

            <FormField
                label="Resumo para busca e compartilhamento"
                for="meta_description"
                :error="errors.meta_description"
                hint="Até 160 caracteres."
                class="sm:col-span-2"
            >
                <Input
                    id="meta_description"
                    name="meta_description"
                    :default-value="postagem?.meta_description ?? ''"
                    maxlength="160"
                />
            </FormField>

            <FormField
                label="Imagem de capa"
                for="capa"
                :error="errors.capa"
                hint="Até 4 MB. Enviar outra substitui a atual."
                class="sm:col-span-2"
            >
                <img
                    v-if="postagem?.capa"
                    :src="postagem.capa"
                    alt="Capa atual"
                    class="max-h-32 w-auto rounded border object-contain"
                />
                <FileInput id="capa" name="capa" />
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
                    :default-value="postagem?.status ?? 'rascunho'"
                    required
                />
            </FormField>

            <FormField
                label="Data de publicação"
                for="data_publicacao"
                :error="errors.data_publicacao"
                hint="Em branco ao publicar: usa a data de hoje."
            >
                <Input
                    id="data_publicacao"
                    type="date"
                    name="data_publicacao"
                    :default-value="postagem?.data_publicacao ?? ''"
                />
            </FormField>

            <FormField
                label="Categoria"
                for="categoria_id"
                :error="errors.categoria_id"
            >
                <SelectInput
                    id="categoria_id"
                    name="categoria_id"
                    :options="categorias"
                    :default-value="postagem?.categoria_id"
                    placeholder="Nenhuma"
                />
            </FormField>

            <FormField
                label="Patrocinador"
                for="patrocinador_id"
                :error="errors.patrocinador_id"
                hint="Com patrocinador, a postagem é marcada como patrocinada."
            >
                <SelectInput
                    id="patrocinador_id"
                    name="patrocinador_id"
                    :options="patrocinadores"
                    :default-value="postagem?.patrocinador_id"
                    placeholder="Nenhum"
                />
            </FormField>

            <FormField
                label="Fonte original"
                for="fonte_original_url"
                :error="errors.fonte_original_url"
                hint="Para repostagens."
                class="sm:col-span-2"
            >
                <Input
                    id="fonte_original_url"
                    type="url"
                    name="fonte_original_url"
                    :default-value="postagem?.fonte_original_url ?? ''"
                    maxlength="255"
                    placeholder="https://"
                />
            </FormField>

            <FormActions
                class="sm:col-span-2"
                :processing="processing"
                :cancel-href="PostagemController.index.url()"
            />
        </Form>
    </div>
</template>
