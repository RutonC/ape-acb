// ==============================|| TIPOS - UTILIZADORES E PERFIS ||============================== //

export interface RoleRef {
  id: number;
  name: string;
}

export interface AdminUser {
  id: number;
  name: string;
  email: string;
  is_active: boolean;
  roles: RoleRef[];
  two_factor_enabled: boolean;
  is_self: boolean;
  is_last_admin: boolean;
  created_at: string | null;
}

export interface Role {
  id: number;
  name: string;
  is_admin: boolean;
  permissions: string[];
  users_count: number;
}

export interface PermissionGroup {
  group: string;
  items: { key: string; label: string }[];
}

/** Resposta paginada do Laravel (API Resources). */
export interface Paginated<T> {
  data: T[];
  meta: { current_page: number; per_page: number; total: number; last_page: number };
}
