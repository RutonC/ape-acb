import { ReactNode, useState } from 'react';

// material-ui
import FormHelperText from '@mui/material/FormHelperText';
import InputAdornment from '@mui/material/InputAdornment';
import InputLabel from '@mui/material/InputLabel';
import OutlinedInput, { OutlinedInputProps } from '@mui/material/OutlinedInput';
import Stack from '@mui/material/Stack';

// third-party
import { useField } from 'formik';

// project-imports
import IconButton from 'components/@extended/IconButton';

// assets
import { Eye, EyeSlash } from 'iconsax-react';

// ==============================|| FORMULÁRIO - CAMPO DE TEXTO ||============================== //

type Props = Omit<OutlinedInputProps, 'name' | 'value' | 'onChange' | 'onBlur' | 'error'> & {
  name: string;
  label: ReactNode;
  helperText?: ReactNode;
};

export default function TextField({ name, label, helperText, type = 'text', id, ...other }: Props) {
  const [field, meta] = useField<string>(name);
  const [visible, setVisible] = useState(false);
  const inputId = id ?? `campo-${name}`;
  const error = meta.touched && meta.error ? meta.error : undefined;
  const isPassword = type === 'password';

  return (
    <Stack sx={{ gap: 1 }}>
      <InputLabel htmlFor={inputId} required={other.required}>
        {label}
      </InputLabel>
      <OutlinedInput
        {...other}
        {...field}
        value={field.value ?? ''}
        id={inputId}
        type={isPassword && visible ? 'text' : type}
        fullWidth
        error={Boolean(error)}
        aria-describedby={error || helperText ? `${inputId}-ajuda` : undefined}
        endAdornment={
          isPassword ? (
            <InputAdornment position="end">
              <IconButton
                aria-label={visible ? 'Esconder palavra-passe' : 'Mostrar palavra-passe'}
                onClick={() => setVisible((value) => !value)}
                onMouseDown={(event) => event.preventDefault()}
                edge="end"
                color="secondary"
              >
                {visible ? <Eye /> : <EyeSlash />}
              </IconButton>
            </InputAdornment>
          ) : (
            other.endAdornment
          )
        }
      />
      {(error || helperText) && (
        <FormHelperText id={`${inputId}-ajuda`} error={Boolean(error)} sx={{ mt: -0.5 }}>
          {error ?? helperText}
        </FormHelperText>
      )}
    </Stack>
  );
}
