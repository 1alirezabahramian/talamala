<?php

declare(strict_types=1);

namespace Talamala\Integrations\Jibit;

/**
 * Boundary for Jibit Identicator (official v1.5.2 contract).
 * Token lifecycle, matching/identity flows only according to official PDF.
 * Live readiness requires real sandbox/production credentials (not in this packet).
 *
 * Jibit verification is NOT equivalent to admin approval or runtime customer access.
 */
interface JibitIdentityClient
{
    /**
     * National code + mobile match (Shahkar-style) when supported by contract.
     * Implementation must follow official request/response shapes from the PDF.
     */
    public function matchNationalCodeWithMobile(string $nationalCode, string $mobile): JibitMatchResult;
}
