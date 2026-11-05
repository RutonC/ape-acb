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
import { ContactMessage } from 'types/inbox';

// ==============================|| CAIXA DE ENTRADA - MENSAGEM ||============================== //
// Abrir uma mensagem nova marca-a como lida (no servidor); o contador do menu actualiza-se a seguir.

const STATUS_OPTIONS = [
  { value: 'new', label: 'Nova' },
  { value: 'read', label: 'Lida' },
  { value: 'replied', label: 'Respondida' }
];

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

export default function MessageDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { data, error, isLoading, mutate } = useSWR<{ data: ContactMessage }>(`/messages/${id}`, fetcher, { revalidateOnFocus: false });
  const [confirmDelete, setConfirmDelete] = useState(false);

  useEffect(() => {
    if (data) refreshInboxCounts();
  }, [data?.data.id]); // eslint-disable-line react-hooks/exhaustive-deps

  if (isLoading) return <Loader />;
  if (error || !data) return <NotFound />;

  const message = data.data;

  const remove = async () => {
    try {
      await api.delete(`/messages/${message.id}`);
      notify.success('Mensagem apagada.');
      refreshInboxCounts();
      navigate('/mensagens', { replace: true });
    } catch (err) {
      notify.error(errorMessage(err));
    }
  };

  const subject = encodeURIComponent('Re: Contacto através do site da APE');

  return (
    <Stack sx={{ gap: 3 }}>
      <Stack direction={{ xs: 'column', sm: 'row' }} sx={{ justifyContent: 'space-between', alignItems: { sm: 'center' }, gap: 2 }}>
        <Box sx={{ minWidth: 0 }}>
          <Stack direction="row" sx={{ gap: 1.5, alignItems: 'center', flexWrap: 'wrap' }}>
            <Typography variant="h3">{message.name}</Typography>
            <InboxStatusChip status={message.status} label={message.status_label} />
          </Stack>
          <Typography variant="body2" color="text.secondary">
            Recebida em {formatDateTime(message.created_at)}
          </Typography>
        </Box>
        <Stack direction="row" sx={{ gap: 1 }}>
          <Button component={RouterLink} to="/mensagens" variant="outlined" color="secondary">
            Voltar
          </Button>
          <Button variant="outlined" color="error" onClick={() => setConfirmDelete(true)}>
            Apagar
          </Button>
        </Stack>
      </Stack>

      <Grid container spacing={3}>
        <Grid size={{ xs: 12, lg: 8 }}>
          <MainCard title="Mensagem">
            <Stack sx={{ gap: 3 }}>
              <Typography variant="body1" sx={{ whiteSpace: 'pre-wrap', wordBreak: 'break-word' }}>
                {message.message}
              </Typography>
              <Divider />
              <Stack direction="row" sx={{ gap: 1.5, flexWrap: 'wrap' }}>
                <Button variant="contained" href={`mailto:${message.email}?subject=${subject}`}>
                  Responder por e-mail
                </Button>
                {message.phone && (
                  <Button variant="outlined" href={`tel:${message.phone.replace(/[^\d+]/g, '')}`}>
                    Ligar
                  </Button>
                )}
                {message.status !== 'replied' && (
                  <Button
                    variant="outlined"
                    color="success"
                    onClick={async () => {
                      try {
                        const { data: saved } = await api.put<{ data: ContactMessage }>(`/messages/${message.id}`, {
                          status: 'replied',
                          notes: message.notes ?? ''
                        });
                        await mutate({ data: saved.data }, { revalidate: false });
                        notify.success('Marcada como respondida.');
                        refreshInboxCounts();
                      } catch (err) {
                        notify.error(errorMessage(err));
                      }
                    }}
                  >
                    Marcar como respondida
                  </Button>
                )}
              </Stack>
            </Stack>
          </MainCard>
        </Grid>

        <Grid size={{ xs: 12, lg: 4 }}>
          <Stack sx={{ gap: 3 }}>
            <MainCard title="Contacto">
              <Stack sx={{ gap: 2 }}>
                <Info label="E-mail">
                  <Link href={`mailto:${message.email}`}>{message.email}</Link>
                </Info>
                {message.phone && <Info label="Telefone">{message.phone}</Info>}
                {message.organization && <Info label="Entidade">{message.organization}</Info>}
                {message.service && <Info label="Assunto">{message.service}</Info>}
                <Info label="Idioma do site">{message.locale === 'en' ? 'Inglês' : 'Português'}</Info>
              </Stack>
            </MainCard>
            <StatusNotesCard
              endpoint={`/messages/${message.id}`}
              record={message}
              options={STATUS_OPTIONS}
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
        title="Apagar esta mensagem?"
        description="Esta acção não pode ser desfeita."
        confirmLabel="Apagar"
        danger
        onClose={() => setConfirmDelete(false)}
        onConfirm={remove}
      />
    </Stack>
  );
}
