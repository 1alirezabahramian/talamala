/**
 * Displays server-normalized assets only.
 * money_toman + gold_weight_g from GET /v1/customer/assets
 * Never compute balances client-side. No price. No writes.
 */

import { useEffect, useState } from 'react';
import { fetchCustomerAssets, type AssetsResponse } from '../../api/assets';

export type AssetsViewModel = {
  moneyToman: string;
  goldWeightG: string;
  status: 'ok' | 'not_bound' | 'unavailable';
  message?: string;
};

export type AssetsScreenProps = {
  /** Bearer token from OTP login when available */
  token?: string;
  /** Local/skeleton only — X-Customer-Id outside production */
  customerId?: string;
  /** Controlled mode: pass data from parent instead of fetching */
  data?: AssetsViewModel | null;
  loading?: boolean;
};

function toViewModel(res: AssetsResponse): AssetsViewModel {
  if (res.status === 'not_bound') {
    return {
      moneyToman: res.money_toman,
      goldWeightG: res.gold_weight_g,
      status: 'not_bound',
      message: res.message,
    };
  }
  return {
    moneyToman: res.money_toman,
    goldWeightG: res.gold_weight_g,
    status: 'ok',
  };
}

export function AssetsScreen(props: AssetsScreenProps) {
  const controlled = props.data !== undefined;
  const [loading, setLoading] = useState(!controlled && (props.loading ?? true));
  const [error, setError] = useState<string | null>(null);
  const [view, setView] = useState<AssetsViewModel | null>(props.data ?? null);
  const [reload, setReload] = useState(0);

  useEffect(() => {
    if (controlled) {
      setView(props.data ?? null);
      setLoading(!!props.loading);
      return;
    }
    let cancelled = false;
    (async () => {
      setLoading(true);
      setError(null);
      const res = await fetchCustomerAssets(props.token, props.customerId);
      if (cancelled) return;
      if (!res.ok) {
        setError(
          res.error === 'kimia_unavailable'
            ? 'منبع مالی موقتاً در دسترس نیست'
            : res.message || res.error || 'خطا در خواندن دارایی',
        );
        setView(null);
      } else {
        setView(toViewModel(res.data));
      }
      setLoading(false);
    })();
    return () => {
      cancelled = true;
    };
  }, [controlled, props.data, props.loading, props.token, props.customerId, reload]);

  if (loading) {
    return (
      <div className="tal-screen tal-assets" dir="rtl" lang="fa">
        <p className="tal-muted">در حال خواندن دارایی…</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="tal-screen tal-assets" dir="rtl" lang="fa">
        <h1>دارایی</h1>
        <p className="tal-error" role="alert">
          {error}
        </p>
        {!controlled ? (
          <button type="button" className="tal-btn" onClick={() => setReload((n) => n + 1)}>
            تلاش مجدد
          </button>
        ) : null}
      </div>
    );
  }

  if (!view) {
    return (
      <div className="tal-screen tal-assets" dir="rtl" lang="fa">
        <h1>دارایی</h1>
        <p className="tal-muted">داده‌ای نیست</p>
      </div>
    );
  }

  return (
    <div className="tal-screen tal-assets" dir="rtl" lang="fa">
      <header className="tal-header">
        <h1>دارایی من</h1>
        <p className="tal-muted">مقادیر فقط از Kimia Read (سرور)</p>
      </header>

      {view.status === 'not_bound' ? (
        <p className="tal-muted" role="status">
          {view.message || 'حساب Kimia هنوز متصل نشده است'}
        </p>
      ) : null}

      <dl className="tal-assets-list">
        <div>
          <dt>موجودی نقد (تومان)</dt>
          <dd dir="ltr">{view.moneyToman}</dd>
        </div>
        <div>
          <dt>وزن طلا (گرم)</dt>
          <dd dir="ltr">{view.goldWeightG}</dd>
        </div>
      </dl>
    </div>
  );
}
