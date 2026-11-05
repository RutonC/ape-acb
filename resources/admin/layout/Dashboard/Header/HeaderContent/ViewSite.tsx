// material-ui
import Tooltip from '@mui/material/Tooltip';
import Box from '@mui/material/Box';

// project-imports
import IconButton from 'components/@extended/IconButton';

// assets
import { Global } from 'iconsax-react';

// ==============================|| HEADER CONTENT - VER O SITE ||============================== //

export default function ViewSite() {
  return (
    <Box sx={{ flexShrink: 0, ml: 0.75 }}>
      <Tooltip title="Ver o site">
        <IconButton
          onClick={() => window.open('/', '_blank', 'noopener')}
          aria-label="Ver o site"
          color="secondary"
          variant="light"
          size="large"
          sx={(theme) => ({
            p: 1,
            color: 'secondary.main',
            bgcolor: 'secondary.100',
            ...theme.applyStyles('dark', { bgcolor: 'background.default' })
          })}
        >
          <Global variant="Bulk" />
        </IconButton>
      </Tooltip>
    </Box>
  );
}
