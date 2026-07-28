<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\UserResource;
use App\Models\Appointment;
use App\Models\User;

class ClientController extends Controller
{
    public function show(User $client)
    {
        $appointments = Appointment::query()
            ->where('client_id', $client->id)
            ->with(['service', 'barber', 'branch'])
            ->orderBy('date_time', 'desc')
            ->get();

        return response()->json([
            'client' => new UserResource($client),
            'appointments' => AppointmentResource::collection($appointments),
        ]);
    }
}
