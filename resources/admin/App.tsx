import { RouterProvider } from 'react-router-dom';

// project-imports
import router from 'routes';
import ThemeCustomization from 'themes';
import { AuthProvider } from 'contexts/AuthContext';
import ScrollTop from 'components/ScrollTop';
import Snackbar from 'components/@extended/Snackbar';

// ==============================|| APP - TEMA E ROTAS ||============================== //

export default function App() {
  return (
    <ThemeCustomization>
      <ScrollTop>
        <AuthProvider>
          <RouterProvider router={router} />
          <Snackbar />
        </AuthProvider>
      </ScrollTop>
    </ThemeCustomization>
  );
}
