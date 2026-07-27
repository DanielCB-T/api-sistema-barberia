<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Nunca expone password ni remember_token: solo se listan los campos
 * explícitamente permitidos (whitelist), no se ocultan campos de un array
 * que ya trae todo el modelo.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birthdate' => $this->birthdate?->format('Y-m-d'),
            'role' => $this->role,
            'branch_id' => $this->branch_id,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'avatar' => $this->avatar,
            'created_at' => $this->created_at,
        ];
    }
}
