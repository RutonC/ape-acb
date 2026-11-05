import { useState } from 'react';

// material-ui
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import ButtonBase from '@mui/material/ButtonBase';
import Collapse from '@mui/material/Collapse';
import Chip from '@mui/material/Chip';
import Grid from '@mui/material/Grid2';
import Stack from '@mui/material/Stack';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';

// third-party
import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { SortableContext, sortableKeyboardCoordinates, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { getIn, useFormikContext } from 'formik';

// project-imports
import BlockFields from './BlockFields';
import BlockPicker from './BlockPicker';
import { blockIcon } from './icons';
import { blockSummary, newBlock, newKey } from './schema';
import ConfirmDialog from 'components/ConfirmDialog';
import IconButton from 'components/@extended/IconButton';
import ButtonsField from 'components/form/ButtonsField';
import SwitchField from 'components/form/SwitchField';
import TextField from 'components/form/TextField';

// types
import { Block, BlockDefinition } from 'types/pages';

// assets
import { Add, ArrowDown2, ArrowUp2, Copy, Eye, EyeSlash, Menu, Trash } from 'iconsax-react';

// ==============================|| CONSTRUTOR DE PÁGINAS - BLOCOS ||============================== //

const BACKGROUNDS = [
  { value: 'white', label: 'Branco' },
  { value: 'light', label: 'Cinzento claro' },
  { value: 'dark', label: 'Azul escuro' }
];

interface Props {
  /** Caminho da lista no formulário (ex.: "content.pt"). */
  name: string;
  definitions: BlockDefinition[];
}

interface ItemProps {
  block: Block;
  index: number;
  total: number;
  name: string;
  definition?: BlockDefinition;
  expanded: boolean;
  hasErrors: boolean;
  onToggle: () => void;
  onMove: (to: number) => void;
  onDuplicate: () => void;
  onRemove: () => void;
  onAddBelow: () => void;
  onToggleActive: () => void;
}

function BlockItem({
  block,
  index,
  total,
  name,
  definition,
  expanded,
  hasErrors,
  onToggle,
  onMove,
  onDuplicate,
  onRemove,
  onAddBelow,
  onToggleActive
}: ItemProps) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: block._key! });
  const Icon = blockIcon(block.type);
  const active = block.data?.is_active !== false;
  const summary = blockSummary(block);
  const prefix = `${name}.${index}.data`;

  return (
    <Box
      ref={setNodeRef}
      style={{ transform: CSS.Transform.toString(transform), transition }}
      sx={{ position: 'relative', zIndex: isDragging ? 2 : 1 }}
    >
      <Box
        sx={{
          border: '1px solid',
          borderColor: hasErrors ? 'error.main' : isDragging ? 'primary.main' : 'divider',
          borderRadius: 1.5,
          bgcolor: 'background.paper',
          boxShadow: isDragging ? 6 : 'none',
          opacity: active ? 1 : 0.7
        }}
      >
        <Stack direction="row" sx={{ alignItems: 'center', gap: 1, px: 1, py: 1 }}>
          <IconButton
            {...attributes}
            {...listeners}
            size="small"
            color="secondary"
            aria-label="Arrastar bloco para mudar a posição"
            sx={{ cursor: 'grab' }}
          >
            <Menu size={18} />
          </IconButton>
          <ButtonBase
            onClick={onToggle}
            aria-expanded={expanded}
            sx={{
              flexGrow: 1,
              minWidth: 0,
              justifyContent: 'flex-start',
              gap: 1,
              textAlign: 'left',
              borderRadius: 1,
              py: 0.5,
              '&:focus-visible': { outline: '2px solid', outlineColor: 'primary.main' }
            }}
          >
            <Box sx={{ color: 'primary.main', display: 'flex' }}>
              <Icon size={22} variant="Bulk" />
            </Box>
            <Box sx={{ minWidth: 0, flexGrow: 1 }}>
              <Typography variant="subtitle1" noWrap>
                {definition?.label ?? `Bloco desconhecido (${block.type})`}
              </Typography>
              {summary && (
                <Typography variant="caption" color="text.secondary" noWrap component="p">
                  {summary}
                </Typography>
              )}
            </Box>
            {!active && <Chip size="small" variant="light" color="secondary" label="Oculto" />}
            {hasErrors && <Chip size="small" variant="light" color="error" label="Por corrigir" />}
          </ButtonBase>
          <Stack direction="row" sx={{ gap: 0.25, flexShrink: 0 }}>
            <Tooltip title={active ? 'Ocultar no site' : 'Mostrar no site'}>
              <IconButton size="small" color="secondary" onClick={onToggleActive} aria-label={active ? 'Ocultar bloco' : 'Mostrar bloco'}>
                {active ? <Eye size={16} /> : <EyeSlash size={16} />}
              </IconButton>
            </Tooltip>
            <Tooltip title="Subir">
              <span>
                <IconButton
                  size="small"
                  color="secondary"
                  disabled={index === 0}
                  onClick={() => onMove(index - 1)}
                  aria-label="Subir bloco"
                >
                  <ArrowUp2 size={16} />
                </IconButton>
              </span>
            </Tooltip>
            <Tooltip title="Descer">
              <span>
                <IconButton
                  size="small"
                  color="secondary"
                  disabled={index === total - 1}
                  onClick={() => onMove(index + 1)}
                  aria-label="Descer bloco"
                >
                  <ArrowDown2 size={16} />
                </IconButton>
              </span>
            </Tooltip>
            <Tooltip title="Duplicar">
              <IconButton size="small" color="secondary" onClick={onDuplicate} aria-label="Duplicar bloco">
                <Copy size={16} />
              </IconButton>
            </Tooltip>
            <Tooltip title="Adicionar bloco abaixo">
              <IconButton size="small" color="primary" onClick={onAddBelow} aria-label="Adicionar bloco abaixo">
                <Add size={16} />
              </IconButton>
            </Tooltip>
            <Tooltip title="Remover">
              <IconButton size="small" color="error" onClick={onRemove} aria-label="Remover bloco">
                <Trash size={16} />
              </IconButton>
            </Tooltip>
          </Stack>
        </Stack>
        <Collapse in={expanded} unmountOnExit>
          <Box sx={{ borderTop: '1px solid', borderColor: 'divider', p: 2.5 }}>
            {definition ? (
              <Stack sx={{ gap: 3 }}>
                <BlockFields fields={definition.fields} prefix={prefix} />
                <Box component="fieldset" sx={{ border: '1px dashed', borderColor: 'divider', borderRadius: 1.5, p: 2, m: 0 }}>
                  <Typography component="legend" variant="subtitle1" sx={{ px: 0.5 }}>
                    Aparência
                  </Typography>
                  <Grid container spacing={2.5} sx={{ alignItems: 'flex-start' }}>
                    <Grid size={{ xs: 12, md: 3 }}>
                      <SwitchField name={`${prefix}.is_active`} label="Visível no site" />
                    </Grid>
                    <Grid size={{ xs: 12, md: 5 }}>
                      <ButtonsField name={`${prefix}.background`} label="Fundo da secção" options={BACKGROUNDS} />
                    </Grid>
                    <Grid size={{ xs: 12, md: 4 }}>
                      <TextField
                        name={`${prefix}.anchor`}
                        label="Âncora (id)"
                        helperText="Permite ligar a esta secção com #âncora."
                        placeholder="ex.: contactos"
                      />
                    </Grid>
                  </Grid>
                </Box>
              </Stack>
            ) : (
              <Typography color="error">Este tipo de bloco já não existe. Remova-o.</Typography>
            )}
          </Box>
        </Collapse>
      </Box>
    </Box>
  );
}

export default function BlockEditor({ name, definitions }: Props) {
  const { values, errors, setFieldValue } = useFormikContext();
  const blocks: Block[] = getIn(values, name) ?? [];
  const blockErrors = getIn(errors, name);
  const [expanded, setExpanded] = useState<string[]>([]);
  const [picker, setPicker] = useState<{ open: boolean; at: number }>({ open: false, at: -1 });
  const [toRemove, setToRemove] = useState<number | null>(null);
  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates })
  );
  const byType = new Map(definitions.map((definition) => [definition.type, definition]));

  const set = (next: Block[]) => setFieldValue(name, next);

  const move = (from: number, to: number) => {
    const next = [...blocks];
    const [moved] = next.splice(from, 1);
    next.splice(to, 0, moved);
    set(next);
  };

  const insert = (definition: BlockDefinition, at: number) => {
    const block = newBlock(definition);
    const next = [...blocks];
    next.splice(at < 0 ? next.length : at + 1, 0, block);
    set(next);
    setExpanded((current) => [...current, block._key!]);
  };

  const duplicate = (index: number) => {
    const copy: Block = { ...structuredClone({ type: blocks[index].type, data: blocks[index].data }), _key: newKey() };
    const next = [...blocks];
    next.splice(index + 1, 0, copy);
    set(next);
  };

  const onDragEnd = ({ active, over }: DragEndEvent) => {
    if (!over || active.id === over.id) return;
    move(
      blocks.findIndex((block) => block._key === active.id),
      blocks.findIndex((block) => block._key === over.id)
    );
  };

  const allOpen = blocks.length > 0 && blocks.every((block) => expanded.includes(block._key!));

  return (
    <Stack sx={{ gap: 1.5 }}>
      <Stack direction="row" sx={{ alignItems: 'center', justifyContent: 'space-between', gap: 1, flexWrap: 'wrap' }}>
        <Typography variant="subtitle1">
          Blocos da página{' '}
          <Typography component="span" color="text.secondary">
            ({blocks.length})
          </Typography>
        </Typography>
        {blocks.length > 0 && (
          <Button size="small" color="secondary" onClick={() => setExpanded(allOpen ? [] : blocks.map((block) => block._key!))}>
            {allOpen ? 'Fechar todos' : 'Abrir todos'}
          </Button>
        )}
      </Stack>

      {blocks.length === 0 && (
        <Box sx={{ border: '1px dashed', borderColor: 'divider', borderRadius: 2, p: 4, textAlign: 'center' }}>
          <Typography color="text.secondary">Esta página ainda não tem blocos. Comece por um Destaque ou um Texto livre.</Typography>
        </Box>
      )}

      <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={onDragEnd}>
        <SortableContext items={blocks.map((block) => block._key!)} strategy={verticalListSortingStrategy}>
          <Stack sx={{ gap: 1.5 }}>
            {blocks.map((block, index) => (
              <BlockItem
                key={block._key}
                block={block}
                index={index}
                total={blocks.length}
                name={name}
                definition={byType.get(block.type)}
                expanded={expanded.includes(block._key!)}
                hasErrors={Boolean(blockErrors?.[index])}
                onToggle={() =>
                  setExpanded((current) =>
                    current.includes(block._key!) ? current.filter((key) => key !== block._key) : [...current, block._key!]
                  )
                }
                onMove={(to) => move(index, to)}
                onDuplicate={() => duplicate(index)}
                onRemove={() => setToRemove(index)}
                onAddBelow={() => setPicker({ open: true, at: index })}
                onToggleActive={() => setFieldValue(`${name}.${index}.data.is_active`, block.data?.is_active === false)}
              />
            ))}
          </Stack>
        </SortableContext>
      </DndContext>

      <Box>
        <Button variant="outlined" startIcon={<Add />} onClick={() => setPicker({ open: true, at: -1 })}>
          Adicionar bloco
        </Button>
      </Box>

      <BlockPicker
        open={picker.open}
        definitions={definitions}
        onClose={() => setPicker({ open: false, at: -1 })}
        onPick={(definition) => {
          insert(definition, picker.at);
          setPicker({ open: false, at: -1 });
        }}
      />

      <ConfirmDialog
        open={toRemove !== null}
        title="Remover este bloco?"
        description="O conteúdo do bloco perde-se quando guardar a página. Para o esconder sem apagar, use o olho para o ocultar."
        confirmLabel="Remover"
        danger
        onClose={() => setToRemove(null)}
        onConfirm={() => {
          if (toRemove !== null) set(blocks.filter((_, i) => i !== toRemove));
          setToRemove(null);
        }}
      />
    </Stack>
  );
}
