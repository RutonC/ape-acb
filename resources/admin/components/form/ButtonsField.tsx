import { ReactNode } from 'react';

// material-ui
import FormHelperText from '@mui/material/FormHelperText';
import Stack from '@mui/material/Stack';
import ToggleButton from '@mui/material/ToggleButton';
import ToggleButtonGroup from '@mui/material/ToggleButtonGroup';
import Typography from '@mui/material/Typography';

// third-party
import { useField } from 'formik';

// ==============================|| FORMULÁRIO - ESCOLHA EM BOTÕES ||============================== //

interface Props {
  name: string;
  label: ReactNode;
  options: { value: string | number; label: string }[];
  helperText?: ReactNode;
}

export default function ButtonsField({ name, label, options, helperText }: Props) {
  const [field, meta, helpers] = useField<string | number | null>(name);
  const error = meta.touched && meta.error ? String(meta.error) : undefined;
  const labelId = `rotulo-${name.replace(/\./g, '-')}`;

  return (
    <Stack sx={{ gap: 1 }}>
      <Typography variant="subtitle1" component="p" id={labelId}>
        {label}
      </Typography>
      <ToggleButtonGroup
        exclusive
        size="small"
        value={field.value === null || field.value === undefined ? null : String(field.value)}
        onChange={(_, value) => {
          if (value === null) return;
          const option = options.find((o) => String(o.value) === value);
          helpers.setValue(option ? option.value : value);
        }}
        aria-labelledby={labelId}
        sx={{ flexWrap: 'wrap' }}
      >
        {options.map((option) => (
          <ToggleButton key={String(option.value)} value={String(option.value)} sx={{ textTransform: 'none' }}>
            {option.label}
          </ToggleButton>
        ))}
      </ToggleButtonGroup>
      {(error || helperText) && <FormHelperText error={Boolean(error)}>{error ?? helperText}</FormHelperText>}
    </Stack>
  );
}
