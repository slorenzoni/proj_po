/** Áreas do painel, espelhando App\Enums\AreaAdmin. */
export type AreaAdmin =
    | 'cadastros'
    | 'verificacoes'
    | 'usuarios'
    | 'configuracoes';

/** Item de um campo de seleção (enums e listas vindas do servidor). */
export type Opcao = {
    value: string | number;
    label: string;
};

/** Formato do paginador do Laravel entregue pelo Inertia. */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type ColunaTabela = {
    key: string;
    label: string;
    class?: string;
};
