import { useState } from 'react';

// material-ui
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import Grid from '@mui/material/Grid2';
import InputAdornment from '@mui/material/InputAdornment';
import OutlinedInput from '@mui/material/OutlinedInput';
import Stack from '@mui/material/Stack';
import Table from '@mui/material/Table';
import TableBody from '@mui/material/TableBody';
import TableCell from '@mui/material/TableCell';
import TableContainer from '@mui/material/TableContainer';
import TableHead from '@mui/material/TableHead';
import TableRow from '@mui/material/TableRow';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';

// third-party
import { Form, Formik } from 'formik';
import useSWR from 'swr';
import * as Yup from 'yup';

// project-imports
import ConfirmDialog from 'components/ConfirmDialog';
import MainCard from 'components/MainCard';
import ReorderDialog from 'components/ReorderDialog';
import IconButton from 'components/@extended/IconButton';
import SlugField from 'components/form/SlugField';
import TextField from 'components/form/TextField';
import api, { errorMessage, fetcher } from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';
import { SLUG_PATTERN } from 'utils/slug';

// types
import { BlogTerm } from 'types/blog';

// assets
import { Add, Edit, ExportSquare, SearchNormal1, Sort, Trash } from 'iconsax-react';

// ==============================|| BLOG - CATEGORIAS / ETIQUETAS ||============================== //

const KINDS = {
  categories: { singular: 'categoria', newLabel: 'Nova categoria', prefix: '/blog/categoria/', reorder: true, empty: 'Ainda não há categorias.' },
  tags: { singular: 'etiqueta', newLabel: 'Nova etiqueta', prefix: '/blog/etiqueta/', reorder: false, empty: 'Ainda não há etiquetas. Também pode criá-las no formulário do artigo.' }
} as const;

type Kind = keyof typeof KINDS;

function TermDialog({ kind, term, onClose, onSaved }: { kind: Kind; term: BlogTerm | null; onClose: () => void; onSaved: () => void }) {
  const config = KINDS[kind];
  const isEdit = Boolean(term);

  return (
    <Dialog open onClose={onClose} maxWidth="sm" fullWidth>
      <Formik
        initialValues={{ name: { pt: term?.name.pt ?? '', en: term?.name.en ?? '' }, slug: term?.slug ?? '' }}
        validationSchema={Yup.object({
          name: Yup.object({ pt: Yup.string().trim().max(80).required('Indique o nome em português.') }),
          slug: Yup.string().required('Indique o endereço.').matches(SLUG_PATTERN, 'Use apenas letras minúsculas, números e hífenes.')
        })}
        onSubmit={async (values, helpers) => {
          try {
            if (isEdit) await api.put(`/blog/${kind}/${term!.id}`, values);
            else await api.post(`/blog/${kind}`, values);
            notify.success('Guardado.');
            onSaved();
          } catch (error) {
            handleFormError(error, helpers);
          }
        }}
      >
        {({ isSubmitting }) => (
          <Form noValidate>
            <DialogTitle>{isEdit ? `Editar ${term!.label}` : config.newLabel}</DialogTitle>
            <DialogContent dividers>
              <Grid container spacing={2.5}>
                <Grid size={{ xs: 12, sm: 6 }}>
                  <TextField name="name.pt" label="Nome (português)" required autoFocus />
                </Grid>
                <Grid size={{ xs: 12, sm: 6 }}>
                  <TextField name="name.en" label="Nome (inglês)" />
                </Grid>
                <Grid size={12}>
                  <SlugField source="name.pt" prefix={config.prefix} auto={!isEdit} />
                </Grid>
              </Grid>
            </DialogContent>
            <DialogActions sx={{ px: 3, py: 2 }}>
              <Button color="secondary" onClick={onClose}>
                Cancelar
              </Button>
              <Button type="submit" variant="contained" disabled={isSubmitting}>
                {isEdit ? 'Guardar' : 'Criar'}
              </Button>
            </DialogActions>
          </Form>
        )}
      </Formik>
    </Dialog>
  );
}

export default function Terms({ kind }: { kind: Kind }) {
  const config = KINDS[kind];
  const { data, mutate } = useSWR<{ data: BlogTerm[] }>(`/blog/${kind}`, fetcher);
  const [search, setSearch] = useState('');
  const [editing, setEditing] = useState<BlogTerm | null | undefined>(undefined);
  const [toDelete, setToDelete] = useState<BlogTerm | null>(null);
  const [reorderOpen, setReorderOpen] = useState(false);

  const term = search.trim().toLowerCase();
  const rows = (data?.data ?? []).filter((item) => !term || item.label.toLowerCase().includes(term) || (item.name.en ?? '').toLowerCase().includes(term));

  const remove = async () => {
    if (!toDelete) return;
    const item = toDelete;
    setToDelete(null);
    try {
      await api.delete(`/blog/${kind}/${item.id}`);
      notify.success('Apagado.');
      mutate();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  return (
    <MainCard content={false}>
      <Stack direction={{ xs: 'column', md: 'row' }} sx={{ gap: 2, p: 2.5, alignItems: { md: 'center' } }}>
        <OutlinedInput
          value={search}
          onChange={(event) => setSearch(event.target.value)}
          placeholder="Pesquisar"
          inputProps={{ 'aria-label': `Pesquisar ${config.singular}s` }}
          startAdornment={
            <InputAdornment position="start">
              <SearchNormal1 size={18} />
            </InputAdornment>
          }
          sx={{ minWidth: { md: 300 } }}
        />
        <Box sx={{ ml: { md: 'auto' }, display: 'flex', gap: 1 }}>
          {config.reorder && (
            <Button variant="outlined" color="secondary" startIcon={<Sort />} onClick={() => setReorderOpen(true)} disabled={(data?.data.length ?? 0) < 2}>
              Ordenar
            </Button>
          )}
          <Button variant="contained" startIcon={<Add />} onClick={() => setEditing(null)}>
            {config.newLabel}
          </Button>
        </Box>
      </Stack>

      <TableContainer>
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Nome</TableCell>
              <TableCell>Endereço</TableCell>
              <TableCell align="center">Artigos</TableCell>
              <TableCell align="right">Acções</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {rows.map((item) => (
              <TableRow key={item.id} hover>
                <TableCell>
                  <Typography variant="subtitle1">{item.label}</Typography>
                  <Typography variant="caption" color={item.name.en ? 'text.secondary' : 'warning.dark'}>
                    {item.name.en ? `EN: ${item.name.en}` : 'Sem nome em inglês'}
                  </Typography>
                </TableCell>
                <TableCell>
                  <Typography variant="body2" sx={{ fontFamily: 'monospace' }}>
                    {config.prefix}
                    {item.slug}
                  </Typography>
                </TableCell>
                <TableCell align="center">{item.posts_count}</TableCell>
                <TableCell align="right">
                  <Tooltip title="Ver no site">
                    <IconButton color="secondary" onClick={() => window.open(item.url, '_blank', 'noopener')} aria-label={`Ver ${item.label} no site`}>
                      <ExportSquare />
                    </IconButton>
                  </Tooltip>
                  <Tooltip title="Editar">
                    <IconButton color="primary" onClick={() => setEditing(item)} aria-label={`Editar ${item.label}`}>
                      <Edit />
                    </IconButton>
                  </Tooltip>
                  <Tooltip title="Apagar">
                    <IconButton color="error" onClick={() => setToDelete(item)} aria-label={`Apagar ${item.label}`}>
                      <Trash />
                    </IconButton>
                  </Tooltip>
                </TableCell>
              </TableRow>
            ))}
            {data && rows.length === 0 && (
              <TableRow>
                <TableCell colSpan={4} align="center" sx={{ py: 5 }}>
                  <Typography color="text.secondary">{term ? 'Nenhum resultado.' : config.empty}</Typography>
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </TableContainer>

      {editing !== undefined && (
        <TermDialog
          kind={kind}
          term={editing}
          onClose={() => setEditing(undefined)}
          onSaved={() => {
            setEditing(undefined);
            mutate();
          }}
        />
      )}

      {config.reorder && (
        <ReorderDialog
          open={reorderOpen}
          title="Ordenar categorias"
          endpoint="/blog/categories/reorder"
          items={(data?.data ?? []).map((item) => ({ id: item.id, label: item.label }))}
          onClose={() => setReorderOpen(false)}
          onSaved={() => mutate()}
        />
      )}

      <ConfirmDialog
        open={toDelete !== null}
        title={`Apagar ${config.singular} «${toDelete?.label ?? ''}»?`}
        description={
          toDelete && toDelete.posts_count > 0
            ? `${toDelete.posts_count === 1 ? '1 artigo fica' : `${toDelete.posts_count} artigos ficam`} sem esta ${config.singular}. Os artigos não são apagados.`
            : 'Os artigos não são afectados.'
        }
        confirmLabel="Apagar"
        danger
        onClose={() => setToDelete(null)}
        onConfirm={remove}
      />
    </MainCard>
  );
}
