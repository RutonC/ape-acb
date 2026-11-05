// material-ui
import Chip from '@mui/material/Chip';
import Tooltip from '@mui/material/Tooltip';

// assets
import { TickCircle, Warning2 } from 'iconsax-react';

// ==============================|| ESTADO DA TRADUÇÃO (COLUNA "EN") ||============================== //

export interface TranslationState {
  complete: boolean;
  missing: string[];
}

export default function TranslationStatusChip({ status }: { status?: TranslationState }) {
  if (!status) return null;

  return status.complete ? (
    <Tooltip title="Tradução completa">
      <Chip size="small" color="success" variant="light" icon={<TickCircle size={16} />} label="EN" />
    </Tooltip>
  ) : (
    <Tooltip title={`Falta traduzir: ${status.missing.join(', ')}`}>
      <Chip size="small" color="warning" variant="light" icon={<Warning2 size={16} />} label="EN" />
    </Tooltip>
  );
}
