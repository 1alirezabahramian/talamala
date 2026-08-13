<?php

declare(strict_types=1);

namespace Talamala\Tests\Feature\Kimia;

use PHPUnit\Framework\TestCase;
use Talamala\Application\Kimia\CustomerFinancialReadService;
use Talamala\Integrations\Kimia\FakeKimiaReadClient;

final class FinancialReadServiceTest extends TestCase
{
    public function testRialToTomanAndGoldWeight(): void
    {
        $fake = new FakeKimiaReadClient();
        // Matches domain workshop evidence shape
        $fake->seedBalance(350, [
            [
                'Weight' => 1,
                'Money' => -2999219914,
                'CurrencyId' => 11,
                'CurrencySymbol' => 'ریال',
            ],
            [
                'Weight' => 0,
                'Money' => 500,
                'CurrencyId' => 12,
                'CurrencySymbol' => '$',
            ],
        ]);

        $svc = new CustomerFinancialReadService($fake);
        $assets = $svc->assetsForKimiaAccount(350);

        $this->assertSame('-299921991', $assets['money_toman']);
        $this->assertSame('1', $assets['gold_weight_g']);
        $this->assertCount(2, $assets['lines']);
    }
}
