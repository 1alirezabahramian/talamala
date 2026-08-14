/**
 * OTP → registration_required → Registration form → done.
 * Local runnable path: /otp-demo.html
 */

import { useState } from 'react';
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

  return (
    <div className="tal-screen" dir="rtl" lang="fa">
      <h1>{step.kind === 'authenticated' ? 'ورود موفق' : 'ثبت‌نام ثبت شد'}</h1>
      {step.customerId ? (
        <p className="tal-muted">customer_id: {step.customerId}</p>
      ) : null}
      {step.accessStatus ? (
        <p className="tal-muted">access_status: {step.accessStatus}</p>
      ) : null}
      {step.kind === 'registered' ? (
        <p className="tal-muted">
          وضعیت پیش‌فرض محدود است تا staff approve (جدا از Jibit).
        </p>
      ) : null}
      <button type="button" onClick={() => setStep({ name: 'request' })}>
        شروع دوباره
      </button>
    </div>
  );
}
