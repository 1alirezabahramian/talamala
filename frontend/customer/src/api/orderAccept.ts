/**
 * Accept order from immutable quote_id.
 * Settlement always blocked_by_ground_truth until Kimia write GT clears.
 * Dev fixture seed is optional and marked non-live.
 */

import { apiPost, type ApiResult } from './client';

export type AcceptOrderResponse = {
  order_id: string;
  status: string;
  from_idempotency_cache?: boolean;
  settlement: string;
};

export type SeedQuoteResponse = {
  quote_id: string;
  expires_at: string;
  note?: string;
};

export async function acceptOrderFromQuote(
  quoteId: string,
  idempotencyKey: string,
  token?: string,
  customerIdFallback?: string,
): Promise<ApiResult<AcceptOrderResponse>> {
  const extra: Record<string, string> = {
    'Idempotency-Key': idempotencyKey,
  };
  if (!token && customerIdFallback) {
    extra['X-Customer-Id'] = customerIdFallback;
  }
  return apiPost<AcceptOrderResponse>(
    '/v1/customer/orders/accept',
    { quote_id: quoteId },
    token,
    extra,
  );
}

/**
 * Dev-only: POST /v1/dev/seed-quote with X-Talamala-Dev: 1
 * Fixture prices — NOT a live price provider.
 */
export async function seedDevQuote(
  customerId: string,
  body?: { quantity?: string; unit_price_rial?: string; total_rial?: string },
): Promise<ApiResult<SeedQuoteResponse>> {
  return apiPost<SeedQuoteResponse>(
    '/v1/dev/seed-quote',
    {
      customer_id: customerId,
      quantity: body?.quantity ?? '1.000',
      unit_price_rial: body?.unit_price_rial ?? '350000000',
      total_rial: body?.total_rial ?? '350000000',
    },
    undefined,
    { 'X-Talamala-Dev': '1' },
  );
}
