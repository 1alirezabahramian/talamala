/**
 * Staff customer directory — no balances.
 */

import { apiGet, type ApiResult } from './client';

export type AdminCustomerItem = {
  customer_id: string;
  mobile: string;
  full_name: string | null;
  access_status: string;
  kimia_bound: boolean;
  created_at: string;
};

export type AdminCustomersResponse = { items: AdminCustomerItem[] };

export async function fetchAdminCustomers(token: string): Promise<ApiResult<AdminCustomersResponse>> {
  return apiGet<AdminCustomersResponse>('/v1/admin/customers', token);
}
