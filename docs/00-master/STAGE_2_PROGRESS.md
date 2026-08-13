# Stage 2 Progress (talago)

**Date:** 2026-08-12

## Delivered

| Item | Evidence |
|------|----------|
| OtpAuthApplicationService | request + verify, hashed OTP, tenant-scoped challenges |
| FakeSmsOtpSender | test double; no production use |
| Feature tests | OtpAuthFlowTest, TenantIsolationTest |
| Tenant isolation on OTP | challenge from tenant-a cannot verify under tenant-b |
| Existing vs new mobile | seedExistingCustomer → session; else needsRegistration |
| Audit events | otp.request / otp.verify_failed / otp.verify_success |
| Kimia Swagger archive note | SHA ea3de1aa… from GoldPlatform historical swagger.json |

## Swagger-confirmed (Read + schema inventory)

- Account list filter param: `Type`
- Groups param: `accountType`
- Exchange Action: 32 buy / 64 sell (Swagger)
- Cash/Transfer Action: 2 receive / 4 pay
- RequestId UUID for idempotent mutations
- BalanceDto Money/Weight/CurrencyId

## Still blocked

- Live Kimia write execution
- Create customer production path without credentials
- Price provider, payments, Goftino

## Next

- Wire HTTP controllers for /auth/customer/otp/*
- Staff password rotation service implementation
- Stage 3: KimiaReadClient HTTP adapter against archived swagger only for reads
EOF
find /home/workdir/artifacts/talamala -name "*.php" -print0 | xargs -0 -n1 php -l 2>&1 | grep -v "No syntax" || echo ALL_OK
find /home/workdir/artifacts/talamala -type f | wc -l
php -r '
require "/home/workdir/artifacts/talamala/backend/app/Domain/Identity/CustomerAccessStatus.php";
require "/home/workdir/artifacts/talamala/backend/app/Domain/Identity/OtpChallenge.php";
require "/home/workdir/artifacts/talamala/backend/app/Domain/Identity/AuthResult.php";
require "/home/workdir/artifacts/talamala/backend/app/Domain/Audit/AuditEvent.php";
require "/home/workdir/artifacts/talamala/backend/app/Domain/Audit/AuditLogger.php";
require "/home/workdir/artifacts/talamala/backend/app/Integrations/Sms/SmsSendResult.php";
require "/home/workdir/artifacts/talamala/backend/app/Integrations/Sms/SmsOtpSender.php";
require "/home/workdir/artifacts/talamala/backend/app/Infrastructure/Sms/FakeSmsOtpSender.php";
require "/home/workdir/artifacts/talamala/backend/app/Infrastructure/Persistence/InMemoryAuditLogger.php";
require "/home/workdir/artifacts/talamala/backend/app/Application/Identity/OtpAuthApplicationService.php";
$sms = new Talamala\Infrastructure\Sms\FakeSmsOtpSender();
$audit = new Talamala\Infrastructure\Persistence\InMemoryAuditLogger();
$svc = new Talamala\Application\Identity\OtpAuthApplicationService($sms, $audit);
$svc->seedExistingCustomer("t1", "09121234567", "c1");
$ch = $svc->requestOtp("t1", "09121234567", "login", "x");
$code = $sms->sent[0]["parameters"]["Code"];
$r = $svc->verifyOtp("t1", $ch->id, $code, "x");
echo $r->success && $r->customerId === "c1" ? "OTP_FLOW_OK\n" : "OTP_FLOW_FAIL\n";
'
