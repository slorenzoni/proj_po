<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';

/**
 * Captcha do Cloudflare (Turnstile) no login (PG1), no cadastro e no "esqueci a senha" (PG3) — SEGURANCA.md.
 *
 * O widget cria sozinho, dentro desta caixa, o campo escondido `cf-turnstile-response` com o
 * token; como a caixa fica dentro do <Form> da tela, o token vai junto no envio. Quem confere é
 * o servidor (App\Http\Middleware\VerificarCaptcha). Modo "gerenciado": quase sempre passa sem
 * clique.
 *
 * O token só vale UMA vez: depois de cada envio a tela chama reiniciar() para gerar outro.
 * Sem chave pública (captchaSiteKey null — testes, ou captcha desligado) não mostra nada.
 */
defineProps<{
    erro?: string;
}>();

type Turnstile = {
    render: (elemento: HTMLElement, opcoes: Record<string, unknown>) => string;
    reset: (id: string) => void;
    remove: (id: string) => void;
};

declare global {
    interface Window {
        turnstile?: Turnstile;
        carregandoTurnstile?: Promise<Turnstile> | null;
    }
}

const SCRIPT = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

const page = usePage();
const siteKey = computed(() => page.props.captchaSiteKey as string | null | undefined);
const caixa = ref<HTMLElement | null>(null);
const falhouAoCarregar = ref(false);
let widgetId: string | null = null;

// Um único <script> por página, mesmo que o componente seja montado de novo.
function carregarScript(): Promise<Turnstile> {
    if (window.turnstile) {
        return Promise.resolve(window.turnstile);
    }

    window.carregandoTurnstile ??= new Promise<Turnstile>((resolver, rejeitar) => {
        const script = document.createElement('script');
        script.src = SCRIPT;
        script.async = true;
        script.defer = true;
        script.onload = () => (window.turnstile ? resolver(window.turnstile) : rejeitar(new Error('Turnstile não carregou')));
        script.onerror = () => {
            window.carregandoTurnstile = null;
            rejeitar(new Error('Turnstile não carregou'));
        };
        document.head.appendChild(script);
    });

    return window.carregandoTurnstile;
}

function reiniciar(): void {
    if (widgetId !== null && window.turnstile) {
        window.turnstile.reset(widgetId);
    }
}

defineExpose({ reiniciar });

onMounted(async () => {
    if (!siteKey.value || !caixa.value) {
        return;
    }

    try {
        const turnstile = await carregarScript();

        widgetId = turnstile.render(caixa.value, {
            sitekey: siteKey.value,
            language: 'pt-br',
            theme: 'auto',
        });
    } catch {
        // Cloudflare fora do ar ou bloqueado no navegador: o formulário segue sem token e o
        // servidor decide (login aceita; cadastro e recuperação de senha recusam).
        falhouAoCarregar.value = true;
    }
});

onBeforeUnmount(() => {
    if (widgetId !== null && window.turnstile) {
        window.turnstile.remove(widgetId);
    }
});
</script>

<template>
    <div v-if="siteKey" class="grid gap-2">
        <div ref="caixa" />

        <p v-if="falhouAoCarregar" class="text-sm text-muted-foreground">
            Não foi possível carregar a verificação de segurança. Recarregue a página; se continuar,
            tente mais tarde.
        </p>

        <InputError :message="erro" />
    </div>
</template>
