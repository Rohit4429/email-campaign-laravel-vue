<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    public function get_contact(){
        return $this->hasMany(Contact::class,'campaign_id','id');
    }

    public function get_user(){
        return $this->hasOne(User::class,'id','added_by');
    }
}
