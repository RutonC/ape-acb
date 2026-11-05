// material-ui
import Box from '@mui/material/Box';
import ButtonBase from '@mui/material/ButtonBase';
import Dialog from '@mui/material/Dialog';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import Grid from '@mui/material/Grid2';
import Typography from '@mui/material/Typography';

// project-imports
import { blockIcon } from './icons';

// types
import { BlockDefinition } from 'types/pages';

// ==============================|| BLOCOS - ESCOLHER O TIPO ||============================== //

interface Props {
  open: boolean;
  definitions: BlockDefinition[];
  onClose: () => void;
  onPick: (definition: BlockDefinition) => void;
}

export default function BlockPicker({ open, definitions, onClose, onPick }: Props) {
  return (
    <Dialog open={open} onClose={onClose} maxWidth="md" fullWidth>
      <DialogTitle>Adicionar bloco</DialogTitle>
      <DialogContent dividers>
        <Grid container spacing={1.5}>
          {definitions.map((definition) => {
            const Icon = blockIcon(definition.type);
            return (
              <Grid key={definition.type} size={{ xs: 12, sm: 6 }}>
                <ButtonBase
                  onClick={() => onPick(definition)}
                  sx={{
                    width: 1,
                    textAlign: 'left',
                    justifyContent: 'flex-start',
                    alignItems: 'flex-start',
                    gap: 1.5,
                    p: 1.5,
                    border: '1px solid',
                    borderColor: 'divider',
                    borderRadius: 1.5,
                    '&:hover, &:focus-visible': { borderColor: 'primary.main', bgcolor: 'primary.lighter' }
                  }}
                >
                  <Box sx={{ color: 'primary.main', flexShrink: 0, mt: 0.25 }}>
                    <Icon size={24} variant="Bulk" />
                  </Box>
                  <Box>
                    <Typography variant="subtitle1">
                      {definition.label}
                      {!definition.active && (
                        <Typography component="span" variant="caption" color="text.secondary">
                          {' '}
                          (começa oculto)
                        </Typography>
                      )}
                    </Typography>
                    <Typography variant="caption" color="text.secondary">
                      {definition.description}
                    </Typography>
                  </Box>
                </ButtonBase>
              </Grid>
            );
          })}
        </Grid>
      </DialogContent>
    </Dialog>
  );
}
