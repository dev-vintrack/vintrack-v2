# VINTrack — Production Deployment

Production deployment is forbidden unless:
1. Relevant Sprint is APPROVED.
2. Local implementation is tested.
3. Production impact is documented.
4. Backup/rollback is ready.
5. Project Owner explicitly authorizes deployment.

Production:
- Neubox/cPanel
- PHP 8.3
- MariaDB 10.6.27
- InnoDB/utf8mb4
- SFTP/File Manager/Cron
- No SSH
- No PHP CLI
- No Git Deployment
- Do not rely on Laravel Scheduler.

Never reset production DB or perform destructive/unscoped updates/deletes.

Deploy via approved SFTP/File Manager process, approved DB migration mechanism and cPanel Cron.

Record:
- date/time;
- Sprint;
- files;
- migrations;
- Cron changes;
- config changes;
- validation;
- issues;
- rollback status.
