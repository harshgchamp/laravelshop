<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as ModelsPermission;
use Spatie\Permission\Traits\HasRoles;

class Permission extends ModelsPermission
{
    use HasRoles;

    public function getCreatedAtAttribute()
    {
        return date('d-m-Y H:i', strtotime($this->attributes['created_at']));
    }

    public function getUpdatedAtAttribute()
    {
        return date('d-m-Y H:i', strtotime($this->attributes['updated_at']));
    }
}
