/**
 * Registration after OTP registration_required.
 * Fields exact OpenAPI: mobile, national_code, full_name.
 * Jibit is backend gate only — not staff approval.
 */

import { useState, type FormEvent } from 'react';
import { registerCustomer } from '../../api/auth';
import { FormField } from '../../ui';

export type RegistrationScreenProps = {
  mobile: string;
  onSuccess: (payload: {
    customerId: string;
    accessStatus: string;
    kimiaBound: boolean;
  }) => void;
  onBack?: () => void;
};

export function RegistrationScreen(props: RegistrationScreenProps) {
  const [fullName, setFullName] = useState('');
  const [nationalCode, setNationalCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    const nc = nationalCode.trim();
    const name = fullName.trim();
    if (name.length < 2) {
      setError('نام کامل را وارد کنید');
      return;
    }
    if (!/^\d{8,10}$/.test(nc)) {
      setError('کد ملی باید ۸ تا ۱۰ رقم باشد');
      return;
    }
    setLoading(true);
    try {
      const res = await registerCustomer({
        mobile: props.mobile,
        national_code: nc,
        full_name: name,
      });
      if (!res.ok) {
        setError(mapRegError(res.error, res.message));
        return;
      }
      props.onSuccess({
        customerId: res.data.customer_id,
        accessStatus: res.data.access_status,
        kimiaBound: res.data.kimia_bound,
      });
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="tal-screen tal-registration" dir="rtl" lang="fa">
      <header className="tal-header">
        <h1>تکمیل ثبت‌نام</h1>
        <p className="tal-muted">
          موبایل تأییدشده: <span dir="ltr">{props.mobile}</span>
        </p>
      </header>

      <form onSubmit={onSubmit} className="tal-form" noValidate>
        <FormField id="full_name" label="نام کامل" hint="مطابق مدارک">
          <input
            id="full_name"
            name="full_name"
            type="text"
            autoComplete="name"
            value={fullName}
            disabled={loading}
            onChange={(ev) => setFullName(ev.target.value)}
          />
        </FormField>

        <FormField id="national_code" label="کد ملی" hint="۸ تا ۱۰ رقم" error={error}>
          <input
            id="national_code"
            name="national_code"
            type="text"
            inputMode="numeric"
            dir="ltr"
            maxLength={10}
            value={nationalCode}
            disabled={loading}
            onChange={(ev) => setNationalCode(ev.target.value.replace(/\D+/g, '').slice(0, 10))}
            aria-invalid={!!error}
          />
        </FormField>

        <button type="submit" disabled={loading || !fullName.trim() || nationalCode.length < 8}>
          {loading ? 'در حال ارسال…' : 'ثبت‌نام'}
        </button>

        {props.onBack ? (
          <button type="button" className="tal-link" disabled={loading} onClick={props.onBack}>
            بازگشت
          </button>
        ) : null}
      </form>
    </div>
  );
}

function mapRegError(code: string, message?: string): string {
  switch (code) {
    case 'jibit_mismatch':
      return 'تطبیق هویت (Jibit) ناموفق بود';
    case 'already_registered':
      return 'این موبایل قبلاً ثبت شده است';
    case 'validation':
      return message || 'فیلدها ناقص است';
    case 'network_error':
      return 'ارتباط با سرور برقرار نشد';
    default:
      return message || 'خطا در ثبت‌نام';
  }
}
