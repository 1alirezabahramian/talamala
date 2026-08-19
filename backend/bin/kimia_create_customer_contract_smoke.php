<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap_autoload.php';
require_once dirname(__DIR__) . '/app/Integrations/Kimia/KimiaExceptions.php';

use Talamala\Integrations\Kimia\FakeKimiaCreateCustomerClient;
use Talamala\Integrations\Kimia\HttpKimiaCreateCustomerClient;
use Talamala\Integrations\Kimia\KimiaContractNotGroundedException;
use Talamala\Integrations\Kimia\KimiaCreateCustomerContract;

$pass=0; $fail=0; $root=dirname(__DIR__,2);
function ok(string $n): void { global $pass; ++$pass; echo "OK  {$n}\n"; }
function bad(string $n,string $m): void { global $fail; ++$fail; echo "FAIL {$n}: {$m}\n"; }

$fromFile=KimiaCreateCustomerContract::fromJsonFile($root.'/docs/providers/official/KIMIA_CREATE_CUSTOMER_CONTRACT.json');
if (!$fromFile->isGrounded()) ok('repo_contract_not_grounded'); else bad('repo_contract_not_grounded','expected NOT grounded');
$http=new HttpKimiaCreateCustomerClient('http://example.invalid','u','p',$fromFile);
try { $http->create(['Name'=>'x']); bad('http_refuses_ungrounded','expected exception'); } catch (KimiaContractNotGroundedException) { ok('http_refuses_ungrounded'); }

$fixture=new KimiaCreateCustomerContract(true,'POST','/api/__test_only_not_live__','TestOnly',['Name','Mobile'],['Type'],200,'AccountId',[],null,'UNIT TEST FIXTURE ONLY');
try { (new KimiaCreateCustomerContract(true,'GET','/api/account','TestOnly',['Name'],[],200,'AccountId',[],null,'TEST'))->assertGroundedForHttp(); bad('grounded_method_must_be_post','expected throw'); } catch (KimiaContractNotGroundedException) { ok('grounded_method_must_be_post'); }
try { (new KimiaCreateCustomerContract(true,'POST','https://evil.example/api/account','TestOnly',['Name'],[],200,'AccountId',[],null,'TEST'))->assertGroundedForHttp(); bad('grounded_path_must_be_relative','expected throw'); } catch (KimiaContractNotGroundedException) { ok('grounded_path_must_be_relative'); }

$fake=new FakeKimiaCreateCustomerClient($fixture);
try { $fake->create(['Name'=>'A']); bad('fake_requires_required_fields','expected missing Mobile'); } catch (\InvalidArgumentException) { ok('fake_requires_required_fields'); }
$res=$fake->create(['Name'=>'A','Mobile'=>'09121234567']);
if ($res->accountId!==null && $res->path==='/api/__test_only_not_live__') ok('fake_create_with_fixture'); else bad('fake_create_with_fixture','bad result');
try { $fake->create(['Name'=>'A','Mobile'=>'09121234567','Invented'=>1]); bad('fake_rejects_unknown_field','expected throw'); } catch (\InvalidArgumentException) { ok('fake_rejects_unknown_field'); }

echo "---\nPASS={$pass} FAIL={$fail}\n";
echo "NOTE: Live Create not executed. GT-002 remains open until swagger extract grounds contract JSON.\n";
exit($fail===0?0:1);
