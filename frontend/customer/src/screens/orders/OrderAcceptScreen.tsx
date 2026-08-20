/**
 * پذیرش سفارش از quote_id موجود (API فعلی).
 * settlement از سرور می‌آید — معمولاً blocked تا GT تسویه.
 * seed dev فقط fixture محلی، نه قیمت زنده.
 */

import { useState, type FormEvent } from 'react';
import { acceptOrderFromQuote, seedDevQuote } from '../../api/orderAccept';

export type OrderAcceptScreenProps = {
  token?: string;
  customerId?: string;
  initialQuoteId?: string;
  allowDevSeed?: boolean;
};

export function OrderAcceptScreen(props: OrderAcceptScreenProps) {
  const [quoteId, setQuoteId] = useState(props.initialQuoteId ?? '');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [result, setResult] = useState<{
    orderId: string;
    status: string;
    settlement: string;
    fromCache?: boolean;
  } | null>(null);
  const [seedNote, setSeedNote] = useState<string | null>(null);

  async function onSeed() {
    if (!props.customerId) {
      setError('برای ساخت quote آزمایشی، شناسه مشتری لازم است');
      return;
    }
    setLoading(true);
    setError(null);
    setResult(null);
    try {
      const res = await seedDevQuote(props.customerId);
      if (!res.ok) {
        setError(res.message || res.error || 'ساخت quote آزمایشی ناموفق بود');
        return;
      }
      setQuoteId(res.data.quote_id);
      setSeedNote(res.data.note || 'این quote فقط fixture محلی است — قیمت زنده نیست');
    } finally {
      setLoading(false);
    }
  }

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    setResult(null);
    const q = quoteId.trim();
    if (!q) {
      setError('شناسه پیشنهاد (quote_id) را وارد کنید');
      return;
    }
    setLoading(true);
    try {
      const idem =
        typeof crypto !== 'undefined' && 'randomUUID' in crypto
          ? crypto.randomUUID()
          : `idem-${Date.now()}`;
      const res = await acceptOrderFromQuote(q, idem, props.token, props.customerId);
      if (!res.ok) {
        setError(res.message || res.error || 'پذیرش سفارش ناموفق بود');
        return;
      }
      setResult({
        orderId: res.data.order_id,
        status: res.data.status,
        settlement: res.data.settlement,
        fromCache: !!res.data.from_idempotency_cache,
      });
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="tal-screen tal-order-accept" dir="rtl" lang="fa">
      <header className="tal-header">
        <h1>پذیرش سفارش</h1>
        <p className="tal-muted">فقط با quote موجود · تسویه مالی از سمت سرور کنترل می‌شود</p>
        <div className="tal-card" role="status"><strong>هشدار تسویه:</strong> تا تکمیل Ground Truth، settlement سمت سرور blocked می‌ماند — موجودی Kimia جعل نمی‌شود.</div>
      </header>

      <form onSubmit={onSubmit} className="tal-form" noValidate>
        <label htmlFor="quote_id">شناسه پیشنهاد (quote_id)</label>
        <input
          id="quote_id"
          name="quote_id"
          type="text"
          dir="ltr"
          autoComplete="off"
          value={quoteId}
          disabled={loading}
          onChange={(ev) => setQuoteId(ev.target.value)}
          placeholder="quote-…"
        />

        {props.allowDevSeed ? (
          <button
            type="button"
            className="tal-btn-ghost"
            disabled={loading}
            onClick={() => void onSeed()}
            style={{ width: '100%', marginTop: 4 }}
          >
            {loading ? '…' : 'quote آزمایشی (فقط محیط dev)'}
          </button>
        ) : null}

        {seedNote ? <p className="tal-muted">{seedNote}</p> : null}

        {error ? (
          <p className="tal-error" role="alert">
            {error}
          </p>
        ) : null}

        {result ? (
          <div className="tal-card" role="status">
            <div className="tal-list-title">سفارش ثبت شد</div>
            <dl className="tal-dl">
              <div>
                <dt>شناسه سفارش</dt>
                <dd dir="ltr">{result.orderId}</dd>
              </div>
              <div>
                <dt>وضعیت</dt>
                <dd dir="ltr">{result.status}</dd>
              </div>
              <div>
                <dt>تسویه</dt>
                <dd dir="ltr">{result.settlement}</dd>
              </div>
              {result.fromCache ? (
                <div>
                  <dt>idempotency</dt>
                  <dd>پاسخ از cache</dd>
                </div>
              ) : null}
            </dl>
            {result.settlement.includes('blocked') ? (
              <p className="tal-muted">تسویه هنوز توسط سرور مسدود است — نیاز به Ground Truth جدا.</p>
            ) : null}
          </div>
        ) : null}

        <button type="submit" disabled={loading || !quoteId.trim()}>
          {loading ? 'در حال ارسال…' : 'قبول سفارش'}
        </button>
      </form>
    </div>
  );
}
