/**
 * Customer custody (Amanat) — READ only.
 * GET /v1/customer/custody
 * Talamala is sole truth for physical custody; no client-side invention.
 */

import { apiGet, type ApiResult } from './client';

export type CustodyItemDto = {
  id: string;
  description: string;
  weight_grams: string;
  status: string;
};

export type CustodyListResponse = {
  items: CustodyItemDto[];
};

export async function fetchCustomerCustody(
  token?: string,
  customerIdFallback?: string,
): Promise<ApiResult<CustodyListResponse>> {
  const extra =
    !token && customerIdFallback
      ? { 'X-Customer-Id': customerIdFallback }
      : undefined;
  return apiGet<CustodyListResponse>('/v1/customer/custody', token, extra);
}
