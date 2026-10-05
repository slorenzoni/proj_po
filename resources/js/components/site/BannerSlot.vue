<script setup lang="ts">
import BannerCliqueController from '@/actions/App/Http/Controllers/Site/BannerCliqueController';
import type { BannerSite } from '@/types';

/** Espaço publicitário de uma posição do site. Não ocupa lugar quando não há banner. */
defineProps<{
    banners: BannerSite[];
}>();
</script>

<template>
    <aside v-if="banners.length > 0" class="space-y-2" aria-label="Publicidade">
        <p class="text-xs tracking-wide text-muted-foreground uppercase">
            Publicidade
        </p>
        <template v-for="banner in banners" :key="banner.uuid">
            <!-- Link comum (não é visita do Inertia): o servidor conta o clique e redireciona para fora. -->
            <a
                v-if="banner.tem_link"
                :href="BannerCliqueController.url(banner.uuid)"
                target="_blank"
                rel="sponsored noopener"
                class="block overflow-hidden rounded-lg border"
            >
                <img
                    v-if="banner.imagem"
                    :src="banner.imagem"
                    :alt="`Anúncio de ${banner.patrocinador ?? 'patrocinador'}`"
                    class="w-full object-cover"
                />
            </a>
            <img
                v-else-if="banner.imagem"
                :src="banner.imagem"
                :alt="`Anúncio de ${banner.patrocinador ?? 'patrocinador'}`"
                class="w-full rounded-lg border object-cover"
            />
        </template>
    </aside>
</template>
