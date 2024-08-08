<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /**
    * User Cards
    * @param 
    */
    public function applications()
    {
        return $this->hasMany('App\Model\CustomerApplication','customer_id')->select('id','application_number','status','customer_id','created_at','updated_at');
    }

    public function getPhoneNumberAttribute($value) {
        if($value){
        	$value = preg_replace('~.*(\d{3})[^\d]*(\d{3})[^\d]*(\d{4}).*~', 
                '($1) $2-$3', $value);
            return $value;
        }else{
            return $value;
        }
    }
}
