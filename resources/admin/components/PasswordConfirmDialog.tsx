// material-ui
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// third-party
import { Form, Formik } from 'formik';
import * as Yup from 'yup';

// project-imports
import TextField from 'components/form/TextField';
import api from 'utils/axios';
import { handleFormError } from 'utils/forms';

// ==============================|| CONFIRMAR PALAVRA-PASSE ||============================== //
// Pedido pelo Laravel (resposta 423) antes de acções sensíveis, como mexer no 2FA.

interface Props {
  open: boolean;
  onClose: () => void;
  onConfirmed: () => void;
}

export default function PasswordConfirmDialog({ open, onClose, onConfirmed }: Props) {
  return (
    <Dialog open={open} onClose={onClose} maxWidth="xs" fullWidth>
      <Formik
        initialValues={{ password: '' }}
        validationSchema={Yup.object({ password: Yup.string().required('Indique a palavra-passe.') })}
        onSubmit={async (values, helpers) => {
          try {
            await api.post('/auth/user/confirm-password', values);
            helpers.resetForm();
            onConfirmed();
          } catch (error) {
            handleFormError(error, helpers);
          }
        }}
      >
        {({ isSubmitting }) => (
          <Form noValidate>
            <DialogTitle>Confirmar palavra-passe</DialogTitle>
            <DialogContent>
              <Stack sx={{ gap: 2, pt: 1 }}>
                <Typography color="text.secondary">Por segurança, confirme a sua palavra-passe para continuar.</Typography>
                <TextField name="password" label="Palavra-passe" type="password" autoComplete="current-password" autoFocus />
              </Stack>
            </DialogContent>
            <DialogActions sx={{ px: 3, pb: 2 }}>
              <Button color="secondary" onClick={onClose}>
                Cancelar
              </Button>
              <Button type="submit" variant="contained" disabled={isSubmitting}>
                Confirmar
              </Button>
            </DialogActions>
          </Form>
        )}
      </Formik>
    </Dialog>
  );
}
