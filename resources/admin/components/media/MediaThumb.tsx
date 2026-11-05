// material-ui
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';

// types
import { MediaItem } from 'types/media';

// assets
import { DocumentText } from 'iconsax-react';

// ==============================|| MÉDIA - MINIATURA ||============================== //

export default function MediaThumb({ item, size = '100%' }: { item: MediaItem; size?: number | string }) {
  return (
    <Box
      sx={{
        width: size,
        aspectRatio: '1 / 1',
        borderRadius: 1.5,
        overflow: 'hidden',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        // Xadrez claro para se verem imagens transparentes (logótipos brancos, SVG).
        backgroundColor: 'secondary.lighter',
        backgroundImage: item.is_image
          ? 'linear-gradient(45deg, rgba(0,0,0,.04) 25%, transparent 25%), linear-gradient(-45deg, rgba(0,0,0,.04) 25%, transparent 25%), linear-gradient(45deg, transparent 75%, rgba(0,0,0,.04) 75%), linear-gradient(-45deg, transparent 75%, rgba(0,0,0,.04) 75%)'
          : 'none',
        backgroundSize: '16px 16px',
        backgroundPosition: '0 0, 0 8px, 8px -8px, -8px 0'
      }}
    >
      {item.thumbnail_url ? (
        <Box
          component="img"
          src={item.thumbnail_url}
          alt={item.alt ?? ''}
          loading="lazy"
          sx={{ width: '100%', height: '100%', objectFit: item.ext === 'svg' ? 'contain' : 'cover', p: item.ext === 'svg' ? 1 : 0 }}
        />
      ) : (
        <Box sx={{ textAlign: 'center', color: 'text.secondary' }}>
          <DocumentText size={36} variant="Bulk" />
          <Typography variant="caption" sx={{ display: 'block', fontWeight: 600 }}>
            {item.ext.toUpperCase()}
          </Typography>
        </Box>
      )}
    </Box>
  );
}
