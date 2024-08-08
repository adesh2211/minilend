<?php

use Illuminate\Database\Seeder;
use App\User;
use Illuminate\Support\Facades\Hash;
class ChangePassword extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::where(['email'=>'info.minilend@gmail.com'])->first();
    	if($user){
    		$user->password = Hash::make('admin@123');
    		$user->save();
    		dd('changed--');
    	}
    }
}
