<?php
/*
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubProject extends Model {
    protected $fillable = ['project_id', 'name', 'required_amount'];
    
    // إضافة القيم المحسوبة إلى الاستجابة
    protected $appends = ['collected_amount', 'remaining_amount'];

    // علاقة المشروع الفرعي بالتفاصيل الخاصة به
    public function details() {
        return $this->hasMany(SubProjectDetail::class);
    }

    // علاقة التبرعات بالمشروع الفرعي
    public function donations() {
        return $this->morphMany(Donation::class, 'donatable');
    }

    // حساب إجمالي التبرعات لهذا المشروع الفرعي
    public function getCollectedAmountAttribute() {
        return $this->donations()->sum('amount');
    }

    // حساب المبلغ المتبقي لهذا المشروع الفرعي
    public function getRemainingAmountAttribute() {
        return $this->required_amount - $this->collected_amount;
    }
}
*/

/*
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubProject extends Model {
    protected $fillable = ['project_id', 'name', 'required_amount','image','location','needs'];
 
    // علاقة المشروع الفرعي بالتفاصيل الخاصة به
    public function details() {
        return $this->hasMany(SubProjectDetail::class);
    }

    // علاقة التبرعات بالمشروع الفرعي
    public function donations()
    {
        return $this->belongsToMany(Donation::class, 'sub_projects_donations', 'sub_project_id', 'donation_id');
    }
    
    // حساب إجمالي التبرعات لهذا المشروع الفرعي
    /*public function getCollectedAmountAttribute() {
        return $this->donations()->sum('amount');
    }

    // حساب المبلغ المتبقي لهذا المشروع الفرعي
    public function getRemainingAmountAttribute() {
        return $this->required_amount - $this->collected_amount;
    }
}
*/


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',

        'name_en',
        'name_ar',
        'location_en',
        'location_ar',
        'needs_en',
        'needs_ar',
        'required_amount',
        'collected_amount',
        'remaining_amount',
        'image',
        'status'
    ];

    // علاقة مع التفاصيل
    public function details()
    {
        return $this->hasMany(SubProjectDetail::class);
    }

    // علاقة مع المشروع الرئيسي
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // علاقة مع التبرعات (إن كنت تستخدم جدول وسيط)
    public function donations()
    {
        return $this->belongsToMany(Donation::class, 'sub_projects_donations', 'sub_project_id', 'donation_id');
    }
    public function carts(){
        return $this->belongsToMany(Cart::class,'cart_sub_projects')->withPivot('amount');
    }
}
