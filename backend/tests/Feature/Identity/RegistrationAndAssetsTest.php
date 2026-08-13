<?php

declare(strict_types=1);

namespace Talamala\Tests\Feature\Identity;

use PHPUnit\Framework\TestCase;
use Talamala\Application\Identity\CustomerRegistrationService;
use Talamala\Application\Kimia\CustomerFinancialReadService;
use Talamala\Domain\Identity\CustomerAccessStatus;
use Talamala\Http\Controllers\Customer\CustomerAssetsController;
use Talamala\Domain\Tenant\Tenant;
use Talamala\Infrastructure\Persistence\InMemoryAuditLogger;
use Talamala\Infrastructure\Persistence\InMemoryCustomerRepository;
use Talamala\Integrations\Jibit\FakeJibitIdentityClient;
use Talamala\Integrations\Kimia\FakeKimiaReadClient;

final class RegistrationAndAssetsTest extends TestCase
{
    public function testRegistrationThenBindThenAssets(): void
    {
        $repo = new InMemoryCustomerRepository();
        $jibit = new FakeJibitIdentityClient();
        $jibit->allowMatch('0012345678', '09121234567');
        $reg = new CustomerRegistrationService($repo, $jibit, new InMemoryAuditLogger());

        $result = $reg->completeRegistration('t1', [
            'mobile' => '09121234567',
            'national_code' => '0012345678',
            'full_name' => 'علی تست',
        ], 'corr-reg');

        $this->assertTrue($result->success);
        $this->assertSame(CustomerAccessStatus::Limited, $result->customer->accessStatus);
        $this->assertNull($result->customer->kimiaAccountId);

        $bound = $reg->bindKimiaAccount('t1', $result->customer->id, 350, 'corr-bind');
        $this->assertTrue($bound->success);
        $this->assertSame(350, $bound->customer->kimiaAccountId);

        $fakeKimia = new FakeKimiaReadClient();
        $fakeKimia->seedBalance(350, [
            ['Weight' => '2.5', 'Money' => '10000000', 'CurrencyId' => 11, 'CurrencySymbol' => 'ریال'],
        ]);
        $fin = new CustomerFinancialReadService($fakeKimia);
        $ctrl = new CustomerAssetsController($repo, $fin);
        $tenant = new Tenant('t1', 'demo', 'demo.local', true, true);

        $response = $ctrl->assets($tenant, $result->customer->id);
        $this->assertSame(200, $response['status']);
        $this->assertSame('ok', $response['body']['status']);
        $this->assertSame('1000000', $response['body']['money_toman']);
        $this->assertSame('2.5', $response['body']['gold_weight_g']);
    }

    public function testJibitMismatchBlocksRegistration(): void
    {
        $reg = new CustomerRegistrationService(
            new InMemoryCustomerRepository(),
            new FakeJibitIdentityClient(),
            new InMemoryAuditLogger(),
        );
        $result = $reg->completeRegistration('t1', [
            'mobile' => '09120000000',
            'national_code' => '9999999999',
            'full_name' => 'Fail',
        ], 'c');
        $this->assertFalse($result->success);
        $this->assertSame('jibit_mismatch', $result->errorCode);
    }
}
