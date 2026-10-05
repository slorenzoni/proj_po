<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import DeleteButton from '@/components/admin/DeleteButton.vue';
import { Button } from '@/components/ui/button';

/** Botões "editar" e "excluir" de uma linha de listagem. */
defineProps<{
    nome: string;
    editHref: string;
    deleteHref: string;
}>();
</script>

<template>
    <div class="flex items-center justify-end gap-1">
        <slot />
        <Button variant="ghost" size="icon" as-child>
            <Link :href="editHref" :aria-label="`Editar ${nome}`">
                <Pencil />
            </Link>
        </Button>
        <DeleteButton :href="deleteHref" :nome="nome" />
    </div>
</template>
