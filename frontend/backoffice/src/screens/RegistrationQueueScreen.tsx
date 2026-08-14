/**
 * Backoffice registration queue.
 * Lists Limited customers; approve → Active.
 * No Kimia internal codes. No financial values.
 */

import { useCallback, useEffect, useState } from 'react';
import {
  approveRegistration,
  listRegistrationQueue,
  type RegistrationQueueItemDto,
} from '../api/registrations';

export type RegistrationQueueItem = {
  customerId: string;
  mobile: string;
  fullName: string | null;
  accessStatus: string;
  kimiaBound: boolean;
  createdAt: string;
};

export type RegistrationQueueScreenProps = {
  /** Staff Bearer token (required for live fetch) */
  token: string;
  /** Controlled mode optional */
  items?: RegistrationQueueItem[];
  loading?: boolean;
  onApprove?: (customerId: string) => Promise<void>;
};

function mapItem(d: RegistrationQueueItemDto): RegistrationQueueItem {
  return {
    customerId: d.customer_id,
    mobile: d.mobile,
    fullName: d.full_name,
    accessStatus: d.access_status,
    kimiaBound: d.kimia_bound,
    createdAt: d.created_at,
  };
}

export function RegistrationQueueScreen(props: RegistrationQueueScreenProps) {
  const controlled = props.items !== undefined;
  const [loading, setLoading] = useState(!controlled);
  const [error, setError] = useState<string | null>(null);
  const [items, setItems] = useState<RegistrationQueueItem[]>(props.items ?? []);
  const [busyId, setBusyId] = useState<string | null>(null);

  const reload = useCallback(async () => {
    if (controlled) return;
    setLoading(true);
    setError(null);
    const res = await listRegistrationQueue(props.token);
    if (!res.ok) {
      setError(res.message || res.error || 'خطا در بارگذاری صف');
      setItems([]);
    } else {
      setItems((res.data.items ?? []).map(mapItem));
    }
    setLoading(false);
  }, [controlled, props.token]);

  useEffect(() => {
    if (controlled) {
      setItems(props.items ?? []);
      setLoading(!!props.loading);
      return;
    }
    void reload();
  }, [controlled, props.items, props.loading, reload]);

  async function handleApprove(customerId: string) {
    setBusyId(customerId);
    setError(null);
    try {
      if (props.onApprove) {
        await props.onApprove(customerId);
      } else {
        const res = await approveRegistration(props.token, customerId);
        if (!res.ok) {
          setError(res.message || res.error || 'تأیید ناموفق');
          return;
        }
      }
      if (!controlled) {
        await reload();
      }
    } finally {
      setBusyId(null);
    }
  }

  if (loading) {
    return (
      <div className="tal-screen" dir="rtl" lang="fa">
        <p className="tal-muted">در حال بارگذاری صف ثبت‌نام…</p>
      </div>
    );
  }

  return (
    <div className="tal-screen tal-reg-queue" dir="rtl" lang="fa">
      <header className="tal-header">
        <h1>صف ثبت‌نام</h1>
        <p className="tal-muted">مشتریان در انتظار تأیید (Limited → Active)</p>
      </header>

      {error ? (
        <p className="tal-error" role="alert">
          {error}
        </p>
      ) : null}

      {!controlled ? (
        <button type="button" className="tal-secondary" onClick={() => void reload()}>
          تازه‌سازی
        </button>
      ) : null}

      {items.length === 0 ? (
        <p className="tal-muted">صف خالی است</p>
      ) : (
        <ul className="tal-list">
          {items.map((it) => (
            <li key={it.customerId} className="tal-list-item">
              <div className="tal-list-title">{it.fullName || '—'}</div>
              <div className="tal-list-meta" dir="ltr">
                {it.mobile} · {it.accessStatus}
                {it.kimiaBound ? ' · kimia_bound' : ''}
              </div>
              <button
                type="button"
                disabled={busyId === it.customerId}
                onClick={() => void handleApprove(it.customerId)}
              >
                {busyId === it.customerId ? '…' : 'تأیید'}
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
