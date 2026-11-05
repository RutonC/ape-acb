import { useState } from 'react';
import { Link as RouterLink, useNavigate, useSearchParams } from 'react-router-dom';

// material-ui
import Button from '@mui/material/Button';
import Chip from '@mui/material/Chip';
import Stack from '@mui/material/Stack';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';

// project-imports
import { PAGE_STATES } from './pageConfig';
import ConfirmDialog from 'components/ConfirmDialog';
import TranslationStatusChip from 'components/TranslationStatusChip';
import IconButton from 'components/@extended/IconButton';
import DataTable, { Column } from 'components/data-table/DataTable';
import api, { errorMessage } from 'utils/axios';
import notify from 'utils/notify';

// types
import { PageRecord } from 'types/pages';

// assets
import { Add, Edit, ExportSquare, Eye, Home2, RotateLeft, Trash } from 'iconsax-react';

// ==============================|| PÁGINAS - LISTA ||============================== //

type Pending = { type: 'trash' | 'force'; page: PageRecord; reload: () => void } | null;

export default function PagesList() {
  const navigate = useNavigate();
  const [params, setParams] = useSearchParams();
  const inTrash = params.get('trashed') === 'only';
  const [pending, setPending] = useState<Pending>(null);

  const open = async (page: PageRecord) => {
    if (page.is_visible) {
      window.open(page.url, '_blank', 'noopener');
      return;
    }
    const tab = window.open('', '_blank');
    try {
      const { data } = await api.get<{ url: string }>(`/pages/${page.id}/preview`);
      if (tab) tab.location.href = data.url;
    } catch (error) {
      tab?.close();
      notify.error(errorMessage(error));
    }
  };

  const restore = async (page: PageRecord, reload: () => void) => {
    try {
      await api.post(`/pages/${page.id}/restore`);
      notify.success('Página restaurada.');
      reload();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  const confirmPending = async () => {
    if (!pending) return;
    const { type, page, reload } = pending;
    setPending(null);
    try {
      await api.delete(type === 'trash' ? `/pages/${page.id}` : `/pages/${page.id}/force`);
      notify.success(type === 'trash' ? 'Página enviada para a lixeira.' : 'Página apagada definitivamente.');
      reload();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  const columns: Column<PageRecord>[] = [
    {
      id: 'slug',
      label: 'Página',
      sortable: true,
      render: (page) => (
        <Stack>
          <Stack direction="row" sx={{ gap: 1, alignItems: 'center', flexWrap: 'wrap' }}>
            <Typography variant="subtitle1">{page.name}</Typography>
            {page.is_homepage && <Chip size="small" variant="light" color="primary" icon={<Home2 size={14} />} label="Página inicial" />}
            {page.fixed_route && (
              <Tooltip title={`Dá conteúdo à página fixa "${page.fixed_route}" do site.`}>
                <Chip size="small" variant="light" color="secondary" label={`Página fixa: ${page.fixed_route}`} />
              </Tooltip>
            )}
          </Stack>
          <Typography variant="caption" color="text.secondary" sx={{ fontFamily: 'monospace' }}>
            {page.is_homepage ? '/' : `/${page.slug}`}
          </Typography>
        </Stack>
      )
    },
    { id: 'translation', label: 'EN', align: 'center', render: (page) => <TranslationStatusChip status={page.translation} /> },
    {
      id: 'state',
      label: 'Estado',
      render: (page) => <Chip size="small" variant="light" color={PAGE_STATES[page.state].color} label={PAGE_STATES[page.state].label} />
    },
    {
      id: 'blocks',
      label: 'Blocos',
      align: 'center',
      render: (page) => (
        <Tooltip
          title={`${page.blocks_count.pt} em português, ${page.blocks_count.en || 'nenhum'} em inglês${page.blocks_count.en ? '' : ' (usa os portugueses)'}`}
        >
          <span>{page.blocks_count.pt}</span>
        </Tooltip>
      )
    },
    {
      id: 'updated_at',
      label: 'Actualizada',
      sortable: true,
      render: (page) => (
        <Typography variant="body2" color="text.secondary">
          {page.updated_at ? new Date(page.updated_at).toLocaleDateString('pt-PT', { dateStyle: 'medium' }) : ''}
        </Typography>
      )
    }
  ];

  return (
    <>
      <DataTable<PageRecord>
        key={inTrash ? 'lixeira' : 'paginas'}
        endpoint={inTrash ? '/pages?filter[trashed]=only' : '/pages'}
        columns={columns}
        defaultSort="slug"
        perPageOptions={[50, 100]}
        searchPlaceholder="Pesquisar por título ou endereço"
        filters={[
          {
            id: 'state',
            label: 'Estado',
            options: [
              { value: 'published', label: 'Publicadas' },
              { value: 'draft', label: 'Rascunhos' }
            ]
          }
        ]}
        toolbar={
          <>
            <Button
              variant={inTrash ? 'contained' : 'outlined'}
              color={inTrash ? 'error' : 'secondary'}
              startIcon={<Trash size={18} />}
              onClick={() => setParams(inTrash ? {} : { trashed: 'only' })}
            >
              {inTrash ? 'Sair da lixeira' : 'Lixeira'}
            </Button>
            {!inTrash && (
              <Button component={RouterLink} to="/paginas/nova" variant="contained" startIcon={<Add />}>
                Nova página
              </Button>
            )}
          </>
        }
        emptyText={inTrash ? 'A lixeira está vazia.' : 'Ainda não há páginas.'}
        rowActions={(page, reload) =>
          page.state === 'trashed' ? (
            <>
              <Tooltip title="Restaurar">
                <IconButton color="primary" onClick={() => restore(page, reload)} aria-label={`Restaurar ${page.name}`}>
                  <RotateLeft />
                </IconButton>
              </Tooltip>
              <Tooltip title="Apagar definitivamente">
                <IconButton
                  color="error"
                  onClick={() => setPending({ type: 'force', page, reload })}
                  aria-label={`Apagar ${page.name} definitivamente`}
                >
                  <Trash />
                </IconButton>
              </Tooltip>
            </>
          ) : (
            <>
              <Tooltip title={page.is_visible ? 'Ver no site' : 'Pré-visualizar'}>
                <IconButton
                  color="secondary"
                  onClick={() => open(page)}
                  aria-label={`${page.is_visible ? 'Ver' : 'Pré-visualizar'} ${page.name}`}
                >
                  {page.is_visible ? <ExportSquare /> : <Eye />}
                </IconButton>
              </Tooltip>
              <Tooltip title="Editar">
                <IconButton color="primary" onClick={() => navigate(`/paginas/${page.id}`)} aria-label={`Editar ${page.name}`}>
                  <Edit />
                </IconButton>
              </Tooltip>
              <Tooltip title={page.is_homepage ? 'A página inicial não pode ir para a lixeira' : 'Enviar para a lixeira'}>
                <span>
                  <IconButton
                    color="error"
                    disabled={page.is_homepage}
                    onClick={() => setPending({ type: 'trash', page, reload })}
                    aria-label={`Enviar ${page.name} para a lixeira`}
                  >
                    <Trash />
                  </IconButton>
                </span>
              </Tooltip>
            </>
          )
        }
      />

      <ConfirmDialog
        open={pending !== null}
        title={
          pending?.type === 'force' ? `Apagar «${pending.page.name}» definitivamente?` : `Enviar «${pending?.page.name}» para a lixeira?`
        }
        description={
          pending?.type === 'force'
            ? 'Não é possível recuperar depois.'
            : 'Deixa de aparecer no site e as ligações para ela deixam de funcionar. Pode restaurá-la a partir da lixeira.'
        }
        confirmLabel={pending?.type === 'force' ? 'Apagar' : 'Enviar para a lixeira'}
        danger
        onClose={() => setPending(null)}
        onConfirm={confirmPending}
      />
    </>
  );
}
