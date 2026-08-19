<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap_autoload.php';
require_once dirname(__DIR__) . '/app/Integrations/Kimia/KimiaExceptions.php';

use Talamala\Integrations\Kimia\FakeKimiaCreateCustomerClient;
use Talamala\Integrations\Kimia\KimiaContractNotGroundedException;
use Talamala\Integrations\Kimia\KimiaAccountDtoInput;
use Talamala\Integrations\Kimia\KimiaCreateCustomerContract;

$pass=0; $fail=0; $root=dirname(__DIR__,2);
function ok(string $n): void { global $pass; ++$pass; echo "OK  {$n}\n"; }
function bad(string $n,string $m): void { global $fail; ++$fail; echo "FAIL {$n}: {$m}\n"; }

$fromFile=KimiaCreateCustomerContract::fromJsonFile($root.'/docs/providers/official/KIMIA_CREATE_CUSTOMER_CONTRACT.json');
if ($fromFile->isGrounded()) ok('repo_contract_grounded'); else bad('repo_contract_grounded','expected grounded live Swagger contract');
if ($fromFile->method==='POST' && $fromFile->path==='/api/account' && $fromFile->requestSchema==='AccountDto') ok('repo_contract_exact_target'); else bad('repo_contract_exact_target','wrong method/path/schema');
if ($fromFile->requiredFields===[]) ok('swagger_allows_zero_required_fields'); else bad('swagger_allows_zero_required_fields','Swagger AccountDto has no required array');
$needed=['AccountCode','AccountId','Address','Comment','DateBirthday','EconomicCode','IsVisible','Mobile','Name','NationalCode','PostalCode','ShopName','Tel','Type'];
$actual=$fromFile->optionalFields; sort($needed); sort($actual);
if ($actual===$needed) ok('repo_contract_optional_fields_exact'); else bad('repo_contract_optional_fields_exact','optional field set differs from live Swagger');
if ($fromFile->successHttpStatus===200 && $fromFile->successIdField===null) ok('repo_contract_success_primitive_id'); else bad('repo_contract_success_primitive_id','expected HTTP 200 primitive integer id');
try { $fromFile->assertGroundedForHttp(); ok('grounded_http_guard_accepts_live_contract'); } catch (\Throwable $e) { bad('grounded_http_guard_accepts_live_contract',$e->getMessage()); }
try { $fromFile->assertPayloadKeys(['Name'=>'A','Mobile'=>'09121234567','NationalCode'=>'0012345678','Type'=>3]); ok('grounded_payload_known_keys'); } catch (\Throwable $e) { bad('grounded_payload_known_keys',$e->getMessage()); }
try { $fromFile->assertPayloadKeys(['Invented'=>1]); bad('grounded_payload_rejects_unknown','expected throw'); } catch (\InvalidArgumentException) { ok('grounded_payload_rejects_unknown'); }
try { (new KimiaCreateCustomerContract(true,'GET','/api/account','AccountDto',[],['Name'],200,null,[],null,'TEST'))->assertGroundedForHttp(); bad('grounded_method_must_be_post','expected throw'); } catch (KimiaContractNotGroundedException) { ok('grounded_method_must_be_post'); }
try { (new KimiaCreateCustomerContract(true,'POST','https://evil.example/api/account','AccountDto',[],['Name'],200,null,[],null,'TEST'))->assertGroundedForHttp(); bad('grounded_path_must_be_relative','expected throw'); } catch (KimiaContractNotGroundedException) { ok('grounded_path_must_be_relative'); }

$fixture=new KimiaCreateCustomerContract(true,'POST','/api/__test_only_not_live__','TestOnly',['Name','Mobile'],['Type'],200,'AccountId',[],null,'UNIT TEST FIXTURE ONLY');
$fake=new FakeKimiaCreateCustomerClient($fixture);
try { $fake->create(['Name'=>'A']); bad('fake_requires_fixture_required_fields','expected missing Mobile'); } catch (\InvalidArgumentException) { ok('fake_requires_fixture_required_fields'); }
$res=$fake->create(['Name'=>'A','Mobile'=>'09121234567']);
if ($res->accountId!==null && $res->path==='/api/__test_only_not_live__') ok('fake_create_with_fixture'); else bad('fake_create_with_fixture','bad result');

try {
    KimiaAccountDtoInput::assertValues(['Type' => 6]);
    bad('dto_type_create_allowlist', 'bank type 6 must not pass Create allowlist');
} catch (\InvalidArgumentException) {
    ok('dto_type_create_allowlist');
}
try {
    KimiaAccountDtoInput::assertValues(['Type' => 3, 'Name' => 'ok']);
    ok('dto_type_retail_allowed');
} catch (\Throwable $e) {
    bad('dto_type_retail_allowed', $e->getMessage());
}
try {
    KimiaAccountDtoInput::assertValues(['Name' => str_repeat('x', 256)]);
    bad('dto_name_maxlength', 'expected throw');
} catch (\InvalidArgumentException) {
    ok('dto_name_maxlength');
}
try {
    KimiaAccountDtoInput::assertValues(['Name' => 'A', 'Type' => 1]);
    ok('dto_wholesale_allowed');
} catch (\Throwable $e) {
    bad('dto_wholesale_allowed', $e->getMessage());
}
try {
    KimiaAccountDtoInput::assertValues(['Type' => 10]);
    ok('dto_custody_allowed');
} catch (\Throwable $e) {
    bad('dto_custody_allowed', $e->getMessage());
}
try {
    KimiaAccountDtoInput::assertValues(['Type' => '3']);
    bad('dto_integer_string_rejected', 'numeric strings must not satisfy integer/int32');
} catch (\InvalidArgumentException) {
    ok('dto_integer_string_rejected');
}
try {
    KimiaAccountDtoInput::assertValues(['AccountId' => 2147483648]);
    bad('dto_int32_range', 'value outside int32 must be rejected');
} catch (\InvalidArgumentException) {
    ok('dto_int32_range');
}

echo "---\nPASS={$pass} FAIL={$fail}\n";
echo "NOTE: Live Create not executed. Core Swagger HTTP contract is grounded; duplicate/validation/readback semantics remain partial and require separate evidence.\n";
exit($fail===0?0:1);
