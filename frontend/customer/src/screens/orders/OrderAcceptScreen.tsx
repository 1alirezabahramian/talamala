/**
 * پذیرش سفارش از quote_id موجود.
 * settlement همیشه blocked تا GT Kimia write.
 * seed dev فقط برای دموی لوکال — fixture نه قیمت زنده.
 */

import { useState, type FormEvent } from 'react';
import { acceptOrderFromQuote, seedDevQuote } from '../../api/orderAccept';

export type OrderAcceptScreenProps = {
  token?: string;
  customerId?: string;
  /** pre-filled quote from parent */
  initialQuoteId?: string;
  allowDevSeed?: boolean;
};

export function OrderAcceptScreen(props: OrderAcceptScreenProps) {
  const [quoteId, setQuoteId] = useState(props.initialQuoteId ?? '');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [result, setResult] = useState<string | null>(null);

  async function onSeed() {
    if (!props.customerId) {
      setError('customerId برای seed لازم است');
      return;
    }
    setLoading(true);
    setError(null);
    try {
      const res = await seedDevQuote(props.customerId);
      if (!res.ok) {
        setError(res.message || res.error || 'seed ناموفق');
        return;
      }
      setQuoteId(res.data.quote_id);
      setResult(
        `fixture quote: ${res.data.quote_id}\n${res.data.note || 'Fixture only — not a live price provider'}`,
      );
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
      setError('quote_id لازم است');
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
        setError(res.message || res.error || 'accept ناموفق');
        return;
      }
      setResult(
        [
          `order_id: ${res.data.order_id}`,
          `status: ${res.data.status}`,
          `settlement: ${res.data.settlement}`,
          res.data.from_idempotency_cache ? 'from_idempotency_cache: true' : '',
        ]
          .filter(Boolean)
          .join('\n'),
      );
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="tal-screen tal-order-accept" dir="rtl" lang="fa">
      <header className="tal-header">
        <h1>پذیرش سفارش</h1>
        <p className="tal-muted">
          فقط quote_id · settlement = blocked_by_ground_truth
        </p>
      </header>

      <form onSubmit={onSubmit} className="tal-form" noValidate>
        <label htmlFor="quote_id">quote_id</label>
        <input
          id="quote_id"
          dir="ltr"
          value={quoteId}
          disabled={loading}
          onChange={(e) => setQuoteId(e.target.value)}
        />

        {props.allowDevSeed && props.customerId ? (
          <button type="button" disabled={loading} onClick={() => void onSeed()}>
            seed fixture quote (dev)
          </button>
        ) : null}

        {error ? (
          <p className="tal-error" role="alert">
            {error}
          </p>
        ) : null}
        {result ? (
          <pre className="tal-pre" dir="ltr">
            {result}
          </pre>
        ) : null}

        <button type="submit" disabled={loading || !quoteId.trim()}>
          {loading ? '…' : 'قبول سفارش'}
        </button>
      </form>
    </div>
  );
}
