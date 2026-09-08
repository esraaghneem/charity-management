<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'donor_id',
    ];

    // علاقة مع نموذج المتبرع
    public function donor()
    {
        return $this->belongsTo(Donor::class);
    }
  /*  public function items()
    {
        return $this->hasMany(Cart::class);
    }
*/
public function subprojects(){

    return $this->belongsToMany(SubProject::class,'cart_sub_projects')->withPivot('amount');
}
public function campaigns(){
    return $this->belongsToMany(Campaign::class,'cart_campaigns')->withPivot('amount');
}
}
