/**
 * Staff auth — login + optional first-login password rotate.
 * Demo defaults: operator / ChangeMe-Now-1
 */

import { apiPost, type ApiResult } from './client';

export type StaffLoginResponse = {
  staff_id: string;
  must_change_password?: boolean;
  access_token?: string;
  token_type?: string;
};

export type StaffRotateResponse = {
  ok?: boolean;
  rotated?: boolean;
  [key: string]: unknown;
};

export async function staffLogin(
  username: string,
  password: string,
): Promise<ApiResult<StaffLoginResponse>> {
  return apiPost<StaffLoginResponse>('/v1/auth/staff/login', { username, password });
}

export async function staffRotatePassword(
  staffId: string,
  currentPassword: string,
  newPassword: string,
  token?: string,
): Promise<ApiResult<StaffRotateResponse>> {
  return apiPost<StaffRotateResponse>(
    '/v1/auth/staff/password/rotate',
    { current_password: currentPassword, new_password: newPassword },
    token,
    { 'X-Staff-Id': staffId },
  );
}

/** Revoke current Bearer session. */
export async function logout(token: string): Promise<ApiResult<{ revoked?: boolean }>> {
  return apiPost<{ revoked?: boolean }>('/v1/auth/logout', {}, token);
}
