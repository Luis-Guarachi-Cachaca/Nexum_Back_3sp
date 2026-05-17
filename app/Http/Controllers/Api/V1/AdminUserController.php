<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 100);

        $query = User::with(['portfolio', 'roles'])
            ->where('id', '!=', $request->user()->id);

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $users = $query->paginate($perPage);

        return response()->json([
            'data' => $users->map(fn (User $user) => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
                'role'       => $user->roles->first()?->name,
                'is_active'  => $user->is_active,
                'created_at' => $user->created_at->toISOString(),
                'portfolio'  => $user->portfolio
                    ? ['global_privacy' => $user->portfolio->global_privacy]
                    : null,
            ]),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        // Prevenir que el admin se desactive a sí mismo
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot change the status of your own account.'], 422);
        }

        if ($user->is_active) {
            $user->update([
                'is_active'            => false,
                'deactivated_by_admin' => true,
            ]);

            // Revocar todos los tokens activos
            $user->tokens()->delete();

            // Registrar en activity log con el usuario afectado como sujeto
            $action = 'deactivated';
            activity()
                ->performedOn($user)
                ->causedBy($request->user())
                ->withProperties([
                    'attributes' => [
                        'is_active' => false,
                        'deactivated_by_admin' => true,
                    ],
                    'old' => [
                        'is_active' => true,
                        'deactivated_by_admin' => false,
                    ],
                ])
                ->event('deactivated')
                ->log("User {$action}: {$user->email}");

            return response()->json([
                'message' => 'User deactivated successfully.',
                'user'    => ['id' => $user->id, 'email' => $user->email, 'is_active' => false],
            ]);
        }

        $user->update([
            'is_active'            => true,
            'deactivated_by_admin' => false,
        ]);

        // Registrar en activity log con el usuario afectado como sujeto
        $action = 'reactivated';
        activity()
            ->performedOn($user)
            ->causedBy($request->user())
            ->withProperties([
                'attributes' => [
                    'is_active' => true,
                    'deactivated_by_admin' => false,
                ],
                'old' => [
                    'is_active' => false,
                    'deactivated_by_admin' => true,
                ],
            ])
            ->event('reactivated')
            ->log("User {$action}: {$user->email}");

        return response()->json([
            'message' => 'User reactivated successfully.',
            'user'    => ['id' => $user->id, 'email' => $user->email, 'is_active' => true],
        ]);
    }
}
