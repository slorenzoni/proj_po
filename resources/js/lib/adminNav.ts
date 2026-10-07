import {
    Award,
    BadgeCheck,
    CalendarDays,
    Gavel,
    Handshake,
    Image,
    Layers,
    Settings2,
    Shapes,
    Swords,
    UserCog,
    Users,
} from '@lucide/vue';
import BannerController from '@/actions/App/Http/Controllers/Admin/BannerController';
import CategoriaController from '@/actions/App/Http/Controllers/Admin/CategoriaController';
import ConfiguracaoPontuacaoController from '@/actions/App/Http/Controllers/Admin/ConfiguracaoPontuacaoController';
import EstiloDeLutaController from '@/actions/App/Http/Controllers/Admin/EstiloDeLutaController';
import EventoController from '@/actions/App/Http/Controllers/Admin/EventoController';
import JuizController from '@/actions/App/Http/Controllers/Admin/JuizController';
import OrganizacaoController from '@/actions/App/Http/Controllers/Admin/OrganizacaoController';
import PatrocinadorController from '@/actions/App/Http/Controllers/Admin/PatrocinadorController';
import TreinadorController from '@/actions/App/Http/Controllers/Admin/TreinadorController';
import UsuarioController from '@/actions/App/Http/Controllers/Admin/UsuarioController';
import VerificacaoController from '@/actions/App/Http/Controllers/Admin/VerificacaoController';
import AtletaController from '@/actions/App/Http/Controllers/Admin/AtletaController';
import { dashboard as adminDashboard } from '@/routes/admin';
import type { AreaAdmin, BreadcrumbItem, NavItem } from '@/types';

export type AdminNavItem = NavItem & {
    area: AreaAdmin;
    descricao: string;
};

/**
 * Telas do painel, na ordem do menu. "area" é a mesma usada nos gates do servidor:
 * serve só para esconder o que o nível do administrador não acessa.
 */
export const adminNavItems: AdminNavItem[] = [
    {
        title: 'Eventos e lutas',
        descricao:
            'Eventos, card de lutas, andamento ao vivo e placar oficial.',
        href: EventoController.index(),
        icon: CalendarDays,
        area: 'cadastros',
    },
    {
        title: 'Atletas',
        descricao: 'Cadastro, cartel, fotos e estilos de luta.',
        href: AtletaController.index(),
        icon: Swords,
        area: 'cadastros',
    },
    {
        title: 'Organizações',
        descricao: 'Promotoras dos eventos.',
        href: OrganizacaoController.index(),
        icon: Award,
        area: 'cadastros',
    },
    {
        title: 'Categorias',
        descricao: 'Modalidades e categorias de peso.',
        href: CategoriaController.index(),
        icon: Layers,
        area: 'cadastros',
    },
    {
        title: 'Estilos de luta',
        descricao: 'Estilos praticados pelos atletas.',
        href: EstiloDeLutaController.index(),
        icon: Shapes,
        area: 'cadastros',
    },
    {
        title: 'Juízes',
        descricao: 'Árbitros e juízes laterais.',
        href: JuizController.index(),
        icon: Gavel,
        area: 'cadastros',
    },
    {
        title: 'Treinadores',
        descricao: 'Treinadores dos atletas.',
        href: TreinadorController.index(),
        icon: Users,
        area: 'cadastros',
    },
    {
        title: 'Patrocinadores',
        descricao: 'Empresas patrocinadoras e contratos.',
        href: PatrocinadorController.index(),
        icon: Handshake,
        area: 'cadastros',
    },
    {
        title: 'Banners',
        descricao: 'Peças publicitárias por posição do site.',
        href: BannerController.index(),
        icon: Image,
        area: 'cadastros',
    },
    {
        title: 'Verificações',
        descricao: 'Pedidos de selo de verificado.',
        href: VerificacaoController.index(),
        icon: BadgeCheck,
        area: 'verificacoes',
    },
    {
        title: 'Usuários',
        descricao: 'Contas, papéis e administradores.',
        href: UsuarioController.index(),
        icon: UserCog,
        area: 'usuarios',
    },
    {
        title: 'Pontuação',
        descricao: 'Pontos dos palpites e pesos por momento da troca.',
        href: ConfiguracaoPontuacaoController.edit(),
        icon: Settings2,
        area: 'configuracoes',
    },
];

export function adminNavFor(areas: AreaAdmin[]): AdminNavItem[] {
    return adminNavItems.filter((item) => areas.includes(item.area));
}

/** Trilha padrão das telas do painel: "Administração › {tela}". */
export function adminBreadcrumbs(
    title: string,
    href: BreadcrumbItem['href'],
): BreadcrumbItem[] {
    return [
        { title: 'Administração', href: adminDashboard() },
        { title, href },
    ];
}
