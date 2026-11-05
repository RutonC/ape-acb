// project-imports
import { openSnackbar } from 'api/snackbar';

// types
import { SnackbarProps } from 'types/snackbar';

// ==============================|| AVISOS (SNACKBAR) ||============================== //

function show(message: string, color: 'success' | 'error' | 'warning' | 'info') {
  openSnackbar({
    open: true,
    message,
    variant: 'alert',
    alert: { color, variant: 'filled' },
    anchorOrigin: { vertical: 'top', horizontal: 'right' },
    close: true
  } as SnackbarProps);
}

const notify = {
  success: (message: string) => show(message, 'success'),
  error: (message: string) => show(message, 'error'),
  warning: (message: string) => show(message, 'warning'),
  info: (message: string) => show(message, 'info')
};

export default notify;
