<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { dashboard } from '@/routes/admin';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Administração',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Administração" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <h1 class="text-xl font-semibold">Painel administrativo</h1>
        <p class="text-sm text-muted-foreground">
            Os cadastros (organizações, eventos, lutas, atletas e juízes) serão
            adicionados nas próximas fases.
        </p>
    </div>
</template>
