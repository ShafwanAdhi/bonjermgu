<?php

namespace App\Support;

use App\Mail\AccountPasswordResetMail;
use App\Models\AccountPasswordReset;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AccountPasswordResetBroker
{
    public function sendForUsername(string $username): PasswordResetResult
    {
        $user = User::query()
            ->where('username', $username)
            ->first();

        if (! $user) {
            return PasswordResetResult::UserNotFound;
        }

        return $this->sendForUser($user);
    }

    public function sendForUser(User $user): PasswordResetResult
    {
        if (! $user->is_active) {
            return PasswordResetResult::Inactive;
        }

        $email = $this->emailFor($user);

        if (! $email) {
            return PasswordResetResult::MissingEmail;
        }

        $plainToken = Str::random(64);

        DB::transaction(function () use ($user, $email, $plainToken) {
            AccountPasswordReset::query()
                ->where('user_id', $user->id)
                ->delete();

            AccountPasswordReset::create([
                'user_id' => $user->id,
                'email' => $email,
                'token_hash' => $this->hashToken($plainToken),
                'expires_at' => now()->addMinutes($this->expiresInMinutes()),
            ]);
        });

        Mail::to($email)->send(new AccountPasswordResetMail(
            user: $user,
            resetUrl: route('password.reset', ['token' => $plainToken]),
            expiresInMinutes: $this->expiresInMinutes(),
        ));

        return PasswordResetResult::Sent;
    }

    public function reset(string $token, string $password): PasswordResetResult
    {
        $record = AccountPasswordReset::query()
            ->with('user')
            ->where('token_hash', $this->hashToken($token))
            ->first();

        if (! $record || $record->expires_at->isPast()) {
            return PasswordResetResult::InvalidToken;
        }

        if (! $record->user->is_active) {
            return PasswordResetResult::Inactive;
        }

        if (Hash::check($password, $record->user->password)) {
            return PasswordResetResult::SamePassword;
        }

        DB::transaction(function () use ($record, $password) {
            $record->user->update(['password' => $password]);
            $record->delete();
        });

        return PasswordResetResult::Sent;
    }

    public function emailFor(User $user): ?string
    {
        $profile = $user->profile()->first();
        $email = $profile?->email ?? null;

        return is_string($email) && trim($email) !== '' ? trim($email) : null;
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function expiresInMinutes(): int
    {
        return (int) config('auth.passwords.users.expire', 60);
    }
}
