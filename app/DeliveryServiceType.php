<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeliveryServiceType extends Model 
{

    protected $fillable = [];

    protected $visible = ['*'];  

    protected $table = 'tbl_delivery_service_type';
    protected $primaryKey = 'id_delivery_type';

   

    

}
