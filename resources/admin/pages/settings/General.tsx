// material-ui
import Box from '@mui/material/Box';
import Grid from '@mui/material/Grid2';
import Stack from '@mui/material/Stack';

// third-party
import { Form, Formik } from 'formik';
import useSWR from 'swr';
import * as Yup from 'yup';

// project-imports
import Loader from 'components/Loader';
import MainCard from 'components/MainCard';
import FormPage from 'components/form/FormPage';
import ListField from 'components/form/ListField';
import TextField from 'components/form/TextField';
import { LocaleTabs, TranslatableProvider, TranslatableTextField, translations } from 'components/form/translatable';
import MediaPickerField from 'components/media/MediaPickerField';
import api, { fetcher } from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';

// types
import { GeneralSettings } from 'types/settings';

// ==============================|| DEFINIÇÕES - SITE E CONTACTOS ||============================== //

const values = (settings: GeneralSettings) => ({
  ...settings,
  tagline: translations(settings.tagline),
  footer_about: translations(settings.footer_about),
  hours: translations(settings.hours),
  phones: settings.phones.length ? settings.phones : [''],
  whatsapp: settings.whatsapp ?? '',
  map_embed_url: settings.map_embed_url ?? '',
  nuit: settings.nuit ?? '',
  commercial_registry: settings.commercial_registry ?? '',
  facebook: settings.facebook ?? '',
  linkedin: settings.linkedin ?? '',
  instagram: settings.instagram ?? ''
});

const url = (message: string) => Yup.string().url(message).nullable();

export default function GeneralSettingsPage() {
  const { data, mutate } = useSWR<{ data: GeneralSettings }>('/settings/general', fetcher, { revalidateOnFocus: false });

  if (!data) return <Loader />;

  return (
    <Formik
      enableReinitialize
      initialValues={values(data.data)}
      validationSchema={Yup.object({
        site_name: Yup.string().trim().required('Indique o nome da empresa.'),
        tagline: Yup.object({ pt: Yup.string().trim().required('Indique o slogan em português.') }),
        footer_about: Yup.object({ pt: Yup.string().trim().required('Indique o texto do rodapé em português.') }),
        address: Yup.string().trim().required('Indique a morada.'),
        address_short: Yup.string().trim().required('Indique a morada curta.'),
        email: Yup.string().email('Indique um e-mail válido.').required('Indique o e-mail.'),
        phones: Yup.array()
          .of(Yup.string().trim().required('Indique o número ou remova a linha.'))
          .min(1, 'Indique pelo menos um telefone.'),
        hours: Yup.object({ pt: Yup.string().trim().required('Indique o horário em português.') }),
        forms_recipient_email: Yup.string().email('Indique um e-mail válido.').required('Indique o e-mail que recebe os formulários.'),
        map_embed_url: url('Indique um endereço completo (https://...).'),
        facebook: url('Indique um endereço completo (https://...).'),
        linkedin: url('Indique um endereço completo (https://...).'),
        instagram: url('Indique um endereço completo (https://...).')
      })}
      onSubmit={async (formValues, helpers) => {
        try {
          const { data: saved } = await api.put<{ data: GeneralSettings }>('/settings/general', formValues);
          await mutate({ data: saved.data }, { revalidate: false });
          helpers.resetForm({ values: values(saved.data) });
          notify.success('Definições guardadas.');
        } catch (error) {
          handleFormError(error, helpers, { nested: true });
        }
      }}
    >
      <Form noValidate>
        <TranslatableProvider>
          <FormPage title="Site e contactos" subtitle="Identidade, contactos, formulários e redes sociais." backTo="/">
            <Grid container spacing={3}>
              <Grid size={{ xs: 12, lg: 7 }}>
                <MainCard title="Identidade" content={false}>
                  <Box sx={{ px: 3 }}>
                    <LocaleTabs />
                  </Box>
                  <Stack sx={{ gap: 3, p: 3 }}>
                    <TextField
                      name="site_name"
                      label="Nome da empresa"
                      required
                      helperText="Aparece no rodapé, no copyright e nos dados estruturados."
                    />
                    <TranslatableTextField name="tagline" label="Slogan" required />
                    <TranslatableTextField name="footer_about" label="Texto de apresentação no rodapé" required multiline rows={4} />
                    <TranslatableTextField name="hours" label="Horário de atendimento" required />
                  </Stack>
                </MainCard>
              </Grid>

              <Grid size={{ xs: 12, lg: 5 }}>
                <MainCard title="Logótipos">
                  <Stack sx={{ gap: 3 }}>
                    <MediaPickerField
                      name="logo_id"
                      label="Logótipo (fundo claro)"
                      helperText="Sem ficheiro, usa-se o logótipo oficial do manual de marca."
                    />
                    <MediaPickerField name="logo_white_id" label="Logótipo branco (fundo escuro)" />
                    <MediaPickerField
                      name="favicon_id"
                      label="Favicon (ícone do separador)"
                      helperText="PNG quadrado (mínimo 192 × 192 px) ou SVG."
                    />
                  </Stack>
                </MainCard>
              </Grid>

              <Grid size={{ xs: 12, lg: 7 }}>
                <MainCard title="Contactos">
                  <Grid container spacing={3}>
                    <Grid size={12}>
                      <TextField name="address" label="Morada completa" required />
                    </Grid>
                    <Grid size={{ xs: 12, sm: 6 }}>
                      <TextField name="address_short" label="Morada curta (cabeçalho)" required />
                    </Grid>
                    <Grid size={{ xs: 12, sm: 6 }}>
                      <TextField name="email" label="E-mail" type="email" required />
                    </Grid>
                    <Grid size={{ xs: 12, sm: 6 }}>
                      <ListField name="phones" label="Telefones" addLabel="Adicionar telefone" max={5} />
                    </Grid>
                    <Grid size={{ xs: 12, sm: 6 }}>
                      <TextField name="whatsapp" label="WhatsApp" helperText="Número do botão flutuante. Vazio esconde o botão." />
                    </Grid>
                    <Grid size={12}>
                      <TextField
                        name="map_embed_url"
                        label="Endereço de incorporação do Google Maps"
                        helperText="Google Maps, Partilhar, Incorporar um mapa, e copiar o endereço do atributo src."
                      />
                    </Grid>
                  </Grid>
                </MainCard>
              </Grid>

              <Grid size={{ xs: 12, lg: 5 }}>
                <Stack sx={{ gap: 3 }}>
                  <MainCard title="Formulários e dados legais">
                    <Stack sx={{ gap: 3 }}>
                      <TextField
                        name="forms_recipient_email"
                        label="E-mail que recebe os formulários"
                        type="email"
                        required
                        helperText="As mensagens de contacto e os pedidos de adesão seguem para aqui e ficam também na Caixa de entrada."
                      />
                      <TextField name="nuit" label="NUIT" />
                      <TextField name="commercial_registry" label="Registo comercial" />
                    </Stack>
                  </MainCard>
                  <MainCard title="Redes sociais">
                    <Stack sx={{ gap: 3 }}>
                      <TextField name="facebook" label="Facebook" placeholder="https://www.facebook.com/..." />
                      <TextField name="linkedin" label="LinkedIn" placeholder="https://www.linkedin.com/company/..." />
                      <TextField name="instagram" label="Instagram" placeholder="https://www.instagram.com/..." />
                    </Stack>
                  </MainCard>
                </Stack>
              </Grid>
            </Grid>
          </FormPage>
        </TranslatableProvider>
      </Form>
    </Formik>
  );
}
