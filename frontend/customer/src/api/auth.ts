/**
 * Customer OTP + registration API — contracts match OpenAPI auth-v1.3 + Kernel.
 * Never invent response fields.
 */

import { apiGet, apiPost, type ApiResult } from './client';

export type OtpPurpose = 'login' | 'registration';

export type OtpRequestResponse = {
  challenge_id: string;
  expires_at: string;
  purpose: string;
};

export type OtpVerifyAuthenticated = {
  status: 'authenticated';
  access_token: string;
  customer_id: string;
  access_status?: string;
};

export type OtpVerifyRegistrationRequired = {
  status: 'registration_required';
  message?: string;
};

export type OtpVerifySuccess = OtpVerifyAuthenticated | OtpVerifyRegistrationRequired;

export type DevLastOtp = {
  mobile: string | null;
  code: string | null;
  count: number;
};

export type RegisterRequest = {
  mobile: string;
  national_code: string;
  full_name: string;
};

export type RegisterSuccess = {
  customer_id: string;
  access_status: string;
  kimia_bound: boolean;
};

/** Iran mobile normalize for display/API only. */
export function normalizeIranMobile(input: string): string {
  const digits = input.replace(/\D+/g, '');
  if (digits.startsWith('98') && digits.length === 12) {
    return '0' + digits.slice(2);
  }
  if (digits.startsWith('9') && digits.length === 10) {
    return '0' + digits;
  }
  return digits;
}

export function isValidIranMobile(input: string): boolean {
  const m = normalizeIranMobile(input);
  return /^09\d{9}$/.test(m);
}

export async function requestOtp(
  mobile: string,
  purpose: OtpPurpose = 'registration',
): Promise<ApiResult<OtpRequestResponse>> {
  const normalized = normalizeIranMobile(mobile);
  return apiPost<OtpRequestResponse>('/v1/auth/customer/otp/request', {
    mobile: normalized,
    purpose,
  });
}

export async function verifyOtp(
  challengeId: string,
  code: string,
): Promise<ApiResult<OtpVerifySuccess>> {
  return apiPost<OtpVerifySuccess>('/v1/auth/customer/otp/verify', {
    challenge_id: challengeId,
    code: code.trim(),
  });
}

/**
 * POST /v1/auth/customer/register
 * Body exact: mobile, national_code, full_name
 * 201 → customer_id, access_status, kimia_bound
 * Jibit mismatch → error (backend); staff approve is separate.
 */
export async function registerCustomer(
  body: RegisterRequest,
): Promise<ApiResult<RegisterSuccess>> {
  return apiPost<RegisterSuccess>('/v1/auth/customer/register', {
    mobile: normalizeIranMobile(body.mobile),
    national_code: body.national_code.trim(),
    full_name: body.full_name.trim(),
  });
}

export async function fetchDevLastOtp(): Promise<ApiResult<DevLastOtp>> {
  return apiGet<DevLastOtp>('/v1/dev/last-otp', undefined, { 'X-Talamala-Dev': '1' });
}

/** Revoke current Bearer session. */
export async function logout(token: string): Promise<ApiResult<{ revoked: boolean }>> {
  return apiPost<{ revoked: boolean }>('/v1/auth/logout', {}, token);
}
