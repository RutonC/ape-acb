// ==============================|| DATAS ||============================== //

/** "26/10/2026, 14:30" (hora local do browser). */
export function formatDateTime(value: string | null | undefined): string {
  if (!value) return '';
  const date = new Date(value);

  return Number.isNaN(date.getTime()) ? '' : date.toLocaleString('pt-PT', { dateStyle: 'short', timeStyle: 'short' });
}

/** "26/10/2026" a partir de "AAAA-MM-DD" ou ISO 8601. */
export function formatDate(value: string | null | undefined): string {
  if (!value) return '';
  const date = new Date(value.length === 10 ? `${value}T00:00:00` : value);

  return Number.isNaN(date.getTime()) ? '' : date.toLocaleDateString('pt-PT');
}
