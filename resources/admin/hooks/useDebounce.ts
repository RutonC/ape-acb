import { useEffect, useState } from 'react';

// ==============================|| HOOK - VALOR COM ATRASO ||============================== //
// Para pesquisas: só actualiza depois de o utilizador parar de escrever.

export default function useDebounce<T>(value: T, delay = 350): T {
  const [debounced, setDebounced] = useState(value);

  useEffect(() => {
    const timer = setTimeout(() => setDebounced(value), delay);
    return () => clearTimeout(timer);
  }, [value, delay]);

  return debounced;
}
