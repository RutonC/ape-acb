// third-party
import { FormikHelpers, setIn } from 'formik';

// project-imports
import { errorMessage, fieldErrors } from 'utils/axios';
import notify from 'utils/notify';

// ==============================|| FORMULÁRIOS - ERROS DA API ||============================== //

interface Options {
  /** Traduz nomes da API para nomes do formulário (ex.: "recovery_code" -> "code"). */
  fieldMap?: Record<string, string>;
  /** Formulários longos (ex.: páginas com blocos): também mostra um aviso com o resumo dos erros. */
  nested?: boolean;
}

/**
 * Mostra os erros de validação (422) junto aos campos e os restantes num aviso.
 */
export function handleFormError<T>(error: unknown, helpers: FormikHelpers<T>, options: Options | Record<string, string> = {}) {
  const { fieldMap = {}, nested = false }: Options =
    'fieldMap' in options || 'nested' in options ? (options as Options) : { fieldMap: options as Record<string, string> };
  const fields = fieldErrors(error);
  const entries = Object.entries(fields);

  if (entries.length === 0) {
    notify.error(errorMessage(error));
    helpers.setSubmitting(false);
    return;
  }

  let errors: Record<string, unknown> = {};
  let touched: Record<string, unknown> = {};

  for (const [field, message] of entries) {
    // Caminho completo (ex.: "title.pt", "content.pt.2.data.title"), para o erro aparecer no campo.
    const name = fieldMap[field] ?? field;
    errors = setIn(errors, name, message);
    touched = setIn(touched, name, true);
  }

  helpers.setErrors(errors as never);
  helpers.setTouched(touched as never, false);

  // Em formulários longos o erro pode estar num bloco fechado ou noutro idioma.
  if (nested && entries.length > 0) {
    notify.error(
      entries.length === 1
        ? `Há um campo por corrigir: ${entries[0][1]}`
        : `Há ${entries.length} campos por corrigir. O primeiro: ${entries[0][1]}`
    );
  }

  helpers.setSubmitting(false);
}
