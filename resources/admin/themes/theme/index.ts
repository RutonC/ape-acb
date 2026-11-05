// project-imports
import Default from './default';
import { ThemeMode } from 'config';

// types
import { PresetColor } from 'types/config';
import { PaletteThemeProps } from 'types/theme';

// ==============================|| TEMA - SELECTOR ||============================== //
// Só existe o tema da marca APE.

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const Theme = (_presetColor: PresetColor, mode: ThemeMode): PaletteThemeProps => Default(mode);

export default Theme;
