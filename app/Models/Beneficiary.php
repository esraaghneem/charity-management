<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;


class Beneficiary extends Model
{
    use HasFactory,Notifiable,HasApiTokens;

    protected $fillable = [
        'first_name',
        'father_name',
        'mother_name',
        'address_en',
        'address_ar',
        'phone',
        'email',
        'password',
        'fcm_token',
       
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // العلاقة: مستفيد عنده عدة طلبات استفادة
    public function supportRequests()
    {
        return $this->hasMany(SupportRequest::class);
    }

     public function notifications()
    {
        return $this->morphMany(\App\Models\Notification::class, 'notifiable');
    }
}