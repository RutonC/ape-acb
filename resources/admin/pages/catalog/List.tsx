import { useState } from 'react';
import { Link as RouterLink, useNavigate } from 'react-router-dom';

// material-ui
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import Switch from '@mui/material/Switch';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';

// third-party
import useSWR from 'swr';

// project-imports
import { CATALOG, CatalogKind, catalogValues } from './config';
import ConfirmDialog from 'components/ConfirmDialog';
import ReorderDialog from 'components/ReorderDialog';
import TranslationStatusChip from 'components/TranslationStatusChip';
import IconButton from 'components/@extended/IconButton';
import DataTable, { Column } from 'components/data-table/DataTable';
import api, { errorMessage, fetcher } from 'utils/axios';
import notify from 'utils/notify';

// types
import { CatalogItem } from 'types/catalog';

// assets
import { Add, Edit, ExportSquare, Sort, Trash } from 'iconsax-react';

// ==============================|| SERVIÇOS / PRODUTOS - LISTA ||============================== //

export default function CatalogList({ kind }: { kind: CatalogKind }) {
  const config = CATALOG[kind];
  const navigate = useNavigate();
  const [reorderOpen, setReorderOpen] = useState(false);
  const [toDelete, setToDelete] = useState<{ item: CatalogItem; reload: () => void } | null>(null);
  const [tableKey, setTableKey] = useState(0);
  const { data: all, mutate: reloadAll } = useSWR<{ data: CatalogItem[] }>(reorderOpen ? `${config.endpoint}?per_page=100` : null, fetcher);

  const togglePublished = async (item: CatalogItem, reload: () => void) => {
    try {
      await api.put(`${config.endpoint}/${item.id}`, { ...catalogValues(item), is_published: !item.is_published });
      notify.success(item.is_published ? 'Oculto no site.' : 'Visível no site.');
      reload();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  const remove = async () => {
    if (!toDelete) return;
    const { item, reload } = toDelete;
    setToDelete(null);
    try {
      await api.delete(`${config.endpoint}/${item.id}`);
      notify.success(`${item.name} apagado.`);
      reload();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  const columns: Column<CatalogItem>[] = [
    {
      id: 'sort_order',
      label: 'Nome',
      sortable: true,
      render: (item) => (
        <Stack>
          <Typography variant="subtitle1">{item.name}</Typography>
          {item.summary_text && (
            <Typography variant="caption" color="text.secondary" sx={{ display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden', maxWidth: 520 }}>
              {item.summary_text}
            </Typography>
          )}
        </Stack>
      )
    },
    { id: 'translation', label: 'EN', align: 'center', render: (item) => <TranslationStatusChip status={item.translation} /> },
    {
      id: 'updated_at',
      label: 'Actualizado',
      sortable: true,
      render: (item) => (
        <Typography variant="body2" color="text.secondary">
          {item.updated_at ? new Date(item.updated_at).toLocaleDateString('pt-PT', { dateStyle: 'medium' }) : ''}
        </Typography>
      )
    }
  ];

  return (
    <>
      <DataTable<CatalogItem>
        key={tableKey}
        endpoint={config.endpoint}
        columns={columns}
        defaultSort="sort_order"
        perPageOptions={[50, 100]}
        searchPlaceholder={`Pesquisar ${config.plural.toLowerCase()}`}
        filters={[
          {
            id: 'published',
            label: 'Estado',
            options: [
              { value: '1', label: 'Visíveis' },
              { value: '0', label: 'Ocultos' }
            ]
          }
        ]}
        toolbar={
          <>
            <Button variant="outlined" color="secondary" startIcon={<Sort />} onClick={() => setReorderOpen(true)}>
              Ordenar
            </Button>
            <Button component={RouterLink} to={`${config.path}/novo`} variant="contained" startIcon={<Add />}>
              {config.newLabel}
            </Button>
          </>
        }
        emptyText={`Ainda não há ${config.plural.toLowerCase()}.`}
        bulkActions={[
          {
            label: 'Apagar',
            color: 'error',
            icon: <Trash size={16} />,
            confirm: { title: (n) => (n === 1 ? `Apagar o ${config.singular} seleccionado?` : `Apagar ${n} ${config.plural.toLowerCase()}?`), confirmLabel: 'Apagar', description: 'As ligações para estas páginas deixam de funcionar.' },
            run: async (ids) => {
              await api.post(`${config.endpoint}/bulk-delete`, { ids });
              return ids.length === 1 ? 'Apagado.' : `${ids.length} apagados.`;
            }
          }
        ]}
        rowActions={(item, reload) => (
          <>
            <Tooltip title={item.is_published ? 'Visível no site' : 'Oculto no site'}>
              <Switch checked={item.is_published} onChange={() => togglePublished(item, reload)} inputProps={{ 'aria-label': `Visível no site: ${item.name}` }} />
            </Tooltip>
            {item.is_published && (
              <Tooltip title="Ver no site">
                <IconButton color="secondary" onClick={() => window.open(item.url, '_blank', 'noopener')} aria-label={`Ver ${item.name} no site`}>
                  <ExportSquare />
                </IconButton>
              </Tooltip>
            )}
            <Tooltip title="Editar">
              <IconButton color="primary" onClick={() => navigate(`${config.path}/${item.id}`)} aria-label={`Editar ${item.name}`}>
                <Edit />
              </IconButton>
            </Tooltip>
            <Tooltip title="Apagar">
              <IconButton color="error" onClick={() => setToDelete({ item, reload })} aria-label={`Apagar ${item.name}`}>
                <Trash />
              </IconButton>
            </Tooltip>
          </>
        )}
      />

      <ReorderDialog
        open={reorderOpen && Boolean(all)}
        title={`Ordenar ${config.plural.toLowerCase()}`}
        endpoint={`${config.endpoint}/reorder`}
        items={(all?.data ?? []).map((item) => ({ id: item.id, label: item.name, caption: item.is_published ? undefined : 'Oculto' }))}
        onClose={() => setReorderOpen(false)}
        onSaved={() => {
          reloadAll();
          setTableKey((key) => key + 1);
        }}
      />

      <ConfirmDialog
        open={toDelete !== null}
        title={`Apagar ${toDelete?.item.name ?? ''}?`}
        description="A página deixa de existir no site e as ligações para ela deixam de funcionar. Para esconder sem apagar, desligue “Visível no site”."
        confirmLabel="Apagar"
        danger
        onClose={() => setToDelete(null)}
        onConfirm={remove}
      />
    </>
  );
}
