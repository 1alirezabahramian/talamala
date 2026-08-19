import type { ReactNode } from 'react';

export function EmptyBlock(props: { title?: string; children: ReactNode }) {
  return (
    <div className="tal-empty" dir="rtl" lang="fa">
      {props.title ? <p className="tal-list-title">{props.title}</p> : null}
      <p className="tal-muted">{props.children}</p>
    </div>
  );
}
