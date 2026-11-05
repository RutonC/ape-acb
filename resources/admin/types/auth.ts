// ==============================|| TIPOS - AUTENTICAÇÃO ||============================== //

/** Áreas do painel (chaves de App\Support\Permissions::ALL). */
export type Area =
  | 'messages'
  | 'applications'
  | 'companies'
  | 'jobs'
  | 'pages'
  | 'menus'
  | 'media'
  | 'services'
  | 'blog'
  | 'team'
  | 'settings'
  | 'users';

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  roles: string[];
  is_admin: boolean;
  areas: Area[];
  two_factor: {
    enabled: boolean;
    pending: boolean;
    required: boolean;
  };
}

export type AuthStatus = 'loading' | 'guest' | 'authenticated';

export type LoginResult = 'ok' | 'two-factor';

export interface AuthContextValue {
  status: AuthStatus;
  user: AuthUser | null;
  /** O 2FA é obrigatório e ainda não está configurado. */
  needsTwoFactorSetup: boolean;
  login: (email: string, password: string, remember: boolean) => Promise<LoginResult>;
  twoFactorChallenge: (payload: { code: string } | { recovery_code: string }) => Promise<void>;
  logout: () => Promise<void>;
  refresh: () => Promise<AuthUser | null>;
  can: (area?: Area | string) => boolean;
}
