const MAP: Record<string, string> = {
  ok: 'فعال',
  active: 'فعال',
  limited: 'محدود',
  pending: 'در انتظار',
  accepted: 'پذیرفته‌شده',
  completed: 'تکمیل‌شده',
  cancelled: 'لغو شده',
  blocked: 'مسدود',
  not_bound: 'بدون اتصال مالی',
  unavailable: 'ناموجود',
  received: 'دریافت‌شده',
  ready: 'آماده‌تحویل',
  delivered: 'تحویل‌شده',
};

export function StatusBadge(props: { value: string }) {
  const label = MAP[props.value] ?? props.value;
  return (
    <span className="tal-badge" data-status={props.value}>
      {label}
    </span>
  );
}
