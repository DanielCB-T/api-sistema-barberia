<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'message' => $this->message,
            'status' => $this->status,
            'appointment_id' => $this->appointment_id,
            'created_at' => $this->created_at,
        ];
    }
}
