<?php

namespace App\Application\Auth\Services;

use App\Mail\SendOtpMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OtpService
{
    public const TTL_MINUTES = 5;
    public const LENGTH = 6;

    public function generate(?User $user = null): string
    {
        $otp = (string) random_int(100000, 999999);

        if ($user) {
            $user->update([
                'email_otp' => $otp,
                'email_otp_expire' => Carbon::now()->addMinutes(self::TTL_MINUTES),
            ]);
        }

        return $otp;
    }

    public function generateForSession(string $email, string $type = 'otp'): string
    {
        $otp = (string) random_int(100000, 999999);

        session()->put($this->sessionKey($type), [
            'email' => $email,
            'code' => $otp,
            'expires_at' => Carbon::now()->addMinutes(self::TTL_MINUTES)->getTimestamp(),
        ]);

        return $otp;
    }

    private function sessionKey(string $type): string
    {
        return $type . '_otp';
    }

    public function send(string $email, string $otp, string $subject, string $title): bool
    {
        try {
            Mail::to($email)->send(new SendOtpMail($otp, $subject, $title));

            return true;
        } catch (Throwable $e) {
            Log::error('No se pudo enviar el correo OTP', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function validate(?User $user, string $code): bool
    {
        if (! $user || $user->email_otp === null || $user->email_otp_expire === null) {
            return false;
        }

        if (! hash_equals((string) $user->email_otp, $code)) {
            return false;
        }

        if (Carbon::now()->greaterThan($user->email_otp_expire)) {
            return false;
        }

        $user->update([
            'email_otp' => null,
            'email_otp_expire' => null,
            'email_verified_at' => Carbon::now(),
        ]);

        return true;
    }

    public function validateSession(string $email, string $code, string $type = 'otp'): bool
    {
        $key = $this->sessionKey($type);
        $otp = session($key);

        if (! $otp || $otp['email'] !== $email) {
            return false;
        }

        if (Carbon::now()->getTimestamp() > $otp['expires_at']) {
            session()->forget($key);

            return false;
        }

        if (! hash_equals((string) $otp['code'], $code)) {
            return false;
        }

        session()->forget($key);

        return true;
    }

    public function clear(?User $user = null, string $type = 'otp'): void
    {
        if ($user) {
            $user->update([
                'email_otp' => null,
                'email_otp_expire' => null,
            ]);
        }

        session()->forget($this->sessionKey($type));
    }
}
