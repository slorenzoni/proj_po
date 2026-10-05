<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import UsuarioController from '@/actions/App/Http/Controllers/Admin/UsuarioController';
import DeleteButton from '@/components/admin/DeleteButton.vue';
import FormField from '@/components/admin/FormField.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import SelectInput from '@/components/admin/SelectInput.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { adminBreadcrumbs } from '@/lib/adminNav';
import { formatarDataHora } from '@/lib/formatos';
import type { Opcao } from '@/types';

type Usuario = {
    uuid: string;
    name: string;
    email: string;
    email_verificado: boolean;
    cliente: boolean;
    nivel_acesso: string | null;
    criado_em: string | null;
    papeis: { uuid: string; nome: string }[];
};

defineProps<{
    usuario: Usuario;
    ehProprioUsuario: boolean;
    papeisDisponiveis: Opcao[];
    niveis: Opcao[];
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs('Usuários', UsuarioController.index()),
    },
});
</script>

<template>
    <Head :title="usuario.name" />

    <div class="flex h-full flex-1 flex-col gap-8 p-4">
        <PageHeader :title="usuario.name" :description="usuario.email">
            <Badge v-if="usuario.cliente" variant="outline">Cliente</Badge>
            <Badge
                :variant="usuario.email_verificado ? 'secondary' : 'outline'"
            >
                {{
                    usuario.email_verificado
                        ? 'E-mail confirmado'
                        : 'E-mail não confirmado'
                }}
            </Badge>
        </PageHeader>

        <p class="-mt-4 text-sm text-muted-foreground">
            Conta criada em {{ formatarDataHora(usuario.criado_em) }}.
        </p>

        <section class="max-w-xl space-y-4">
            <header class="space-y-0.5">
                <h2 class="text-base font-medium">Perfil de administrador</h2>
                <p class="text-sm text-muted-foreground">
                    O nível define quais áreas do painel a pessoa acessa.
                </p>
            </header>

            <p
                v-if="ehProprioUsuario"
                class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
            >
                Esta é a sua conta. Você não pode alterar o seu próprio perfil
                de administrador.
            </p>
            <template v-else>
                <Form
                    v-bind="
                        UsuarioController.definirAdministrador.form(
                            usuario.uuid,
                        )
                    "
                    :options="{ preserveScroll: true }"
                    class="flex flex-wrap items-end gap-2"
                    v-slot="{ errors, processing }"
                >
                    <FormField
                        label="Nível de acesso"
                        for="nivel_acesso"
                        :error="errors.nivel_acesso"
                        class="min-w-56 flex-1"
                    >
                        <SelectInput
                            id="nivel_acesso"
                            name="nivel_acesso"
                            :options="niveis"
                            :default-value="usuario.nivel_acesso"
                            placeholder="Selecione…"
                            required
                        />
                    </FormField>
                    <Button type="submit" :disabled="processing">
                        {{
                            usuario.nivel_acesso
                                ? 'Alterar nível'
                                : 'Tornar administrador'
                        }}
                    </Button>
                </Form>

                <DeleteButton
                    v-if="usuario.nivel_acesso"
                    :href="
                        UsuarioController.revogarAdministrador.url(usuario.uuid)
                    "
                    :nome="`o acesso de administrador de ${usuario.name}`"
                    label="Revogar"
                    :icon-only="false"
                />
            </template>
        </section>

        <section class="max-w-xl space-y-4">
            <header class="space-y-0.5">
                <h2 class="text-base font-medium">Papéis</h2>
                <p class="text-sm text-muted-foreground">
                    Permissões extras, como a de Comentarista.
                </p>
            </header>

            <ul class="space-y-2">
                <li
                    v-for="papel in usuario.papeis"
                    :key="papel.uuid"
                    class="flex items-center justify-between gap-2 rounded-lg border px-3 py-2 text-sm"
                >
                    <span class="font-medium">{{ papel.nome }}</span>
                    <DeleteButton
                        :href="
                            UsuarioController.removerPapel.url({
                                usuario: usuario.uuid,
                                papel: papel.uuid,
                            })
                        "
                        :nome="`o papel ${papel.nome}`"
                        label="Remover"
                    />
                </li>
                <li
                    v-if="usuario.papeis.length === 0"
                    class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                >
                    Nenhum papel atribuído.
                </li>
            </ul>

            <Form
                v-if="papeisDisponiveis.length > 0"
                v-bind="UsuarioController.atribuirPapel.form(usuario.uuid)"
                :options="{ preserveScroll: true }"
                reset-on-success
                class="flex flex-wrap items-start gap-2 rounded-lg bg-muted/40 p-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid min-w-56 flex-1 gap-1">
                    <SelectInput
                        name="papel_id"
                        :options="papeisDisponiveis"
                        placeholder="Papel…"
                        required
                        aria-label="Papel"
                    />
                    <InputError :message="errors.papel_id" />
                </div>
                <Button type="submit" :disabled="processing">Atribuir</Button>
            </Form>
        </section>
    </div>
</template>
