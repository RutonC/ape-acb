import { useState } from 'react';
import { Link as RouterLink, useNavigate } from 'react-router-dom';

// material-ui
import Button from '@mui/material/Button';
import Chip from '@mui/material/Chip';
import Stack from '@mui/material/Stack';
import Switch from '@mui/material/Switch';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';

// third-party
import useSWR from 'swr';

// project-imports
import ConfirmDialog from 'components/ConfirmDialog';
import UserAvatar from 'components/UserAvatar';
import IconButton from 'components/@extended/IconButton';
import DataTable, { Column } from 'components/data-table/DataTable';
import api, { errorMessage, fetcher } from 'utils/axios';
import notify from 'utils/notify';

// types
import { AdminUser, Role } from 'types/users';

// assets
import { Add, Edit, Key, ShieldTick, Trash } from 'iconsax-react';

// ==============================|| UTILIZADORES - LISTA ||============================== //

type PendingAction = { type: 'delete' | 'reset'; user: AdminUser; reload: () => void } | null;

export default function UsersList() {
  const navigate = useNavigate();
  const [pending, setPending] = useState<PendingAction>(null);
  const { data: roles } = useSWR<{ data: Role[] }>('/roles', fetcher);

  const toggleActive = async (user: AdminUser, reload: () => void) => {
    try {
      await api.put(`/users/${user.id}`, { name: user.name, email: user.email, roles: user.roles.map((r) => r.id), is_active: !user.is_active });
      notify.success(user.is_active ? `${user.name} já não pode entrar no painel.` : `${user.name} pode voltar a entrar no painel.`);
      reload();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  const confirmPending = async () => {
    if (!pending) return;
    const { type, user, reload } = pending;
    setPending(null);
    try {
      if (type === 'delete') {
        await api.delete(`/users/${user.id}`);
        notify.success('Utilizador apagado.');
      } else {
        await api.post(`/users/${user.id}/reset-two-factor`);
        notify.success(`Verificação em dois passos reposta para ${user.name}.`);
      }
      reload();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  const columns: Column<AdminUser>[] = [
    {
      id: 'name',
      label: 'Nome',
      sortable: true,
      render: (user) => (
        <Stack direction="row" sx={{ gap: 1.5, alignItems: 'center' }}>
          <UserAvatar name={user.name} />
          <Stack>
            <Typography variant="subtitle1">
              {user.name}
              {user.is_self && (
                <Typography component="span" variant="caption" color="text.secondary">
                  {' '}
                  (eu)
                </Typography>
              )}
            </Typography>
            <Typography variant="caption" color="text.secondary">
              {user.email}
            </Typography>
          </Stack>
        </Stack>
      )
    },
    {
      id: 'roles',
      label: 'Perfis',
      render: (user) => (
        <Stack direction="row" sx={{ gap: 0.5, flexWrap: 'wrap' }}>
          {user.roles.map((item) => (
            <Chip key={item.id} size="small" label={item.name} color={item.name === 'Administrador' ? 'primary' : 'secondary'} variant="light" />
          ))}
        </Stack>
      )
    },
    {
      id: 'two_factor',
      label: '2 passos',
      align: 'center',
      render: (user) =>
        user.two_factor_enabled ? (
          <Chip size="small" color="success" variant="light" icon={<ShieldTick size={16} />} label="Activa" />
        ) : (
          <Typography variant="caption" color="text.secondary">
            Por configurar
          </Typography>
        )
    }
  ];

  return (
    <>
      <DataTable<AdminUser>
        endpoint="/users"
        columns={columns}
        defaultSort="name"
        searchPlaceholder="Pesquisar por nome ou e-mail"
        filters={[
          { id: 'role', label: 'Perfil', options: (roles?.data ?? []).map((role) => ({ value: String(role.id), label: role.name })) },
          {
            id: 'active',
            label: 'Estado',
            options: [
              { value: '1', label: 'Activos' },
              { value: '0', label: 'Desactivados' }
            ]
          }
        ]}
        toolbar={
          <Button component={RouterLink} to="/utilizadores/novo" variant="contained" startIcon={<Add />}>
            Novo utilizador
          </Button>
        }
        emptyText="Ainda não há utilizadores."
        rowActions={(user, reload) => {
          const locked = user.is_self || user.is_last_admin;
          return (
            <>
              <Tooltip title={user.is_self ? 'Não pode desactivar a sua conta' : user.is_last_admin ? 'Último administrador activo' : user.is_active ? 'Desactivar' : 'Activar'}>
                <span>
                  <Switch checked={user.is_active} disabled={locked} onChange={() => toggleActive(user, reload)} inputProps={{ 'aria-label': `Activo: ${user.name}` }} />
                </span>
              </Tooltip>
              <Tooltip title="Editar">
                <IconButton color="primary" onClick={() => navigate(`/utilizadores/${user.id}`)} aria-label={`Editar ${user.name}`}>
                  <Edit />
                </IconButton>
              </Tooltip>
              {user.two_factor_enabled && (
                <Tooltip title="Repor verificação em dois passos">
                  <IconButton color="warning" onClick={() => setPending({ type: 'reset', user, reload })} aria-label={`Repor 2 passos de ${user.name}`}>
                    <Key />
                  </IconButton>
                </Tooltip>
              )}
              {!locked && (
                <Tooltip title="Apagar">
                  <IconButton color="error" onClick={() => setPending({ type: 'delete', user, reload })} aria-label={`Apagar ${user.name}`}>
                    <Trash />
                  </IconButton>
                </Tooltip>
              )}
            </>
          );
        }}
      />

      <ConfirmDialog
        open={pending !== null}
        title={pending?.type === 'delete' ? `Apagar ${pending.user.name}?` : `Repor os dois passos de ${pending?.user.name}?`}
        description={
          pending?.type === 'delete'
            ? 'A conta é apagada. Se quiser manter o histórico, desactive-a em vez de apagar.'
            : 'O utilizador terá de configurar de novo a aplicação de autenticação no próximo acesso (por exemplo, se perdeu o telemóvel).'
        }
        confirmLabel={pending?.type === 'delete' ? 'Apagar' : 'Repor'}
        danger={pending?.type === 'delete'}
        onClose={() => setPending(null)}
        onConfirm={confirmPending}
      />
    </>
  );
}
