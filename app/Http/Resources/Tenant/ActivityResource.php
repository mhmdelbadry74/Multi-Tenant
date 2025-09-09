<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
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
            'type' => $this->type,
            'subject' => $this->subject,
            'description' => $this->description,
            'happened_at' => $this->happened_at?->toISOString(),
            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }),
            'contact' => $this->whenLoaded('contact', function () {
                return new ContactResource($this->contact);
            }),
            'deal' => $this->whenLoaded('deal', function () {
                return new DealResource($this->deal);
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
