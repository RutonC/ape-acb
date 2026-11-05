import { ReactNode } from 'react';

// material-ui
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import FormHelperText from '@mui/material/FormHelperText';
import OutlinedInput from '@mui/material/OutlinedInput';
import Stack from '@mui/material/Stack';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';

// third-party
import { FieldArray, getIn, useFormikContext } from 'formik';

// project-imports
import IconButton from 'components/@extended/IconButton';

// assets
import { Add, ArrowDown2, ArrowUp2, Trash } from 'iconsax-react';

// ==============================|| FORMULÁRIO - LISTA DE TEXTOS ||============================== //
// Ex.: pontos de uma lista, o que inclui um plano. Guarda uma lista simples de textos.

interface Props {
  name: string;
  label: ReactNode;
  addLabel?: string;
  max?: number | null;
  helperText?: ReactNode;
}

export default function ListField({ name, label, addLabel = 'Adicionar', max, helperText }: Props) {
  const { values, errors, setFieldValue } = useFormikContext();
  const items: string[] = getIn(values, name) ?? [];
  const error = typeof getIn(errors, name) === 'string' ? getIn(errors, name) : undefined;

  return (
    <FieldArray name={name}>
      {({ push, remove, move }) => (
        <Stack sx={{ gap: 1 }}>
          <Typography variant="subtitle1" component="p">
            {label}
          </Typography>
          {items.map((item, index) => (
            <Stack key={index} direction="row" sx={{ gap: 0.5, alignItems: 'center' }}>
              <Typography variant="body2" color="text.secondary" sx={{ width: 20, textAlign: 'right' }}>
                {index + 1}.
              </Typography>
              <OutlinedInput
                value={item ?? ''}
                onChange={(event) => setFieldValue(`${name}.${index}`, event.target.value)}
                size="small"
                fullWidth
                inputProps={{ 'aria-label': `${typeof label === 'string' ? label : 'Item'} ${index + 1}` }}
              />
              <Tooltip title="Subir">
                <span>
                  <IconButton
                    size="small"
                    color="secondary"
                    disabled={index === 0}
                    onClick={() => move(index, index - 1)}
                    aria-label="Subir"
                  >
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
                    aria-label="Descer"
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
          ))}
          {error && <FormHelperText error>{error}</FormHelperText>}
          {helperText && !error && <FormHelperText>{helperText}</FormHelperText>}
          {(!max || items.length < max) && (
            <Box>
              <Button size="small" variant="outlined" startIcon={<Add size={16} />} onClick={() => push('')}>
                {addLabel}
              </Button>
            </Box>
          )}
        </Stack>
      )}
    </FieldArray>
  );
}
