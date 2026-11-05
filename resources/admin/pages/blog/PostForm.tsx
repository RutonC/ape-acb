import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

// material-ui
import Alert from '@mui/material/Alert';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Chip from '@mui/material/Chip';
import FormHelperText from '@mui/material/FormHelperText';
import Grid from '@mui/material/Grid2';
import Stack from '@mui/material/Stack';
import ToggleButton from '@mui/material/ToggleButton';
import ToggleButtonGroup from '@mui/material/ToggleButtonGroup';
import Typography from '@mui/material/Typography';

// third-party
import { Form, Formik, useField, useFormikContext } from 'formik';
import useSWR from 'swr';
import * as Yup from 'yup';

// project-imports
import { formatDateTime, postValues, STATES } from './config';
import ConfirmDialog from 'components/ConfirmDialog';
import Loader from 'components/Loader';
import MainCard from 'components/MainCard';
import CreatableMultiSelect, { CreatableOption } from 'components/form/CreatableMultiSelect';
import DateTimeField from 'components/form/DateTimeField';
import FormPage from 'components/form/FormPage';
import SelectField, { Option } from 'components/form/SelectField';
import SlugField from 'components/form/SlugField';
import { LocaleTabs, TranslatableProvider, TranslatableRichTextField, TranslatableTextField, useEditingLocale } from 'components/form/translatable';
import MediaPickerField from 'components/media/MediaPickerField';
import useAuth from 'hooks/useAuth';
import NotFound from 'pages/NotFound';
import api, { errorMessage, fetcher } from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';
import { SLUG_PATTERN } from 'utils/slug';

// types
import { BlogTerm, Post } from 'types/blog';

// assets
import { Eye } from 'iconsax-react';

// ==============================|| BLOG - ARTIGO ||============================== //

function StatusField() {
  const [field, , helpers] = useField<Post['status']>('status');
  const { values } = useFormikContext<ReturnType<typeof postValues>>();
  const scheduled = field.value === 'published' && values.published_at && new Date(values.published_at) > new Date();

  return (
    <Stack sx={{ gap: 1 }}>
      <Typography variant="subtitle1" component="p" id="estado-label">
        Estado
      </Typography>
      <ToggleButtonGroup exclusive size="small" value={field.value} onChange={(_, value) => value && helpers.setValue(value)} aria-labelledby="estado-label">
        <ToggleButton value="draft">Rascunho</ToggleButton>
        <ToggleButton value="published" color="success">
          Publicado
        </ToggleButton>
      </ToggleButtonGroup>
      {scheduled && <FormHelperText sx={{ color: 'warning.dark' }}>Agendado: aparece no site em {formatDateTime(values.published_at)}.</FormHelperText>}
    </Stack>
  );
}

/** Pré-visualiza a versão guardada, no idioma que está a ser editado. */
function PreviewButton({ postId }: { postId: number }) {
  const locale = useEditingLocale();
  const { dirty } = useFormikContext();

  const preview = async () => {
    const tab = window.open('', '_blank');
    try {
      const { data } = await api.get<{ url: string }>(`/posts/${postId}/preview?locale=${locale}`);
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

export default function PostForm() {
  const { id } = useParams();
  const isEdit = Boolean(id);
  const navigate = useNavigate();
  const { user } = useAuth();
  const { data, isLoading, error, mutate } = useSWR<{ data: Post }>(isEdit ? `/posts/${id}` : null, fetcher);
  const { data: categories } = useSWR<{ data: BlogTerm[] }>('/blog/categories', fetcher);
  const { data: tags, mutate: reloadTags } = useSWR<{ data: BlogTerm[] }>('/blog/tags', fetcher);
  const { data: authors } = useSWR<{ data: Option[] }>('/options/authors', fetcher);
  const [confirmTrash, setConfirmTrash] = useState(false);

  if (isEdit && isLoading) return <Loader />;
  if (isEdit && (error || !data)) return <NotFound />;

  const post = data?.data;

  const createTag = async (label: string): Promise<CreatableOption> => {
    const { data: created } = await api.post<{ data: BlogTerm }>('/blog/tags', { name: { pt: label } });
    await reloadTags();
    return { value: created.data.id, label: created.data.label };
  };

  const trash = async () => {
    try {
      await api.delete(`/posts/${post!.id}`);
      notify.success('Artigo enviado para a lixeira.');
      navigate('/blog/artigos', { replace: true });
    } catch (err) {
      notify.error(errorMessage(err));
    }
  };

  const restore = async () => {
    try {
      await api.post(`/posts/${post!.id}/restore`);
      await mutate();
      notify.success('Artigo restaurado.');
    } catch (err) {
      notify.error(errorMessage(err));
    }
  };

  return (
    <Formik
      enableReinitialize
      initialValues={postValues(post, user?.id)}
      validationSchema={Yup.object({
        title: Yup.object({ pt: Yup.string().trim().max(160).required('Indique o título em português.') }),
        content: Yup.object({
          pt: Yup.string().test('texto', 'Escreva o texto em português.', (value) => Boolean(value && value.replace(/<[^>]*>/g, '').trim()))
        }),
        slug: Yup.string()
          .required('Indique o endereço.')
          .matches(SLUG_PATTERN, 'Use apenas letras minúsculas, números e hífenes.')
          .notOneOf(['categoria', 'etiqueta'], 'Este endereço está reservado pelo blog.')
      })}
      onSubmit={async (values, helpers) => {
        try {
          if (isEdit) {
            const { data: saved } = await api.put<{ data: Post }>(`/posts/${id}`, values);
            await mutate({ data: saved.data }, { revalidate: false });
            helpers.resetForm({ values: postValues(saved.data) });
            notify.success(saved.data.state === 'scheduled' ? `Guardado. Publica-se em ${formatDateTime(saved.data.published_at)}.` : 'Guardado.');
          } else {
            const { data: created } = await api.post<{ data: Post }>('/posts', values);
            helpers.resetForm({ values: postValues(created.data) });
            notify.success('Artigo criado.');
            navigate(`/blog/artigos/${created.data.id}`, { replace: true });
          }
        } catch (err) {
          handleFormError(err, helpers);
        }
      }}
    >
      <Form noValidate>
        <TranslatableProvider>
          <FormPage
            title={isEdit ? post!.name : 'Novo artigo'}
            subtitle={
              isEdit && (
                <Stack direction="row" component="span" sx={{ gap: 1, alignItems: 'center' }}>
                  <Chip size="small" variant="light" color={STATES[post!.state].color} label={STATES[post!.state].label} />
                  <span>/blog/{post!.slug}</span>
                </Stack>
              )
            }
            backTo="/blog/artigos"
            viewUrl={post?.is_visible ? post.url : null}
            extraActions={
              isEdit && (
                <>
                  {!post!.is_visible && post!.state !== 'trashed' && <PreviewButton postId={post!.id} />}
                  {post!.state === 'trashed' ? (
                    <Button variant="contained" onClick={restore}>
                      Restaurar
                    </Button>
                  ) : (
                    <Button variant="outlined" color="error" onClick={() => setConfirmTrash(true)}>
                      Lixeira
                    </Button>
                  )}
                </>
              )
            }
          >
            {post?.state === 'trashed' && <Alert severity="warning">Este artigo está na lixeira e não aparece no site. Restaure-o para o voltar a publicar.</Alert>}
            <Grid container spacing={3}>
              <Grid size={{ xs: 12, lg: 8 }}>
                <MainCard content={false}>
                  <Box sx={{ px: 3 }}>
                    <LocaleTabs />
                  </Box>
                  <Stack sx={{ gap: 3, p: 3 }}>
                    <TranslatableTextField name="title" label="Título" required />
                    <TranslatableTextField name="excerpt" label="Resumo" multiline rows={2} helperText="Aparece na listagem do blog e na partilha nas redes sociais (até 280 caracteres)." />
                    <TranslatableRichTextField name="content" label="Texto" required />
                  </Stack>
                </MainCard>

                <MainCard title="SEO" sx={{ mt: 3 }}>
                  <Stack sx={{ gap: 3 }}>
                    <TranslatableTextField name="meta_title" label="Título SEO" helperText="Até 70 caracteres. Vazio: usa o título." />
                    <TranslatableTextField name="meta_description" label="Descrição SEO" multiline rows={2} helperText="Até 160 caracteres. Vazio: usa o resumo." />
                  </Stack>
                </MainCard>
              </Grid>

              <Grid size={{ xs: 12, lg: 4 }}>
                <Stack sx={{ gap: 3 }}>
                  <MainCard title="Publicação">
                    <Stack sx={{ gap: 2.5 }}>
                      <StatusField />
                      <DateTimeField name="published_at" label="Data de publicação" helperText="Uma data futura agenda o artigo. Vazio: usa o momento em que é publicado." />
                      <SlugField source="title.pt" prefix="/blog/" auto={!isEdit} />
                      <SelectField name="author_id" label="Autor" options={authors?.data ?? []} loading={!authors} placeholder="Equipa APE" helperText="Vazio: aparece como Equipa APE." />
                    </Stack>
                  </MainCard>
                  <MainCard title="Classificação">
                    <Stack sx={{ gap: 2.5 }}>
                      <SelectField
                        name="post_category_id"
                        label="Categoria"
                        options={(categories?.data ?? []).map((c) => ({ value: c.id, label: c.label }))}
                        loading={!categories}
                      />
                      <CreatableMultiSelect
                        name="tags"
                        label="Etiquetas"
                        options={(tags?.data ?? []).map((t) => ({ value: t.id, label: t.label }))}
                        loading={!tags}
                        onCreate={createTag}
                        helperText="Escreva o nome de uma etiqueta nova para a criar."
                      />
                    </Stack>
                  </MainCard>
                  <MainCard title="Imagem de destaque">
                    <MediaPickerField name="cover_image_id" label="Imagem" helperText="Recomendado 1200 × 675 px (16:9). Também é usada na partilha nas redes sociais." />
                  </MainCard>
                </Stack>
              </Grid>
            </Grid>
          </FormPage>
        </TranslatableProvider>

        <ConfirmDialog
          open={confirmTrash}
          title={`Enviar «${post?.name ?? ''}» para a lixeira?`}
          description="Deixa de aparecer no site. Pode restaurá-lo a partir da lixeira."
          confirmLabel="Enviar para a lixeira"
          danger
          onClose={() => setConfirmTrash(false)}
          onConfirm={trash}
        />
      </Form>
    </Formik>
  );
}
