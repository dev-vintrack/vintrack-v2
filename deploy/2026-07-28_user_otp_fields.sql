-- VINTrack v2: campos OTP, registro de oficial y entidad
ALTER TABLE users
    ADD COLUMN email_otp VARCHAR(6) NULL AFTER email_verified_at,
    ADD COLUMN email_otp_expire DATETIME NULL AFTER email_otp,
    ADD COLUMN es_oficial TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN entidad VARCHAR(128) NULL AFTER es_oficial;
