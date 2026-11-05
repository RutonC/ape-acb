import { Link as RouterLink } from 'react-router-dom';

// material-ui
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// project-imports
import MainCard from 'components/MainCard';

// ==============================|| PAINEL - PÁGINA NÃO ENCONTRADA ||============================== //

export default function NotFound() {
  return (
    <MainCard>
      <Stack sx={{ gap: 2, alignItems: 'flex-start' }}>
        <Typography variant="h3">Página não encontrada</Typography>
        <Typography color="text.secondary">Esta secção não existe ou ainda não está disponível no novo painel.</Typography>
        <Button component={RouterLink} to="/" variant="contained">
          Voltar ao início
        </Button>
      </Stack>
    </MainCard>
  );
}
