/**
 * Staff custody operations: receive / mark ready / deliver.
 */

export function CustodyOpsScreen(_props: {
  onReceive: (payload: {
    customerId: string;
    description: string;
    weightGrams: string;
    fineness?: string;
  }) => Promise<void>;
}): null {
  return null;
}
