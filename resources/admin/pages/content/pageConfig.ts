// project-imports
import { withKeys, withoutKeys } from 'components/blocks/schema';
import { translations } from 'components/form/translatable';

// types
import { Block, PageRecord } from 'types/pages';

// ==============================|| PÁGINAS - VALORES DO FORMULÁRIO ||============================== //

export function pageValues(page?: PageRecord) {
  return {
    title: translations(page?.title),
    meta_title: translations(page?.meta_title),
    meta_description: translations(page?.meta_description),
    content: { pt: withKeys(page?.content.pt), en: withKeys(page?.content.en) },
    slug: page?.slug ?? '',
    status: page?.status ?? ('draft' as PageRecord['status']),
    is_homepage: page?.is_homepage ?? false,
    og_image_id: page?.og_image_id ?? null
  };
}

export type PageValues = ReturnType<typeof pageValues>;

/** Dados a enviar (sem as chaves locais dos blocos). */
export function pagePayload(values: PageValues) {
  return {
    ...values,
    content: { pt: withoutKeys(values.content.pt as Block[]), en: withoutKeys(values.content.en as Block[]) }
  };
}

export const PAGE_STATES = {
  draft: { label: 'Rascunho', color: 'secondary' },
  published: { label: 'Publicada', color: 'success' },
  trashed: { label: 'Na lixeira', color: 'error' }
} as const;
