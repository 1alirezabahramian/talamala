/**
 * امانات من — list only.
 * weight_grams is opaque decimal string from server.
 * No receive/ready/deliver from customer app (staff-only).
 */

import { useEffect, useState } from 'react';
import { fetchCustomerCustody, type CustodyItemDto } from '../../api/custody';

export type CustodyListScreenProps = {
  token?: string;
  customerId?: string;
};

const statusLabel: Record<string, string> = {
  held: 'نگهداری',
  ready_for_pickup: 'آماده تحویل',
  delivered: 'تحویل‌شده',
};

export function CustodyListScreen(props: CustodyListScreenProps) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [items, setItems] = useState<CustodyItemDto[]>([]);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      setLoading(true);
      setError(null);
      const res = await fetchCustomerCustody(props.token, props.customerId);
      if (cancelled) return;
      if (!res.ok) {
        setError(res.message || res.error || 'خطا در خواندن امانات');
        setItems([]);
      } else {
        setItems(res.data.items ?? []);
      }
      setLoading(false);
    })();
    return () => {
      cancelled = true;
    };
  }, [props.token, props.customerId]);

  if (loading) {
    return (
      <div className="tal-screen" dir="rtl" lang="fa">
        <p className="tal-muted">در حال بارگذاری امانات…</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="tal-screen" dir="rtl" lang="fa">
        <h1>امانات من</h1>
        <p className="tal-error" role="alert">
          {error}
        </p>
      </div>
    );
  }

  return (
    <div className="tal-screen tal-custody-list" dir="rtl" lang="fa">
      <header className="tal-header">
        <h1>امانات من</h1>
        <p className="tal-muted">فیزیکی — منبع حقیقت Talamala (فقط مشاهده)</p>
      </header>

      {items.length === 0 ? (
        <p className="tal-muted">امانتی ثبت نشده است</p>
      ) : (
        <ul className="tal-list">
          {items.map((it) => (
            <li key={it.id} className="tal-list-item">
              <div className="tal-list-title">{it.description}</div>
              <div className="tal-list-meta">
                <span dir="ltr">{it.weight_grams}</span> گرم ·{' '}
                {statusLabel[it.status] ?? it.status}
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
