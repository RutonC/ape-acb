import { useEffect, useState } from 'react';
import { Link as RouterLink, useNavigate, useParams } from 'react-router-dom';

// material-ui
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Grid from '@mui/material/Grid2';
import Link from '@mui/material/Link';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// third-party
import useSWR from 'swr';

// project-imports
import { COMPANY_STATUS_OPTIONS } from './Companies';
import StatusNotesCard from './StatusNotesCard';
import ConfirmDialog from 'components/ConfirmDialog';
import InboxStatusChip from 'components/InboxStatusChip';
import Loader from 'components/Loader';
import MainCard from 'components/MainCard';
import { refreshInboxCounts } from 'hooks/useInboxCounts';
import NotFound from 'pages/NotFound';
import api, { errorMessage, fetcher } from 'utils/axios';
import { formatDateTime } from 'utils/dates';
import notify from 'utils/notify';

// types
import { CompanyRegistration } from 'types/inbox';

// ==============================|| CAIXA DE ENTRADA - EMPRESA INSCRITA ||============================== //

export default function CompanyDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { data, error, isLoading, mutate } = useSWR<{ data: CompanyRegistration }>(`/company-registrations/${id}`, fetcher, { revalidateOnFocus: false });
  const [confirmDelete, setConfirmDelete] = useState(false);

  useEffect(() => {
    if (data) refreshInboxCounts();
  }, [data?.data.id]); // eslint-disable-line react-hooks/exhaustive-deps

  if (isLoading) return <Loader />;
  if (error || !data) return <NotFound />;

  const registration = data.data;

  const remove = async () => {
    try {
      await api.delete(`/company-registrations/${registration.id}`);
      notify.success('Inscrição apagada.');
      refreshInboxCounts();
      navigate('/inscricoes-de-empresas', { replace: true });
    } catch (err) {
      notify.error(errorMessage(err));
    }
  };

  return (
    <Stack sx={{ gap: 3 }}>
      <Stack direction={{ xs: 'column', sm: 'row' }} sx={{ justifyContent: 'space-between', alignItems: { sm: 'center' }, gap: 2 }}>
        <Box sx={{ minWidth: 0 }}>
          <Stack direction="row" sx={{ gap: 1.5, alignItems: 'center', flexWrap: 'wrap' }}>
            <Typography variant="h3">{registration.company_name}</Typography>
            <InboxStatusChip status={registration.status} label={registration.status_label} />
          </Stack>
          <Typography variant="body2" color="text.secondary">
            Recebida em {formatDateTime(registration.created_at)}
          </Typography>
        </Box>
        <Stack direction="row" sx={{ gap: 1 }}>
          <Button component={RouterLink} to="/inscricoes-de-empresas" variant="outlined" color="secondary">
            Voltar
          </Button>
          <Button variant="outlined" color="error" onClick={() => setConfirmDelete(true)}>
            Apagar
          </Button>
        </Stack>
      </Stack>

      <Grid container spacing={3}>
        <Grid size={{ xs: 12, lg: 8 }}>
          <MainCard title="Contacto">
            <Stack sx={{ gap: 2 }}>
              <Typography>
                <strong>Nome:</strong> {registration.name}
              </Typography>
              <Typography>
                <strong>Telefone:</strong> <Link href={`tel:${registration.phone.replace(/\s+/g, '')}`}>{registration.phone}</Link>
              </Typography>
              <Typography>
                <strong>E-mail:</strong> <Link href={`mailto:${registration.email}`}>{registration.email}</Link>
              </Typography>
            </Stack>
          </MainCard>
        </Grid>
        <Grid size={{ xs: 12, lg: 4 }}>
          <StatusNotesCard
            endpoint={`/company-registrations/${registration.id}`}
            record={registration}
            options={COMPANY_STATUS_OPTIONS}
            onSaved={async (saved) => {
              await mutate({ data: saved }, { revalidate: false });
              refreshInboxCounts();
            }}
          />
        </Grid>
      </Grid>

      <ConfirmDialog
        open={confirmDelete}
        title="Apagar esta inscrição?"
        description="Esta acção não pode ser desfeita."
        confirmLabel="Apagar"
        danger
        onClose={() => setConfirmDelete(false)}
        onConfirm={remove}
      />
    </Stack>
  );
}
