/**
 * OTP (login|registration) → registration_required → Registration → pending
 * or authenticated → CustomerShell.
 * Session restored from localStorage when present. No client money math.
 */

import { useEffect, useState } from 'react';
import { logout, type OtpPurpose } from './api/auth';
import { CustomerShell } from './CustomerShell';
import { OtpRequestScreen } from './screens/auth/OtpRequestScreen';
import { OtpVerifyScreen } from './screens/auth/OtpVerifyScreen';
import { RegistrationScreen } from './screens/auth/RegistrationScreen';

const SESSION_KEY = 'talamala_customer_session_v1';

type Session = {
  accessToken: string;
  customerId: string;
  accessStatus?: string;
};

type Step =
  | { name: 'request'; purpose: OtpPurpose }
  | { name: 'verify'; challengeId: string; mobile: string; expiresAt: string; purpose: OtpPurpose }
  | { name: 'register'; mobile: string }
  | {
      name: 'pending';
      customerId: string;
      accessStatus: string;
      kimiaBound: boolean;
    }
  | { name: 'shell'; session: Session };

function loadSession(): Session | null {
  try {
    const raw = localStorage.getItem(SESSION_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw) as Session;
    if (parsed?.accessToken && parsed?.customerId) return parsed;
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

export function AppOtpFlow() {
  const [step, setStep] = useState<Step>({ name: 'request', purpose: 'login' });
  const [booting, setBooting] = useState(true);
  const [logoutBusy, setLogoutBusy] = useState(false);
  const [logoutMsg, setLogoutMsg] = useState<string | null>(null);

  useEffect(() => {
    const s = loadSession();
    if (s) setStep({ name: 'shell', session: s });
    setBooting(false);
  }, []);

  async function onLogout(token?: string) {
    setLogoutMsg(null);
    setLogoutBusy(true);
    if (token) {
      // Best-effort server revoke: local logout must still work for expired/revoked tokens
      // or temporary network failures, otherwise a stale localStorage session can trap the UI.
      await logout(token);
    }
    saveSession(null);
    setLogoutBusy(false);
    setStep({ name: 'request', purpose: 'login' });
  }

  if (booting) {
    return (
      <div className="tal-screen" dir="rtl" lang="fa">
        <p className="tal-muted">در حال آماده‌سازی…</p>
      </div>
    );
  }

  if (step.name === 'request') {
    return (
      <div className="tal-app" dir="rtl" lang="fa">
        <div className="tal-purpose-bar">
          <button
            type="button"
            className={step.purpose === 'login' ? 'active' : ''}
            onClick={() => setStep({ name: 'request', purpose: 'login' })}
          >
            ورود
          </button>
          <button
            type="button"
            className={step.purpose === 'registration' ? 'active' : ''}
            onClick={() => setStep({ name: 'request', purpose: 'registration' })}
          >
            ثبت‌نام جدید
          </button>
        </div>
        <OtpRequestScreen
          purpose={step.purpose}
          onChallenge={(challengeId, mobile, expiresAt) =>
            setStep({
              name: 'verify',
              challengeId,
              mobile,
              expiresAt,
              purpose: step.purpose,
            })
          }
        />
      </div>
    );
  }

  if (step.name === 'verify') {
    return (
      <OtpVerifyScreen
        challengeId={step.challengeId}
        mobile={step.mobile}
        expiresAt={step.expiresAt}
        onBack={() => setStep({ name: 'request', purpose: step.purpose })}
        onRegistrationRequired={() => setStep({ name: 'register', mobile: step.mobile })}
        onAuthenticated={({ accessToken, customerId, accessStatus }) => {
          const session: Session = {
            accessToken: accessToken ?? '',
            customerId: customerId ?? '',
            accessStatus,
          };
          if (!session.accessToken || !session.customerId) return;
          saveSession(session);
          setStep({ name: 'shell', session });
        }}
      />
    );
  }

  if (step.name === 'register') {
    return (
      <RegistrationScreen
        mobile={step.mobile}
        onBack={() => setStep({ name: 'request', purpose: 'registration' })}
        onSuccess={({ customerId, accessStatus, kimiaBound }) =>
          setStep({
            name: 'pending',
            customerId,
            accessStatus,
            kimiaBound,
          })
        }
      />
    );
  }

  if (step.name === 'pending') {
    return (
      <div className="tal-screen tal-card-wrap" dir="rtl" lang="fa">
        <div className="tal-card">
          <h1>درخواست ثبت شد</h1>
          <p className="tal-muted">
            ثبت‌نام شما دریافت شد و در انتظار تأیید کارکنان است. پس از تأیید، با همان شماره
            موبایل از مسیر «ورود» وارد شوید.
          </p>
          <dl className="tal-dl">
            <div>
              <dt>شناسه مشتری</dt>
              <dd dir="ltr">{step.customerId}</dd>
            </div>
            <div>
              <dt>وضعیت دسترسی</dt>
              <dd>{step.accessStatus}</dd>
            </div>
            <div>
              <dt>اتصال Kimia</dt>
              <dd>{step.kimiaBound ? 'بسته شده' : 'هنوز بسته نشده'}</dd>
            </div>
          </dl>
          <button type="button" className="tal-btn" onClick={() => setStep({ name: 'request', purpose: 'login' })}>
            بازگشت به ورود
          </button>
        </div>
      </div>
    );
  }

  // shell
  const { session } = step;
  return (
    <div className="tal-app tal-app-shell" dir="rtl" lang="fa">
      <header className="tal-topbar">
        <div className="tal-topbar-title">
          <strong>حساب مشتری</strong>
          <span className="tal-muted" dir="ltr">
            {session.customerId}
          </span>
          {session.accessStatus ? (
            <span className="tal-badge">{session.accessStatus}</span>
          ) : null}
        </div>
        <button
          type="button"
          className="tal-btn tal-btn-ghost"
          onClick={() => void onLogout(session.accessToken)}
          disabled={logoutBusy}
        >
          {logoutBusy ? '…' : 'خروج'}
        </button>
      </header>
      {logoutMsg ? (
        <p className="tal-error" role="alert">
          {logoutMsg}
        </p>
      ) : null}
      <CustomerShell token={session.accessToken} customerId={session.customerId} />
    </div>
  );
}
