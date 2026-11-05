import { useCallback, useEffect, useRef, useState } from 'react';

// material-ui
import Alert from '@mui/material/Alert';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Chip from '@mui/material/Chip';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// third-party
import { Form, Formik } from 'formik';
import * as Yup from 'yup';

// project-imports
import ConfirmDialog from 'components/ConfirmDialog';
import PasswordConfirmDialog from 'components/PasswordConfirmDialog';
import TextField from 'components/form/TextField';
import useAuth from 'hooks/useAuth';
import api, { errorMessage, isStatus } from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';

// ==============================|| VERIFICAÇÃO EM DOIS PASSOS ||============================== //
// Activar (código QR + confirmação), ver e regenerar códigos de recuperação, desactivar.

interface Setup {
  svg: string;
  secret: string;
}

export default function TwoFactorSettings({ onEnabled }: { onEnabled?: () => void }) {
  const { user, refresh } = useAuth();
  const twoFactor = user?.two_factor;
  const [setup, setSetup] = useState<Setup | null>(null);
  const [codes, setCodes] = useState<string[] | null>(null);
  const [busy, setBusy] = useState(false);
  const [confirmDisable, setConfirmDisable] = useState(false);
  const pending = useRef<(() => Promise<void>) | null>(null);
  const [askPassword, setAskPassword] = useState(false);
  // Na configuração obrigatória, o estado só é actualizado depois de guardar os códigos
  // (senão a página passava logo para o painel).
  const [justEnabled, setJustEnabled] = useState(false);
  const enabled = Boolean(twoFactor?.enabled || justEnabled);

  /** Corre a acção; se o Laravel pedir a palavra-passe (423), pede-a e repete. */
  const run = useCallback(async (action: () => Promise<void>) => {
    setBusy(true);
    try {
      await action();
    } catch (error) {
      if (isStatus(error, 423)) {
        pending.current = action;
        setAskPassword(true);
      } else {
        notify.error(errorMessage(error));
      }
    } finally {
      setBusy(false);
    }
  }, []);

  const loadSetup = useCallback(async () => {
    const [qr, key] = await Promise.all([
      api.get<{ svg: string }>('/auth/user/two-factor-qr-code'),
      api.get<{ secretKey: string }>('/auth/user/two-factor-secret-key')
    ]);
    setSetup({ svg: qr.data.svg, secret: key.data.secretKey });
  }, []);

  const loadCodes = useCallback(async () => {
    const { data } = await api.get<string[]>('/auth/user/two-factor-recovery-codes');
    setCodes(data);
  }, []);

  // Activação começada e não confirmada (ex.: recarregou a página): mostra o código QR outra vez.
  useEffect(() => {
    if (twoFactor?.pending && !setup) run(loadSetup);
  }, [twoFactor?.pending, setup, run, loadSetup]);

  const enable = () =>
    run(async () => {
      await api.post('/auth/user/two-factor-authentication');
      await loadSetup();
      await refresh();
    });

  const disable = () =>
    run(async () => {
      await api.delete('/auth/user/two-factor-authentication');
      setCodes(null);
      setSetup(null);
      setJustEnabled(false);
      await refresh();
      notify.success('Verificação em dois passos desactivada.');
    });

  const regenerate = () =>
    run(async () => {
      await api.post('/auth/user/two-factor-recovery-codes');
      await loadCodes();
      notify.success('Novos códigos gerados. Os anteriores deixaram de funcionar.');
    });

  const copyCodes = async () => {
    if (!codes) return;
    await navigator.clipboard.writeText(codes.join('\n'));
    notify.success('Códigos copiados.');
  };

  const downloadCodes = () => {
    if (!codes) return;
    const blob = new Blob([`Códigos de recuperação do painel APE (${user?.email})\n\n${codes.join('\n')}\n`], { type: 'text/plain' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'ape-codigos-de-recuperacao.txt';
    link.click();
    URL.revokeObjectURL(link.href);
  };

  if (!twoFactor) return null;

  return (
    <Stack sx={{ gap: 3 }}>
      <Stack direction="row" sx={{ gap: 1.5, alignItems: 'center', flexWrap: 'wrap' }}>
        <Typography variant="h5">Estado</Typography>
        {enabled ? (
          <Chip color="success" label="Activa" size="small" />
        ) : (
          <Chip color={twoFactor.required ? 'warning' : 'default'} label={twoFactor.required ? 'Obrigatória, por configurar' : 'Desactivada'} size="small" />
        )}
      </Stack>

      <Typography color="text.secondary">
        Além da palavra-passe, a entrada no painel passa a pedir um código de 6 dígitos gerado por uma aplicação no telemóvel (Google
        Authenticator, Microsoft Authenticator, 1Password ou semelhante).
      </Typography>

      {!enabled && !setup && (
        <Box>
          <Button variant="contained" onClick={enable} disabled={busy}>
            Activar verificação em dois passos
          </Button>
        </Box>
      )}

      {!enabled && setup && (
        <Stack sx={{ gap: 2 }}>
          <Typography>
            <strong>1.</strong> Na aplicação de autenticação, leia este código QR:
          </Typography>
          <Box
            sx={{ bgcolor: '#fff', p: 2, borderRadius: 2, width: 'fit-content', border: '1px solid', borderColor: 'divider' }}
            aria-label="Código QR para a aplicação de autenticação"
            role="img"
            dangerouslySetInnerHTML={{ __html: setup.svg }}
          />
          <Typography variant="body2" color="text.secondary">
            Não consegue ler? Introduza esta chave na aplicação:{' '}
            <Box component="code" sx={{ fontWeight: 600, letterSpacing: 1, wordBreak: 'break-all' }}>
              {setup.secret}
            </Box>
          </Typography>
          <Typography>
            <strong>2.</strong> Indique o código de 6 dígitos que a aplicação mostra:
          </Typography>
          <Formik
            initialValues={{ code: '' }}
            validationSchema={Yup.object({ code: Yup.string().trim().required('Indique o código.') })}
            onSubmit={async (values, helpers) => {
              try {
                await api.post('/auth/user/confirmed-two-factor-authentication', { code: values.code.replace(/\s/g, '') });
                await loadCodes();
                setSetup(null);
                setJustEnabled(true);
                if (!onEnabled) await refresh();
                notify.success('Verificação em dois passos activa.');
              } catch (error) {
                handleFormError(error, helpers);
              }
            }}
          >
            {({ isSubmitting }) => (
              <Form noValidate>
                <Stack direction={{ xs: 'column', sm: 'row' }} sx={{ gap: 2, alignItems: { sm: 'flex-start' } }}>
                  <Box sx={{ maxWidth: 220 }}>
                    <TextField name="code" label="Código" autoComplete="one-time-code" inputProps={{ inputMode: 'numeric', maxLength: 7 }} />
                  </Box>
                  <Button type="submit" variant="contained" disabled={isSubmitting} sx={{ mt: { sm: 3.75 } }}>
                    Confirmar
                  </Button>
                </Stack>
              </Form>
            )}
          </Formik>
        </Stack>
      )}

      {codes && (
        <Alert severity="warning" sx={{ '& .MuiAlert-message': { width: 1 } }}>
          <Stack sx={{ gap: 1.5 }}>
            <Typography sx={{ fontWeight: 600 }}>Guarde estes códigos de recuperação num local seguro.</Typography>
            <Typography variant="body2">
              Cada código serve uma vez e permite entrar se perder o telemóvel. Não voltam a ser mostrados sem confirmar a palavra-passe.
            </Typography>
            <Box
              component="ul"
              sx={{ m: 0, p: 2, listStyle: 'none', display: 'grid', gridTemplateColumns: { xs: '1fr', sm: '1fr 1fr' }, gap: 1, bgcolor: 'background.paper', borderRadius: 1, fontFamily: 'monospace' }}
            >
              {codes.map((code) => (
                <li key={code}>{code}</li>
              ))}
            </Box>
            <Stack direction="row" sx={{ gap: 1, flexWrap: 'wrap' }}>
              <Button size="small" variant="outlined" onClick={copyCodes}>
                Copiar
              </Button>
              <Button size="small" variant="outlined" onClick={downloadCodes}>
                Descarregar
              </Button>
              {onEnabled && justEnabled && (
                <Button size="small" variant="contained" onClick={onEnabled}>
                  Já guardei, continuar
                </Button>
              )}
            </Stack>
          </Stack>
        </Alert>
      )}

      {enabled && !onEnabled && (
        <Stack direction="row" sx={{ gap: 1.5, flexWrap: 'wrap' }}>
          {!codes && (
            <Button variant="outlined" onClick={() => run(loadCodes)} disabled={busy}>
              Ver códigos de recuperação
            </Button>
          )}
          <Button variant="outlined" onClick={regenerate} disabled={busy}>
            Gerar novos códigos
          </Button>
          {!twoFactor.required && (
            <Button variant="outlined" color="error" onClick={() => setConfirmDisable(true)} disabled={busy}>
              Desactivar
            </Button>
          )}
        </Stack>
      )}

      {enabled && twoFactor.required && !onEnabled && (
        <Typography variant="body2" color="text.secondary">
          A verificação em dois passos é obrigatória neste painel e não pode ser desactivada. Se perdeu o telemóvel, use um código de
          recuperação ou peça a um administrador para a repor.
        </Typography>
      )}

      <ConfirmDialog
        open={confirmDisable}
        title="Desactivar a verificação em dois passos?"
        description="A entrada no painel volta a pedir só a palavra-passe."
        confirmLabel="Desactivar"
        danger
        onClose={() => setConfirmDisable(false)}
        onConfirm={() => {
          setConfirmDisable(false);
          disable();
        }}
      />

      <PasswordConfirmDialog
        open={askPassword}
        onClose={() => {
          setAskPassword(false);
          pending.current = null;
        }}
        onConfirmed={() => {
          setAskPassword(false);
          const action = pending.current;
          pending.current = null;
          if (action) run(action);
        }}
      />
    </Stack>
  );
}
