<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <AuthLayout :title="title" :description="description">
        <slot />
    </AuthLayout>

    <!-- Avisos (toast) também nas telas de login/cadastro: ex.: limite de reenvio da verificação (SEGURANCA.md, PG3). -->
    <Toaster />
</template>
