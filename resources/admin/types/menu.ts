// material-ui
import { ChipProps } from '@mui/material/Chip';

// project-imports
import { GenericCardProps } from './root';
import { NavActionType } from 'config';

// ==============================|| TYPES - MENU  ||============================== //

type NavActionProps = {
  type: NavActionType;
  label: string;
  function?: any;
  url?: string;
  target?: boolean;
  icon: GenericCardProps['iconPrimary'] | string;
};

export type NavItemType = {
  breadcrumbs?: boolean;
  caption?: string;
  children?: NavItemType[];
  elements?: NavItemType[];
  chip?: ChipProps;
  color?: 'primary' | 'secondary' | 'default' | undefined;
  disabled?: boolean;
  external?: boolean;
  isDropdown?: boolean;
  icon?: GenericCardProps['iconPrimary'] | string;
  id?: string;
  link?: string;
  search?: string;
  target?: boolean;
  title?: string;
  type?: string;
  url?: string | undefined;
  actions?: NavActionProps[];
  /** Área do painel (permissão) necessária para ver o item. */
  area?: string;
  /** Contador da Caixa de entrada mostrado ao lado do item. */
  badge?: 'messages' | 'applications' | 'companies';
};

export type LinkTarget = '_blank' | '_self' | '_parent' | '_top';

export type MenuProps = {
  /**
   * Indicate if dashboard layout menu open or not
   */
  isDashboardDrawerOpened: boolean;

  /**
   * Indicate if component layout menu open or not
   */
  isComponentDrawerOpened: boolean;
};
