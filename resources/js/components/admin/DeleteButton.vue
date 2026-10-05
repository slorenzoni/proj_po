<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const props = withDefaults(
    defineProps<{
        /** URL que recebe o DELETE. */
        href: string;
        /** Nome do registro, exibido na confirmação. */
        nome: string;
        label?: string;
        /** Exibe só o ícone (para linhas de tabela). */
        iconOnly?: boolean;
    }>(),
    {
        label: 'Excluir',
        iconOnly: true,
    },
);

const aberto = ref(false);
const processando = ref(false);

function excluir(): void {
    router.delete(props.href, {
        preserveScroll: true,
        onStart: () => (processando.value = true),
        onFinish: () => {
            processando.value = false;
            aberto.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="aberto">
        <DialogTrigger as-child>
            <Button
                v-if="iconOnly"
                variant="ghost"
                size="icon"
                :aria-label="`${label} ${nome}`"
            >
                <Trash2 class="text-destructive" />
            </Button>
            <Button v-else variant="destructive">{{ label }}</Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>{{ label }} “{{ nome }}”?</DialogTitle>
                <DialogDescription>
                    O registro deixa de aparecer no sistema. O histórico é
                    mantido para auditoria.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancelar</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="processando"
                    @click="excluir"
                >
                    {{ label }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
