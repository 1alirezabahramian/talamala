/**
 * OTP → registration_required → Registration form → done / shell.
 * Authenticated with token → CustomerShell (existing screens only).
 */

import { useState } from 'react';
import { logout } from './api/auth';
import { CustomerShell } from './CustomerShell';
import { OtpRequestScreen } from './screens/auth/OtpRequestScreen';
import { OtpVerifyScreen } from './screens/auth/OtpVerifyScreen';
import { RegistrationScreen } from './screens/auth/RegistrationScreen';

type Step =
  | { name: 'request' }
  | { name: 'verify'; challengeId: string; mobile: string; expiresAt: string }
  | { name: 'register'; mobile: string }
  | {
      name: 'done';
      kind: 'authenticated' | 'registered';
      accessToken?: string;
      customerId?: string;
      accessStatus?: string;
      kimiaBound?: boolean;
    };

export function AppOtpFlow() {
  const [step, setStep] = useState<Step>({ name: 'request' });
  const [logoutBusy, setLogoutBusy] = useState(false);
  const [logoutMsg, setLogoutMsg] = useState<string | null>(null);

  if (step.name === 'request') {
    return (
      <OtpRequestScreen
        purpose="registration"
        onChallenge={(challengeId, mobile, expiresAt) =>
          setStep({ name: 'verify', challengeId, mobile, expiresAt })
        }
      />
    );
  }

  if (step.name === 'verify') {
    return (
      <OtpVerifyScreen
        challengeId={step.challengeId}
        mobile={step.mobile}
        expiresAt={step.expiresAt}
        onBack={() => setStep({ name: 'request' })}
        onRegistrationRequired={() => setStep({ name: 'register', mobile: step.mobile })}
        onAuthenticated={({ accessToken, customerId, accessStatus }) =>
          setStep({
            name: 'done',
            kind: 'authenticated',
            accessToken,
            customerId,
            accessStatus,
          })
        }
      />
    );
  }

  if (step.name === 'register') {
    return (
      <RegistrationScreen
        mobile={step.mobile}
        onBack={() => setStep({ name: 'request' })}
        onSuccess={({ customerId, accessStatus, kimiaBound }) =>
          setStep({
            name: 'done',
            kind: 'registered',
            customerId,
            accessStatus,
            kimiaBound,
          })
        }
      />
    );
  }

  const done = step;
  const token = done.accessToken;

  async function onLogout() {
    setLogoutMsg(null);
    if (!token) {
      setStep({ name: 'request' });
      return;
    }
    setLogoutBusy(true);
    const res = await logout(token);
    setLogoutBusy(false);
    if (!res.ok) {
      setLogoutMsg(res.error || 'logout_failed');
      return;
    }
    setStep({ name: 'request' });
  }

  // Authenticated session → existing CustomerShell (no new business rules)
  if (done.kind === 'authenticated' && token && done.customerId) {
    return (
      <div dir="rtl" lang="fa" style={{ maxWidth: 720, margin: '0 auto', padding: '1rem' }}>
        <header
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            gap: 12,
            marginBottom: 12,
            flexWrap: 'wrap',
          }}
        >
          <div>
            <strong>پوسته مشتری</strong>
            <span style={{ color: '#9aa4b2', fontSize: '0.85rem', marginRight: 8 }}>
              {done.customerId}
            </span>
          </div>
          <button type="button" onClick={onLogout} disabled={logoutBusy}>
            {logoutBusy ? '...' : 'خروج'}
          </button>
        </header>
        {logoutMsg ? <p style={{ color: '#e85d5d' }}>{logoutMsg}</p> : null}
        <CustomerShell token={token} customerId={done.customerId} />
      </div>
    );
  }

  return (
    <div className="tal-screen" dir="rtl" lang="fa">
      <h1>{done.kind === 'authenticated' ? 'ورود موفق' : 'ثبت‌نام ثبت شد'}</h1>
      {done.customerId ? (
        <p className="tal-muted">customer_id: {done.customerId}</p>
      ) : null}
      {done.accessStatus ? (
        <p className="tal-muted">access_status: {done.accessStatus}</p>
      ) : null}
      {done.kind === 'registered' ? (
        <p className="tal-muted">
          در انتظار تأیید کارکنان · kimia_bound: {done.kimiaBound ? 'yes' : 'no'}
        </p>
      ) : null}
      {logoutMsg ? <p style={{ color: '#e85d5d' }}>{logoutMsg}</p> : null}
      {token ? (
        <button type="button" onClick={onLogout} disabled={logoutBusy}>
          {logoutBusy ? '...' : 'خروج (لغو نشست)'}
        </button>
      ) : null}
      <button type="button" onClick={() => setStep({ name: 'request' })}>
        شروع دوباره
      </button>
    </div>
  );
}
