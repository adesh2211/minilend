<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CustomerApplication extends Model
{
    //

    public function getPhoneNumberAttribute($value) {
        if($value){
        	$phone = preg_replace('~.*(\d{3})[^\d]*(\d{3})[^\d]*(\d{4}).*~', 
                '($1)-$2-$3'." \n", $value);
            return $value;
        }else{
            return false;
        }
    }

}
