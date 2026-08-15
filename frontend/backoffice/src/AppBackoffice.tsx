/**
 * Staff entry: login → (optional rotate) → registration queue.
 * No financial policy UI. No Kimia write. Tenant via X-Talamala-Host.
 */

import { useState } from 'react';
import { staffLogin, staffRotatePassword, logout } from './api/auth';
import { RegistrationQueueScreen } from './screens/RegistrationQueueScreen';

type Phase =
  | { name: 'login' }
  | { name: 'rotate'; staffId: string; token?: string }
  | { name: 'queue'; staffId: string; token: string };

export function AppBackoffice() {
  const [phase, setPhase] = useState<Phase>({ name: 'login' });
  const [username, setUsername] = useState('operator');
  const [password, setPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function onLogin(e: React.FormEvent) {
    e.preventDefault();
    setBusy(true);
    setError(null);
    const res = await staffLogin(username, password);
    setBusy(false);
    if (!res.ok) {
      setError(res.error || 'login_failed');
      return;
    }
    const staffId = res.data.staff_id;
    const token = res.data.access_token ?? '';
    if (res.data.must_change_password) {
      setPhase({ name: 'rotate', staffId, token: token || undefined });
      return;
    }
    if (!token) {
      setError('token_missing');
      return;
    }
    setPhase({ name: 'queue', staffId, token });
  }

  async function onRotate(e: React.FormEvent) {
    e.preventDefault();
    if (phase.name !== 'rotate') return;
    setBusy(true);
    setError(null);
    const res = await staffRotatePassword(phase.staffId, password, newPassword, phase.token);
    setBusy(false);
    if (!res.ok) {
      setError(res.error || 'rotate_failed');
      return;
    }
    // Re-login after rotate for a clean token
    const login = await staffLogin(username, newPassword);
    if (!login.ok || !login.data.access_token) {
      setError('relogin_failed');
      setPhase({ name: 'login' });
      return;
    }
    setPassword(newPassword);
    setPhase({ name: 'queue', staffId: login.data.staff_id, token: login.data.access_token });
  }

  if (phase.name === 'queue') {
    return (
      <div style={{ maxWidth: 640, margin: '1.5rem auto', padding: '0 1rem' }}>
        <header style={{ marginBottom: '1rem', display: 'flex', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap' }}>
          <div>
            <h1 style={{ fontSize: '1.25rem', margin: 0 }}>صف ثبت‌نام</h1>
            <p style={{ color: '#9aa4b2', fontSize: '0.9rem' }}>
              staff: {phase.staffId} · approve فقط وضعیت دسترسی را فعال می‌کند
            </p>
          </div>
          <button
            type="button"
            onClick={async () => {
              setBusy(true);
              await logout(phase.token);
              setBusy(false);
              setPhase({ name: 'login' });
              setPassword('');
            }}
            disabled={busy}
            style={{ padding: '8px 12px', height: 'fit-content' }}
          >
            خروج
          </button>
        </header>
        <RegistrationQueueScreen token={phase.token} />
      </div>
    );
  }

  if (phase.name === 'rotate') {
    return (
      <div style={{ maxWidth: 420, margin: '2rem auto', padding: '0 1rem' }}>
        <h1 style={{ fontSize: '1.25rem' }}>تغییر رمز اجباری</h1>
        <form onSubmit={onRotate}>
          <label style={{ display: 'block', marginBottom: 8 }}>
            رمز جدید
            <input
              type="password"
              value={newPassword}
              onChange={(e) => setNewPassword(e.target.value)}
              required
              minLength={8}
              style={{ display: 'block', width: '100%', marginTop: 4, padding: 10 }}
            />
          </label>
          {error && <p style={{ color: '#e85d5d' }}>{error}</p>}
          <button type="submit" disabled={busy} style={{ width: '100%', padding: 12 }}>
            {busy ? '...' : 'ذخیره و ادامه'}
          </button>
        </form>
      </div>
    );
  }

  return (
    <div style={{ maxWidth: 420, margin: '2rem auto', padding: '0 1rem' }}>
      <h1 style={{ fontSize: '1.25rem' }}>ورود کارکنان</h1>
      <p style={{ color: '#9aa4b2', fontSize: '0.9rem' }}>tenant از Host · بدون selector دستی</p>
      <form onSubmit={onLogin}>
        <label style={{ display: 'block', marginBottom: 8 }}>
          نام کاربری
          <input
            value={username}
            onChange={(e) => setUsername(e.target.value)}
            required
            style={{ display: 'block', width: '100%', marginTop: 4, padding: 10 }}
          />
        </label>
        <label style={{ display: 'block', marginBottom: 8 }}>
          رمز
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            style={{ display: 'block', width: '100%', marginTop: 4, padding: 10 }}
          />
        </label>
        {error && <p style={{ color: '#e85d5d' }}>{error}</p>}
        <button type="submit" disabled={busy} style={{ width: '100%', padding: 12 }}>
          {busy ? '...' : 'ورود'}
        </button>
      </form>
    </div>
  );
}
