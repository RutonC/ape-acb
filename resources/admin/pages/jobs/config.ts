// project-imports
import { translations } from 'components/form/translatable';

// types
import { JobOpening } from 'types/inbox';

// ==============================|| VAGAS - VALORES DO FORMULÁRIO ||============================== //

const TEXT_FIELDS = ['title', 'contract_type', 'location', 'summary', 'description', 'requirements'] as const;

/** Valores do formulário a partir da vaga (ou vazios, para uma nova). */
export function jobValues(job?: JobOpening) {
  const text = Object.fromEntries(TEXT_FIELDS.map((field) => [field, translations(job?.[field])])) as Record<
    (typeof TEXT_FIELDS)[number],
    { pt: string; en: string }
  >;

  return {
    ...text,
    slug: job?.slug ?? '',
    job_category_id: job?.job_category_id ?? null,
    positions: job?.positions ?? null,
    experience: job?.experience ?? '',
    salary: job?.salary ?? '',
    cover_image_id: job?.cover_image_id ?? null,
    is_open: job?.is_open ?? true,
    posted_at: job?.posted_at ?? null,
    closes_at: job?.closes_at ?? null
  };
}

export type JobValues = ReturnType<typeof jobValues>;
