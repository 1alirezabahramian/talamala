/**
 * Staff: login → optional rotate → shell (registration queue | custody | orders).
 * Existing APIs only. No Kimia write. No reject endpoint (blocked).
 * Settlement remains blocked_by_ground_truth until GT-005.
 */

import { useEffect, useState } from 'react';
import { staffLogin, staffRotatePassword, logout } from './api/auth';
import { RegistrationQueueScreen } from './screens/RegistrationQueueScreen';
import { FormField } from './ui';
import { CustodyOpsScreen } from './screens/CustodyOpsScreen';
import { OrdersListScreen } from './screens/OrdersListScreen';

const SESSION_KEY = 'talamala_staff_session_v1';

type Session = { staffId: string; token: string; username: string };

type Phase =
  | { name: 'login' }
  | { name: 'rotate'; staffId: string; token?: string; username: string }
  | { name: 'app'; session: Session; tab: 'queue' | 'custody' | 'orders' };

function loadSession(): Session | null {
  try {
    const raw = localStorage.getItem(SESSION_KEY);
    if (!raw) return null;
    const s = JSON.parse(raw) as Session;
    if (s?.token && s?.staffId) return s;
  } catch {
    /* ignore */
  }
  return null;
}

function saveSession(s: Session | null): void {
  try {
    if (!s) localStorage.removeItem(SESSION_KEY);
    else localStorage.setItem(SESSION_KEY, JSON.stringify(s));
  } catch {
    /* ignore */
  }
}

export function AppBackoffice() {
  const [phase, setPhase] = useState<Phase>({ name: 'login' });
  const [booting, setBooting] = useState(true);
  const [username, setUsername] = useState('operator');
  const [password, setPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    const s = loadSession();
    if (s) setPhase({ name: 'app', session: s, tab: 'queue' });
    setBooting(false);
  }, []);

  async function onLogin(e: React.FormEvent) {
    e.preventDefault();
    setBusy(true);
    setError(null);
    const res = await staffLogin(username.trim(), password);
    setBusy(false);
    if (!res.ok) {
      setError(
        res.error === 'invalid_credentials'
          ? 'نام کاربری یا رمز نادرست است'
          : res.message || res.error || 'ورود ناموفق',
      );
      return;
    }
    const staffId = res.data.staff_id;
    const token = res.data.access_token ?? '';
    if (res.data.must_change_password) {
      setPhase({ name: 'rotate', staffId, token: token || undefined, username: username.trim() });
      return;
    }
    if (!token) {
      setError('توکن نشست دریافت نشد');
      return;
    }
    const session = { staffId, token, username: username.trim() };
    saveSession(session);
    setPhase({ name: 'app', session, tab: 'queue' });
  }

  async function onRotate(e: React.FormEvent) {
    e.preventDefault();
    if (phase.name !== 'rotate') return;
    setBusy(true);
    setError(null);
    const res = await staffRotatePassword(phase.staffId, password, newPassword, phase.token);
    setBusy(false);
    if (!res.ok) {
      const map: Record<string, string> = {
        password_too_weak: 'رمز جدید خیلی کوتاه است (حداقل ۱۰ کاراکتر)',
        invalid_current_password: 'رمز فعلی نادرست است',
        password_reuse: 'رمز جدید نباید با قبلی یکی باشد',
      };
      setError(map[res.error] || res.message || res.error || 'تعویض رمز ناموفق');
      return;
    }
    const login = await staffLogin(phase.username, newPassword);
    if (!login.ok || !login.data.access_token) {
      setError('ورود مجدد پس از تعویض رمز ناموفق بود');
      setPhase({ name: 'login' });
      return;
    }
    setPassword(newPassword);
    const session = {
      staffId: login.data.staff_id,
      token: login.data.access_token,
      username: phase.username,
    };
    saveSession(session);
    setPhase({ name: 'app', session, tab: 'queue' });
  }

  async function onLogout() {
    if (phase.name === 'app') {
      await logout(phase.session.token);
    }
    saveSession(null);
    setPassword('');
    setNewPassword('');
    setPhase({ name: 'login' });
  }

  if (booting) {
    return (
      <div className="bo-screen" dir="rtl" lang="fa">
        <p className="tal-muted">در حال آماده‌سازی…</p>
      </div>
    );
  }

  if (phase.name === 'login') {
    return (
      <div className="bo-screen" dir="rtl" lang="fa">
        <div className="bo-card">
          <h1>ورود کارکنان</h1>
          <p className="tal-muted">Tenant از Host / X-Talamala-Host — بدون tenant در بدنه</p>
          <form onSubmit={onLogin} className="bo-form">
            <FormField id="user" label="نام کاربری">
              <input
                id="user"
                autoComplete="username"
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                disabled={busy}
              />
            </FormField>
            <FormField id="pass" label="رمز عبور">
              <input
                id="pass"
                type="password"
                autoComplete="current-password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                disabled={busy}
              />
            </FormField>
            {error ? (
              <p className="error" role="alert">
                {error}
              </p>
            ) : null}
            <button type="submit" disabled={busy || !username || !password}>
              {busy ? '…' : 'ورود'}
            </button>
          </form>
        </div>
      </div>
    );
  }

  if (phase.name === 'rotate') {
    return (
      <div className="bo-screen" dir="rtl" lang="fa">
        <div className="bo-card">
          <h1>تعویض رمز الزامی</h1>
          <p className="tal-muted">اولین ورود — رمز جدید حداقل ۱۰ کاراکتر</p>
          <form onSubmit={onRotate} className="bo-form">
            <FormField id="np" label="رمز جدید" hint="حداقل ۱۰ کاراکتر">
              <input
                id="np"
                type="password"
                autoComplete="new-password"
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                disabled={busy}
              />
            </FormField>
            {error ? (
              <p className="error" role="alert">
                {error}
              </p>
            ) : null}
            <button type="submit" disabled={busy || newPassword.length < 10}>
              {busy ? '…' : 'ثبت رمز و ادامه'}
            </button>
          </form>
        </div>
      </div>
    );
  }

  const { session, tab } = phase;
  return (
    <div className="bo-app" dir="rtl" lang="fa">
      <header className="bo-topbar">
        <div>
          <strong>پشتیبانی / عملیات</strong>
          <span className="tal-muted"> {session.username}</span>
        </div>
        <button type="button" className="bo-btn-ghost" onClick={() => void onLogout()}>
          خروج
        </button>
      </header>
      <nav className="bo-nav" aria-label="منوی کارکنان">
        <button
          type="button"
          className={tab === 'queue' ? 'active' : ''}
          onClick={() => setPhase({ name: 'app', session, tab: 'queue' })}
        >
          صف ثبت‌نام
        </button>
        <button
          type="button"
          className={tab === 'custody' ? 'active' : ''}
          onClick={() => setPhase({ name: 'app', session, tab: 'custody' })}
        >
          عملیات امانت
        </button>
        <button
          type="button"
          className={tab === 'orders' ? 'active' : ''}
          onClick={() => setPhase({ name: 'app', session, tab: 'orders' })}
        >
          سفارش‌ها
        </button>
      </nav>
      <main>
        {tab === 'queue' ? <RegistrationQueueScreen token={session.token} /> : null}
        {tab === 'custody' ? <CustodyOpsScreen token={session.token} /> : null}
        {tab === 'orders' ? <OrdersListScreen token={session.token} /> : null}
      </main>
    </div>
  );
}
