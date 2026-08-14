/**
 * Minimal OTP vertical shell (request → verify → done).
 * Wire into Vite/Next when design-system gate opens.
 * Local runnable path today: /otp-demo.html on PHP server.
 */

import { useState } from 'react';
import { OtpRequestScreen } from './screens/auth/OtpRequestScreen';
import { OtpVerifyScreen } from './screens/auth/OtpVerifyScreen';

type Step =
  | { name: 'request' }
  | { name: 'verify'; challengeId: string; mobile: string; expiresAt: string }
  | {
      name: 'done';
      kind: 'authenticated' | 'registration_required';
      accessToken?: string;
      customerId?: string;
      accessStatus?: string;
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
        onRegistrationRequired={() =>
          setStep({ name: 'done', kind: 'registration_required' })
        }
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

  return (
    <div className="tal-screen" dir="rtl" lang="fa">
      <h1>{step.kind === 'authenticated' ? 'ورود موفق' : 'ثبت‌نام لازم است'}</h1>
      {step.kind === 'authenticated' ? (
        <p className="tal-muted">customer_id: {step.customerId}</p>
      ) : (
        <p className="tal-muted">موبایل تأیید شد — مرحله بعد: فرم ثبت‌نام</p>
      )}
      <button type="button" onClick={() => setStep({ name: 'request' })}>
        شروع دوباره
      </button>
    </div>
  );
}
