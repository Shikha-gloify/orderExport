<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderSmsDetails extends Model 
{

    protected $fillable = [];

    protected $visible = ['pk_order_sms_id'];  

    protected $table = 'tbl_order_sms_details';
    protected $primaryKey = 'pk_order_sms_id';

    

}
