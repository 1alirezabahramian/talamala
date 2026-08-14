/**
 * لیست سفارش‌ها — فقط خواندن.
 * هیچ محاسبهٔ مالی سمت کلاینت.
 */

import { useEffect, useState } from 'react';
import { fetchCustomerOrders, type OrderItemDto } from '../../api/orders';

export type OrdersListScreenProps = {
  token?: string;
  customerId?: string;
};

export function OrdersListScreen(props: OrdersListScreenProps) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [items, setItems] = useState<OrderItemDto[]>([]);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      setLoading(true);
      setError(null);
      const res = await fetchCustomerOrders(props.token, props.customerId);
      if (cancelled) return;
      if (!res.ok) {
        setError(res.message || res.error || 'خطا در خواندن سفارش‌ها');
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
        <p className="tal-muted">در حال بارگذاری سفارش‌ها…</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="tal-screen" dir="rtl" lang="fa">
        <h1>سفارش‌های من</h1>
        <p className="tal-error" role="alert">
          {error}
        </p>
      </div>
    );
  }

  return (
    <div className="tal-screen tal-orders-list" dir="rtl" lang="fa">
      <header className="tal-header">
        <h1>سفارش‌های من</h1>
        <p className="tal-muted">مقادیر از سرور — settlement روی write مسدود است</p>
      </header>
      {items.length === 0 ? (
        <p className="tal-muted">سفارشی نیست</p>
      ) : (
        <ul className="tal-list">
          {items.map((it) => (
            <li key={it.order_id} className="tal-list-item">
              <div className="tal-list-title" dir="ltr">
                {it.order_id}
              </div>
              <div className="tal-list-meta" dir="ltr">
                status: {it.status} · quote: {it.quote_id}
                <br />
                qty: {it.quantity} · total_rial: {it.total_rial}
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
