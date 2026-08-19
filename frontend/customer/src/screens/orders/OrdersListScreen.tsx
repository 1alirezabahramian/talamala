/**
 * لیست سفارش‌ها — فقط خواندن از API موجود.
 * هیچ محاسبهٔ مالی سمت کلاینت.
 */

import { useCallback, useEffect, useState } from 'react';
import { fetchCustomerOrders, type OrderItemDto } from '../../api/orders';

export type OrdersListScreenProps = {
  token?: string;
  customerId?: string;
};

const statusFa: Record<string, string> = {
  accepted: 'پذیرفته‌شده',
  pending: 'در انتظار',
  completed: 'تکمیل‌شده',
  cancelled: 'لغو شده',
  blocked: 'مسدود',
};

export function OrdersListScreen(props: OrdersListScreenProps) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [items, setItems] = useState<OrderItemDto[]>([]);
  const [reload, setReload] = useState(0);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    const res = await fetchCustomerOrders(props.token, props.customerId);
    if (!res.ok) {
      setError(res.message || res.error || 'خطا در خواندن سفارش‌ها');
      setItems([]);
    } else {
      setItems(res.data.items ?? []);
    }
    setLoading(false);
  }, [props.token, props.customerId]);

  useEffect(() => {
    void load();
  }, [load, reload]);

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
        <button type="button" className="tal-btn" onClick={() => setReload((n) => n + 1)}>
          تلاش مجدد
        </button>
      </div>
    );
  }

  return (
    <div className="tal-screen tal-orders-list" dir="rtl" lang="fa">
      <header className="tal-header">
        <h1>سفارش‌های من</h1>
        <p className="tal-muted">نمایش سرور · بدون محاسبه در مرورگر</p>
      </header>

      <p style={{ marginBottom: '0.75rem' }}>
        <button type="button" className="tal-btn-ghost" onClick={() => setReload((n) => n + 1)}>
          تازه‌سازی
        </button>
      </p>

      {items.length === 0 ? (
        <p className="tal-muted">
          هنوز سفارشی ثبت نشده است. از زبانه «پذیرش سفارش» می‌توانید quote موجود را بپذیرید.
        </p>
      ) : (
        <ul className="tal-list">
          {items.map((it) => (
            <li key={it.order_id} className="tal-list-item">
              <div className="tal-list-title" dir="ltr">
                {it.order_id}
              </div>
              <div className="tal-list-meta">
                وضعیت: {statusFa[it.status] ?? it.status}
                <br />
                <span dir="ltr">
                  quote: {it.quote_id} · qty: {it.quantity} · total_rial: {it.total_rial}
                </span>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
