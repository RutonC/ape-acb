import { useState } from 'react';
import { Link as RouterLink, useNavigate } from 'react-router-dom';

// material-ui
import Button from '@mui/material/Button';
import Chip from '@mui/material/Chip';
import Stack from '@mui/material/Stack';
import Table from '@mui/material/Table';
import TableBody from '@mui/material/TableBody';
import TableCell from '@mui/material/TableCell';
import TableContainer from '@mui/material/TableContainer';
import TableHead from '@mui/material/TableHead';
import TableRow from '@mui/material/TableRow';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';

// third-party
import useSWR from 'swr';

// project-imports
import ConfirmDialog from 'components/ConfirmDialog';
import MainCard from 'components/MainCard';
import IconButton from 'components/@extended/IconButton';
import api, { errorMessage, fetcher } from 'utils/axios';
import notify from 'utils/notify';

// types
import { PermissionGroup, Role } from 'types/users';

// assets
import { Add, Edit, Trash } from 'iconsax-react';

// ==============================|| PERFIS E PERMISSÕES - LISTA ||============================== //

export default function RolesList() {
  const navigate = useNavigate();
  const { data, mutate } = useSWR<{ data: Role[] }>('/roles', fetcher);
  const { data: permissions } = useSWR<{ data: PermissionGroup[] }>('/permissions', fetcher);
  const [toDelete, setToDelete] = useState<Role | null>(null);

  const labels = Object.fromEntries((permissions?.data ?? []).flatMap((group) => group.items.map((item) => [item.key, item.label])));

  const remove = async () => {
    if (!toDelete) return;
    const role = toDelete;
    setToDelete(null);
    try {
      await api.delete(`/roles/${role.id}`);
      notify.success('Perfil apagado.');
      mutate();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  return (
    <MainCard content={false}>
      <Stack direction="row" sx={{ p: 2.5, justifyContent: 'space-between', alignItems: 'center', gap: 2, flexWrap: 'wrap' }}>
        <Typography color="text.secondary">Cada perfil define as áreas do painel a que os utilizadores acedem.</Typography>
        <Button component={RouterLink} to="/perfis/novo" variant="contained" startIcon={<Add />}>
          Novo perfil
        </Button>
      </Stack>
      <TableContainer>
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Perfil</TableCell>
              <TableCell>Áreas do painel</TableCell>
              <TableCell align="center">Utilizadores</TableCell>
              <TableCell align="right">Acções</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {data?.data.map((role) => (
              <TableRow key={role.id} hover>
                <TableCell>
                  <Typography variant="subtitle1">{role.name}</Typography>
                </TableCell>
                <TableCell>
                  <Stack direction="row" sx={{ gap: 0.5, flexWrap: 'wrap', maxWidth: 560 }}>
                    {role.is_admin ? (
                      <Chip size="small" color="primary" variant="light" label="Todas" />
                    ) : role.permissions.length === 0 ? (
                      <Typography variant="caption" color="text.secondary">
                        Nenhuma
                      </Typography>
                    ) : (
                      role.permissions.map((key) => <Chip key={key} size="small" color="secondary" variant="light" label={labels[key] ?? key} />)
                    )}
                  </Stack>
                </TableCell>
                <TableCell align="center">{role.users_count}</TableCell>
                <TableCell align="right">
                  <Stack direction="row" sx={{ gap: 0.5, justifyContent: 'flex-end' }}>
                    {!role.is_admin && (
                      <Tooltip title="Editar">
                        <IconButton color="primary" onClick={() => navigate(`/perfis/${role.id}`)} aria-label={`Editar ${role.name}`}>
                          <Edit />
                        </IconButton>
                      </Tooltip>
                    )}
                    {!role.is_admin && (
                      <Tooltip title={role.users_count > 0 ? 'Só é possível apagar perfis sem utilizadores' : 'Apagar'}>
                        <span>
                          <IconButton
                            color="error"
                            disabled={role.users_count > 0}
                            onClick={() => setToDelete(role)}
                            aria-label={`Apagar ${role.name}`}
                          >
                            <Trash />
                          </IconButton>
                        </span>
                      </Tooltip>
                    )}
                    {role.is_admin && (
                      <Typography variant="caption" color="text.secondary">
                        Acesso total, não editável
                      </Typography>
                    )}
                  </Stack>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>

      <ConfirmDialog
        open={toDelete !== null}
        title={`Apagar o perfil ${toDelete?.name ?? ''}?`}
        confirmLabel="Apagar"
        danger
        onClose={() => setToDelete(null)}
        onConfirm={remove}
      />
    </MainCard>
  );
}
