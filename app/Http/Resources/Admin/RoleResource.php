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
            // {id, name} objects — index page uses `name` for the popover display;
            // edit form extracts `id` for MultiSelect pre-selection.
            // whenLoaded() prevents N+1 when permissions were not eager-loaded.
            'permissions' => $this->whenLoaded(
                'permissions',
                fn () => $this->permissions->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->all(),
            ),
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
