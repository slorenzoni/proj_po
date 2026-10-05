<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import BlogController from '@/actions/App/Http/Controllers/Site/BlogController';
import EventoController from '@/actions/App/Http/Controllers/Site/EventoController';
import HomeController from '@/actions/App/Http/Controllers/Site/HomeController';
import RankingController from '@/actions/App/Http/Controllers/Site/RankingController';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard, login, register } from '@/routes';
import type { User } from '@/types';

/** Layout das páginas públicas: funciona com ou sem usuário logado. */
const page = usePage();

// Nas páginas públicas não há garantia de usuário logado.
const user = computed(() => page.props.auth.user as User | null);

const { isCurrentOrParentUrl } = useCurrentUrl();

const links = [
    { title: 'Eventos', href: EventoController.index() },
    { title: 'Ranking', href: RankingController.geral() },
    { title: 'Blog', href: BlogController.index() },
];
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b">
            <div
                class="mx-auto flex w-full max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3"
            >
                <Link
                    :href="HomeController()"
                    class="flex items-center gap-2 font-semibold"
                >
                    <span
                        class="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground"
                    >
                        <AppLogoIcon class="size-5 fill-current" />
                    </span>
                    {{ page.props.name }}
                </Link>

                <nav class="flex items-center gap-1" aria-label="Principal">
                    <Link
                        v-for="link in links"
                        :key="link.title"
                        :href="link.href"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors hover:bg-accent"
                        :class="
                            isCurrentOrParentUrl(link.href)
                                ? 'bg-accent text-accent-foreground'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ link.title }}
                    </Link>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <Button v-if="user" size="sm" as-child>
                        <Link :href="dashboard()">Meus palpites</Link>
                    </Button>
                    <template v-else>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="login()">Entrar</Link>
                        </Button>
                        <Button size="sm" as-child>
                            <Link :href="register()">Criar conta</Link>
                        </Button>
                    </template>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
            <slot />
        </main>

        <footer class="border-t">
            <p
                class="mx-auto w-full max-w-6xl px-4 py-6 text-sm text-muted-foreground"
            >
                {{ page.props.name }} · Palpites sem prêmio em dinheiro. Para
                maiores de 18 anos.
            </p>
        </footer>

        <Toaster />
    </div>
</template>
