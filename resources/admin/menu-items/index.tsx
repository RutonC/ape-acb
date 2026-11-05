// assets
import { Book1, Building4, DocumentText1, HambergerMenu, Briefcase, Hashtag, Note1, Category2, Gallery, Home2, People, Profile2User, ShieldSecurity, Setting2, SearchNormal1, Sms, Teacher, UserAdd } from 'iconsax-react';

// project-imports
import { InboxCounts } from 'types/inbox';

// types
import { NavItemType } from 'types/menu';

// ==============================|| MENU LATERAL ||============================== //
// Cada item pode exigir uma área do painel (`area`); o menu só mostra o que o perfil permite.
// As restantes secções entram aqui à medida que cada fase fica pronta.

const menuItems: { items: NavItemType[] } = {
  items: [
    {
      id: 'group-geral',
      title: 'Geral',
      type: 'group',
      children: [{ id: 'dashboard', title: 'Painel', type: 'item', url: '/', icon: Home2, breadcrumbs: false }]
    },
    {
      id: 'group-caixa-de-entrada',
      title: 'Caixa de entrada',
      type: 'group',
      children: [
        { id: 'candidaturas', title: 'Candidaturas', type: 'item', url: '/candidaturas', icon: UserAdd, area: 'applications', badge: 'applications' },
        { id: 'inscricoes-de-empresas', title: 'Empresas inscritas', type: 'item', url: '/inscricoes-de-empresas', icon: Building4, area: 'companies', badge: 'companies' },
        { id: 'mensagens', title: 'Mensagens', type: 'item', url: '/mensagens', icon: Sms, area: 'messages', badge: 'messages' }
      ]
    },
    {
      id: 'group-vagas',
      title: 'Vagas',
      type: 'group',
      children: [
        { id: 'vagas', title: 'Vagas', type: 'item', url: '/vagas', icon: Briefcase, area: 'jobs' },
        { id: 'categorias-de-vagas', title: 'Categorias', type: 'item', url: '/categorias-de-vagas', icon: Category2, area: 'jobs' }
      ]
    },
    {
      id: 'group-conteudo',
      title: 'Conteúdo',
      type: 'group',
      children: [
        { id: 'paginas', title: 'Páginas', type: 'item', url: '/paginas', icon: DocumentText1, area: 'pages' },
        { id: 'menus', title: 'Menus', type: 'item', url: '/menus', icon: HambergerMenu, area: 'menus' },
        { id: 'servicos', title: 'Serviços', type: 'item', url: '/servicos', icon: Teacher, area: 'services' },
        { id: 'media', title: 'Biblioteca de média', type: 'item', url: '/media', icon: Gallery, area: 'media' }
      ]
    },
    {
      id: 'group-blog',
      title: 'Notícias',
      type: 'group',
      children: [
        { id: 'artigos', title: 'Notícias', type: 'item', url: '/blog/artigos', icon: Note1, area: 'blog' },
        { id: 'blog-categorias', title: 'Categorias', type: 'item', url: '/blog/categorias', icon: Book1, area: 'blog' },
        { id: 'blog-etiquetas', title: 'Etiquetas', type: 'item', url: '/blog/etiquetas', icon: Hashtag, area: 'blog' }
      ]
    },
    {
      id: 'group-institucional',
      title: 'Institucional',
      type: 'group',
      children: [
        { id: 'equipa', title: 'Equipa', type: 'item', url: '/equipa', icon: People, area: 'team' }
      ]
    },
    {
      id: 'group-definicoes',
      title: 'Definições',
      type: 'group',
      children: [
        { id: 'definicoes-site', title: 'Site e contactos', type: 'item', url: '/definicoes/site', icon: Setting2, area: 'settings' },
        { id: 'definicoes-seo', title: 'SEO e Analytics', type: 'item', url: '/definicoes/seo', icon: SearchNormal1, area: 'settings' }
      ]
    },
    {
      id: 'group-administracao',
      title: 'Administração',
      type: 'group',
      children: [
        { id: 'utilizadores', title: 'Utilizadores', type: 'item', url: '/utilizadores', icon: Profile2User, area: 'users' },
        { id: 'perfis', title: 'Perfis e permissões', type: 'item', url: '/perfis', icon: ShieldSecurity, area: 'users' }
      ]
    }
  ]
};

/** Menu visível para quem tem as áreas indicadas (grupos vazios desaparecem). */
export function filterMenu(items: NavItemType[], can: (area?: string) => boolean): NavItemType[] {
  return items
    .filter((item) => can(item.area))
    .map((item) => (item.children ? { ...item, children: filterMenu(item.children, can) } : item))
    .filter((item) => item.type !== 'group' || item.url || (item.children?.length ?? 0) > 0);
}

/** Junta os contadores (candidaturas, empresas e mensagens novas) aos itens que os pedem; zero não mostra nada. */
export function withBadges(items: NavItemType[], counts: InboxCounts): NavItemType[] {
  return items.map((item) => {
    if (item.children) return { ...item, children: withBadges(item.children, counts) };

    const count = item.badge ? counts[item.badge] : 0;
    return count > 0 ? { ...item, chip: { label: String(count), color: 'warning', variant: 'filled', size: 'small' } } : item;
  });
}

export default menuItems;
