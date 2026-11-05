import { ReactNode } from 'react';

// material-ui
import FormHelperText from '@mui/material/FormHelperText';
import InputLabel from '@mui/material/InputLabel';
import Stack from '@mui/material/Stack';

// third-party
import { useField } from 'formik';

// project-imports
import RichTextEditor from './RichTextEditor';

// ==============================|| FORMULÁRIO - TEXTO FORMATADO ||============================== //

interface Props {
  name: string;
  label: ReactNode;
  required?: boolean;
  helperText?: ReactNode;
  placeholder?: string;
}

export default function RichTextField({ name, label, required, helperText, placeholder }: Props) {
  const [field, meta, helpers] = useField<string>(name);
  const error = meta.touched && meta.error ? meta.error : undefined;
  const labelId = `rotulo-${name.replace(/\./g, '-')}`;

  return (
    <Stack sx={{ gap: 1 }}>
      <InputLabel id={labelId} required={required} component="span">
        {label}
      </InputLabel>
      <RichTextEditor
        value={field.value ?? ''}
        onChange={(html) => helpers.setValue(html)}
        onBlur={() => helpers.setTouched(true)}
        error={Boolean(error)}
        placeholder={placeholder}
        ariaLabelledBy={labelId}
      />
      {(error || helperText) && (
        <FormHelperText error={Boolean(error)} sx={{ mt: -0.5 }}>
          {error ?? helperText}
        </FormHelperText>
      )}
    </Stack>
  );
}
