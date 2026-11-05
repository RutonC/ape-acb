import { useNavigate } from 'react-router-dom';

// material-ui
import Grid from '@mui/material/Grid2';
import Link from '@mui/material/Link';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// project-imports
import Logo from 'components/logo';
import useAuth from 'hooks/useAuth';
import AuthWrapper from 'sections/auth/AuthWrapper';
import TwoFactorSettings from 'sections/profile/TwoFactorSettings';

// ==============================|| CONFIGURAÇÃO OBRIGATÓRIA DO 2FA ||============================== //

export default function TwoFactorSetup() {
  const { user, logout, refresh } = useAuth();
  const navigate = useNavigate();

  return (
    <AuthWrapper>
      <Grid container spacing={3}>
        <Grid sx={{ textAlign: 'center' }} size={12}>
          <Logo />
        </Grid>
        <Grid size={12}>
          <Stack sx={{ gap: 1 }}>
            <Typography variant="h3">Proteja a sua conta</Typography>
            <Typography color="text.secondary">
              Olá, {user?.name}. Para usar o painel é obrigatório configurar a verificação em dois passos. Demora um minuto.
            </Typography>
          </Stack>
        </Grid>
        <Grid size={12}>
          <TwoFactorSettings
            onEnabled={async () => {
              await refresh();
              navigate('/', { replace: true });
            }}
          />
        </Grid>
        <Grid size={12}>
          <Link component="button" type="button" variant="h6" color="text.secondary" onClick={() => logout()}>
            Terminar sessão
          </Link>
        </Grid>
      </Grid>
    </AuthWrapper>
  );
}
