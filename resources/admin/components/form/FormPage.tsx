import { ReactNode } from 'react';
import { Link as RouterLink, useBlocker } from 'react-router-dom';

// material-ui
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Paper from '@mui/material/Paper';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// third-party
import { useFormikContext } from 'formik';

// project-imports
import ConfirmDialog from 'components/ConfirmDialog';

// assets
import { ExportSquare } from 'iconsax-react';

// ==============================|| PÁGINA DE EDIÇÃO ||============================== //
// Título, acções (ver no site, apagar), conteúdo e barra fixa com "Guardar".
// Avisa antes de sair com alterações por guardar.

interface Props {
  title: ReactNode;
  subtitle?: ReactNode;
  backTo: string;
  /** Endereço público (só aparece "Ver no site" quando o registo está publicado). */
  viewUrl?: string | null;
  extraActions?: ReactNode;
  submitLabel?: string;
  children: ReactNode;
}

function UnsavedChangesGuard() {
  const { dirty, isSubmitting } = useFormikContext();
  const blocker = useBlocker(({ currentLocation, nextLocation }) => dirty && !isSubmitting && currentLocation.pathname !== nextLocation.pathname);

  return (
    <ConfirmDialog
      open={blocker.state === 'blocked'}
      title="Sair sem guardar?"
      description="Há alterações por guardar nesta página. Se sair agora, perdem-se."
      confirmLabel="Sair sem guardar"
      danger
      onClose={() => blocker.reset?.()}
      onConfirm={() => blocker.proceed?.()}
    />
  );
}

export default function FormPage({ title, subtitle, backTo, viewUrl, extraActions, submitLabel = 'Guardar', children }: Props) {
  const { dirty, isSubmitting } = useFormikContext();

  return (
    <Stack sx={{ gap: 3, pb: 2 }}>
      <Stack direction={{ xs: 'column', sm: 'row' }} sx={{ justifyContent: 'space-between', alignItems: { sm: 'center' }, gap: 2 }}>
        <Box sx={{ minWidth: 0 }}>
          <Typography variant="h3" noWrap>
            {title}
          </Typography>
          {subtitle && (
            <Typography variant="body2" color="text.secondary">
              {subtitle}
            </Typography>
          )}
        </Box>
        <Stack direction="row" sx={{ gap: 1, flexShrink: 0 }}>
          {viewUrl && (
            <Button variant="outlined" color="secondary" href={viewUrl} target="_blank" rel="noopener" endIcon={<ExportSquare size={16} />}>
              Ver no site
            </Button>
          )}
          {extraActions}
        </Stack>
      </Stack>

      {children}

      <Paper
        elevation={0}
        sx={{
          position: 'sticky',
          bottom: 0,
          zIndex: 5,
          border: '1px solid',
          borderColor: 'divider',
          borderRadius: 2,
          px: 2.5,
          py: 1.5,
          display: 'flex',
          alignItems: 'center',
          gap: 1.5,
          flexWrap: 'wrap'
        }}
      >
        <Typography variant="body2" color={dirty ? 'warning.dark' : 'text.secondary'} sx={{ mr: 'auto' }} role="status">
          {dirty ? 'Alterações por guardar' : 'Sem alterações'}
        </Typography>
        <Button component={RouterLink} to={backTo} color="secondary">
          Voltar
        </Button>
        <Button type="submit" variant="contained" disabled={isSubmitting}>
          {isSubmitting ? 'A guardar…' : submitLabel}
        </Button>
      </Paper>

      <UnsavedChangesGuard />
    </Stack>
  );
}
