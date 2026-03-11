<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\InvitationCode;
use App\Models\Organization;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function registerCto(array $payload): array
    {
        $result = DB::transaction(function () use ($payload): array {
            $organizationName = trim((string) $payload['organization_name']);
            $candidateSlug = isset($payload['organization_slug']) && $payload['organization_slug'] !== null
                ? (string) $payload['organization_slug']
                : Str::slug($organizationName);

            $baseSlug = $candidateSlug !== '' ? $candidateSlug : 'organization';
            $slug = $baseSlug;
            $suffix = 1;
            while (Organization::query()->where('slug', $slug)->exists()) {
                $suffix++;
                $slug = "{$baseSlug}-{$suffix}";
            }

            $organization = Organization::query()->create([
                'name' => $organizationName,
                'slug' => $slug,
            ]);

            $fullName = trim($payload['first_name'].' '.$payload['last_name']);
            $user = User::query()->create([
                'organization_id' => $organization->id,
                'role' => UserRole::CTO->value,
                'first_name' => $payload['first_name'],
                'last_name' => $payload['last_name'],
                'name' => $fullName !== '' ? $fullName : $payload['email'],
                'email' => mb_strtolower($payload['email']),
                'password' => $payload['password'],
            ]);

            $token = $user->createToken(env('MOBILE_APP_NAME', 'TaskCollabMobile'))->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        });

        return $result;
    }

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
