<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * GET /api/branches (público)
     */
    public function index(Request $request)
    {
        $branches = Branch::query()->orderBy('name')->get();

        return BranchResource::collection($branches);
    }

    /**
     * GET /api/branches/{branch} (público)
     */
    public function show(Branch $branch)
    {
        return new BranchResource($branch);
    }

    /**
     * POST /api/branches (solo admin)
     */
    public function store(StoreBranchRequest $request)
    {
        $branch = Branch::create($request->validated());

        return (new BranchResource($branch))
            ->additional(['message' => 'Sucursal creada correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT/PATCH /api/branches/{branch} (solo admin)
     */
    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $branch->update($request->validated());

        return (new BranchResource($branch))
            ->additional(['message' => 'Sucursal actualizada correctamente.']);
    }

    /**
     * DELETE /api/branches/{branch} (solo admin)
     */
    public function destroy(Branch $branch)
    {
        $branch->delete();

        return response()->json([
            'message' => 'Sucursal eliminada correctamente.',
        ]);
    }
}
