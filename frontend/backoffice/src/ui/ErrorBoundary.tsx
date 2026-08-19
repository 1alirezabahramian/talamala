import { Component, type ErrorInfo, type ReactNode } from 'react';

type Props = { children: ReactNode };
type State = { error: Error | null };

export class ErrorBoundary extends Component<Props, State> {
  state: State = { error: null };

  static getDerivedStateFromError(error: Error): State {
    return { error };
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    console.error('Talamala backoffice UI error', error, info.componentStack);
  }

  render() {
    if (this.state.error) {
      return (
        <div className="tal-screen" dir="rtl" lang="fa" role="alert">
          <h1>خطای نمایش</h1>
          <p className="tal-muted">صفحه با مشکل مواجه شد. تازه کنید یا دوباره وارد شوید.</p>
          <button type="button" className="tal-btn" onClick={() => this.setState({ error: null })}>
            تلاش مجدد
          </button>
        </div>
      );
    }
    return this.props.children;
  }
}
