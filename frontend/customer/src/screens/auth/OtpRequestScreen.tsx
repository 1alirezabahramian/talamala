/**
 * Customer OTP request — Persian RTL, mobile-first.
 * No financial math on client. Calls backend only.
 *
 * High-fidelity visual gate still applies before broad UI polish (UI/UX spec).
 */

export type OtpRequestProps = {
  onSubmit: (mobile: string) => Promise<void>;
  loading?: boolean;
  error?: string | null;
};

export function OtpRequestScreen(_props: OtpRequestProps): null {
  // Structure placeholder — real React tree lands with design-system gate.
  // Screen responsibilities:
  // 1. Mobile input (Iran format)
  // 2. Submit → POST /v1/auth/customer/otp/request
  // 3. Navigate to OtpVerifyScreen with challenge_id
  // 4. Loading / error / empty states required
  return null;
}
