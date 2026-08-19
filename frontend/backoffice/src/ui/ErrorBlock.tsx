export function ErrorBlock(props: { message: string; onRetry?: () => void }) {
  return (
    <div className="tal-screen" dir="rtl" lang="fa">
      <p className="tal-error" role="alert">
        {props.message}
      </p>
      {props.onRetry ? (
        <button type="button" className="tal-btn" onClick={props.onRetry}>
          تلاش مجدد
        </button>
      ) : null}
    </div>
  );
}
