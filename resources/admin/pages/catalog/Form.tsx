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
import { CATALOG, CatalogKind, catalogValues } from './config';
import ConfirmDialog from 'components/ConfirmDialog';
import Loader from 'components/Loader';
import MainCard from 'components/MainCard';
import RichTextField from 'components/editor/RichTextField';
import FormPage from 'components/form/FormPage';
import IconSelectField from 'components/form/IconSelectField';
import RepeaterField from 'components/form/RepeaterField';
import SlugField from 'components/form/SlugField';
import SwitchField from 'components/form/SwitchField';
import TextField from 'components/form/TextField';
import { LocaleTabs, TranslatableProvider, TranslatableTextField, useTranslatableField } from 'components/form/translatable';
import MediaPickerField from 'components/media/MediaPickerField';
import NotFound from 'pages/NotFound';
import api, { errorMessage, fetcher } from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';
import { SLUG_PATTERN } from 'utils/slug';

// types
import { CatalogItem, DetailSection } from 'types/catalog';

// ==============================|| SERVIÇOS / PRODUTOS - FORMULÁRIO ||============================== //

/** Secções de detalhe ("O que inclui", "Entregáveis"...), uma lista por idioma. */
function DetailSections() {
  const locale = useTranslatableField('details');

  return (
    <RepeaterField<DetailSection>
      key={locale}
      name={`details.${locale}`}
      label="Secções de detalhe"
      helperText="Ex.: O que inclui, Entregáveis, Condições. Aparecem na página de detalhe, por esta ordem."
      addLabel="Adicionar secção"
      itemLabel={(item) => item.title || 'Nova secção'}
      newItem={() => ({ title: '', icon: null, content: '' })}
      renderItem={(prefix) => (
        <Grid container spacing={2}>
          <Grid size={{ xs: 12, sm: 7 }}>
            <TextField name={`${prefix}.title`} label="Título" required />
          </Grid>
          <Grid size={{ xs: 12, sm: 5 }}>
            <IconSelectField name={`${prefix}.icon`} label="Ícone" />
          </Grid>
          <Grid size={12}>
            <RichTextField name={`${prefix}.content`} label="Texto" />
          </Grid>
        </Grid>
      )}
    />
  );
}

export default function CatalogForm({ kind }: { kind: CatalogKind }) {
  const config = CATALOG[kind];
  const { id } = useParams();
  const isEdit = Boolean(id);
  const navigate = useNavigate();
  const { data, isLoading, error, mutate } = useSWR<{ data: CatalogItem }>(isEdit ? `${config.endpoint}/${id}` : null, fetcher);
  const [confirmDelete, setConfirmDelete] = useState(false);

  if (isEdit && isLoading) return <Loader />;
  if (isEdit && (error || !data)) return <NotFound />;

  const item = data?.data;

  const remove = async () => {
    try {
      await api.delete(`${config.endpoint}/${item!.id}`);
      notify.success(`${item!.name} apagado.`);
      navigate(config.path, { replace: true });
    } catch (err) {
      notify.error(errorMessage(err));
    }
  };

  return (
    <Formik
      enableReinitialize
      initialValues={catalogValues(item)}
      validationSchema={Yup.object({
        title: Yup.object({ pt: Yup.string().trim().max(80).required('Indique o nome em português.') }),
        slug: Yup.string().required('Indique o endereço.').matches(SLUG_PATTERN, 'Use apenas letras minúsculas, números e hífenes.')
      })}
      onSubmit={async (values, helpers) => {
        try {
          if (isEdit) {
            const { data: saved } = await api.put<{ data: CatalogItem }>(`${config.endpoint}/${id}`, values);
            await mutate({ data: saved.data }, { revalidate: false });
            helpers.resetForm({ values: catalogValues(saved.data) });
            notify.success('Guardado.');
          } else {
            const { data: created } = await api.post<{ data: CatalogItem }>(config.endpoint, values);
            helpers.resetForm({ values: catalogValues(created.data) });
            notify.success(`${created.data.name} criado.`);
            navigate(`${config.path}/${created.data.id}`, { replace: true });
          }
        } catch (err) {
          handleFormError(err, helpers);
        }
      }}
    >
      <Form noValidate>
        <TranslatableProvider>
          <FormPage
            title={isEdit ? item!.name : config.newLabel}
            subtitle={isEdit ? `${config.publicPrefix}${item!.slug}` : undefined}
            backTo={config.path}
            viewUrl={item?.is_published ? item.url : null}
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
                    <TranslatableTextField name="title" label="Nome" required />
                    <TranslatableTextField name="tagline" label="Frase de destaque" helperText="Título grande da introdução na página de detalhe." />
                    <TranslatableTextField name="summary" label="Resumo" multiline rows={2} helperText="Aparece nos cartões e listagens (até 200 caracteres)." />
                    <TranslatableTextField name="intro" label="Introdução" multiline rows={4} />
                    <Grid container spacing={3}>
                      <Grid size={{ xs: 12, sm: 6 }}>
                        <TranslatableTextField name="audience" label="Para quem" />
                      </Grid>
                      <Grid size={{ xs: 12, sm: 6 }}>
                        <TranslatableTextField name="timeline" label="Prazo típico" />
                      </Grid>
                    </Grid>
                    <DetailSections />
                  </Stack>
                </MainCard>

                <MainCard title="SEO" sx={{ mt: 3 }}>
                  <Stack sx={{ gap: 3 }}>
                    <TranslatableTextField name="meta_title" label="Título SEO" helperText="Até 70 caracteres. Vazio: usa o nome." />
                    <TranslatableTextField name="meta_description" label="Descrição SEO" multiline rows={2} helperText="Até 160 caracteres. Vazio: usa o resumo." />
                  </Stack>
                </MainCard>
              </Grid>

              <Grid size={{ xs: 12, lg: 4 }}>
                <Stack sx={{ gap: 3 }}>
                  <MainCard title="Publicação">
                    <Stack sx={{ gap: 2.5 }}>
                      <SwitchField name="is_published" label="Visível no site" />
                      <SlugField source="title.pt" prefix={config.publicPrefix} auto={!isEdit} />
                    </Stack>
                  </MainCard>
                  <MainCard title="Site próprio do serviço">
                    <Stack sx={{ gap: 2.5 }}>
                      <TextField name="external_url" label="Endereço" helperText="Opcional. Mostra um botão para o site do serviço." />
                      <TextField name="external_label" label="Texto do botão" helperText="Ex.: Visitar o site da APE" />
                    </Stack>
                  </MainCard>
                  <MainCard title="Imagem e ícone">
                    <Stack sx={{ gap: 2.5 }}>
                      <IconSelectField name="icon" label="Ícone (cartões e menus)" />
                      <MediaPickerField name="image_id" label="Imagem da página de detalhe" helperText="Sem imagem, mostra o ícone sobre o fundo da marca." />
                    </Stack>
                  </MainCard>
                </Stack>
              </Grid>
            </Grid>
          </FormPage>
        </TranslatableProvider>

        <ConfirmDialog
          open={confirmDelete}
          title={`Apagar ${item?.name ?? ''}?`}
          description="A página deixa de existir no site e as ligações para ela deixam de funcionar."
          confirmLabel="Apagar"
          danger
          onClose={() => setConfirmDelete(false)}
          onConfirm={remove}
        />
      </Form>
    </Formik>
  );
}
