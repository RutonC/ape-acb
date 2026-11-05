// ==============================|| TIPOS - CAIXA DE ENTRADA E VAGAS ||============================== //

import { TranslationState } from 'components/TranslationStatusChip';
import { Translated } from 'types/catalog';

export type MessageStatus = 'new' | 'read' | 'replied';

export interface ContactMessage {
  id: number;
  name: string;
  organization: string | null;
  email: string;
  phone: string | null;
  service: string | null;
  subject: string | null;
  excerpt: string;
  locale: string;
  status: MessageStatus;
  status_label: string;
  created_at: string | null;
  /** Só no detalhe. */
  message?: string;
  notes?: string | null;
}

export type ApplicationStatus = 'new' | 'reviewing' | 'interview' | 'rejected' | 'hired';

export interface JobApplication {
  id: number;
  name: string;
  email: string;
  phone: string;
  position: string;
  job_opening_id: number | null;
  cv_name: string;
  locale: string;
  status: ApplicationStatus;
  status_label: string;
  created_at: string | null;
  /** Só no detalhe. */
  birth_date?: string | null;
  gender?: string | null;
  document_type?: string | null;
  document_number?: string | null;
  qualifications?: string | null;
  experience_years?: number | null;
  address?: string | null;
  phone_alt?: string | null;
  message?: string | null;
  notes?: string | null;
}

export interface CompanyRegistration {
  id: number;
  company_name: string;
  name: string;
  phone: string;
  email: string;
  status: MessageStatus;
  status_label: string;
  notes: string | null;
  created_at: string | null;
}

export interface JobCategory {
  id: number;
  slug: string;
  icon: string | null;
  name_text: string;
  name: Translated;
  jobs_count: number;
  sort_order: number;
}

export interface JobOpening {
  id: number;
  slug: string;
  title_text: string;
  contract_text: string | null;
  category_text: string | null;
  location_text: string | null;
  job_category_id: number | null;
  positions: number | null;
  experience: string | null;
  salary: string | null;
  cover_image_id: number | null;
  is_open: boolean;
  is_accepting: boolean;
  posted_at: string | null;
  closes_at: string | null;
  applications_count: number;
  sort_order: number;
  url: string;
  translation: TranslationState;
  /** Só no detalhe. */
  title?: Translated;
  contract_type?: Translated;
  location?: Translated;
  summary?: Translated;
  description?: Translated;
  requirements?: Translated;
}

export interface InboxCounts {
  messages: number;
  applications: number;
  companies: number;
}
