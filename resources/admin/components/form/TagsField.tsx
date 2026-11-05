import { ReactNode, useState } from 'react';

// material-ui
import Autocomplete from '@mui/material/Autocomplete';
import Chip from '@mui/material/Chip';
import FormHelperText from '@mui/material/FormHelperText';
import InputLabel from '@mui/material/InputLabel';
import MuiTextField from '@mui/material/TextField';
import Stack from '@mui/material/Stack';

// third-party
import { useField } from 'formik';

// ==============================|| FORMULÁRIO - ETIQUETAS LIVRES ||============================== //
// Escrever e carregar em Enter (ou vírgula) para acrescentar.

interface Props {
  name: string;
  label: ReactNode;
  placeholder?: string;
  helperText?: ReactNode;
  suggestions?: string[];
}

export default function TagsField({ name, label, placeholder, helperText, suggestions = [] }: Props) {
  const [field, meta, helpers] = useField<string[]>(name);
  const values = field.value ?? [];
  const error = meta.touched && meta.error ? String(meta.error) : undefined;
  const id = `campo-${name.replace(/\./g, '-')}`;

  const [text, setText] = useState('');

  const add = (items: string[]) => helpers.setValue(Array.from(new Set(items.map((item) => item.trim()).filter(Boolean))));

  return (
    <Stack sx={{ gap: 1 }}>
      <InputLabel htmlFor={id}>{label}</InputLabel>
      <Autocomplete<string, true, false, true>
        id={id}
        multiple
        freeSolo
        options={suggestions.filter((item) => !values.includes(item))}
        value={values}
        inputValue={text}
        onInputChange={(_, next, reason) => {
          // Vírgula também acrescenta (ex.: colar "Laravel, React").
          if (reason === 'input' && next.includes(',')) {
            const parts = next.split(',');
            add([...values, ...parts.slice(0, -1)]);
            setText(parts[parts.length - 1].trimStart());
            return;
          }
          setText(next);
        }}
        onChange={(_, next) => add(next as string[])}
        onBlur={() => helpers.setTouched(true)}
        renderTags={(selected, getTagProps) =>
          selected.map((option, index) => {
            const { key, ...itemProps } = getTagProps({ index });
            return <Chip key={key} label={option} size="small" variant="light" color="primary" {...itemProps} />;
          })
        }
        renderInput={(params) => (
          <MuiTextField
            {...params}
            placeholder={values.length ? undefined : placeholder}
            error={Boolean(error)}
          />
        )}
      />
      {(error || helperText) && <FormHelperText error={Boolean(error)}>{error ?? helperText}</FormHelperText>}
    </Stack>
  );
}
