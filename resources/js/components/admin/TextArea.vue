<script setup lang="ts">
withDefaults(
    defineProps<{
        id?: string;
        name?: string;
        defaultValue?: string | null;
        rows?: number;
        required?: boolean;
        maxlength?: number;
        placeholder?: string;
    }>(),
    {
        rows: 4,
    },
);
</script>

<template>
    <textarea
        :id="id"
        :name="name"
        :rows="rows"
        :required="required"
        :maxlength="maxlength"
        :placeholder="placeholder"
        :value="defaultValue ?? ''"
        class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
    />
</template>
