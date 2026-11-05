import { forwardRef, CSSProperties, ReactNode, ReactElement } from 'react';

// material-ui
import Card, { CardProps } from '@mui/material/Card';
import CardContent, { CardContentProps } from '@mui/material/CardContent';
import CardHeader, { CardHeaderProps } from '@mui/material/CardHeader';
import Divider from '@mui/material/Divider';
import Typography from '@mui/material/Typography';

// project-imports
import useConfig from 'hooks/useConfig';

// types
import { KeyedObject } from 'types/root';

// header style
const headerSX = { p: 2.5, '& .MuiCardHeader-action': { m: '0px auto', alignSelf: 'center' } };

export interface MainCardProps extends KeyedObject {
  border?: boolean;
  boxShadow?: boolean;
  children?: ReactElement;
  subheader?: ReactElement | string;
  style?: CSSProperties;
  content?: boolean;
  contentSX?: CardContentProps['sx'];
  darkTitle?: boolean;
  divider?: boolean;
  sx?: CardProps['sx'];
  secondary?: CardHeaderProps['action'];
  shadow?: string;
  elevation?: number;
  title?: ReactNode | string;
  modal?: boolean;
}

// ==============================|| CUSTOM - MAIN CARD ||============================== //

const MainCard = forwardRef<HTMLDivElement, MainCardProps>(function MainCard(
  {
    border = true,
    boxShadow = true,
    children,
    subheader,
    content = true,
    contentSX = {},
    darkTitle,
    divider = true,
    elevation,
    secondary,
    shadow,
    sx = {},
    title,
    modal = false,
    ...others
  },
  ref
) {
  const { themeContrast } = useConfig();

  return (
    <Card
      elevation={elevation || 0}
      ref={ref}
      {...others}
      sx={[
        (theme) => ({
          position: 'relative',
          border: border ? '1px solid' : 'none',
          borderRadius: 1.5,
          borderColor: theme.palette.divider,
          ...(((themeContrast && boxShadow) || shadow) && { boxShadow: shadow ? shadow : theme.customShadows.z1 }),
          ...(modal && {
            position: 'absolute' as 'absolute',
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%)',
            width: { xs: `calc( 100% - 50px)`, sm: 'auto' },
            '& .MuiCardContent-root': { overflowY: 'auto', minHeight: 'auto', maxHeight: `calc(100vh - 200px)` }
          })
        }),
        sx
      ]}
    >
      {/* card header and action */}
      {!darkTitle && title && (
        <CardHeader sx={headerSX} title={title} action={secondary} subheader={subheader} slotProps={{ title: { variant: 'subtitle1' } }} />
      )}
      {darkTitle && title && <CardHeader sx={headerSX} title={<Typography variant="h4">{title}</Typography>} action={secondary} />}

      {/* content & header divider */}
      {title && divider && <Divider />}

      {/* card content */}
      {content && <CardContent sx={contentSX}>{children}</CardContent>}
      {!content && children}

    </Card>
  );
});

export default MainCard;
