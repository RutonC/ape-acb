// project-imports
import api, { errorMessage } from 'utils/axios';
import notify from 'utils/notify';

// ==============================|| DESCARREGAR FICHEIROS PRIVADOS ||============================== //
// Pede o ficheiro à API (com a sessão) e entrega-o ao browser; um erro vira aviso em vez de página em branco.

export async function downloadFile(url: string, fallbackName: string) {
  try {
    const response = await api.get<Blob>(url, { responseType: 'blob' });
    const disposition = String(response.headers['content-disposition'] ?? '');
    const name = /filename\*=UTF-8''([^;]+)/i.exec(disposition)?.[1] ?? /filename="?([^";]+)"?/i.exec(disposition)?.[1] ?? fallbackName;

    const href = URL.createObjectURL(response.data);
    const link = document.createElement('a');
    link.href = href;
    link.download = decodeURIComponent(name);
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(href);
  } catch (error) {
    // Com responseType blob, o corpo do erro também vem em Blob.
    notify.error(errorMessage(error, 'Não foi possível descarregar o ficheiro. Pode já não existir.'));
  }
}
