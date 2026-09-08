<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_en',
        'title_ar',
        'location_en',
        'location_ar',
        'image',
        'description_en',
        'description_ar',
    ];
}