/**
 * Backoffice registration queue.
 * Lists Limited customers; approve → Active.
 * No financial display of Kimia internal codes.
 */

export type RegistrationQueueItem = {
  customerId: string;
  mobile: string;
  fullName: string | null;
  accessStatus: string;
  kimiaBound: boolean;
  createdAt: string;
};

export function RegistrationQueueScreen(_props: {
  items: RegistrationQueueItem[];
  loading: boolean;
  onApprove: (customerId: string) => Promise<void>;
}): null {
  return null;
}
