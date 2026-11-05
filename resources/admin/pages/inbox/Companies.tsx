import { useState } from 'react';
import { Link as RouterLink, useNavigate } from 'react-router-dom';

// material-ui
import Link from '@mui/material/Link';
import Stack from '@mui/material/Stack';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';

// project-imports
import ConfirmDialog from 'components/ConfirmDialog';
import InboxStatusChip from 'components/InboxStatusChip';
import IconButton from 'components/@extended/IconButton';
import DataTable, { BulkAction, Column } from 'components/data-table/DataTable';
import { refreshInboxCounts } from 'hooks/useInboxCounts';
import api, { errorMessage } from 'utils/axios';
import { formatDateTime } from 'utils/dates';
import notify from 'utils/notify';

// types
import { CompanyRegistration } from 'types/inbox';

// assets
import { Eye, Trash } from 'iconsax-react';

// ==============================|| CAIXA DE ENTRADA - EMPRESAS INSCRITAS ||============================== //

export const COMPANY_STATUS_OPTIONS = [
  { value: 'new', label: 'Nova' },
  { value: 'read', label: 'Lida' },
  { value: 'replied', label: 'Contactada' }
];

export default function Companies() {
  const navigate = useNavigate();
  const [toDelete, setToDelete] = useState<{ registration: CompanyRegistration; reload: () => void } | null>(null);

  const remove = async () => {
    if (!toDelete) return;
    const { registration, reload } = toDelete;
    setToDelete(null);
    try {
      await api.delete(`/company-registrations/${registration.id}`);
      notify.success('Inscrição apagada.');
      reload();
      refreshInboxCounts();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  const bulkActions: BulkAction[] = [
    {
      label: 'Apagar',
      color: 'error',
      icon: <Trash size={16} />,
      confirm: {
        title: (count) => `Apagar ${count === 1 ? 'a inscrição seleccionada' : `as ${count} inscrições seleccionadas`}?`,
        description: 'Esta acção não pode ser desfeita.',
        confirmLabel: 'Apagar'
      },
      run: async (ids) => {
        await api.post('/company-registrations/bulk-delete', { ids });
        refreshInboxCounts();
        return 'Inscrições apagadas.';
      }
    }
  ];

  const columns: Column<CompanyRegistration>[] = [
    {
      id: 'company_name',
      label: 'Empresa',
      sortable: true,
      render: (registration) => (
        <Stack>
          <Link
            component={RouterLink}
            to={`/inscricoes-de-empresas/${registration.id}`}
            underline="hover"
            variant="subtitle1"
            sx={{ fontWeight: registration.status === 'new' ? 700 : 500 }}
          >
            {registration.company_name}
          </Link>
          <Typography variant="caption" color="text.secondary">
            {registration.name}
          </Typography>
        </Stack>
      )
    },
    { id: 'phone', label: 'Telefone', render: (registration) => registration.phone },
    { id: 'email', label: 'E-mail', render: (registration) => registration.email },
    {
      id: 'status',
      label: 'Estado',
      sortable: true,
      render: (registration) => <InboxStatusChip status={registration.status} label={registration.status_label} />
    },
    { id: 'created_at', label: 'Recebida', sortable: true, render: (registration) => formatDateTime(registration.created_at) }
  ];

  return (
    <>
      <DataTable<CompanyRegistration>
        endpoint="/company-registrations"
        columns={columns}
        filters={[{ id: 'status', label: 'Estado', options: COMPANY_STATUS_OPTIONS }]}
        defaultSort="-created_at"
        perPageOptions={[25, 50, 100]}
        searchPlaceholder="Pesquisar por empresa, nome, e-mail ou telefone"
        bulkActions={bulkActions}
        emptyText="Ainda não há empresas inscritas."
        rowActions={(registration, reload) => (
          <>
            <Tooltip title="Abrir">
              <IconButton color="primary" onClick={() => navigate(`/inscricoes-de-empresas/${registration.id}`)} aria-label={`Abrir inscrição de ${registration.company_name}`}>
                <Eye />
              </IconButton>
            </Tooltip>
            <Tooltip title="Apagar">
              <IconButton color="error" onClick={() => setToDelete({ registration, reload })} aria-label={`Apagar inscrição de ${registration.company_name}`}>
                <Trash />
              </IconButton>
            </Tooltip>
          </>
        )}
      />

      <ConfirmDialog
        open={toDelete !== null}
        title={`Apagar a inscrição de ${toDelete?.registration.company_name ?? ''}?`}
        description="Esta acção não pode ser desfeita."
        confirmLabel="Apagar"
        danger
        onClose={() => setToDelete(null)}
        onConfirm={remove}
      />
    </>
  );
}
