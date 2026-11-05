import { DragEvent, useRef, useState } from 'react';

// material-ui
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import LinearProgress from '@mui/material/LinearProgress';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// project-imports
import { extensionOf, formatBytes, IMAGE_EXTENSIONS } from './format';
import api, { errorMessage } from 'utils/axios';
import notify from 'utils/notify';

// types
import { MediaAccept, MediaItem, UploadLimits } from 'types/media';

// assets
import { DocumentUpload } from 'iconsax-react';

// ==============================|| MÉDIA - CARREGAR FICHEIROS ||============================== //
// Arrastar e largar ou escolher ficheiros. Cada ficheiro vai num pedido próprio, para
// um erro (formato, tamanho, SVG inseguro) não impedir os restantes.

interface Props {
  limits?: UploadLimits;
  accept: MediaAccept;
  onUploaded: (items: MediaItem[]) => void;
}

export default function UploadZone({ limits, accept, onUploaded }: Props) {
  const input = useRef<HTMLInputElement>(null);
  const [dragging, setDragging] = useState(false);
  const [progress, setProgress] = useState<{ current: number; total: number; percent: number } | null>(null);

  const allowed = (limits?.extensions ?? []).filter((ext) => accept === 'all' || IMAGE_EXTENSIONS.includes(ext));

  const upload = async (files: File[]) => {
    const valid = files.filter((file) => {
      if (!allowed.includes(extensionOf(file.name))) {
        notify.error(`"${file.name}": formato não aceite (${allowed.join(', ').toUpperCase()}).`);
        return false;
      }
      if (limits && file.size > limits.max_size) {
        notify.error(`"${file.name}" tem ${formatBytes(file.size)}. O máximo é ${formatBytes(limits.max_size)}.`);
        return false;
      }
      return true;
    });

    const uploaded: MediaItem[] = [];

    for (const [index, file] of valid.entries()) {
      setProgress({ current: index + 1, total: valid.length, percent: 0 });
      const body = new FormData();
      body.append('files[]', file);
      try {
        const { data } = await api.post<{ data: MediaItem[] }>('/media', body, {
          onUploadProgress: (event) => setProgress({ current: index + 1, total: valid.length, percent: event.total ? Math.round((event.loaded / event.total) * 100) : 0 })
        });
        uploaded.push(...data.data);
      } catch (error) {
        notify.error(`"${file.name}": ${errorMessage(error, 'não foi possível carregar.')}`);
      }
    }

    setProgress(null);
    if (uploaded.length > 0) {
      notify.success(uploaded.length === 1 ? 'Ficheiro carregado.' : `${uploaded.length} ficheiros carregados.`);
      onUploaded(uploaded);
    }
  };

  const onDrop = (event: DragEvent) => {
    event.preventDefault();
    setDragging(false);
    if (!progress) upload(Array.from(event.dataTransfer.files));
  };

  return (
    <Box
      onDragOver={(event) => {
        event.preventDefault();
        setDragging(true);
      }}
      onDragLeave={() => setDragging(false)}
      onDrop={onDrop}
      sx={{
        border: '2px dashed',
        borderColor: dragging ? 'primary.main' : 'divider',
        bgcolor: dragging ? 'primary.lighter' : 'secondary.lighter',
        borderRadius: 2,
        p: 2.5,
        transition: 'all .15s'
      }}
    >
      <input
        ref={input}
        type="file"
        multiple
        hidden
        accept={allowed.map((ext) => `.${ext}`).join(',')}
        onChange={(event) => {
          upload(Array.from(event.target.files ?? []));
          event.target.value = '';
        }}
      />
      {progress ? (
        <Stack sx={{ gap: 1 }}>
          <Typography variant="subtitle2">
            A carregar {progress.total > 1 ? `${progress.current} de ${progress.total}` : 'o ficheiro'}… {progress.percent}%
          </Typography>
          <LinearProgress variant="determinate" value={progress.percent} />
        </Stack>
      ) : (
        <Stack direction={{ xs: 'column', sm: 'row' }} sx={{ gap: 2, alignItems: 'center' }}>
          <DocumentUpload size={32} variant="Bulk" />
          <Stack sx={{ flexGrow: 1, textAlign: { xs: 'center', sm: 'left' } }}>
            <Typography variant="subtitle1">Arraste ficheiros para aqui</Typography>
            <Typography variant="caption" color="text.secondary">
              {allowed.map((ext) => ext.toUpperCase()).join(', ')}
              {limits ? `, até ${formatBytes(limits.max_size)} cada` : ''}
            </Typography>
          </Stack>
          <Button variant="contained" onClick={() => input.current?.click()}>
            Escolher ficheiros
          </Button>
        </Stack>
      )}
    </Box>
  );
}
