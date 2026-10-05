<script setup lang="ts">
import { computed } from 'vue';
import { useInitials } from '@/composables/useInitials';

/** Foto principal do atleta, com as iniciais quando não há foto. */
const props = withDefaults(
    defineProps<{
        nome: string;
        foto: string | null;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        size: 'md',
    },
);

const { getInitials } = useInitials();

const tamanho = computed(
    () =>
        ({
            sm: 'size-10 text-sm',
            md: 'size-16 text-lg',
            lg: 'size-28 text-3xl',
        })[props.size],
);
</script>

<template>
    <img
        v-if="foto"
        :src="foto"
        :alt="`Foto de ${nome}`"
        class="shrink-0 rounded-full border object-cover"
        :class="tamanho"
    />
    <span
        v-else
        class="flex shrink-0 items-center justify-center rounded-full border bg-muted font-semibold text-muted-foreground"
        :class="tamanho"
        aria-hidden="true"
    >
        {{ getInitials(nome) }}
    </span>
</template>
