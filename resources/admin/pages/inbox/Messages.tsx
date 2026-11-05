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
import { ContactMessage } from 'types/inbox';

// assets
import { Eye, Trash } from 'iconsax-react';

// ==============================|| CAIXA DE ENTRADA - MENSAGENS ||============================== //

const FILTERS = [
  {
    id: 'status',
    label: 'Estado',
    options: [
      { value: 'new', label: 'Novas' },
      { value: 'read', label: 'Lidas' },
      { value: 'replied', label: 'Respondidas' }
    ]
  }
];

export default function Messages() {
  const navigate = useNavigate();
  const [toDelete, setToDelete] = useState<{ message: ContactMessage; reload: () => void } | null>(null);

  const remove = async () => {
    if (!toDelete) return;
    const { message, reload } = toDelete;
    setToDelete(null);
    try {
      await api.delete(`/messages/${message.id}`);
      notify.success('Mensagem apagada.');
      reload();
      refreshInboxCounts();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  const setStatus = (status: 'read' | 'replied', label: string): BulkAction => ({
    label,
    run: async (ids) => {
      await api.post('/messages/bulk-status', { ids, status });
      refreshInboxCounts();
      return 'Estado actualizado.';
    }
  });

  const bulkActions: BulkAction[] = [
    setStatus('read', 'Marcar como lidas'),
    setStatus('replied', 'Marcar como respondidas'),
    {
      label: 'Apagar',
      color: 'error',
      icon: <Trash size={16} />,
      confirm: {
        title: (count) => `Apagar ${count === 1 ? 'a mensagem seleccionada' : `as ${count} mensagens seleccionadas`}?`,
        description: 'Esta acção não pode ser desfeita.',
        confirmLabel: 'Apagar'
      },
      run: async (ids) => {
        await api.post('/messages/bulk-delete', { ids });
        refreshInboxCounts();
        return 'Mensagens apagadas.';
      }
    }
  ];

  const columns: Column<ContactMessage>[] = [
    {
      id: 'name',
      label: 'Remetente',
      sortable: true,
      render: (message) => (
        <Stack>
          <Link
            component={RouterLink}
            to={`/mensagens/${message.id}`}
            underline="hover"
            variant="subtitle1"
            sx={{ fontWeight: message.status === 'new' ? 700 : 500 }}
          >
            {message.name}
          </Link>
          <Typography variant="caption" color="text.secondary">
            {[message.organization, message.email].filter(Boolean).join(' · ')}
          </Typography>
        </Stack>
      )
    },
    {
      id: 'message',
      label: 'Mensagem',
      render: (message) => (
        <Stack>
          {message.service && (
            <Typography variant="caption" color="primary">
              {message.service}
            </Typography>
          )}
          <Typography
            variant="body2"
            color="text.secondary"
            sx={{ display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden', maxWidth: 480 }}
          >
            {message.excerpt}
          </Typography>
        </Stack>
      )
    },
    {
      id: 'status',
      label: 'Estado',
      sortable: true,
      render: (message) => <InboxStatusChip status={message.status} label={message.status_label} />
    },
    { id: 'created_at', label: 'Recebida', sortable: true, render: (message) => formatDateTime(message.created_at) }
  ];

  return (
    <>
      <DataTable<ContactMessage>
        endpoint="/messages"
        columns={columns}
        filters={FILTERS}
        defaultSort="-created_at"
        perPageOptions={[25, 50, 100]}
        searchPlaceholder="Pesquisar por nome, e-mail ou texto"
        bulkActions={bulkActions}
        emptyText="Ainda não há mensagens do formulário de contacto."
        rowActions={(message, reload) => (
          <>
            <Tooltip title="Abrir">
              <IconButton
                color="primary"
                onClick={() => navigate(`/mensagens/${message.id}`)}
                aria-label={`Abrir mensagem de ${message.name}`}
              >
                <Eye />
              </IconButton>
            </Tooltip>
            <Tooltip title="Apagar">
              <IconButton color="error" onClick={() => setToDelete({ message, reload })} aria-label={`Apagar mensagem de ${message.name}`}>
                <Trash />
              </IconButton>
            </Tooltip>
          </>
        )}
      />

      <ConfirmDialog
        open={toDelete !== null}
        title={`Apagar a mensagem de ${toDelete?.message.name ?? ''}?`}
        description="Esta acção não pode ser desfeita."
        confirmLabel="Apagar"
        danger
        onClose={() => setToDelete(null)}
        onConfirm={remove}
      />
    </>
  );
}
