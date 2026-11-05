// material-ui
import Alert from '@mui/material/Alert';
import Box from '@mui/material/Box';
import Grid from '@mui/material/Grid2';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

// project-imports
import { itemDefaults, itemTitle } from './schema';
import RichTextField from 'components/editor/RichTextField';
import ButtonsField from 'components/form/ButtonsField';
import IconSelectField from 'components/form/IconSelectField';
import LinkField from 'components/form/LinkField';
import ListField from 'components/form/ListField';
import RepeaterField from 'components/form/RepeaterField';
import SelectField from 'components/form/SelectField';
import SwitchField from 'components/form/SwitchField';
import TextField from 'components/form/TextField';
import MediaPickerField from 'components/media/MediaPickerField';

// types
import { FieldDef } from 'types/pages';

// ==============================|| BLOCOS - CAMPOS (A PARTIR DA DEFINIÇÃO) ||============================== //
// Desenha os campos de um bloco segundo App\Support\Blocks\BlockSchema. `prefix` é o
// caminho no formulário (ex.: "content.pt.2.data").

const WIDTHS = { full: 12, half: 6, third: 4, quarter: 3 } as const;

function Field({ field, prefix }: { field: FieldDef; prefix: string }) {
  const name = `${prefix}.${field.name}`;
  const label = field.label;

  switch (field.type) {
    case 'text':
      return (
        <TextField
          name={name}
          label={label}
          required={field.required}
          placeholder={field.placeholder}
          helperText={field.help}
          type={field.input === 'url' ? 'url' : field.input === 'tel' ? 'tel' : 'text'}
          inputProps={field.max ? { maxLength: field.max } : undefined}
        />
      );
    case 'textarea':
      return (
        <TextField
          name={name}
          label={label}
          required={field.required}
          placeholder={field.placeholder}
          helperText={field.help}
          multiline
          minRows={field.rows ?? 3}
        />
      );
    case 'number':
      return <TextField name={name} label={label} required={field.required} helperText={field.help} type="number" />;
    case 'richtext':
      return <RichTextField name={name} label={label} required={field.required} helperText={field.help} />;
    case 'media':
      return <MediaPickerField name={name} label={label} required={field.required} helperText={field.help} accept={field.accept} />;
    case 'icon':
      return <IconSelectField name={name} label={label} helperText={field.help} />;
    case 'select':
      return (
        <SelectField
          name={name}
          label={label}
          options={field.options ?? []}
          placeholder={field.placeholder ?? 'Escolher'}
          helperText={field.help}
        />
      );
    case 'buttons':
      return <ButtonsField name={name} label={label} options={field.options ?? []} helperText={field.help} />;
    case 'toggle':
      return <SwitchField name={name} label={label} helperText={field.help} />;
    case 'link':
      return <LinkField name={name} label={label} withNewTab={field.with_new_tab ?? true} />;
    case 'button':
      return (
        <Box sx={{ border: '1px solid', borderColor: 'divider', borderRadius: 1.5, p: 2 }}>
          <Stack sx={{ gap: 2 }}>
            <Typography variant="subtitle1">{label}</Typography>
            <TextField name={`${name}.label`} label="Texto do botão" helperText="Vazio esconde o botão." />
            <LinkField name={name} label="Destino" withNewTab={field.with_new_tab ?? true} />
          </Stack>
        </Box>
      );
    case 'list':
      return <ListField name={name} label={label} addLabel={field.add_label} max={field.max_items} helperText={field.help} />;
    case 'repeater':
      return (
        <RepeaterField<Record<string, unknown>>
          name={name}
          label={label}
          helperText={field.help}
          addLabel={field.add_label}
          max={field.max_items ?? undefined}
          itemLabel={(item) => itemTitle(item, field.item_label) || 'Novo item'}
          newItem={() => itemDefaults(field)}
          renderItem={(itemPrefix) => <BlockFields fields={field.fields ?? []} prefix={itemPrefix} />}
        />
      );
    default:
      return null;
  }
}

export default function BlockFields({ fields, prefix }: { fields: FieldDef[]; prefix: string }) {
  return (
    <Grid container spacing={2.5}>
      {fields.map((field, index) => {
        if (field.type === 'note') {
          return (
            <Grid key={index} size={12}>
              <Alert severity="info">{field.label}</Alert>
            </Grid>
          );
        }

        if (field.type === 'group') {
          return (
            <Grid key={index} size={12}>
              <Box component="fieldset" sx={{ border: '1px solid', borderColor: 'divider', borderRadius: 1.5, p: 2, m: 0 }}>
                <Typography component="legend" variant="subtitle1" sx={{ px: 0.5 }}>
                  {field.label}
                </Typography>
                <BlockFields fields={field.fields ?? []} prefix={prefix} />
              </Box>
            </Grid>
          );
        }

        const width = WIDTHS[field.width ?? 'full'];
        return (
          <Grid key={field.name ?? index} size={{ xs: 12, sm: width === 3 ? 6 : width, md: width }}>
            <Field field={field} prefix={prefix} />
          </Grid>
        );
      })}
    </Grid>
  );
}
