<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderZoneDetails extends Model 
{

    protected $fillable = [];

    protected $visible = ['outstationCharge','stateId','extraKilometer'];  

    protected $table = 'tbl_order_zone_details';
    protected $primaryKey = 'idOrderZone';

   

    

}
