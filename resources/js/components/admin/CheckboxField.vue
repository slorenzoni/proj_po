<script setup lang="ts">
/**
 * Caixa de seleção nativa para o <Form> do Inertia. O campo oculto garante que "0"
 * seja enviado quando desmarcada (o navegador omite caixas desmarcadas).
 */
defineProps<{
    id: string;
    name: string;
    label: string;
    defaultChecked?: boolean;
    hint?: string;
}>();
</script>

<template>
    <div class="grid gap-1">
        <label :for="id" class="flex items-center gap-2 text-sm font-medium">
            <input type="hidden" :name="name" value="0" />
            <input
                :id="id"
                type="checkbox"
                :name="name"
                value="1"
                :checked="defaultChecked"
                class="size-4 rounded border-input accent-primary"
            />
            {{ label }}
        </label>
        <p v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</p>
    </div>
</template>
