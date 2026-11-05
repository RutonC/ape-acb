import { Link as RouterLink, useNavigate, useSearchParams } from 'react-router-dom';

// material-ui
import Alert from '@mui/material/Alert';
import Button from '@mui/material/Button';
import Grid from '@mui/material/Grid2';
import Link from '@mui/material/Link';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// third-party
import { Form, Formik } from 'formik';
import * as Yup from 'yup';

// project-imports
import Logo from 'components/logo';
import TextField from 'components/form/TextField';
import AuthWrapper from 'sections/auth/AuthWrapper';
import api, { csrf } from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';
import { passwordSchema } from 'utils/validation';

// ==============================|| NOVA PALAVRA-PASSE (LIGAÇÃO DO E-MAIL) ||============================== //

export default function ResetPassword() {
  const [params] = useSearchParams();
  const navigate = useNavigate();
  const token = params.get('token') ?? '';
  const email = params.get('email') ?? '';

  return (
    <AuthWrapper>
      <Grid container spacing={3}>
        <Grid sx={{ textAlign: 'center' }} size={12}>
          <Logo />
        </Grid>
        <Grid size={12}>
          <Typography variant="h3">Nova palavra-passe</Typography>
        </Grid>
        <Grid size={12}>
          {!token || !email ? (
            <Alert severity="warning">A ligação está incompleta. Abra-a de novo a partir do e-mail ou peça uma nova.</Alert>
          ) : (
            <Formik
              initialValues={{ password: '', password_confirmation: '' }}
              validationSchema={Yup.object({
                password: passwordSchema,
                password_confirmation: Yup.string()
                  .oneOf([Yup.ref('password')], 'As palavras-passe não coincidem.')
                  .required('Repita a palavra-passe.')
              })}
              onSubmit={async (values, helpers) => {
                try {
                  await csrf();
                  await api.post('/auth/reset-password', { token, email, ...values });
                  notify.success('Palavra-passe alterada. Já pode entrar.');
                  navigate('/login', { replace: true });
                } catch (error) {
                  handleFormError(error, helpers, { email: 'password' });
                }
              }}
            >
              {({ isSubmitting }) => (
                <Form noValidate>
                  <Stack sx={{ gap: 3 }}>
                    <Typography color="text.secondary">
                      Conta: <strong>{email}</strong>
                    </Typography>
                    <TextField name="password" label="Nova palavra-passe" type="password" autoComplete="new-password" autoFocus />
                    <TextField name="password_confirmation" label="Repetir a palavra-passe" type="password" autoComplete="new-password" />
                    <Button disableElevation disabled={isSubmitting} fullWidth size="large" type="submit" variant="contained">
                      Guardar palavra-passe
                    </Button>
                  </Stack>
                </Form>
              )}
            </Formik>
          )}
        </Grid>
        <Grid size={12}>
          <Link component={RouterLink} to="/recuperar-palavra-passe" variant="h6">
            Pedir uma nova ligação
          </Link>
        </Grid>
      </Grid>
    </AuthWrapper>
  );
}
