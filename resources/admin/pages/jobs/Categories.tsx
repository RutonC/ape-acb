import { useState } from 'react';

// material-ui
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
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
import Loader from 'components/Loader';
import MainCard from 'components/MainCard';
import ReorderDialog from 'components/ReorderDialog';
import IconButton from 'components/@extended/IconButton';
import IconSelectField from 'components/form/IconSelectField';
import SlugField from 'components/form/SlugField';
import { TranslatableProvider, TranslatableTextField, translations } from 'components/form/translatable';
import api, { errorMessage, fetcher } from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';
import { SLUG_PATTERN } from 'utils/slug';

// types
import { JobCategory } from 'types/inbox';

// assets
import { Add, Edit, Sort, Trash } from 'iconsax-react';

// ==============================|| VAGAS - CATEGORIAS ||============================== //

function CategoryDialog({ category, onClose, onSaved }: { category: JobCategory | null; onClose: () => void; onSaved: () => void }) {
  const isEdit = Boolean(category);

  return (
    <Dialog open onClose={onClose} maxWidth="sm" fullWidth>
      <Formik
        initialValues={{ name: translations(category?.name), slug: category?.slug ?? '', icon: category?.icon ?? '' }}
        validationSchema={Yup.object({
          name: Yup.object({ pt: Yup.string().trim().max(80).required('Indique o nome.') }),
          slug: Yup.string().required('Indique o endereço.').matches(SLUG_PATTERN, 'Use apenas letras minúsculas, números e hífenes.')
        })}
        onSubmit={async (values, helpers) => {
          try {
            if (isEdit) {
              await api.put(`/job-categories/${category!.id}`, values);
            } else {
              await api.post('/job-categories', values);
            }
            notify.success('Guardado.');
            onSaved();
          } catch (err) {
            handleFormError(err, helpers);
          }
        }}
      >
        {({ isSubmitting }) => (
          <Form noValidate>
            <TranslatableProvider>
              <DialogTitle>{isEdit ? 'Editar categoria' : 'Nova categoria'}</DialogTitle>
              <DialogContent>
                <Stack sx={{ gap: 2.5, pt: 1 }}>
                  <TranslatableTextField name="name" label="Nome" required />
                  <SlugField source="name.pt" prefix="/vagas?categoria=" auto={!isEdit} />
                  <IconSelectField name="icon" label="Ícone" />
                </Stack>
              </DialogContent>
              <DialogActions>
                <Button color="secondary" onClick={onClose}>
                  Cancelar
                </Button>
                <Button type="submit" variant="contained" disabled={isSubmitting}>
                  Guardar
                </Button>
              </DialogActions>
            </TranslatableProvider>
          </Form>
        )}
      </Formik>
    </Dialog>
  );
}

export default function JobCategories() {
  const { data, isLoading, mutate } = useSWR<{ data: JobCategory[] }>('/job-categories', fetcher);
  const [editing, setEditing] = useState<JobCategory | 'new' | null>(null);
  const [toDelete, setToDelete] = useState<JobCategory | null>(null);
  const [reordering, setReordering] = useState(false);

  const categories = data?.data ?? [];

  const remove = async () => {
    if (!toDelete) return;
    const category = toDelete;
    setToDelete(null);
    try {
      await api.delete(`/job-categories/${category.id}`);
      notify.success('Categoria apagada.');
      mutate();
    } catch (err) {
      notify.error(errorMessage(err));
    }
  };

  if (isLoading) return <Loader />;

  return (
    <MainCard
      title="Categorias de vagas"
      secondary={
        <Stack direction="row" sx={{ gap: 1 }}>
          <Button variant="outlined" color="secondary" startIcon={<Sort size={18} />} onClick={() => setReordering(true)} disabled={categories.length < 2}>
            Ordenar
          </Button>
          <Button variant="contained" startIcon={<Add size={18} />} onClick={() => setEditing('new')}>
            Nova categoria
          </Button>
        </Stack>
      }
      content={false}
    >
      <TableContainer>
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Nome</TableCell>
              <TableCell>Endereço</TableCell>
              <TableCell align="right">Vagas</TableCell>
              <TableCell align="right">Acções</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {categories.length === 0 && (
              <TableRow>
                <TableCell colSpan={4}>
                  <Typography color="text.secondary">Ainda não há categorias.</Typography>
                </TableCell>
              </TableRow>
            )}
            {categories.map((category) => (
              <TableRow key={category.id} hover>
                <TableCell>{category.name_text}</TableCell>
                <TableCell>{category.slug}</TableCell>
                <TableCell align="right">{category.jobs_count}</TableCell>
                <TableCell align="right">
                  <Tooltip title="Editar">
                    <IconButton color="primary" onClick={() => setEditing(category)} aria-label={`Editar ${category.name_text}`}>
                      <Edit />
                    </IconButton>
                  </Tooltip>
                  <Tooltip title="Apagar">
                    <IconButton color="error" onClick={() => setToDelete(category)} aria-label={`Apagar ${category.name_text}`}>
                      <Trash />
                    </IconButton>
                  </Tooltip>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>

      {editing !== null && (
        <CategoryDialog
          category={editing === 'new' ? null : editing}
          onClose={() => setEditing(null)}
          onSaved={() => {
            setEditing(null);
            mutate();
          }}
        />
      )}

      <ConfirmDialog
        open={toDelete !== null}
        title={`Apagar ${toDelete?.name_text ?? ''}?`}
        description="As vagas desta categoria ficam sem categoria."
        confirmLabel="Apagar"
        danger
        onClose={() => setToDelete(null)}
        onConfirm={remove}
      />

      {reordering && (
        <ReorderDialog
          open
          title="Ordenar categorias"
          endpoint="/job-categories/reorder"
          items={categories.map((category) => ({ id: category.id, label: category.name_text }))}
          onClose={() => setReordering(false)}
          onSaved={() => {
            setReordering(false);
            mutate();
          }}
        />
      )}
    </MainCard>
  );
}
