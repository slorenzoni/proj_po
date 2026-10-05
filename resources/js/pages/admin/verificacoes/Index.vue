<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { FileText } from '@lucide/vue';
import VerificacaoController from '@/actions/App/Http/Controllers/Admin/VerificacaoController';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import TextArea from '@/components/admin/TextArea.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
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
import { Label } from '@/components/ui/label';
import { adminBreadcrumbs } from '@/lib/adminNav';
import { formatarDataHora } from '@/lib/formatos';
import type { Opcao, Paginated } from '@/types';

type Solicitacao = {
    uuid: string;
    usuario: string | null;
    email: string | null;
    descricao: string | null;
    status: string;
    pendente: boolean;
    motivo_rejeicao: string | null;
    analisado_por: string | null;
    analisado_em: string | null;
    criado_em: string | null;
};

defineProps<{
    solicitacoes: Paginated<Solicitacao>;
    filtros: { status: string };
    statusDisponiveis: Opcao[];
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs(
            'Verificações',
            VerificacaoController.index(),
        ),
    },
});
</script>

<template>
    <Head title="Verificações" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Verificações"
            description="Pedidos de selo de verificado. Confira o comprovante antes de aprovar."
        />

        <nav class="flex flex-wrap gap-2" aria-label="Filtrar por situação">
            <Button
                v-for="opcao in statusDisponiveis"
                :key="opcao.value"
                :variant="
                    opcao.value === filtros.status ? 'default' : 'outline'
                "
                size="sm"
                as-child
            >
                <Link
                    :href="
                        VerificacaoController.index({
                            query: { status: String(opcao.value) },
                        })
                    "
                >
                    {{ opcao.label }}
                </Link>
            </Button>
        </nav>

        <ul class="max-w-3xl space-y-3">
            <li
                v-for="solicitacao in solicitacoes.data"
                :key="solicitacao.uuid"
                class="space-y-3 rounded-lg border p-4"
            >
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="font-medium">{{ solicitacao.usuario }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ solicitacao.email }} · enviado em
                            {{ formatarDataHora(solicitacao.criado_em) }}
                        </p>
                    </div>
                    <Badge variant="secondary">{{ solicitacao.status }}</Badge>
                </div>

                <p v-if="solicitacao.descricao" class="text-sm">
                    {{ solicitacao.descricao }}
                </p>

                <p
                    v-if="!solicitacao.pendente"
                    class="text-sm text-muted-foreground"
                >
                    Analisado por {{ solicitacao.analisado_por ?? '—' }} em
                    {{ formatarDataHora(solicitacao.analisado_em) }}.
                    <template v-if="solicitacao.motivo_rejeicao">
                        Motivo: {{ solicitacao.motivo_rejeicao }}
                    </template>
                </p>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Download comum (não é visita do Inertia): o servidor devolve o arquivo. -->
                    <Button variant="outline" size="sm" as-child>
                        <a
                            :href="
                                VerificacaoController.documento.url(
                                    solicitacao.uuid,
                                )
                            "
                        >
                            <FileText /> Baixar comprovante
                        </a>
                    </Button>

                    <template v-if="solicitacao.pendente">
                        <Form
                            v-bind="
                                VerificacaoController.aprovar.form(
                                    solicitacao.uuid,
                                )
                            "
                            :options="{ preserveScroll: true }"
                            v-slot="{ processing }"
                        >
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="processing"
                            >
                                Aprovar
                            </Button>
                        </Form>

                        <Dialog>
                            <DialogTrigger as-child>
                                <Button variant="destructive" size="sm">
                                    Rejeitar
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <Form
                                    v-bind="
                                        VerificacaoController.rejeitar.form(
                                            solicitacao.uuid,
                                        )
                                    "
                                    :options="{ preserveScroll: true }"
                                    class="space-y-6"
                                    v-slot="{ errors, processing }"
                                >
                                    <DialogHeader class="space-y-3">
                                        <DialogTitle>
                                            Rejeitar o pedido de
                                            {{ solicitacao.usuario }}?
                                        </DialogTitle>
                                        <DialogDescription>
                                            O motivo fica registrado na
                                            solicitação.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div class="grid gap-2">
                                        <Label
                                            :for="`motivo-${solicitacao.uuid}`"
                                        >
                                            Motivo da rejeição
                                        </Label>
                                        <TextArea
                                            :id="`motivo-${solicitacao.uuid}`"
                                            name="motivo_rejeicao"
                                            required
                                            :maxlength="1000"
                                        />
                                        <InputError
                                            :message="errors.motivo_rejeicao"
                                        />
                                    </div>

                                    <DialogFooter class="gap-2">
                                        <DialogClose as-child>
                                            <Button variant="secondary">
                                                Cancelar
                                            </Button>
                                        </DialogClose>
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            :disabled="processing"
                                        >
                                            Rejeitar
                                        </Button>
                                    </DialogFooter>
                                </Form>
                            </DialogContent>
                        </Dialog>
                    </template>
                </div>
            </li>
            <li
                v-if="solicitacoes.data.length === 0"
                class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
            >
                Nenhuma solicitação nesta situação.
            </li>
        </ul>

        <Pagination :page="solicitacoes" />
    </div>
</template>
