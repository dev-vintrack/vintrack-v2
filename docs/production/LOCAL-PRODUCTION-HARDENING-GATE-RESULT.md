# VINTrack — Local Production Hardening Gate PG-05 + PG-18 — RESULT

Date: 2026-08-14
Scope: local only
Production Authorization: **NOT AUTHORIZED**

## 1. Governance and baseline

DEC-044, DEC-045 and DEC-046 were confirmed. Production Readiness remained `READY WITH CONDITIONS`; deployment was not executed. Baseline: **112 tests, 496 assertions, 0 failures**. Global Pint baseline contained pre-existing style debt outside this Gate; Pint was therefore required over affected PHP only. Existing uncommitted Owner documentation was preserved.

## 2. PG-05 implementation

Evidence upload is quarantine-first with persistent `PENDING`, `SCANNING`, `CLEAN`, `INFECTED`, `ERROR` states. Upload never implies CLEAN. Only CLEAN permits ordinary download and satisfies the server-side SUBMIT/RESUBMIT malware gate. Active non-CLEAN documents block submission. Removed documents do not participate. INFECTED remains stored, unavailable for ordinary download and present in scan/audit history.

The additive reversible migration adds queue/claim/retry fields to `notification_case_documents` and append-only `notification_case_document_scans`. Cycle `up/down/up` passed on local MySQL 8.4.3. The design uses portable InnoDB columns, indexes, FK and unique `(document, attempt)`; it adds no MySQL-only feature.

`MalwareScanner` is the Application abstraction. Only `FakeMalwareScanner` and a multiprocess test fake exist; **no production provider or adapter was implemented or selected**. `MalwareScanProcessor` claims a document under row lock, calls the scanner outside the transaction and finalizes only the matching hashed claim. Retryable ERROR uses bounded backoff, at most three attempts; stale SCANNING claims may be recovered after ten minutes. `evidence:process-malware-scans --batch=N` is discrete and bounded (1–100), with no daemon, worker or production Cron.

Multiprocess MySQL evidence for the same document: worker results `CLEAN` and `SCANNING`, fake scanner invocations **1**, persisted attempts **1**, final state **CLEAN**. The second result is the coherent in-progress contract while the claim owner completes. Sequential retry then reads the terminal state without a second invocation. Scan events/history contain document ID, attempt, result and retry scheduling, but never Evidence content.

PG-05 final state: **IMPLEMENTED LOCALLY — PROVIDER SELECTION AND PRODUCTION VERIFICATION REQUIRED**. It is **not CLOSED** because only a fake exists and no provider/integration has Owner approval.

## 3. PG-18 frontend inventory and migration

Critical runtime assets found and self-hosted with exact versions: jQuery 3.7.1; Bootstrap 5.3.2; Bootstrap Icons 1.11.2; DataTables 1.13.6; Buttons 2.4.2; Responsive 2.5.0; JSZip 3.10.1; pdfmake 0.2.7; DataTables Spanish 1.13.6. All are under `public/vendor/vintrack`, included in the artifact and covered by `ASSET-MANIFEST.txt` with byte size and SHA-256. No Node/NPM/Composer dependency was added and production needs no build tool. There is no CDN fallback. **DataTables was not upgraded.**

External critical asset origins `code.jquery.com`, `cdn.datatables.net`, `cdnjs.cloudflare.com` and jsDelivr Bootstrap were removed from application views. Report logos now use an existing local VINTrack image. Remaining external resources are functional/content integrations rather than migrated critical libraries: Carvana/Cartrack iframes, official/legal links and email links. The unused Laravel welcome scaffold still references Bunny Fonts and must remain excluded from production routing/artifact or be cleaned before a future CSP is activated.

Offline evidence: automated tests assert local files, minimum content, versions, JSON language and absence of migrated origins. Browser smoke with no CDN references initialized DataTables, produced its wrapper and pagination, reported two rows and displayed one row at page length one. This is behavioral verification, not only HTTP 200.

PG-18 final state: **CLOSED LOCALLY** for the authorized asset-migration Gate. Production CSP activation and target-environment artifact verification remain external conditions.

## 4. CSP assessment

- `script-src`: target `'self'`; current application has inline scripts in Blade, so nonce/hash refactoring is required before removing `'unsafe-inline'`. No demonstrated need for `'unsafe-eval'` from the migrated baseline.
- `style-src`: target `'self'`; current Blade/layout inline styles and style attributes require inventory/refactoring or narrowly documented hashes/nonces.
- `font-src`: target `'self'`; Bootstrap Icons font is local. Exclude/remove the unused Bunny Fonts scaffold.
- `img-src`: target `'self' data:`; PDF generation uses local filesystem image. Review any provider-returned remote images before enforcement.
- `connect-src`: target `'self'`; current fetch/DataTables AJAX is same-origin. Provider calls are server-side.
- `frame-src`: Carvana and Cartrack origins are required only on the current embedded-tool views and should be narrowly allowlisted if those features remain.
- `default-src`: target `'self'`; add `object-src 'none'`, `base-uri 'self'`, `frame-ancestors 'self'` subject to final production inventory.

No CSP header was enabled by this Gate.

## 5. Validation

Final focused Gate run: **10 tests, 86 assertions, 0 failures**. Added coverage includes pending/download block, CLEAN, INFECTED, ERROR/retry, retry suppression, scan history, submit blocking, removed Evidence and IDOR regression. PG-18 automated offline tests pass, and the browser behavior smoke passed. Migration `up/down/up`: PASS. Final full suite: **116 tests, 545 assertions, 0 failures**. Pint over all affected PHP: PASS after formatting. `git diff --check`: PASS.

Production Readiness: **READY WITH CONDITIONS**
Production Authorization: **NOT AUTHORIZED**
Deployment: **NOT EXECUTED**

## 6. Production conditions

PG-05 still requires Owner-approved provider selection, production adapter implementation/tests and authorized target integration verification. PG-18 requires artifact/hash verification in the target environment and a separately authorized CSP rollout. All existing `BLOCKED_EXTERNAL` Gates remain unchanged. No production, Neubox, SFTP, cPanel, Cron, SMTP, DNS, paid API, real scanner, SPRINT-09 or Production Environment Verification action occurred.

READY FOR OWNER REVIEW
