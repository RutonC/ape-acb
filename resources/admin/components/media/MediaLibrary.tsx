import { useState } from 'react';

// material-ui
import Box from '@mui/material/Box';
import ButtonBase from '@mui/material/ButtonBase';
import Checkbox from '@mui/material/Checkbox';
import Grid from '@mui/material/Grid2';
import InputAdornment from '@mui/material/InputAdornment';
import LinearProgress from '@mui/material/LinearProgress';
import MenuItem from '@mui/material/MenuItem';
import OutlinedInput from '@mui/material/OutlinedInput';
import Pagination from '@mui/material/Pagination';
import Select from '@mui/material/Select';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// third-party
import useSWR from 'swr';

// project-imports
import MediaThumb from './MediaThumb';
import UploadZone from './UploadZone';
import useDebounce from 'hooks/useDebounce';
import { fetcher } from 'utils/axios';

// types
import { MediaAccept, MediaItem, MediaPage } from 'types/media';

// assets
import { SearchNormal1 } from 'iconsax-react';

// ==============================|| BIBLIOTECA DE MÉDIA (GRELHA) ||============================== //
// Usada na página da biblioteca (gerir) e no diálogo dos formulários (escolher).

interface Props {
  accept?: MediaAccept;
  /** Ids seleccionados (caixas de selecção). */
  selected: number[];
  onToggle: (item: MediaItem) => void;
  /** Clique na miniatura (ex.: abrir detalhes). Sem isto, o clique selecciona. */
  onOpen?: (item: MediaItem) => void;
  /** Muda quando é preciso recarregar (ex.: depois de editar ou apagar). */
  refreshKey?: number;
  onUploaded?: (items: MediaItem[]) => void;
}

export default function MediaLibrary({ accept = 'all', selected, onToggle, onOpen, refreshKey = 0, onUploaded }: Props) {
  const [search, setSearch] = useState('');
  const [type, setType] = useState<string>(accept === 'all' ? '' : accept);
  const [page, setPage] = useState(1);
  const term = useDebounce(search);

  const query = new URLSearchParams({ page: String(page), per_page: '24' });
  if (term) query.set('search', term);
  if (type) query.set('filter[type]', type);

  const { data, isLoading, isValidating, mutate } = useSWR<MediaPage>([`/media?${query}`, refreshKey], ([url]: [string]) => fetcher(url), { keepPreviousData: true });

  return (
    <Stack sx={{ gap: 2 }}>
      <UploadZone
        limits={data?.upload}
        accept={accept}
        onUploaded={(items) => {
          setPage(1);
          mutate();
          onUploaded?.(items);
        }}
      />

      <Stack direction={{ xs: 'column', sm: 'row' }} sx={{ gap: 2 }}>
        <OutlinedInput
          value={search}
          onChange={(event) => {
            setSearch(event.target.value);
            setPage(1);
          }}
          placeholder="Pesquisar por nome, título ou texto alternativo"
          inputProps={{ 'aria-label': 'Pesquisar na biblioteca' }}
          startAdornment={
            <InputAdornment position="start">
              <SearchNormal1 size={18} />
            </InputAdornment>
          }
          sx={{ flexGrow: 1 }}
        />
        {accept === 'all' && (
          <Select
            value={type}
            displayEmpty
            onChange={(event) => {
              setType(event.target.value);
              setPage(1);
            }}
            inputProps={{ 'aria-label': 'Tipo de ficheiro' }}
            sx={{ minWidth: 180 }}
          >
            <MenuItem value="">Todos os ficheiros</MenuItem>
            <MenuItem value="image">Imagens</MenuItem>
            <MenuItem value="video">Vídeos</MenuItem>
            <MenuItem value="document">Documentos</MenuItem>
          </Select>
        )}
      </Stack>

      <Box sx={{ height: 4 }}>{(isLoading || isValidating) && <LinearProgress />}</Box>

      {data && data.data.length === 0 && (
        <Typography color="text.secondary" sx={{ textAlign: 'center', py: 5 }}>
          {term || type ? 'Nenhum ficheiro encontrado.' : 'A biblioteca está vazia. Carregue o primeiro ficheiro.'}
        </Typography>
      )}

      <Grid container spacing={2}>
        {data?.data.map((item) => {
          const isSelected = selected.includes(item.id);
          return (
            <Grid key={item.id} size={{ xs: 6, sm: 4, md: 3, lg: 2 }}>
              <Box
                sx={{
                  position: 'relative',
                  borderRadius: 2,
                  border: '2px solid',
                  borderColor: isSelected ? 'primary.main' : 'transparent',
                  p: 0.5,
                  '&:hover .media-check, & .media-check.Mui-checked': { opacity: 1 }
                }}
              >
                <ButtonBase
                  onClick={() => (onOpen ? onOpen(item) : onToggle(item))}
                  sx={{ width: 1, display: 'block', borderRadius: 1.5, '&:focus-visible': { outline: '2px solid', outlineColor: 'primary.main' } }}
                  aria-label={`${onOpen ? 'Abrir' : isSelected ? 'Retirar' : 'Escolher'} ${item.title ?? item.file_name}`}
                >
                  <MediaThumb item={item} />
                </ButtonBase>
                <Checkbox
                  className="media-check"
                  checked={isSelected}
                  onChange={() => onToggle(item)}
                  inputProps={{ 'aria-label': `Seleccionar ${item.title ?? item.file_name}` }}
                  sx={{ position: 'absolute', top: 6, left: 6, opacity: onOpen ? 0 : 1, bgcolor: 'background.paper', borderRadius: 1, p: 0.25, '&:hover': { bgcolor: 'background.paper' } }}
                />
                <Typography variant="caption" sx={{ display: 'block', mt: 0.5, px: 0.5 }} noWrap title={item.file_name}>
                  {item.title || item.file_name}
                </Typography>
                {item.is_image && !item.alt && (
                  <Typography variant="caption" color="warning.dark" sx={{ display: 'block', px: 0.5 }}>
                    Sem texto alternativo
                  </Typography>
                )}
              </Box>
            </Grid>
          );
        })}
      </Grid>

      {data && data.meta.last_page > 1 && (
        <Stack sx={{ alignItems: 'center' }}>
          <Pagination
            count={data.meta.last_page}
            page={data.meta.current_page}
            onChange={(_, value) => setPage(value)}
            color="primary"
            getItemAriaLabel={(itemType, value) => (itemType === 'page' ? `Página ${value}` : itemType === 'next' ? 'Página seguinte' : 'Página anterior')}
          />
        </Stack>
      )}
    </Stack>
  );
}
