<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EventoController from '@/actions/App/Http/Controllers/Site/EventoController';
import Pagination from '@/components/admin/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatarDataHora } from '@/lib/formatos';
import type { Paginated } from '@/types';

type Evento = {
    uuid: string;
    nome: string;
    organizacao: string | null;
    data: string;
    local: string;
    status: { value: string; label: string };
    lutas_count: number;
};

defineProps<{
    quando: 'proximos' | 'encerrados';
    eventos: Paginated<Evento>;
}>();
</script>

<template>
    <Head title="Eventos" />

    <div class="space-y-6">
        <header class="space-y-4">
            <h1 class="text-2xl font-semibold tracking-tight">Eventos</h1>
            <nav class="flex gap-2" aria-label="Filtrar eventos">
                <Button
                    :variant="quando === 'proximos' ? 'default' : 'outline'"
                    size="sm"
                    as-child
                >
                    <Link :href="EventoController.index()">Próximos</Link>
                </Button>
                <Button
                    :variant="quando === 'encerrados' ? 'default' : 'outline'"
                    size="sm"
                    as-child
                >
                    <Link
                        :href="
                            EventoController.index({
                                query: { quando: 'encerrados' },
                            })
                        "
                    >
                        Encerrados
                    </Link>
                </Button>
            </nav>
        </header>

        <p
            v-if="eventos.data.length === 0"
            class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
            {{
                quando === 'proximos'
                    ? 'Nenhum evento agendado no momento.'
                    : 'Nenhum evento encerrado ainda.'
            }}
        </p>

        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <li v-for="evento in eventos.data" :key="evento.uuid">
                <Link
                    :href="EventoController.show(evento.uuid)"
                    class="flex h-full flex-col gap-2 rounded-lg border p-4 transition-colors hover:bg-accent/50"
                >
                    <span class="flex items-start justify-between gap-2">
                        <span class="font-semibold">{{ evento.nome }}</span>
                        <Badge
                            :variant="
                                evento.status.value === 'ao_vivo'
                                    ? 'default'
                                    : 'outline'
                            "
                        >
                            {{ evento.status.label }}
                        </Badge>
                    </span>
                    <span class="text-sm text-muted-foreground">
                        {{ evento.organizacao }}
                    </span>
                    <span class="text-sm">
                        {{ formatarDataHora(evento.data) }}
                    </span>
                    <span
                        v-if="evento.local"
                        class="text-sm text-muted-foreground"
                    >
                        {{ evento.local }}
                    </span>
                    <span class="mt-auto pt-2 text-sm text-muted-foreground">
                        {{ evento.lutas_count }}
                        {{ evento.lutas_count === 1 ? 'luta' : 'lutas' }} no
                        card
                    </span>
                </Link>
            </li>
        </ul>

        <Pagination :page="eventos" />
    </div>
</template>
