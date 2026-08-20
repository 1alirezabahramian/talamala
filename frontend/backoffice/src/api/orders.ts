/**
 * Staff order list — read-only. Settlement always blocked until GT-005.
 * No client-side finance math.
 */

import { apiGet, type ApiResult } from './client';

export type AdminOrderItem = {
  order_id: string;
  customer_id: string;
  quote_id: string;
  status: string;
  side: string;
  asset: string;
  quantity: string;
  unit_price_rial: string;
  total_rial: string;
  created_at: string;
  settlement: string;
};

export type AdminOrdersResponse = {
  items: AdminOrderItem[];
};

export async function fetchAdminOrders(
  token: string,
): Promise<ApiResult<AdminOrdersResponse>> {
  return apiGet<AdminOrdersResponse>('/v1/admin/orders', token);
}
