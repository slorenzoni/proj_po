<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = withDefaults(
    defineProps<{
        /** URL da listagem; a busca vai em "?busca=". */
        action: string;
        busca?: string;
        placeholder?: string;
        /** Outros filtros da listagem, mantidos ao buscar. */
        extra?: Record<string, string | number | null | undefined>;
    }>(),
    {
        busca: '',
        placeholder: 'Buscar…',
        extra: () => ({}),
    },
);

const termo = ref(props.busca);

function buscar(): void {
    router.get(
        props.action,
        { ...props.extra, busca: termo.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
</script>

<template>
    <form
        class="flex w-full max-w-sm items-center gap-2"
        @submit.prevent="buscar"
    >
        <Input
            v-model="termo"
            type="search"
            name="busca"
            :placeholder="placeholder"
            aria-label="Buscar"
        />
        <Button type="submit" variant="outline" size="icon" aria-label="Buscar">
            <Search />
        </Button>
    </form>
</template>
