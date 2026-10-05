<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SolicitacaoVerificacaoController from '@/actions/App/Http/Controllers/Site/SolicitacaoVerificacaoController';
import FileInput from '@/components/admin/FileInput.vue';
import FormField from '@/components/admin/FormField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import TextArea from '@/components/admin/TextArea.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatarDataHora } from '@/lib/formatos';

type Solicitacao = {
    uuid: string;
    status: { value: 'pendente' | 'aprovada' | 'rejeitada'; label: string };
    descricao: string | null;
    motivo_rejeicao: string | null;
    criado_em: string | null;
};

defineProps<{
    verificado: boolean;
    podeSolicitar: boolean;
    solicitacoes: Solicitacao[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Selo de verificado',
                href: SolicitacaoVerificacaoController.index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Selo de verificado" />

    <div class="flex h-full flex-1 flex-col gap-8 p-4">
        <PageHeader
            title="Selo de verificado"
            description="O selo mostra aos outros usuários que a sua identidade foi confirmada pela nossa equipe."
        >
            <Badge v-if="verificado">Conta verificada</Badge>
        </PageHeader>

        <section v-if="podeSolicitar" class="max-w-xl space-y-4">
            <h2 class="text-base font-medium">Solicitar o selo</h2>

            <Form
                v-bind="SolicitacaoVerificacaoController.store.form()"
                reset-on-success
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <FormField
                    label="Comprovante"
                    for="documento"
                    :error="errors.documento"
                    required
                    hint="PDF, JPG ou PNG de até 5 MB. Só a nossa equipe tem acesso ao arquivo."
                >
                    <FileInput
                        id="documento"
                        name="documento"
                        accept=".pdf,.jpg,.jpeg,.png"
                        required
                    />
                </FormField>

                <FormField
                    label="Justificativa"
                    for="descricao"
                    :error="errors.descricao"
                    hint="Opcional. Conte quem você é ou por que pede o selo."
                >
                    <TextArea
                        id="descricao"
                        name="descricao"
                        :maxlength="1000"
                    />
                </FormField>

                <Button type="submit" :disabled="processing">
                    Enviar solicitação
                </Button>
            </Form>
        </section>
        <p
            v-else-if="!verificado"
            class="max-w-xl rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
        >
            {{
                solicitacoes.some(
                    (solicitacao) => solicitacao.status.value === 'pendente',
                )
                    ? 'Sua solicitação está em análise. Você poderá enviar outra depois da resposta.'
                    : 'O selo está disponível apenas para contas de cliente.'
            }}
        </p>

        <section v-if="solicitacoes.length > 0" class="max-w-xl space-y-3">
            <h2 class="text-base font-medium">Suas solicitações</h2>
            <ul class="space-y-2">
                <li
                    v-for="solicitacao in solicitacoes"
                    :key="solicitacao.uuid"
                    class="space-y-1 rounded-lg border p-3 text-sm"
                >
                    <p
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <span class="text-muted-foreground">
                            Enviada em
                            {{ formatarDataHora(solicitacao.criado_em) }}
                        </span>
                        <Badge
                            :variant="
                                solicitacao.status.value === 'aprovada'
                                    ? 'default'
                                    : 'secondary'
                            "
                        >
                            {{ solicitacao.status.label }}
                        </Badge>
                    </p>
                    <p v-if="solicitacao.descricao">
                        {{ solicitacao.descricao }}
                    </p>
                    <p v-if="solicitacao.motivo_rejeicao">
                        <span class="font-medium">Motivo da rejeição:</span>
                        {{ solicitacao.motivo_rejeicao }}
                    </p>
                </li>
            </ul>
        </section>
    </div>
</template>
