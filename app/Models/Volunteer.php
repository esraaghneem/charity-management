<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable; 
use Laravel\Sanctum\HasApiTokens;

class Volunteer extends Authenticatable
{
    use HasApiTokens, HasFactory,Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'address_en',
        'address_ar',
        'academic_certificate_en',
        'academic_certificate_ar',
        'experiences_en',
        'experiences_ar',
        'email',
        'password',
        'fcm_token'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

       public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_volunteer', 'volunteer_id', 'role_id')->withPivot('status');
    }
}
