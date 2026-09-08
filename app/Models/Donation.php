<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

   
    protected $fillable = [
        'donor_id',
        'sub_project_id',
        'campaign_id',
        'amount',
    ];

    public function donor()
    {
        return $this->belongsTo(Donor::class);
    }

   
    public function subProject()
    {
        return $this->belongsTo(SubProject::class);
    }

  
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}