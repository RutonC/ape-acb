import { Link as RouterLink, useNavigate, useParams } from 'react-router-dom';

// material-ui
import Button from '@mui/material/Button';
import Checkbox from '@mui/material/Checkbox';
import FormControlLabel from '@mui/material/FormControlLabel';
import FormGroup from '@mui/material/FormGroup';
import FormHelperText from '@mui/material/FormHelperText';
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
import SwitchField from 'components/form/SwitchField';
import TextField from 'components/form/TextField';
import useAuth from 'hooks/useAuth';
import api, { fetcher } from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';
import { emailSchema } from 'utils/validation';

// types
import { AdminUser, Role } from 'types/users';

// ==============================|| UTILIZADORES - CRIAR / EDITAR ||============================== //

export default function UserForm() {
  const { id } = useParams();
  const isEdit = Boolean(id);
  const navigate = useNavigate();
  const { refresh } = useAuth();
  const { data: user, isLoading } = useSWR<{ data: AdminUser }>(isEdit ? `/users/${id}` : null, fetcher);
  const { data: roles } = useSWR<{ data: Role[] }>('/roles', fetcher);

  if ((isEdit && isLoading) || !roles) return <Loader />;

  const record = user?.data;
  const locked = Boolean(record?.is_self || record?.is_last_admin);

  return (
    <Formik
      initialValues={{
        name: record?.name ?? '',
        email: record?.email ?? '',
        password: '',
        password_confirmation: '',
        roles: record?.roles.map((role) => role.id) ?? ([] as number[]),
        is_active: record?.is_active ?? true
      }}
      validationSchema={Yup.object({
        name: Yup.string().trim().max(120).required('Indique o nome.'),
        email: emailSchema,
        password: isEdit
          ? Yup.string().test('min', 'Use pelo menos 8 caracteres.', (value) => !value || value.length >= 8)
          : Yup.string().min(8, 'Use pelo menos 8 caracteres.').required('Indique a palavra-passe.'),
        password_confirmation: Yup.string().test('match', 'As palavras-passe não coincidem.', function (value) {
          return (value ?? '') === (this.parent.password ?? '');
        }),
        roles: Yup.array().min(1, 'Escolha pelo menos um perfil.')
      })}
      onSubmit={async (values, helpers) => {
        try {
          const payload = { ...values, name: values.name.trim(), email: values.email.trim() };
          if (isEdit) {
            await api.put(`/users/${id}`, payload);
            if (record?.is_self) await refresh();
            notify.success('Utilizador guardado.');
          } else {
            await api.post('/users', payload);
            notify.success('Utilizador criado. Comunique-lhe a palavra-passe por um canal seguro.');
          }
          navigate('/utilizadores');
        } catch (error) {
          handleFormError(error, helpers);
        }
      }}
    >
      {({ values, errors, touched, isSubmitting, setFieldValue }) => (
        <Form noValidate>
          <Grid container spacing={3}>
            <Grid size={{ xs: 12, md: 8 }}>
              <MainCard title={isEdit ? `Editar ${record?.name ?? ''}` : 'Novo utilizador'}>
                <Grid container spacing={3}>
                  <Grid size={{ xs: 12, sm: 6 }}>
                    <TextField name="name" label="Nome" required autoComplete="off" />
                  </Grid>
                  <Grid size={{ xs: 12, sm: 6 }}>
                    <TextField name="email" label="E-mail" type="email" required autoComplete="off" />
                  </Grid>
                  <Grid size={{ xs: 12, sm: 6 }}>
                    <TextField
                      name="password"
                      label={isEdit ? 'Nova palavra-passe' : 'Palavra-passe'}
                      type="password"
                      required={!isEdit}
                      autoComplete="new-password"
                      helperText={isEdit ? 'Deixe em branco para manter a actual.' : 'Pelo menos 8 caracteres.'}
                    />
                  </Grid>
                  <Grid size={{ xs: 12, sm: 6 }}>
                    <TextField name="password_confirmation" label="Repetir a palavra-passe" type="password" required={!isEdit} autoComplete="new-password" />
                  </Grid>
                </Grid>
              </MainCard>
            </Grid>

            <Grid size={{ xs: 12, md: 4 }}>
              <MainCard title="Perfil e estado">
                <Stack sx={{ gap: 2 }}>
                  <Stack>
                    <Typography variant="subtitle1" component="p" id="perfis-label">
                      Perfis
                    </Typography>
                    <Typography variant="caption" color="text.secondary">
                      Administrador: acesso total. Os restantes perfis acedem só às áreas escolhidas em Perfis.
                    </Typography>
                    <FormGroup aria-labelledby="perfis-label" sx={{ mt: 1 }}>
                      {roles.data.map((role) => (
                        <FormControlLabel
                          key={role.id}
                          label={role.name}
                          control={
                            <Checkbox
                              checked={values.roles.includes(role.id)}
                              onChange={(event) =>
                                setFieldValue(
                                  'roles',
                                  event.target.checked ? [...values.roles, role.id] : values.roles.filter((value) => value !== role.id)
                                )
                              }
                            />
                          }
                        />
                      ))}
                    </FormGroup>
                    {touched.roles && errors.roles && <FormHelperText error>{String(errors.roles)}</FormHelperText>}
                  </Stack>
                  <SwitchField
                    name="is_active"
                    label="Pode entrar no painel"
                    disabled={locked}
                    helperText={
                      record?.is_self
                        ? 'Não pode desactivar a sua própria conta.'
                        : record?.is_last_admin
                          ? 'É o último administrador activo.'
                          : 'Desactive em vez de apagar para manter o histórico.'
                    }
                  />
                </Stack>
              </MainCard>
            </Grid>

            <Grid size={12}>
              <Stack direction="row" sx={{ gap: 1.5, justifyContent: 'flex-end' }}>
                <Button component={RouterLink} to="/utilizadores" color="secondary">
                  Cancelar
                </Button>
                <Button type="submit" variant="contained" disabled={isSubmitting}>
                  {isEdit ? 'Guardar' : 'Criar utilizador'}
                </Button>
              </Stack>
            </Grid>
          </Grid>
        </Form>
      )}
    </Formik>
  );
}
