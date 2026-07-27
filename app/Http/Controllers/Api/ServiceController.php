<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * GET /api/services  (público, paginado)
     * Filtros opcionales: ?category=Corte&per_page=10&page=2
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        $services = Service::query()
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')))
            ->orderBy('name')
            ->paginate($perPage);

        return ServiceResource::collection($services);
    }

    /**
     * GET /api/services/{service}  (público)
     */
    public function show(Service $service)
    {
        return new ServiceResource($service);
    }

    /**
     * POST /api/services  (solo admin)
     */
    public function store(StoreServiceRequest $request)
    {
        $service = Service::create($request->validated());

        return (new ServiceResource($service))
            ->additional(['message' => 'Servicio creado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT/PATCH /api/services/{service}  (solo admin)
     */
    public function update(UpdateServiceRequest $request, Service $service)
    {
        $service->update($request->validated());

        return (new ServiceResource($service))
            ->additional(['message' => 'Servicio actualizado correctamente.']);
    }

    /**
     * DELETE /api/services/{service}  (solo admin)
     */
    public function destroy(Service $service)
    {
        $service->delete();

        return response()->json([
            'message' => 'Servicio eliminado correctamente.',
        ]);
    }
}
