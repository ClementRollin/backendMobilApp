<?php

namespace App\Services;

use App\Models\InvitationCode;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $payload): array
    {
        $result = DB::transaction(function () use ($payload): array {
            $invitation = InvitationCode::query()
                ->where('code', $payload['invitation_code'])
                ->lockForUpdate()
                ->first();

            if (! $invitation) {
                throw ValidationException::withMessages([
                    'invitation_code' => ['Invitation code is invalid.'],
                ]);
            }

            if ($invitation->used_at !== null) {
                throw ValidationException::withMessages([
                    'invitation_code' => ['Invitation code has already been used.'],
                ]);
            }

            if ($invitation->revoked_at !== null) {
                throw ValidationException::withMessages([
                    'invitation_code' => ['Invitation code has been revoked.'],
                ]);
            }

            if (mb_strtolower($invitation->email) !== mb_strtolower($payload['email'])) {
                throw ValidationException::withMessages([
                    'email' => ['The email must match the invitation target email.'],
                ]);
            }

            $fullName = trim($invitation->first_name.' '.$invitation->last_name);

            $user = User::query()->create([
                'organization_id' => $invitation->organization_id,
                'role' => $invitation->target_role,
                'first_name' => $invitation->first_name,
                'last_name' => $invitation->last_name,
                'name' => $fullName !== '' ? $fullName : $payload['email'],
                'email' => $payload['email'],
                'password' => $payload['password'],
            ]);

            if ($invitation->team_id !== null) {
                TeamMembership::query()->updateOrCreate(
                    ['team_id' => $invitation->team_id, 'user_id' => $user->id],
                    ['organization_id' => $invitation->organization_id]
                );
            }

            $invitation->used_at = now();
            $invitation->save();

            $token = $user->createToken(env('MOBILE_APP_NAME', 'TaskCollabMobile'))->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        });

        return $result;
    }

    public function login(array $payload): array
    {
        $user = User::query()->where('email', $payload['email'])->first();

        if (! $user || ! Hash::check($payload['password'], $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        $token = $user->createToken(env('MOBILE_APP_NAME', 'TaskCollabMobile'))->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
