// material-ui
import Link from '@mui/material/Link';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// ==============================|| MAIN LAYOUT - FOOTER ||============================== //

export default function Footer() {
  return (
    <Stack direction="row" sx={{ justifyContent: 'space-between', alignItems: 'center', p: '24px 16px 0px', mt: 'auto' }}>
      <Typography variant="caption">&copy; {new Date().getFullYear()} APE, Agência Privada de Emprego</Typography>
      <Link href="/" target="_blank" variant="caption" color="text.primary">
        Ver o site
      </Link>
    </Stack>
  );
}
