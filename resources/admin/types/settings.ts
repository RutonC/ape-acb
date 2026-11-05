// ==============================|| TIPOS - DEFINIÇÕES E PAINEL INICIAL ||============================== //

import { ApplicationStatus, MessageStatus } from 'types/inbox';

export type LocalizedText = { pt: string; en: string };

export interface GeneralSettings {
  site_name: string;
  tagline: LocalizedText;
  footer_about: LocalizedText;
  logo_id: number | null;
  logo_white_id: number | null;
  favicon_id: number | null;
  address: string;
  address_short: string;
  email: string;
  phones: string[];
  whatsapp: string | null;
  hours: LocalizedText;
  map_embed_url: string | null;
  forms_recipient_email: string;
  nuit: string | null;
  commercial_registry: string | null;
  facebook: string | null;
  linkedin: string | null;
  instagram: string | null;
}

export interface SeoSettings {
  default_title: LocalizedText;
  default_description: LocalizedText;
  og_image_id: number | null;
  ga4_measurement_id: string | null;
  google_site_verification: string | null;
}

export interface DashboardData {
  counts: Record<string, { label: string; count: number; path: string }>;
  applications: { id: number; name: string; position: string; status: ApplicationStatus; status_label: string; created_at: string | null }[] | null;
  messages: { id: number; name: string; excerpt: string; status: MessageStatus; status_label: string; created_at: string | null }[] | null;
  translations: { type: string; title: string; missing: string; path: string }[] | null;
}
