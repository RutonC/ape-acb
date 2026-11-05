import { useState } from 'react';
import { Link as RouterLink, useNavigate } from 'react-router-dom';

// material-ui
import Link from '@mui/material/Link';
import Stack from '@mui/material/Stack';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';

// third-party
import useSWR from 'swr';

// project-imports
import ConfirmDialog from 'components/ConfirmDialog';
import InboxStatusChip from 'components/InboxStatusChip';
import IconButton from 'components/@extended/IconButton';
import DataTable, { BulkAction, Column } from 'components/data-table/DataTable';
import { refreshInboxCounts } from 'hooks/useInboxCounts';
import api, { errorMessage, fetcher } from 'utils/axios';
import { formatDateTime } from 'utils/dates';
import { downloadFile } from 'utils/download';
import notify from 'utils/notify';

// types
import { JobApplication, JobOpening } from 'types/inbox';
import { Paginated } from 'types/users';

// assets
import { DocumentDownload, Eye, Trash } from 'iconsax-react';

// ==============================|| CAIXA DE ENTRADA - CANDIDATURAS ||============================== //

export const APPLICATION_STATUS_OPTIONS = [
  { value: 'new', label: 'Nova' },
  { value: 'reviewing', label: 'Em análise' },
  { value: 'interview', label: 'Entrevista' },
  { value: 'rejected', label: 'Não seleccionada' },
  { value: 'hired', label: 'Admitida' }
];

export default function Applications() {
  const navigate = useNavigate();
  const [toDelete, setToDelete] = useState<{ application: JobApplication; reload: () => void } | null>(null);
  const { data: jobs } = useSWR<Paginated<JobOpening>>('/jobs?per_page=100', fetcher);

  const filters = [
    { id: 'status', label: 'Estado', options: APPLICATION_STATUS_OPTIONS },
    {
      id: 'job',
      label: 'Vaga',
      options: [
        { value: 'spontaneous', label: 'Candidatura espontânea' },
        ...(jobs?.data ?? []).map((job) => ({ value: String(job.id), label: job.title_text }))
      ]
    }
  ];

  const remove = async () => {
    if (!toDelete) return;
    const { application, reload } = toDelete;
    setToDelete(null);
    try {
      await api.delete(`/applications/${application.id}`);
      notify.success('Candidatura apagada, com o currículo.');
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
        title: (count) => `Apagar ${count === 1 ? 'a candidatura seleccionada' : `as ${count} candidaturas seleccionadas`}?`,
        description: 'Os currículos também são apagados. Esta acção não pode ser desfeita.',
        confirmLabel: 'Apagar'
      },
      run: async (ids) => {
        await api.post('/applications/bulk-delete', { ids });
        refreshInboxCounts();
        return 'Candidaturas apagadas.';
      }
    }
  ];

  const columns: Column<JobApplication>[] = [
    {
      id: 'name',
      label: 'Candidato',
      sortable: true,
      render: (application) => (
        <Stack>
          <Link
            component={RouterLink}
            to={`/candidaturas/${application.id}`}
            underline="hover"
            variant="subtitle1"
            sx={{ fontWeight: application.status === 'new' ? 700 : 500 }}
          >
            {application.name}
          </Link>
          <Typography variant="caption" color="text.secondary">
            {application.email}
          </Typography>
        </Stack>
      )
    },
    { id: 'position', label: 'Vaga', render: (application) => application.position },
    {
      id: 'status',
      label: 'Estado',
      sortable: true,
      render: (application) => <InboxStatusChip status={application.status} label={application.status_label} />
    },
    { id: 'created_at', label: 'Recebida', sortable: true, render: (application) => formatDateTime(application.created_at) }
  ];

  return (
    <>
      <DataTable<JobApplication>
        endpoint="/applications"
        columns={columns}
        filters={filters}
        defaultSort="-created_at"
        perPageOptions={[25, 50, 100]}
        searchPlaceholder="Pesquisar por nome, e-mail ou texto"
        bulkActions={bulkActions}
        emptyText="Ainda não há candidaturas."
        rowActions={(application, reload) => (
          <>
            <Tooltip title="Descarregar currículo">
              <IconButton
                color="secondary"
                onClick={() => downloadFile(`/applications/${application.id}/cv`, application.cv_name)}
                aria-label={`Descarregar currículo de ${application.name}`}
              >
                <DocumentDownload />
              </IconButton>
            </Tooltip>
            <Tooltip title="Abrir">
              <IconButton
                color="primary"
                onClick={() => navigate(`/candidaturas/${application.id}`)}
                aria-label={`Abrir candidatura de ${application.name}`}
              >
                <Eye />
              </IconButton>
            </Tooltip>
            <Tooltip title="Apagar">
              <IconButton
                color="error"
                onClick={() => setToDelete({ application, reload })}
                aria-label={`Apagar candidatura de ${application.name}`}
              >
                <Trash />
              </IconButton>
            </Tooltip>
          </>
        )}
      />

      <ConfirmDialog
        open={toDelete !== null}
        title={`Apagar a candidatura de ${toDelete?.application.name ?? ''}?`}
        description="O currículo também é apagado. Esta acção não pode ser desfeita."
        confirmLabel="Apagar"
        danger
        onClose={() => setToDelete(null)}
        onConfirm={remove}
      />
    </>
  );
}
