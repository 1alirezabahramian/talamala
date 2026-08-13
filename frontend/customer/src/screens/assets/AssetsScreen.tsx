/**
 * Displays server-normalized assets only.
 * money_toman + gold_weight_g from GET /v1/customer/assets
 * Never compute balances client-side.
 */

export type AssetsViewModel = {
  moneyToman: string;
  goldWeightG: string;
  status: 'ok' | 'not_bound' | 'unavailable';
};

export function AssetsScreen(_props: { data: AssetsViewModel | null; loading: boolean }): null {
  return null;
}
