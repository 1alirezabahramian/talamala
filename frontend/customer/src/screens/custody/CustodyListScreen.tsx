/**
 * امانات من — list only.
 * weight_grams is opaque decimal string from server.
 * No receive/ready/deliver from customer app (staff-only).
 */

import { useEffect, useState } from 'react';
import { fetchCustomerCustody, type CustodyItemDto } from '../../api/custody';
import { EmptyBlock, ErrorBlock, LoadingBlock, NoticeBanner } from '../../ui';

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
  const [reload, setReload] = useState(0);

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
  }, [props.token, props.customerId, reload]);

  if (loading) {
    return <LoadingBlock label="در حال خواندن امانات…" />;
  }

  if (error) {
    return (
      <div className="tal-screen" dir="rtl" lang="fa">
        <h1>امانات من</h1>
        <ErrorBlock message={error} onRetry={() => setReload((n) => n + 1)} />
      </div>
    );
  }

  return (
    <div className="tal-screen tal-custody-list" dir="rtl" lang="fa">
      <header className="tal-header">
        <h1>امانات من</h1>
        <NoticeBanner tone="info">
          فقط مشاهده — دریافت/تحویل فقط توسط staff در بک‌آفیس.
        </NoticeBanner>
      </header>

      {items.length === 0 ? (
        <EmptyBlock title="امانت فعالی نیست">هنوز امانتی ثبت نشده است.</EmptyBlock>
      ) : (
        <ul className="tal-list">
          {items.map((it) => (
            <li key={it.id} className="tal-card">
              <div className="tal-list-title">{it.description}</div>
              <p className="tal-muted" dir="ltr">
                weight_grams: {it.weight_grams}
              </p>
              <p>{statusLabel[it.status] ?? it.status}</p>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
