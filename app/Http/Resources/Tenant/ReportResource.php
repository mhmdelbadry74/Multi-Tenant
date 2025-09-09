<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'summary' => $this->when(isset($this['summary']), $this['summary']),
            'by_status' => $this->when(isset($this['by_status']), $this['by_status']),
            'by_month' => $this->when(isset($this['by_month']), $this['by_month']),
            'by_type' => $this->when(isset($this['by_type']), $this['by_type']),
            'by_company' => $this->when(isset($this['by_company']), $this['by_company']),
            'recent_contacts' => $this->when(isset($this['recent_contacts']), function () {
                return ContactResource::collection($this['recent_contacts']);
            }),
            'recent_activities' => $this->when(isset($this['recent_activities']), function () {
                return ActivityResource::collection($this['recent_activities']);
            }),
        ];
    }
}
