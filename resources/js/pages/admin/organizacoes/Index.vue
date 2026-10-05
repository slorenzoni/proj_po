<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import OrganizacaoController from '@/actions/App/Http/Controllers/Admin/OrganizacaoController';
import DataTable from '@/components/admin/DataTable.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import Pagination from '@/components/admin/Pagination.vue';
import RowActions from '@/components/admin/RowActions.vue';
import SearchForm from '@/components/admin/SearchForm.vue';
import { Button } from '@/components/ui/button';
import { adminBreadcrumbs } from '@/lib/adminNav';
import type { Paginated } from '@/types';

type Organizacao = {
    uuid: string;
    nome: string;
    pais_origem: string | null;
    logo: string | null;
};

defineProps<{
    organizacoes: Paginated<Organizacao>;
    filtros: { busca: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: adminBreadcrumbs(
            'Organizações',
            OrganizacaoController.index(),
        ),
    },
});
</script>

<template>
    <Head title="Organizações" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Organizações"
            description="Promotoras dos eventos, como UFC e Bellator."
        >
            <Button as-child>
                <Link :href="OrganizacaoController.create()">
                    <Plus /> Nova organização
                </Link>
            </Button>
        </PageHeader>

        <SearchForm
            :action="OrganizacaoController.index.url()"
            :busca="filtros.busca"
            placeholder="Buscar por nome…"
        />

        <DataTable
            :columns="[
                { key: 'nome', label: 'Nome' },
                { key: 'pais_origem', label: 'País de origem' },
            ]"
            :rows="organizacoes.data"
        >
            <template #cell-nome="{ row }">
                <span class="flex items-center gap-3 font-medium">
                    <img
                        v-if="row.logo"
                        :src="row.logo"
                        alt=""
                        class="size-8 rounded object-contain"
                    />
                    {{ row.nome }}
                </span>
            </template>
            <template #actions="{ row }">
                <RowActions
                    :nome="row.nome"
                    :edit-href="OrganizacaoController.edit.url(row.uuid)"
                    :delete-href="OrganizacaoController.destroy.url(row.uuid)"
                />
            </template>
        </DataTable>

        <Pagination :page="organizacoes" />
    </div>
</template>
