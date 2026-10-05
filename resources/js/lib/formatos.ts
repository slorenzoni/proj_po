const dataHora = new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
});

const data = new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeZone: 'UTC',
});

/** Data e hora (ISO 8601) no formato brasileiro; "—" quando vazio. */
export function formatarDataHora(iso: string | null | undefined): string {
    return iso ? dataHora.format(new Date(iso)) : '—';
}

/** Data sem hora ("AAAA-MM-DD") no formato brasileiro; "—" quando vazio. */
export function formatarData(iso: string | null | undefined): string {
    // Interpretada em UTC para o dia não recuar no fuso do Brasil.
    return iso ? data.format(new Date(`${iso}T00:00:00Z`)) : '—';
}
