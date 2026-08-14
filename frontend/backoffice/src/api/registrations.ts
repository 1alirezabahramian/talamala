/**
 * Admin registration queue — list pending + approve.
 * Contracts match RegistrationQueueController (Kernel).
 */

import { apiGet, apiPost, type ApiResult } from './client';

export type RegistrationQueueItemDto = {
  customer_id: string;
  mobile: string;
  full_name: string | null;
  national_code: string;
  access_status: string;
  kimia_bound: boolean;
  created_at: string;
};

export type RegistrationQueueResponse = {
  items: RegistrationQueueItemDto[];
};

export type ApproveResponse = {
  customer_id: string;
  access_status: string;
};

export async function listRegistrationQueue(
  token: string,
): Promise<ApiResult<RegistrationQueueResponse>> {
  return apiGet<RegistrationQueueResponse>('/v1/admin/registrations', token);
}

export async function approveRegistration(
  token: string,
  customerId: string,
): Promise<ApiResult<ApproveResponse>> {
  return apiPost<ApproveResponse>(
    `/v1/admin/registrations/${encodeURIComponent(customerId)}/approve`,
    {},
    token,
  );
}
