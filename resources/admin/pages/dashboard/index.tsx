import { Link as RouterLink } from 'react-router-dom';

// material-ui
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Grid from '@mui/material/Grid2';
import List from '@mui/material/List';
import ListItemButton from '@mui/material/ListItemButton';
import ListItemText from '@mui/material/ListItemText';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// third-party
import useSWR from 'swr';

// project-imports
import InboxStatusChip from 'components/InboxStatusChip';
import Loader from 'components/Loader';
import MainCard from 'components/MainCard';
import useAuth from 'hooks/useAuth';
import { fetcher } from 'utils/axios';
import { formatDateTime } from 'utils/dates';

// types
import { DashboardData } from 'types/settings';

// assets
import { TickCircle } from 'iconsax-react';

// ==============================|| PAINEL - INÍCIO ||============================== //

function Counter({ label, count, path }: { label: string; count: number; path: string }) {
  return (
    <MainCard content={false} sx={{ height: '100%' }}>
      <ListItemButton component={RouterLink} to={path} sx={{ p: 2.5, display: 'block', height: '100%' }}>
        <Typography variant="h2">{count}</Typography>
        <Typography variant="body2" color="text.secondary">
          {label}
        </Typography>
      </ListItemButton>
    </MainCard>
  );
}

export default function Dashboard() {
  const { user } = useAuth();
  const { data, error } = useSWR<{ data: DashboardData }>('/dashboard', fetcher);

  const dashboard = data?.data;
  const counters = Object.entries(dashboard?.counts ?? {});

  return (
    <Grid container spacing={3}>
      <Grid size={12}>
        <Typography variant="h3">Olá, {user?.name?.split(' ')[0]}</Typography>
        <Typography color="text.secondary">Resumo do site e da caixa de entrada.</Typography>
      </Grid>

      {!dashboard && !error && (
        <Grid size={12}>
          <Loader />
        </Grid>
      )}

      {error && (
        <Grid size={12}>
          <Typography color="error">Não foi possível carregar o resumo. Recarregue a página.</Typography>
        </Grid>
      )}

      {counters.map(([area, counter]) => (
        <Grid key={area} size={{ xs: 6, md: 3 }}>
          <Counter {...counter} />
        </Grid>
      ))}

      {dashboard?.applications && (
        <Grid size={{ xs: 12, lg: 6 }}>
          <MainCard
            title="Últimas candidaturas"
            secondary={
              <Button component={RouterLink} to="/candidaturas" size="small">
                Ver todas
              </Button>
            }
            content={false}
          >
            {dashboard.applications.length === 0 ? (
              <Typography color="text.secondary" sx={{ p: 2.5 }}>
                Ainda não há candidaturas.
              </Typography>
            ) : (
              <List disablePadding>
                {dashboard.applications.map((application) => (
                  <ListItemButton key={application.id} component={RouterLink} to={`/candidaturas/${application.id}`} divider>
                    <ListItemText
                      primary={
                        <Stack direction="row" sx={{ gap: 1, alignItems: 'center' }}>
                          <Typography variant="subtitle1" sx={{ fontWeight: application.status === 'new' ? 700 : 500 }}>
                            {application.name}
                          </Typography>
                          <InboxStatusChip status={application.status} label={application.status_label} />
                        </Stack>
                      }
                      secondary={`${formatDateTime(application.created_at)} · ${application.position}`}
                      secondaryTypographyProps={{ noWrap: true }}
                    />
                  </ListItemButton>
                ))}
              </List>
            )}
          </MainCard>
        </Grid>
      )}

      {dashboard?.messages && (
        <Grid size={{ xs: 12, lg: 6 }}>
          <MainCard
            title="Últimas mensagens"
            secondary={
              <Button component={RouterLink} to="/mensagens" size="small">
                Ver todas
              </Button>
            }
            content={false}
          >
            {dashboard.messages.length === 0 ? (
              <Typography color="text.secondary" sx={{ p: 2.5 }}>
                Ainda não há mensagens.
              </Typography>
            ) : (
              <List disablePadding>
                {dashboard.messages.map((message) => (
                  <ListItemButton key={message.id} component={RouterLink} to={`/mensagens/${message.id}`} divider>
                    <ListItemText
                      primary={
                        <Stack direction="row" sx={{ gap: 1, alignItems: 'center' }}>
                          <Typography variant="subtitle1" sx={{ fontWeight: message.status === 'new' ? 700 : 500 }}>
                            {message.name}
                          </Typography>
                          <InboxStatusChip status={message.status} label={message.status_label} />
                        </Stack>
                      }
                      secondary={`${formatDateTime(message.created_at)} · ${message.excerpt}`}
                      secondaryTypographyProps={{ noWrap: true }}
                    />
                  </ListItemButton>
                ))}
              </List>
            )}
          </MainCard>
        </Grid>
      )}

      {dashboard?.translations && (
        <Grid size={12}>
          <MainCard title="Traduções em inglês" content={false}>
            {dashboard.translations.length === 0 ? (
              <Stack direction="row" sx={{ gap: 1, alignItems: 'center', p: 2.5 }}>
                <TickCircle size={20} color="var(--mui-palette-success-main, #2e7d32)" />
                <Typography color="text.secondary">Todo o conteúdo tem versão em inglês.</Typography>
              </Stack>
            ) : (
              <>
                <Typography color="text.secondary" sx={{ px: 2.5, pt: 2, pb: 1 }}>
                  {dashboard.translations.length === 1 ? '1 item tem' : `${dashboard.translations.length} itens têm`} texto em português sem
                  versão inglesa. Enquanto faltar, o site em inglês mostra o português.
                </Typography>
                <List disablePadding>
                  {dashboard.translations.map((item, index) => (
                    <ListItemButton key={`${item.path}-${index}`} component={RouterLink} to={item.path} divider>
                      <ListItemText primary={item.title} secondary={`${item.type} · falta: ${item.missing}`} />
                      <Box component="span" sx={{ color: 'primary.main', typography: 'body2' }}>
                        Traduzir
                      </Box>
                    </ListItemButton>
                  ))}
                </List>
              </>
            )}
          </MainCard>
        </Grid>
      )}
    </Grid>
  );
}
