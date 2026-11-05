// material-ui
import Chip, { ChipProps } from '@mui/material/Chip';

// types
import { ApplicationStatus, MessageStatus } from 'types/inbox';

// ==============================|| ESTADO NA CAIXA DE ENTRADA ||============================== //

const COLORS: Record<MessageStatus | ApplicationStatus, ChipProps['color']> = {
  new: 'warning',
  read: 'default',
  replied: 'success',
  reviewing: 'info',
  interview: 'primary',
  rejected: 'default',
  hired: 'success'
};

export default function InboxStatusChip({ status, label }: { status: MessageStatus | ApplicationStatus; label: string }) {
  return <Chip size="small" variant="light" color={COLORS[status]} label={label} />;
}
