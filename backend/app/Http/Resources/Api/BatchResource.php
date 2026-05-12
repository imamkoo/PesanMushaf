<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isFull = $this->registrations_count !== null
            ? $this->resource->isFullByOccupancy((int) $this->registrations_count)
            : ($this->resource->exists ? $this->resource->isFullByOccupancy() : (bool) $this->is_full);

        return [
            'id' => $this->id,
            'district_id' => $this->district_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'batch_number' => $this->batch_number,
            'education_level' => $this->education_level,
            'max_capacity' => $this->max_capacity,
            'is_full' => $isFull,
            'district' => DistrictResource::make($this->whenLoaded('district')),
            'registrations_count' => $this->whenCounted('registrations'),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
