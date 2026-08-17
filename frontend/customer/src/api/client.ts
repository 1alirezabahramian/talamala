/**
 * Thin API client. Tenant is Host-based — no tenant_id in body.
 * All financial numbers treated as opaque strings from server.
 *
 * Local: PHP server at 127.0.0.1:8080, tenant via X-Talamala-Host (demo.local).
 * Browser cannot override Host; backend accepts X-Talamala-Host as equivalent.
 */

export type ApiResult<T> =
  | { ok: true; data: T; status: number }
  | { ok: false; status: number; error: string; message?: string; retryAfter?: number };

export type ClientConfig = {
  /** API origin, no trailing slash. Default http://127.0.0.1:8080 */
  baseUrl: string;
  /** Tenant host slug sent as X-Talamala-Host. Default demo.local */
  tenantHost: string;
};

const defaultConfig: ClientConfig = {
  baseUrl:
    (typeof window !== 'undefined' &&
      (window as unknown as { __TALAMALA_API__?: string }).__TALAMALA_API__) ||
    'http://127.0.0.1:8080',
  tenantHost:
    (typeof window !== 'undefined' &&
      (window as unknown as { __TALAMALA_TENANT__?: string }).__TALAMALA_TENANT__) ||
    'demo.local',
};

let config: ClientConfig = { ...defaultConfig };

export function configureClient(partial: Partial<ClientConfig>): void {
  config = { ...config, ...partial };
}

export function getClientConfig(): ClientConfig {
  return { ...config };
}

function buildHeaders(token?: string, extra?: Record<string, string>): Record<string, string> {
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-Talamala-Host': config.tenantHost,
    'X-Correlation-Id':
      (typeof crypto !== 'undefined' && 'randomUUID' in crypto
        ? crypto.randomUUID()
        : `web-${Date.now()}-${Math.random().toString(16).slice(2)}`),
  };
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }
  if (extra) {
    Object.assign(headers, extra);
  }
  return headers;
}

function url(path: string): string {
  if (path.startsWith('http://') || path.startsWith('https://')) {
    return path;
  }
  const base = config.baseUrl.replace(/\/$/, '');
  const p = path.startsWith('/') ? path : `/${path}`;
  return `${base}${p}`;
}

export async function apiPost<T>(
  path: string,
  body: Record<string, unknown>,
  token?: string,
  extraHeaders?: Record<string, string>,
): Promise<ApiResult<T>> {
  try {
    const res = await fetch(url(path), {
      method: 'POST',
      headers: buildHeaders(token, extraHeaders),
      body: JSON.stringify(body),
    });
    const data = (await res.json().catch(() => ({}))) as Record<string, unknown>;
    if (!res.ok) {
      const retryHeader = res.headers.get('Retry-After');
      return {
        ok: false,
        status: res.status,
        error: String(data.error ?? 'request_failed'),
        message: data.message != null ? String(data.message) : undefined,
        retryAfter: retryHeader
          ? Number(retryHeader)
          : typeof data.retry_after === 'number'
            ? data.retry_after
            : undefined,
      };
    }
    return { ok: true, data: data as T, status: res.status };
  } catch {
    return { ok: false, status: 0, error: 'network_error', message: 'Cannot reach API server' };
  }
}

export async function apiGet<T>(
  path: string,
  token?: string,
  extraHeaders?: Record<string, string>,
): Promise<ApiResult<T>> {
  try {
    const res = await fetch(url(path), {
      method: 'GET',
      headers: buildHeaders(token, extraHeaders),
    });
    const data = (await res.json().catch(() => ({}))) as Record<string, unknown>;
    if (!res.ok) {
      return {
        ok: false,
        status: res.status,
        error: String(data.error ?? 'request_failed'),
        message: data.message != null ? String(data.message) : undefined,
      };
    }
    return { ok: true, data: data as T, status: res.status };
  } catch {
    return { ok: false, status: 0, error: 'network_error', message: 'Cannot reach API server' };
  }
}
