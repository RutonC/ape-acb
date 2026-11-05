import { ReactNode, useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';

// material-ui
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Checkbox from '@mui/material/Checkbox';
import InputAdornment from '@mui/material/InputAdornment';
import LinearProgress from '@mui/material/LinearProgress';
import MenuItem from '@mui/material/MenuItem';
import OutlinedInput from '@mui/material/OutlinedInput';
import Select from '@mui/material/Select';
import Stack from '@mui/material/Stack';
import Table from '@mui/material/Table';
import TableBody from '@mui/material/TableBody';
import TableCell from '@mui/material/TableCell';
import TableContainer from '@mui/material/TableContainer';
import TableHead from '@mui/material/TableHead';
import TablePagination from '@mui/material/TablePagination';
import TableRow from '@mui/material/TableRow';
import TableSortLabel from '@mui/material/TableSortLabel';
import Typography from '@mui/material/Typography';

// third-party
import useSWR from 'swr';

// project-imports
import ConfirmDialog from 'components/ConfirmDialog';
import { LOCALES } from 'components/form/translatable';
import MainCard from 'components/MainCard';
import useDebounce from 'hooks/useDebounce';
import { errorMessage, fetcher } from 'utils/axios';
import notify from 'utils/notify';

// types
import { Paginated } from 'types/users';

// assets
import { SearchNormal1 } from 'iconsax-react';

// ==============================|| TABELA GENÉRICA DO PAINEL ||============================== //
// Lê da API (ListQuery do Laravel): ?search, ?sort=-campo, ?filter[x], ?page, ?per_page.
// O estado (página, pesquisa, filtros, ordenação) fica no endereço, por isso
// "voltar" depois de editar um registo regressa à mesma vista.

export interface Column<T> {
  id: string;
  label: string;
  render: (row: T) => ReactNode;
  /** Permite ordenar por esta coluna (o id é o nome da coluna na API). */
  sortable?: boolean;
  align?: 'left' | 'center' | 'right';
  width?: number | string;
}

export interface Filter {
  id: string;
  label: string;
  options: { value: string; label: string }[];
}

export interface BulkAction {
  label: string;
  color?: 'primary' | 'error' | 'secondary';
  icon?: ReactNode;
  /** Pede confirmação antes de correr. */
  confirm?: { title: (count: number) => string; description?: string; confirmLabel?: string };
  run: (ids: number[]) => Promise<string | void>;
}

interface Props<T extends { id: number }> {
  endpoint: string;
  columns: Column<T>[];
  filters?: Filter[];
  searchPlaceholder?: string;
  defaultSort?: string;
  perPageOptions?: number[];
  /** Botões à direita da barra de pesquisa (ex.: "Novo"). */
  toolbar?: ReactNode;
  rowActions?: (row: T, reload: () => void) => ReactNode;
  bulkActions?: BulkAction[];
  emptyText?: string;
}

export default function DataTable<T extends { id: number }>({
  endpoint,
  columns: allColumns,
  filters = [],
  searchPlaceholder = 'Pesquisar',
  defaultSort = '',
  perPageOptions = [15, 30, 50],
  toolbar,
  rowActions,
  bulkActions = [],
  emptyText = 'Sem registos.'
}: Props<T>) {
  // A coluna do estado da tradução só faz sentido com mais de um idioma activo.
  const columns = LOCALES.length > 1 ? allColumns : allColumns.filter((column) => column.id !== 'translation');
  const [params, setParams] = useSearchParams();
  const [search, setSearch] = useState(params.get('search') ?? '');
  const term = useDebounce(search);
  const [selected, setSelected] = useState<number[]>([]);
  const [pendingBulk, setPendingBulk] = useState<BulkAction | null>(null);
  const [running, setRunning] = useState(false);

  const page = Math.max(Number(params.get('page') ?? 1), 1);
  const perPage = perPageOptions.includes(Number(params.get('per_page'))) ? Number(params.get('per_page')) : perPageOptions[0];
  const sort = params.get('sort') ?? defaultSort;

  const update = (changes: Record<string, string | null>, resetPage = true) => {
    const next = new URLSearchParams(params);
    for (const [key, value] of Object.entries(changes)) {
      if (value === null || value === '') next.delete(key);
      else next.set(key, value);
    }
    if (resetPage) next.delete('page');
    setParams(next, { replace: true });
  };

  // A pesquisa só vai para o endereço quando se pára de escrever.
  useEffect(() => {
    if ((params.get('search') ?? '') !== term) update({ search: term });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [term]);

  const query = useMemo(() => {
    const api = new URLSearchParams({ page: String(page), per_page: String(perPage) });
    if (term) api.set('search', term);
    if (sort) api.set('sort', sort);
    for (const filter of filters) {
      const value = params.get(filter.id);
      if (value) api.set(`filter[${filter.id}]`, value);
    }
    return api.toString();
  }, [page, perPage, term, sort, filters, params]);

  const separator = endpoint.includes('?') ? '&' : '?';
  const { data, isLoading, isValidating, mutate } = useSWR<Paginated<T>>(`${endpoint}${separator}${query}`, fetcher, { keepPreviousData: true });
  const rows = data?.data ?? [];

  // Selecção só vale para a página visível.
  useEffect(() => setSelected([]), [query]);

  const reload = () => {
    mutate();
  };

  const toggleSort = (column: string) => {
    const next = sort === column ? `-${column}` : sort === `-${column}` ? '' : column;
    update({ sort: next || null }, false);
  };

  const runBulk = async (action: BulkAction) => {
    setRunning(true);
    try {
      const message = await action.run(selected);
      if (message) notify.success(message);
      setSelected([]);
      reload();
    } catch (error) {
      notify.error(errorMessage(error));
    } finally {
      setRunning(false);
      setPendingBulk(null);
    }
  };

  const hasBulk = bulkActions.length > 0;
  const allSelected = rows.length > 0 && selected.length === rows.length;

  return (
    <MainCard content={false}>
      <Stack direction={{ xs: 'column', md: 'row' }} sx={{ gap: 2, p: 2.5, alignItems: { md: 'center' }, flexWrap: { md: 'wrap' } }}>
        <OutlinedInput
          value={search}
          onChange={(event) => setSearch(event.target.value)}
          placeholder={searchPlaceholder}
          inputProps={{ 'aria-label': searchPlaceholder }}
          startAdornment={
            <InputAdornment position="start">
              <SearchNormal1 size={18} />
            </InputAdornment>
          }
          sx={{ minWidth: { md: 260 }, flex: { md: '1 1 260px' } }}
        />
        {filters.map((filter) => (
          <Select
            key={filter.id}
            value={params.get(filter.id) ?? ''}
            displayEmpty
            onChange={(event) => update({ [filter.id]: event.target.value })}
            inputProps={{ 'aria-label': filter.label }}
            sx={{ minWidth: 200 }}
          >
            <MenuItem value="">{filter.label}: todos</MenuItem>
            {filter.options.map((option) => (
              <MenuItem key={option.value} value={option.value}>
                {option.label}
              </MenuItem>
            ))}
          </Select>
        ))}
        {toolbar && <Box sx={{ ml: { md: 'auto' }, display: 'flex', gap: 1, flexWrap: 'wrap' }}>{toolbar}</Box>}
      </Stack>

      {hasBulk && selected.length > 0 && (
        <Stack direction="row" sx={{ gap: 1.5, alignItems: 'center', px: 2.5, py: 1.25, bgcolor: 'primary.lighter', flexWrap: 'wrap' }}>
          <Typography variant="subtitle1">{selected.length === 1 ? '1 seleccionado' : `${selected.length} seleccionados`}</Typography>
          {bulkActions.map((action) => (
            <Button
              key={action.label}
              size="small"
              variant="outlined"
              color={action.color ?? 'primary'}
              startIcon={action.icon}
              disabled={running}
              onClick={() => (action.confirm ? setPendingBulk(action) : runBulk(action))}
            >
              {action.label}
            </Button>
          ))}
          <Button size="small" color="secondary" onClick={() => setSelected([])}>
            Limpar selecção
          </Button>
        </Stack>
      )}

      <Box sx={{ height: 4 }}>{(isLoading || isValidating) && <LinearProgress />}</Box>

      <TableContainer>
        <Table>
          <TableHead>
            <TableRow>
              {hasBulk && (
                <TableCell padding="checkbox">
                  <Checkbox
                    checked={allSelected}
                    indeterminate={selected.length > 0 && !allSelected}
                    onChange={(event) => setSelected(event.target.checked ? rows.map((row) => row.id) : [])}
                    inputProps={{ 'aria-label': 'Seleccionar todos' }}
                  />
                </TableCell>
              )}
              {columns.map((column) => (
                <TableCell key={column.id} align={column.align} sx={{ width: column.width }} sortDirection={sort.replace('-', '') === column.id ? (sort.startsWith('-') ? 'desc' : 'asc') : false}>
                  {column.sortable ? (
                    <TableSortLabel
                      active={sort.replace('-', '') === column.id}
                      direction={sort === `-${column.id}` ? 'desc' : 'asc'}
                      onClick={() => toggleSort(column.id)}
                    >
                      {column.label}
                    </TableSortLabel>
                  ) : (
                    column.label
                  )}
                </TableCell>
              ))}
              {rowActions && <TableCell align="right">Acções</TableCell>}
            </TableRow>
          </TableHead>
          <TableBody>
            {rows.map((row) => (
              <TableRow key={row.id} hover selected={selected.includes(row.id)}>
                {hasBulk && (
                  <TableCell padding="checkbox">
                    <Checkbox
                      checked={selected.includes(row.id)}
                      onChange={(event) => setSelected(event.target.checked ? [...selected, row.id] : selected.filter((id) => id !== row.id))}
                      inputProps={{ 'aria-label': `Seleccionar registo ${row.id}` }}
                    />
                  </TableCell>
                )}
                {columns.map((column) => (
                  <TableCell key={column.id} align={column.align}>
                    {column.render(row)}
                  </TableCell>
                ))}
                {rowActions && (
                  <TableCell align="right">
                    <Stack direction="row" sx={{ gap: 0.5, justifyContent: 'flex-end' }}>
                      {rowActions(row, reload)}
                    </Stack>
                  </TableCell>
                )}
              </TableRow>
            ))}
            {!isLoading && rows.length === 0 && (
              <TableRow>
                <TableCell colSpan={columns.length + (hasBulk ? 1 : 0) + (rowActions ? 1 : 0)} align="center" sx={{ py: 6 }}>
                  <Typography color="text.secondary">{term || filters.some((f) => params.get(f.id)) ? 'Nenhum resultado para esta pesquisa.' : emptyText}</Typography>
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </TableContainer>

      <TablePagination
        component="div"
        count={data?.meta.total ?? 0}
        page={(data?.meta.current_page ?? page) - 1}
        rowsPerPage={perPage}
        rowsPerPageOptions={perPageOptions}
        onPageChange={(_, value) => update({ page: value > 0 ? String(value + 1) : null }, false)}
        onRowsPerPageChange={(event) => update({ per_page: String(event.target.value) })}
        labelRowsPerPage="Por página"
        labelDisplayedRows={({ from, to, count }) => `${from}–${to} de ${count}`}
        getItemAriaLabel={(type) => (type === 'next' ? 'Página seguinte' : type === 'previous' ? 'Página anterior' : type === 'first' ? 'Primeira página' : 'Última página')}
      />

      <ConfirmDialog
        open={pendingBulk !== null}
        title={pendingBulk?.confirm?.title(selected.length) ?? ''}
        description={pendingBulk?.confirm?.description}
        confirmLabel={pendingBulk?.confirm?.confirmLabel ?? 'Confirmar'}
        danger={pendingBulk?.color === 'error'}
        loading={running}
        onClose={() => setPendingBulk(null)}
        onConfirm={() => pendingBulk && runBulk(pendingBulk)}
      />
    </MainCard>
  );
}
