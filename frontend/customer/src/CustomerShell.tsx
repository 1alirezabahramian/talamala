/**
 * Customer shell — tab navigation for existing screens only.
 */

import { useState } from 'react';
import { AssetsScreen } from './screens/assets/AssetsScreen';
import { CustodyListScreen } from './screens/custody/CustodyListScreen';
import { OrdersListScreen } from './screens/orders/OrdersListScreen';
import { OrderAcceptScreen } from './screens/orders/OrderAcceptScreen';

type Tab = 'assets' | 'custody' | 'orders' | 'accept';

export type CustomerShellProps = {
  token?: string;
  customerId?: string;
};

export function CustomerShell(props: CustomerShellProps) {
  const [tab, setTab] = useState<Tab>('assets');

  return (
    <div className="tal-shell" dir="rtl" lang="fa">
      <nav className="tal-nav" aria-label="منوی مشتری">
        {(
          [
            ['assets', 'دارایی'],
            ['custody', 'امانات'],
            ['orders', 'سفارش‌ها'],
            ['accept', 'پذیرش سفارش'],
          ] as const
        ).map(([id, label]) => (
          <button
            key={id}
            type="button"
            className={tab === id ? 'active' : ''}
            onClick={() => setTab(id)}
            aria-current={tab === id ? 'page' : undefined}
          >
            {label}
          </button>
        ))}
      </nav>
      <main className="tal-shell-main">
        {tab === 'assets' ? (
          <AssetsScreen token={props.token} customerId={props.customerId} />
        ) : null}
        {tab === 'custody' ? (
          <CustodyListScreen token={props.token} customerId={props.customerId} />
        ) : null}
        {tab === 'orders' ? (
          <OrdersListScreen token={props.token} customerId={props.customerId} />
        ) : null}
        {tab === 'accept' ? (
          <OrderAcceptScreen
            token={props.token}
            customerId={props.customerId}
            allowDevSeed
          />
        ) : null}
      </main>
      <footer className="tal-shell-footer tal-muted">Talamala · مقادیر مالی فقط از سرور</footer>
    </div>
  );
}
