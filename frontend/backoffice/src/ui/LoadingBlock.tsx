export function LoadingBlock(props: { label?: string }) {
  return (
    <div className="tal-screen" dir="rtl" lang="fa" role="status" aria-live="polite">
      <p className="tal-muted">{props.label ?? 'در حال بارگذاری…'}</p>
    </div>
  );
}
