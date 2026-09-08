<?php

/*
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Project extends Model {
    use HasFactory;

    protected $fillable = ['name']; // يسمح بإدخال اسم المشروع فقط

    // علاقة: المشروع يحتوي على مشاريع فرعية
    public function subProjects() {
        return $this->hasMany(SubProject::class);
    }

    // علاقة: المشروع يحتوي على عدة تبرعات (Polymorphic)
    public function donations() {
        return $this->morphMany(Donation::class, 'donatable');
    }

    // علاقة: المشروع يمكن إضافته إلى سلة التبرعات
    public function cartItems() {
        return $this->hasMany(CartItem::class);
    }
}*/

/*
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model {
    use HasFactory;

    protected $fillable = ['name','image']; // السماح بإدخال اسم المشروع والصورة

    // علاقة: المشروع يحتوي على مشاريع فرعية
    public function subProjects() {
        return $this->hasMany(SubProject::class);
    }

   
    public function cartItems() {
        return $this->hasMany(CartItem::class);
    }


}

*/
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model {
    use HasFactory;

    protected $fillable = ['name_en', 'name_ar', 'image',  'status'];

    // إرجاع الاسم حسب اللغة الحالية
    public function getNameAttribute()
    {
        $locale = app()->getLocale();
        if ($locale === 'ar' && $this->name_ar) {
            return $this->name_ar;
        }
        return $this->name_en ?? $this->name_ar;
    }

    // علاقات أخرى (كما في الكود السابق)
    public function subProjects() {
        return $this->hasMany(SubProject::class);
    }

    public function cartItems() {
        return $this->hasMany(CartItem::class);
    }
}
