<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import CaptchaCloudflare from '@/components/CaptchaCloudflare.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

// O token do captcha só vale uma vez: depois de cada envio, gera outro.
const captcha = ref<InstanceType<typeof CaptchaCloudflare> | null>(null);

defineOptions({
    layout: {
        title: 'Esqueci minha senha',
        description:
            'Informe seu e-mail para receber um link de redefinição de senha',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Esqueci minha senha" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form
            v-bind="email.form()"
            v-slot="{ errors, processing }"
            @finish="captcha?.reiniciar()"
        >
            <div class="grid gap-2">
                <Label for="email">E-mail</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    v-focus
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="mt-4">
                <!-- Captcha do Cloudflare (SEGURANCA.md, PG3) -->
                <CaptchaCloudflare ref="captcha" :erro="errors['cf-turnstile-response']" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="w-full"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    Enviar link de redefinição
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>Ou volte para</span>
            <TextLink :href="login()">o login</TextLink>
        </div>
    </div>
</template>
