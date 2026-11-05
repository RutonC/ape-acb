import { ReactNode } from 'react';

// material-ui
import Autocomplete from '@mui/material/Autocomplete';
import FormHelperText from '@mui/material/FormHelperText';
import InputLabel from '@mui/material/InputLabel';
import MuiTextField from '@mui/material/TextField';
import Stack from '@mui/material/Stack';

// third-party
import { useField } from 'formik';

// ==============================|| FORMULÁRIO - ESCOLHA (COM PESQUISA) ||============================== //

export interface Option {
  value: string | number;
  label: string;
}

interface Props {
  name: string;
  label: ReactNode;
  options: Option[];
  multiple?: boolean;
  required?: boolean;
  placeholder?: string;
  helperText?: ReactNode;
  disabled?: boolean;
  loading?: boolean;
}

export default function SelectField({ name, label, options, multiple = false, required, placeholder = 'Escolher', helperText, disabled, loading }: Props) {
  const [field, meta, helpers] = useField<string | number | null | Array<string | number>>(name);
  const error = meta.touched && meta.error ? String(meta.error) : undefined;
  const id = `campo-${name.replace(/\./g, '-')}`;

  const byValue = (value: unknown) => options.find((option) => String(option.value) === String(value)) ?? null;
  const value = multiple
    ? ((Array.isArray(field.value) ? field.value : []).map(byValue).filter(Boolean) as Option[])
    : byValue(field.value);

  return (
    <Stack sx={{ gap: 1 }}>
      <InputLabel htmlFor={id} required={required}>
        {label}
      </InputLabel>
      <Autocomplete<Option, boolean>
        id={id}
        multiple={multiple}
        options={options}
        value={value as never}
        disabled={disabled}
        loading={loading}
        loadingText="A carregar…"
        noOptionsText="Sem opções"
        getOptionLabel={(option) => option.label}
        isOptionEqualToValue={(option, selected) => String(option.value) === String(selected.value)}
        onChange={(_, selected) => {
          helpers.setValue(
            multiple ? ((selected as Option[]) ?? []).map((option) => option.value) : ((selected as Option | null)?.value ?? null)
          );
        }}
        onBlur={() => helpers.setTouched(true)}
        renderInput={(params) => <MuiTextField {...params} placeholder={placeholder} error={Boolean(error)} />}
      />
      {(error || helperText) && (
        <FormHelperText error={Boolean(error)} sx={{ mt: -0.5 }}>
          {error ?? helperText}
        </FormHelperText>
      )}
    </Stack>
  );
}
