/**
 * Customer orders — READ list only.
 * total_rial / quantity are opaque decimal strings from server.
 */

import { apiGet, type ApiResult } from './client';

export type OrderItemDto = {
  order_id: string;
  quote_id: string;
  status: string;
  quantity: string;
  total_rial: string;
};

export type OrderListResponse = {
  items: OrderItemDto[];
};

export async function fetchCustomerOrders(
  token?: string,
  customerIdFallback?: string,
): Promise<ApiResult<OrderListResponse>> {
  const extra =
    !token && customerIdFallback
      ? { 'X-Customer-Id': customerIdFallback }
      : undefined;
  return apiGet<OrderListResponse>('/v1/customer/orders', token, extra);
}
