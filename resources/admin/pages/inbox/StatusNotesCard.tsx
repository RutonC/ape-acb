// material-ui
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';

// third-party
import { Form, Formik } from 'formik';

// project-imports
import MainCard from 'components/MainCard';
import SelectField, { Option } from 'components/form/SelectField';
import TextField from 'components/form/TextField';
import api from 'utils/axios';
import { handleFormError } from 'utils/forms';
import notify from 'utils/notify';

// ==============================|| CAIXA DE ENTRADA - ESTADO E NOTAS INTERNAS ||============================== //

interface Props<T extends { status: string; notes?: string | null }> {
  endpoint: string;
  record: T;
  options: Option[];
  onSaved: (saved: T) => void;
}

export default function StatusNotesCard<T extends { status: string; notes?: string | null }>({
  endpoint,
  record,
  options,
  onSaved
}: Props<T>) {
  return (
    <MainCard title="Estado e notas">
      <Formik
        enableReinitialize
        initialValues={{ status: record.status, notes: record.notes ?? '' }}
        onSubmit={async (values, helpers) => {
          try {
            const { data } = await api.put<{ data: T }>(endpoint, values);
            notify.success('Guardado.');
            onSaved(data.data);
          } catch (error) {
            handleFormError(error, helpers);
          }
        }}
      >
        {({ dirty, isSubmitting }) => (
          <Form noValidate>
            <Stack sx={{ gap: 2.5 }}>
              <SelectField name="status" label="Estado" options={options} required />
              <TextField name="notes" label="Notas internas" multiline minRows={4} helperText="Só visíveis no painel." />
              <Button type="submit" variant="contained" disabled={!dirty || isSubmitting}>
                {isSubmitting ? 'A guardar…' : 'Guardar'}
              </Button>
            </Stack>
          </Form>
        )}
      </Formik>
    </MainCard>
  );
}
