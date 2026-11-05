// third-party
import * as Yup from 'yup';

// ==============================|| VALIDAÇÕES PARTILHADAS ||============================== //

/** Mesma regra do Laravel (Password::defaults): pelo menos 8 caracteres. */
export const passwordSchema = Yup.string().min(8, 'Use pelo menos 8 caracteres.').required('Indique a palavra-passe.');

export const emailSchema = Yup.string().trim().email('Indique um e-mail válido.').max(190).required('Indique o e-mail.');
