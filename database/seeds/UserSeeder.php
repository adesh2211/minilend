<?php

use Illuminate\Database\Seeder;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$user = User::where(['email'=>'admin@minilend.com'])->first();
    	if(!$user){
    		$user =  User::create(['email'=>'admin@minilend.com','password'=>Hash::make('admin@123'),'name' => 'Admin']);
    	}

    	$role = Role::firstOrCreate(['name' => 'admin']);
    	$user->assignRole('admin');
    }
}
