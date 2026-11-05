import { useState } from 'react';

// material-ui
import Alert from '@mui/material/Alert';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import Grid from '@mui/material/Grid2';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import useMediaQuery from '@mui/material/useMediaQuery';

// third-party
import { Form, Formik } from 'formik';
import useSWR from 'swr';
import * as Yup from 'yup';

// project-imports
import { formatBytes } from './format';
import ConfirmDialog from 'components/ConfirmDialog';
import Loader from 'components/Loader';
import TextField from 'components/form/TextField';
import useAuth from 'hooks/useAuth';
import api, { errorMessage, fetcher } from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';

// types
import { MediaItem } from 'types/media';

// assets
import { Copy, DocumentDownload } from 'iconsax-react';

// ==============================|| MÉDIA - DETALHES E EDIÇÃO ||============================== //

interface Props {
  id: number | null;
  onClose: () => void;
  onChanged: () => void;
}

export default function MediaDetailsDialog({ id, onClose, onChanged }: Props) {
  const fullScreen = useMediaQuery((theme) => theme.breakpoints.down('md'));
  const { can } = useAuth();
  const canEdit = can('media');
  const { data, mutate } = useSWR<{ data: MediaItem; usage: string[] }>(id ? `/media/${id}` : null, fetcher);
  const [confirmDelete, setConfirmDelete] = useState(false);

  const item = data?.data;

  const copyUrl = async () => {
    if (!item) return;
    await navigator.clipboard.writeText(new URL(item.url, window.location.origin).toString());
    notify.success('Endereço copiado.');
  };

  const remove = async () => {
    if (!item) return;
    try {
      await api.delete(`/media/${item.id}`);
      notify.success('Ficheiro apagado.');
      setConfirmDelete(false);
      onChanged();
      onClose();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  return (
    <Dialog open={id !== null} onClose={onClose} maxWidth="md" fullWidth fullScreen={fullScreen}>
      <DialogTitle>{item ? item.title || item.file_name : 'Ficheiro'}</DialogTitle>
      {!item ? (
        <DialogContent>
          <Box sx={{ minHeight: 200, position: 'relative' }}>
            <Loader />
          </Box>
        </DialogContent>
      ) : (
        <Formik
          enableReinitialize
          initialValues={{ title: item.title ?? '', alt: item.alt ?? '', caption: item.caption ?? '', description: item.description ?? '' }}
          validationSchema={Yup.object({ title: Yup.string().max(255), alt: Yup.string().max(255) })}
          onSubmit={async (values, helpers) => {
            try {
              await api.put(`/media/${item.id}`, values);
              notify.success('Ficheiro actualizado.');
              mutate();
              onChanged();
            } catch (error) {
              handleFormError(error, helpers);
            }
          }}
        >
          {({ isSubmitting, dirty }) => (
            <Form noValidate>
              <DialogContent dividers>
                <Grid container spacing={3}>
                  <Grid size={{ xs: 12, md: 5 }}>
                    <Stack sx={{ gap: 2 }}>
                      <Box sx={{ bgcolor: 'secondary.lighter', borderRadius: 2, p: 1, textAlign: 'center' }}>
                        {item.preview_url ? (
                          <Box component="img" src={item.preview_url} alt={item.alt ?? ''} sx={{ maxWidth: '100%', maxHeight: 320, objectFit: 'contain' }} />
                        ) : (
                          <Typography sx={{ py: 6 }} color="text.secondary">
                            Pré-visualização indisponível ({item.ext.toUpperCase()})
                          </Typography>
                        )}
                      </Box>
                      <Box component="dl" sx={{ m: 0, display: 'grid', gridTemplateColumns: 'auto 1fr', columnGap: 2, rowGap: 0.5, '& dt': { color: 'text.secondary' }, '& dd': { m: 0, wordBreak: 'break-all' } }}>
                        <dt>Ficheiro</dt>
                        <dd>{item.file_name}</dd>
                        <dt>Tipo</dt>
                        <dd>{item.ext.toUpperCase()}</dd>
                        {item.width && item.height && (
                          <>
                            <dt>Dimensões</dt>
                            <dd>
                              {item.width} × {item.height} px
                            </dd>
                          </>
                        )}
                        <dt>Tamanho</dt>
                        <dd>{formatBytes(item.size)}</dd>
                        {item.created_at && (
                          <>
                            <dt>Carregado</dt>
                            <dd>{new Date(item.created_at).toLocaleString('pt-PT', { dateStyle: 'medium', timeStyle: 'short' })}</dd>
                          </>
                        )}
                      </Box>
                      <Stack direction="row" sx={{ gap: 1, flexWrap: 'wrap' }}>
                        <Button size="small" variant="outlined" startIcon={<Copy size={16} />} onClick={copyUrl}>
                          Copiar endereço
                        </Button>
                        <Button size="small" variant="outlined" startIcon={<DocumentDownload size={16} />} href={item.url} target="_blank" rel="noopener">
                          Abrir original
                        </Button>
                      </Stack>
                    </Stack>
                  </Grid>
                  <Grid size={{ xs: 12, md: 7 }}>
                    <Stack sx={{ gap: 2.5 }}>
                      {item.is_image && (
                        <TextField
                          name="alt"
                          label="Texto alternativo"
                          disabled={!canEdit}
                          helperText="Descreve a imagem para leitores de ecrã e motores de pesquisa (ex.: Equipa da ACB na sede, na Beira)."
                        />
                      )}
                      <TextField name="title" label="Título" disabled={!canEdit} />
                      <TextField name="caption" label="Legenda" multiline minRows={2} disabled={!canEdit} />
                      <TextField name="description" label="Descrição" multiline minRows={2} disabled={!canEdit} />
                      <Box>
                        <Typography variant="subtitle1" sx={{ mb: 0.5 }}>
                          Onde é usado
                        </Typography>
                        {data.usage.length === 0 ? (
                          <Typography variant="body2" color="text.secondary">
                            Não está a ser usado em nenhum conteúdo.
                          </Typography>
                        ) : (
                          <Box component="ul" sx={{ m: 0, pl: 2.5 }}>
                            {data.usage.map((use) => (
                              <li key={use}>
                                <Typography variant="body2">{use}</Typography>
                              </li>
                            ))}
                          </Box>
                        )}
                      </Box>
                    </Stack>
                  </Grid>
                </Grid>
              </DialogContent>
              <DialogActions sx={{ px: 3, py: 2 }}>
                {canEdit && (
                  <Button color="error" onClick={() => setConfirmDelete(true)} sx={{ mr: 'auto' }}>
                    Apagar
                  </Button>
                )}
                <Button color="secondary" onClick={onClose}>
                  Fechar
                </Button>
                {canEdit && (
                  <Button type="submit" variant="contained" disabled={isSubmitting || !dirty}>
                    Guardar
                  </Button>
                )}
              </DialogActions>
            </Form>
          )}
        </Formik>
      )}

      <ConfirmDialog
        open={confirmDelete}
        title="Apagar este ficheiro?"
        description={
          data && data.usage.length > 0 ? (
            <>
              <Alert severity="warning" sx={{ mb: 1 }}>
                Está a ser usado em: {data.usage.join('; ')}. Esses conteúdos ficam sem a imagem.
              </Alert>
              O ficheiro é apagado do servidor e não pode ser recuperado.
            </>
          ) : (
            'O ficheiro é apagado do servidor e não pode ser recuperado.'
          )
        }
        confirmLabel="Apagar"
        danger
        onClose={() => setConfirmDelete(false)}
        onConfirm={remove}
      />
    </Dialog>
  );
}
