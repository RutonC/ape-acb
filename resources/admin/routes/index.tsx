import { lazy } from 'react';
import { createBrowserRouter } from 'react-router-dom';

// project-imports
import { AreaGuard, AuthGuard, GuestGuard, TWO_FACTOR_SETUP_PATH } from './guards';
import Loadable from 'components/Loadable';
import DashboardLayout from 'layout/Dashboard';

const Login = Loadable(lazy(() => import('pages/auth/Login')));
const ForgotPassword = Loadable(lazy(() => import('pages/auth/ForgotPassword')));
const ResetPassword = Loadable(lazy(() => import('pages/auth/ResetPassword')));
const TwoFactorSetup = Loadable(lazy(() => import('pages/auth/TwoFactorSetup')));

const Dashboard = Loadable(lazy(() => import('pages/dashboard')));
const Profile = Loadable(lazy(() => import('pages/profile')));
const UsersList = Loadable(lazy(() => import('pages/users/List')));
const UserForm = Loadable(lazy(() => import('pages/users/Form')));
const RolesList = Loadable(lazy(() => import('pages/roles/List')));
const RoleForm = Loadable(lazy(() => import('pages/roles/Form')));
const MediaPage = Loadable(lazy(() => import('pages/media')));
const CatalogList = Loadable(lazy(() => import('pages/catalog/List')));
const CatalogForm = Loadable(lazy(() => import('pages/catalog/Form')));
const JobsList = Loadable(lazy(() => import('pages/jobs/List')));
const JobForm = Loadable(lazy(() => import('pages/jobs/Form')));
const JobCategories = Loadable(lazy(() => import('pages/jobs/Categories')));
const Team = Loadable(lazy(() => import('pages/team')));
const Messages = Loadable(lazy(() => import('pages/inbox/Messages')));
const MessageDetail = Loadable(lazy(() => import('pages/inbox/MessageDetail')));
const Applications = Loadable(lazy(() => import('pages/inbox/Applications')));
const ApplicationDetail = Loadable(lazy(() => import('pages/inbox/ApplicationDetail')));
const Companies = Loadable(lazy(() => import('pages/inbox/Companies')));
const CompanyDetail = Loadable(lazy(() => import('pages/inbox/CompanyDetail')));
const PostsList = Loadable(lazy(() => import('pages/blog/PostsList')));
const PostForm = Loadable(lazy(() => import('pages/blog/PostForm')));
const BlogTerms = Loadable(lazy(() => import('pages/blog/Terms')));
const PagesList = Loadable(lazy(() => import('pages/content/PagesList')));
const PageForm = Loadable(lazy(() => import('pages/content/PageForm')));
const Menus = Loadable(lazy(() => import('pages/content/Menus')));
const MenuForm = Loadable(lazy(() => import('pages/content/MenuForm')));
const GeneralSettings = Loadable(lazy(() => import('pages/settings/General')));
const SeoSettings = Loadable(lazy(() => import('pages/settings/Seo')));
const NotFound = Loadable(lazy(() => import('pages/NotFound')));

// ==============================|| ROTAS DO PAINEL ||============================== //

const router = createBrowserRouter(
  [
    { path: '/login', element: <GuestGuard><Login /></GuestGuard> },
    { path: '/recuperar-palavra-passe', element: <GuestGuard><ForgotPassword /></GuestGuard> },
    { path: '/repor-palavra-passe', element: <ResetPassword /> },
    { path: TWO_FACTOR_SETUP_PATH, element: <AuthGuard allowTwoFactorSetup><TwoFactorSetup /></AuthGuard> },
    {
      path: '/',
      element: (
        <AuthGuard>
          <DashboardLayout />
        </AuthGuard>
      ),
      children: [
        { index: true, element: <Dashboard /> },
        { path: 'perfil', element: <Profile /> },
        { path: 'paginas', element: <AreaGuard area="pages"><PagesList /></AreaGuard> },
        { path: 'paginas/nova', element: <AreaGuard area="pages"><PageForm key="new" /></AreaGuard> },
        { path: 'paginas/:id', element: <AreaGuard area="pages"><PageForm key="edit" /></AreaGuard> },
        { path: 'menus', element: <AreaGuard area="menus"><Menus /></AreaGuard> },
        { path: 'menus/:id', element: <AreaGuard area="menus"><MenuForm /></AreaGuard> },
        { path: 'media', element: <AreaGuard area="media"><MediaPage /></AreaGuard> },
        { path: 'servicos', element: <AreaGuard area="services"><CatalogList key="service" kind="service" /></AreaGuard> },
        { path: 'servicos/novo', element: <AreaGuard area="services"><CatalogForm key="service-new" kind="service" /></AreaGuard> },
        { path: 'servicos/:id', element: <AreaGuard area="services"><CatalogForm key="service-edit" kind="service" /></AreaGuard> },
        { path: 'vagas', element: <AreaGuard area="jobs"><JobsList /></AreaGuard> },
        { path: 'vagas/nova', element: <AreaGuard area="jobs"><JobForm key="new" /></AreaGuard> },
        { path: 'vagas/:id', element: <AreaGuard area="jobs"><JobForm key="edit" /></AreaGuard> },
        { path: 'categorias-de-vagas', element: <AreaGuard area="jobs"><JobCategories /></AreaGuard> },
        { path: 'blog/artigos', element: <AreaGuard area="blog"><PostsList /></AreaGuard> },
        { path: 'blog/artigos/novo', element: <AreaGuard area="blog"><PostForm key="new" /></AreaGuard> },
        { path: 'blog/artigos/:id', element: <AreaGuard area="blog"><PostForm key="edit" /></AreaGuard> },
        { path: 'blog/categorias', element: <AreaGuard area="blog"><BlogTerms key="categories" kind="categories" /></AreaGuard> },
        { path: 'blog/etiquetas', element: <AreaGuard area="blog"><BlogTerms key="tags" kind="tags" /></AreaGuard> },
        { path: 'mensagens', element: <AreaGuard area="messages"><Messages /></AreaGuard> },
        { path: 'mensagens/:id', element: <AreaGuard area="messages"><MessageDetail key="message" /></AreaGuard> },
        { path: 'candidaturas', element: <AreaGuard area="applications"><Applications /></AreaGuard> },
        { path: 'candidaturas/:id', element: <AreaGuard area="applications"><ApplicationDetail key="application" /></AreaGuard> },
        { path: 'inscricoes-de-empresas', element: <AreaGuard area="companies"><Companies /></AreaGuard> },
        { path: 'inscricoes-de-empresas/:id', element: <AreaGuard area="companies"><CompanyDetail key="company" /></AreaGuard> },
        { path: 'equipa', element: <AreaGuard area="team"><Team /></AreaGuard> },
        { path: 'definicoes/site', element: <AreaGuard area="settings"><GeneralSettings /></AreaGuard> },
        { path: 'definicoes/seo', element: <AreaGuard area="settings"><SeoSettings /></AreaGuard> },
        { path: 'utilizadores', element: <AreaGuard area="users"><UsersList /></AreaGuard> },
        { path: 'utilizadores/novo', element: <AreaGuard area="users"><UserForm /></AreaGuard> },
        { path: 'utilizadores/:id', element: <AreaGuard area="users"><UserForm key="edit" /></AreaGuard> },
        { path: 'perfis', element: <AreaGuard area="users"><RolesList /></AreaGuard> },
        { path: 'perfis/novo', element: <AreaGuard area="users"><RoleForm /></AreaGuard> },
        { path: 'perfis/:id', element: <AreaGuard area="users"><RoleForm key="edit" /></AreaGuard> },
        { path: '*', element: <NotFound /> }
      ]
    }
  ],
  { basename: '/admin' }
);

export default router;
