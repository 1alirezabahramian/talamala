/**
 * Backoffice API client.
 * Tenant from X-Talamala-Host (fail-closed). No tenant_id in body.
 */

export type ApiResult<T> =
  | { ok: true; data: T; status: number }
  | { ok: false; status: number; error: string; message?: string };

export type ClientConfig = {
  baseUrl: string;
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

function baseHeaders(token?: string, extra?: Record<string, string>): Record<string, string> {
  const h: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Talamala-Host': config.tenantHost,
    ...extra,
  };
  if (token) {
    h.Authorization = `Bearer ${token}`;
  }
  return h;
}

async function parseResult<T>(res: Response): Promise<ApiResult<T>> {
  const body = await res.json().catch(() => ({}));
  if (!res.ok) {
    return {
      ok: false,
      status: res.status,
      error: String(body.error ?? 'http_error'),
      message: body.message,
    };
  }
  return { ok: true, data: body as T, status: res.status };
}

export async function apiGet<T>(
  path: string,
  token?: string,
  extra?: Record<string, string>,
): Promise<ApiResult<T>> {
  try {
    const res = await fetch(`${config.baseUrl}${path}`, {
      method: 'GET',
      headers: baseHeaders(token, extra),
    });
    return parseResult<T>(res);
  } catch {
    return { ok: false, status: 0, error: 'network_error', message: 'network' };
  }
}

export async function apiPost<T>(
  path: string,
  body?: unknown,
  token?: string,
  extra?: Record<string, string>,
): Promise<ApiResult<T>> {
  try {
    const res = await fetch(`${config.baseUrl}${path}`, {
      method: 'POST',
      headers: baseHeaders(token, extra),
      body: body === undefined ? undefined : JSON.stringify(body),
    });
    return parseResult<T>(res);
  } catch {
    return { ok: false, status: 0, error: 'network_error', message: 'network' };
  }
}
