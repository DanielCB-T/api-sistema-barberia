<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'date_time' => $this->date_time?->format('Y-m-d H:i'),
            'duration' => $this->duration,
            'pay_online' => (bool) $this->pay_online,
            'notify_sms' => (bool) $this->notify_sms,
            'client' => new UserResource($this->whenLoaded('client')),
            'barber' => new UserResource($this->whenLoaded('barber')),
            'service' => new ServiceResource($this->whenLoaded('service')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'history' => AppointmentStatusHistoryResource::collection(
                $this->whenLoaded('statusHistory')
            ),
            'created_at' => $this->created_at,
        ];
    }
}
