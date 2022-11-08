<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderPaymentDetails extends Model 
{

    protected $fillable = [];

    protected $visible = ['payment_type','payment_status'];  

    protected $table = 'tbl_order_payment_details';
    protected $primaryKey = 'id_order_payment_details';

    

}
