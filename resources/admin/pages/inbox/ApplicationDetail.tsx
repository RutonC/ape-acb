import { ReactNode, useEffect, useState } from 'react';
import { Link as RouterLink, useNavigate, useParams } from 'react-router-dom';

// material-ui
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Divider from '@mui/material/Divider';
import Grid from '@mui/material/Grid2';
import Link from '@mui/material/Link';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// third-party
import useSWR from 'swr';

// project-imports
import { APPLICATION_STATUS_OPTIONS } from './Applications';
import StatusNotesCard from './StatusNotesCard';
import ConfirmDialog from 'components/ConfirmDialog';
import InboxStatusChip from 'components/InboxStatusChip';
import Loader from 'components/Loader';
import MainCard from 'components/MainCard';
import { refreshInboxCounts } from 'hooks/useInboxCounts';
import NotFound from 'pages/NotFound';
import api, { errorMessage, fetcher } from 'utils/axios';
import { formatDate, formatDateTime } from 'utils/dates';
import { downloadFile } from 'utils/download';
import notify from 'utils/notify';

// types
import { JobApplication } from 'types/inbox';

// assets
import { DocumentDownload } from 'iconsax-react';

// ==============================|| CAIXA DE ENTRADA - CANDIDATURA ||============================== //
// Abrir uma candidatura nova passa-a "Em análise" (no servidor).

function Info({ label, children }: { label: string; children: ReactNode }) {
  return (
    <Stack sx={{ gap: 0.25 }}>
      <Typography variant="caption" color="text.secondary">
        {label}
      </Typography>
      <Typography variant="body1" sx={{ wordBreak: 'break-word' }}>
        {children}
      </Typography>
    </Stack>
  );
}

export default function ApplicationDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { data, error, isLoading, mutate } = useSWR<{ data: JobApplication }>(`/applications/${id}`, fetcher, { revalidateOnFocus: false });
  const [confirmDelete, setConfirmDelete] = useState(false);

  useEffect(() => {
    if (data) refreshInboxCounts();
  }, [data?.data.id]); // eslint-disable-line react-hooks/exhaustive-deps

  if (isLoading) return <Loader />;
  if (error || !data) return <NotFound />;

  const application = data.data;

  const remove = async () => {
    try {
      await api.delete(`/applications/${application.id}`);
      notify.success('Candidatura apagada, com o currículo.');
      refreshInboxCounts();
      navigate('/candidaturas', { replace: true });
    } catch (err) {
      notify.error(errorMessage(err));
    }
  };

  return (
    <Stack sx={{ gap: 3 }}>
      <Stack direction={{ xs: 'column', sm: 'row' }} sx={{ justifyContent: 'space-between', alignItems: { sm: 'center' }, gap: 2 }}>
        <Box sx={{ minWidth: 0 }}>
          <Stack direction="row" sx={{ gap: 1.5, alignItems: 'center', flexWrap: 'wrap' }}>
            <Typography variant="h3">{application.name}</Typography>
            <InboxStatusChip status={application.status} label={application.status_label} />
          </Stack>
          <Typography variant="body2" color="text.secondary">
            {application.position} · recebida em {formatDateTime(application.created_at)}
          </Typography>
        </Box>
        <Stack direction="row" sx={{ gap: 1 }}>
          <Button component={RouterLink} to="/candidaturas" variant="outlined" color="secondary">
            Voltar
          </Button>
          <Button variant="outlined" color="error" onClick={() => setConfirmDelete(true)}>
            Apagar
          </Button>
        </Stack>
      </Stack>

      <Grid container spacing={3}>
        <Grid size={{ xs: 12, lg: 8 }}>
          <MainCard title="Candidatura">
            <Stack sx={{ gap: 3 }}>
              <Info label="Vaga">{application.position}</Info>
              <Grid container spacing={3}>
                <Grid size={{ xs: 12, sm: 8 }}>
                  <Info label="Habilitações literárias">{application.qualifications || '—'}</Info>
                </Grid>
                <Grid size={{ xs: 12, sm: 4 }}>
                  <Info label="Anos de experiência">{application.experience_years ?? '—'}</Info>
                </Grid>
              </Grid>
              <Info label="Observações">
                <Box component="span" sx={{ whiteSpace: 'pre-wrap' }}>
                  {application.message || '—'}
                </Box>
              </Info>
              <Divider />
              <Stack direction="row" sx={{ gap: 1.5, alignItems: 'center', flexWrap: 'wrap' }}>
                <Button
                  variant="contained"
                  startIcon={<DocumentDownload size={18} />}
                  onClick={() => downloadFile(`/applications/${application.id}/cv`, application.cv_name)}
                >
                  Descarregar currículo
                </Button>
                <Typography variant="body2" color="text.secondary">
                  {application.cv_name}
                </Typography>
              </Stack>
            </Stack>
          </MainCard>
        </Grid>

        <Grid size={{ xs: 12, lg: 4 }}>
          <Stack sx={{ gap: 3 }}>
            <MainCard title="Candidato">
              <Stack sx={{ gap: 2 }}>
                <Info label="E-mail">
                  <Link href={`mailto:${application.email}`}>{application.email}</Link>
                </Info>
                <Info label="Telefone">{application.phone}</Info>
                {application.phone_alt && <Info label="Telefone alternativo">{application.phone_alt}</Info>}
                <Info label="Endereço">{application.address || '—'}</Info>
                <Info label="Data de nascimento">{formatDate(application.birth_date ?? null)}</Info>
                <Info label="Sexo">{application.gender || '—'}</Info>
                <Info label={application.document_type || 'Documento de registo'}>{application.document_number || '—'}</Info>
              </Stack>
            </MainCard>
            <StatusNotesCard
              endpoint={`/applications/${application.id}`}
              record={application}
              options={APPLICATION_STATUS_OPTIONS}
              onSaved={async (saved) => {
                await mutate({ data: saved }, { revalidate: false });
                refreshInboxCounts();
              }}
            />
          </Stack>
        </Grid>
      </Grid>

      <ConfirmDialog
        open={confirmDelete}
        title="Apagar esta candidatura?"
        description="O currículo também é apagado. Esta acção não pode ser desfeita."
        confirmLabel="Apagar"
        danger
        onClose={() => setConfirmDelete(false)}
        onConfirm={remove}
      />
    </Stack>
  );
}
