<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nim_or_email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->resolveUser($validated['nim_or_email']);

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'nim_or_email' => ['Kredensial tidak valid.'],
            ]);
        }

        $token = $user->createToken('mobile', ['mobile'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }

    private function resolveUser(string $nimOrEmail): ?User
    {
        $user = User::where('email', $nimOrEmail)->first();

        if ($user) {
            return $user;
        }

        if (class_exists(Mahasiswa::class)) {
            /** @var Mahasiswa|null $mhs */
            $mhs = Mahasiswa::where('nim', $nimOrEmail)->first();

            return $mhs?->user;
        }

        return null;
    }
}
