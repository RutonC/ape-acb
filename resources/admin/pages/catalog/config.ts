// project-imports
import { translations } from 'components/form/translatable';

// types
import { CatalogItem, DetailSection } from 'types/catalog';

// ==============================|| CATÁLOGO - CONFIGURAÇÃO POR TIPO ||============================== //

export type CatalogKind = 'service';

export const CATALOG = {
  service: {
    endpoint: '/services',
    path: '/servicos',
    area: 'services',
    plural: 'Serviços',
    singular: 'serviço',
    newLabel: 'Novo serviço',
    publicPrefix: '/servicos/'
  }
} as const;

const TEXT_FIELDS = ['title', 'tagline', 'summary', 'intro', 'audience', 'timeline', 'meta_title', 'meta_description'] as const;

/** Valores do formulário a partir do registo (ou vazios, para um novo). */
export function catalogValues(item?: CatalogItem) {
  const text = Object.fromEntries(TEXT_FIELDS.map((field) => [field, translations(item?.[field])])) as Record<(typeof TEXT_FIELDS)[number], { pt: string; en: string }>;

  return {
    ...text,
    slug: item?.slug ?? '',
    details: { pt: item?.details.pt ?? ([] as DetailSection[]), en: item?.details.en ?? ([] as DetailSection[]) },
    icon: item?.icon ?? null,
    image_id: item?.image_id ?? null,
    external_url: item?.external_url ?? '',
    external_label: item?.external_label ?? '',
    is_published: item?.is_published ?? true
  };
}

export type CatalogValues = ReturnType<typeof catalogValues>;
