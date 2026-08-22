# CR-007 — Cloudmersive PG-05 Integration Result

## Scope completed

- Cloudmersive advanced-file adapter behind `MalwareScanner`.
- Private-stream upload, restrictive content policy, fail-closed mapping and configuration binding.
- Tests for clean, rejected and unconfigured outcomes.

## Not completed

- No real provider call, provider key, Neubox connectivity test or cPanel Cron configuration.
- Privacy/DPA, retention, reuse/sharing and North America processing confirmation remain pending from Cloudmersive.

## Configuration

```
CLOUDMERSIVE_ENABLED=true
CLOUDMERSIVE_API_KEY=<secret>
CLOUDMERSIVE_BASE_URL=https://api.cloudmersive.com
CLOUDMERSIVE_TIMEOUT=20
```

## Tests

`php artisan test tests/Feature/NotificationCases/CloudmersiveMalwareScannerTest.php tests/Feature/NotificationCases/MalwareScanGateTest.php`

Result: 5 passed, 18 assertions.

## Production impact

The provider sends private Evidence to Cloudmersive. Do not enable production scanning until the listed external privacy/contract evidence is approved and the target HTTPS/Cron verification is authorized and executed.

READY FOR OWNER REVIEW
