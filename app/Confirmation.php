<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Confirmation extends Model 
{

    protected $fillable = [];

    protected $visible = ['confirmation_number','subscription_id'];  

    protected $table = 'tbl_subscription_transaction_details';
    protected $primaryKey = 'subscription_id';

    

}
