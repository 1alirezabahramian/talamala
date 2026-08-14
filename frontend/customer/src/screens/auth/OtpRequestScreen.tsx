/**
 * Customer OTP request — Persian RTL, mobile-first.
 * No financial math on client. Calls backend only.
 *
 * Responsibilities:
 * 1. Mobile input (Iran format)
 * 2. Submit → POST /v1/auth/customer/otp/request
 * 3. Navigate to verify with challenge_id
 * 4. Loading / error / empty states
 */

import { useState, type FormEvent } from 'react';
import { isValidIranMobile, normalizeIranMobile, requestOtp, type OtpPurpose } from '../../api/auth';

export type OtpRequestProps = {
  purpose?: OtpPurpose;
  onChallenge: (challengeId: string, mobile: string, expiresAt: string) => void;
  loading?: boolean;
  error?: string | null;
};

export function OtpRequestScreen(props: OtpRequestProps) {
  const purpose = props.purpose ?? 'registration';
  const [mobile, setMobile] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(props.error ?? null);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    const normalized = normalizeIranMobile(mobile);
    if (!isValidIranMobile(normalized)) {
      setError('شماره موبایل معتبر نیست (مثال: ۰۹۱۲۱۲۳۴۵۶۷)');
      return;
    }
    setLoading(true);
    try {
      const res = await requestOtp(normalized, purpose);
      if (!res.ok) {
        if (res.status === 429) {
          const wait = res.retryAfter ?? 60;
          setError(`محدودیت نرخ. ${wait} ثانیه صبر کنید.`);
        } else {
          setError(res.message || mapError(res.error));
        }
        return;
      }
      props.onChallenge(res.data.challenge_id, normalized, res.data.expires_at);
    } finally {
      setLoading(false);
    }
  }

  const busy = loading || !!props.loading;

  return (
    <div className="tal-screen tal-otp-request" dir="rtl" lang="fa">
      <header className="tal-header">
        <h1>ورود با پیامک</h1>
        <p className="tal-muted">شماره موبایل خود را وارد کنید</p>
      </header>

      <form onSubmit={onSubmit} className="tal-form" noValidate>
        <label htmlFor="mobile">موبایل</label>
        <input
          id="mobile"
          name="mobile"
          type="tel"
          inputMode="numeric"
          autoComplete="tel"
          placeholder="09121234567"
          value={mobile}
          disabled={busy}
          onChange={(ev) => setMobile(ev.target.value)}
          maxLength={13}
          dir="ltr"
        />

        {error ? (
          <p className="tal-error" role="alert">
            {error}
          </p>
        ) : null}

        <button type="submit" disabled={busy || mobile.trim() === ''}>
          {busy ? 'در حال ارسال…' : 'دریافت کد'}
        </button>
      </form>
    </div>
  );
}

function mapError(code: string): string {
  switch (code) {
    case 'mobile_required':
      return 'شماره موبایل الزامی است';
    case 'invalid_purpose':
      return 'هدف درخواست نامعتبر است';
    case 'network_error':
      return 'ارتباط با سرور برقرار نشد';
    case 'tenant_unresolved':
      return 'tenant از Host resolve نشد (fail-closed)';
    default:
      return 'خطا در درخواست کد';
  }
}
