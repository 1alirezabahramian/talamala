/**
 * Staff custody ops — Amanat lifecycle.
 * weight_grams is decimal string only (no float math client-side).
 */

import { apiPost, type ApiResult } from './client';

export type CustodyReceiveRequest = {
  customer_id: string;
  description: string;
  weight_grams: string;
  fineness?: string;
  barcode_ref?: string;
};

export type CustodyReceiveResponse = {
  id: string;
  status: string;
  weight_grams: string;
};

export type CustodyTransitionResponse = {
  id: string;
  status: string;
};

export async function custodyReceive(
  token: string,
  body: CustodyReceiveRequest,
): Promise<ApiResult<CustodyReceiveResponse>> {
  return apiPost<CustodyReceiveResponse>('/v1/admin/custody/receive', body, token);
}

export async function custodyMarkReady(
  token: string,
  custodyId: string,
): Promise<ApiResult<CustodyTransitionResponse>> {
  return apiPost<CustodyTransitionResponse>(
    `/v1/admin/custody/${encodeURIComponent(custodyId)}/ready`,
    {},
    token,
  );
}

export async function custodyDeliver(
  token: string,
  custodyId: string,
): Promise<ApiResult<CustodyTransitionResponse>> {
  return apiPost<CustodyTransitionResponse>(
    `/v1/admin/custody/${encodeURIComponent(custodyId)}/deliver`,
    {},
    token,
  );
}
