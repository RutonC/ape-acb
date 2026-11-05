import { useEffect, useRef } from 'react';

// material-ui
import InputAdornment from '@mui/material/InputAdornment';

// third-party
import { getIn, useFormikContext } from 'formik';

// project-imports
import TextField from './TextField';
import { slugify } from 'utils/slug';

// ==============================|| FORMULÁRIO - ENDEREÇO (SLUG) ||============================== //
// Ao criar, acompanha o título português até a pessoa mexer no endereço.

interface Props {
  name?: string;
  label?: string;
  /** Campo de onde gerar (ex.: "title.pt"). */
  source: string;
  prefix: string;
  /** Só gera automaticamente em registos novos. */
  auto: boolean;
  helperText?: string;
}

export default function SlugField({ name = 'slug', label = 'Endereço (URL)', source, prefix, auto, helperText }: Props) {
  const { values, setFieldValue } = useFormikContext();
  const sourceValue: string = getIn(values, source) ?? '';
  const value: string = getIn(values, name) ?? '';
  const touchedByUser = useRef(!auto || value !== '');
  const lastGenerated = useRef(value);

  useEffect(() => {
    if (!auto) return;
    if (value !== lastGenerated.current) touchedByUser.current = true;
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [value]);

  useEffect(() => {
    if (!auto || touchedByUser.current) return;
    const generated = slugify(sourceValue);
    lastGenerated.current = generated;
    setFieldValue(name, generated, false);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [sourceValue]);

  return (
    <TextField
      name={name}
      label={label}
      required
      helperText={helperText ?? 'Letras minúsculas, números e hífenes. Mudar o endereço quebra ligações antigas.'}
      startAdornment={
        <InputAdornment position="start" sx={{ mr: 0 }}>
          {prefix}
        </InputAdornment>
      }
      inputProps={{ spellCheck: false, autoCapitalize: 'none' }}
    />
  );
}
