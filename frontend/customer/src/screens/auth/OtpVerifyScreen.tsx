/**
 * Customer OTP verify screen.
 * On success: session token OR registration_required.
 */

export type OtpVerifyProps = {
  challengeId: string;
  onVerify: (code: string) => Promise<'authenticated' | 'registration_required' | 'error'>;
  loading?: boolean;
  error?: string | null;
};

export function OtpVerifyScreen(_props: OtpVerifyProps): null {
  return null;
}
