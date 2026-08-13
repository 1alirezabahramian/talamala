<?php

declare(strict_types=1);

namespace Talamala\Http\Controllers\Customer;

use Talamala\Application\Kimia\CustomerFinancialReadService;
use Talamala\Domain\Identity\CustomerRepository;
use Talamala\Domain\Tenant\Tenant;

/**
 * GET /v1/customer/assets
 * Requires authenticated customer context (customer_id from session).
 * Balances always from Kimia read model — never local ledger.
 */
final class CustomerAssetsController
{
    public function __construct(
        private readonly CustomerRepository $customers,
        private readonly CustomerFinancialReadService $financialRead,
    ) {}

    public function assets(Tenant $tenant, string $customerId): array
    {
        $customer = $this->customers->findById($tenant->id, $customerId);
        if ($customer === null) {
            return ['status' => 404, 'body' => ['error' => 'customer_not_found']];
        }

        if ($customer->accessStatus->value === 'blocked' || $customer->accessStatus->value === 'suspended') {
            return ['status' => 403, 'body' => ['error' => 'access_denied']];
        }

        if (!$customer->isBoundToKimia()) {
            return [
                'status' => 200,
                'body' => [
                    'status' => 'not_bound',
                    'money_toman' => '0',
                    'gold_weight_g' => '0',
                    'message' => 'Kimia account not linked yet',
                ],
            ];
        }

        try {
            $assets = $this->financialRead->assetsForKimiaAccount($customer->kimiaAccountId);
        } catch (\Throwable $e) {
            return [
                'status' => 503,
                'body' => [
                    'error' => 'kimia_unavailable',
                    'message' => 'Financial source temporarily unavailable',
                ],
            ];
        }

        return [
            'status' => 200,
            'body' => [
                'status' => 'ok',
                'money_toman' => $assets['money_toman'],
                'gold_weight_g' => $assets['gold_weight_g'],
                // Do not expose CurrencyId / AccountId / Action codes to customer UI
            ],
        ];
    }
}
