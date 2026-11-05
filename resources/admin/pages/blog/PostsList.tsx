import { useState } from 'react';
import { Link as RouterLink, useNavigate, useSearchParams } from 'react-router-dom';


// material-ui
import Button from '@mui/material/Button';
import Chip from '@mui/material/Chip';
import Stack from '@mui/material/Stack';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';

// third-party
import useSWR from 'swr';

// project-imports
import { formatDateTime, STATES } from './config';
import ConfirmDialog from 'components/ConfirmDialog';
import TranslationStatusChip from 'components/TranslationStatusChip';
import IconButton from 'components/@extended/IconButton';
import DataTable, { BulkAction, Column } from 'components/data-table/DataTable';
import api, { errorMessage, fetcher } from 'utils/axios';
import notify from 'utils/notify';

// types
import { BlogTerm, Post } from 'types/blog';

// assets
import { Add, Edit, ExportSquare, Eye, RotateLeft, Trash } from 'iconsax-react';

// ==============================|| BLOG - ARTIGOS ||============================== //

type Pending = { type: 'trash' | 'force'; post: Post; reload: () => void } | null;

export default function PostsList() {
  const navigate = useNavigate();
  const [params, setParams] = useSearchParams();
  const inTrash = params.get('trashed') === 'only';
  const [pending, setPending] = useState<Pending>(null);
  const { data: categories } = useSWR<{ data: BlogTerm[] }>('/blog/categories', fetcher);

  const open = async (post: Post) => {
    if (post.is_visible) {
      window.open(post.url, '_blank', 'noopener');
      return;
    }
    // Rascunhos e agendados: pré-visualização assinada (abre a janela antes do pedido, para não ser bloqueada).
    const tab = window.open('', '_blank');
    try {
      const { data } = await api.get<{ url: string }>(`/posts/${post.id}/preview`);
      if (tab) tab.location.href = data.url;
    } catch (error) {
      tab?.close();
      notify.error(errorMessage(error));
    }
  };

  const restore = async (post: Post, reload: () => void) => {
    try {
      await api.post(`/posts/${post.id}/restore`);
      notify.success('Artigo restaurado.');
      reload();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  const confirmPending = async () => {
    if (!pending) return;
    const { type, post, reload } = pending;
    setPending(null);
    try {
      await api.delete(type === 'trash' ? `/posts/${post.id}` : `/posts/${post.id}/force`);
      notify.success(type === 'trash' ? 'Artigo enviado para a lixeira.' : 'Artigo apagado definitivamente.');
      reload();
    } catch (error) {
      notify.error(errorMessage(error));
    }
  };

  const bulk = (action: 'trash' | 'restore' | 'force', message: (n: number) => string) => async (ids: number[]) => {
    await api.post('/posts/bulk', { action, ids });
    return message(ids.length);
  };

  const bulkActions: BulkAction[] = inTrash
    ? [
        { label: 'Restaurar', icon: <RotateLeft size={16} />, run: bulk('restore', (n) => (n === 1 ? 'Artigo restaurado.' : `${n} artigos restaurados.`)) },
        {
          label: 'Apagar definitivamente',
          color: 'error',
          icon: <Trash size={16} />,
          confirm: { title: (n) => (n === 1 ? 'Apagar o artigo definitivamente?' : `Apagar ${n} artigos definitivamente?`), description: 'Não é possível recuperar depois.', confirmLabel: 'Apagar' },
          run: bulk('force', (n) => (n === 1 ? 'Artigo apagado.' : `${n} artigos apagados.`))
        }
      ]
    : [
        {
          label: 'Enviar para a lixeira',
          color: 'error',
          icon: <Trash size={16} />,
          confirm: { title: (n) => (n === 1 ? 'Enviar o artigo para a lixeira?' : `Enviar ${n} artigos para a lixeira?`), description: 'Deixam de aparecer no site. Pode restaurá-los a partir da lixeira.', confirmLabel: 'Enviar para a lixeira' },
          run: bulk('trash', (n) => (n === 1 ? 'Artigo enviado para a lixeira.' : `${n} artigos enviados para a lixeira.`))
        }
      ];

  const columns: Column<Post>[] = [
    {
      id: 'title',
      label: 'Artigo',
      render: (post) => (
        <Stack>
          <Typography variant="subtitle1">{post.name}</Typography>
          <Typography variant="caption" color="text.secondary">
            {[post.category_name, post.author_name].filter(Boolean).join(' · ')}
          </Typography>
        </Stack>
      )
    },
    { id: 'translation', label: 'EN', align: 'center', render: (post) => <TranslationStatusChip status={post.translation} /> },
    { id: 'state', label: 'Estado', render: (post) => <Chip size="small" variant="light" color={STATES[post.state].color} label={STATES[post.state].label} /> },
    {
      id: 'published_at',
      label: 'Publicação',
      sortable: true,
      render: (post) => (
        <Typography variant="body2" color="text.secondary" sx={{ whiteSpace: 'nowrap' }}>
          {formatDateTime(post.published_at)}
        </Typography>
      )
    }
  ];

  return (
    <>
      <DataTable<Post>
        key={inTrash ? 'lixeira' : 'artigos'}
        endpoint={inTrash ? '/posts?filter[trashed]=only' : '/posts'}
        columns={columns}
        defaultSort="-published_at"
        searchPlaceholder="Pesquisar por título"
        filters={[
          {
            id: 'state',
            label: 'Estado',
            options: [
              { value: 'draft', label: 'Rascunhos' },
              { value: 'scheduled', label: 'Agendados' },
              { value: 'published', label: 'Publicados' }
            ]
          },
          { id: 'category', label: 'Categoria', options: (categories?.data ?? []).map((c) => ({ value: String(c.id), label: c.label })) }
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
              <Button component={RouterLink} to="/blog/artigos/novo" variant="contained" startIcon={<Add />}>
                Novo artigo
              </Button>
            )}
          </>
        }
        emptyText={inTrash ? 'A lixeira está vazia.' : 'Ainda não há artigos.'}
        bulkActions={bulkActions}
        rowActions={(post, reload) =>
          post.state === 'trashed' ? (
            <>
              <Tooltip title="Restaurar">
                <IconButton color="primary" onClick={() => restore(post, reload)} aria-label={`Restaurar ${post.name}`}>
                  <RotateLeft />
                </IconButton>
              </Tooltip>
              <Tooltip title="Apagar definitivamente">
                <IconButton color="error" onClick={() => setPending({ type: 'force', post, reload })} aria-label={`Apagar ${post.name} definitivamente`}>
                  <Trash />
                </IconButton>
              </Tooltip>
            </>
          ) : (
            <>
              <Tooltip title={post.is_visible ? 'Ver no site' : 'Pré-visualizar'}>
                <IconButton color="secondary" onClick={() => open(post)} aria-label={`${post.is_visible ? 'Ver' : 'Pré-visualizar'} ${post.name}`}>
                  {post.is_visible ? <ExportSquare /> : <Eye />}
                </IconButton>
              </Tooltip>
              <Tooltip title="Editar">
                <IconButton color="primary" onClick={() => navigate(`/blog/artigos/${post.id}`)} aria-label={`Editar ${post.name}`}>
                  <Edit />
                </IconButton>
              </Tooltip>
              <Tooltip title="Enviar para a lixeira">
                <IconButton color="error" onClick={() => setPending({ type: 'trash', post, reload })} aria-label={`Enviar ${post.name} para a lixeira`}>
                  <Trash />
                </IconButton>
              </Tooltip>
            </>
          )
        }
      />

      <ConfirmDialog
        open={pending !== null}
        title={pending?.type === 'force' ? `Apagar «${pending.post.name}» definitivamente?` : `Enviar «${pending?.post.name}» para a lixeira?`}
        description={pending?.type === 'force' ? 'Não é possível recuperar depois.' : 'Deixa de aparecer no site. Pode restaurá-lo a partir da lixeira.'}
        confirmLabel={pending?.type === 'force' ? 'Apagar' : 'Enviar para a lixeira'}
        danger
        onClose={() => setPending(null)}
        onConfirm={confirmPending}
      />
    </>
  );
}
