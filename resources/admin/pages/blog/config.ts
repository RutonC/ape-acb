// project-imports
import { translations } from 'components/form/translatable';

// types
import { Post, PostState } from 'types/blog';

// ==============================|| BLOG - VALORES E ESTADOS ||============================== //

const TEXT_FIELDS = ['title', 'excerpt', 'content', 'meta_title', 'meta_description'] as const;

export function postValues(post?: Post, defaultAuthor?: number | null) {
  const text = Object.fromEntries(TEXT_FIELDS.map((field) => [field, translations(post?.[field])])) as Record<(typeof TEXT_FIELDS)[number], { pt: string; en: string }>;

  return {
    ...text,
    slug: post?.slug ?? '',
    status: post?.status ?? ('draft' as Post['status']),
    published_at: post?.published_at ?? null,
    post_category_id: post?.post_category_id ?? null,
    tags: post?.tags ?? ([] as number[]),
    author_id: post ? post.author_id : (defaultAuthor ?? null),
    cover_image_id: post?.cover_image_id ?? null
  };
}

export const STATES: Record<PostState, { label: string; color: 'secondary' | 'warning' | 'success' | 'error' }> = {
  draft: { label: 'Rascunho', color: 'secondary' },
  scheduled: { label: 'Agendado', color: 'warning' },
  published: { label: 'Publicado', color: 'success' },
  trashed: { label: 'Na lixeira', color: 'error' }
};

export function formatDateTime(value: string | null): string {
  return value ? new Date(value).toLocaleString('pt-PT', { dateStyle: 'medium', timeStyle: 'short' }) : '';
}
