// assets
import {
  Book1,
  Briefcase,
  Building4,
  Chart,
  DocumentText,
  Element3,
  Gallery,
  Image,
  Message,
  MessageQuestion,
  Microphone2,
  People,
  Ranking,
  Routing2,
  Star1,
  Wallet3
} from 'iconsax-react';

// ==============================|| BLOCOS - ÍCONES ||============================== //

const ICONS = {
  hero: Star1,
  cards: Element3,
  about: Image,
  counters: Chart,
  job_hero: Briefcase,
  job_categories: Element3,
  hiring_banner: Building4,
  job_list: Briefcase,
  job_locations: Routing2,
  blog: Book1,
  team: People,
  company_info: Building4,
  process: Routing2,
  cta: Microphone2,
  testimonials: Message,
  logos: Ranking,
  gallery: Gallery,
  pricing: Wallet3,
  faq: MessageQuestion,
  rich_text: DocumentText
} as const;

export function blockIcon(type: string) {
  return ICONS[type as keyof typeof ICONS] ?? Element3;
}
