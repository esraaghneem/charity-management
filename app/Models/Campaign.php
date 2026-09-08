<?php
/*

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model {
    use HasFactory;

    protected $fillable = [ 'name','image','required_amount','startDate','endDate','location','status'];
    public function donations()
    {
        return $this->belongsToMany(Donation::class, 'campaigns_donations', 'campaign_id', 'donation_id');
    }
    
}
*/
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model {
    use HasFactory;

    protected $fillable = [
        'name_en', 'name_ar',
        'image',
        'required_amount',
        'startDate',
        'endDate',
        'location_en',
        'location_ar',
        'status'
    ];

    public function donations()
    {
        return $this->belongsToMany(Donation::class, 'campaigns_donations', 'campaign_id', 'donation_id');
    }
    public function carts(){
        return $this->belongsToMany(Cart::class,'cart_campaigns')->withPivot('amount');
    }
}
