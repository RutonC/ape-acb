// ==============================|| TIPOS - CATÁLOGO, PORTFÓLIO E EQUIPA ||============================== //

import { TranslationState } from 'components/TranslationStatusChip';

/** Campo traduzível como vem da API (um idioma pode faltar). */
export type Translated<T = string> = { pt: T | null; en: T | null };

export interface DetailSection {
  title: string;
  icon: string | null;
  content: string | null;
}

export interface CatalogItem {
  id: number;
  kind: 'service';
  slug: string;
  name: string;
  summary_text: string | null;
  title: Translated;
  tagline: Translated;
  summary: Translated;
  intro: Translated;
  details: Translated<DetailSection[]>;
  audience: Translated;
  timeline: Translated;
  meta_title: Translated;
  meta_description: Translated;
  icon: string | null;
  image_id: number | null;
  external_url: string | null;
  external_label: string | null;
  is_published: boolean;
  sort_order: number;
  url: string;
  translation: TranslationState;
  updated_at: string | null;
}

export interface TeamMember {
  id: number;
  name: string;
  role_text: string;
  role: Translated;
  bio: Translated;
  photo_id: number | null;
  linkedin_url: string | null;
  email: string | null;
  is_published: boolean;
  sort_order: number;
  translation: TranslationState;
}
