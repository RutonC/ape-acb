import { useContext } from 'react';

// project-imports
import AuthContext from 'contexts/AuthContext';

// ==============================|| HOOK - AUTENTICAÇÃO ||============================== //

export default function useAuth() {
  const context = useContext(AuthContext);

  if (!context) throw new Error('useAuth tem de ser usado dentro do AuthProvider');

  return context;
}
