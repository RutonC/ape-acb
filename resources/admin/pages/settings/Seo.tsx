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
import TextField from 'components/form/TextField';
import { LocaleTabs, TranslatableProvider, TranslatableTextField, translations } from 'components/form/translatable';
import MediaPickerField from 'components/media/MediaPickerField';
import api, { fetcher } from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';

// types
import { SeoSettings } from 'types/settings';

// ==============================|| DEFINIÇÕES - SEO E ANALYTICS ||============================== //

const values = (settings: SeoSettings) => ({
  ...settings,
  default_title: translations(settings.default_title),
  default_description: translations(settings.default_description),
  ga4_measurement_id: settings.ga4_measurement_id ?? '',
  google_site_verification: settings.google_site_verification ?? ''
});

export default function SeoSettingsPage() {
  const { data, mutate } = useSWR<{ data: SeoSettings }>('/settings/seo', fetcher, { revalidateOnFocus: false });

  if (!data) return <Loader />;

  return (
    <Formik
      enableReinitialize
      initialValues={values(data.data)}
      validationSchema={Yup.object({
        default_title: Yup.object({ pt: Yup.string().trim().max(70, 'Até 70 caracteres.').required('Indique o título em português.') }),
        default_description: Yup.object({
          pt: Yup.string().trim().max(160, 'Até 160 caracteres.').required('Indique a descrição em português.')
        }),
        ga4_measurement_id: Yup.string().matches(/^(G-[A-Z0-9]+)?$/, 'O ID de medição tem o formato G-XXXXXXXXXX.')
      })}
      onSubmit={async (formValues, helpers) => {
        try {
          const { data: saved } = await api.put<{ data: SeoSettings }>('/settings/seo', formValues);
          await mutate({ data: saved.data }, { revalidate: false });
          helpers.resetForm({ values: values(saved.data) });
          notify.success('Definições guardadas.');
        } catch (error) {
          handleFormError(error, helpers);
        }
      }}
    >
      <Form noValidate>
        <TranslatableProvider>
          <FormPage title="SEO e Analytics" subtitle="Valores por omissão e integrações com o Google." backTo="/">
            <Grid container spacing={3}>
              <Grid size={{ xs: 12, lg: 8 }}>
                <MainCard title="SEO por omissão" content={false}>
                  <Box sx={{ px: 3 }}>
                    <LocaleTabs />
                  </Box>
                  <Stack sx={{ gap: 3, p: 3 }}>
                    <TranslatableTextField
                      name="default_title"
                      label="Título (title)"
                      required
                      helperText="Usado nas páginas sem título próprio. Até 70 caracteres."
                    />
                    <TranslatableTextField
                      name="default_description"
                      label="Descrição (meta description)"
                      required
                      multiline
                      rows={3}
                      helperText="Usada nas páginas sem descrição própria. Até 160 caracteres."
                    />
                  </Stack>
                </MainCard>
              </Grid>

              <Grid size={{ xs: 12, lg: 4 }}>
                <Stack sx={{ gap: 3 }}>
                  <MainCard title="Imagem de partilha">
                    <MediaPickerField
                      name="og_image_id"
                      label="Imagem para redes sociais"
                      helperText="Recomendado: 1200 × 630 px. Sem imagem, usa-se a da marca."
                    />
                  </MainCard>
                  <MainCard title="Integrações">
                    <Stack sx={{ gap: 3 }}>
                      <TextField
                        name="ga4_measurement_id"
                        label="Google Analytics 4: ID de medição"
                        placeholder="G-XXXXXXXXXX"
                        helperText="Só é carregado depois de o visitante aceitar os cookies."
                      />
                      <TextField name="google_site_verification" label="Google Search Console: código de verificação" />
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
