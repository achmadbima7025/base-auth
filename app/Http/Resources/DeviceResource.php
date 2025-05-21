<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'device_identifier' => $this->device_identifier,
            'name' => $this->name,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'rejected_by' => $this->rejected_by,
            'rejected_at' => $this->rejected_at,
            'admin_notes' => $this->admin_notes,
            'last_login_ip' => $this->last_login_ip,
            'last_login_at' => $this->last_login_at,
            'last_used_at' => $this->last_used_at,
            'user' => new UserResource($this->whenLoaded('user')),
            'approver' => new UserResource($this->whenLoaded('approver')),
        ];
    }
}
