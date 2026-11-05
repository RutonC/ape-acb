import { createContext, ReactNode, useCallback, useContext, useEffect, useMemo, useState } from 'react';

// material-ui
import Badge from '@mui/material/Badge';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import Tab from '@mui/material/Tab';
import Tabs from '@mui/material/Tabs';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';

// third-party
import { getIn, useFormikContext } from 'formik';

// project-imports
import TextField from './TextField';
import RichTextField from 'components/editor/RichTextField';
import notify from 'utils/notify';

// assets
import { LanguageSquare } from 'iconsax-react';

// ==============================|| CAMPOS TRADUZÍVEIS (PT / EN) ||============================== //
// Os campos traduzíveis guardam { pt: '...', en: '...' } (spatie/laravel-translatable).
// O formulário inteiro alterna entre idiomas nos separadores; o separador EN mostra
// quantos campos têm texto em português e ainda não têm versão inglesa.

export type Locale = 'pt' | 'en';

// A APE publica só em português. Para activar o inglês, acrescentar { code: 'en', label: 'Inglês' }
// aqui e definir SITE_LOCALES=pt,en no servidor.
export const LOCALES: readonly { code: Locale; label: string }[] = [{ code: 'pt', label: 'Português' }];

export const DEFAULT_LOCALE: Locale = 'pt';

/** Valor vazio para um campo traduzível. */
export const emptyTranslations = (): Record<Locale, string> => ({ pt: '', en: '' });

/** Normaliza o que vem da API (pode faltar um idioma ou vir null). */
export function translations(value: Partial<Record<string, string | null>> | null | undefined): Record<Locale, string> {
  return { pt: value?.pt ?? '', en: value?.en ?? '' };
}

const hasText = (value: unknown): boolean =>
  Array.isArray(value) ? value.length > 0 : typeof value === 'string' && (value.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim() !== '' || value.includes('<img'));

interface ContextValue {
  locale: Locale;
  setLocale: (locale: Locale) => void;
  register: (name: string) => () => void;
  fields: string[];
}

const TranslatableContext = createContext<ContextValue | null>(null);

export function TranslatableProvider({ children }: { children: ReactNode }) {
  const [locale, setLocale] = useState<Locale>(DEFAULT_LOCALE);
  const [fields, setFields] = useState<string[]>([]);

  const register = useCallback((name: string) => {
    setFields((current) => (current.includes(name) ? current : [...current, name]));
    return () => setFields((current) => current.filter((field) => field !== name));
  }, []);

  const value = useMemo(() => ({ locale, setLocale, register, fields }), [locale, register, fields]);

  return <TranslatableContext.Provider value={value}>{children}</TranslatableContext.Provider>;
}

function useTranslatable(name?: string) {
  const context = useContext(TranslatableContext);
  if (!context) throw new Error('Campos traduzíveis precisam do TranslatableProvider');

  const { register } = context;
  useEffect(() => (name ? register(name) : undefined), [name, register]);

  return context;
}

/**
 * Para campos traduzíveis feitos à medida (ex.: lista repetível por idioma): regista o
 * campo na contagem do que falta traduzir e devolve o idioma em edição.
 */
export function useTranslatableField(name: string): Locale {
  return useTranslatable(name).locale;
}

/** Idioma que está a ser editado (sem registar nenhum campo). */
export function useEditingLocale(): Locale {
  return useTranslatable().locale;
}

/** Separadores de idioma + "Copiar PT para EN" (só preenche campos ingleses vazios). */
export function LocaleTabs({ extra }: { extra?: ReactNode }) {
  const { locale, setLocale, fields } = useTranslatable();
  const { values, setFieldValue } = useFormikContext();

  const missing = fields.filter((field) => hasText(getIn(values, `${field}.pt`)) && !hasText(getIn(values, `${field}.en`)));

  const multiple = LOCALES.length > 1;

  const copy = () => {
    for (const field of missing) setFieldValue(`${field}.en`, structuredClone(getIn(values, `${field}.pt`)));
    notify.info('Texto português copiado para os campos em inglês vazios. Reveja e traduza antes de guardar.');
    setLocale('en');
  };

  return (
    <Stack direction={{ xs: 'column', sm: 'row' }} sx={{ alignItems: { sm: 'center' }, justifyContent: 'space-between', gap: 1, borderBottom: 1, borderColor: 'divider' }}>
      {multiple ? (
      <Tabs value={locale} onChange={(_, value) => setLocale(value)} aria-label="Idioma do conteúdo">
        {LOCALES.map(({ code, label }) => (
          <Tab
            key={code}
            value={code}
            label={
              code === 'en' && missing.length > 0 ? (
                <Tooltip title={`${missing.length} ${missing.length === 1 ? 'campo' : 'campos'} por traduzir`}>
                  <Badge color="warning" badgeContent={missing.length} sx={{ '& .MuiBadge-badge': { right: -14 } }}>
                    {label}
                  </Badge>
                </Tooltip>
              ) : (
                label
              )
            }
            sx={{ mr: code === 'en' && missing.length > 0 ? 2 : 0 }}
          />
        ))}
      </Tabs>
      ) : (
        <span />
      )}
      <Stack direction="row" sx={{ gap: 1, pb: { xs: 1, sm: 0 } }}>
        {extra}
        {multiple && missing.length > 0 && (
          <Button size="small" color="secondary" startIcon={<LanguageSquare size={18} />} onClick={copy}>
            Copiar PT para EN
          </Button>
        )}
      </Stack>
    </Stack>
  );
}

/** Mostra o texto português como referência quando se está a traduzir. */
function SourceHint({ name }: { name: string }) {
  const { values } = useFormikContext();
  const source = getIn(values, `${name}.pt`);

  if (!hasText(source)) return null;

  const text = String(source).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

  return (
    <Box component="span">
      Em português: <em>{text.length > 140 ? `${text.slice(0, 140)}…` : text}</em>
    </Box>
  );
}

interface FieldProps {
  name: string;
  label: string;
  required?: boolean;
  multiline?: boolean;
  rows?: number;
  helperText?: ReactNode;
  placeholder?: string;
}

export function TranslatableTextField({ name, label, required, multiline, rows = 3, helperText, placeholder }: FieldProps) {
  const { locale } = useTranslatable(name);
  const isDefault = locale === DEFAULT_LOCALE;

  return (
    <TextField
      key={`${name}.${locale}`}
      name={`${name}.${locale}`}
      label={label}
      required={required && isDefault}
      multiline={multiline}
      minRows={multiline ? rows : undefined}
      placeholder={placeholder}
      helperText={isDefault ? helperText : <SourceHint name={name} />}
    />
  );
}

export function TranslatableRichTextField({ name, label, required, helperText }: FieldProps) {
  const { locale } = useTranslatable(name);
  const isDefault = locale === DEFAULT_LOCALE;

  return (
    <Stack sx={{ gap: 0.5 }}>
      <RichTextField key={`${name}.${locale}`} name={`${name}.${locale}`} label={label} required={required && isDefault} helperText={helperText} />
      {!isDefault && (
        <Typography variant="caption" color="text.secondary">
          <SourceHint name={name} />
        </Typography>
      )}
    </Stack>
  );
}
