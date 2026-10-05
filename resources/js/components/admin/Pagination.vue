<script setup lang="ts" generic="T">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

defineProps<{
    page: Paginated<T>;
}>();
</script>

<template>
    <nav
        v-if="page.total > 0"
        class="flex flex-wrap items-center justify-between gap-2 text-sm text-muted-foreground"
        aria-label="Paginação"
    >
        <p>Exibindo {{ page.from }}–{{ page.to }} de {{ page.total }}</p>
        <div v-if="page.last_page > 1" class="flex items-center gap-2">
            <Button
                variant="outline"
                size="sm"
                :disabled="!page.prev_page_url"
                :as-child="!!page.prev_page_url"
            >
                <Link
                    v-if="page.prev_page_url"
                    :href="page.prev_page_url"
                    preserve-scroll
                >
                    Anterior
                </Link>
                <template v-else>Anterior</template>
            </Button>
            <span>Página {{ page.current_page }} de {{ page.last_page }}</span>
            <Button
                variant="outline"
                size="sm"
                :disabled="!page.next_page_url"
                :as-child="!!page.next_page_url"
            >
                <Link
                    v-if="page.next_page_url"
                    :href="page.next_page_url"
                    preserve-scroll
                >
                    Próxima
                </Link>
                <template v-else>Próxima</template>
            </Button>
        </div>
    </nav>
</template>
