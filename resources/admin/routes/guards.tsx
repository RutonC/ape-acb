import { ReactElement } from 'react';
import { Navigate, useLocation } from 'react-router-dom';

// project-imports
import Loader from 'components/Loader';
import useAuth from 'hooks/useAuth';
import NotAllowed from 'pages/NotAllowed';

// types
import { Area } from 'types/auth';

// ==============================|| GUARDAS DE ROTA ||============================== //

export const TWO_FACTOR_SETUP_PATH = '/configurar-dois-passos';

/** Só com sessão iniciada. Com 2FA obrigatório por configurar, só a página de configuração. */
export function AuthGuard({ children, allowTwoFactorSetup = false }: { children: ReactElement; allowTwoFactorSetup?: boolean }) {
  const { status, needsTwoFactorSetup } = useAuth();
  const location = useLocation();

  if (status === 'loading') return <Loader />;

  if (status === 'guest') return <Navigate to="/login" replace state={{ from: location.pathname + location.search }} />;

  if (needsTwoFactorSetup && !allowTwoFactorSetup) return <Navigate to={TWO_FACTOR_SETUP_PATH} replace />;

  if (!needsTwoFactorSetup && allowTwoFactorSetup) return <Navigate to="/" replace />;

  return children;
}

/** Páginas de entrada (login, recuperar palavra-passe): quem já entrou vai para o painel. */
export function GuestGuard({ children }: { children: ReactElement }) {
  const { status } = useAuth();

  if (status === 'loading') return <Loader />;

  if (status === 'authenticated') return <Navigate to="/" replace />;

  return children;
}

/** Secções que exigem uma área (permissão) do painel. */
export function AreaGuard({ area, children }: { area: Area; children: ReactElement }) {
  const { can } = useAuth();

  return can(area) ? children : <NotAllowed />;
}
