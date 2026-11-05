// ==============================|| TIPOS - BIBLIOTECA DE MÉDIA ||============================== //

export interface MediaItem {
  id: number;
  name: string;
  file_name: string;
  title: string | null;
  alt: string | null;
  caption: string | null;
  description: string | null;
  ext: string;
  type: string;
  is_image: boolean;
  width: number | null;
  height: number | null;
  size: number | null;
  url: string;
  thumbnail_url: string | null;
  preview_url: string | null;
  created_at: string | null;
}

export interface UploadLimits {
  max_size: number;
  extensions: string[];
}

export interface MediaPage {
  data: MediaItem[];
  meta: { current_page: number; per_page: number; total: number; last_page: number };
  upload: UploadLimits;
}

export type MediaAccept = 'image' | 'video' | 'all';
