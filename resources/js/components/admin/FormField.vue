<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';

defineProps<{
    label: string;
    /** id do campo, para associar o rótulo. */
    for: string;
    error?: string;
    hint?: string;
    required?: boolean;
}>();
</script>

<template>
    <div class="grid gap-2">
        <Label :for="$props.for">
            {{ label }}
            <span v-if="required" class="text-destructive" aria-hidden="true"
                >*</span
            >
        </Label>
        <slot />
        <p v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</p>
        <InputError :message="error" />
    </div>
</template>
