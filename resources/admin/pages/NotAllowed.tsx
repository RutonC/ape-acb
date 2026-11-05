import { Link as RouterLink } from 'react-router-dom';

// material-ui
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// project-imports
import MainCard from 'components/MainCard';

// ==============================|| PAINEL - SEM ACESSO ||============================== //

export default function NotAllowed() {
  return (
    <MainCard>
      <Stack sx={{ gap: 2, alignItems: 'flex-start' }}>
        <Typography variant="h3">Sem acesso</Typography>
        <Typography color="text.secondary">
          O seu perfil não dá acesso a esta área. Se precisar dela, peça a um administrador para a acrescentar ao seu perfil.
        </Typography>
        <Button component={RouterLink} to="/" variant="contained">
          Voltar ao início
        </Button>
      </Stack>
    </MainCard>
  );
}
