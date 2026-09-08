<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'beneficiary_id',
        'health_status_en',
        'health_status_ar',
        'monthly_income',
        'job_status_en',
        'job_status_ar',
        'is_single',
        'is_urgent',
        'support_type_en',
        'support_type_ar',
          'is_male',
        'has_family',
        'is_male_breadwinner_for_family',
        'is_female_breadwinner_for_family',
       'is_youth_without_family',
        'is_girl_without_family',
        'is_orphan',
        'is_injured',
        'is_disabled',
        'user_id',
        'total_number_of_children',
        'number_of_disabled_children',
        'needs_en',
        'needs_ar',
        'status'
        
    ];

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }
}