// ==============================|| TEXTOS DO PAINEL ||============================== //
// O painel é só em português: os títulos do menu já vêm traduzidos. Este componente
// substitui o FormattedMessage do react-intl usado pelos componentes do Able.

const messages: Record<string, string> = {
  home: 'Início',
  'more-items': 'Mais'
};

export default function FormattedMessage({ id }: { id?: string }) {
  if (!id) return null;

  return <>{messages[id] ?? id}</>;
}
