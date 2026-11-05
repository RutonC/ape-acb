import { Link } from 'react-router-dom';
import { To } from 'history';

// material-ui
import { useTheme } from '@mui/material/styles';
import ButtonBase from '@mui/material/ButtonBase';
import { SxProps } from '@mui/system';

// project-imports
import { APP_DEFAULT_PATH, ThemeMode } from 'config';

interface Props {
  isIcon?: boolean;
  sx?: SxProps;
  to?: To;
}

// ==============================|| LOGÓTIPO APE ||============================== //
// O logótipo oficial tem texto escuro; no modo escuro fica sobre fundo branco.

export default function LogoSection({ isIcon, sx, to }: Props) {
  const theme = useTheme();
  const dark = theme.palette.mode === ThemeMode.DARK;

  return (
    <ButtonBase disableRipple component={Link} to={!to ? APP_DEFAULT_PATH : to} sx={sx} aria-label="APE, ir para o início">
      <img
        src="/images/brand/logo-ape.png"
        alt="APE, Agência Privada de Emprego"
        style={{ height: isIcon ? 40 : 52, width: 'auto', display: 'block', ...(dark ? { background: '#fff', borderRadius: 6, padding: 3 } : {}) }}
      />
    </ButtonBase>
  );
}
