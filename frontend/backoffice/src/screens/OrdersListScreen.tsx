/**
 * لیست سفارش‌های tenant — فقط خواندن.
 * settlement همیشه blocked_by_ground_truth تا GT-005.
 */

import { useCallback, useEffect, useState } from 'react';
import { fetchAdminOrders, type AdminOrderItem } from '../api/orders';
import { EmptyBlock, ErrorBlock, LoadingBlock, NoticeBanner, StatusBadge } from '../ui';

export type OrdersListScreenProps = {
  token: string;
};

export function OrdersListScreen(props: OrdersListScreenProps) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [items, setItems] = useState<AdminOrderItem[]>([]);
  const [reload, setReload] = useState(0);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    const res = await fetchAdminOrders(props.token);
    if (!res.ok) {
      setError(res.message || res.error || 'خطا در خواندن سفارش‌ها');
      setItems([]);
    } else {
      setItems(res.data.items ?? []);
    }
    setLoading(false);
  }, [props.token]);

  useEffect(() => {
    void load();
  }, [load, reload]);

  if (loading) {
    return <LoadingBlock label="در حال بارگذاری سفارش‌ها…" />;
  }

  if (error) {
    return (
      <div className="tal-screen" dir="rtl" lang="fa">
        <h1>سفارش‌ها</h1>
        <ErrorBlock message={error} onRetry={() => setReload((n) => n + 1)} />
      </div>
    );
  }

  return (
    <div className="tal-screen" dir="rtl" lang="fa">
      <header className="tal-header">
        <h1>سفارش‌های tenant</h1>
        <NoticeBanner tone="warn">
          تسویه مالی مسدود است (blocked_by_ground_truth) تا تکمیل GT-005 — موجودی Kimia جعل نمی‌شود.
        </NoticeBanner>
      </header>

      <p style={{ marginBottom: '0.75rem' }}>
        <button type="button" className="tal-btn-ghost" onClick={() => setReload((n) => n + 1)}>
          تازه‌سازی
        </button>
      </p>

      {items.length === 0 ? (
        <EmptyBlock title="سفارشی نیست">هنوز سفارشی در این tenant ثبت نشده است.</EmptyBlock>
      ) : (
        <ul className="tal-list">
          {items.map((it) => (
            <li key={it.order_id} className="tal-list-item">
              <div className="tal-list-title" dir="ltr">
                {it.order_id}
              </div>
              <div className="tal-list-meta">
                مشتری: <span dir="ltr">{it.customer_id}</span>
                <br />
                وضعیت: <StatusBadge value={it.status} /> · settlement: {it.settlement}
                <br />
                <span dir="ltr">
                  {it.side}/{it.asset} · qty={it.quantity} · total_rial={it.total_rial}
                </span>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
