/**
 * Customer OTP verify screen.
 * On success: session token OR registration_required.
 */

import { useState, type FormEvent } from 'react';
import { verifyOtp } from '../../api/auth';
import { FormField } from '../../ui';

export type OtpVerifyProps = {
  challengeId: string;
  mobile?: string;
  expiresAt?: string;
  onAuthenticated: (payload: {
    accessToken: string;
    customerId: string;
    accessStatus?: string;
  }) => void;
  onRegistrationRequired: () => void;
  onBack?: () => void;
  loading?: boolean;
  error?: string | null;
};

export function OtpVerifyScreen(props: OtpVerifyProps) {
  const [code, setCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(props.error ?? null);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    const trimmed = code.trim();
    if (!/^\d{4,8}$/.test(trimmed)) {
      setError('کد را به‌درستی وارد کنید');
      return;
    }
    setLoading(true);
    try {
      const res = await verifyOtp(props.challengeId, trimmed);
      if (!res.ok) {
        setError(res.message || mapVerifyError(res.error));
        return;
      }
      if (res.data.status === 'registration_required') {
        props.onRegistrationRequired();
        return;
      }
      props.onAuthenticated({
        accessToken: res.data.access_token,
        customerId: res.data.customer_id,
        accessStatus: res.data.access_status,
      });
    } finally {
      setLoading(false);
    }
  }

  const busy = loading || !!props.loading;

  return (
    <div className="tal-screen tal-otp-verify" dir="rtl" lang="fa">
      <header className="tal-header">
        <h1>تأیید کد</h1>
        {props.mobile ? (
          <p className="tal-muted">
            کد ارسال‌شده به <span dir="ltr">{props.mobile}</span> را وارد کنید
          </p>
        ) : (
          <p className="tal-muted">کد پیامک را وارد کنید</p>
        )}
      </header>

      <form onSubmit={onSubmit} className="tal-form" noValidate>
        <FormField id="otp" label="کد یک‌بارمصرف">
          <input
            id="otp"
            name="otp"
            type="text"
            inputMode="numeric"
            autoComplete="one-time-code"
            placeholder="------"
            value={code}
            disabled={busy}
            onChange={(ev) => setCode(ev.target.value.replace(/\D+/g, '').slice(0, 8))}
            maxLength={8}
            dir="ltr"
          />
        </FormField>

        {error ? (
          <p className="tal-error" role="alert">
            {error}
          </p>
        ) : null}

        <button type="submit" disabled={busy || code.length < 4}>
          {busy ? 'در حال بررسی…' : 'تأیید'}
        </button>

        {props.onBack ? (
          <button type="button" className="tal-link" disabled={busy} onClick={props.onBack}>
            بازگشت
          </button>
        ) : null}
      </form>
    </div>
  );
}

function mapVerifyError(code: string): string {
  switch (code) {
    case 'otp_not_found':
      return 'چالش یافت نشد یا منقضی شده';
    case 'otp_expired':
      return 'کد منقضی شده — دوباره درخواست کنید';
    case 'otp_exhausted':
      return 'تعداد تلاش بیش از حد';
    case 'challenge_and_code_required':
      return 'کد و شناسه چالش الزامی است';
    case 'network_error':
      return 'ارتباط با سرور برقرار نشد';
    default:
      return 'کد نامعتبر است';
  }
}
