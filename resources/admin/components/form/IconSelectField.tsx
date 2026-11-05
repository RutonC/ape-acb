import { ReactNode } from 'react';

// material-ui
import Autocomplete from '@mui/material/Autocomplete';
import Box from '@mui/material/Box';
import FormHelperText from '@mui/material/FormHelperText';
import InputAdornment from '@mui/material/InputAdornment';
import InputLabel from '@mui/material/InputLabel';
import MuiTextField from '@mui/material/TextField';
import Stack from '@mui/material/Stack';

// third-party
import { useField } from 'formik';
import useSWR from 'swr';

// project-imports
import { fetcher } from 'utils/axios';

// ==============================|| FORMULÁRIO - ÍCONE ||============================== //

interface IconOption {
  value: string;
  label: string;
  svg: string;
}

function IconPreview({ svg }: { svg: string }) {
  return <Box component="span" aria-hidden sx={{ display: 'inline-flex', width: 22, height: 22, color: 'primary.main', '& svg': { width: 22, height: 22 } }} dangerouslySetInnerHTML={{ __html: svg }} />;
}

export default function IconSelectField({ name, label, helperText }: { name: string; label: ReactNode; helperText?: ReactNode }) {
  const [field, meta, helpers] = useField<string | null>(name);
  const { data, isLoading } = useSWR<{ data: IconOption[] }>('/options/icons', fetcher, { revalidateOnFocus: false });
  const options = data?.data ?? [];
  const value = options.find((option) => option.value === field.value) ?? null;
  const error = meta.touched && meta.error ? meta.error : undefined;
  const id = `campo-${name.replace(/\./g, '-')}`;

  return (
    <Stack sx={{ gap: 1 }}>
      <InputLabel htmlFor={id}>{label}</InputLabel>
      <Autocomplete<IconOption>
        id={id}
        options={options}
        value={value}
        loading={isLoading}
        loadingText="A carregar…"
        noOptionsText="Sem ícones"
        getOptionLabel={(option) => option.label}
        isOptionEqualToValue={(option, selected) => option.value === selected.value}
        onChange={(_, selected) => helpers.setValue(selected?.value ?? null)}
        onBlur={() => helpers.setTouched(true)}
        renderOption={({ key, ...props }, option) => (
          <Box component="li" key={key} {...props} sx={{ gap: 1.5 }}>
            <IconPreview svg={option.svg} />
            {option.label}
          </Box>
        )}
        renderInput={(params) => (
          <MuiTextField
            {...params}
            placeholder="Sem ícone"
            error={Boolean(error)}
            slotProps={{
              input: {
                ...params.InputProps,
                startAdornment: value ? (
                  <InputAdornment position="start">
                    <IconPreview svg={value.svg} />
                  </InputAdornment>
                ) : null
              }
            }}
          />
        )}
      />
      {(error || helperText) && <FormHelperText error={Boolean(error)}>{error ?? helperText}</FormHelperText>}
    </Stack>
  );
}
