/** Atleta como aparece nos cards do site (App\Http\Controllers\Site\Concerns\ApresentaLutas). */
export type AtletaResumo = {
    id: number;
    uuid: string;
    nome: string;
    apelido: string | null;
    pais: string | null;
    foto: string | null;
    cartel: string;
};

export type SituacaoLuta = {
    value: 'agendada' | 'em_andamento' | 'encerrada' | 'cancelada';
    label: string;
};

/** Luta como aparece nos cards do site. */
export type LutaResumo = {
    uuid: string;
    ordem_na_card: number;
    tipo_card: string | null;
    categoria_peso: string | null;
    numero_rounds: number | null;
    status: SituacaoLuta;
    participante_a: AtletaResumo;
    participante_b: AtletaResumo;
    vencedor_id: number | null;
    metodo_vitoria: string | null;
    round_fim: number | null;
};

/** Banner entregue por App\Services\ExibicaoDeBanners. */
export type BannerSite = {
    uuid: string;
    imagem: string | null;
    patrocinador: string | null;
    tem_link: boolean;
};
