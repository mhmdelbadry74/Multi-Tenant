<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
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
            'title' => $this->title,
            'amount' => $this->amount,
            'status' => $this->status,
            'description' => $this->description,
            'contact' => $this->whenLoaded('contact', function () {
                return new ContactResource($this->contact);
            }),
            'assigned_user' => $this->whenLoaded('assignedUser', function () {
                return new UserResource($this->assignedUser);
            }),
            'activities' => $this->whenLoaded('activities', function () {
                return ActivityResource::collection($this->activities);
            }),
            'closed_at' => $this->closed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
