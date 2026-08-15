import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

/**
 * Thin Vite for customer shell.
 * API origin defaults to same host in production; local uses 127.0.0.1:8080 via client.ts.
 * No financial calculation in frontend — opaque decimal strings from backend only.
 */
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5174,
    proxy: {
      '/v1': 'http://127.0.0.1:8080',
      '/healthz': 'http://127.0.0.1:8080',
      '/readyz': 'http://127.0.0.1:8080',
    },
  },
  build: {
    outDir: 'dist',
    sourcemap: true,
    emptyOutDir: true,
  },
});
