<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BlogController from '@/actions/App/Http/Controllers/Site/BlogController';
import Pagination from '@/components/admin/Pagination.vue';
import BannerSlot from '@/components/site/BannerSlot.vue';
import { Badge } from '@/components/ui/badge';
import { formatarData } from '@/lib/formatos';
import type { BannerSite, Paginated } from '@/types';

type Postagem = {
    uuid: string;
    slug: string;
    titulo: string;
    resumo: string | null;
    capa: string | null;
    patrocinado: boolean;
    data_publicacao: string | null;
};

defineProps<{
    postagens: Paginated<Postagem>;
    banners: BannerSite[];
}>();
</script>

<template>
    <Head title="Blog" />

    <div class="space-y-8">
        <h1 class="text-2xl font-semibold tracking-tight">Blog</h1>

        <BannerSlot :banners="banners" />

        <p
            v-if="postagens.data.length === 0"
            class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
            Nenhuma postagem publicada ainda.
        </p>

        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <li v-for="postagem in postagens.data" :key="postagem.uuid">
                <Link
                    :href="BlogController.show(postagem.slug)"
                    class="flex h-full flex-col overflow-hidden rounded-lg border transition-colors hover:bg-accent/50"
                >
                    <img
                        v-if="postagem.capa"
                        :src="postagem.capa"
                        alt=""
                        class="aspect-video w-full object-cover"
                    />
                    <span class="flex flex-1 flex-col gap-2 p-4">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold">
                                {{ postagem.titulo }}
                            </span>
                            <Badge
                                v-if="postagem.patrocinado"
                                variant="outline"
                            >
                                Patrocinado
                            </Badge>
                        </span>
                        <span
                            v-if="postagem.resumo"
                            class="text-sm text-muted-foreground"
                        >
                            {{ postagem.resumo }}
                        </span>
                        <span
                            class="mt-auto pt-2 text-xs text-muted-foreground"
                        >
                            {{ formatarData(postagem.data_publicacao) }}
                        </span>
                    </span>
                </Link>
            </li>
        </ul>

        <Pagination :page="postagens" />
    </div>
</template>
