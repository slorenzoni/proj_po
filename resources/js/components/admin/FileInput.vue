<script setup lang="ts">
/**
 * Campo de arquivo nativo. Não usa o Input compartilhado porque ele aplica v-model,
 * e o Vue limparia o arquivo escolhido a cada nova renderização.
 */
withDefaults(
    defineProps<{
        id: string;
        name: string;
        accept?: string;
        required?: boolean;
    }>(),
    {
        accept: 'image/*',
    },
);
</script>

<template>
    <input
        :id="id"
        type="file"
        :name="name"
        :accept="accept"
        :required="required"
        class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none file:mr-3 file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
    />
</template>
