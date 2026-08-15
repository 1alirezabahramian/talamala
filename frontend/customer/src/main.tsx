import './styles.css';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { AppOtpFlow } from './AppOtpFlow';

/**
 * Customer entry — OTP → register/auth flow already present.
 * Tenant via X-Talamala-Host (see api/client.ts). No client-side money math.
 */
const root = document.getElementById('root');
if (root) {
  createRoot(root).render(
    <StrictMode>
      <AppOtpFlow />
    </StrictMode>,
  );
}
