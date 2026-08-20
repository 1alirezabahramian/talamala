import { useCallback, useEffect, useState } from 'react';
import { fetchAdminCustomers, type AdminCustomerItem } from '../api/customers';
import { EmptyBlock, ErrorBlock, LoadingBlock, NoticeBanner, StatusBadge } from '../ui';

export type CustomersListScreenProps = { token: string };

export function CustomersListScreen(props: CustomersListScreenProps) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [items, setItems] = useState<AdminCustomerItem[]>([]);
  const [reload, setReload] = useState(0);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    const res = await fetchAdminCustomers(props.token);
    if (!res.ok) {
      setError(res.message || res.error || 'خطا در خواندن مشتریان');
      setItems([]);
    } else {
      setItems(res.data.items ?? []);
    }
    setLoading(false);
  }, [props.token]);

  useEffect(() => { void load(); }, [load, reload]);

  if (loading) return <LoadingBlock label="در حال بارگذاری مشتریان…" />;
  if (error) return <div className="tal-screen" dir="rtl" lang="fa"><h1>مشتریان</h1><ErrorBlock message={error} onRetry={() => setReload((n) => n + 1)} /></div>;

  return (
    <div className="tal-screen" dir="rtl" lang="fa">
      <header className="tal-header">
        <h1>مشتریان tenant</h1>
        <NoticeBanner tone="info">فقط پروفایل — بدون نمایش موجودی Kimia. اتصال مالی فقط با flag.</NoticeBanner>
      </header>
      <p style={{ marginBottom: '0.75rem' }}><button type="button" className="tal-btn-ghost" onClick={() => setReload((n) => n + 1)}>تازه‌سازی</button></p>
      {items.length === 0 ? <EmptyBlock title="مشتری‌ای نیست">هنوز مشتری در این tenant ثبت نشده است.</EmptyBlock> : (
        <ul className="tal-list">{items.map((it) => <li key={it.customer_id} className="tal-list-item">
          <div className="tal-list-title">{it.full_name || it.mobile}</div>
          <div className="tal-list-meta"><span dir="ltr">{it.mobile}</span><br />وضعیت: <StatusBadge value={it.access_status} />{' · '}Kimia: {it.kimia_bound ? 'متصل' : 'بدون اتصال'}</div>
        </li>)}</ul>
      )}
    </div>
  );
}
