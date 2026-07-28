<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Branch;

class BranchController extends Controller
{
    /**
     * GET /api/branches  (público, sin paginar: son pocas sucursales)
     * Incluye opening_time/closing_time, que usa el frontend para validar
     * el horario comercial antes de agendar una cita (ver tarea 13).
     */
    public function index()
    {
        return BranchResource::collection(Branch::query()->orderBy('name')->get());
    }

    /**
     * GET /api/branches/{branch}  (público)
     */
    public function show(Branch $branch)
    {
        return new BranchResource($branch);
    }
}