import { SyntheticEvent } from 'react';
import { useSearchParams } from 'react-router-dom';

// material-ui
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import Tab from '@mui/material/Tab';
import Tabs from '@mui/material/Tabs';

// third-party
import { Form, Formik } from 'formik';
import * as Yup from 'yup';

// project-imports
import MainCard from 'components/MainCard';
import TextField from 'components/form/TextField';
import useAuth from 'hooks/useAuth';
import TwoFactorSettings from 'sections/profile/TwoFactorSettings';
import api from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';
import { emailSchema, passwordSchema } from 'utils/validation';

// assets
import { Lock, ShieldTick, User } from 'iconsax-react';

// ==============================|| O MEU PERFIL ||============================== //

const TABS = ['dados', 'palavra-passe', 'dois-passos'] as const;

function ProfileForm() {
  const { user, refresh } = useAuth();

  return (
    <Formik
      enableReinitialize
      initialValues={{ name: user?.name ?? '', email: user?.email ?? '' }}
      validationSchema={Yup.object({ name: Yup.string().trim().max(255).required('Indique o nome.'), email: emailSchema })}
      onSubmit={async (values, helpers) => {
        try {
          await api.put('/auth/user/profile-information', { name: values.name.trim(), email: values.email.trim() });
          await refresh();
          notify.success('Dados actualizados.');
        } catch (error) {
          handleFormError(error, helpers);
        }
      }}
    >
      {({ isSubmitting, dirty }) => (
        <Form noValidate>
          <Stack sx={{ gap: 3, maxWidth: 520 }}>
            <TextField name="name" label="Nome" autoComplete="name" />
            <TextField name="email" label="E-mail" type="email" autoComplete="email" helperText="Usado para entrar e para recuperar a palavra-passe." />
            <Box>
              <Button type="submit" variant="contained" disabled={isSubmitting || !dirty}>
                Guardar
              </Button>
            </Box>
          </Stack>
        </Form>
      )}
    </Formik>
  );
}

function PasswordForm() {
  return (
    <Formik
      initialValues={{ current_password: '', password: '', password_confirmation: '' }}
      validationSchema={Yup.object({
        current_password: Yup.string().required('Indique a palavra-passe actual.'),
        password: passwordSchema,
        password_confirmation: Yup.string().oneOf([Yup.ref('password')], 'As palavras-passe não coincidem.').required('Repita a palavra-passe.')
      })}
      onSubmit={async (values, helpers) => {
        try {
          await api.put('/auth/user/password', values);
          helpers.resetForm();
          notify.success('Palavra-passe alterada.');
        } catch (error) {
          handleFormError(error, helpers);
        }
      }}
    >
      {({ isSubmitting }) => (
        <Form noValidate>
          <Stack sx={{ gap: 3, maxWidth: 520 }}>
            <TextField name="current_password" label="Palavra-passe actual" type="password" autoComplete="current-password" />
            <TextField name="password" label="Nova palavra-passe" type="password" autoComplete="new-password" helperText="Pelo menos 8 caracteres." />
            <TextField name="password_confirmation" label="Repetir a nova palavra-passe" type="password" autoComplete="new-password" />
            <Box>
              <Button type="submit" variant="contained" disabled={isSubmitting}>
                Alterar palavra-passe
              </Button>
            </Box>
          </Stack>
        </Form>
      )}
    </Formik>
  );
}

export default function Profile() {
  const [params, setParams] = useSearchParams();
  const tab = TABS.includes(params.get('separador') as (typeof TABS)[number]) ? (params.get('separador') as (typeof TABS)[number]) : 'dados';

  const onChange = (_: SyntheticEvent, value: string) => setParams({ separador: value }, { replace: true });

  return (
    <MainCard title="O meu perfil" content={false}>
      <Box sx={{ borderBottom: 1, borderColor: 'divider', px: 2 }}>
        <Tabs value={tab} onChange={onChange} variant="scrollable" scrollButtons="auto" aria-label="Separadores do perfil">
          <Tab value="dados" label="Dados pessoais" icon={<User />} iconPosition="start" />
          <Tab value="palavra-passe" label="Palavra-passe" icon={<Lock />} iconPosition="start" />
          <Tab value="dois-passos" label="Verificação em dois passos" icon={<ShieldTick />} iconPosition="start" />
        </Tabs>
      </Box>
      <Box sx={{ p: 3 }}>
        {tab === 'dados' && <ProfileForm />}
        {tab === 'palavra-passe' && <PasswordForm />}
        {tab === 'dois-passos' && <TwoFactorSettings />}
      </Box>
    </MainCard>
  );
}
