import { ReactNode, useEffect, useState } from 'react';

// material-ui
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import Stack from '@mui/material/Stack';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';

// third-party
import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

// project-imports
import IconButton from 'components/@extended/IconButton';
import api, { errorMessage } from 'utils/axios';
import notify from 'utils/notify';

// assets
import { ArrowDown2, ArrowUp2, Menu } from 'iconsax-react';

// ==============================|| ORDENAR (ARRASTAR E LARGAR) ||============================== //
// Também funciona só com teclado: Tab até à pega, Espaço para pegar, setas para mover.

export interface ReorderItem {
  id: number;
  label: ReactNode;
  caption?: ReactNode;
}

interface Props {
  open: boolean;
  title: string;
  items: ReorderItem[];
  endpoint: string;
  onClose: () => void;
  onSaved: () => void;
}

function Row({ item, index, total, move }: { item: ReorderItem; index: number; total: number; move: (from: number, to: number) => void }) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: item.id });

  return (
    <Stack
      ref={setNodeRef}
      direction="row"
      sx={{
        alignItems: 'center',
        gap: 1,
        p: 1,
        pl: 0.5,
        border: '1px solid',
        borderColor: isDragging ? 'primary.main' : 'divider',
        borderRadius: 1.5,
        bgcolor: 'background.paper',
        boxShadow: isDragging ? 4 : 0,
        position: 'relative',
        zIndex: isDragging ? 1 : 0
      }}
      style={{ transform: CSS.Transform.toString(transform), transition }}
    >
      <IconButton {...attributes} {...listeners} size="small" color="secondary" aria-label="Arrastar para mudar a posição" sx={{ cursor: 'grab' }}>
        <Menu size={18} />
      </IconButton>
      <Typography variant="body2" color="text.secondary" sx={{ width: 24, textAlign: 'right' }}>
        {index + 1}.
      </Typography>
      <Box sx={{ flexGrow: 1, minWidth: 0 }}>
        <Typography variant="subtitle1" noWrap>
          {item.label}
        </Typography>
        {item.caption && (
          <Typography variant="caption" color="text.secondary" noWrap component="p">
            {item.caption}
          </Typography>
        )}
      </Box>
      <Tooltip title="Subir">
        <span>
          <IconButton size="small" color="secondary" disabled={index === 0} onClick={() => move(index, index - 1)} aria-label="Subir">
            <ArrowUp2 size={16} />
          </IconButton>
        </span>
      </Tooltip>
      <Tooltip title="Descer">
        <span>
          <IconButton size="small" color="secondary" disabled={index === total - 1} onClick={() => move(index, index + 1)} aria-label="Descer">
            <ArrowDown2 size={16} />
          </IconButton>
        </span>
      </Tooltip>
    </Stack>
  );
}

export default function ReorderDialog({ open, title, items, endpoint, onClose, onSaved }: Props) {
  const [order, setOrder] = useState<ReorderItem[]>(items);
  const [saving, setSaving] = useState(false);
  const sensors = useSensors(useSensor(PointerSensor), useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }));

  useEffect(() => {
    if (open) setOrder(items);
  }, [open, items]);

  const move = (from: number, to: number) => setOrder((current) => arrayMove(current, from, to));

  const onDragEnd = ({ active, over }: DragEndEvent) => {
    if (!over || active.id === over.id) return;
    setOrder((current) =>
      arrayMove(
        current,
        current.findIndex((item) => item.id === active.id),
        current.findIndex((item) => item.id === over.id)
      )
    );
  };

  const save = async () => {
    setSaving(true);
    try {
      await api.post(endpoint, { ids: order.map((item) => item.id) });
      notify.success('Ordem guardada.');
      onSaved();
      onClose();
    } catch (error) {
      notify.error(errorMessage(error));
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onClose={onClose} maxWidth="sm" fullWidth>
      <DialogTitle>{title}</DialogTitle>
      <DialogContent dividers>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
          Arraste pela pega ou use as setas. A ordem é a mesma em que aparecem no site.
        </Typography>
        <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={onDragEnd}>
          <SortableContext items={order.map((item) => item.id)} strategy={verticalListSortingStrategy}>
            <Stack sx={{ gap: 1 }}>
              {order.map((item, index) => (
                <Row key={item.id} item={item} index={index} total={order.length} move={move} />
              ))}
            </Stack>
          </SortableContext>
        </DndContext>
      </DialogContent>
      <DialogActions sx={{ px: 3, py: 2 }}>
        <Button color="secondary" onClick={onClose}>
          Cancelar
        </Button>
        <Button variant="contained" onClick={save} disabled={saving}>
          Guardar ordem
        </Button>
      </DialogActions>
    </Dialog>
  );
}
