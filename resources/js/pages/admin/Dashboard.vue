<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import { adminNavFor } from '@/lib/adminNav';
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

const page = usePage();
const itens = computed(() => adminNavFor(page.props.auth.areasAdmin));
</script>

<template>
    <Head title="Administração" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Painel administrativo"
            description="Escolha o que deseja gerenciar. Você vê apenas as áreas liberadas para o seu nível de acesso."
        />

        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <li v-for="item in itens" :key="item.title">
                <Link
                    :href="item.href"
                    class="flex h-full items-start gap-3 rounded-lg border p-4 transition-colors hover:bg-accent"
                >
                    <component
                        :is="item.icon"
                        class="mt-0.5 size-5 shrink-0 text-muted-foreground"
                    />
                    <span class="space-y-0.5">
                        <span class="block font-medium">{{ item.title }}</span>
                        <span class="block text-sm text-muted-foreground">
                            {{ item.descricao }}
                        </span>
                    </span>
                </Link>
            </li>
        </ul>
    </div>
</template>
