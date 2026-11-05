import { useNavigate } from 'react-router-dom';

// material-ui
import Button from '@mui/material/Button';
import Grid from '@mui/material/Grid2';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// third-party
import useSWR from 'swr';

// project-imports
import Loader from 'components/Loader';
import MainCard from 'components/MainCard';
import { fetcher } from 'utils/axios';

// types
import { MenuRecord } from 'types/pages';

// assets
import { Edit } from 'iconsax-react';

// ==============================|| MENUS DO SITE ||============================== //

export default function Menus() {
  const navigate = useNavigate();
  const { data } = useSWR<{ data: MenuRecord[] }>('/menus', fetcher);

  if (!data) return <Loader />;

  return (
    <Grid container spacing={3}>
      {data.data.map((menu) => (
        <Grid key={menu.id} size={{ xs: 12, md: 6 }}>
          <MainCard>
            <Stack sx={{ gap: 1.5, height: 1 }}>
              <Stack direction="row" sx={{ justifyContent: 'space-between', alignItems: 'flex-start', gap: 2 }}>
                <Stack>
                  <Typography variant="h5">{menu.location_label}</Typography>
                  <Typography variant="body2" color="text.secondary">
                    {menu.description}
                  </Typography>
                </Stack>
                <Button
                  variant="outlined"
                  startIcon={<Edit size={18} />}
                  onClick={() => navigate(`/menus/${menu.id}`)}
                  aria-label={`Editar ${menu.location_label}`}
                >
                  Editar
                </Button>
              </Stack>
              <Typography variant="body2">
                {menu.items_count === 0
                  ? 'Sem itens: o site usa o menu predefinido.'
                  : `${menu.items_count} ${menu.items_count === 1 ? 'item' : 'itens'}: `}
                {menu.items
                  .map((item) => item.label.pt)
                  .filter(Boolean)
                  .join(', ')}
              </Typography>
            </Stack>
          </MainCard>
        </Grid>
      ))}
    </Grid>
  );
}
