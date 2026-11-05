import { ReactNode } from 'react';

// material-ui
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import FormHelperText from '@mui/material/FormHelperText';
import Stack from '@mui/material/Stack';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';

// third-party
import { FieldArray, getIn, useFormikContext } from 'formik';

// project-imports
import IconButton from 'components/@extended/IconButton';

// assets
import { Add, ArrowDown2, ArrowUp2, Trash } from 'iconsax-react';

// ==============================|| FORMULÁRIO - LISTA REPETÍVEL ||============================== //
// Itens com os mesmos campos (perguntas frequentes, contadores, passos...). Cada item
// pode ser movido para cima/baixo ou removido. `renderItem` recebe o prefixo do item
// ("faq.2") para os nomes dos campos.

interface Props<T> {
  name: string;
  label: ReactNode;
  itemLabel: (item: T, index: number) => ReactNode;
  newItem: () => T;
  renderItem: (prefix: string, index: number) => ReactNode;
  addLabel?: string;
  max?: number;
  helperText?: ReactNode;
}

export default function RepeaterField<T>({ name, label, itemLabel, newItem, renderItem, addLabel = 'Adicionar', max, helperText }: Props<T>) {
  const { values, errors, touched } = useFormikContext();
  const items: T[] = getIn(values, name) ?? [];
  const error = getIn(touched, name) && typeof getIn(errors, name) === 'string' ? getIn(errors, name) : undefined;

  return (
    <FieldArray name={name}>
      {({ push, remove, move }) => (
        <Stack sx={{ gap: 1.5 }}>
          <Typography variant="subtitle1">{label}</Typography>
          {helperText && (
            <Typography variant="caption" color="text.secondary" sx={{ mt: -1 }}>
              {helperText}
            </Typography>
          )}
          {items.map((item, index) => (
            <Box key={index} sx={{ border: '1px solid', borderColor: 'divider', borderRadius: 1.5 }}>
              <Stack direction="row" sx={{ alignItems: 'center', px: 2, py: 1, bgcolor: 'secondary.lighter', borderTopLeftRadius: 12, borderTopRightRadius: 12, gap: 1 }}>
                <Typography variant="subtitle2" sx={{ flexGrow: 1, minWidth: 0 }} noWrap>
                  {index + 1}. {itemLabel(item, index)}
                </Typography>
                <Tooltip title="Subir">
                  <span>
                    <IconButton size="small" color="secondary" disabled={index === 0} onClick={() => move(index, index - 1)} aria-label={`Subir item ${index + 1}`}>
                      <ArrowUp2 size={16} />
                    </IconButton>
                  </span>
                </Tooltip>
                <Tooltip title="Descer">
                  <span>
                    <IconButton
                      size="small"
                      color="secondary"
                      disabled={index === items.length - 1}
                      onClick={() => move(index, index + 1)}
                      aria-label={`Descer item ${index + 1}`}
                    >
                      <ArrowDown2 size={16} />
                    </IconButton>
                  </span>
                </Tooltip>
                <Tooltip title="Remover">
                  <IconButton size="small" color="error" onClick={() => remove(index)} aria-label={`Remover item ${index + 1}`}>
                    <Trash size={16} />
                  </IconButton>
                </Tooltip>
              </Stack>
              <Box sx={{ p: 2 }}>{renderItem(`${name}.${index}`, index)}</Box>
            </Box>
          ))}
          {error && <FormHelperText error>{error}</FormHelperText>}
          {(!max || items.length < max) && (
            <Box>
              <Button variant="outlined" startIcon={<Add />} onClick={() => push(newItem())}>
                {addLabel}
              </Button>
            </Box>
          )}
        </Stack>
      )}
    </FieldArray>
  );
}
