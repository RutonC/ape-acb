import { useState } from 'react';
import { Link as RouterLink } from 'react-router-dom';

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

// ==============================|| RECUPERAR PALAVRA-PASSE ||============================== //

export default function ForgotPassword() {
  const [sent, setSent] = useState(false);

  return (
    <AuthWrapper>
      <Grid container spacing={3}>
        <Grid sx={{ textAlign: 'center' }} size={12}>
          <Logo />
        </Grid>
        <Grid size={12}>
          <Stack sx={{ gap: 1 }}>
            <Typography variant="h3">Recuperar a palavra-passe</Typography>
            <Typography color="text.secondary">Indique o e-mail da sua conta. Enviamos uma ligação para criar uma nova palavra-passe.</Typography>
          </Stack>
        </Grid>
        <Grid size={12}>
          {sent ? (
            <Alert severity="success">
              Se existir uma conta com esse e-mail, vai receber a ligação dentro de momentos. Veja também a pasta de spam.
            </Alert>
          ) : (
            <Formik
              initialValues={{ email: '' }}
              validationSchema={Yup.object({ email: Yup.string().trim().email('Indique um e-mail válido.').required('Indique o e-mail.') })}
              onSubmit={async (values, helpers) => {
                try {
                  await csrf();
                  await api.post('/auth/forgot-password', { email: values.email.trim() });
                  setSent(true);
                } catch (error) {
                  handleFormError(error, helpers);
                }
              }}
            >
              {({ isSubmitting }) => (
                <Form noValidate>
                  <Stack sx={{ gap: 3 }}>
                    <TextField name="email" label="E-mail" type="email" autoComplete="email" autoFocus />
                    <Button disableElevation disabled={isSubmitting} fullWidth size="large" type="submit" variant="contained">
                      Enviar ligação
                    </Button>
                  </Stack>
                </Form>
              )}
            </Formik>
          )}
        </Grid>
        <Grid size={12}>
          <Link component={RouterLink} to="/login" variant="h6">
            Voltar ao início de sessão
          </Link>
        </Grid>
      </Grid>
    </AuthWrapper>
  );
}
