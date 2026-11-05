import { createContext, ReactNode, useCallback, useEffect, useMemo, useState } from 'react';

// project-imports
import api, { AUTH_EVENTS, csrf, isStatus } from 'utils/axios';

// types
import { AuthContextValue, AuthStatus, AuthUser, LoginResult } from 'types/auth';

// ==============================|| CONTEXTO DE AUTENTICAÇÃO ||============================== //
// Sessão do Laravel (Fortify + Sanctum). O estado vem sempre de /api/admin/me.

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [status, setStatus] = useState<AuthStatus>('loading');
  const [user, setUser] = useState<AuthUser | null>(null);

  const refresh = useCallback(async () => {
    try {
      const { data } = await api.get<{ data: AuthUser }>('/me');
      setUser(data.data);
      setStatus('authenticated');
      return data.data;
    } catch (error) {
      if (isStatus(error, 401) || isStatus(error, 403)) {
        setUser(null);
        setStatus('guest');
        return null;
      }
      throw error;
    }
  }, []);

  useEffect(() => {
    csrf()
      .then(refresh)
      .catch(() => setStatus('guest'));

    const onUnauthenticated = () => {
      setUser(null);
      setStatus('guest');
    };
    const onTwoFactorRequired = () => {
      refresh();
    };

    window.addEventListener(AUTH_EVENTS.unauthenticated, onUnauthenticated);
    window.addEventListener(AUTH_EVENTS.twoFactorRequired, onTwoFactorRequired);

    return () => {
      window.removeEventListener(AUTH_EVENTS.unauthenticated, onUnauthenticated);
      window.removeEventListener(AUTH_EVENTS.twoFactorRequired, onTwoFactorRequired);
    };
  }, [refresh]);

  const login = useCallback(
    async (email: string, password: string, remember: boolean): Promise<LoginResult> => {
      await csrf();
      const { data } = await api.post<{ two_factor: boolean }>('/auth/login', { email, password, remember });

      if (data.two_factor) return 'two-factor';

      await refresh();
      return 'ok';
    },
    [refresh]
  );

  const twoFactorChallenge = useCallback(
    async (payload: { code: string } | { recovery_code: string }) => {
      await api.post('/auth/two-factor-challenge', payload);
      await refresh();
    },
    [refresh]
  );

  const logout = useCallback(async () => {
    try {
      await api.post('/auth/logout');
    } finally {
      setUser(null);
      setStatus('guest');
      await csrf().catch(() => undefined);
    }
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({
      status,
      user,
      needsTwoFactorSetup: Boolean(user?.two_factor.required && !user.two_factor.enabled),
      login,
      twoFactorChallenge,
      logout,
      refresh,
      can: (area) => !area || Boolean(user?.areas.includes(area as never))
    }),
    [status, user, login, twoFactorChallenge, logout, refresh]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export default AuthContext;
