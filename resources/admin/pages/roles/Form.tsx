import { Link as RouterLink, useNavigate, useParams } from 'react-router-dom';

// material-ui
import Button from '@mui/material/Button';
import Checkbox from '@mui/material/Checkbox';
import FormControlLabel from '@mui/material/FormControlLabel';
import Grid from '@mui/material/Grid2';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// third-party
import { Form, Formik } from 'formik';
import useSWR from 'swr';
import * as Yup from 'yup';

// project-imports
import Loader from 'components/Loader';
import MainCard from 'components/MainCard';
import TextField from 'components/form/TextField';
import NotFound from 'pages/NotFound';
import api, { fetcher } from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';

// types
import { PermissionGroup, Role } from 'types/users';

// ==============================|| PERFIS - CRIAR / EDITAR ||============================== //

export default function RoleForm() {
  const { id } = useParams();
  const isEdit = Boolean(id);
  const navigate = useNavigate();
  const { data: role, isLoading, error } = useSWR<{ data: Role }>(isEdit ? `/roles/${id}` : null, fetcher);
  const { data: groups } = useSWR<{ data: PermissionGroup[] }>('/permissions', fetcher);

  if ((isEdit && isLoading) || !groups) return <Loader />;
  if (isEdit && (error || role?.data.is_admin)) return <NotFound />;

  const allKeys = groups.data.flatMap((group) => group.items.map((item) => item.key));

  return (
    <Formik
      initialValues={{ name: role?.data.name ?? '', permissions: role?.data.permissions ?? ([] as string[]) }}
      validationSchema={Yup.object({ name: Yup.string().trim().max(80).required('Indique o nome do perfil.') })}
      onSubmit={async (values, helpers) => {
        try {
          const payload = { ...values, name: values.name.trim() };
          if (isEdit) {
            await api.put(`/roles/${id}`, payload);
            notify.success('Perfil guardado.');
          } else {
            await api.post('/roles', payload);
            notify.success('Perfil criado.');
          }
          navigate('/perfis');
        } catch (error) {
          handleFormError(error, helpers);
        }
      }}
    >
      {({ values, isSubmitting, setFieldValue }) => {
        const toggle = (key: string, checked: boolean) =>
          setFieldValue('permissions', checked ? [...values.permissions, key] : values.permissions.filter((value) => value !== key));

        return (
          <Form noValidate>
            <Stack sx={{ gap: 3 }}>
              <MainCard title={isEdit ? `Editar perfil ${role?.data.name ?? ''}` : 'Novo perfil'}>
                <Stack sx={{ maxWidth: 480 }}>
                  <TextField name="name" label="Nome do perfil" required />
                </Stack>
              </MainCard>

              <MainCard
                title="Áreas do painel"
                subheader="Marque as áreas que este perfil pode ver e gerir (criar, editar e apagar)."
                secondary={
                  <FormControlLabel
                    label="Todas"
                    control={
                      <Checkbox
                        checked={values.permissions.length === allKeys.length}
                        indeterminate={values.permissions.length > 0 && values.permissions.length < allKeys.length}
                        onChange={(event) => setFieldValue('permissions', event.target.checked ? allKeys : [])}
                      />
                    }
                  />
                }
              >
                <Grid container spacing={3}>
                  {groups.data.map((group) => (
                    <Grid key={group.group} size={{ xs: 12, sm: 6, lg: 4 }}>
                      <Typography variant="subtitle1" sx={{ mb: 1 }}>
                        {group.group}
                      </Typography>
                      <Stack>
                        {group.items.map((item) => (
                          <FormControlLabel
                            key={item.key}
                            label={item.label}
                            control={<Checkbox checked={values.permissions.includes(item.key)} onChange={(event) => toggle(item.key, event.target.checked)} />}
                          />
                        ))}
                      </Stack>
                    </Grid>
                  ))}
                </Grid>
              </MainCard>

              <Stack direction="row" sx={{ gap: 1.5, justifyContent: 'flex-end' }}>
                <Button component={RouterLink} to="/perfis" color="secondary">
                  Cancelar
                </Button>
                <Button type="submit" variant="contained" disabled={isSubmitting}>
                  {isEdit ? 'Guardar' : 'Criar perfil'}
                </Button>
              </Stack>
            </Stack>
          </Form>
        );
      }}
    </Formik>
  );
}
