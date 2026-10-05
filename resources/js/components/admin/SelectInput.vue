<script setup lang="ts">
import { ref, watch } from 'vue';
import type { Opcao } from '@/types';

/**
 * <select> nativo com a aparência do Input. Nativo de propósito: é enviado pelo <Form>
 * do Inertia através do atributo "name", sem estado extra.
 */
const props = defineProps<{
    options: Opcao[];
    id?: string;
    name?: string;
    /** Valor inicial quando o campo não é controlado por v-model. */
    defaultValue?: string | number | null;
    /** Texto da opção vazia; sem ele não há opção vazia. */
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
}>();

const model = defineModel<string | number | null>();

const selecionado = ref(String(model.value ?? props.defaultValue ?? ''));

watch(model, (novo) => {
    selecionado.value = String(novo ?? '');
});

function aoMudar(event: Event): void {
    selecionado.value = (event.target as HTMLSelectElement).value;
    model.value = selecionado.value === '' ? null : selecionado.value;
}
</script>

<template>
    <select
        :id="id"
        :name="name"
        :value="selecionado"
        :required="required"
        :disabled="disabled"
        class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30"
        @change="aoMudar"
    >
        <option v-if="placeholder !== undefined" value="">
            {{ placeholder }}
        </option>
        <option
            v-for="option in options"
            :key="option.value"
            :value="String(option.value)"
        >
            {{ option.label }}
        </option>
    </select>
</template>
