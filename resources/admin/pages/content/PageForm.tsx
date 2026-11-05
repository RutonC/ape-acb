import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

// material-ui
import Alert from '@mui/material/Alert';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Chip from '@mui/material/Chip';
import Grid from '@mui/material/Grid2';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// third-party
import { Form, Formik, useFormikContext } from 'formik';
import useSWR from 'swr';
import * as Yup from 'yup';

// project-imports
import { PAGE_STATES, pagePayload, pageValues, PageValues } from './pageConfig';
import BlockEditor from 'components/blocks/BlockEditor';
import ConfirmDialog from 'components/ConfirmDialog';
import Loader from 'components/Loader';
import MainCard from 'components/MainCard';
import ButtonsField from 'components/form/ButtonsField';
import FormPage from 'components/form/FormPage';
import SlugField from 'components/form/SlugField';
import SwitchField from 'components/form/SwitchField';
import {
  LocaleTabs,
  TranslatableProvider,
  TranslatableTextField,
  useEditingLocale,
  useTranslatableField
} from 'components/form/translatable';
import MediaPickerField from 'components/media/MediaPickerField';
import NotFound from 'pages/NotFound';
import api, { errorMessage, fetcher } from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';
import { SLUG_PATTERN } from 'utils/slug';

// types
import { BlockDefinition, PageRecord } from 'types/pages';

// assets
import { Eye } from 'iconsax-react';

// ==============================|| PÁGINAS - CONSTRUTOR ||============================== //

const RESERVED = ['admin', 'api', 'en', 'pt', 'img', 'storage', 'build', 'up', 'pre-visualizar', 'sanctum'];

/** Blocos do idioma em edição. Inglês vazio: o site mostra os blocos portugueses. */
function LocaleBlocks({ definitions }: { definitions: BlockDefinition[] }) {
  const locale = useTranslatableField('content');
  const { values, setFieldValue } = useFormikContext<PageValues>();
  const empty = locale !== 'pt' && values.content.en.length === 0;

  return (
    <Stack sx={{ gap: 2 }}>
      {empty && (
        <Alert
          severity="info"
          action={
            values.content.pt.length > 0 && (
              <Button color="inherit" size="small" onClick={() => setFieldValue('content.en', structuredClone(values.content.pt))}>
                Copiar blocos PT
              </Button>
            )
          }
        >
          Sem blocos em inglês, a versão inglesa da página mostra os blocos portugueses. Copie-os para traduzir.
        </Alert>
      )}
      <BlockEditor key={locale} name={`content.${locale}`} definitions={definitions} />
    </Stack>
  );
}

function PreviewButton({ pageId }: { pageId: number }) {
  const locale = useEditingLocale();
  const { dirty } = useFormikContext();

  const preview = async () => {
    const tab = window.open('', '_blank');
    try {
      const { data } = await api.get<{ url: string }>(`/pages/${pageId}/preview?locale=${locale}`);
      if (tab) tab.location.href = data.url;
      if (dirty) notify.info('A pré-visualização mostra a versão guardada. Guarde para ver as alterações recentes.');
    } catch (err) {
      tab?.close();
      notify.error(errorMessage(err));
    }
  };

  return (
    <Button variant="outlined" color="secondary" startIcon={<Eye size={18} />} onClick={preview}>
      Pré-visualizar {locale.toUpperCase()}
    </Button>
  );
}

export default function PageForm() {
  const { id } = useParams();
  const isEdit = Boolean(id);
  const navigate = useNavigate();
  const { data, isLoading, error, mutate } = useSWR<{ data: PageRecord }>(isEdit ? `/pages/${id}` : null, fetcher, {
    revalidateOnFocus: false
  });
  const { data: blocks } = useSWR<{ data: BlockDefinition[] }>('/pages/blocks', fetcher, { revalidateOnFocus: false });
  const [confirmTrash, setConfirmTrash] = useState(false);

  if ((isEdit && isLoading) || !blocks) return <Loader />;
  if (isEdit && (error || !data)) return <NotFound />;

  const page = data?.data;

  const trash = async () => {
    try {
      await api.delete(`/pages/${page!.id}`);
      notify.success('Página enviada para a lixeira.');
      navigate('/paginas', { replace: true });
    } catch (err) {
      notify.error(errorMessage(err));
    }
  };

  const restore = async () => {
    try {
      await api.post(`/pages/${page!.id}/restore`);
      await mutate();
      notify.success('Página restaurada.');
    } catch (err) {
      notify.error(errorMessage(err));
    }
  };

  return (
    <Formik
      key={page?.id ?? 'nova'}
      initialValues={pageValues(page)}
      validationSchema={Yup.object({
        title: Yup.object({ pt: Yup.string().trim().max(160).required('Indique o título em português.') }),
        slug: Yup.string()
          .required('Indique o endereço.')
          .matches(SLUG_PATTERN, 'Use apenas letras minúsculas, números e hífenes (ex.: sobre-nos).')
          .notOneOf(RESERVED, 'Este endereço é usado pelo sistema.')
      })}
      onSubmit={async (values, helpers) => {
        try {
          if (isEdit) {
            const { data: saved } = await api.put<{ data: PageRecord }>(`/pages/${id}`, pagePayload(values));
            await mutate({ data: saved.data }, { revalidate: false });
            helpers.resetForm({ values: pageValues(saved.data) });
            notify.success('Página guardada.');
          } else {
            const { data: created } = await api.post<{ data: PageRecord }>('/pages', pagePayload(values));
            helpers.resetForm({ values: pageValues(created.data) });
            notify.success('Página criada.');
            navigate(`/paginas/${created.data.id}`, { replace: true });
          }
        } catch (err) {
          handleFormError(err, helpers, { nested: true });
        }
      }}
    >
      <Form noValidate>
        <TranslatableProvider>
          <FormPage
            title={isEdit ? page!.name : 'Nova página'}
            subtitle={
              isEdit && (
                <Stack direction="row" component="span" sx={{ gap: 1, alignItems: 'center', flexWrap: 'wrap' }}>
                  <Chip size="small" variant="light" color={PAGE_STATES[page!.state].color} label={PAGE_STATES[page!.state].label} />
                  <span>{page!.is_homepage ? '/' : `/${page!.slug}`}</span>
                  {page!.fixed_route && <span>· conteúdo da página fixa “{page!.fixed_route}”</span>}
                </Stack>
              )
            }
            backTo="/paginas"
            viewUrl={page?.is_visible ? page.url : null}
            extraActions={
              isEdit && (
                <>
                  {!page!.is_visible && page!.state !== 'trashed' && <PreviewButton pageId={page!.id} />}
                  {page!.state === 'trashed' ? (
                    <Button variant="contained" onClick={restore}>
                      Restaurar
                    </Button>
                  ) : (
                    !page!.is_homepage && (
                      <Button variant="outlined" color="error" onClick={() => setConfirmTrash(true)}>
                        Lixeira
                      </Button>
                    )
                  )}
                </>
              )
            }
          >
            {page?.state === 'trashed' && (
              <Alert severity="warning">Esta página está na lixeira e não aparece no site. Restaure-a para a voltar a publicar.</Alert>
            )}
            <Grid container spacing={3}>
              <Grid size={{ xs: 12, xl: 8 }}>
                <MainCard content={false}>
                  <Box sx={{ px: 3 }}>
                    <LocaleTabs />
                  </Box>
                  <Stack sx={{ gap: 3, p: 3 }}>
                    <TranslatableTextField
                      name="title"
                      label="Título"
                      required
                      helperText="Aparece no separador do browser e no banner das páginas sem bloco Destaque."
                    />
                    <LocaleBlocks definitions={blocks.data} />
                  </Stack>
                </MainCard>
              </Grid>

              <Grid size={{ xs: 12, xl: 4 }}>
                <Stack sx={{ gap: 3 }}>
                  <MainCard title="Publicação">
                    <Stack sx={{ gap: 2.5 }}>
                      <ButtonsField
                        name="status"
                        label="Estado"
                        options={[
                          { value: 'draft', label: 'Rascunho' },
                          { value: 'published', label: 'Publicada' }
                        ]}
                      />
                      <SlugField
                        source="title.pt"
                        prefix="/"
                        auto={!isEdit}
                        helperText="Usar o endereço de uma página fixa (ex.: sobre-nos, contactos) dá conteúdo a essa página em vez de criar uma nova."
                      />
                      <SwitchField
                        name="is_homepage"
                        label="Página inicial do site"
                        helperText="Só uma página pode ser a página inicial."
                      />
                    </Stack>
                  </MainCard>
                  <MainCard title="SEO e partilha">
                    <Stack sx={{ gap: 2.5 }}>
                      <Typography variant="body2" color="text.secondary">
                        Os campos SEO seguem o idioma escolhido nos separadores.
                      </Typography>
                      <TranslatableTextField name="meta_title" label="Título SEO" helperText="Até 70 caracteres. Vazio: usa o título." />
                      <TranslatableTextField
                        name="meta_description"
                        label="Descrição SEO"
                        multiline
                        rows={2}
                        helperText="Até 160 caracteres."
                      />
                      <MediaPickerField
                        name="og_image_id"
                        label="Imagem de partilha"
                        helperText="Redes sociais (1200 × 630 px). Vazio: usa a imagem das Definições SEO."
                      />
                    </Stack>
                  </MainCard>
                </Stack>
              </Grid>
            </Grid>
          </FormPage>
        </TranslatableProvider>

        <ConfirmDialog
          open={confirmTrash}
          title={`Enviar «${page?.name ?? ''}» para a lixeira?`}
          description="Deixa de aparecer no site. Pode restaurá-la a partir da lixeira."
          confirmLabel="Enviar para a lixeira"
          danger
          onClose={() => setConfirmTrash(false)}
          onConfirm={trash}
        />
      </Form>
    </Formik>
  );
}
