/**
 * Customer OTP auth API — contracts match OpenAPI auth-v1.3 + Kernel.
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

/** Iran mobile: 09xxxxxxxxx or +989xxxxxxxxx → normalized digits for display only. */
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
 * Dev-only: read last OTP from FakeSms (requires X-Talamala-Dev: 1).
 * Never call in production builds.
 */
export async function fetchDevLastOtp(): Promise<ApiResult<DevLastOtp>> {
  return apiGet<DevLastOtp>('/v1/dev/last-otp', undefined, { 'X-Talamala-Dev': '1' });
}
