/**
 * Kimia Read only — GET /v1/customer/assets
 * All money/weight are opaque decimal strings from server.
 * Never compute balances client-side.
 */

import { apiGet, type ApiResult } from './client';

export type AssetsOk = {
  status: 'ok';
  money_toman: string;
  gold_weight_g: string;
};

export type AssetsNotBound = {
  status: 'not_bound';
  money_toman: string;
  gold_weight_g: string;
  message?: string;
};

export type AssetsResponse = AssetsOk | AssetsNotBound;

/**
 * Prefer Bearer customer session.
 * Outside production, Kernel also accepts X-Customer-Id (skeleton).
 */
export async function fetchCustomerAssets(
  token?: string,
  customerIdFallback?: string,
): Promise<ApiResult<AssetsResponse>> {
  const extra =
    !token && customerIdFallback
      ? { 'X-Customer-Id': customerIdFallback }
      : undefined;
  return apiGet<AssetsResponse>('/v1/customer/assets', token, extra);
}
