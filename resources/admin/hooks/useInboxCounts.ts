// third-party
import useSWR, { mutate } from 'swr';

// project-imports
import useAuth from 'hooks/useAuth';
import { fetcher } from 'utils/axios';

// types
import { InboxCounts } from 'types/inbox';

// ==============================|| CONTADORES DA CAIXA DE ENTRADA ||============================== //

const KEY = '/inbox/counts';

/** Candidaturas, empresas e mensagens novas (para o menu); só pede à API quem tem acesso a alguma das áreas. */
export default function useInboxCounts(): InboxCounts {
  const { can } = useAuth();
  const enabled = can('messages') || can('applications') || can('companies');
  const { data } = useSWR<{ data: InboxCounts }>(enabled ? KEY : null, fetcher, { refreshInterval: 60_000 });

  return data?.data ?? { messages: 0, applications: 0, companies: 0 };
}

/** Actualiza os contadores já (depois de abrir, mudar o estado ou apagar). */
export function refreshInboxCounts() {
  return mutate(KEY);
}
