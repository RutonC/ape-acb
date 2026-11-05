// ==============================|| TIPOS - PÁGINAS, BLOCOS E MENUS ||============================== //

import { TranslationState } from 'components/TranslationStatusChip';
import { Translated } from 'types/catalog';

export type FieldType =
  | 'text'
  | 'textarea'
  | 'richtext'
  | 'number'
  | 'media'
  | 'icon'
  | 'select'
  | 'buttons'
  | 'toggle'
  | 'link'
  | 'button'
  | 'list'
  | 'repeater'
  | 'group'
  | 'note';

/** Campo de um bloco, como definido em App\Support\Blocks\BlockSchema. */
export interface FieldDef {
  accept?: 'image' | 'video' | 'all';
  type: FieldType;
  name: string | null;
  label: string;
  required?: boolean;
  max?: number | null;
  rows?: number;
  help?: string;
  placeholder?: string;
  width?: 'full' | 'half' | 'third' | 'quarter';
  input?: 'url' | 'tel';
  options?: { value: string | number; label: string }[];
  default?: unknown;
  default_label?: string | null;
  with_new_tab?: boolean;
  fields?: FieldDef[];
  item_label?: string[] | null;
  add_label?: string;
  max_items?: number | null;
}

export interface BlockDefinition {
  type: string;
  label: string;
  description: string;
  background: 'white' | 'light' | 'dark';
  active: boolean;
  fields: FieldDef[];
}

export interface Block {
  /** Chave local do painel (não é guardada). */
  _key?: string;
  type: string;
  data: Record<string, unknown>;
}

export interface PageRecord {
  id: number;
  slug: string;
  name: string;
  title: Translated;
  content: Translated<Block[]>;
  meta_title: Translated;
  meta_description: Translated;
  status: 'draft' | 'published';
  state: 'draft' | 'published' | 'trashed';
  is_homepage: boolean;
  fixed_route: string | null;
  blocks_count: { pt: number; en: number };
  og_image_id: number | null;
  url: string;
  is_visible: boolean;
  translation: TranslationState;
  published_at: string | null;
  updated_at: string | null;
}

export interface MenuItem {
  label: { pt?: string | null; en?: string | null };
  link: { type: 'route' | 'page' | 'url'; route?: string | null; page_id?: number | null; url?: string | null; new_tab?: boolean };
  children?: MenuItem[];
}

export interface MenuRecord {
  id: number;
  location: string;
  location_label: string;
  description: string;
  allows_children: boolean;
  name: string;
  items: MenuItem[];
  items_count: number;
  updated_at: string | null;
}
