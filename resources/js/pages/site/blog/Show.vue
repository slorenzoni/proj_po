<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BlogController from '@/actions/App/Http/Controllers/Site/BlogController';
import BannerSlot from '@/components/site/BannerSlot.vue';
import { Badge } from '@/components/ui/badge';
import { formatarData } from '@/lib/formatos';
import type { BannerSite } from '@/types';

type Postagem = {
    titulo: string;
    resumo: string | null;
    /** HTML gerado no servidor a partir do Markdown, com o HTML digitado já neutralizado. */
    conteudo_html: string;
    capa: string | null;
    autor: string | null;
    categoria: string | null;
    data_publicacao: string | null;
    patrocinador: { nome: string; link: string | null } | null;
    fonte_original_url: string | null;
};

defineProps<{
    postagem: Postagem;
    banners: BannerSite[];
}>();
</script>

<template>
    <Head :title="postagem.titulo">
        <meta
            v-if="postagem.resumo"
            head-key="description"
            name="description"
            :content="postagem.resumo"
        />
    </Head>

    <article class="mx-auto max-w-3xl space-y-6">
        <Link
            :href="BlogController.index()"
            class="text-sm text-muted-foreground underline-offset-4 hover:underline"
        >
            ← Blog
        </Link>

        <header class="space-y-3">
            <h1 class="text-3xl font-semibold tracking-tight">
                {{ postagem.titulo }}
            </h1>
            <p
                class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
            >
                <span v-if="postagem.autor">Por {{ postagem.autor }}</span>
                <span>· {{ formatarData(postagem.data_publicacao) }}</span>
                <Badge v-if="postagem.categoria" variant="secondary">
                    {{ postagem.categoria }}
                </Badge>
            </p>
            <p
                v-if="postagem.patrocinador"
                class="rounded-lg border bg-muted/40 p-3 text-sm"
            >
                Conteúdo patrocinado por
                <a
                    v-if="postagem.patrocinador.link"
                    :href="postagem.patrocinador.link"
                    target="_blank"
                    rel="sponsored noopener"
                    class="font-medium underline"
                >
                    {{ postagem.patrocinador.nome }}
                </a>
                <span v-else class="font-medium">
                    {{ postagem.patrocinador.nome }}
                </span>
                .
            </p>
        </header>

        <img
            v-if="postagem.capa"
            :src="postagem.capa"
            alt=""
            class="w-full rounded-lg border object-cover"
        />

        <!-- eslint-disable-next-line vue/no-v-html -- HTML produzido no servidor com o conteúdo do usuário escapado -->
        <div class="conteudo-da-postagem" v-html="postagem.conteudo_html" />

        <p v-if="postagem.fonte_original_url" class="text-sm">
            Fonte original:
            <a
                :href="postagem.fonte_original_url"
                target="_blank"
                rel="noopener"
                class="underline"
            >
                {{ postagem.fonte_original_url }}
            </a>
        </p>

        <BannerSlot :banners="banners" />
    </article>
</template>

<style scoped>
/* O HTML vem pronto do servidor, então os estilos alcançam os elementos internos com :deep(). */
.conteudo-da-postagem {
    line-height: 1.7;
}

.conteudo-da-postagem :deep(p),
.conteudo-da-postagem :deep(ul),
.conteudo-da-postagem :deep(ol),
.conteudo-da-postagem :deep(blockquote),
.conteudo-da-postagem :deep(pre) {
    margin-block: 1rem;
}

.conteudo-da-postagem :deep(h2),
.conteudo-da-postagem :deep(h3) {
    margin-top: 2rem;
    margin-bottom: 0.5rem;
    font-weight: 600;
    font-size: 1.25rem;
}

.conteudo-da-postagem :deep(ul) {
    padding-left: 1.5rem;
    list-style: disc;
}

.conteudo-da-postagem :deep(ol) {
    padding-left: 1.5rem;
    list-style: decimal;
}

.conteudo-da-postagem :deep(a) {
    text-decoration: underline;
}

.conteudo-da-postagem :deep(blockquote) {
    padding-left: 1rem;
    border-left: 3px solid var(--border);
    color: var(--muted-foreground);
}

.conteudo-da-postagem :deep(img) {
    max-width: 100%;
    border-radius: 0.5rem;
}
</style>
