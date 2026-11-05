import { ReactNode } from 'react';

// material-ui
import Stack from '@mui/material/Stack';
import ToggleButton from '@mui/material/ToggleButton';
import ToggleButtonGroup from '@mui/material/ToggleButtonGroup';
import Typography from '@mui/material/Typography';

// third-party
import { getIn, useFormikContext } from 'formik';
import useSWR from 'swr';

// project-imports
import SelectField, { Option } from './SelectField';
import SwitchField from './SwitchField';
import TextField from './TextField';
import { fetcher } from 'utils/axios';

// ==============================|| FORMULÁRIO - LIGAÇÃO ||============================== //
// Formato lido por App\Support\Links::url():
// { type: 'route' | 'page' | 'url', route: 'services.show|dominios', page_id: 3, url: 'https://...', new_tab: false }
// As rotas internas resolvem-se no idioma da página, por isso a mesma ligação serve PT e EN.

export interface LinkValue {
  type: 'route' | 'page' | 'url';
  route?: string | null;
  page_id?: number | null;
  url?: string | null;
  new_tab?: boolean;
}

export const emptyLink = (): LinkValue => ({ type: 'route', route: null, page_id: null, url: '', new_tab: false });

interface Props {
  name: string;
  label: ReactNode;
  withNewTab?: boolean;
}

export default function LinkField({ name, label, withNewTab = true }: Props) {
  const { values, setFieldValue } = useFormikContext();
  const { data, isLoading } = useSWR<{ routes: Option[]; pages: Option[] }>('/links/options', fetcher);
  const type: LinkValue['type'] = getIn(values, `${name}.type`) ?? 'route';

  return (
    <Stack sx={{ gap: 1.5 }}>
      <Typography variant="subtitle1" component="p">
        {label}
      </Typography>
      <ToggleButtonGroup
        exclusive
        size="small"
        value={type}
        onChange={(_, value) => value && setFieldValue(`${name}.type`, value)}
        aria-label={typeof label === 'string' ? `Destino: ${label}` : 'Destino da ligação'}
      >
        <ToggleButton value="route">Página do site</ToggleButton>
        <ToggleButton value="page">Página do painel</ToggleButton>
        <ToggleButton value="url">Endereço (URL)</ToggleButton>
      </ToggleButtonGroup>

      {type === 'route' && <SelectField name={`${name}.route`} label="Página do site" options={data?.routes ?? []} loading={isLoading} />}
      {type === 'page' && <SelectField name={`${name}.page_id`} label="Página do painel" options={data?.pages ?? []} loading={isLoading} />}
      {type === 'url' && <TextField name={`${name}.url`} label="Endereço" placeholder="https://… ou /caminho" />}
      {withNewTab && <SwitchField name={`${name}.new_tab`} label="Abrir num novo separador" />}
    </Stack>
  );
}
