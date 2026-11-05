import { ReactNode } from 'react';

// material-ui
import FormHelperText from '@mui/material/FormHelperText';
import InputLabel from '@mui/material/InputLabel';
import OutlinedInput from '@mui/material/OutlinedInput';
import Stack from '@mui/material/Stack';

// third-party
import { useField } from 'formik';

// ==============================|| FORMULÁRIO - DATA (E HORA) ||============================== //
// Campo nativo do browser. O valor no formulário é o da API (ISO 8601 ou AAAA-MM-DD);
// ao gravar envia-se "AAAA-MM-DDTHH:mm" na hora local, que o Laravel interpreta no fuso da aplicação.

interface Props {
  name: string;
  label: ReactNode;
  withTime?: boolean;
  required?: boolean;
  helperText?: ReactNode;
}

function toInput(value: string | null | undefined, withTime: boolean): string {
  if (!value) return '';
  if (!withTime) return value.slice(0, 10);

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value.slice(0, 16);

  const pad = (n: number) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export default function DateTimeField({ name, label, withTime = true, required, helperText }: Props) {
  const [field, meta, helpers] = useField<string | null>(name);
  const error = meta.touched && meta.error ? meta.error : undefined;
  const id = `campo-${name.replace(/\./g, '-')}`;

  return (
    <Stack sx={{ gap: 1 }}>
      <InputLabel htmlFor={id} required={required}>
        {label}
      </InputLabel>
      <OutlinedInput
        id={id}
        type={withTime ? 'datetime-local' : 'date'}
        value={toInput(field.value, withTime)}
        onChange={(event) => helpers.setValue(event.target.value || null)}
        onBlur={() => helpers.setTouched(true)}
        error={Boolean(error)}
        fullWidth
      />
      {(error || helperText) && (
        <FormHelperText error={Boolean(error)} sx={{ mt: -0.5 }}>
          {error ?? helperText}
        </FormHelperText>
      )}
    </Stack>
  );
}
