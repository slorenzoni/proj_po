<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

withDefaults(
    defineProps<{
        processing: boolean;
        /** Para onde o "Cancelar" leva (normalmente a listagem). */
        cancelHref: string;
        label?: string;
    }>(),
    {
        label: 'Salvar',
    },
);
</script>

<template>
    <div class="flex items-center gap-2">
        <Button type="submit" :disabled="processing">{{ label }}</Button>
        <Button variant="ghost" as-child>
            <Link :href="cancelHref">Cancelar</Link>
        </Button>
    </div>
</template>
