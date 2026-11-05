import { useState } from 'react';

// material-ui
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// project-imports
import ConfirmDialog from 'components/ConfirmDialog';
import MainCard from 'components/MainCard';
import MediaDetailsDialog from 'components/media/MediaDetailsDialog';
import MediaLibrary from 'components/media/MediaLibrary';
import useAuth from 'hooks/useAuth';
import api, { errorMessage } from 'utils/axios';
import notify from 'utils/notify';

// assets
import { Trash } from 'iconsax-react';

// ==============================|| BIBLIOTECA DE MÉDIA ||============================== //

export default function MediaPage() {
  const { can } = useAuth();
  const [selected, setSelected] = useState<number[]>([]);
  const [openId, setOpenId] = useState<number | null>(null);
  const [refreshKey, setRefreshKey] = useState(0);
  const [confirmBulk, setConfirmBulk] = useState(false);

  const refresh = () => setRefreshKey((key) => key + 1);

  const bulkDelete = async () => {
    try {
      await api.post('/media/bulk-delete', { ids: selected });
      notify.success(selected.length === 1 ? 'Ficheiro apagado.' : `${selected.length} ficheiros apagados.`);
      setSelected([]);
      refresh();
    } catch (error) {
      notify.error(errorMessage(error));
    } finally {
      setConfirmBulk(false);
    }
  };

  return (
    <MainCard>
      {selected.length > 0 && (
        <Stack direction="row" sx={{ gap: 1.5, alignItems: 'center', mb: 2, p: 1.5, bgcolor: 'primary.lighter', borderRadius: 1.5, flexWrap: 'wrap' }}>
          <Typography variant="subtitle1">{selected.length === 1 ? '1 seleccionado' : `${selected.length} seleccionados`}</Typography>
          {can('media') && (
            <Button size="small" variant="outlined" color="error" startIcon={<Trash size={16} />} onClick={() => setConfirmBulk(true)}>
              Apagar
            </Button>
          )}
          <Button size="small" color="secondary" onClick={() => setSelected([])}>
            Limpar selecção
          </Button>
        </Stack>
      )}

      <MediaLibrary
        accept="all"
        selected={selected}
        refreshKey={refreshKey}
        onToggle={(item) => setSelected((current) => (current.includes(item.id) ? current.filter((id) => id !== item.id) : [...current, item.id]))}
        onOpen={(item) => setOpenId(item.id)}
      />

      <MediaDetailsDialog id={openId} onClose={() => setOpenId(null)} onChanged={refresh} />

      <ConfirmDialog
        open={confirmBulk}
        title={selected.length === 1 ? 'Apagar o ficheiro seleccionado?' : `Apagar ${selected.length} ficheiros?`}
        description="Os ficheiros são apagados do servidor. Conteúdos que os usem ficam sem a imagem. Para ver onde um ficheiro é usado, abra-o antes de apagar."
        confirmLabel="Apagar"
        danger
        onClose={() => setConfirmBulk(false)}
        onConfirm={bulkDelete}
      />
    </MainCard>
  );
}
