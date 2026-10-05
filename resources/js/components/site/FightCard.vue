<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import LutaController from '@/actions/App/Http/Controllers/Site/LutaController';
import AthleteAvatar from '@/components/site/AthleteAvatar.vue';
import { Badge } from '@/components/ui/badge';
import type { AtletaResumo, LutaResumo } from '@/types';

/** Card de uma luta: os dois atletas, a situação e, se houver, o resultado. */
const props = defineProps<{
    luta: LutaResumo;
}>();

function venceu(atleta: AtletaResumo): boolean {
    return props.luta.vencedor_id === atleta.id;
}
</script>

<template>
    <Link
        :href="LutaController.show(luta.uuid)"
        class="block rounded-lg border p-4 transition-colors hover:bg-accent/50"
    >
        <div
            class="mb-3 flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
        >
            <Badge
                :variant="
                    luta.status.value === 'em_andamento' ? 'default' : 'outline'
                "
            >
                {{ luta.status.label }}
            </Badge>
            <span v-if="luta.categoria_peso">{{ luta.categoria_peso }}</span>
            <span v-if="luta.numero_rounds">
                · {{ luta.numero_rounds }} rounds
            </span>
            <span v-if="luta.tipo_card">· {{ luta.tipo_card }}</span>
        </div>

        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3">
            <div
                v-for="(atleta, indice) in [
                    luta.participante_a,
                    luta.participante_b,
                ]"
                :key="atleta.uuid"
                class="flex items-center gap-3"
                :class="
                    indice === 1
                        ? 'order-3 flex-row-reverse text-right'
                        : 'order-1'
                "
            >
                <AthleteAvatar :nome="atleta.nome" :foto="atleta.foto" />
                <div class="min-w-0">
                    <p class="truncate font-semibold">{{ atleta.nome }}</p>
                    <p class="text-xs text-muted-foreground tabular-nums">
                        {{ atleta.cartel }}
                    </p>
                    <Badge v-if="venceu(atleta)" class="mt-1">Venceu</Badge>
                </div>
            </div>
            <span
                class="order-2 text-sm font-medium text-muted-foreground"
                aria-hidden="true"
            >
                ×
            </span>
        </div>

        <p
            v-if="luta.metodo_vitoria"
            class="mt-3 text-center text-sm text-muted-foreground"
        >
            {{ luta.metodo_vitoria }}
            <template v-if="luta.round_fim">
                · round {{ luta.round_fim }}
            </template>
        </p>
    </Link>
</template>
