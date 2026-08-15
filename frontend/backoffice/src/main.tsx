import './styles.css';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { AppBackoffice } from './AppBackoffice';

const root = document.getElementById('root');
if (root) {
  createRoot(root).render(
    <StrictMode>
      <AppBackoffice />
    </StrictMode>,
  );
}
