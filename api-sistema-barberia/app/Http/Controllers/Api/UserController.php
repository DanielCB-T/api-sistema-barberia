<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserController extends Controller
{
    /**
     * GET /api/users  (solo admin)
     * Filtros: ?role=barber&per_page=10
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        $users = User::query()
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->input('role')))
            ->with('branch')
            ->orderBy('name')
            ->paginate($perPage);

        return UserResource::collection($users);
    }

    /**
     * GET /api/users/{user}  (admin, o el propio usuario)
     */
    public function show(Request $request, User $user)
    {
        if (! $request->user()->isAdmin() && $request->user()->id !== $user->id) {
            throw new AccessDeniedHttpException('No tienes permisos para ver este usuario.');
        }

        return new UserResource($user->load('branch'));
    }
}
