import { createRoot } from 'react-dom/client';

// fontes da marca: Outfit (títulos) e Urbanist (texto)
import '@fontsource/outfit/500.css';
import '@fontsource/outfit/600.css';
import '@fontsource/outfit/700.css';
import '@fontsource/urbanist/400.css';
import '@fontsource/urbanist/500.css';
import '@fontsource/urbanist/600.css';
import '@fontsource/urbanist/700.css';

import 'simplebar-react/dist/simplebar.min.css';

// project-imports
import App from './App';
import { ConfigProvider } from 'contexts/ConfigContext';

// ==============================|| PAINEL APE - ARRANQUE ||============================== //

createRoot(document.getElementById('admin')!).render(
  <ConfigProvider>
    <App />
  </ConfigProvider>
);
