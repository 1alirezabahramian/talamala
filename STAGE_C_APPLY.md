# Stage C apply

```bash
cd /path/to/talamala
unzip -o talamala-stage-C-otp.zip
php backend/bin/http_smoke.php   # expect PASS=32
cd backend
php -S 127.0.0.1:8080 -t public public/router.php
# browser: http://127.0.0.1:8080/otp-demo.html
```

Then commit:

```bash
git add frontend/customer backend/public/otp-demo.html \
  backend/app/Application/Identity/OtpAuthApplicationService.php \
  backend/app/Infrastructure/Sms/FakeSmsOtpSender.php \
  docs/00-master
git commit -m "feat(stage-C): wire customer OTP screens to local PHP server"
git push
```
