import { useState } from 'react';
import { Link as RouterLink, useLocation, useNavigate } from 'react-router-dom';

// material-ui
import Button from '@mui/material/Button';
import Checkbox from '@mui/material/Checkbox';
import FormControlLabel from '@mui/material/FormControlLabel';
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
import useAuth from 'hooks/useAuth';
import AuthWrapper from 'sections/auth/AuthWrapper';
import { handleFormError } from 'utils/forms';

// ==============================|| ENTRAR NO PAINEL ||============================== //

export default function Login() {
  const { login, twoFactorChallenge } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [step, setStep] = useState<'credentials' | 'two-factor'>('credentials');
  const [useRecovery, setUseRecovery] = useState(false);
  const [remember, setRemember] = useState(false);

  const goToPanel = () => navigate((location.state as { from?: string } | null)?.from ?? '/', { replace: true });

  return (
    <AuthWrapper>
      <Grid container spacing={3}>
        <Grid sx={{ textAlign: 'center' }} size={12}>
          <Logo />
        </Grid>

        {step === 'credentials' ? (
          <>
            <Grid size={12}>
              <Typography variant="h3">Entrar no painel</Typography>
            </Grid>
            <Grid size={12}>
              <Formik
                initialValues={{ email: '', password: '' }}
                validationSchema={Yup.object({
                  email: Yup.string().trim().email('Indique um e-mail válido.').required('Indique o e-mail.'),
                  password: Yup.string().required('Indique a palavra-passe.')
                })}
                onSubmit={async (values, helpers) => {
                  try {
                    const result = await login(values.email.trim(), values.password, remember);
                    if (result === 'two-factor') {
                      setStep('two-factor');
                    } else {
                      goToPanel();
                    }
                  } catch (error) {
                    handleFormError(error, helpers);
                  }
                }}
              >
                {({ isSubmitting }) => (
                  <Form noValidate>
                    <Stack sx={{ gap: 3 }}>
                      <TextField name="email" label="E-mail" type="email" autoComplete="username" autoFocus />
                      <TextField name="password" label="Palavra-passe" type="password" autoComplete="current-password" />
                      <Stack direction="row" sx={{ justifyContent: 'space-between', alignItems: 'center', mt: -1.5, gap: 2 }}>
                        <FormControlLabel
                          control={<Checkbox checked={remember} onChange={(event) => setRemember(event.target.checked)} size="small" />}
                          label={<Typography variant="h6">Manter sessão iniciada</Typography>}
                        />
                        <Link variant="h6" component={RouterLink} to="/recuperar-palavra-passe" color="text.primary">
                          Esqueceu a palavra-passe?
                        </Link>
                      </Stack>
                      <Button disableElevation disabled={isSubmitting} fullWidth size="large" type="submit" variant="contained">
                        Entrar
                      </Button>
                    </Stack>
                  </Form>
                )}
              </Formik>
            </Grid>
          </>
        ) : (
          <>
            <Grid size={12}>
              <Stack sx={{ gap: 1 }}>
                <Typography variant="h3">Verificação em dois passos</Typography>
                <Typography color="text.secondary">
                  {useRecovery
                    ? 'Indique um dos códigos de recuperação que guardou quando configurou a verificação.'
                    : 'Abra a aplicação de autenticação no telemóvel e indique o código de 6 dígitos.'}
                </Typography>
              </Stack>
            </Grid>
            <Grid size={12}>
              <Formik
                key={useRecovery ? 'recovery' : 'code'}
                initialValues={{ code: '' }}
                validationSchema={Yup.object({ code: Yup.string().trim().required('Indique o código.') })}
                onSubmit={async (values, helpers) => {
                  try {
                    const code = values.code.trim();
                    await twoFactorChallenge(useRecovery ? { recovery_code: code } : { code: code.replace(/\s/g, '') });
                    goToPanel();
                  } catch (error) {
                    handleFormError(error, helpers, { recovery_code: 'code' });
                  }
                }}
              >
                {({ isSubmitting }) => (
                  <Form noValidate>
                    <Stack sx={{ gap: 3 }}>
                      <TextField
                        name="code"
                        label={useRecovery ? 'Código de recuperação' : 'Código de verificação'}
                        autoComplete="one-time-code"
                        inputProps={useRecovery ? undefined : { inputMode: 'numeric', maxLength: 7 }}
                        autoFocus
                      />
                      <Button disableElevation disabled={isSubmitting} fullWidth size="large" type="submit" variant="contained">
                        Confirmar
                      </Button>
                      <Stack direction="row" sx={{ justifyContent: 'space-between', gap: 2 }}>
                        <Link component="button" type="button" variant="h6" onClick={() => setUseRecovery((value) => !value)}>
                          {useRecovery ? 'Usar o código da aplicação' : 'Usar um código de recuperação'}
                        </Link>
                        <Link component="button" type="button" variant="h6" color="text.secondary" onClick={() => setStep('credentials')}>
                          Voltar
                        </Link>
                      </Stack>
                    </Stack>
                  </Form>
                )}
              </Formik>
            </Grid>
          </>
        )}
      </Grid>
    </AuthWrapper>
  );
}
