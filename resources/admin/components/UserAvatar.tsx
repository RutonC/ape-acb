// material-ui
import Avatar, { AvatarProps } from '@mui/material/Avatar';

// ==============================|| AVATAR COM INICIAIS ||============================== //

export function initials(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return '?';
  const first = parts[0][0];
  const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
  return (first + last).toUpperCase();
}

export default function UserAvatar({ name, sx, ...other }: AvatarProps & { name: string }) {
  return (
    <Avatar
      {...other}
      alt=""
      sx={[{ bgcolor: 'primary.lighter', color: 'primary.dark', fontWeight: 600, fontSize: '0.875rem', width: 36, height: 36 }, ...(Array.isArray(sx) ? sx : [sx])]}
    >
      {initials(name)}
    </Avatar>
  );
}
