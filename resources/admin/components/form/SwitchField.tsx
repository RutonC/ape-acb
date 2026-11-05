import { ReactNode } from 'react';

// material-ui
import FormControlLabel from '@mui/material/FormControlLabel';
import FormHelperText from '@mui/material/FormHelperText';
import Stack from '@mui/material/Stack';
import Switch from '@mui/material/Switch';

// third-party
import { useField } from 'formik';

// ==============================|| FORMULÁRIO - INTERRUPTOR ||============================== //

interface Props {
  name: string;
  label: ReactNode;
  helperText?: ReactNode;
  disabled?: boolean;
}

export default function SwitchField({ name, label, helperText, disabled }: Props) {
  const [field, meta, helpers] = useField<boolean>(name);
  const error = meta.touched && meta.error ? meta.error : undefined;

  return (
    <Stack>
      <FormControlLabel
        control={<Switch checked={Boolean(field.value)} onChange={(_, checked) => helpers.setValue(checked)} disabled={disabled} />}
        label={label}
      />
      {(error || helperText) && <FormHelperText error={Boolean(error)}>{error ?? helperText}</FormHelperText>}
    </Stack>
  );
}
