<?php
/*

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubProjectDetail extends Model {
    protected $fillable = ['sub_project_id', 'key', 'value'];

    public function subProject() {
        return $this->belongsTo(SubProject::class);
    }
}


 */


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubProjectDetail extends Model {
    protected $fillable = [
        'sub_project_id',
        'key_en', 'key_ar',
        'value_en', 'value_ar',
    ];

    public function subProject() {
        return $this->belongsTo(SubProject::class);
    }
}

 