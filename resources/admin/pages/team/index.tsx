import { useState } from 'react';

// material-ui
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import Grid from '@mui/material/Grid2';
import Stack from '@mui/material/Stack';
import Switch from '@mui/material/Switch';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';

// third-party
import { Form, Formik } from 'formik';
import useSWR from 'swr';
import * as Yup from 'yup';

// project-imports
import ConfirmDialog from 'components/ConfirmDialog';
import ReorderDialog from 'components/ReorderDialog';
import TranslationStatusChip from 'components/TranslationStatusChip';
import UserAvatar from 'components/UserAvatar';
import IconButton from 'components/@extended/IconButton';
import DataTable, { Column } from 'components/data-table/DataTable';
import SwitchField from 'components/form/SwitchField';
import TextField from 'components/form/TextField';
import { translations } from 'components/form/translatable';
import MediaPickerField from 'components/media/MediaPickerField';
import api, { errorMessage, fetcher } from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';

// types
import { TeamMember } from 'types/catalog';

// assets
import { Add, Edit, Sort, Trash } from 'iconsax-react';

// ==============================|| EQUIPA ||============================== //

const memberValues = (member?: TeamMember | null) => ({
  name: member?.name ?? '',
  role: translations(member?.role),
  bio: translations(member?.bio),
  photo_id: member?.photo_id ?? null,
  linkedin_url: member?.linkedin_url ?? '',
  email: member?.email ?? '',
  is_published: member?.is_published ?? true
});

function MemberDialog({ member, onClose, onSaved }: { member: TeamMember | null; onClose: () => void; onSaved: () => void }) {
  const isEdit = Boolean(member);

  return (
    <Dialog open onClose={onClose} maxWidth="md" fullWidth>
      <Formik
        initialValues={memberValues(member)}
        validationSchema={Yup.object({
          name: Yup.string().trim().max(120).required('Indique o nome.'),
          role: Yup.object({ pt: Yup.string().trim().required('Indique a função em português.') }),
          linkedin_url: Yup.string().url('Indique um endereço completo (https://...).').nullable(),
          email: Yup.string().email('Indique um e-mail válido.').nullable()
        })}
        onSubmit={async (values, helpers) => {
          try {
            if (isEdit) await api.put(`/team/${member!.id}`, values);
            else await api.post('/team', values);
            notify.success(isEdit ? 'Guardado.' : 'Membro da equipa adicionado.');
            onSaved();
          } catch (error) {
            handleFormError(error, helpers);
          }
        }}
      >
        {({ isSubmitting }) => (
          <Form noValidate>
            <DialogTitle>{isEdit ? `Editar ${member!.name}` : 'Novo membro da equipa'}</DialogTitle>
            <DialogContent dividers>
              <Grid container spacing={2.5}>
                <Grid size={{ xs: 12, md: 8 }}>
                  <Stack sx={{ gap: 2.5 }}>
                    <TextField name="name" label="Nome" required autoFocus />
                    <Grid container spacing={2}>
                      <Grid size={{ xs: 12, sm: 6 }}>
                        <TextField name="role.pt" label="Função (português)" required />
                      </Grid>
                      <Grid size={{ xs: 12, sm: 6 }}>
                        <TextField name="role.en" label="Função (inglês)" />
                      </Grid>
                      <Grid size={{ xs: 12, sm: 6 }}>
                        <TextField name="bio.pt" label="Especialidade (português)" multiline minRows={2} helperText="Uma linha." />
                      </Grid>
                      <Grid size={{ xs: 12, sm: 6 }}>
                        <TextField name="bio.en" label="Especialidade (inglês)" multiline minRows={2} />
                      </Grid>
                      <Grid size={{ xs: 12, sm: 6 }}>
                        <TextField name="linkedin_url" label="LinkedIn" placeholder="https://www.linkedin.com/in/..." />
                      </Grid>
                      <Grid size={{ xs: 12, sm: 6 }}>
                        <TextField name="email" label="E-mail" type="email" />
                      </Grid>
                    </Grid>
                    <SwitchField name="is_published" label="Visível no site" />
                  </Stack>
                </Grid>
                <Grid size={{ xs: 12, md: 4 }}>
                  <MediaPickerField name="photo_id" label="Fotografia" helperText="Quadrada, pelo menos 600 × 600 px." />
                </Grid>
              </Grid>
            </DialogContent>
            <DialogActions sx={{ px: 3, py: 2 }}>
              <Button color="secondary" onClick={onClose}>
                Cancelar
              </Button>
              <Button type="submit" variant="contained" disabled={isSubmitting}>
                {isEdit ? 'Guardar' : 'Adicionar'}
              </Button>
            </DialogActions>
          </Form>
        )}
      </Formik>
    </Dialog>
  );
}

export default function Team() {
  const [editing, setEditing] = useState<TeamMember | null | undefined>(undefined);
  const [toDelete, setToDelete] = useState<{ member: TeamMember; reload: () => void } | null>(null);
  const [reorderOpen, setReorderOpen] = useState(false);
  const [tableKey, setTableKey] = useState(0);
  const { data: all, mutate: reloadAll } = useSWR<{ data: TeamMember[] }>(reorderOpen ? '/team?per_page=100' : null, fetcher);

  const reloadTable = () => setTableKey((key) => key + 1);

  const togglePublished = async (member: TeamMember, reload: () => void) => {
    try {
      await api.put(`/team/${member.id}`, { ...memberValues(member), is_published: !member.is_published });
      reload();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  const remove = async () => {
    if (!toDelete) return;
    const { member, reload } = toDelete;
    setToDelete(null);
    try {
      await api.delete(`/team/${member.id}`);
      notify.success(`${member.name} removido da equipa.`);
      reload();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  const columns: Column<TeamMember>[] = [
    {
      id: 'sort_order',
      label: 'Nome',
      sortable: true,
      render: (member) => (
        <Stack direction="row" sx={{ gap: 1.5, alignItems: 'center' }}>
          <UserAvatar name={member.name} />
          <Stack>
            <Typography variant="subtitle1">{member.name}</Typography>
            <Typography variant="caption" color="text.secondary">
              {member.role_text}
            </Typography>
          </Stack>
        </Stack>
      )
    },
    { id: 'translation', label: 'EN', align: 'center', render: (member) => <TranslationStatusChip status={member.translation} /> },
  ];

  return (
    <>
      <DataTable<TeamMember>
        key={tableKey}
        endpoint="/team"
        columns={columns}
        defaultSort="sort_order"
        perPageOptions={[50, 100]}
        searchPlaceholder="Pesquisar por nome ou função"
        toolbar={
          <>
            <Button variant="outlined" color="secondary" startIcon={<Sort />} onClick={() => setReorderOpen(true)}>
              Ordenar
            </Button>
            <Button variant="contained" startIcon={<Add />} onClick={() => setEditing(null)}>
              Adicionar membro
            </Button>
          </>
        }
        emptyText="A equipa ainda está vazia. O bloco Equipa só aparece no site quando houver membros visíveis."
        rowActions={(member, reload) => (
          <>
            <Tooltip title={member.is_published ? 'Visível no site' : 'Oculto no site'}>
              <Switch checked={member.is_published} onChange={() => togglePublished(member, reload)} inputProps={{ 'aria-label': `Visível: ${member.name}` }} />
            </Tooltip>
            <Tooltip title="Editar">
              <IconButton color="primary" onClick={() => setEditing(member)} aria-label={`Editar ${member.name}`}>
                <Edit />
              </IconButton>
            </Tooltip>
            <Tooltip title="Remover">
              <IconButton color="error" onClick={() => setToDelete({ member, reload })} aria-label={`Remover ${member.name}`}>
                <Trash />
              </IconButton>
            </Tooltip>
          </>
        )}
      />

      {editing !== undefined && (
        <MemberDialog
          member={editing}
          onClose={() => setEditing(undefined)}
          onSaved={() => {
            setEditing(undefined);
            reloadTable();
          }}
        />
      )}

      <ReorderDialog
        open={reorderOpen && Boolean(all)}
        title="Ordenar a equipa"
        endpoint="/team/reorder"
        items={(all?.data ?? []).map((member) => ({ id: member.id, label: member.name, caption: member.role_text }))}
        onClose={() => setReorderOpen(false)}
        onSaved={() => {
          reloadAll();
          reloadTable();
        }}
      />

      <ConfirmDialog
        open={toDelete !== null}
        title={`Remover ${toDelete?.member.name ?? ''} da equipa?`}
        description="Para esconder sem apagar, desligue “Visível no site”."
        confirmLabel="Remover"
        danger
        onClose={() => setToDelete(null)}
        onConfirm={remove}
      />
    </>
  );
}
