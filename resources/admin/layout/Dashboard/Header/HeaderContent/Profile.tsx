import { useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';

// material-ui
import Box from '@mui/material/Box';
import ButtonBase from '@mui/material/ButtonBase';
import Divider from '@mui/material/Divider';
import ListItemIcon from '@mui/material/ListItemIcon';
import Menu from '@mui/material/Menu';
import MenuItem from '@mui/material/MenuItem';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// project-imports
import UserAvatar from 'components/UserAvatar';
import useAuth from 'hooks/useAuth';

// assets
import { Logout, ShieldTick, User } from 'iconsax-react';

// ==============================|| CABEÇALHO - MENU DO UTILIZADOR ||============================== //

export default function Profile() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const anchorRef = useRef<HTMLButtonElement>(null);
  const [open, setOpen] = useState(false);

  if (!user) return null;

  const go = (path: string) => {
    setOpen(false);
    navigate(path);
  };

  return (
    <Box sx={{ flexShrink: 0, ml: 0.75 }}>
      <ButtonBase
        ref={anchorRef}
        onClick={() => setOpen(true)}
        aria-label={`Menu da conta de ${user.name}`}
        aria-haspopup="true"
        aria-controls={open ? 'menu-conta' : undefined}
        sx={{ p: 0.25, borderRadius: 1, '&:focus-visible': { outline: '2px solid', outlineColor: 'primary.main' } }}
      >
        <UserAvatar name={user.name} />
      </ButtonBase>
      <Menu
        id="menu-conta"
        anchorEl={anchorRef.current}
        open={open}
        onClose={() => setOpen(false)}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
        slotProps={{ paper: { sx: { minWidth: 260, mt: 1 } } }}
      >
        <Stack direction="row" sx={{ gap: 1.5, alignItems: 'center', px: 2, py: 1.5 }}>
          <UserAvatar name={user.name} />
          <Stack sx={{ minWidth: 0 }}>
            <Typography variant="subtitle1" noWrap>
              {user.name}
            </Typography>
            <Typography variant="caption" color="text.secondary" noWrap>
              {user.roles.join(', ')}
            </Typography>
          </Stack>
        </Stack>
        <Divider />
        <MenuItem onClick={() => go('/perfil')}>
          <ListItemIcon>
            <User size={18} />
          </ListItemIcon>
          O meu perfil
        </MenuItem>
        <MenuItem onClick={() => go('/perfil?separador=dois-passos')}>
          <ListItemIcon>
            <ShieldTick size={18} />
          </ListItemIcon>
          Verificação em dois passos
        </MenuItem>
        <Divider />
        <MenuItem
          onClick={() => {
            setOpen(false);
            logout();
          }}
        >
          <ListItemIcon>
            <Logout size={18} />
          </ListItemIcon>
          Terminar sessão
        </MenuItem>
      </Menu>
    </Box>
  );
}
