<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            // getRoleNames() returns a Collection of role name strings (Spatie).
            // first() gives the primary role, or null if no role assigned.
            'role' => $this->getRoleNames()->first(),
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
