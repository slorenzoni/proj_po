<script setup lang="ts" generic="T extends { uuid: string }">
import type { ColunaTabela } from '@/types';

withDefaults(
    defineProps<{
        columns: ColunaTabela[];
        rows: T[];
        emptyMessage?: string;
    }>(),
    {
        emptyMessage: 'Nenhum registro encontrado.',
    },
);

// Cada coluna pode ser personalizada com o slot "cell-{key}"; "actions" é a última coluna.
const slots = defineSlots<{
    [name: string]: (props: { row: T }) => unknown;
}>();

function valor(row: T, key: string): unknown {
    const bruto = (row as Record<string, unknown>)[key];

    return bruto === null || bruto === undefined || bruto === '' ? '—' : bruto;
}
</script>

<template>
    <div class="overflow-x-auto rounded-lg border">
        <table class="w-full text-sm">
            <thead class="bg-muted/50 text-left text-muted-foreground">
                <tr>
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        scope="col"
                        class="px-4 py-2.5 font-medium"
                        :class="column.class"
                    >
                        {{ column.label }}
                    </th>
                    <th v-if="slots.actions" scope="col" class="w-px px-4">
                        <span class="sr-only">Ações</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows" :key="row.uuid" class="border-t">
                    <td
                        v-for="column in columns"
                        :key="column.key"
                        class="px-4 py-2.5"
                        :class="column.class"
                    >
                        <slot :name="`cell-${column.key}`" :row="row">
                            {{ valor(row, column.key) }}
                        </slot>
                    </td>
                    <td v-if="slots.actions" class="px-4 py-2.5">
                        <div class="flex items-center justify-end gap-2">
                            <slot name="actions" :row="row" />
                        </div>
                    </td>
                </tr>
                <tr v-if="rows.length === 0" class="border-t">
                    <td
                        :colspan="columns.length + (slots.actions ? 1 : 0)"
                        class="px-4 py-8 text-center text-muted-foreground"
                    >
                        {{ emptyMessage }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
