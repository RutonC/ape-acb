import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

// material-ui
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Grid from '@mui/material/Grid2';
import Stack from '@mui/material/Stack';

// third-party
import { Form, Formik } from 'formik';
import useSWR from 'swr';
import * as Yup from 'yup';

// project-imports
import { jobValues } from './config';
import ConfirmDialog from 'components/ConfirmDialog';
import Loader from 'components/Loader';
import MainCard from 'components/MainCard';
import DateTimeField from 'components/form/DateTimeField';
import FormPage from 'components/form/FormPage';
import SlugField from 'components/form/SlugField';
import SelectField from 'components/form/SelectField';
import SwitchField from 'components/form/SwitchField';
import TextField from 'components/form/TextField';
import { LocaleTabs, TranslatableProvider, TranslatableRichTextField, TranslatableTextField } from 'components/form/translatable';
import MediaPickerField from 'components/media/MediaPickerField';
import NotFound from 'pages/NotFound';
import api, { errorMessage, fetcher } from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';
import { SLUG_PATTERN } from 'utils/slug';

// types
import { JobCategory, JobOpening } from 'types/inbox';

// ==============================|| VAGAS - FORMULÁRIO ||============================== //

export default function JobForm() {
  const { id } = useParams();
  const isEdit = Boolean(id);
  const navigate = useNavigate();
  const { data, isLoading, error, mutate } = useSWR<{ data: JobOpening }>(isEdit ? `/jobs/${id}` : null, fetcher);
  const { data: categories } = useSWR<{ data: JobCategory[] }>('/job-categories', fetcher);
  const [confirmDelete, setConfirmDelete] = useState(false);

  if (isEdit && isLoading) return <Loader />;
  if (isEdit && (error || !data)) return <NotFound />;

  const job = data?.data;

  const remove = async () => {
    try {
      await api.delete(`/jobs/${job!.id}`);
      notify.success('Vaga apagada.');
      navigate('/vagas', { replace: true });
    } catch (err) {
      notify.error(errorMessage(err));
    }
  };

  return (
    <Formik
      enableReinitialize
      initialValues={jobValues(job)}
      validationSchema={Yup.object({
        title: Yup.object({ pt: Yup.string().trim().max(120).required('Indique o título em português.') }),
        slug: Yup.string().required('Indique o endereço.').matches(SLUG_PATTERN, 'Use apenas letras minúsculas, números e hífenes.')
      })}
      onSubmit={async (values, helpers) => {
        try {
          if (isEdit) {
            const { data: saved } = await api.put<{ data: JobOpening }>(`/jobs/${id}`, values);
            await mutate({ data: saved.data }, { revalidate: false });
            helpers.resetForm({ values: jobValues(saved.data) });
            notify.success('Guardado.');
          } else {
            const { data: created } = await api.post<{ data: JobOpening }>('/jobs', values);
            helpers.resetForm({ values: jobValues(created.data) });
            notify.success('Vaga criada.');
            navigate(`/vagas/${created.data.id}`, { replace: true });
          }
        } catch (err) {
          handleFormError(err, helpers);
        }
      }}
    >
      <Form noValidate>
        <TranslatableProvider>
          <FormPage
            title={isEdit ? job!.title_text : 'Nova vaga'}
            subtitle={isEdit ? `/vagas/${job!.slug}` : undefined}
            backTo="/vagas"
            viewUrl={job?.url ?? null}
            extraActions={
              isEdit && (
                <Button variant="outlined" color="error" onClick={() => setConfirmDelete(true)}>
                  Apagar
                </Button>
              )
            }
          >
            <Grid container spacing={3}>
              <Grid size={{ xs: 12, lg: 8 }}>
                <MainCard content={false}>
                  <Box sx={{ px: 3 }}>
                    <LocaleTabs />
                  </Box>
                  <Stack sx={{ gap: 3, p: 3 }}>
                    <TranslatableTextField name="title" label="Título da vaga" required />
                    <Grid container spacing={3}>
                      <Grid size={{ xs: 12, sm: 6 }}>
                        <TranslatableTextField name="contract_type" label="Tipo de contrato" placeholder="Tempo inteiro, estágio…" />
                      </Grid>
                      <Grid size={{ xs: 12, sm: 6 }}>
                        <TranslatableTextField name="location" label="Local" placeholder="Beira" />
                      </Grid>
                    </Grid>
                    <TranslatableTextField
                      name="summary"
                      label="Resumo"
                      multiline
                      rows={2}
                      helperText="Aparece na lista de vagas (até 400 caracteres)."
                    />
                    <TranslatableRichTextField name="description" label="Descrição da vaga" />
                    <TranslatableRichTextField name="requirements" label="Requisitos" />
                  </Stack>
                </MainCard>
              </Grid>

              <Grid size={{ xs: 12, lg: 4 }}>
                <MainCard title="Publicação">
                  <Stack sx={{ gap: 2.5 }}>
                    <SwitchField name="is_open" label="Vaga aberta" />
                    <DateTimeField name="posted_at" label="Data de publicação" withTime={false} helperText="Vazio: hoje." />
                    <DateTimeField
                      name="closes_at"
                      label="Candidaturas até"
                      withTime={false}
                      helperText="Depois desta data a vaga aparece como encerrada e deixa de aceitar candidaturas."
                    />
                    <SlugField source="title.pt" prefix="/vagas/" auto={!isEdit} />
                  </Stack>
                </MainCard>
                <MainCard title="Detalhes" sx={{ mt: 3 }}>
                  <Stack sx={{ gap: 2.5 }}>
                    <SelectField
                      name="job_category_id"
                      label="Categoria"
                      loading={!categories}
                      placeholder="Sem categoria"
                      options={(categories?.data ?? []).map((category) => ({ value: category.id, label: category.name_text }))}
                    />
                    <TextField name="positions" label="Número de vagas" type="number" helperText="Vazio não aparece no site." />
                    <TextField name="experience" label="Experiência" placeholder="1 a 2 anos" helperText="Vazio não aparece no site." />
                    <TextField name="salary" label="Salário" helperText="Vazio não aparece no site." />
                    <MediaPickerField name="cover_image_id" label="Imagem da vaga" helperText="Larga, por exemplo 1400 × 500 px." />
                  </Stack>
                </MainCard>
              </Grid>
            </Grid>
          </FormPage>
        </TranslatableProvider>

        <ConfirmDialog
          open={confirmDelete}
          title={`Apagar ${job?.title_text ?? ''}?`}
          description={
            job && job.applications_count > 0
              ? `A página da vaga deixa de existir. As ${job.applications_count} candidaturas ficam na Caixa de entrada como candidaturas espontâneas.`
              : 'A página da vaga deixa de existir.'
          }
          confirmLabel="Apagar"
          danger
          onClose={() => setConfirmDelete(false)}
          onConfirm={remove}
        />
      </Form>
    </Formik>
  );
}
