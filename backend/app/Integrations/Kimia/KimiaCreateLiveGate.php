<?php

declare(strict_types=1);

namespace Talamala\Integrations\Kimia;

/**
 * Default-deny gate for any live Kimia Create Customer HTTP call.
 * Contract grounded ≠ authorized to mutate.
 * Requires BOTH:
 *  - explicit env KIMIA_CREATE_ENABLE=1
 *  - contract document live_create_authorized=true (Owner-archived)
 * No silent enable from pilot env.
 */
final class KimiaCreateLiveGate
{
    public static function isEnvEnabled(): bool
    {
        $v = getenv('KIMIA_CREATE_ENABLE');
        if ($v === false || $v === '') {
            return false;
        }

        return $v === '1' || strtolower((string) $v) === 'true';
    }

    /**
     * @throws KimiaContractNotGroundedException
     */
    public static function assertLiveMutationAllowed(KimiaCreateCustomerContract $contract): void
    {
        if (!self::isEnvEnabled()) {
            throw new KimiaContractNotGroundedException(
                'Live Kimia Create is default-deny (KIMIA_CREATE_ENABLE not set). Contract grounded does not authorize mutation.'
            );
        }
        if (!$contract->liveCreateAuthorized) {
            throw new KimiaContractNotGroundedException(
                'Live Kimia Create blocked: contract live_create_authorized=false until explicit Owner authorization is archived.'
            );
        }
        $contract->assertGroundedForHttp();
    }
}
