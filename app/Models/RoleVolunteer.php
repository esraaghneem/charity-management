<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleVolunteer extends Model
{
    protected $table = 'role_volunteer';

    public function roles()
    {
        return $this->belongsTo(Role::class,'role_id');
    }

    public function volunteers()
    {
        return $this->belongsTo(Volunteer::class, 'volunteer_id');
    }
}