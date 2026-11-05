// ==============================|| TIPOS - BLOG ||============================== //

import { TranslationState } from 'components/TranslationStatusChip';
import { Translated } from 'types/catalog';

export type PostState = 'draft' | 'scheduled' | 'published' | 'trashed';

export interface Post {
  id: number;
  slug: string;
  name: string;
  title: Translated;
  excerpt: Translated;
  content: Translated;
  meta_title: Translated;
  meta_description: Translated;
  status: 'draft' | 'published';
  state: PostState;
  published_at: string | null;
  post_category_id: number | null;
  category_name: string | null;
  tags: number[];
  author_id: number | null;
  author_name: string;
  cover_image_id: number | null;
  url: string;
  is_visible: boolean;
  translation: TranslationState;
  deleted_at: string | null;
  updated_at: string | null;
}

export interface BlogTerm {
  id: number;
  slug: string;
  label: string;
  name: Translated;
  sort_order: number | null;
  posts_count: number;
  url: string;
}
