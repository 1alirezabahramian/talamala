/**
 * Non-blocking operational notice (RTL-safe).
 * Use for GT/settlement warnings — never invent balances.
 */
export type NoticeBannerProps = {
  children: string;
  tone?: 'info' | 'warn';
  role?: 'status' | 'alert';
};

export function NoticeBanner(props: NoticeBannerProps) {
  const tone = props.tone ?? 'info';
  const cls =
    tone === 'warn' ? 'tal-banner tal-banner-warn' : 'tal-banner tal-banner-info';
  return (
    <p className={cls} role={props.role ?? 'status'}>
      {props.children}
    </p>
  );
}
