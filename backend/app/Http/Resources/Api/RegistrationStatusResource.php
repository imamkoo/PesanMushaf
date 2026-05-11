<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationStatusResource extends JsonResource
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
            'registration_code' => $this->registration_code,
            'name' => $this->name,
            'school_name' => $this->school_name,
            'edition' => $this->edition,
            'education_level' => $this->education_level,
            'page_number' => $this->page_number,
            'status' => $this->payment_status,
            'total_payment' => $this->total_payment,
            'district' => $this->whenLoaded('district', fn () => $this->district?->name),
            'batch' => $this->whenLoaded('batch', fn () => $this->batch?->name),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
