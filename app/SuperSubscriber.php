<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SuperSubscriber extends Model 
{

    protected $fillable = [];

    protected $visible = ['subscription_id','subscriber_name'];  

    protected $table = 'tbl_super_subscription';
    protected $primaryKey = 'subscription_id';

    

}
