import { ReactNode, useEffect, useState } from 'react';

// material-ui
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import Divider from '@mui/material/Divider';
import FormControlLabel from '@mui/material/FormControlLabel';
import MenuItem from '@mui/material/MenuItem';
import OutlinedInput from '@mui/material/OutlinedInput';
import Select from '@mui/material/Select';
import Stack from '@mui/material/Stack';
import Switch from '@mui/material/Switch';
import Tooltip from '@mui/material/Tooltip';

// third-party
import Image from '@tiptap/extension-image';
import { TableKit } from '@tiptap/extension-table';
import { Placeholder } from '@tiptap/extensions';
import { Editor, EditorContent, useEditor, useEditorState } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';

// project-imports
import IconButton from 'components/@extended/IconButton';
import MediaLibraryDialog from 'components/media/MediaLibraryDialog';

// assets
import {
  ArrowRotateLeft,
  ArrowRotateRight,
  Eraser,
  Gallery,
  Grid1,
  Link1,
  Minus,
  QuoteUp,
  TextBold,
  TextItalic,
  TextUnderline,
  TextalignJustifycenter
} from 'iconsax-react';

// ==============================|| EDITOR DE TEXTO (TIPTAP) ||============================== //
// Guarda HTML, como o editor do painel anterior: o conteúdo existente continua válido.
// No site o HTML é sanitizado (App\Support\RichContent).

/** Imagem da biblioteca: guarda também o id (data-id) para se saber onde é usada. */
const LibraryImage = Image.extend({
  addAttributes() {
    return {
      ...this.parent?.(),
      'data-id': { default: null, parseHTML: (element) => element.getAttribute('data-id'), renderHTML: (attrs) => (attrs['data-id'] ? { 'data-id': attrs['data-id'] } : {}) }
    };
  }
});

interface Props {
  value: string;
  onChange: (html: string) => void;
  onBlur?: () => void;
  placeholder?: string;
  error?: boolean;
  id?: string;
  ariaLabelledBy?: string;
}

function ToolButton({ title, active, disabled, onClick, children }: { title: string; active?: boolean; disabled?: boolean; onClick: () => void; children: ReactNode }) {
  return (
    <Tooltip title={title}>
      <span>
        <IconButton
          size="small"
          color={active ? 'primary' : 'secondary'}
          variant={active ? 'light' : 'text'}
          disabled={disabled}
          onClick={onClick}
          aria-label={title}
          aria-pressed={active}
        >
          {children}
        </IconButton>
      </span>
    </Tooltip>
  );
}

function LinkDialog({ editor, open, onClose }: { editor: Editor; open: boolean; onClose: () => void }) {
  const [url, setUrl] = useState('');
  const [newTab, setNewTab] = useState(false);

  useEffect(() => {
    if (open) {
      const attrs = editor.getAttributes('link');
      setUrl(attrs.href ?? '');
      setNewTab(attrs.target === '_blank');
    }
  }, [open, editor]);

  const apply = () => {
    const href = url.trim();
    const chain = editor.chain().focus().extendMarkRange('link');
    if (!href) chain.unsetLink().run();
    else chain.setLink({ href, target: newTab ? '_blank' : null, rel: newTab ? 'noopener noreferrer' : null }).run();
    onClose();
  };

  return (
    <Dialog open={open} onClose={onClose} maxWidth="xs" fullWidth>
      <DialogTitle>Ligação</DialogTitle>
      <DialogContent>
        <Stack sx={{ gap: 2, pt: 1 }}>
          <OutlinedInput
            autoFocus
            value={url}
            onChange={(event) => setUrl(event.target.value)}
            placeholder="https://… ou /caminho"
            inputProps={{ 'aria-label': 'Endereço da ligação' }}
            onKeyDown={(event) => event.key === 'Enter' && (event.preventDefault(), apply())}
          />
          <FormControlLabel control={<Switch checked={newTab} onChange={(event) => setNewTab(event.target.checked)} />} label="Abrir num novo separador" />
        </Stack>
      </DialogContent>
      <DialogActions sx={{ px: 3, pb: 2 }}>
        {editor.isActive('link') && (
          <Button color="error" onClick={() => (editor.chain().focus().extendMarkRange('link').unsetLink().run(), onClose())} sx={{ mr: 'auto' }}>
            Remover ligação
          </Button>
        )}
        <Button color="secondary" onClick={onClose}>
          Cancelar
        </Button>
        <Button variant="contained" onClick={apply}>
          Aplicar
        </Button>
      </DialogActions>
    </Dialog>
  );
}

function Toolbar({ editor }: { editor: Editor }) {
  const [linkOpen, setLinkOpen] = useState(false);
  const [mediaOpen, setMediaOpen] = useState(false);

  const state = useEditorState({
    editor,
    selector: ({ editor: e }) => ({
      heading: e.isActive('heading', { level: 2 }) ? 'h2' : e.isActive('heading', { level: 3 }) ? 'h3' : 'p',
      bold: e.isActive('bold'),
      italic: e.isActive('italic'),
      underline: e.isActive('underline'),
      bulletList: e.isActive('bulletList'),
      orderedList: e.isActive('orderedList'),
      blockquote: e.isActive('blockquote'),
      link: e.isActive('link'),
      table: e.isActive('table'),
      canUndo: e.can().undo(),
      canRedo: e.can().redo()
    })
  });

  const chain = () => editor.chain().focus();

  return (
    <Stack
      direction="row"
      sx={{ flexWrap: 'wrap', alignItems: 'center', gap: 0.25, px: 1, py: 0.75, borderBottom: '1px solid', borderColor: 'divider', bgcolor: 'secondary.lighter' }}
      role="toolbar"
      aria-label="Formatação do texto"
    >
      <Select
        size="small"
        value={state.heading}
        onChange={(event) => {
          const value = event.target.value;
          if (value === 'p') chain().setParagraph().run();
          else chain().toggleHeading({ level: value === 'h2' ? 2 : 3 }).run();
        }}
        inputProps={{ 'aria-label': 'Estilo do parágrafo' }}
        sx={{ minWidth: 130, mr: 0.5, bgcolor: 'background.paper', '& .MuiSelect-select': { py: 0.75 } }}
      >
        <MenuItem value="p">Parágrafo</MenuItem>
        <MenuItem value="h2">Título</MenuItem>
        <MenuItem value="h3">Subtítulo</MenuItem>
      </Select>
      <ToolButton title="Negrito" active={state.bold} onClick={() => chain().toggleBold().run()}>
        <TextBold size={18} />
      </ToolButton>
      <ToolButton title="Itálico" active={state.italic} onClick={() => chain().toggleItalic().run()}>
        <TextItalic size={18} />
      </ToolButton>
      <ToolButton title="Sublinhado" active={state.underline} onClick={() => chain().toggleUnderline().run()}>
        <TextUnderline size={18} />
      </ToolButton>
      <Divider orientation="vertical" flexItem sx={{ mx: 0.5 }} />
      <ToolButton title="Lista com marcas" active={state.bulletList} onClick={() => chain().toggleBulletList().run()}>
        <TextalignJustifycenter size={18} />
      </ToolButton>
      <ToolButton title="Lista numerada" active={state.orderedList} onClick={() => chain().toggleOrderedList().run()}>
        <Box component="span" sx={{ fontSize: 12, fontWeight: 700, lineHeight: 1 }}>
          1.
        </Box>
      </ToolButton>
      <ToolButton title="Citação" active={state.blockquote} onClick={() => chain().toggleBlockquote().run()}>
        <QuoteUp size={18} />
      </ToolButton>
      <ToolButton title="Linha separadora" onClick={() => chain().setHorizontalRule().run()}>
        <Minus size={18} />
      </ToolButton>
      <Divider orientation="vertical" flexItem sx={{ mx: 0.5 }} />
      <ToolButton title="Ligação" active={state.link} onClick={() => setLinkOpen(true)}>
        <Link1 size={18} />
      </ToolButton>
      <ToolButton title="Imagem da biblioteca" onClick={() => setMediaOpen(true)}>
        <Gallery size={18} />
      </ToolButton>
      <ToolButton
        title="Tabela"
        active={state.table}
        onClick={() => !state.table && chain().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()}
      >
        <Grid1 size={18} />
      </ToolButton>
      {state.table && (
        <Stack direction="row" sx={{ gap: 0.5, mx: 0.5 }}>
          <Button size="small" color="secondary" onClick={() => chain().addRowAfter().run()}>
            + linha
          </Button>
          <Button size="small" color="secondary" onClick={() => chain().addColumnAfter().run()}>
            + coluna
          </Button>
          <Button size="small" color="secondary" onClick={() => chain().deleteRow().run()}>
            − linha
          </Button>
          <Button size="small" color="secondary" onClick={() => chain().deleteColumn().run()}>
            − coluna
          </Button>
          <Button size="small" color="error" onClick={() => chain().deleteTable().run()}>
            Apagar tabela
          </Button>
        </Stack>
      )}
      <Divider orientation="vertical" flexItem sx={{ mx: 0.5 }} />
      <ToolButton title="Limpar formatação" onClick={() => chain().unsetAllMarks().clearNodes().run()}>
        <Eraser size={18} />
      </ToolButton>
      <ToolButton title="Desfazer" disabled={!state.canUndo} onClick={() => chain().undo().run()}>
        <ArrowRotateLeft size={18} />
      </ToolButton>
      <ToolButton title="Refazer" disabled={!state.canRedo} onClick={() => chain().redo().run()}>
        <ArrowRotateRight size={18} />
      </ToolButton>

      <LinkDialog editor={editor} open={linkOpen} onClose={() => setLinkOpen(false)} />
      <MediaLibraryDialog
        open={mediaOpen}
        onClose={() => setMediaOpen(false)}
        accept="image"
        onSelect={(items) => {
          setMediaOpen(false);
          for (const item of items) {
            chain()
              .setImage({ src: item.url, alt: item.alt ?? '', title: item.title ?? undefined, ...({ 'data-id': String(item.id) } as object) })
              .run();
          }
        }}
      />
    </Stack>
  );
}

export default function RichTextEditor({ value, onChange, onBlur, placeholder = 'Escreva aqui…', error, id, ariaLabelledBy }: Props) {
  const editor = useEditor({
    extensions: [
      StarterKit.configure({
        heading: { levels: [2, 3] },
        link: { openOnClick: false, autolink: true, defaultProtocol: 'https' }
      }),
      LibraryImage,
      TableKit.configure({ table: { resizable: false } }),
      Placeholder.configure({ placeholder })
    ],
    content: value || '',
    editorProps: {
      attributes: {
        ...(id && { id }),
        ...(ariaLabelledBy && { 'aria-labelledby': ariaLabelledBy }),
        'aria-multiline': 'true',
        role: 'textbox',
        class: 'acb-rich-text'
      }
    },
    onUpdate: ({ editor: e }) => onChange(e.isEmpty ? '' : e.getHTML()),
    onBlur: () => onBlur?.()
  });

  // Valor alterado fora do editor (ex.: "Copiar PT para EN").
  useEffect(() => {
    if (editor && !editor.isFocused && value !== (editor.isEmpty ? '' : editor.getHTML())) {
      editor.commands.setContent(value || '', { emitUpdate: false });
    }
  }, [value, editor]);

  if (!editor) return null;

  return (
    <Box
      sx={(theme) => ({
        border: '1px solid',
        borderColor: error ? 'error.main' : 'divider',
        borderRadius: 1,
        overflow: 'hidden',
        '&:focus-within': { borderColor: error ? 'error.main' : 'primary.main', boxShadow: `0 0 0 2px ${theme.palette.primary.lighter}` },
        '& .acb-rich-text': {
          minHeight: 180,
          maxHeight: 560,
          overflowY: 'auto',
          px: 2,
          py: 1.5,
          outline: 'none',
          fontSize: '0.9375rem',
          lineHeight: 1.65,
          '& > * + *': { mt: 1.25 },
          '& h2': { fontSize: '1.375rem', fontWeight: 600, fontFamily: theme.typography.h2.fontFamily },
          '& h3': { fontSize: '1.125rem', fontWeight: 600, fontFamily: theme.typography.h3.fontFamily },
          '& p, & h2, & h3, & ul, & ol, & blockquote': { my: 0 },
          '& ul, & ol': { pl: 3 },
          '& blockquote': { borderLeft: '3px solid', borderColor: 'primary.light', pl: 2, color: 'text.secondary' },
          '& a': { color: 'primary.main', textDecoration: 'underline' },
          '& img': { maxWidth: '100%', height: 'auto', borderRadius: 1 },
          '& img.ProseMirror-selectednode': { outline: '3px solid', outlineColor: 'primary.main' },
          '& hr': { border: 0, borderTop: '1px solid', borderColor: 'divider', my: 2 },
          '& table': { borderCollapse: 'collapse', width: '100%' },
          '& th, & td': { border: '1px solid', borderColor: 'divider', p: 1, verticalAlign: 'top', minWidth: 60 },
          '& th': { bgcolor: 'secondary.lighter', fontWeight: 600, textAlign: 'left' },
          '& .selectedCell': { bgcolor: 'primary.lighter' },
          '& p.is-editor-empty:first-of-type::before': { content: 'attr(data-placeholder)', color: 'text.disabled', float: 'left', height: 0, pointerEvents: 'none' }
        }
      })}
    >
      <Toolbar editor={editor} />
      <EditorContent editor={editor} />
    </Box>
  );
}
