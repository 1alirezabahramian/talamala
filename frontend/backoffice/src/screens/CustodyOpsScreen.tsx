/**
 * Staff custody operations: receive → ready → deliver.
 * weight_grams opaque decimal string from / to server.
 */

import { useState, type FormEvent } from 'react';
import {
  custodyDeliver,
  custodyMarkReady,
  custodyReceive,
} from '../api/custody';

export type CustodyOpsScreenProps = {
  token: string;
  /** Optional controlled receive (tests) */
  onReceive?: (payload: {
    customerId: string;
    description: string;
    weightGrams: string;
    fineness?: string;
  }) => Promise<void>;
};

type LastItem = {
  id: string;
  status: string;
  weight_grams?: string;
};

export function CustodyOpsScreen(props: CustodyOpsScreenProps) {
  const [customerId, setCustomerId] = useState('');
  const [description, setDescription] = useState('');
  const [weightGrams, setWeightGrams] = useState('');
  const [fineness, setFineness] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [last, setLast] = useState<LastItem | null>(null);

  async function onReceiveSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    const cid = customerId.trim();
    const desc = description.trim();
    const weight = weightGrams.trim();
    if (!cid || !desc || !weight) {
      setError('customer_id، شرح و وزن الزامی است');
      return;
    }
    if (!/^\d+(\.\d+)?$/.test(weight)) {
      setError('وزن باید رشتهٔ اعشاری مثبت باشد');
      return;
    }
    setLoading(true);
    try {
      if (props.onReceive) {
        await props.onReceive({
          customerId: cid,
          description: desc,
          weightGrams: weight,
          fineness: fineness.trim() || undefined,
        });
        return;
      }
      const res = await custodyReceive(props.token, {
        customer_id: cid,
        description: desc,
        weight_grams: weight,
        fineness: fineness.trim() || undefined,
      });
      if (!res.ok) {
        setError(res.message || res.error || 'receive ناموفق');
        return;
      }
      setLast({
        id: res.data.id,
        status: res.data.status,
        weight_grams: res.data.weight_grams,
      });
    } finally {
      setLoading(false);
    }
  }

  async function transition(kind: 'ready' | 'deliver') {
    if (!last?.id) {
      setError('ابتدا receive کنید یا id امانت را از پاسخ قبلی داشته باشید');
      return;
    }
    setLoading(true);
    setError(null);
    try {
      const res =
        kind === 'ready'
          ? await custodyMarkReady(props.token, last.id)
          : await custodyDeliver(props.token, last.id);
      if (!res.ok) {
        setError(res.message || res.error || 'transition ناموفق');
        return;
      }
      setLast({ id: res.data.id, status: res.data.status, weight_grams: last.weight_grams });
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="tal-screen tal-custody-ops" dir="rtl" lang="fa">
      <header className="tal-header">
        <h1>عملیات امانت (staff)</h1>
        <p className="tal-muted">receive → ready_for_pickup → delivered</p>
      </header>

      <form onSubmit={onReceiveSubmit} className="tal-form" noValidate>
        <label htmlFor="customer_id">Customer ID</label>
        <input
          id="customer_id"
          dir="ltr"
          value={customerId}
          disabled={loading}
          onChange={(e) => setCustomerId(e.target.value)}
        />

        <label htmlFor="description">شرح</label>
        <input
          id="description"
          value={description}
          disabled={loading}
          onChange={(e) => setDescription(e.target.value)}
        />

        <label htmlFor="weight_grams">وزن (گرم) — decimal string</label>
        <input
          id="weight_grams"
          dir="ltr"
          inputMode="decimal"
          value={weightGrams}
          disabled={loading}
          onChange={(e) => setWeightGrams(e.target.value)}
        />

        <label htmlFor="fineness">عیار (اختیاری)</label>
        <input
          id="fineness"
          dir="ltr"
          value={fineness}
          disabled={loading}
          onChange={(e) => setFineness(e.target.value)}
        />

        {error ? (
          <p className="tal-error" role="alert">
            {error}
          </p>
        ) : null}

        <button type="submit" disabled={loading}>
          {loading ? '…' : 'دریافت امانت (receive)'}
        </button>
      </form>

      {last ? (
        <div className="tal-card">
          <p dir="ltr">
            id: {last.id}
            <br />
            status: {last.status}
            {last.weight_grams ? (
              <>
                <br />
                weight_grams: {last.weight_grams}
              </>
            ) : null}
          </p>
          <button
            type="button"
            disabled={loading || last.status === 'ready_for_pickup' || last.status === 'delivered'}
            onClick={() => void transition('ready')}
          >
            آماده تحویل (ready)
          </button>
          <button
            type="button"
            disabled={loading || last.status !== 'ready_for_pickup'}
            onClick={() => void transition('deliver')}
          >
            تحویل (deliver)
          </button>
        </div>
      ) : null}
    </div>
  );
}
