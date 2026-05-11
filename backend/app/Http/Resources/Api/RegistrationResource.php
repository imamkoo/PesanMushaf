<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationResource extends JsonResource
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
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'nik' => $this->nik,
            'address' => $this->address,
            'education_level' => $this->education_level,
            'edition' => $this->edition,
            'school_name' => $this->school_name,
            'page_number' => $this->page_number,
            'status' => $this->payment_status,
            'financial' => [
                'base_price' => $this->base_price,
                'total_payment' => $this->total_payment,
            ],
            'district' => DistrictResource::make($this->whenLoaded('district')),
            'batch' => BatchResource::make($this->whenLoaded('batch')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
