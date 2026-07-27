<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BarberResource;
use App\Models\User;
use Illuminate\Http\Request;

class BarberController extends Controller
{
    public function index(Request $request)
    {
        $barbers = User::query()
            ->where('role', 'barber')
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->input('branch_id')))
            ->with('branch')
            ->orderBy('name')
            ->get();

        return BarberResource::collection($barbers);
    }
}
