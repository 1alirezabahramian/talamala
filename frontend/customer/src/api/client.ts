/**
 * Thin API client. Tenant is Host-based — no tenant_id in body.
 * All financial numbers treated as opaque strings from server.
 */

export type ApiResult<T> =
  | { ok: true; data: T }
  | { ok: false; status: number; error: string };

export async function apiPost<T>(
  path: string,
  body: Record<string, unknown>,
  token?: string,
): Promise<ApiResult<T>> {
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }
  try {
    const res = await fetch(path, {
      method: 'POST',
      headers,
      body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      return { ok: false, status: res.status, error: data.error ?? 'request_failed' };
    }
    return { ok: true, data: data as T };
  } catch {
    return { ok: false, status: 0, error: 'network_error' };
  }
}

export async function apiGet<T>(path: string, token: string): Promise<ApiResult<T>> {
  try {
    const res = await fetch(path, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      return { ok: false, status: res.status, error: data.error ?? 'request_failed' };
    }
    return { ok: true, data: data as T };
  } catch {
    return { ok: false, status: 0, error: 'network_error' };
  }
}
