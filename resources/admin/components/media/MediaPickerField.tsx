import { ReactNode, useState } from 'react';

// material-ui
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import FormHelperText from '@mui/material/FormHelperText';
import Stack from '@mui/material/Stack';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';

// third-party
import { useField } from 'formik';
import useSWR from 'swr';

// project-imports
import MediaLibraryDialog from './MediaLibraryDialog';
import MediaThumb from './MediaThumb';
import IconButton from 'components/@extended/IconButton';
import { fetcher } from 'utils/axios';

// types
import { MediaAccept, MediaItem } from 'types/media';

// assets
import { ArrowLeft2, ArrowRight2, Gallery, Trash } from 'iconsax-react';

// ==============================|| FORMULÁRIO - IMAGEM DA BIBLIOTECA ||============================== //
// Guarda o id do ficheiro (ou uma lista de ids, com `multiple`, ex.: galerias).

interface Props {
  name: string;
  label: ReactNode;
  multiple?: boolean;
  accept?: MediaAccept;
  required?: boolean;
  helperText?: ReactNode;
}

export default function MediaPickerField({ name, label, multiple = false, accept = 'image', required, helperText }: Props) {
  const [field, meta, helpers] = useField<number | null | number[]>(name);
  const [open, setOpen] = useState(false);
  const error = meta.touched && meta.error ? String(meta.error) : undefined;

  const ids = (multiple ? (Array.isArray(field.value) ? field.value : []) : field.value ? [field.value as number] : []).map(Number).filter(Boolean);
  const { data } = useSWR<{ data: MediaItem[] }>(ids.length ? `/media?per_page=100&ids=${ids.join(',')}` : null, fetcher, { keepPreviousData: true });
  const byId = new Map((data?.data ?? []).map((item) => [item.id, item]));

  const set = (next: number[]) => {
    helpers.setTouched(true, false);
    helpers.setValue(multiple ? next : (next[0] ?? null));
  };

  const move = (index: number, delta: number) => {
    const next = [...ids];
    const [moved] = next.splice(index, 1);
    next.splice(index + delta, 0, moved);
    set(next);
  };

  return (
    <Stack sx={{ gap: 1 }}>
      <Typography variant="subtitle1" component="p">
        {label}
        {required && (
          <Box component="span" sx={{ color: 'error.main' }}>
            {' '}
            *
          </Box>
        )}
      </Typography>

      {ids.length > 0 && (
        <Stack direction="row" sx={{ gap: 1.5, flexWrap: 'wrap' }}>
          {ids.map((id, index) => {
            const item = byId.get(id);
            return (
              <Box key={`${id}-${index}`} sx={{ width: 132 }}>
                {item ? (
                  <MediaThumb item={item} />
                ) : (
                  <Box sx={{ width: 1, aspectRatio: '1 / 1', bgcolor: 'secondary.lighter', borderRadius: 1.5, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    <Typography variant="caption" color="text.secondary">
                      {data ? 'Ficheiro apagado' : '…'}
                    </Typography>
                  </Box>
                )}
                <Typography variant="caption" sx={{ display: 'block', mt: 0.5 }} noWrap title={item?.file_name}>
                  {item ? item.title || item.file_name : `#${id}`}
                </Typography>
                <Stack direction="row" sx={{ justifyContent: 'center' }}>
                  {multiple && (
                    <>
                      <Tooltip title="Mover para trás">
                        <span>
                          <IconButton size="small" color="secondary" disabled={index === 0} onClick={() => move(index, -1)} aria-label="Mover para trás">
                            <ArrowLeft2 size={16} />
                          </IconButton>
                        </span>
                      </Tooltip>
                      <Tooltip title="Mover para a frente">
                        <span>
                          <IconButton size="small" color="secondary" disabled={index === ids.length - 1} onClick={() => move(index, 1)} aria-label="Mover para a frente">
                            <ArrowRight2 size={16} />
                          </IconButton>
                        </span>
                      </Tooltip>
                    </>
                  )}
                  <Tooltip title="Retirar">
                    <IconButton size="small" color="error" onClick={() => set(ids.filter((_, i) => i !== index))} aria-label={`Retirar ${item?.file_name ?? 'ficheiro'}`}>
                      <Trash size={16} />
                    </IconButton>
                  </Tooltip>
                </Stack>
              </Box>
            );
          })}
        </Stack>
      )}

      <Box>
        <Button variant="outlined" startIcon={<Gallery size={18} />} onClick={() => setOpen(true)}>
          {multiple ? 'Adicionar da biblioteca' : ids.length ? 'Trocar' : accept === 'image' ? 'Escolher imagem' : 'Escolher ficheiro'}
        </Button>
      </Box>

      {(error || helperText) && <FormHelperText error={Boolean(error)}>{error ?? helperText}</FormHelperText>}

      <MediaLibraryDialog
        open={open}
        multiple={multiple}
        accept={accept}
        onClose={() => setOpen(false)}
        onSelect={(items) => {
          setOpen(false);
          set(multiple ? [...ids, ...items.map((item) => item.id).filter((id) => !ids.includes(id))] : [items[0].id]);
        }}
      />
    </Stack>
  );
}
