import { useParams } from 'react-router-dom';

// material-ui
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
import LinkField from 'components/form/LinkField';
import RepeaterField from 'components/form/RepeaterField';
import TextField from 'components/form/TextField';
import NotFound from 'pages/NotFound';
import api, { fetcher } from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';

// types
import { MenuItem, MenuRecord } from 'types/pages';

// ==============================|| MENUS - EDITAR ||============================== //

const newItem = (): MenuItem => ({
  label: { pt: '', en: '' },
  link: { type: 'route', route: null, page_id: null, url: '', new_tab: false },
  children: []
});

const menuValues = (menu: MenuRecord) => ({
  name: menu.name,
  items: menu.items.map((item) => ({
    label: { pt: item.label.pt ?? '', en: item.label.en ?? '' },
    link: { ...newItem().link, ...item.link },
    children: (item.children ?? []).map((child) => ({
      label: { pt: child.label.pt ?? '', en: child.label.en ?? '' },
      link: { ...newItem().link, ...child.link }
    }))
  }))
});

function ItemFields({ prefix }: { prefix: string }) {
  return (
    <Grid container spacing={2}>
      <Grid size={{ xs: 12, sm: 6 }}>
        <TextField name={`${prefix}.label.pt`} label="Texto (português)" required />
      </Grid>
      <Grid size={{ xs: 12, sm: 6 }}>
        <TextField name={`${prefix}.label.en`} label="Texto (inglês)" helperText="Vazio: usa o português." />
      </Grid>
      <Grid size={12}>
        <LinkField name={`${prefix}.link`} label="Destino" />
      </Grid>
    </Grid>
  );
}

export default function MenuForm() {
  const { id } = useParams();
  const { data, isLoading, error, mutate } = useSWR<{ data: MenuRecord }>(`/menus/${id}`, fetcher, { revalidateOnFocus: false });

  if (isLoading) return <Loader />;
  if (error || !data) return <NotFound />;

  const menu = data.data;

  return (
    <Formik
      key={menu.id}
      initialValues={menuValues(menu)}
      validationSchema={Yup.object({ name: Yup.string().trim().required('Indique o nome.') })}
      onSubmit={async (values, helpers) => {
        try {
          const { data: saved } = await api.put<{ data: MenuRecord }>(`/menus/${menu.id}`, values);
          await mutate({ data: saved.data }, { revalidate: false });
          helpers.resetForm({ values: menuValues(saved.data) });
          notify.success('Menu guardado.');
        } catch (err) {
          handleFormError(err, helpers, { nested: true });
        }
      }}
    >
      <Form noValidate>
        <FormPage title={menu.location_label} subtitle={menu.description} backTo="/menus">
          <MainCard>
            <Stack sx={{ gap: 3 }}>
              <Stack sx={{ maxWidth: 420 }}>
                <TextField name="name" label="Nome (interno)" required />
              </Stack>
              <RepeaterField<MenuItem>
                name="items"
                label="Itens do menu"
                helperText={menu.items.length === 0 ? 'Sem itens, o site usa o menu predefinido.' : undefined}
                addLabel="Adicionar item"
                max={30}
                itemLabel={(item) => item.label.pt || 'Novo item'}
                newItem={newItem}
                renderItem={(prefix) => (
                  <Stack sx={{ gap: 2.5 }}>
                    <ItemFields prefix={prefix} />
                    {menu.allows_children && (
                      <RepeaterField<MenuItem>
                        name={`${prefix}.children`}
                        label="Submenu"
                        addLabel="Adicionar item ao submenu"
                        max={20}
                        itemLabel={(item) => item.label.pt || 'Novo item'}
                        newItem={() => ({ label: { pt: '', en: '' }, link: newItem().link })}
                        renderItem={(childPrefix) => <ItemFields prefix={childPrefix} />}
                      />
                    )}
                  </Stack>
                )}
              />
            </Stack>
          </MainCard>
        </FormPage>
      </Form>
    </Formik>
  );
}
