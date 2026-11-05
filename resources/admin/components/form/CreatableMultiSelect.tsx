import { ReactNode, useState } from 'react';

// material-ui
import Autocomplete, { createFilterOptions } from '@mui/material/Autocomplete';
import Chip from '@mui/material/Chip';
import FormHelperText from '@mui/material/FormHelperText';
import InputLabel from '@mui/material/InputLabel';
import MuiTextField from '@mui/material/TextField';
import Stack from '@mui/material/Stack';

// third-party
import { useField } from 'formik';

// project-imports
import { errorMessage } from 'utils/axios';
import notify from 'utils/notify';

// ==============================|| FORMULÁRIO - ESCOLHA MÚLTIPLA COM CRIAÇÃO ||============================== //
// Ex.: etiquetas de um artigo. Escrever um nome que não existe oferece "Criar «nome»".

export interface CreatableOption {
  value: number;
  label: string;
  /** Opção "Criar «...»" (não é um registo). */
  create?: string;
}

interface Props {
  name: string;
  label: ReactNode;
  options: CreatableOption[];
  /** Cria o registo e devolve a nova opção. */
  onCreate: (label: string) => Promise<CreatableOption>;
  loading?: boolean;
  helperText?: ReactNode;
  placeholder?: string;
}

const filter = createFilterOptions<CreatableOption>();

export default function CreatableMultiSelect({ name, label, options, onCreate, loading, helperText, placeholder = 'Escolher ou escrever para criar' }: Props) {
  const [field, meta, helpers] = useField<number[]>(name);
  const [creating, setCreating] = useState(false);
  const ids = field.value ?? [];
  const selected = ids.map((id) => options.find((option) => option.value === id) ?? { value: id, label: `#${id}` });
  const error = meta.touched && meta.error ? String(meta.error) : undefined;
  const id = `campo-${name.replace(/\./g, '-')}`;

  return (
    <Stack sx={{ gap: 1 }}>
      <InputLabel htmlFor={id}>{label}</InputLabel>
      <Autocomplete<CreatableOption, true>
        id={id}
        multiple
        options={options}
        value={selected}
        loading={loading || creating}
        loadingText="A carregar…"
        noOptionsText="Sem opções"
        disableCloseOnSelect
        getOptionLabel={(option) => option.label}
        isOptionEqualToValue={(option, value) => option.value === value.value}
        filterOptions={(all, params) => {
          const filtered = filter(all, params);
          const text = params.inputValue.trim();
          if (text && !all.some((option) => option.label.toLowerCase() === text.toLowerCase())) {
            filtered.push({ value: -1, label: `Criar «${text}»`, create: text });
          }
          return filtered;
        }}
        onChange={async (_, next) => {
          const creatable = next.find((option) => option.create);
          if (!creatable) {
            helpers.setValue(next.map((option) => option.value));
            return;
          }
          setCreating(true);
          try {
            const created = await onCreate(creatable.create!);
            helpers.setValue([...next.filter((option) => !option.create).map((option) => option.value), created.value]);
            notify.success(`«${created.label}» criada.`);
          } catch (err) {
            notify.error(errorMessage(err));
          } finally {
            setCreating(false);
          }
        }}
        onBlur={() => helpers.setTouched(true)}
        renderTags={(values, getTagProps) =>
          values.map((option, index) => {
            const { key, ...tagProps } = getTagProps({ index });
            return <Chip key={key} label={option.label} size="small" variant="light" color="primary" {...tagProps} />;
          })
        }
        renderInput={(params) => <MuiTextField {...params} placeholder={ids.length ? undefined : placeholder} error={Boolean(error)} />}
      />
      {(error || helperText) && <FormHelperText error={Boolean(error)}>{error ?? helperText}</FormHelperText>}
    </Stack>
  );
}
