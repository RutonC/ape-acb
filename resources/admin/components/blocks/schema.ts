// types
import { Block, BlockDefinition, FieldDef } from 'types/pages';

// ==============================|| BLOCOS - VALORES POR OMISSÃO ||============================== //

let counter = 0;

/** Chave local para identificar o bloco no painel (arrastar, abrir/fechar). */
export function newKey(): string {
  counter += 1;
  return `b${Date.now().toString(36)}${counter}`;
}

function fieldDefaults(fields: FieldDef[]): Record<string, unknown> {
  const data: Record<string, unknown> = {};

  for (const field of fields) {
    if (field.type === 'note') continue;
    if (field.type === 'group') {
      Object.assign(data, fieldDefaults(field.fields ?? []));
      continue;
    }

    const name = field.name as string;
    switch (field.type) {
      case 'list':
      case 'repeater':
        data[name] = [];
        break;
      case 'toggle':
        data[name] = Boolean(field.default);
        break;
      case 'link':
        data[name] = { type: 'route', route: null, page_id: null, url: '', new_tab: false };
        break;
      case 'button':
        data[name] = { label: field.default_label ?? '', type: 'route', route: null, page_id: null, url: '', new_tab: false };
        break;
      case 'select':
      case 'buttons':
        data[name] = field.default ?? null;
        break;
      case 'media':
      case 'icon':
      case 'number':
        data[name] = null;
        break;
      default:
        data[name] = (field.default as string | undefined) ?? '';
    }
  }

  return data;
}

/** Item novo de uma lista repetível. */
export function itemDefaults(field: FieldDef): Record<string, unknown> {
  return fieldDefaults(field.fields ?? []);
}

/** Bloco novo com os valores por omissão e a aparência do tipo. */
export function newBlock(definition: BlockDefinition): Block {
  return {
    _key: newKey(),
    type: definition.type,
    data: { ...fieldDefaults(definition.fields), is_active: definition.active, background: definition.background, anchor: '' }
  };
}

/** Acrescenta chaves locais aos blocos vindos da API. */
export function withKeys(blocks: Block[] | null | undefined): Block[] {
  return (blocks ?? []).map((block) => ({ ...block, _key: newKey() }));
}

/** Retira as chaves locais antes de gravar. */
export function withoutKeys(blocks: Block[]): Block[] {
  return blocks.map(({ type, data }) => ({ type, data }));
}

/** Texto curto para o cabeçalho do bloco fechado. */
export function blockSummary(block: Block): string {
  const data = block.data ?? {};
  for (const key of ['title', 'subtitle', 'text', 'content']) {
    const value = data[key];
    if (typeof value === 'string' && value.trim()) {
      const text = value
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
      return text.length > 70 ? `${text.slice(0, 70)}…` : text;
    }
  }
  return '';
}

/** Título de um item de lista repetível a partir dos campos indicados. */
export function itemTitle(item: Record<string, unknown>, fields: string[] | null | undefined): string {
  if (!fields?.length) return '';
  const parts = fields.map((name) => item?.[name]).filter((value) => value !== null && value !== undefined && String(value).trim() !== '');
  return parts.join(fields.length > 1 && fields.includes('label') ? ' ' : '').trim();
}
