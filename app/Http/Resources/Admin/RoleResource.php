<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            // permissions_count comes from withCount() in RoleService::list()
            'permissions_count' => $this->permissions_count ?? 0,
            // Array of permission IDs — used to pre-select checkboxes on the edit form.
            // whenLoaded() prevents N+1: only included when ->with('permissions') was called.
            'permissions' => $this->whenLoaded(
                'permissions',
                fn () => $this->permissions->pluck('id')->all(),
            ),
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
