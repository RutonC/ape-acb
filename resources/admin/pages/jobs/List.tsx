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
import { jobValues } from './config';
import ConfirmDialog from 'components/ConfirmDialog';
import ReorderDialog from 'components/ReorderDialog';
import TranslationStatusChip from 'components/TranslationStatusChip';
import IconButton from 'components/@extended/IconButton';
import DataTable, { Column } from 'components/data-table/DataTable';
import api, { errorMessage, fetcher } from 'utils/axios';
import { formatDate } from 'utils/dates';
import notify from 'utils/notify';

// types
import { JobOpening } from 'types/inbox';
import { Paginated } from 'types/users';

// assets
import { Add, Edit, ExportSquare, Sort, Trash } from 'iconsax-react';

// ==============================|| VAGAS - LISTA ||============================== //

/** Aberta no site: ligada e dentro do prazo. */
const isLive = (job: JobOpening) => job.is_open && (!job.closes_at || job.closes_at >= new Date().toISOString().slice(0, 10));

export default function JobsList() {
  const navigate = useNavigate();
  const [reorderOpen, setReorderOpen] = useState(false);
  const [toDelete, setToDelete] = useState<{ job: JobOpening; reload: () => void } | null>(null);
  const [tableKey, setTableKey] = useState(0);
  const { data: all, mutate: reloadAll } = useSWR<Paginated<JobOpening>>(reorderOpen ? '/jobs?per_page=100' : null, fetcher);

  const toggleOpen = async (job: JobOpening, reload: () => void) => {
    try {
      // A API valida o registo completo, por isso vai buscar os textos antes de alterar o interruptor.
      const { data } = await api.get<{ data: JobOpening }>(`/jobs/${job.id}`);
      await api.put(`/jobs/${job.id}`, { ...jobValues(data.data), is_open: !job.is_open });
      notify.success(job.is_open ? 'Vaga fechada.' : 'Vaga aberta.');
      reload();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  const remove = async () => {
    if (!toDelete) return;
    const { job, reload } = toDelete;
    setToDelete(null);
    try {
      await api.delete(`/jobs/${job.id}`);
      notify.success('Vaga apagada.');
      reload();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  const columns: Column<JobOpening>[] = [
    {
      id: 'sort_order',
      label: 'Vaga',
      sortable: true,
      render: (job) => (
        <Stack>
          <Typography variant="subtitle1">{job.title_text}</Typography>
          {job.contract_text && (
            <Typography variant="caption" color="text.secondary">
              {job.contract_text}
            </Typography>
          )}
        </Stack>
      )
    },
    { id: 'translation', label: 'EN', align: 'center', render: (job) => <TranslationStatusChip status={job.translation} /> },
    {
      id: 'closes_at',
      label: 'Até',
      sortable: true,
      render: (job) => (job.closes_at ? formatDate(job.closes_at) : '—')
    },
    {
      id: 'applications_count',
      label: 'Candidaturas',
      align: 'center',
      render: (job) =>
        job.applications_count > 0 ? (
          <Chip
            size="small"
            color="primary"
            variant="light"
            component={RouterLink}
            to={`/candidaturas?job=${job.id}`}
            clickable
            label={job.applications_count}
          />
        ) : (
          <Typography variant="body2" color="text.secondary">
            0
          </Typography>
        )
    },
    {
      id: 'is_open',
      label: 'Estado',
      render: (job) => (
        <Chip
          size="small"
          variant="light"
          color={isLive(job) ? 'success' : 'default'}
          label={isLive(job) ? 'Aberta' : job.is_open ? 'Prazo terminado' : 'Fechada'}
        />
      )
    }
  ];

  return (
    <>
      <DataTable<JobOpening>
        key={tableKey}
        endpoint="/jobs"
        columns={columns}
        defaultSort="sort_order"
        perPageOptions={[50, 100]}
        searchPlaceholder="Pesquisar vagas"
        filters={[
          {
            id: 'open',
            label: 'Estado',
            options: [
              { value: '1', label: 'Abertas' },
              { value: '0', label: 'Fechadas' }
            ]
          }
        ]}
        toolbar={
          <>
            <Button variant="outlined" color="secondary" startIcon={<Sort />} onClick={() => setReorderOpen(true)}>
              Ordenar
            </Button>
            <Button component={RouterLink} to="/vagas/nova" variant="contained" startIcon={<Add />}>
              Nova vaga
            </Button>
          </>
        }
        emptyText="Ainda não há vagas. Sem vagas abertas, a página Carreiras mostra só a candidatura espontânea."
        rowActions={(job, reload) => (
          <>
            <Tooltip title={job.is_open ? 'Aberta a candidaturas' : 'Fechada'}>
              <Switch
                checked={job.is_open}
                onChange={() => toggleOpen(job, reload)}
                inputProps={{ 'aria-label': `Aberta: ${job.title_text}` }}
              />
            </Tooltip>
            {isLive(job) && (
              <Tooltip title="Ver no site">
                <IconButton
                  color="secondary"
                  onClick={() => window.open(job.url, '_blank', 'noopener')}
                  aria-label={`Ver ${job.title_text} no site`}
                >
                  <ExportSquare />
                </IconButton>
              </Tooltip>
            )}
            <Tooltip title="Editar">
              <IconButton color="primary" onClick={() => navigate(`/vagas/${job.id}`)} aria-label={`Editar ${job.title_text}`}>
                <Edit />
              </IconButton>
            </Tooltip>
            <Tooltip title="Apagar">
              <IconButton color="error" onClick={() => setToDelete({ job, reload })} aria-label={`Apagar ${job.title_text}`}>
                <Trash />
              </IconButton>
            </Tooltip>
          </>
        )}
      />

      <ReorderDialog
        open={reorderOpen && Boolean(all)}
        title="Ordenar vagas"
        endpoint="/jobs/reorder"
        items={(all?.data ?? []).map((job) => ({ id: job.id, label: job.title_text, caption: job.is_open ? undefined : 'Fechada' }))}
        onClose={() => setReorderOpen(false)}
        onSaved={() => {
          reloadAll();
          setTableKey((key) => key + 1);
        }}
      />

      <ConfirmDialog
        open={toDelete !== null}
        title={`Apagar ${toDelete?.job.title_text ?? ''}?`}
        description={
          toDelete && toDelete.job.applications_count > 0
            ? `A página da vaga deixa de existir. As ${toDelete.job.applications_count} candidaturas ficam na Caixa de entrada como candidaturas espontâneas. Para parar de receber candidaturas sem apagar, feche a vaga.`
            : 'A página da vaga deixa de existir. Para parar de receber candidaturas sem apagar, feche a vaga.'
        }
        confirmLabel="Apagar"
        danger
        onClose={() => setToDelete(null)}
        onConfirm={remove}
      />
    </>
  );
}
