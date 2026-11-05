import { useEffect, useState } from 'react';

// material-ui
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import Typography from '@mui/material/Typography';
import useMediaQuery from '@mui/material/useMediaQuery';

// project-imports
import MediaLibrary from './MediaLibrary';

// types
import { MediaAccept, MediaItem } from 'types/media';

// ==============================|| ESCOLHER DA BIBLIOTECA DE MÉDIA ||============================== //

interface Props {
  open: boolean;
  onClose: () => void;
  onSelect: (items: MediaItem[]) => void;
  multiple?: boolean;
  accept?: MediaAccept;
  title?: string;
}

export default function MediaLibraryDialog({ open, onClose, onSelect, multiple = false, accept = 'image', title }: Props) {
  const fullScreen = useMediaQuery((theme) => theme.breakpoints.down('sm'));
  const [picked, setPicked] = useState<MediaItem[]>([]);

  useEffect(() => {
    if (open) setPicked([]);
  }, [open]);

  const toggle = (item: MediaItem) =>
    setPicked((current) =>
      current.some((p) => p.id === item.id) ? current.filter((p) => p.id !== item.id) : multiple ? [...current, item] : [item]
    );

  return (
    <Dialog open={open} onClose={onClose} maxWidth="lg" fullWidth fullScreen={fullScreen}>
      <DialogTitle>{title ?? (accept === 'image' ? (multiple ? 'Escolher imagens' : 'Escolher imagem') : 'Escolher ficheiro')}</DialogTitle>
      <DialogContent dividers>
        <MediaLibrary
          accept={accept}
          selected={picked.map((item) => item.id)}
          onToggle={toggle}
          onUploaded={(items) => setPicked((current) => (multiple ? [...current, ...items] : items.slice(-1)))}
        />
      </DialogContent>
      <DialogActions sx={{ px: 3, py: 2 }}>
        {multiple && picked.length > 0 && (
          <Typography variant="body2" color="text.secondary" sx={{ mr: 'auto' }}>
            {picked.length === 1 ? '1 seleccionado' : `${picked.length} seleccionados`}
          </Typography>
        )}
        <Button color="secondary" onClick={onClose}>
          Cancelar
        </Button>
        <Button variant="contained" disabled={picked.length === 0} onClick={() => onSelect(picked)}>
          {multiple ? 'Usar selecção' : accept === 'image' ? 'Usar imagem' : 'Usar ficheiro'}
        </Button>
      </DialogActions>
    </Dialog>
  );
}
