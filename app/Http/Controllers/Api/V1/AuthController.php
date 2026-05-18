<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Notifications\VerifyEmailQueued;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'password'   => $request->password,
        ]);

        $user->assignRole('professional');

        $user->notify(new VerifyEmailQueued);

        return response()->json([
            'message' => 'Registration successful. Please check your email to verify your account.',
            'user'    => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
            ],
        ], 201);
    }

    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');

        // Ignorar la firma si estamos en local para evitar problemas con localhost vs 127.0.0.1, o simplemente usar hash_equals
        if (! $request->hasValidSignature() && ! app()->environment('local')) {
            return redirect($frontendUrl . '/login?status=error');
        }

        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect($frontendUrl . '/login?status=error');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect($frontendUrl . '/login?status=already-verified');
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return redirect($frontendUrl . '/login?status=success');
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();

        // Respuesta genérica para no exponer si el email existe o no
        if (! $user || $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'If the account exists and is unverified, a new link has been sent.'], 200);
        }

        $user->notify(new VerifyEmailQueued);

        return response()->json(['message' => 'Verification email resent.'], 200);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            $message = $user->deactivated_by_admin
                ? 'Tu cuenta fue desactivada. Contactá al administrador.'
                : 'Tu cuenta está desactivada. Podés reactivarla desde la app.';

            return response()->json(['message' => $message], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Please verify your email address before logging in.'], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
                'locale'     => $user->locale,
                'role'       => $user->getRoleNames()->first(),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        // Siempre 200 para no exponer si el email existe en el sistema
        return response()->json(['message' => 'If that email exists in our system, a reset link has been sent.']);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 400);
        }

        return response()->json(['message' => __($status)]);
    }

    public function updateLocale(Request $request): JsonResponse
    {
        $request->validate([
            'locale' => 'required|string|in:es,en,pt',
        ]);

        $request->user()->update([
            'locale' => $request->locale,
        ]);

        return response()->json(['message' => 'Locale updated successfully.']);
    }
}
