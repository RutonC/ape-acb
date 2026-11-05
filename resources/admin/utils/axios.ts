import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios';

// ==============================|| CLIENTE DA API DO PAINEL ||============================== //
// Mesmo domínio do Laravel: a sessão vai no cookie e o token CSRF no cabeçalho
// X-XSRF-TOKEN (lido do cookie XSRF-TOKEN que o Sanctum define).

const api = axios.create({
  baseURL: '/api/admin',
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
});

/** Pede ao Laravel o cookie CSRF (antes do login ou quando a sessão expira). */
export async function csrf() {
  await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}

/** Eventos que o contexto de autenticação escuta. */
export const AUTH_EVENTS = {
  unauthenticated: 'admin:unauthenticated',
  twoFactorRequired: 'admin:two-factor-required'
};

api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError<{ code?: string }>) => {
    const config = error.config as (InternalAxiosRequestConfig & { _retried?: boolean }) | undefined;
    const status = error.response?.status;

    // 419: o token CSRF expirou. Renova e repete uma vez.
    if (status === 419 && config && !config._retried) {
      config._retried = true;
      await csrf();
      return api(config);
    }

    if (status === 401) {
      window.dispatchEvent(new Event(AUTH_EVENTS.unauthenticated));
    }

    if (status === 403 && error.response?.data?.code === 'two_factor_required') {
      window.dispatchEvent(new Event(AUTH_EVENTS.twoFactorRequired));
    }

    return Promise.reject(error);
  }
);

/** Fetcher para o SWR: devolve o corpo da resposta. */
export const fetcher = (url: string) => api.get(url).then((response) => response.data);

type ErrorBody = { message?: string; errors?: Record<string, string[]> };

/** Mensagem a mostrar ao utilizador para um erro da API. */
export function errorMessage(error: unknown, fallback = 'Não foi possível concluir a operação. Tente de novo.'): string {
  if (axios.isAxiosError<ErrorBody>(error)) {
    if (!error.response) return 'Sem ligação ao servidor. Verifique a ligação à internet.';
    if (error.response.status === 429) return 'Demasiadas tentativas. Aguarde um minuto e tente de novo.';

    const errors = error.response.data?.errors;
    if (errors) return Object.values(errors)[0]?.[0] ?? fallback;

    return error.response.data?.message || fallback;
  }

  return fallback;
}

/** Erros de validação (422) campo a campo, com a primeira mensagem de cada campo. */
export function fieldErrors(error: unknown): Record<string, string> {
  if (axios.isAxiosError<ErrorBody>(error) && error.response?.status === 422 && error.response.data?.errors) {
    return Object.fromEntries(Object.entries(error.response.data.errors).map(([field, messages]) => [field, messages[0]]));
  }

  return {};
}

export function isStatus(error: unknown, status: number): boolean {
  return axios.isAxiosError(error) && error.response?.status === status;
}

export default api;
