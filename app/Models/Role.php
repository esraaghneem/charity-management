<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
        'location_en',
        'location_ar',
        'status',
        'image',
        'start_end_time',
        'start_end_date',
        'description_en',
        'description_ar'
    ];

    // علاقة مع المتطوع (Volunteers)
     public function volunteers()
    {
        return $this->belongsToMany(Volunteer::class, 'role_volunteer', 'role_id', 'volunteer_id')->withPivot('status');
    }
}
